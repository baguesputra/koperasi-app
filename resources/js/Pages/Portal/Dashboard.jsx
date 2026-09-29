import AnggotaLayout from '@/Layouts/AnggotaLayout';
import { Head, Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import {
    PiggyBank,
    Wallet,
    Gauge,
    ArrowRight,
    CheckCircle2,
    ArrowDownCircle,
    Clock,
    XCircle,
    Info,
    CalendarDays,
    ShieldCheck,
    Percent,
    Repeat,
    Eye,
    EyeOff,
} from 'lucide-react';
import { formatRupiah } from '@/Utils/formatCurrency';
import Nominal, { usePrivasiNominal } from '@/Components/ui/Nominal';

const statusPengajuanLabel = {
    diajukan: {
        text: 'Menunggu pemeriksaan Bendahara',
        step: 1,
    },
    approved_bendahara: {
        text: 'Menunggu persetujuan Ketua Koperasi',
        step: 2,
    },
};

const tipeLabel = {
    perpanjang: 'Perubahan Tenor (Perpanjang)',
    percepat: 'Perubahan Tenor (Percepat)',
    lunas_total: 'Pelunasan Dipercepat',
};

const focusRing =
    'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-green focus-visible:ring-offset-2';

function StatCard({ icon: Icon, tone, label, value, caption }) {
    const tones = {
        navy: 'bg-brand-navy/5 text-brand-navy',
        green: 'bg-brand-green-light text-brand-green-dark',
        blue: 'bg-blue-50 text-blue-600',
    };

    return (
        <div className="bg-white rounded-xl border border-slate-100 p-4 snap-start shrink-0 w-[82%] sm:w-auto sm:shrink">
            <div className="flex items-center gap-3">
                <div className={`w-9 h-9 rounded-lg flex items-center justify-center shrink-0 ${tones[tone]}`}>
                    <Icon size={18} />
                </div>

                <div className="min-w-0 flex-1">
                    <p className="text-xs text-slate-500">{label}</p>

                    <p className="text-base sm:text-lg font-bold text-slate-800 leading-tight tabular-nums break-words">
                        {value}
                    </p>
                </div>
            </div>

            {caption && (
                <p className="mt-2.5 text-xs text-slate-400">{caption}</p>
            )}
        </div>
    );
}

function StatusStrip({ dark = false, children }) {
    return (
        <div
            className={`flex items-center gap-2 w-full px-3.5 py-2.5 rounded-lg border ${
                dark
                    ? 'bg-amber-400/15 border-amber-300/30'
                    : 'bg-amber-50 border-amber-200'
            }`}
        >
            <Clock size={14} className={dark ? 'text-amber-300' : 'text-amber-600'} />

            <span className={`text-xs font-semibold ${dark ? 'text-amber-200' : 'text-amber-800'}`}>
                {children}
            </span>
        </div>
    );
}

function CaptionLimitBerjalan({ pengajuan, tampil, limitMaksimal }) {
    if (!pengajuan) {
        return (
            <span>
                dari limit <Nominal nilai={limitMaksimal} tampil={tampil} />
            </span>
        );
    }

    const isKetua = pengajuan.status === 'approved_bendahara';

    return (
        <span className="inline-flex items-center gap-1.5 text-amber-700 font-medium">
            <Clock size={12} />
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

function KuitansiModal({ judul, rows, paragraf, catatan, onClose }) {
    return (
        <div
            className="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm overflow-y-auto animate-in fade-in duration-200"
            role="dialog"
            aria-modal="true"
            aria-labelledby="konfirmasi-title"
        >
            <div className="min-h-full flex items-center justify-center p-4">
                <div className="bg-white rounded-2xl w-full max-w-md my-8 shadow-2xl overflow-hidden">
                    <div className="px-6 pt-8 pb-6 text-center">
                        <div className="w-16 h-16 rounded-full bg-brand-green-light flex items-center justify-center mx-auto mb-4">
                            <CheckCircle2 size={32} className="text-brand-green" />
                        </div>

                        <h2 id="konfirmasi-title" className="text-xl font-bold text-slate-800">
                            {judul}
                        </h2>
                    </div>

                    <div className="mx-6 border-t-2 border-dashed border-slate-200" />

                    <div className="px-6 py-4 space-y-2.5">
                        {rows.map((r) => (
                            <div key={r.label} className="flex items-start justify-between gap-4 text-sm">
                                <span className="text-slate-500 shrink-0">{r.label}</span>
                                <span className="font-bold text-slate-800 text-right">{r.value}</span>
                            </div>
                        ))}
                    </div>

                    <div className="mx-6 border-t-2 border-dashed border-slate-200" />

                    <div className="px-6 py-5">
                        <p className="text-sm text-slate-600 leading-relaxed">{paragraf}</p>

                        <p className="mt-3 text-xs text-slate-500 leading-relaxed bg-slate-50 border border-slate-100 rounded-lg p-3">
                            {catatan}
                        </p>

                        <button
                            type="button"
                            onClick={onClose}
                            className={`w-full mt-5 py-3 text-sm font-bold rounded-xl bg-brand-navy text-white hover:bg-brand-navy-dark transition-colors ${focusRing}`}
                        >
                            Kembali ke Beranda
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}

const percepatanParagraf = {
    percepat: 'Pengajuan pengurangan lama cicilan Anda telah kami terima dan diteruskan melalui WhatsApp Koperasi untuk diproses lebih lanjut.',
    perpanjang: 'Pengajuan penambahan lama cicilan Anda telah kami terima dan diteruskan melalui WhatsApp Koperasi untuk diproses lebih lanjut.',
    lunas_total: 'Pengajuan pelunasan dipercepat Anda telah kami terima dan diteruskan melalui WhatsApp Koperasi untuk diproses lebih lanjut.',
};

function percepatanRows(p) {
    const rows = [
        { label: 'Pinjaman', value: formatRupiah(p.nominal) },
        { label: 'Tenor saat ini', value: `${p.tenor_lama} bulan` },
    ];

    if (p.tipe === 'lunas_total') {
        rows.push({ label: 'Pelunasan', value: 'Seluruh sisa pokok \u2022 bunga 1 bulan' });
    } else {
        const arah = p.tipe === 'percepat' ? 'Tenor dikurangi' : 'Tenor ditambah';
        rows.push({ label: arah, value: `${p.tenor_lama} \u2192 ${p.tenor_baru} bulan` });
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
    klaimBerjalan,
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
                  ((aggregateTotalAngsuran -
                      aggregateTotalSisaAngsuran) /
                      aggregateTotalAngsuran) *
                      100
              )
            : 0;

    const bisaAjukanLimit = bisaAjukan && !pengajuanLimitBerjalan && limitTersedia > 0;

    const bisaUbahTenor =
        !pengajuanBerjalan &&
        pengajuanPercepatanMenunggu.length === 0 &&
        pinjamanAktifList.some((p) => !p.sudah_pakai_percepatan);

    const { flash } = usePage().props;
    const terkirim = flash.pinjamanTerkirim;
    const percepatanTerkirim = flash.percepatanTerkirim;
    const limitTerkirim = flash.limitTerkirim;
    const [konfirmasiDitutup, setKonfirmasiDitutup] = useState(false);
    const [percepatanDitutup, setPercepatanDitutup] = useState(false);
    const [limitDitutup, setLimitDitutup] = useState(false);
    const [nominalTampil, alihNominal] = usePrivasiNominal();

    return (
        <AnggotaLayout>
            <Head title="Beranda" />

            {terkirim && !konfirmasiDitutup && (
                <KuitansiModal
                    judul="Pengajuan Berhasil Terkirim"
                    rows={[
                        { label: 'Nominal Pinjaman', value: formatRupiah(terkirim.nominal) },
                        { label: 'Lama Cicilan', value: `${terkirim.tenor_bulan} bulan` },
                    ]}
                    paragraf="Pengajuan pinjaman Anda telah kami terima dan diteruskan melalui WhatsApp Koperasi untuk diproses lebih lanjut."
                    catatan="Selama masa peninjauan, tidak ada tindakan yang perlu Anda lakukan. Apabila pengajuan disetujui, pemberitahuan akan disampaikan melalui WhatsApp. Status pengajuan juga dapat dipantau pada halaman Beranda."
                    onClose={() => setKonfirmasiDitutup(true)}
                />
            )}

            {percepatanTerkirim && !percepatanDitutup && (
                <KuitansiModal
                    judul="Pengajuan Berhasil Terkirim"
                    rows={percepatanRows(percepatanTerkirim)}
                    paragraf={percepatanParagraf[percepatanTerkirim.tipe]}
                    catatan="Selama masa peninjauan, tidak ada tindakan yang perlu Anda lakukan. Apabila pengajuan disetujui, pemberitahuan beserta bulan mulai berlakunya akan disampaikan melalui WhatsApp. Status pengajuan juga dapat dipantau pada halaman Beranda."
                    onClose={() => setPercepatanDitutup(true)}
                />
            )}

            {limitTerkirim && !limitDitutup && (
                <KuitansiModal
                    judul="Pengajuan Berhasil Terkirim"
                    rows={[
                        { label: 'Limit yang Diminta', value: formatRupiah(limitTerkirim.diminta) },
                        { label: 'Limit Anda Saat Ini', value: formatRupiah(limitTerkirim.limit_saat_ini) },
                    ]}
                    paragraf="Pengajuan penambahan limit Anda telah kami terima dan diteruskan melalui WhatsApp Koperasi untuk diproses lebih lanjut."
                    catatan="Penambahan limit diverifikasi Bendahara lalu diputuskan final oleh Ketua Koperasi. Nominal final bisa berbeda dari yang Anda minta. Apabila disetujui, pemberitahuan akan disampaikan melalui WhatsApp dan limit baru aktif untuk pinjaman berikutnya."
                    onClose={() => setLimitDitutup(true)}
                />
            )}

            <div className="space-y-5">

                {resignMenunggu && (
                    <div className="flex items-start gap-3 bg-amber-50 border border-amber-200 rounded-2xl p-4">
                        <Clock size={20} className="text-amber-600 shrink-0 mt-0.5" />
                        <div>
                            <p className="text-sm font-bold text-amber-800">
                                Penyelesaian resign menunggu pelunasan akhir <Nominal nilai={resignMenunggu.shortfall} tampil={nominalTampil} />
                                {resignMenunggu.jatuh_tempo ? ` sebelum ${resignMenunggu.jatuh_tempo}` : ''}.
                            </p>
                            <p className="text-sm text-amber-700 mt-1">
                                Selesaikan melalui Bendahara (menu Konfirmasi Angsuran).
                                Pengajuan pinjaman dan limit baru dikunci sampai lunas.
                            </p>
                        </div>
                    </div>
                )}

                {/* =====================================================
                    HEADER
                ====================================================== */}
                <div className="flex items-center justify-between gap-3">
                    <div className="flex items-center gap-3 min-w-0">
                        {anggota.foto_url ? (
                            <img
                                src={anggota.foto_url}
                                alt={anggota.nama}
                                className="w-14 h-14 rounded-full object-cover border border-slate-200 shrink-0"
                                onError={(e) => { e.currentTarget.style.display = 'none'; }}
                            />
                        ) : (
                            <div className="w-14 h-14 rounded-full bg-brand-green text-white flex items-center justify-center text-xl font-bold shrink-0">
                                {anggota.nama.charAt(0).toUpperCase()}
                            </div>
                        )}
                        <div className="min-w-0">
                            <p className="text-xs text-slate-500">
                                Selamat datang,
                            </p>

                            <h1 className="text-lg font-bold text-slate-800 leading-tight truncate">
                                {anggota.nama}
                            </h1>
                            <p className="text-xs text-slate-500 truncate">
                                {anggota.no_karyawan} &bull; Anggota sejak {anggota.lama_keanggotaan_label}
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        onClick={alihNominal}
                        aria-label={nominalTampil ? 'Sembunyikan nominal' : 'Tampilkan nominal'}
                        aria-pressed={nominalTampil}
                        className="shrink-0 w-10 h-10 rounded-full border border-slate-200 text-slate-500 hover:text-brand-navy hover:border-brand-navy/30 hover:bg-slate-50 flex items-center justify-center focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-green focus-visible:ring-offset-2"
                    >
                        {nominalTampil ? <EyeOff size={18} aria-hidden="true" /> : <Eye size={18} aria-hidden="true" />}
                    </button>
                </div>

                {/* =====================================================
                    RINGKASAN KEUANGAN
                ====================================================== */}
                <div className="-mx-4 px-4 sm:mx-0 sm:px-0 flex sm:grid sm:grid-cols-3 gap-3 overflow-x-auto sm:overflow-visible snap-x snap-mandatory sm:snap-none pb-1 sm:pb-0 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">

                    <StatCard
                        icon={PiggyBank}
                        tone="green"
                        label="Total Simpanan"
                        value={<Nominal nilai={totalSimpanan} tampil={nominalTampil} />}
                    />

                    <StatCard
                        icon={Wallet}
                        tone="navy"
                        label="Pinjaman Aktif"
                        value={
                            pinjamanAktifCount > 0
                                ? <Nominal nilai={aggregateTotalNominal} tampil={nominalTampil} />
                                : 'Rp0'
                        }
                        caption={
                            pinjamanAktifCount > 0
                                ? `${pinjamanAktifCount} pinjaman sedang berjalan`
                                : 'Belum ada pinjaman aktif'
                        }
                    />

                    <StatCard
                        icon={Gauge}
                        tone="blue"
                        label="Limit Tersedia"
                        value={<Nominal nilai={limitTersedia} tampil={nominalTampil} />}
                        caption={<CaptionLimitBerjalan pengajuan={pengajuanLimitBerjalan} tampil={nominalTampil} limitMaksimal={limitMaksimal} />}
                    />
                </div>

                {/* =====================================================
                    ANGSURAN BERIKUTNYA (signature)
                ====================================================== */}
                {pinjamanAktifCount > 0 && angsuranBerikutnya && (
                    <Link
                        href={route('portal.riwayat')}
                        className={`relative flex flex-col min-[400px]:flex-row min-[400px]:items-stretch bg-brand-green-light rounded-xl group ${focusRing}`}
                    >
                        {/* Lubang tiket kiri-kanan */}
                        <span aria-hidden="true" className="absolute -left-2.5 top-1/2 -translate-y-1/2 w-4 h-4 rounded-full bg-slate-50" />
                        <span aria-hidden="true" className="absolute -right-2.5 top-1/2 -translate-y-1/2 w-4 h-4 rounded-full bg-slate-50" />

                        <div className="flex items-center gap-3.5 flex-1 min-w-0 p-4">
                            <div className="w-10 h-10 rounded-lg bg-white text-brand-green-dark flex items-center justify-center shrink-0 shadow-sm">
                                <CalendarDays size={19} />
                            </div>

                            <div className="min-w-0">
                                <p className="text-xs font-bold uppercase tracking-wide text-brand-green-dark">
                                    Angsuran berikutnya
                                </p>

                                <p className="text-sm font-semibold text-slate-800 truncate mt-0.5">
                                    Angsuran ke-{angsuranBerikutnya.cicilan_ke}
                                    {' \u2022 '}
                                    jatuh tempo {angsuranBerikutnya.tanggal_jatuh_tempo}
                                </p>
                            </div>
                        </div>

                        <div className="flex items-center gap-3 shrink-0 border-t-2 border-dashed border-brand-green/25 bg-white/70 px-4 py-3 min-[400px]:my-2 min-[400px]:mr-3 min-[400px]:rounded-lg min-[400px]:border-t-0 min-[400px]:border-l-2 min-[400px]:py-3 rounded-b-xl min-[400px]:rounded-b-none min-[400px]:rounded-r-xl">
                            <div>
                                <p className="text-xs text-slate-500">Total bayar</p>

                                <p className="text-base font-bold text-slate-800 leading-tight whitespace-nowrap">
                                    <Nominal nilai={angsuranBerikutnya.total_bayar} tampil={nominalTampil} />
                                </p>
                            </div>

                            <ArrowRight
                                size={17}
                                className="text-brand-green-dark shrink-0 group-hover:translate-x-0.5 transition-transform motion-reduce:transition-none"
                            />
                        </div>
                    </Link>
                )}

                {/* =====================================================
                    KONTEN UTAMA
                ====================================================== */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-5">

                    {/* =================================================
                        KOLOM UTAMA
                    ================================================== */}
                    <div className="lg:col-span-2 space-y-5">

                        {/* =================================================
                            PINJAMAN / PENGAJUAN
                        ================================================== */}
                        {pinjamanAktifCount > 0 ? (
                            <div className="bg-brand-navy rounded-2xl p-5 text-white">

                                {/* Header */}
                                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                    <div>
                                        <p className="text-xs text-slate-300 mb-1">
                                            Sisa pinjaman
                                            {pinjamanAktifCount > 1
                                                ? ` (${pinjamanAktifCount} pinjaman aktif)`
                                                : ''}
                                        </p>

                                        <p className="text-2xl font-bold">
                                            <Nominal nilai={aggregateSisaTotalBayar} tampil={nominalTampil} />
                                        </p>

                                        <p className="text-xs text-slate-400 mt-1">
                                            dari total <Nominal nilai={aggregateTotalNominal} tampil={nominalTampil} />
                                        </p>
                                    </div>

                                    <span className="inline-flex self-start sm:self-auto items-center px-2.5 py-1 rounded-full bg-brand-green text-white text-xs font-semibold">
                                        {progress}% lunas
                                    </span>
                                </div>

                                {/* Progress */}
                                <div className="mt-4">
                                    <div className="w-full h-2 bg-white/10 rounded-full overflow-hidden">
                                        <div
                                            className="h-full bg-brand-green rounded-full"
                                            style={{
                                                width: `${progress}%`,
                                            }}
                                        />
                                    </div>

                                    <div className="flex items-center justify-between mt-2 text-xs text-slate-300">
                                        <span>
                                            {aggregateTotalAngsuran -
                                                aggregateTotalSisaAngsuran}{' '}
                                            angsuran telah dibayar
                                        </span>

                                        <span>
                                            sisa {aggregateTotalSisaAngsuran}{' '}angsuran
                                        </span>
                                    </div>
                                </div>

                                {/* Ringkasan Pembayaran */}
                                <div className="mt-4 grid grid-cols-2 gap-3">
                                    <div className="bg-white/5 rounded-xl p-3">
                                        <p className="text-xs text-slate-300">
                                            Cicilan per bulan
                                        </p>

                                        <p className="text-sm font-semibold mt-0.5">
                                            <Nominal nilai={cicilanPerBulan} tampil={nominalTampil} />
                                        </p>
                                    </div>

                                    <div className="bg-white/5 rounded-xl p-3">
                                        <p className="text-xs text-slate-300">
                                            Sisa total pembayaran
                                        </p>

                                        <p className="text-sm font-semibold mt-0.5">
                                            <Nominal nilai={aggregateSisaTotalBayar} tampil={nominalTampil} />
                                        </p>
                                    </div>
                                </div>

                                {/* Aksi */}
                                <div className="mt-4 pt-4 border-t border-white/10 space-y-3">
                                    {pengajuanBerjalan ? (
                                        <StatusStrip dark>
                                            Proses pengajuan:{' '}
                                            {statusPengajuanLabel[pengajuanBerjalan.status]?.text ?? 'Menunggu pemeriksaan'}
                                        </StatusStrip>
                                    ) : bisaAjukan ? (
                                        <div className="flex flex-col sm:flex-row gap-2.5">
                                            <Link
                                                href={route('portal.pinjaman.create')}
                                                className={`flex-1 inline-flex items-center justify-center gap-2 px-4 min-h-[48px] rounded-xl bg-brand-green text-white text-base font-bold hover:bg-brand-green-dark transition-colors ${focusRing}`}
                                            >
                                                Ajukan Pinjaman
                                                <ArrowRight size={15} />
                                            </Link>

                                            {bisaAjukanLimit && (
                                                <Link
                                                    href={route('portal.pengajuan-limit.create')}
                                                    className={`inline-flex items-center justify-center gap-2 px-4 min-h-[48px] rounded-xl border border-white/25 text-white text-sm font-semibold hover:bg-white/10 transition-colors ${focusRing}`}
                                                >
                                                    Ajukan Penambahan Limit
                                                </Link>
                                            )}
                                        </div>
                                    ) : (
                                        <StatusStrip dark>{alasanTidakBisa}</StatusStrip>
                                    )}

                                    {bisaUbahTenor && (
                                        <Link
                                            href={route('portal.percepatan.create')}
                                            className={`inline-flex items-center justify-center gap-1.5 w-full sm:w-auto px-4 min-h-[48px] rounded-xl border border-white/25 text-sm font-semibold text-slate-200 hover:text-white hover:bg-white/10 transition-colors ${focusRing}`}
                                        >
                                            <Repeat size={14} />
                                            Ubah Tenor / Lunas Dipercepat
                                        </Link>
                                    )}
                                </div>
                            </div>
                        ) : pengajuanBerjalan ? (
                            <Link
                                href={route('portal.riwayat')}
                                className={`block bg-brand-navy rounded-2xl p-5 text-white hover:bg-brand-navy-light transition-colors ${focusRing}`}
                            >
                                <div className="flex items-center justify-between gap-3">
                                    <div>
                                        <p className="text-xs text-slate-300 mb-1">
                                            Pengajuan Pinjaman
                                        </p>

                                        <p className="text-2xl font-bold">
                                            <Nominal nilai={pengajuanBerjalan.nominal} tampil={nominalTampil} />
                                        </p>
                                    </div>

                                    <Clock size={20} className="text-amber-300" />
                                </div>

                                <div className="flex items-center gap-1.5 mt-4 mb-2.5">
                                    {[1, 2].map((s) => (
                                        <div
                                            key={s}
                                            className={`h-1.5 flex-1 rounded-full ${
                                                s <=
                                                statusPengajuanLabel[pengajuanBerjalan.status].step
                                                    ? 'bg-amber-300'
                                                    : 'bg-white/10'
                                            }`}
                                        />
                                    ))}
                                </div>

                                <p className="text-sm text-slate-300">
                                    {statusPengajuanLabel[pengajuanBerjalan.status].text}
                                    {pengajuanBerjalan.nominal_disetujui_bendahara && (
                                        <> &bull; Usulan Bendahara: <Nominal nilai={pengajuanBerjalan.nominal_disetujui_bendahara} tampil={nominalTampil} /></>
                                    )}
                                </p>
                            </Link>
                        ) : (
                            <div className="bg-brand-navy rounded-2xl p-5 text-white">

                                {pengajuanDitolak && (
                                    <div className="flex items-start gap-3 bg-red-500/15 border border-red-400/20 rounded-xl p-3.5 mb-4">
                                        <XCircle className="text-red-300 shrink-0 mt-0.5" size={17} />

                                        <div>
                                            <p className="text-sm font-semibold text-red-200">
                                                Pengajuan <Nominal nilai={pengajuanDitolak.nominal} tampil={nominalTampil} /> ditolak
                                            </p>

                                            {pengajuanDitolak.catatan && (
                                                <p className="text-xs text-red-200/70 mt-1">
                                                    {pengajuanDitolak.catatan}
                                                </p>
                                            )}
                                        </div>
                                    </div>
                                )}

                                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                    <div>
                                        <p className="text-lg font-bold">
                                            Belum ada pinjaman aktif
                                        </p>

                                        <p className="text-sm text-slate-400 mt-1">
                                            Limit pengajuan Anda <Nominal nilai={limitMaksimal} tampil={nominalTampil} />
                                        </p>
                                    </div>

                                    {bisaAjukan ? (
                                        <div className="flex flex-col sm:flex-row gap-2.5 shrink-0">
                                            <Link
                                                href={route('portal.pinjaman.create')}
                                                className={`inline-flex items-center justify-center gap-2 px-5 min-h-[48px] rounded-xl bg-brand-green text-white text-base font-bold hover:bg-brand-green-dark transition-colors ${focusRing}`}
                                            >
                                                Ajukan Pinjaman
                                                <ArrowRight size={15} />
                                            </Link>

                                            {bisaAjukanLimit && (
                                                <Link
                                                    href={route('portal.pengajuan-limit.create')}
                                                    className={`inline-flex items-center justify-center px-4 min-h-[48px] rounded-xl border border-white/25 text-white text-sm font-semibold hover:bg-white/10 transition-colors ${focusRing}`}
                                                >
                                                    Ajukan Penambahan Limit
                                                </Link>
                                            )}
                                        </div>
                                    ) : (
                                        <div className="bg-white/5 border border-white/10 rounded-lg px-3.5 py-2.5">
                                            <p className="text-xs text-slate-300 leading-relaxed">
                                                {alasanTidakBisa}
                                            </p>
                                        </div>
                                    )}
                                </div>
                            </div>
                        )}

                        {/* =================================================
                            PENGAJUAN PERUBAHAN TENOR
                        ================================================== */}
                        {pinjamanAktifCount > 0 &&
                            pengajuanPercepatanMenunggu.length > 0 && (
                                <div className="flex items-start gap-3 bg-amber-50 border border-amber-200 rounded-xl px-4 py-3.5">
                                    <Repeat className="text-amber-600 shrink-0 mt-0.5" size={17} />

                                    <div className="min-w-0">
                                        <p className="text-sm font-semibold text-amber-800">
                                            Pengajuan perubahan tenor / pelunasan dalam proses
                                        </p>

                                        <div className="mt-1 space-y-1">
                                            {pengajuanPercepatanMenunggu.map((pp) => (
                                                <p key={pp.id} className="text-xs text-amber-700">
                                                    {tipeLabel[pp.tipe]} • Pinjaman{' '}
                                                    <Nominal nilai={pp.pinjaman_nominal} tampil={nominalTampil} /> •{' '}
                                                    {pp.tenor_lama} → {pp.tenor_baru ?? 'lunas'} bulan •{' '}
                                                    {statusPengajuanLabel[pp.status]?.text ?? pp.status}
                                                </p>
                                            ))}
                                        </div>
                                    </div>
                                </div>
                            )}

                        {/* =================================================
                            INFORMASI HAK PERUBAHAN TENOR
                        ================================================== */}
                        {pinjamanAktifCount > 0 &&
                            pengajuanPercepatanMenunggu.length === 0 &&
                            pinjamanAktifList.some((p) => p.sudah_pakai_percepatan) && (
                                <div className="flex items-start gap-3 bg-slate-50 border border-slate-200 rounded-xl px-4 py-3.5">
                                    <Info className="text-slate-500 shrink-0 mt-0.5" size={17} />

                                    <p className="text-xs text-slate-600 leading-relaxed">
                                        Hak perubahan tenor atau pelunasan dipercepat telah digunakan
                                        pada pinjaman yang masih berjalan. Pengajuan berikutnya dapat
                                        dilakukan setelah pinjaman tersebut dilunasi.
                                    </p>
                                </div>
                            )}

                        {/* =================================================
                            SANTUNAN DANA SOSIAL
                        ================================================== */}
                        {klaimBerjalan ? (
                            <Link
                                href={route('portal.klaim-dana-sosial.create')}
                                className={`flex items-start gap-3 bg-blue-50 border border-blue-200 rounded-xl px-4 py-3.5 hover:bg-blue-100/60 transition-colors ${focusRing}`}
                            >
                                <Clock className="text-blue-600 shrink-0 mt-0.5" size={17} />
                                <div className="min-w-0">
                                    <p className="text-sm font-semibold text-blue-800">
                                        Pengajuan {klaimBerjalan.jenis_label} dalam proses
                                    </p>
                                    <p className="text-xs text-blue-700 mt-0.5">
                                        {klaimBerjalan.status === 'approved_bendahara'
                                            ? 'Menunggu keputusan Ketua Koperasi'
                                            : 'Menunggu verifikasi Bendahara'}
                                        {' \u2022 '}Diajukan {klaimBerjalan.tanggal_pengajuan}
                                    </p>
                                </div>
                            </Link>
                        ) : (
                            <Link
                                href={route('portal.klaim-dana-sosial.create')}
                                className={`flex items-center gap-3 bg-white rounded-xl border border-slate-100 px-4 py-3.5 hover:border-brand-green/40 transition-colors ${focusRing}`}
                            >
                                <div className="w-9 h-9 rounded-lg bg-teal-50 text-teal-700 flex items-center justify-center shrink-0">
                                    <ShieldCheck size={18} />
                                </div>
                                <div className="flex-1 min-w-0">
                                    <p className="text-sm font-bold text-slate-700">
                                        Santunan Dana Sosial
                                    </p>
                                    <p className="text-xs text-slate-400 mt-0.5">
                                        Sakit, kelahiran/khitan, duka, dan pernikahan
                                    </p>
                                </div>
                                <ArrowRight size={16} className="text-slate-300 shrink-0" />
                            </Link>
                        )}

                        {/* =================================================
                            AKTIVITAS TERBARU
                        ================================================== */}
                        <div className="bg-white rounded-xl border border-slate-100 overflow-hidden">
                            <div className="flex items-center justify-between px-4 py-3.5 border-b border-slate-100">
                                <div>
                                    <p className="text-sm font-bold text-slate-700">
                                        Aktivitas Terbaru
                                    </p>

                                    <p className="text-xs text-slate-400 mt-0.5">
                                        Transaksi terakhir Anda
                                    </p>
                                </div>

                                <Link
                                    href={route('portal.riwayat')}
                                    className={`text-xs font-semibold text-brand-green flex items-center gap-1 rounded px-1 py-1 hover:text-brand-green-dark ${focusRing}`}
                                >
                                    Lihat riwayat
                                    <ArrowRight size={13} />
                                </Link>
                            </div>

                            {riwayatGabungan.length === 0 ? (
                                <p className="text-sm text-slate-400 text-center py-8">
                                    Belum ada aktivitas transaksi.
                                </p>
                            ) : (
                                <div className="divide-y divide-slate-100">
                                    {riwayatGabungan.map((item, i) => (
                                        <div key={i} className="flex items-center gap-3 px-4 py-3">
                                            {item.tipe === 'simpanan' ? (
                                                <div className="w-8 h-8 rounded-full bg-brand-green-light text-brand-green-dark flex items-center justify-center shrink-0">
                                                    <ArrowDownCircle size={14} />
                                                </div>
                                            ) : (
                                                <div className="w-8 h-8 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                                                    <CheckCircle2 size={14} />
                                                </div>
                                            )}

                                            <div className="flex-1 min-w-0">
                                                <p className="text-sm font-semibold text-slate-700 truncate">
                                                    {item.label}
                                                </p>

                                                <p className="text-xs text-slate-400">
                                                    {item.tanggal_format}
                                                </p>
                                            </div>

                                            <p className="text-sm font-bold text-slate-800 shrink-0">
                                                <Nominal nilai={item.nominal} tampil={nominalTampil} />
                                            </p>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>

                    {/* =====================================================
                        SIDEBAR
                    ====================================================== */}
                    <div className="space-y-5">

                        {/* Rincian Simpanan */}
                        <div className="bg-white rounded-xl border border-slate-100 p-4">
                            <div className="flex items-center gap-2 mb-3">
                                <PiggyBank size={16} className="text-brand-green" />

                                <p className="text-sm font-bold text-slate-700">
                                    Rincian Simpanan
                                </p>
                            </div>

                            <div className="space-y-2.5">
                                <div className="flex items-center justify-between">
                                    <span className="text-xs text-slate-500">
                                        Simpanan Pokok
                                    </span>

                                    <span className="text-sm font-semibold text-slate-700">
                                        <Nominal nilai={simpananPokok} tampil={nominalTampil} />
                                    </span>
                                </div>

                                <div className="flex items-center justify-between">
                                    <span className="text-xs text-slate-500">
                                        Simpanan Wajib
                                    </span>

                                    <span className="text-sm font-semibold text-slate-700">
                                        <Nominal nilai={simpananWajib} tampil={nominalTampil} />
                                    </span>
                                </div>

                                <div className="pt-2.5 mt-2.5 border-t border-slate-100 flex items-center justify-between">
                                    <span className="text-sm font-semibold text-slate-600">
                                        Total Simpanan
                                    </span>

                                    <span className="text-base font-bold text-slate-800">
                                        <Nominal nilai={totalSimpanan} tampil={nominalTampil} />
                                    </span>
                                </div>

                                {settingSimpanan.length > 0 && (
                                    <details className="group pt-3.5 mt-3 border-t border-slate-100">
                                        <summary className="flex items-center gap-2 cursor-pointer select-none list-none [&::-webkit-details-marker]:hidden rounded focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-green focus-visible:ring-offset-2">
                                            <span className="text-xs uppercase tracking-wide font-semibold text-slate-400">
                                                Ketentuan Simpanan
                                            </span>
                                            <ArrowRight
                                                size={12}
                                                className="ml-auto text-slate-400 rotate-90 group-open:-rotate-90 transition-transform motion-reduce:transition-none"
                                            />
                                        </summary>

                                        <div className="space-y-1.5 mt-2">
                                            {settingSimpanan.map((s, i) => (
                                                <div
                                                    key={i}
                                                    className="flex items-center justify-between gap-3 text-xs"
                                                >
                                                    <span className="text-slate-500">
                                                        {s.label}
                                                    </span>

                                                    <span className="font-semibold text-slate-600">
                                                        {formatRupiah(s.nominal)}
                                                    </span>
                                                </div>
                                            ))}
                                        </div>
                                    </details>
                                )}
                            </div>
                        </div>

                        {/* Ketentuan Pinjaman */}
                        <details className="group bg-white rounded-xl border border-slate-100 p-4">
                            <summary className="flex items-center gap-2 cursor-pointer select-none list-none [&::-webkit-details-marker]:hidden rounded focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-green focus-visible:ring-offset-2">
                                <Percent size={16} className="text-brand-green shrink-0" />

                                <p className="text-sm font-bold text-slate-700">
                                    Ketentuan Pinjaman
                                </p>

                                <ArrowRight
                                    size={13}
                                    className="ml-auto text-slate-400 rotate-90 group-open:-rotate-90 transition-transform motion-reduce:transition-none"
                                />
                            </summary>

                            <div className="space-y-2 mt-3">
                                {tabelTenor.map((t, i) => (
                                    <div
                                        key={i}
                                        className="flex items-center justify-between gap-3 text-xs"
                                    >
                                        <span className="text-slate-500">
                                            {formatRupiah(t.nominal_min)}&ndash;{formatRupiah(t.nominal_max)}
                                        </span>

                                        <span className="font-semibold text-slate-600 shrink-0">
                                            Maks. {t.tenor_maksimal_bulan} bulan
                                        </span>
                                    </div>
                                ))}
                            </div>

                            <details className="group mt-3 pt-3 border-t border-slate-100">
                                <summary className="flex items-center gap-2 cursor-pointer select-none list-none [&::-webkit-details-marker]:hidden rounded focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-green focus-visible:ring-offset-2">
                                    <ShieldCheck size={15} className="text-brand-green shrink-0" />

                                    <span className="text-xs font-semibold text-slate-600">
                                        Alur persetujuan pinjaman
                                    </span>

                                    <ArrowRight
                                        size={13}
                                        className="ml-auto text-slate-400 rotate-90 group-open:-rotate-90 transition-transform motion-reduce:transition-none"
                                    />
                                </summary>

                                <p className="text-xs text-slate-500 leading-relaxed mt-2.5">
                                    Pengajuan diperiksa oleh Bendahara dan selanjutnya memperoleh
                                    persetujuan Ketua Koperasi sebelum proses pencairan.
                                    Informasi simpanan, pinjaman, dan angsuran ditampilkan
                                    berdasarkan data administrasi koperasi.
                                </p>
                            </details>
                        </details>
                    </div>
                </div>
            </div>
        </AnggotaLayout>
    );
}
