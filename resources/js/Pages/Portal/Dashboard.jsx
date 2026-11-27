import AnggotaLayout from '@/Layouts/AnggotaLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState, useMemo, useEffect } from 'react';
import { driver } from 'driver.js';
import 'driver.js/dist/driver.css';
import {
    ArrowRight,
    Check,
    CheckCircle2,
    ArrowDownCircle,
    Clock,
    Landmark,
    Pencil,
    XCircle,
    Info,
    CalendarDays,
    Repeat,
    Eye,
    EyeOff,
    Plus,
    TrendingUp,
    ChevronRight,
    X,
    Lock,
    ShieldCheck,
    Wallet,
} from 'lucide-react';
import { formatRupiah } from '@/Utils/formatCurrency';
import Nominal, { usePrivasiNominal } from '@/Components/ui/Nominal';
import PwaInstallPrompt from '@/Components/ui/PwaInstallPrompt';
import { withIdempotencyKey } from '@/Utils/idempotency';

const statusPengajuanLabel = {
    diajukan: {
        text: 'Menunggu pemeriksaan Sekretaris',
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
    approved_ketua: {
        text: 'Disetujui Ketua — Pencairan dana dijadwalkan pada akhir bulan oleh Bendahara',
        step: 4,
    },
};

const tipeLabel = {
    perpanjang: 'Perubahan Tenor (Perpanjang)',
    percepat: 'Perubahan Tenor (Percepat)',
    lunas_total: 'Pelunasan Dipercepat',
};

function formatRekeningTujuan(bank, noRekening, tampil) {
    if (!bank && !noRekening) return null;
    if (!noRekening) return bank;
    const nomor = tampil ? String(noRekening) : `•••• ${String(noRekening).slice(-4)}`;
    return bank ? `${bank} ${nomor}` : nomor;
}

function StatusRow({
    icon: Icon,
    tone = 'quiet',
    title,
    body,
    href,
    linkLabel,
    onDismiss,
    dismissLabel = 'Tutup pemberitahuan',
    isClosing = false,
}) {
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
            {onDismiss ? (
                <button
                    type="button"
                    onClick={(e) => {
                        e.stopPropagation();
                        e.preventDefault();
                        onDismiss();
                    }}
                    aria-label={dismissLabel}
                    title={dismissLabel}
                    className="pd-status__close pd-focus"
                >
                    <X size={14} aria-hidden="true" />
                </button>
            ) : null}
        </>
    );

    return (
        <li className={`pd-status__row ${isClosing ? 'is-closing' : ''}`}>
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
    const [buka, setBuka] = useState(false);

    return (
        <div
            className="pd-lock"
            tabIndex={0}
            onMouseEnter={() => setBuka(true)}
            onMouseLeave={() => setBuka(false)}
            onFocus={() => setBuka(true)}
            onBlur={() => setBuka(false)}
            onClick={() => setBuka((v) => !v)}
            aria-label={pesan || undefined}
        >
            {children}
            {pesan && buka && (
                <span role="tooltip" className="pd-lock__tip is-open">
                    <span className="pd-lock__tip-icon" aria-hidden="true">
                        <Lock size={12} strokeWidth={2.4} />
                    </span>
                    <span className="pd-lock__tip-text">{pesan}</span>
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
    syaratMasaKeanggotaan = null,
    bolehAjukanLimit: propBolehAjukanLimit,
    pesanTerkunciLimit: propPesanTerkunciLimit,
    statusLimitBadge: propStatusLimitBadge,
    stateLimitLabel: propStateLimitLabel,
    plafonKebijakan,
    riwayatGabungan,
    tabungan = null,
    sukuBungaTabunganAktif = 2,
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

    const { auth, flash } = usePage().props;
    const userHasPin = Boolean(auth?.user?.has_pin);
    const [statusDitutup, setStatusDitutup] = useState(false);
    const [modalBatalOpen, setModalBatalOpen] = useState(false);
    const [alasanBatal, setAlasanBatal] = useState('');
    const [pinBatal, setPinBatal] = useState('');
    const [errorBatal, setErrorBatal] = useState('');
    const [loadingBatal, setLoadingBatal] = useState(false);

    function bukaModalBatal() {
        setAlasanBatal('');
        setPinBatal('');
        setErrorBatal('');
        setModalBatalOpen(true);
    }

    function submitBatalPengajuan(e) {
        e.preventDefault();
        if (!pengajuanBerjalan || loadingBatal) return;
        if (userHasPin && pinBatal.length !== 6) {
            setErrorBatal('Masukkan 6 digit PIN keamanan untuk membatalkan pengajuan.');
            return;
        }
        setLoadingBatal(true);
        setErrorBatal('');
        router.post(
            route('portal.pinjaman.batal', pengajuanBerjalan.id),
            {
                alasan: alasanBatal,
                pin: pinBatal,
            },
            withIdempotencyKey({
                preserveScroll: true,
                onSuccess: () => {
                    setModalBatalOpen(false);
                },
                onError: (errors) => {
                    setErrorBatal(errors.pin ?? errors.batal ?? Object.values(errors)[0] ?? 'Gagal membatalkan pengajuan.');
                },
                onFinish: () => {
                    setLoadingBatal(false);
                },
            }),
        );
    }

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
    const belumGenapEnamBulan = Boolean(syaratMasaKeanggotaan && !syaratMasaKeanggotaan.terpenuhi);

    // 1. Ajukan Pinjaman: hanya bisa jika eligibel, tidak ada pengajuan pinjaman/limit berjalan, limit tersedia > 0, dan tidak ada resign shortfall.
    const bolehAjukanPinjaman = Boolean(
        bisaAjukan &&
        !pengajuanBerjalan &&
        !pengajuanLimitBerjalan &&
        limitTersedia > 0 &&
        !resignMenunggu
    );

    // 2. Tambah Limit: dikontrol penuh oleh domain rule EligibilitasPinjamanService
    const bolehAjukanLimit = propBolehAjukanLimit !== undefined
        ? propBolehAjukanLimit
        : Boolean(bisaAjukan && !pengajuanBerjalan && !pengajuanLimitBerjalan && !resignMenunggu);

    const statusLimitBadge = propStatusLimitBadge || (pengajuanBerjalan || pengajuanLimitBerjalan ? 'diproses' : 'terkunci');
    const stateLimitLabel = propStateLimitLabel || (pengajuanBerjalan
        ? 'Pengajuan diproses'
        : pengajuanLimitBerjalan
        ? 'Sedang diverifikasi'
        : 'Belum memenuhi');

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
        : pengajuanLimitBerjalan
        ? 'Pengajuan kenaikan limit Anda masih diproses.'
        : limitTersedia <= 0
        ? 'Limit pinjaman Anda sudah terpakai penuh.'
        : resignMenunggu
        ? 'Penyelesaian resign Anda sedang menunggu pelunasan.'
        : alasanTidakBisa || 'Belum memenuhi ketentuan koperasi.';

    const pesanTerkunciLimit = propPesanTerkunciLimit || (pengajuanBerjalan
        ? 'Pengajuan kenaikan limit tidak dapat dibuat karena pengajuan pinjaman Anda masih diproses.'
        : pengajuanLimitBerjalan
        ? 'Pengajuan kenaikan limit Anda masih diverifikasi.'
        : resignMenunggu
        ? 'Penyelesaian resign Anda sedang menunggu pelunasan.'
        : alasanTidakBisa || 'Belum memenuhi ketentuan pengajuan limit.');

    const pesanTerkunciTenor = pengajuanBerjalan
        ? 'Perubahan tenor tidak dapat diajukan karena pengajuan pinjaman Anda masih diproses.'
        : pengajuanPercepatanMenunggu.length > 0
        ? 'Pengajuan perubahan tenor Anda masih diproses.'
        : pinjamanAktifCount === 0
        ? 'Ubah tenor hanya tersedia saat Anda memiliki pinjaman aktif.'
        : 'Hak perubahan tenor untuk pinjaman ini sudah digunakan.';

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

    const storageKeyDitolak = pengajuanDitolak?.id
        ? `koperasi.dismissed-notice.rejected-loan.${auth?.user?.id}.${pengajuanDitolak.id}`
        : null;

    const [ditolakDismissed, setDitolakDismissed] = useState(() => {
        if (!storageKeyDitolak) return false;
        try {
            return localStorage.getItem(storageKeyDitolak) === '1';
        } catch {
            return false;
        }
    });
    const [ditolakClosing, setDitolakClosing] = useState(false);

    function handleDismissDitolak() {
        setDitolakClosing(true);
        setTimeout(() => {
            setDitolakDismissed(true);
            setDitolakClosing(false);
            if (storageKeyDitolak) {
                try {
                    localStorage.setItem(storageKeyDitolak, '1');
                } catch {
                    // ignore storage error
                }
            }
        }, 300);
    }

    const adaStatus =
        Boolean(resignMenunggu) ||
        Boolean(pengajuanLimitBerjalan) ||
        (pinjamanAktifCount > 0 && pengajuanPercepatanMenunggu.length > 0) ||
        (!bisaAjukan && !pengajuanBerjalan) ||
        (limitTersedia <= 0 && bisaAjukan && !pengajuanBerjalan) ||
        Boolean(pengajuanDitolak && !pengajuanBerjalan && !ditolakDismissed);

    useEffect(() => {
        const tourKey = `koperasi.dashboard-tour.dismissed.${auth?.user?.id ?? 'guest'}`;
        let activeTour = null;

        const startTour = () => {
            const steps = [
                { element: '[data-tour="member-card"]', popover: { title: 'Kartu Anggota', description: 'Identitas, status keanggotaan, dan status aktif Anda tampil di sini.' } },
                { element: '[data-tour="privacy-toggle"]', popover: { title: 'Sembunyikan Saldo', description: 'Tombol ini menyembunyikan atau menampilkan seluruh nominal keuangan di Dashboard.' } },
                { element: '[data-tour="financial-summary"]', popover: { title: 'Ringkasan Keuangan', description: 'Pantau total simpanan serta sisa angsuran dan pinjaman aktif Anda.' } },
                { element: '[data-tour="quick-actions"]', popover: { title: 'Menu Utama', description: 'Ajukan pinjaman, tambah limit, ubah tenor, atau buka panduan lengkap.' } },
                ...(pengajuanBerjalan ? [{ element: '[data-tour="application-tracker"]', popover: { title: 'Pelacakan Pengajuan', description: 'Pantau proses pemeriksaan Sekretaris, Bendahara, hingga persetujuan Ketua.' } }] : []),
                { element: '[data-tour="loan-progress"]', popover: { title: 'Progres Pinjaman', description: 'Pantau jumlah angsuran terbayar, sisa angsuran, cicilan bulanan, dan persentase pelunasan.' } },
                { element: '[data-tour="savings-card"]', popover: { title: 'Rincian Simpanan', description: 'Lihat total simpanan beserta rincian Simpanan Wajib dan Simpanan Pokok. Ketentuan tarif dapat dibuka di bagian ini.' } },
                { element: '[data-tour="history-summary"]', popover: { title: 'Aktivitas & Riwayat', description: 'Lihat transaksi simpanan dan pembayaran terakhir.' } },
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

        // Hapus flag pembuatan PIN jika ada agar tidak memicu overlay Driver.js saat reload
        sessionStorage.removeItem('koperasi.dashboard-tour.pin-created');

        return () => {
            window.removeEventListener('start-dashboard-tour', requestHandler);
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

    const tanggalHariIni = useMemo(() => {
        try {
            return new Intl.DateTimeFormat('id-ID', {
                weekday: 'long',
                day: 'numeric',
                month: 'short',
                year: 'numeric',
            }).format(new Date());
        } catch {
            return '';
        }
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

                {flash?.status && !statusDitutup && (
                    <div
                        role="status"
                        className="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50/90 px-4 py-3 flex items-center justify-between gap-3 text-sm font-semibold text-emerald-900"
                    >
                        <div className="flex items-center gap-2 min-w-0">
                            <CheckCircle2 size={17} className="text-brand-green-dark shrink-0" aria-hidden="true" />
                            <span>{flash.status}</span>
                        </div>
                        <button
                            type="button"
                            onClick={() => setStatusDitutup(true)}
                            className="p-1 rounded-lg text-emerald-700 hover:bg-emerald-100 shrink-0"
                            aria-label="Tutup pemberitahuan"
                        >
                            <X size={15} />
                        </button>
                    </div>
                )}

                {/* =================================================
                    GREETING
                ================================================== */}
                <div className="pd-head">
                    <div>
                        <h1>
                            {salamWaktu}, {anggota.nama}
                        </h1>
                        <p className="pd-head__line">
                            Pantau saldo simpanan dan kelola fasilitas pinjaman Anda di satu tempat.
                        </p>
                    </div>
                    {tanggalHariIni ? (
                        <span className="pd-head__date">
                            <CalendarDays size={13} aria-hidden="true" />
                            {tanggalHariIni}
                        </span>
                    ) : null}
                </div>

                {/* =================================================
                    MEMBER CARD — Digital Member Card (Signature)
                ================================================== */}
                <section data-tour="member-card" className="pd-band">
                    {/* Header: Status Pill on left, Privacy Toggle on right */}
                    <div className="pd-band__header">
                        <span
                            className={`pd-band__status-pill ${
                                anggotaAktif ? '' : 'pd-band__status-pill--off'
                            }`}
                        >
                            <span className="pd-band__status-dot" aria-hidden="true" />
                            {anggotaAktif ? 'AKTIF' : 'NONAKTIF'}
                        </span>

                        <button
                            data-tour="privacy-toggle"
                            type="button"
                            onClick={alihNominal}
                            aria-label={nominalTampil ? 'Sembunyikan saldo' : 'Tampilkan saldo'}
                            title={nominalTampil ? 'Sembunyikan saldo' : 'Tampilkan saldo'}
                            className="pd-band__toggle pd-focus"
                        >
                            {nominalTampil ? (
                                <EyeOff size={13} aria-hidden="true" />
                            ) : (
                                <Eye size={13} aria-hidden="true" />
                            )}
                            <span className="pd-band__toggle-label">
                                {nominalTampil ? 'Sembunyikan' : 'Tampilkan'}
                            </span>
                        </button>
                    </div>

                    {/* Body: Profile on left, Financial figures on right */}
                    <div className="pd-band__body">
                        <div className="pd-band__profile">
                            <div className="pd-band__avatar-wrap">
                                <div className="pd-band__avatar-container">
                                    <span className="pd-band__avatar pd-band__avatar--fallback" aria-hidden="true">
                                        {initial}
                                    </span>
                                    {anggota.foto_url && !fotoGagal && (
                                        <img
                                            src={anggota.foto_url}
                                            alt={anggota.nama}
                                            loading="eager"
                                            referrerPolicy="no-referrer"
                                            onLoad={() => setFotoGagal(false)}
                                            onError={() => setFotoGagal(true)}
                                            className="pd-band__avatar pd-band__avatar--img"
                                        />
                                    )}
                                </div>
                            </div>

                            <div className="pd-band__profile-meta">
                                <p className="pd-band__name">{anggota.nama}</p>
                                <p className="pd-band__nip">{anggota.no_karyawan}</p>
                                {anggota.lama_keanggotaan_label ? (
                                    <p className="pd-band__joined">
                                        Masa Keanggotaan: {anggota.lama_keanggotaan_label}
                                    </p>
                                ) : null}
                            </div>
                        </div>

                        <div data-tour="financial-summary" className="pd-band__figures">
                            <div className="pd-band__figure">
                                <p className="pd-band__label">Total Simpanan</p>
                                <p className="pd-band__value">
                                    <Nominal nilai={totalSimpanan} tampil={nominalTampil} />
                                </p>
                                <p className="pd-band__note">Simpanan Pokok &amp; Wajib</p>
                            </div>

                            <div className="pd-band__figure pd-band__figure--end">
                                <p className="pd-band__label">
                                    {pinjamanAktifCount > 1 ? `Pinjaman Aktif (${pinjamanAktifCount})` : 'Pinjaman Aktif'}
                                </p>
                                {pinjamanAktifCount > 0 ? (
                                    <>
                                        <p className="pd-band__value pd-band__value--accent">
                                            {aggregateTotalSisaAngsuran} / {aggregateTotalAngsuran} Angsuran
                                        </p>
                                        <p className="pd-band__note">
                                            Sisa <Nominal nilai={aggregateSisaTotalBayar} tampil={nominalTampil} /> &bull;{' '}
                                            <Nominal nilai={cicilanPerBulan} tampil={nominalTampil} />/bln
                                        </p>
                                    </>
                                ) : (
                                    <>
                                        <p className="pd-band__value pd-band__value--empty">
                                            Belum Ada Pinjaman
                                        </p>
                                        {pengajuanBerjalan ? (
                                            <p className="pd-band__note">
                                                <span className="pd-band__pending">
                                                    <Clock size={12} aria-hidden="true" />
                                                    <span>
                                                        Pengajuan{' '}
                                                        <Nominal
                                                            nilai={pengajuanBerjalan.nominal_disetujui ?? pengajuanBerjalan.nominal}
                                                            tampil={nominalTampil}
                                                        />{' '}
                                                        diproses
                                                    </span>
                                                </span>
                                            </p>
                                        ) : pengajuanLimitBerjalan ? (
                                            <p className="pd-band__note">
                                                <CaptionLimitBerjalan
                                                    pengajuan={pengajuanLimitBerjalan}
                                                    tampil={nominalTampil}
                                                />
                                            </p>
                                        ) : (
                                            <p className="pd-band__note">Tidak ada angsuran berjalan</p>
                                        )}
                                    </>
                                )}
                            </div>
                        </div>
                    </div>
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
                                <span className="pd-action__state">Cek saldo &amp; simulasi</span>
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
                                            : pengajuanLimitBerjalan
                                            ? 'Limit sedang diproses'
                                            : limitTersedia <= 0
                                            ? 'Limit pinjaman habis'
                                            : belumGenapEnamBulan
                                            ? `Minimal 6 bln (${syaratMasaKeanggotaan.bulan_simpanan_wajib}/6)`
                                            : 'Belum memenuhi syarat'}
                                    </span>
                                </span>
                                <span className="pd-action__flag">
                                    {!(pengajuanBerjalan || pengajuanLimitBerjalan) && <Lock size={10} aria-hidden="true" />}
                                    {pengajuanBerjalan || pengajuanLimitBerjalan
                                        ? 'Diproses'
                                        : limitTersedia <= 0
                                        ? 'Limit Rp0'
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
                                <span className="pd-action__state">{stateLimitLabel || 'Ajukan kenaikan limit'}</span>
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
                                    <span className="pd-action__state">{stateLimitLabel || 'Belum memenuhi'}</span>
                                </span>
                                <span className="pd-action__flag">
                                    {statusLimitBadge === 'maksimal' ? (
                                        <>
                                            <Lock size={10} aria-hidden="true" />
                                            <span>Maksimal</span>
                                        </>
                                    ) : statusLimitBadge === 'diproses' ? (
                                        'Diproses'
                                    ) : (
                                        <>
                                            <Lock size={10} aria-hidden="true" />
                                            <span>Terkunci</span>
                                        </>
                                    )}
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
                                    {!(pengajuanBerjalan || pengajuanPercepatanMenunggu.length > 0) && (
                                        <Lock size={10} aria-hidden="true" />
                                    )}
                                    {pengajuanBerjalan || pengajuanPercepatanMenunggu.length > 0
                                        ? 'Diproses'
                                        : pinjamanAktifCount === 0
                                        ? 'Nonaktif'
                                        : 'Sudah dipakai'}
                                </span>
                            </div>
                        </PetunjukTerkunci>
                    )}

                    <Link href={route('portal.tabungan.index')} className="pd-action pd-focus">
                        <span className="pd-action__icon">
                            <Wallet size={17} strokeWidth={2.2} aria-hidden="true" />
                        </span>
                        <span className="pd-action__text">
                            <span className="pd-action__label">Tabungan</span>
                            <span className="pd-action__state">
                                {tabungan?.status === 'aktif'
                                    ? tabungan.no_rekening
                                    : ['diajukan', 'approved_admin', 'approved_bendahara'].includes(tabungan?.status)
                                    ? 'Menunggu verifikasi'
                                    : `Bunga ${sukuBungaTabunganAktif}% / tahun`}
                            </span>
                        </span>
                        <span className="pd-action__flag">
                            {tabungan?.status === 'aktif'
                                ? 'Aktif'
                                : ['diajukan', 'approved_admin', 'approved_bendahara'].includes(tabungan?.status)
                                ? 'Diproses'
                                : 'Buka'}
                        </span>
                    </Link>

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
                        <span className="pd-action__flag">Buka</span>
                    </button>
                </nav>

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
                                            meminjam hingga limit aktif saat ini{' '}
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
                                    title={
                                        belumGenapEnamBulan
                                            ? 'Syarat pinjaman & tambah limit: minimal 6 bulan keanggotaan'
                                            : 'Pengajuan pinjaman belum memenuhi ketentuan'
                                    }
                                    body={
                                        belumGenapEnamBulan ? (
                                            <>
                                                Masa keanggotaan Anda baru{' '}
                                                <strong>{syaratMasaKeanggotaan.masa_bulan} bulan</strong> (simpanan
                                                wajib tercatat{' '}
                                                <strong>{syaratMasaKeanggotaan.bulan_simpanan_wajib}/6 bulan</strong>).
                                                Untuk mengajukan pinjaman atau tambah limit lebih cepat, Anda dapat
                                                melunasi simpanan wajib + dana sosial di muka sebanyak{' '}
                                                <strong>{syaratMasaKeanggotaan.kekurangan_bulan} bulan</strong> (
                                                <Nominal
                                                    nilai={syaratMasaKeanggotaan.estimasi_pelunasan_dimuka}
                                                    tampil={nominalTampil}
                                                />
                                                ) melalui Bendahara Koperasi.
                                            </>
                                        ) : (
                                            <>
                                                {alasanTidakBisa ||
                                                    'Anda belum dapat mengajukan pinjaman saat ini sesuai ketentuan koperasi.'}
                                                {sisaAngsuranAktif > 1 && limitTersedia > 0 ? (
                                                    <>
                                                        {' '}
                                                        Sisa limit yang belum terpakai{' '}
                                                        <Nominal nilai={limitTersedia} tampil={nominalTampil} /> baru
                                                        dapat dipakai setelah angsuran tersisa maksimal 1 bulan.
                                                    </>
                                                ) : null}
                                            </>
                                        )
                                    }
                                />
                            )}

                            {limitTersedia <= 0 && bisaAjukan && !pengajuanBerjalan && (
                                <StatusRow
                                    tone="info"
                                    icon={Info}
                                    title="Limit pinjaman Anda saat ini terpakai seluruhnya"
                                    body="Pengajuan pinjaman baru dibuka kembali setelah angsuran berjalan tersisa maksimal 1 bulan, atau Anda dapat mengajukan penambahan limit melalui menu Tambah limit."
                                />
                            )}

                            {pengajuanDitolak && !pengajuanBerjalan && !ditolakDismissed && (
                                <StatusRow
                                    tone="danger"
                                    icon={XCircle}
                                    onDismiss={handleDismissDitolak}
                                    dismissLabel="Tutup pemberitahuan penolakan"
                                    isClosing={ditolakClosing}
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
                    APPLICATION TRACKER (Konsep 1: Connected Stepper)
                ================================================== */}
                {pengajuanBerjalan &&
                    (() => {
                        const currentStep =
                            pengajuanBerjalan.status === 'approved_ketua'
                                ? 4
                                : pengajuanBerjalan.status === 'approved_bendahara'
                                ? 3
                                : pengajuanBerjalan.status === 'approved_admin'
                                ? 2
                                : 1;

                        const isApprovedKetua = pengajuanBerjalan.status === 'approved_ketua';
                        const tenorTampil =
                            pengajuanBerjalan.tenor_disetujui ?? pengajuanBerjalan.tenor_bulan;
                        const nominalDiminta =
                            pengajuanBerjalan.nominal_diminta ?? pengajuanBerjalan.nominal;
                        const adaUsulanBerbeda =
                            !isApprovedKetua &&
                            pengajuanBerjalan.nominal_disetujui_bendahara != null &&
                            Number(pengajuanBerjalan.nominal_disetujui_bendahara) !==
                                Number(nominalDiminta);
                        const rekeningTujuan = formatRekeningTujuan(
                            pengajuanBerjalan.snapshot_bank,
                            pengajuanBerjalan.snapshot_no_rekening,
                            nominalTampil
                        );

                        const langkah = [
                            {
                                nama: 'Sekretaris',
                                selesai: currentStep > 1,
                                kini: currentStep === 1,
                                selesaiText: 'Diverifikasi',
                                kiniText: 'Sedang diperiksa',
                                tungguText: 'Menunggu',
                            },
                            {
                                nama: 'Bendahara',
                                selesai: currentStep > 2,
                                kini: currentStep === 2,
                                selesaiText: 'Diverifikasi',
                                kiniText: 'Sedang diperiksa',
                                tungguText: 'Menunggu',
                            },
                            {
                                nama: 'Ketua Koperasi',
                                selesai: currentStep > 3,
                                kini: currentStep === 3,
                                selesaiText: 'Disetujui',
                                kiniText: 'Menunggu putusan',
                                tungguText: 'Menunggu',
                            },
                            {
                                nama: 'Pencairan',
                                selesai: false,
                                kini: currentStep === 4,
                                selesaiText: 'Dicairkan',
                                kiniText: 'Akhir bulan',
                                tungguText: 'Menunggu',
                            },
                        ];

                        return (
                            <section data-tour="application-tracker" className="pd-card">
                                <div className="pd-card__head">
                                    <div>
                                        <div className="pd-tracker__title-row">
                                            <h2>
                                                {isApprovedKetua
                                                    ? 'Pinjaman disetujui'
                                                    : 'Pengajuan pinjaman'}
                                            </h2>
                                            <span
                                                className={`pd-tracker__badge ${
                                                    isApprovedKetua
                                                        ? 'pd-tracker__badge--payout'
                                                        : 'pd-tracker__badge--process'
                                                }`}
                                            >
                                                {isApprovedKetua
                                                    ? 'Menunggu pencairan'
                                                    : `Tahap ${currentStep} dari 4`}
                                            </span>
                                        </div>
                                        {pengajuanBerjalan.tanggal_pengajuan ? (
                                            <p className="pd-card__note">
                                                Diajukan {pengajuanBerjalan.tanggal_pengajuan}
                                                {pengajuanBerjalan.disetujui_ketua_pada
                                                    ? ` • Disetujui ${pengajuanBerjalan.disetujui_ketua_pada}`
                                                    : ''}
                                            </p>
                                        ) : null}
                                    </div>
                                    <Link href={route('portal.riwayat')} className="pd-link pd-focus">
                                        Lihat di riwayat
                                        <ArrowRight size={13} aria-hidden="true" />
                                    </Link>
                                </div>

                                <div className="pd-figure-row pd-tracker__hero">
                                    <p className="pd-figure">
                                        <Nominal
                                            nilai={
                                                pengajuanBerjalan.nominal_disetujui ??
                                                pengajuanBerjalan.nominal
                                            }
                                            tampil={nominalTampil}
                                        />
                                    </p>
                                    <div className="pd-tracker__chips">
                                        {tenorTampil ? (
                                            <span className="pd-chip pd-chip--neutral">
                                                Tenor {tenorTampil} bulan
                                            </span>
                                        ) : null}
                                        {adaUsulanBerbeda ? (
                                            <span className="pd-chip pd-chip--neutral">
                                                Usulan Bendahara:{' '}
                                                <Nominal
                                                    nilai={pengajuanBerjalan.nominal_disetujui_bendahara}
                                                    tampil={nominalTampil}
                                                />
                                            </span>
                                        ) : null}
                                    </div>
                                </div>

                                <ol className="pd-stepper" aria-label="Tahapan pengajuan pinjaman">
                                    {langkah.map((l, idx) => {
                                        const status = l.selesai
                                            ? l.selesaiText
                                            : l.kini
                                            ? l.kiniText
                                            : l.tungguText;

                                        return (
                                            <li
                                                key={l.nama}
                                                className={`pd-stepper__item ${
                                                    l.selesai
                                                        ? 'pd-stepper__item--done'
                                                        : l.kini
                                                        ? 'pd-stepper__item--now'
                                                        : ''
                                                }`}
                                            >
                                                <div className="pd-stepper__rail">
                                                    <span className="pd-stepper__node" aria-hidden="true">
                                                        {l.selesai ? (
                                                            <Check size={14} strokeWidth={2.7} />
                                                        ) : l.kini ? (
                                                            <Clock size={14} strokeWidth={2.3} />
                                                        ) : (
                                                            idx + 1
                                                        )}
                                                    </span>
                                                    {idx < langkah.length - 1 && (
                                                        <span
                                                            className={`pd-stepper__line ${
                                                                l.selesai
                                                                    ? 'pd-stepper__line--done'
                                                                    : ''
                                                            }`}
                                                            aria-hidden="true"
                                                        />
                                                    )}
                                                </div>
                                                <div className="pd-stepper__meta">
                                                    <span className="pd-stepper__name">{l.nama}</span>
                                                    <span className="pd-stepper__desc">{status}</span>
                                                </div>
                                            </li>
                                        );
                                    })}
                                </ol>

                                {isApprovedKetua ? (
                                    <div className="pd-tracker__callout pd-tracker__callout--payout">
                                        <span
                                            className="pd-tracker__callout-icon"
                                            aria-hidden="true"
                                        >
                                            <Landmark size={15} />
                                        </span>
                                        <div className="pd-tracker__callout-content">
                                            <p className="pd-tracker__callout-title">
                                                Pencairan dijadwalkan akhir bulan oleh Bendahara
                                                {rekeningTujuan ? ` ke ${rekeningTujuan}` : ''}
                                                {rekeningTujuan &&
                                                pengajuanBerjalan.snapshot_atas_nama &&
                                                nominalTampil
                                                    ? ` a.n. ${pengajuanBerjalan.snapshot_atas_nama}`
                                                    : ''}
                                                .
                                            </p>
                                            <p className="pd-tracker__callout-body">
                                                Pinjaman belum terhitung aktif hingga dana ditransfer.
                                                Bukti transfer akan dikirim otomatis melalui WhatsApp.
                                            </p>
                                        </div>
                                    </div>
                                ) : (
                                    <div className="pd-tracker__callout pd-tracker__callout--process">
                                        <span
                                            className="pd-tracker__callout-icon"
                                            aria-hidden="true"
                                        >
                                            <Info size={15} />
                                        </span>
                                        <div className="pd-tracker__callout-content">
                                            <p className="pd-tracker__callout-title">
                                                {statusPengajuanLabel[pengajuanBerjalan.status]
                                                    ?.text ?? 'Menunggu pemeriksaan'}
                                                .
                                            </p>
                                            <p className="pd-tracker__callout-body">
                                                {!adaUsulanBerbeda &&
                                                pengajuanBerjalan.nominal_disetujui_bendahara ? (
                                                    <>
                                                        Usulan Bendahara{' '}
                                                        <strong>
                                                            <Nominal
                                                                nilai={
                                                                    pengajuanBerjalan.nominal_disetujui_bendahara
                                                                }
                                                                tampil={nominalTampil}
                                                            />
                                                        </strong>
                                                        .{' '}
                                                    </>
                                                ) : null}
                                                Pengajuan pinjaman baru dikunci hingga proses ini
                                                selesai diputuskan.
                                            </p>
                                        </div>
                                    </div>
                                )}

                                {(pengajuanBerjalan.bisa_diedit || pengajuanBerjalan.bisa_dibatalkan) && (
                                    <div className="mt-4 pt-3.5 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
                                        <p className="text-xs text-slate-500 leading-relaxed">
                                            {pengajuanBerjalan.akan_reset_saat_edit
                                                ? 'Perubahan nominal/tenor akan mengulang verifikasi dari tahap awal.'
                                                : 'Masih dapat diubah atau dibatalkan sebelum diputus Ketua.'}
                                        </p>
                                        <div className="flex flex-wrap items-center gap-2 shrink-0">
                                            {pengajuanBerjalan.bisa_diedit && (
                                                <Link
                                                    href={route('portal.pinjaman.edit', pengajuanBerjalan.id)}
                                                    className="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-xl border border-slate-300 bg-white text-xs font-semibold text-brand-navy hover:bg-slate-50 transition-colors"
                                                >
                                                    <Pencil size={13} aria-hidden="true" />
                                                    Ubah Pengajuan
                                                </Link>
                                            )}
                                            {pengajuanBerjalan.bisa_dibatalkan && (
                                                <button
                                                    type="button"
                                                    onClick={bukaModalBatal}
                                                    className="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-xl border border-rose-200 bg-rose-50/70 text-xs font-semibold text-rose-700 hover:bg-rose-100 transition-colors cursor-pointer"
                                                >
                                                    <XCircle size={13} aria-hidden="true" />
                                                    Batalkan
                                                </button>
                                            )}
                                        </div>
                                    </div>
                                )}
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

                                {pinjamanAktifCount > 1 && (
                                    <ul className="pd-subloans" aria-label="Rincian pinjaman berjalan">
                                        {pinjamanAktifList.map((p, idx) => (
                                            <li key={p.id} className="pd-subloan">
                                                <span className="pd-subloan__meta">
                                                    Pinjaman #{idx + 1} &middot; Sisa {p.sisa_angsuran}/
                                                    {p.total_angsuran} bln
                                                </span>
                                                <span className="pd-subloan__val">
                                                    <Nominal
                                                        nilai={p.sisa_total_bayar}
                                                        tampil={nominalTampil}
                                                    />
                                                </span>
                                            </li>
                                        ))}
                                    </ul>
                                )}

                                {pinjamanAktif?.bukti_pencairan_url && (
                                    <div className="mt-3 pt-3 border-t border-slate-100">
                                        <a
                                            href={pinjamanAktif.bukti_pencairan_url}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="pd-link pd-focus"
                                        >
                                            Lihat bukti transfer pencairan
                                            <ArrowRight size={13} aria-hidden="true" />
                                        </a>
                                    </div>
                                )}

                                {angsuranBerikutnya && (
                                    <Link href={route('portal.riwayat')} className="pd-due pd-focus">
                                        <span className="pd-due__icon">
                                            <CalendarDays size={15} aria-hidden="true" />
                                        </span>
                                        <span className="pd-due__text">
                                            <span className="pd-due__label">Angsuran berikutnya</span>
                                            <span className="pd-due__value">
                                                Ke-{angsuranBerikutnya.cicilan_ke},{' '}
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
                                <div className="pd-calm">
                                    <div className="pd-calm__left">
                                        <span className="pd-calm__icon" aria-hidden="true">
                                            <ShieldCheck size={18} />
                                        </span>
                                        <div>
                                            <p className="pd-calm__title">Belum ada pinjaman aktif</p>
                                            <p className="pd-calm__desc">
                                                Anda tidak memiliki tagihan maupun angsuran pinjaman berjalan saat ini.
                                            </p>
                                        </div>
                                    </div>
                                    {bolehAjukanPinjaman && (
                                        <Link
                                            href={route('portal.pinjaman.create')}
                                            className="pd-link pd-focus"
                                        >
                                            Ajukan sekarang
                                            <ArrowRight size={13} aria-hidden="true" />
                                        </Link>
                                    )}
                                </div>
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
                                    {riwayatGabungan.map((item, i) => {
                                        const isMasuk = ['simpanan', 'tabungan_masuk', 'tabungan_bunga'].includes(item.tipe);
                                        const isKeluar = item.tipe === 'tabungan_keluar';

                                        return (
                                            <li key={i} className="pd-act">
                                                <span
                                                    className={`pd-act__mark ${
                                                        isMasuk ? 'pd-act__mark--in' : ''
                                                    }`}
                                                >
                                                    {isMasuk ? (
                                                        <ArrowDownCircle size={15} aria-hidden="true" />
                                                    ) : (
                                                        <CheckCircle2 size={15} aria-hidden="true" />
                                                    )}
                                                </span>
                                                <span className="pd-act__text">
                                                    <span className="pd-act__label">{item.label}</span>
                                                    <span className="pd-act__date">
                                                        {item.tanggal_format}
                                                    </span>
                                                </span>
                                                <span
                                                    className={`pd-act__amount ${
                                                        isMasuk ? 'pd-act__amount--in' : ''
                                                    }`}
                                                >
                                                    {isMasuk ? '+' : isKeluar ? '−' : ''}
                                                    <Nominal
                                                        nilai={item.nominal}
                                                        tampil={nominalTampil}
                                                    />
                                                </span>
                                            </li>
                                        );
                                    })}
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
                                        Total simpanan saat ini{' '}
                                        <Nominal nilai={totalSimpanan} tampil={nominalTampil} />
                                    </p>
                                </div>
                            </div>

                            <dl className="pd-rows">
                                <div className="pd-row">
                                    <dt>Simpanan wajib</dt>
                                    <dd>
                                        <Nominal nilai={simpananWajib} tampil={nominalTampil} />
                                    </dd>
                                </div>
                                <div className="pd-row">
                                    <dt>Simpanan pokok</dt>
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

                        {/* ---------------- TABUNGAN ANGGOTA ---------------- */}
                        <section className="pd-card">
                            <div className="pd-card__head">
                                <div>
                                    <h2>Tabungan anggota</h2>
                                    <p className="pd-card__note">
                                        {tabungan?.status === 'aktif'
                                            ? `No. Rek ${tabungan.no_rekening}`
                                            : `Simpanan sukarela berbunga ${sukuBungaTabunganAktif}% / tahun`}
                                    </p>
                                </div>
                                <Link href={route('portal.tabungan.index')} className="pd-link pd-focus">
                                    {tabungan?.status === 'aktif' ? 'Kelola & Setor' : 'Buka menu'}
                                    <ArrowRight size={13} aria-hidden="true" />
                                </Link>
                            </div>

                            {tabungan?.status === 'aktif' ? (
                                <>
                                    <p className="pd-figure">
                                        <Nominal nilai={tabungan.saldo} tampil={nominalTampil} />
                                    </p>
                                    <dl className="pd-rows">
                                        <div className="pd-row">
                                            <dt>Estimasi bunga bulan ini</dt>
                                            <dd>
                                                <Nominal nilai={tabungan.estimasi_bunga_bulan_ini} tampil={nominalTampil} />
                                            </dd>
                                        </div>
                                        <div className="pd-row">
                                            <dt>Total bunga diterima</dt>
                                            <dd>
                                                <Nominal nilai={tabungan.total_bunga} tampil={nominalTampil} />
                                            </dd>
                                        </div>
                                        <div className="pd-row">
                                            <dt>Suku bunga berlaku</dt>
                                            <dd>{tabungan.suku_bunga_aktif}% / tahun</dd>
                                        </div>
                                    </dl>
                                    {tabungan.pengajuan_menunggu_count > 0 && (
                                        <p className="pd-card__foot">
                                            Ada {tabungan.pengajuan_menunggu_count} pengajuan transaksi yang sedang dalam proses persetujuan / pencairan pengurus.
                                        </p>
                                    )}
                                </>
                            ) : ['diajukan', 'approved_admin', 'approved_bendahara'].includes(tabungan?.status) ? (
                                <p className="pd-card__foot">
                                    {tabungan.status === 'diajukan'
                                        ? 'Pengajuan pembukaan rekening tabungan Anda sedang menunggu verifikasi Sekretaris.'
                                        : tabungan.status === 'approved_admin'
                                        ? 'Pengajuan pembukaan rekening Anda lolos Sekretaris dan sedang ditinjau Bendahara.'
                                        : 'Pengajuan pembukaan rekening Anda sudah ditinjau Bendahara dan menunggu persetujuan final Ketua Koperasi.'}
                                </p>
                            ) : (
                                <p className="pd-card__foot">
                                    Ajukan pembukaan rekening tabungan sukarela di Portal Anggota untuk memperoleh bunga {sukuBungaTabunganAktif}% per tahun (dihitung harian, dikreditkan bulanan).
                                </p>
                            )}
                        </section>
                    </div>
                </div>
            </div>

            {modalBatalOpen && pengajuanBerjalan && (
                <div className="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-end sm:items-center justify-center sm:p-4">
                    <div
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="batal-pinjaman-title"
                        className="w-full max-w-md bg-white rounded-t-3xl sm:rounded-2xl shadow-2xl border border-slate-200 overflow-hidden"
                    >
                        <div className="px-5 py-4 border-b border-slate-100 flex items-center justify-between gap-3">
                            <div>
                                <h3 id="batal-pinjaman-title" className="text-base font-bold text-slate-900">
                                    Batalkan Pengajuan Pinjaman
                                </h3>
                                <p className="text-xs text-slate-500 mt-0.5">
                                    Pengajuan #{pengajuanBerjalan.id} ({formatRupiah(pengajuanBerjalan.nominal)})
                                </p>
                            </div>
                            <button
                                type="button"
                                onClick={() => setModalBatalOpen(false)}
                                className="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100"
                                aria-label="Tutup"
                            >
                                <X size={18} />
                            </button>
                        </div>

                        <form onSubmit={submitBatalPengajuan} className="p-5 space-y-4">
                            {errorBatal && (
                                <div className="rounded-xl border border-rose-200 bg-rose-50 px-3.5 py-2.5 text-xs font-semibold text-rose-700">
                                    {errorBatal}
                                </div>
                            )}

                            <div>
                                <p className="text-xs font-semibold text-slate-700 mb-2">Alasan Pembatalan (Opsional)</p>
                                <div className="flex flex-wrap gap-1.5 mb-2.5">
                                    {['Salah nominal / tenor', 'Ingin ubah rekening tujuan', 'Tidak jadi meminjam'].map((preset) => (
                                        <button
                                            key={preset}
                                            type="button"
                                            onClick={() => setAlasanBatal(preset)}
                                            className={`px-2.5 py-1 rounded-full text-xs font-medium border transition-colors cursor-pointer ${
                                                alasanBatal === preset
                                                    ? 'bg-brand-navy text-white border-brand-navy'
                                                    : 'bg-white text-slate-600 border-slate-200 hover:border-slate-300'
                                            }`}
                                        >
                                            {preset}
                                        </button>
                                    ))}
                                </div>
                                <textarea
                                    rows={2}
                                    value={alasanBatal}
                                    onChange={(e) => setAlasanBatal(e.target.value)}
                                    placeholder="Tuliskan alasan pembatalan..."
                                    className="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300 focus:border-brand-navy focus:ring-2 focus:ring-brand-navy/15 outline-none"
                                />
                            </div>

                            {userHasPin && (
                                <div>
                                    <label htmlFor="pin-batal-input" className="block text-xs font-semibold text-slate-700 mb-1.5">
                                        PIN Keamanan (6 Digit) <span className="text-rose-500">*</span>
                                    </label>
                                    <input
                                        id="pin-batal-input"
                                        type="password"
                                        inputMode="numeric"
                                        maxLength={6}
                                        value={pinBatal}
                                        onChange={(e) => {
                                            setPinBatal(e.target.value.replace(/\D/g, '').slice(0, 6));
                                            setErrorBatal('');
                                        }}
                                        placeholder="••••••"
                                        autoFocus
                                        className="w-full h-11 text-center text-lg font-mono tracking-[0.5em] rounded-xl border border-slate-300 focus:border-brand-navy focus:ring-2 focus:ring-brand-navy/20"
                                    />
                                </div>
                            )}

                            <div className="grid grid-cols-2 gap-2.5 pt-2">
                                <button
                                    type="button"
                                    onClick={() => setModalBatalOpen(false)}
                                    disabled={loadingBatal}
                                    className="min-h-[42px] rounded-xl border border-slate-300 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50"
                                >
                                    Tutup
                                </button>
                                <button
                                    type="submit"
                                    disabled={loadingBatal || (userHasPin && pinBatal.length !== 6)}
                                    className="min-h-[42px] rounded-xl bg-rose-600 text-sm font-semibold text-white hover:bg-rose-700 disabled:opacity-50"
                                >
                                    {loadingBatal ? 'Membatalkan...' : 'Ya, Batalkan'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </AnggotaLayout>
    );
}
