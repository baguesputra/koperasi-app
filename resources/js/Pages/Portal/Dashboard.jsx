import AnggotaLayout from '@/Layouts/AnggotaLayout';
import { Head, Link, usePage } from '@inertiajs/react';
import { useState, useMemo, useEffect } from 'react';
import { driver } from 'driver.js';
import 'driver.js/dist/driver.css';
import {
    ArrowRight,
    CheckCircle2,
    ArrowDownCircle,
    Clock,
    XCircle,
    Info,
    CalendarDays,
    Repeat,
    Eye,
    EyeOff,
    Plus,
    TrendingUp,
    ChevronRight,
} from 'lucide-react';
import { formatRupiah } from '@/Utils/formatCurrency';
import Nominal, { usePrivasiNominal } from '@/Components/ui/Nominal';
import PwaInstallPrompt from '@/Components/ui/PwaInstallPrompt';

const statusPengajuanLabel = {
    diajukan: {
        text: 'Menunggu pemeriksaan Admin',
        step: 1,
    },
    approved_admin: {
        text: 'Menunggu pemeriksaan Bendahara',
        step: 2,
    },
    approved_bendahara: {
        text: 'Menunggu persetujuan Ketua Koperasi',
        step: 3,
    },
};

const tipeLabel = {
    perpanjang: 'Perubahan Tenor (Perpanjang)',
    percepat: 'Perubahan Tenor (Percepat)',
    lunas_total: 'Pelunasan Dipercepat',
};

function StatusRow({ icon: Icon, tone = 'quiet', title, body, href, linkLabel }) {
    const isi = (
        <>
            <span className={`pd-status__mark pd-status__mark--${tone}`}>
                <Icon size={13} strokeWidth={2.2} />
            </span>
            <span className="pd-status__text">
                <span className="pd-status__title">{title}</span>
                {body ? <span className="pd-status__body">{body}</span> : null}
            </span>
            {href ? (
                <span className="pd-status__go">
                    {linkLabel}
                    <ArrowRight size={12} aria-hidden="true" />
                </span>
            ) : null}
        </>
    );

    return (
        <li className="pd-status__row">
            {href ? (
                <Link href={href} className="pd-status__link pd-focus">
                    {isi}
                </Link>
            ) : (
                <div className="pd-status__link">{isi}</div>
            )}
        </li>
    );
}

function CaptionLimitBerjalan({ pengajuan, tampil }) {
    if (!pengajuan) return null;

    const isKetua = pengajuan.status === 'approved_bendahara';

    return (
        <span className="pd-band__pending">
            <Clock size={11} aria-hidden="true" />
            <span>
                {isKetua ? 'Menunggu Ketua (' : 'Menunggu Bendahara ('}
                <Nominal nilai={pengajuan.limit_diminta} tampil={tampil} />
                {isKetua && pengajuan.limit_disetujui_bendahara ? (
                    <span>
                        {' → '}
                        <Nominal nilai={pengajuan.limit_disetujui_bendahara} tampil={tampil} />
                    </span>
                ) : null}
                )
            </span>
        </span>
    );
}

function PetunjukTerkunci({ pesan, children }) {
    return (
        <div className="pd-lock" tabIndex={0} aria-label={pesan || undefined}>
            {children}
            {pesan && (
                <span role="tooltip" className="pd-lock__tip">
                    {pesan}
                </span>
            )}
        </div>
    );
}

function KuitansiModal({ judul, rows, paragraf, catatan, onClose }) {
    return (
        <div className="pd-modal" role="dialog" aria-modal="true" aria-labelledby="konfirmasi-title">
            <div className="pd-modal__panel">
                <div className="pd-modal__head">
                    <span className="pd-modal__mark">
                        <CheckCircle2 size={22} aria-hidden="true" />
                    </span>
                    <h2 id="konfirmasi-title">{judul}</h2>
                </div>

                <dl className="pd-modal__rows">
                    {rows.map((r) => (
                        <div key={r.label} className="pd-modal__row">
                            <dt>{r.label}</dt>
                            <dd>{r.value}</dd>
                        </div>
                    ))}
                </dl>

                <p className="pd-modal__body">{paragraf}</p>
                <p className="pd-modal__note">{catatan}</p>

                <button
                    type="button"
                    onClick={onClose}
                    className="pd-modal__close pd-focus"
                >
                    Kembali ke beranda
                </button>
            </div>
        </div>
    );
}

const percepatanParagraf = {
    percepat: 'Pengajuan pengurangan lama cicilan Anda telah kami terima dan diteruskan melalui WhatsApp Koperasi untuk diproses lebih lanjut.',
    perpanjang: 'Pengajuan penambahan lama cicilan Anda telah kami terima dan diteruskan melalui WhatsApp Koperasi untuk diproses lebih lanjut.',
    lunas_total: 'Pengajuan pelunasan dipercepat Anda telah kami terima dan diteruskan melalui WhatsApp Koperasi untuk diproses lebih lanjut.',
};

function percepatanRows(p, tampil) {
    const rows = [
        { label: 'Pinjaman', value: <Nominal nilai={p.nominal} tampil={tampil} /> },
        { label: 'Tenor saat ini', value: `${p.tenor_lama} bulan` },
    ];

    if (p.tipe === 'lunas_total') {
        rows.push({ label: 'Pelunasan', value: 'Seluruh sisa pokok ditambah bunga 1 bulan' });
    } else {
        const arah = p.tipe === 'percepat' ? 'Tenor dikurangi' : 'Tenor ditambah';
        rows.push({ label: arah, value: `${p.tenor_lama} → ${p.tenor_baru} bulan` });
    }

    return rows;
}

export default function Dashboard({
    anggota,
    totalSimpanan,
    simpananPokok,
    simpananWajib,
    limitMaksimal,
    limitTersedia,
    sisaAngsuranAktif,
    cicilanPokokAktif,
    pinjamanAktif,
    pinjamanAktifList,
    pinjamanAktifCount,
    pengajuanPercepatanMenunggu,
    pengajuanBerjalan,
    pengajuanLimitBerjalan,
    pengajuanDitolak,
    resignMenunggu,
    angsuranBerikutnya,
    bisaAjukan,
    alasanTidakBisa,
    riwayatGabungan,
    tabelTenor,
    settingSimpanan,
}) {
    const aggregateTotalNominal = (pinjamanAktifList || []).reduce(
        (sum, p) => sum + (p.nominal || 0),
        0
    );

    const aggregateTotalSisaAngsuran = (pinjamanAktifList || []).reduce(
        (sum, p) => sum + (p.sisa_angsuran || 0),
        0
    );

    const aggregateTotalAngsuran = (pinjamanAktifList || []).reduce(
        (sum, p) => sum + (p.total_angsuran || 0),
        0
    );

    const aggregateSisaTotalBayar = (pinjamanAktifList || []).reduce(
        (sum, p) => sum + (p.sisa_total_bayar || 0),
        0
    );

    const cicilanPerBulan =
        aggregateTotalSisaAngsuran > 0
            ? Math.round(aggregateSisaTotalBayar / aggregateTotalSisaAngsuran)
            : 0;

    const progress =
        aggregateTotalAngsuran > 0
            ? Math.round(
                  ((aggregateTotalAngsuran - aggregateTotalSisaAngsuran) /
                      aggregateTotalAngsuran) *
                      100
              )
            : 0;

    // =========================================================
    // BUSINESS ELIGIBILITY RULES
    // =========================================================
    // 1. Ajukan Pinjaman: hanya bisa jika eligibel, tidak ada pengajuan berjalan, limit tersedia > 0, dan tidak ada resign shortfall.
    const bolehAjukanPinjaman = Boolean(bisaAjukan && !pengajuanBerjalan && limitTersedia > 0 && !resignMenunggu);

    // 2. Tambah Limit: hanya bisa jika eligibel, tidak ada pengajuan pinjaman/limit berjalan, dan tidak ada resign shortfall.
    const bolehAjukanLimit = Boolean(bisaAjukan && !pengajuanBerjalan && !pengajuanLimitBerjalan && !resignMenunggu);

    // 3. Ubah Tenor: hanya jika ada pinjaman aktif, tidak ada pengajuan berjalan, tidak ada percepatan menunggu, dan ada pinjaman yang belum pakai percepatan.
    const bolehUbahTenor = Boolean(
        pinjamanAktifCount > 0 &&
        !pengajuanBerjalan &&
        pengajuanPercepatanMenunggu.length === 0 &&
        pinjamanAktifList.some((p) => !p.sudah_pakai_percepatan)
    );

    // Alasan penolakan per menu, dipakai sebagai tooltip saat kartu nonaktif.
    const pesanTerkunciPinjaman = pengajuanBerjalan
        ? 'Pengajuan pinjaman Anda masih diproses.'
        : limitTersedia <= 0
        ? 'Plafon pinjaman Anda sudah terpakai penuh.'
        : resignMenunggu
        ? 'Penyelesaian resign Anda sedang menunggu pelunasan.'
        : alasanTidakBisa || 'Belum memenuhi ketentuan koperasi.';

    const pesanTerkunciLimit = pengajuanBerjalan
        ? 'Pengajuan kenaikan limit tidak dapat dibuat karena pengajuan pinjaman Anda masih diproses.'
        : pengajuanLimitBerjalan
        ? 'Pengajuan kenaikan limit Anda masih diverifikasi.'
        : resignMenunggu
        ? 'Penyelesaian resign Anda sedang menunggu pelunasan.'
        : alasanTidakBisa || 'Belum memenuhi ketentuan pengajuan limit.';

    const pesanTerkunciTenor = pengajuanBerjalan
        ? 'Perubahan tenor tidak dapat diajukan karena pengajuan pinjaman Anda masih diproses.'
        : pengajuanPercepatanMenunggu.length > 0
        ? 'Pengajuan perubahan tenor Anda masih diproses.'
        : pinjamanAktifCount === 0
        ? 'Ubah tenor hanya tersedia saat Anda memiliki pinjaman aktif.'
        : 'Hak perubahan tenor untuk pinjaman ini sudah digunakan.';

    const { auth, flash } = usePage().props;
    const anggotaAktif = (auth?.user?.anggota_status ?? 'aktif') === 'aktif';
    const initial = anggota?.nama?.charAt(0)?.toUpperCase() ?? '?';

    const terkirim = flash.pinjamanTerkirim;
    const percepatanTerkirim = flash.percepatanTerkirim;
    const limitTerkirim = flash.limitTerkirim;
    const [konfirmasiDitutup, setKonfirmasiDitutup] = useState(false);
    const [percepatanDitutup, setPercepatanDitutup] = useState(false);
    const [limitDitutup, setLimitDitutup] = useState(false);
    const [nominalTampil, alihNominal] = usePrivasiNominal();
    const [fotoGagal, setFotoGagal] = useState(false);

    // Plafon yang sudah terpakai = plafon maksimal dikurangi sisa yang masih bisa diajukan.
    const plafonTerpakai = Math.max(0, (limitMaksimal || 0) - (limitTersedia || 0));
    const persenPlafon =
        limitMaksimal > 0 ? Math.min(100, Math.round((plafonTerpakai / limitMaksimal) * 100)) : 0;

    const adaStatus =
        Boolean(resignMenunggu) ||
        Boolean(pengajuanLimitBerjalan) ||
        (pinjamanAktifCount > 0 && pengajuanPercepatanMenunggu.length > 0) ||
        (!bisaAjukan && !pengajuanBerjalan) ||
        (limitTersedia <= 0 && bisaAjukan && !pengajuanBerjalan) ||
        Boolean(pengajuanDitolak && !pengajuanBerjalan);

    // Tenor ladder: adjacent nominal bands that share the same ceiling collapse
    // into one rung, so the rule reads as five steps instead of ten identical rows.
    const tanggaTenor = useMemo(() => {
        const rungs = [];
        (tabelTenor || []).forEach((t) => {
            const tenor = Number(t.tenor_maksimal_bulan);
            const terakhir = rungs[rungs.length - 1];
            if (terakhir && terakhir.tenor === tenor) {
                terakhir.nominal_max = Number(t.nominal_max);
            } else {
                rungs.push({
                    nominal_min: Number(t.nominal_min),
                    nominal_max: Number(t.nominal_max),
                    tenor,
                });
            }
        });
        return rungs;
    }, [tabelTenor]);

    const tenorTertinggi = tanggaTenor.reduce((max, r) => Math.max(max, r.tenor), 0);

    useEffect(() => {
        const tourKey = `koperasi.dashboard-tour.dismissed.${auth?.user?.id ?? 'guest'}`;
        let activeTour = null;

        const startTour = () => {
            const steps = [
                { element: '[data-tour="member-card"]', popover: { title: 'Kartu Anggota', description: 'Identitas, status keanggotaan, dan status aktif Anda tampil di sini.' } },
                { element: '[data-tour="privacy-toggle"]', popover: { title: 'Sembunyikan Saldo', description: 'Tombol ini menyembunyikan atau menampilkan seluruh nominal keuangan di Dashboard.' } },
                { element: '[data-tour="financial-summary"]', popover: { title: 'Ringkasan Keuangan', description: 'Pantau total simpanan dan limit pinjaman yang masih tersedia.' } },
                { element: '[data-tour="quick-actions"]', popover: { title: 'Menu Utama', description: 'Ajukan pinjaman, tambah limit, ubah tenor, atau buka panduan lengkap.' } },
                ...(pengajuanBerjalan ? [{ element: '[data-tour="application-tracker"]', popover: { title: 'Pelacakan Pengajuan', description: 'Pantau proses pemeriksaan Admin, Bendahara, hingga persetujuan Ketua.' } }] : []),
                { element: '[data-tour="loan-progress"]', popover: { title: 'Progres Pinjaman', description: 'Pantau jumlah angsuran terbayar, sisa angsuran, cicilan bulanan, dan persentase pelunasan.' } },
                { element: '[data-tour="savings-card"]', popover: { title: 'Rincian Simpanan', description: 'Lihat total simpanan beserta komposisi Simpanan Wajib dan Simpanan Pokok. Ketentuan tarif dapat dibuka di bagian ini.' } },
                { element: '[data-tour="history-summary"]', popover: { title: 'Aktivitas & Riwayat', description: 'Lihat transaksi simpanan dan pembayaran terakhir.' } },
                { element: '[data-tour="plafon-rules"]', popover: { title: 'Ketentuan & Plafon', description: 'Lihat batas nominal pinjaman dan tenor maksimal. Plafon mengikuti ketentuan koperasi serta diproses melalui verifikasi Admin, Bendahara, lalu persetujuan Ketua.' } },
            ].filter((step) => document.querySelector(step.element));

            if (!steps.length) return;
            activeTour = driver({
                showProgress: true,
                allowClose: true,
                overlayClickBehavior: 'close',
                nextBtnText: 'Lanjut',
                prevBtnText: 'Kembali',
                doneBtnText: 'Selesai',
                onDestroyStarted: () => {
                    localStorage.setItem(tourKey, '1');
                    sessionStorage.removeItem('koperasi.dashboard-tour.pin-created');
                    activeTour?.destroy();
                },
            });
            activeTour.setSteps(steps);
            activeTour.drive();
        };

        const requestHandler = () => startTour();
        window.addEventListener('start-dashboard-tour', requestHandler);

        const pinCreated = sessionStorage.getItem('koperasi.dashboard-tour.pin-created') === '1';
        const firstVisit = !localStorage.getItem(tourKey) && auth?.user?.has_pin;
        const timer = firstVisit || pinCreated ? window.setTimeout(startTour, 800) : null;

        return () => {
            window.removeEventListener('start-dashboard-tour', requestHandler);
            if (timer) window.clearTimeout(timer);
            activeTour?.destroy();
        };
    }, [auth?.user?.id, pengajuanBerjalan]);

    const salamWaktu = useMemo(() => {
        const jam = new Date().getHours();
        if (jam >= 4 && jam < 11) return 'Selamat Pagi';
        if (jam >= 11 && jam < 15) return 'Selamat Siang';
        if (jam >= 15 && jam < 18) return 'Selamat Sore';
        return 'Selamat Malam';
    }, []);

    return (
        <AnggotaLayout>
            <Head title="Portal Anggota" />

            {/* =====================================================
                FLASH CONFIRMATION MODALS
            ====================================================== */}
            {terkirim && !konfirmasiDitutup && (
                <KuitansiModal
                    judul="Pengajuan berhasil terkirim"
                    rows={[
                        {
                            label: 'Nominal pinjaman',
                            value: <Nominal nilai={terkirim.nominal} tampil={nominalTampil} />,
                        },
                        { label: 'Lama cicilan', value: `${terkirim.tenor_bulan} bulan` },
                    ]}
                    paragraf="Pengajuan pinjaman Anda telah kami terima dan diteruskan melalui WhatsApp Koperasi untuk diproses lebih lanjut."
                    catatan="Selama masa peninjauan, tidak ada tindakan yang perlu Anda lakukan. Apabila pengajuan disetujui, pemberitahuan akan disampaikan melalui WhatsApp. Status pengajuan juga dapat dipantau pada halaman Beranda."
                    onClose={() => setKonfirmasiDitutup(true)}
                />
            )}

            {percepatanTerkirim && !percepatanDitutup && (
                <KuitansiModal
                    judul="Pengajuan berhasil terkirim"
                    rows={percepatanRows(percepatanTerkirim, nominalTampil)}
                    paragraf={percepatanParagraf[percepatanTerkirim.tipe]}
                    catatan="Selama masa peninjauan, tidak ada tindakan yang perlu Anda lakukan. Apabila pengajuan disetujui, pemberitahuan beserta bulan mulai berlakunya akan disampaikan melalui WhatsApp. Status pengajuan juga dapat dipantau pada halaman Beranda."
                    onClose={() => setPercepatanDitutup(true)}
                />
            )}

            {limitTerkirim && !limitDitutup && (
                <KuitansiModal
                    judul="Pengajuan berhasil terkirim"
                    rows={[
                        {
                            label: 'Limit yang diminta',
                            value: <Nominal nilai={limitTerkirim.diminta} tampil={nominalTampil} />,
                        },
                        {
                            label: 'Limit Anda saat ini',
                            value: <Nominal nilai={limitTerkirim.limit_saat_ini} tampil={nominalTampil} />,
                        },
                    ]}
                    paragraf="Pengajuan penambahan limit Anda telah kami terima dan diteruskan melalui WhatsApp Koperasi untuk diproses lebih lanjut."
                    catatan="Penambahan limit diverifikasi Bendahara lalu diputuskan final oleh Ketua Koperasi. Nominal final bisa berbeda dari yang Anda minta. Apabila disetujui, pemberitahuan akan disampaikan melalui WhatsApp dan limit baru aktif untuk pinjaman berikutnya."
                    onClose={() => setLimitDitutup(true)}
                />
            )}

            <div className="pd">
                <PwaInstallPrompt />

                {/* =================================================
                    GREETING
                ================================================== */}
                <div>
                    <h1>
                        {salamWaktu}, {anggota.nama}
                    </h1>
                    <p className="pd-head__line">
                        Pantau saldo simpanan dan kelola fasilitas pinjaman Anda di satu tempat.
                    </p>
                </div>

                {/* =================================================
                    STATUS STRIP — only the conditions that are true
                ================================================== */}
                {adaStatus && (
                    <section className="pd-status" aria-label="Status akun Anda">
                        <ul className="pd-status__list">
                            {resignMenunggu && (
                                <StatusRow
                                    tone="warn"
                                    icon={Clock}
                                    title={
                                        <>
                                            Penyelesaian resign menunggu pelunasan akhir{' '}
                                            <Nominal nilai={resignMenunggu.shortfall} tampil={nominalTampil} />
                                            {resignMenunggu.jatuh_tempo
                                                ? ` sebelum ${resignMenunggu.jatuh_tempo}`
                                                : ''}
                                            .
                                        </>
                                    }
                                    body="Selesaikan melalui Bendahara di menu Konfirmasi Angsuran. Pengajuan pinjaman dan limit baru dikunci sampai lunas."
                                />
                            )}

                            {pengajuanLimitBerjalan && (
                                <StatusRow
                                    tone="warn"
                                    icon={Clock}
                                    href={route('portal.pengajuan-limit.create')}
                                    linkLabel="Tinjau"
                                    title={
                                        <>
                                            Penambahan limit{' '}
                                            <Nominal
                                                nilai={pengajuanLimitBerjalan.limit_diminta}
                                                tampil={nominalTampil}
                                            />{' '}
                                            sedang diverifikasi
                                        </>
                                    }
                                    body={
                                        <>
                                            {pengajuanLimitBerjalan.status === 'approved_bendahara'
                                                ? 'Menunggu persetujuan Ketua Koperasi'
                                                : 'Menunggu pemeriksaan Bendahara'}
                                            {pengajuanLimitBerjalan.status === 'approved_bendahara' &&
                                            pengajuanLimitBerjalan.limit_disetujui_bendahara ? (
                                                <>
                                                    , usulan Bendahara{' '}
                                                    <Nominal
                                                        nilai={
                                                            pengajuanLimitBerjalan.limit_disetujui_bendahara
                                                        }
                                                        tampil={nominalTampil}
                                                    />
                                                </>
                                            ) : null}
                                            . Diajukan {pengajuanLimitBerjalan.tanggal_pengajuan}. Anda tetap dapat
                                            meminjam hingga plafon aktif saat ini{' '}
                                            <Nominal nilai={limitMaksimal} tampil={nominalTampil} />.
                                        </>
                                    }
                                />
                            )}

                            {pinjamanAktifCount > 0 && pengajuanPercepatanMenunggu.length > 0 && (
                                <StatusRow
                                    tone="warn"
                                    icon={Repeat}
                                    title="Pengajuan perubahan tenor atau pelunasan dalam proses"
                                    body={pengajuanPercepatanMenunggu.map((pp) => (
                                        <span key={pp.id} className="pd-status__item">
                                            {tipeLabel[pp.tipe] || pp.tipe}. Pinjaman{' '}
                                            <Nominal nilai={pp.pinjaman_nominal} tampil={nominalTampil} />,{' '}
                                            {pp.tenor_lama} menjadi {pp.tenor_baru ?? 'lunas'} bulan.{' '}
                                            {statusPengajuanLabel[pp.status]?.text ?? pp.status}.
                                        </span>
                                    ))}
                                />
                            )}

                            {!bisaAjukan && !pengajuanBerjalan && (
                                <StatusRow
                                    tone="warn"
                                    icon={Info}
                                    title="Pengajuan pinjaman belum memenuhi ketentuan"
                                    body={
                                        <>
                                            {alasanTidakBisa ||
                                                'Anda belum dapat mengajukan pinjaman saat ini sesuai ketentuan koperasi.'}
                                            {sisaAngsuranAktif > 2 && limitTersedia > 0 ? (
                                                <>
                                                    {' '}
                                                    Sisa plafon yang belum terpakai{' '}
                                                    <Nominal nilai={limitTersedia} tampil={nominalTampil} /> baru
                                                    dapat dipakai setelah angsuran tersisa maksimal 2 bulan.
                                                </>
                                            ) : null}
                                        </>
                                    }
                                />
                            )}

                            {limitTersedia <= 0 && bisaAjukan && !pengajuanBerjalan && (
                                <StatusRow
                                    tone="info"
                                    icon={Info}
                                    title="Plafon pinjaman Anda saat ini terpakai seluruhnya"
                                    body="Pengajuan pinjaman baru dibuka kembali setelah angsuran berjalan tersisa maksimal 2 bulan, atau Anda dapat mengajukan penambahan limit melalui menu Tambah limit."
                                />
                            )}

                            {pengajuanDitolak && !pengajuanBerjalan && (
                                <StatusRow
                                    tone="danger"
                                    icon={XCircle}
                                    title={
                                        <>
                                            Pengajuan pinjaman{' '}
                                            <Nominal nilai={pengajuanDitolak.nominal} tampil={nominalTampil} />{' '}
                                            ditolak
                                        </>
                                    }
                                    body={
                                        <>
                                            {pengajuanDitolak.catatan
                                                ? `Alasan: ${pengajuanDitolak.catatan}. `
                                                : ''}
                                            Anda dapat mengajukan kembali setelah menyesuaikan nominal atau
                                            persyaratan.
                                        </>
                                    }
                                />
                            )}
                        </ul>
                    </section>
                )}

                {/* =================================================
                    MEMBER BAND — the signature
                ================================================== */}
                <section data-tour="member-card" className="pd-band">
                    <div className="pd-band__main">
                        <div className="pd-band__who">
                            {anggota.foto_url && !fotoGagal ? (
                                <img
                                    src={anggota.foto_url}
                                    alt={anggota.nama}
                                    loading="lazy"
                                    referrerPolicy="no-referrer"
                                    onError={() => setFotoGagal(true)}
                                    className="pd-band__avatar"
                                />
                            ) : (
                                <span className="pd-band__avatar">{initial}</span>
                            )}
                            <div className="pd-band__id">
                                <p className="pd-band__name">{anggota.nama}</p>
                                <p className="pd-band__nip">NIP {anggota.no_karyawan}</p>
                                {anggota.lama_keanggotaan_label ? (
                                    <p className="pd-band__meta">
                                        Anggota {anggota.lama_keanggotaan_label}
                                    </p>
                                ) : null}
                            </div>
                        </div>

                    <div data-tour="financial-summary" className="pd-band__figures">
                        <div className="pd-band__figure">
                            <p className="pd-band__label">Total simpanan</p>
                            <p className="pd-band__value">
                                <Nominal nilai={totalSimpanan} tampil={nominalTampil} />
                            </p>
                            <p className="pd-band__note">Simpanan pokok dan wajib</p>
                        </div>

                        <div className="pd-band__figure pd-band__figure--end">
                            <p className="pd-band__label">Sisa plafon</p>
                            <p className="pd-band__value pd-band__value--accent">
                                <Nominal nilai={limitTersedia} tampil={nominalTampil} />
                            </p>
                            {pengajuanLimitBerjalan ? (
                                <p className="pd-band__note">
                                    <CaptionLimitBerjalan
                                        pengajuan={pengajuanLimitBerjalan}
                                        tampil={nominalTampil}
                                    />
                                </p>
                            ) : null}
                        </div>
                    </div>
                    </div>

                    {limitMaksimal > 0 && (
                        <div className="pd-band__rail">
                            <div
                                className="pd-rail pd-rail--night"
                                role="img"
                                aria-label={`Plafon terpakai ${persenPlafon} persen`}
                            >
                                <div
                                    className="pd-rail__fill"
                                    style={{ width: `${persenPlafon}%` }}
                                />
                                <span className="pd-rail__tick" style={{ left: '25%' }} aria-hidden="true" />
                                <span className="pd-rail__tick" style={{ left: '50%' }} aria-hidden="true" />
                                <span className="pd-rail__tick" style={{ left: '75%' }} aria-hidden="true" />
                            </div>
                            <div className="pd-railcap">
                                <span>
                                    Terpakai <Nominal nilai={plafonTerpakai} tampil={nominalTampil} /> dari{' '}
                                    <Nominal nilai={limitMaksimal} tampil={nominalTampil} />
                                </span>
                                <span>{persenPlafon}%</span>
                            </div>
                            <div className="pd-band__tools">
                                <span className="pd-band__badge">
                                    {anggotaAktif ? 'Aktif' : 'Nonaktif'}
                                </span>
                                <button
                                    data-tour="privacy-toggle"
                                    type="button"
                                    onClick={alihNominal}
                                    aria-label={nominalTampil ? 'Sembunyikan saldo' : 'Tampilkan saldo'}
                                    title={nominalTampil ? 'Sembunyikan saldo' : 'Tampilkan saldo'}
                                    className="pd-band__toggle pd-focus"
                                >
                                    {nominalTampil ? <EyeOff size={13} aria-hidden="true" /> : <Eye size={13} aria-hidden="true" />}
                                    <span className="pd-band__toggle-label">
                                        {nominalTampil ? 'Sembunyikan' : 'Tampilkan'}
                                    </span>
                                </button>
                            </div>
                        </div>
                    )}
                </section>

                {/* =================================================
                    ACTION TOOLBAR
                ================================================== */}
                <nav data-tour="quick-actions" className="pd-actions" aria-label="Aksi cepat">
                    {bolehAjukanPinjaman ? (
                        <Link href={route('portal.pinjaman.create')} className="pd-action pd-focus">
                            <span className="pd-action__icon">
                                <Plus size={17} strokeWidth={2.3} aria-hidden="true" />
                            </span>
                            <span className="pd-action__text">
                                <span className="pd-action__label">Ajukan pinjaman</span>
                                <span className="pd-action__state">Plafon siap digunakan</span>
                            </span>
                            <span className="pd-action__flag">Tersedia</span>
                        </Link>
                    ) : (
                        <PetunjukTerkunci pesan={pesanTerkunciPinjaman}>
                            <div className="pd-action" aria-disabled="true">
                                <span className="pd-action__icon">
                                    <Plus size={17} strokeWidth={2.3} aria-hidden="true" />
                                </span>
                                <span className="pd-action__text">
                                    <span className="pd-action__label">Ajukan pinjaman</span>
                                    <span className="pd-action__state">
                                        {pengajuanBerjalan
                                            ? 'Dalam proses approval'
                                            : limitTersedia <= 0
                                            ? 'Plafon limit habis'
                                            : 'Belum memenuhi syarat'}
                                    </span>
                                </span>
                                <span className="pd-action__flag">
                                    {pengajuanBerjalan
                                        ? 'Diproses'
                                        : limitTersedia <= 0
                                        ? 'Plafon Rp0'
                                        : 'Terkunci'}
                                </span>
                            </div>
                        </PetunjukTerkunci>
                    )}

                    {bolehAjukanLimit ? (
                        <Link
                            href={route('portal.pengajuan-limit.create')}
                            className="pd-action pd-action--limit pd-focus"
                        >
                            <span className="pd-action__icon">
                                <TrendingUp size={17} strokeWidth={2.3} aria-hidden="true" />
                            </span>
                            <span className="pd-action__text">
                                <span className="pd-action__label">Tambah limit</span>
                                <span className="pd-action__state">Ajukan kenaikan plafon</span>
                            </span>
                            <span className="pd-action__flag">Tersedia</span>
                        </Link>
                    ) : (
                        <PetunjukTerkunci pesan={pesanTerkunciLimit}>
                            <div className="pd-action pd-action--limit" aria-disabled="true">
                                <span className="pd-action__icon">
                                    <TrendingUp size={17} strokeWidth={2.3} aria-hidden="true" />
                                </span>
                                <span className="pd-action__text">
                                    <span className="pd-action__label">Tambah limit</span>
                                    <span className="pd-action__state">
                                        {pengajuanBerjalan
                                            ? 'Pengajuan diproses'
                                            : pengajuanLimitBerjalan
                                            ? 'Sedang diverifikasi'
                                            : 'Belum memenuhi'}
                                    </span>
                                </span>
                                <span className="pd-action__flag">
                                    {pengajuanBerjalan || pengajuanLimitBerjalan
                                        ? 'Diproses'
                                        : 'Terkunci'}
                                </span>
                            </div>
                        </PetunjukTerkunci>
                    )}

                    {bolehUbahTenor ? (
                        <Link
                            href={route('portal.percepatan.create')}
                            className="pd-action pd-action--tenor pd-focus"
                        >
                            <span className="pd-action__icon">
                                <Repeat size={17} strokeWidth={2.2} aria-hidden="true" />
                            </span>
                            <span className="pd-action__text">
                                <span className="pd-action__label">Ubah tenor</span>
                                <span className="pd-action__state">Percepat atau perpanjang</span>
                            </span>
                            <span className="pd-action__flag">Tersedia</span>
                        </Link>
                    ) : (
                        <PetunjukTerkunci pesan={pesanTerkunciTenor}>
                            <div className="pd-action pd-action--tenor" aria-disabled="true">
                                <span className="pd-action__icon">
                                    <Repeat size={17} strokeWidth={2.2} aria-hidden="true" />
                                </span>
                                <span className="pd-action__text">
                                    <span className="pd-action__label">Ubah tenor</span>
                                    <span className="pd-action__state">
                                        {pengajuanBerjalan
                                            ? 'Pengajuan diproses'
                                            : pengajuanPercepatanMenunggu.length > 0
                                            ? 'Sedang diproses'
                                            : pinjamanAktifCount === 0
                                            ? 'Khusus pinjaman aktif'
                                            : 'Hak telah digunakan'}
                                    </span>
                                </span>
                                <span className="pd-action__flag">
                                    {pengajuanBerjalan || pengajuanPercepatanMenunggu.length > 0
                                        ? 'Diproses'
                                        : pinjamanAktifCount === 0
                                        ? 'Nonaktif'
                                        : 'Sudah dipakai'}
                                </span>
                            </div>
                        </PetunjukTerkunci>
                    )}

                    <button
                        type="button"
                        onClick={() => window.dispatchEvent(new CustomEvent('open-panduan'))}
                        className="pd-action pd-action--guide pd-focus"
                    >
                        <span className="pd-action__icon">
                            <Info size={17} strokeWidth={2.2} aria-hidden="true" />
                        </span>
                        <span className="pd-action__text">
                            <span className="pd-action__label">Panduan</span>
                            <span className="pd-action__state">Aturan koperasi</span>
                        </span>
                    </button>
                </nav>

                {/* =================================================
                    APPLICATION TRACKER
                ================================================== */}
                {pengajuanBerjalan &&
                    (() => {
                        const currentStep =
                            pengajuanBerjalan.status === 'approved_bendahara'
                                ? 3
                                : pengajuanBerjalan.status === 'approved_admin'
                                ? 2
                                : 1;

                        const langkah = [
                            {
                                nama: 'Admin',
                                selesai: currentStep > 1,
                                kini: currentStep === 1,
                                selesaiText: 'Selesai diverifikasi',
                                kiniText: 'Sedang diperiksa',
                                tungguText: '',
                            },
                            {
                                nama: 'Bendahara',
                                selesai: currentStep > 2,
                                kini: currentStep === 2,
                                selesaiText: 'Selesai diverifikasi',
                                kiniText: 'Sedang diverifikasi',
                                tungguText: 'Menunggu Admin',
                            },
                            {
                                nama: 'Ketua Koperasi',
                                selesai: false,
                                kini: currentStep === 3,
                                selesaiText: '',
                                kiniText: 'Tahap persetujuan',
                                tungguText: 'Menunggu Bendahara',
                            },
                        ];

                        return (
                            <section data-tour="application-tracker" className="pd-card">
                                <div className="pd-card__head">
                                    <div>
                                        <h2>Pengajuan pinjaman sedang diproses</h2>
                                        {pengajuanBerjalan.tanggal_pengajuan ? (
                                            <p className="pd-card__note">
                                                Diajukan {pengajuanBerjalan.tanggal_pengajuan}
                                            </p>
                                        ) : null}
                                    </div>
                                    <Link href={route('portal.riwayat')} className="pd-link pd-focus">
                                        Lihat di riwayat
                                        <ArrowRight size={13} aria-hidden="true" />
                                    </Link>
                                </div>

                                <p className="pd-figure">
                                    <Nominal nilai={pengajuanBerjalan.nominal} tampil={nominalTampil} />
                                </p>

                                <ol className="pd-steps">
                                    {langkah.map((l) => {
                                        const status = l.selesai
                                            ? l.selesaiText
                                            : l.kini
                                            ? l.kiniText
                                            : l.tungguText;

                                        return (
                                            <li
                                                key={l.nama}
                                                className={`pd-step ${
                                                    l.selesai
                                                        ? 'pd-step--done'
                                                        : l.kini
                                                        ? 'pd-step--now'
                                                        : ''
                                                }`}
                                            >
                                                <span className="pd-step__label">
                                                    {l.selesai && <CheckCircle2 size={13} aria-hidden="true" />}
                                                    {l.kini && <Clock size={13} aria-hidden="true" />}
                                                    {l.nama}
                                                </span>
                                                <span className="pd-step__state">{status}</span>
                                            </li>
                                        );
                                    })}
                                </ol>

                                <p className="pd-card__foot">
                                    {statusPengajuanLabel[pengajuanBerjalan.status]?.text ??
                                        'Menunggu pemeriksaan'}
                                    .
                                    {pengajuanBerjalan.nominal_disetujui_bendahara ? (
                                        <>
                                            {' '}
                                            Usulan Bendahara{' '}
                                            <strong>
                                                <Nominal
                                                    nilai={pengajuanBerjalan.nominal_disetujui_bendahara}
                                                    tampil={nominalTampil}
                                                />
                                            </strong>
                                            .
                                        </>
                                    ) : null}{' '}
                                    Pengajuan pinjaman baru dikunci hingga proses ini selesai diputuskan.
                                </p>
                            </section>
                        );
                    })()}

                {/* =================================================
                    TWO COLUMNS
                ================================================== */}
                <div className="pd-cols">
                    <div className="pd-col">
                        {/* ---------------- LOAN ---------------- */}
                        {pinjamanAktifCount > 0 ? (
                            <section data-tour="loan-progress" className="pd-card">
                                <div className="pd-card__head">
                                    <div>
                                        <h2>
                                            Pinjaman berjalan
                                            {pinjamanAktifCount > 1
                                                ? ` (${pinjamanAktifCount} pinjaman)`
                                                : ''}
                                        </h2>
                                        <p className="pd-card__note">Sisa pokok dan bunga</p>
                                    </div>
                                    <span className="pd-chip">{progress}% lunas</span>
                                </div>

                                <p className="pd-figure">
                                    <Nominal nilai={aggregateSisaTotalBayar} tampil={nominalTampil} />
                                </p>

                                <div>
                                    <div className="pd-rail">
                                        <div
                                            className="pd-rail__fill"
                                            style={{ width: `${progress}%` }}
                                        />
                                    </div>
                                    <div className="pd-railcap">
                                        <span>
                                            {aggregateTotalAngsuran - aggregateTotalSisaAngsuran}{' '}
                                            angsuran terbayar
                                        </span>
                                        <span>Sisa {aggregateTotalSisaAngsuran} angsuran</span>
                                    </div>
                                </div>

                                <dl className="pd-rows">
                                    <div className="pd-row">
                                        <dt>Cicilan per bulan</dt>
                                        <dd>
                                            <Nominal nilai={cicilanPerBulan} tampil={nominalTampil} />
                                        </dd>
                                    </div>
                                    <div className="pd-row">
                                        <dt>Total pinjaman awal</dt>
                                        <dd>
                                            <Nominal nilai={aggregateTotalNominal} tampil={nominalTampil} />
                                        </dd>
                                    </div>
                                </dl>

                                {angsuranBerikutnya && (
                                    <Link href={route('portal.riwayat')} className="pd-due pd-focus">
                                        <span className="pd-due__icon">
                                            <CalendarDays size={15} aria-hidden="true" />
                                        </span>
                                        <span className="pd-due__text">
                                            <span className="pd-due__label">Angsuran berikutnya</span>
                                            <span className="pd-due__value">
                                                Ke-{angsuranBerikutnya.cicilan_ke}, {' '}
                                                {angsuranBerikutnya.tanggal_jatuh_tempo}
                                            </span>
                                            <span className="pd-due__note">
                                                Dipotong via payroll atau transfer rutin
                                            </span>
                                        </span>
                                        <span className="pd-due__amount">
                                            <span className="pd-due__label">Total bayar</span>
                                            <span>
                                                <Nominal
                                                    nilai={angsuranBerikutnya.total_bayar}
                                                    tampil={nominalTampil}
                                                />
                                            </span>
                                        </span>
                                        <ArrowRight size={15} className="pd-due__go" aria-hidden="true" />
                                    </Link>
                                )}

                                {pengajuanPercepatanMenunggu.length === 0 &&
                                    pinjamanAktifList.some((p) => p.sudah_pakai_percepatan) && (
                                        <p className="pd-card__foot">
                                            Hak perubahan tenor atau pelunasan dipercepat telah digunakan
                                            pada salah satu pinjaman berjalan.
                                        </p>
                                    )}
                            </section>
                        ) : (
                            !pengajuanBerjalan && (
                                <p className="pd-quiet">
                                    Belum ada pinjaman aktif. Plafon{' '}
                                    <Nominal nilai={limitMaksimal} tampil={nominalTampil} /> siap
                                    diajukan lewat menu di atas.
                                </p>
                            )
                        )}

                        {/* ---------------- ACTIVITY ---------------- */}
                        <section data-tour="history-summary" className="pd-card">
                            <div className="pd-card__head">
                                <div>
                                    <h2>Aktivitas terbaru</h2>
                                    <p className="pd-card__note">
                                        Transaksi simpanan dan pembayaran terakhir
                                    </p>
                                </div>
                                <Link href={route('portal.riwayat')} className="pd-link pd-focus">
                                    Lihat riwayat
                                    <ArrowRight size={13} aria-hidden="true" />
                                </Link>
                            </div>

                            {riwayatGabungan.length === 0 ? (
                                <p className="pd-empty">Belum ada aktivitas transaksi.</p>
                            ) : (
                                <ul className="pd-acts">
                                    {riwayatGabungan.map((item, i) => (
                                        <li key={i} className="pd-act">
                                            <span
                                                className={`pd-act__mark ${
                                                    item.tipe === 'simpanan' ? 'pd-act__mark--in' : ''
                                                }`}
                                            >
                                                {item.tipe === 'simpanan' ? (
                                                    <ArrowDownCircle size={14} aria-hidden="true" />
                                                ) : (
                                                    <CheckCircle2 size={14} aria-hidden="true" />
                                                )}
                                            </span>
                                            <span className="pd-act__text">
                                                <span className="pd-act__label">{item.label}</span>
                                                <span className="pd-act__date">
                                                    {item.tanggal_format}
                                                </span>
                                            </span>
                                            <span className="pd-act__amount">
                                                <Nominal
                                                    nilai={item.nominal}
                                                    tampil={nominalTampil}
                                                />
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </section>
                    </div>

                    <div className="pd-col">
                        {/* ---------------- SIMPANAN ---------------- */}
                        <section data-tour="savings-card" className="pd-card">
                            <div className="pd-card__head">
                                <div>
                                    <h2>Rincian simpanan</h2>
                                    <p className="pd-card__note">
                                        Komposisi dari total{' '}
                                        <Nominal nilai={totalSimpanan} tampil={nominalTampil} />
                                    </p>
                                </div>
                            </div>

                            {totalSimpanan > 0 && (
                                <>
                                    <div className="pd-split">
                                        <div
                                            className="pd-split__wajib"
                                            style={{
                                                width: `${Math.round(
                                                    (simpananWajib / totalSimpanan) * 100
                                                )}%`,
                                            }}
                                        />
                                        <div
                                            className="pd-split__pokok"
                                            style={{
                                                width: `${Math.round(
                                                    (simpananPokok / totalSimpanan) * 100
                                                )}%`,
                                            }}
                                        />
                                    </div>
                                    <ul className="pd-legend">
                                        <li>
                                            <span className="pd-legend__dot" aria-hidden="true" />
                                            Wajib
                                        </li>
                                        <li>
                                            <span
                                                className="pd-legend__dot pd-legend__dot--ink"
                                                aria-hidden="true"
                                            />
                                            Pokok
                                        </li>
                                    </ul>
                                </>
                            )}

                            <dl className="pd-rows">
                                <div className="pd-row">
                                    <dt>
                                        Simpanan wajib
                                        {totalSimpanan > 0
                                            ? ` (${Math.round(
                                                  (simpananWajib / totalSimpanan) * 100
                                              )}%)`
                                            : ''}
                                    </dt>
                                    <dd>
                                        <Nominal nilai={simpananWajib} tampil={nominalTampil} />
                                    </dd>
                                </div>
                                <div className="pd-row">
                                    <dt>
                                        Simpanan pokok
                                        {totalSimpanan > 0
                                            ? ` (${Math.round(
                                                  (simpananPokok / totalSimpanan) * 100
                                              )}%)`
                                            : ''}
                                    </dt>
                                    <dd>
                                        <Nominal nilai={simpananPokok} tampil={nominalTampil} />
                                    </dd>
                                </div>
                            </dl>

                            {settingSimpanan.length > 0 && (
                                <details className="pd-details">
                                    <summary>
                                        Ketentuan tarif simpanan
                                        <ChevronRight
                                            size={14}
                                            className="pd-details__chevron"
                                            aria-hidden="true"
                                        />
                                    </summary>
                                    <div className="pd-details__body">
                                        {settingSimpanan.map((s, i) => (
                                            <div key={i}>
                                                <span>{s.label}</span>
                                                <strong>{formatRupiah(s.nominal)}</strong>
                                            </div>
                                        ))}
                                    </div>
                                </details>
                            )}
                        </section>

                        {/* ---------------- PLAFON RULES ---------------- */}
                        <section data-tour="plafon-rules" className="pd-card">
                            <div className="pd-card__head">
                                <div>
                                    <h2>Ketentuan &amp; plafon</h2>
                                    <p className="pd-card__note">
                                        Batas nominal dan tenor maksimal
                                    </p>
                                </div>
                            </div>

                            <ol className="pd-ladder">
                                {tanggaTenor.map((r, i) => (
                                    <li key={i} className="pd-rung">
                                        <span className="pd-rung__nominal">
                                            {formatRupiah(r.nominal_min)}
                                            {r.nominal_max > r.nominal_min ? (
                                                <>
                                                    {' '}
                                                    &ndash; {formatRupiah(r.nominal_max)}
                                                </>
                                            ) : null}
                                        </span>
                                        <span className="pd-rung__bar" aria-hidden="true">
                                            <span
                                                className="pd-rung__fill"
                                                style={{
                                                    width: `${
                                                        tenorTertinggi > 0
                                                            ? Math.round(
                                                                  (r.tenor / tenorTertinggi) * 100
                                                              )
                                                            : 0
                                                    }%`,
                                                }}
                                            />
                                        </span>
                                        <span className="pd-rung__tenor">
                                            Maks. {r.tenor} bulan
                                        </span>
                                    </li>
                                ))}
                            </ol>

                            <p className="pd-card__foot">
                                Pengajuan pinjaman diproses berjenjang: verifikasi Admin, pemeriksaan
                                Bendahara, dan persetujuan Ketua Koperasi.
                            </p>
                        </section>
                    </div>
                </div>
            </div>
        </AnggotaLayout>
    );
}
