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

const focusRing =
    'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-green focus-visible:ring-offset-2';

function AlertLimitBerjalan({ pengajuan, limitSaatIni, tampil }) {
    if (!pengajuan) {
        return null;
    }

    const isKetua = pengajuan.status === 'approved_bendahara';
    const statusText = isKetua
        ? 'Menunggu persetujuan Ketua Koperasi'
        : 'Menunggu pemeriksaan Bendahara';

    return (
        <Link
            href={route('portal.pengajuan-limit.create')}
            className={`flex items-start gap-3 w-full p-4 rounded-2xl border bg-amber-50 border-amber-200 hover:bg-amber-100/60 transition-colors ${focusRing}`}
        >
            <Clock size={18} className="text-amber-600 shrink-0 mt-0.5" />
            <div className="min-w-0">
                <p className="text-sm font-bold text-amber-900">
                    Penambahan limit <Nominal nilai={pengajuan.limit_diminta} tampil={tampil} /> sedang diverifikasi
                </p>
                <p className="text-xs text-amber-700 mt-0.5 leading-relaxed">
                    {statusText}
                    {isKetua && pengajuan.limit_disetujui_bendahara ? (
                        <span>
                            {' • Usulan Bendahara: '}
                            <Nominal nilai={pengajuan.limit_disetujui_bendahara} tampil={tampil} />
                        </span>
                    ) : null}
                    {' • Diajukan '}{pengajuan.tanggal_pengajuan}.
                    {' '}Anda tetap dapat meminjam hingga plafon aktif saat ini <Nominal nilai={limitSaatIni} tampil={tampil} />.
                </p>
            </div>
        </Link>
    );
}

function CaptionLimitBerjalan({ pengajuan, tampil, limitMaksimal }) {
    if (!pengajuan) {
        return (
            <span>
                Plafon maksimal <Nominal nilai={limitMaksimal} tampil={tampil} />
            </span>
        );
    }

    const isKetua = pengajuan.status === 'approved_bendahara';

    return (
        <span className="inline-flex items-center gap-1.5 text-amber-300 font-medium">
            <Clock size={12} className="shrink-0" />
            <span className="truncate">
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

    // 2. Tambah Limit: hanya bisa jika eligibel, tidak sedang ada pengajuan limit berjalan, dan tidak ada resign shortfall.
    const bolehAjukanLimit = Boolean(bisaAjukan && !pengajuanLimitBerjalan && !resignMenunggu);

    // 3. Ubah Tenor: hanya jika ada pinjaman aktif, tidak ada pengajuan berjalan, tidak ada percepatan menunggu, dan ada pinjaman yang belum pakai percepatan.
    const bolehUbahTenor = Boolean(
        pinjamanAktifCount > 0 &&
        !pengajuanBerjalan &&
        pengajuanPercepatanMenunggu.length === 0 &&
        pinjamanAktifList.some((p) => !p.sudah_pakai_percepatan)
    );

    const { flash } = usePage().props;
    const initial = anggota?.nama?.charAt(0)?.toUpperCase() ?? '?';

    const terkirim = flash.pinjamanTerkirim;
    const percepatanTerkirim = flash.percepatanTerkirim;
    const limitTerkirim = flash.limitTerkirim;
    const [konfirmasiDitutup, setKonfirmasiDitutup] = useState(false);
    const [percepatanDitutup, setPercepatanDitutup] = useState(false);
    const [limitDitutup, setLimitDitutup] = useState(false);
    const [nominalTampil, alihNominal] = usePrivasiNominal();

    return (
        <AnggotaLayout>
            <Head title="Portal Anggota" />

            {/* =====================================================
                FLASH CONFIRMATION MODALS
            ====================================================== */}
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

            <div className="space-y-6 sm:space-y-8">
                <PwaInstallPrompt />

                {/* Resign Warning */}
                {resignMenunggu && (
                    <div className="flex items-start gap-3 bg-amber-50 border border-amber-200 rounded-2xl p-4">
                        <Clock size={20} className="text-amber-600 shrink-0 mt-0.5" />
                        <div>
                            <p className="text-sm font-bold text-amber-800">
                                Penyelesaian resign menunggu pelunasan akhir <Nominal nilai={resignMenunggu.shortfall} tampil={nominalTampil} />
                                {resignMenunggu.jatuh_tempo ? ` sebelum ${resignMenunggu.jatuh_tempo}` : ''}.
                            </p>
                            <p className="text-xs text-amber-700 mt-1">
                                Selesaikan melalui Bendahara (menu Konfirmasi Angsuran).
                                Pengajuan pinjaman dan limit baru dikunci sampai lunas.
                            </p>
                        </div>
                    </div>
                )}


                {/* =====================================================
                    MEMBER WELCOME HEADER (Modern Corporate - Option 2)
                ====================================================== */}
                <div className="flex items-center justify-between gap-4 pt-1 sm:pt-2">
                    <div className="min-w-0">
                        <div className="flex items-center gap-2">
                            <span className="text-xs sm:text-sm text-slate-500 font-medium">Selamat datang,</span>
                            <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-emerald-50 text-[10px] sm:text-[11px] font-bold text-brand-green-dark border border-emerald-100">
                                <span className="w-1.5 h-1.5 rounded-full bg-brand-green" />
                                Anggota Aktif
                            </span>
                        </div>
                        <h1 className="text-xl sm:text-2xl md:text-3xl font-extrabold text-slate-800 tracking-tight leading-tight truncate mt-1">
                            {anggota.nama}
                        </h1>
                        <p className="text-xs sm:text-sm text-slate-500 truncate mt-1">
                            NIP: {anggota.no_karyawan} &bull; Bergabung {anggota.lama_keanggotaan_label}
                        </p>
                    </div>

                    {/* Member Avatar Pill */}
                    {anggota.foto_url ? (
                        <img
                            src={anggota.foto_url}
                            alt={anggota.nama}
                            className="w-12 h-12 sm:w-16 sm:h-16 rounded-full object-cover border-2 border-white shadow-md shrink-0"
                            onError={(e) => {
                                e.currentTarget.style.display = 'none';
                            }}
                        />
                    ) : (
                        <div className="w-12 h-12 sm:w-16 sm:h-16 rounded-full bg-gradient-to-tr from-brand-green to-emerald-400 text-white flex items-center justify-center text-lg sm:text-2xl font-bold shadow-md shrink-0">
                            {initial}
                        </div>
                    )}
                </div>

                {/* =====================================================
                    DIGITAL MEMBER CARD (Executive Neobank)
                ====================================================== */}
                <div className="relative overflow-hidden rounded-2xl sm:rounded-3xl bg-gradient-to-br from-[#0B1527] via-[#0F1E36] to-[#0A2E1A] text-white p-5 sm:p-7 shadow-[0_16px_36px_rgba(15,30,54,0.22)] border border-white/10 group">
                    {/* Background Radial Glow */}
                    <div className="absolute -top-24 -right-24 w-72 h-72 bg-emerald-500/15 rounded-full blur-3xl pointer-events-none" />
                    <div className="absolute -bottom-24 -left-24 w-72 h-72 bg-blue-600/10 rounded-full blur-3xl pointer-events-none" />

                    {/* Subtle Grid Accent */}
                    <svg className="absolute inset-0 w-full h-full opacity-5 pointer-events-none" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <pattern id="card-mesh" width="28" height="28" patternUnits="userSpaceOnUse">
                                <path d="M0 28 L28 0 M0 0 L28 28" fill="none" stroke="currentColor" strokeWidth="0.5" />
                            </pattern>
                        </defs>
                        <rect width="100%" height="100%" fill="url(#card-mesh)" />
                    </svg>

                    <div className="relative z-10 flex flex-col justify-between min-h-[200px] sm:min-h-[220px]">
                        {/* Card Top: Chip & Status */}
                        <div className="flex items-start justify-between gap-4">
                            <div className="flex items-center gap-3">
                                {/* Microchip EMV */}
                                <div className="w-10 h-7 rounded bg-gradient-to-tr from-amber-300 via-amber-200 to-amber-400 p-[2px] border border-amber-400/60 shadow-inner flex flex-col justify-between shrink-0">
                                    <div className="w-full h-full border border-amber-700/30 rounded-[2px] flex items-center justify-center">
                                        <div className="w-3/4 h-1/2 border-t border-b border-amber-700/40" />
                                    </div>
                                </div>

                                {/* Contactless Wave */}
                                <svg className="w-5 h-5 text-white/40 rotate-90 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8.5 16.5a5 5 0 010-9M12 19a8.5 8.5 0 000-14M15.5 21.5a12 12 0 000-19" />
                                </svg>

                                <div className="hidden min-[380px]:block">
                                    <p className="text-[10px] font-bold tracking-widest text-emerald-400 uppercase">
                                        Koperasi App &bull; Digital Card
                                    </p>
                                    <p className="text-[11px] text-white/60">
                                        Anggota Aktif
                                    </p>
                                </div>
                            </div>

                            {/* Card Status & Privacy Toggle */}
                            <div className="flex items-center gap-2">
                                <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-500/20 border border-emerald-400/30 text-[11px] font-semibold text-emerald-300">
                                    <span className="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse" />
                                    Aktif
                                </span>
                                <button
                                    type="button"
                                    onClick={alihNominal}
                                    aria-label={nominalTampil ? 'Sembunyikan saldo' : 'Tampilkan saldo'}
                                    title={nominalTampil ? 'Sembunyikan saldo' : 'Tampilkan saldo'}
                                    className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-white/10 hover:bg-white/20 text-white/90 hover:text-white text-[11px] font-medium transition-colors border border-white/10"
                                >
                                    {nominalTampil ? <EyeOff size={13} /> : <Eye size={13} />}
                                    <span className="hidden min-[380px]:inline">{nominalTampil ? 'Sembunyikan' : 'Saldo'}</span>
                                </button>
                            </div>
                        </div>

                        {/* Card Center: Core Balances */}
                        <div className="my-5 sm:my-6 grid grid-cols-1 sm:grid-cols-2 gap-5 sm:gap-8 border-y border-white/10 py-4 sm:py-5">
                            <div>
                                <div className="flex items-center gap-1.5 text-xs text-slate-300 font-medium">
                                    <PiggyBank size={14} className="text-emerald-400" />
                                    <span className="tracking-wider uppercase">Total Simpanan</span>
                                </div>
                                <div className="mt-1 text-2xl sm:text-3xl font-extrabold text-white tracking-tight tabular-nums">
                                    <Nominal nilai={totalSimpanan} tampil={nominalTampil} />
                                </div>
                                <p className="mt-1 text-[11px] text-emerald-300/80">
                                    Simpanan Pokok & Wajib
                                </p>
                            </div>

                            <div className="sm:border-l sm:border-white/10 sm:pl-6">
                                <div className="flex items-center gap-1.5 text-xs text-slate-300 font-medium">
                                    <Gauge size={14} className="text-blue-400" />
                                    <span className="tracking-wider uppercase">Limit Tersedia</span>
                                </div>
                                <div className="mt-1 text-2xl sm:text-3xl font-extrabold text-emerald-300 tracking-tight tabular-nums">
                                    <Nominal nilai={limitTersedia} tampil={nominalTampil} />
                                </div>
                                <p className="mt-1 text-[11px] text-slate-300 truncate">
                                    <CaptionLimitBerjalan pengajuan={pengajuanLimitBerjalan} tampil={nominalTampil} limitMaksimal={limitMaksimal} />
                                </p>
                            </div>
                        </div>

                        {/* Card Footer: Member ID & Details */}
                        <div className="flex items-end justify-between gap-3 text-xs">
                            <div className="min-w-0">
                                <p className="text-[10px] font-semibold text-slate-400 tracking-wider uppercase">
                                    Nama Anggota
                                </p>
                                <p className="text-sm font-bold text-white tracking-wider truncate uppercase">
                                    {anggota.nama}
                                </p>
                            </div>

                            <div className="text-right shrink-0">
                                <p className="text-[10px] font-semibold text-slate-400 tracking-wider uppercase">
                                    No. Anggota
                                </p>
                                <p className="text-xs sm:text-sm font-mono font-bold text-slate-200 tracking-widest">
                                    {anggota.no_anggota || anggota.no_karyawan}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {/* =====================================================
                    QUICK ACTION DOCK
                ====================================================== */}
                <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    {/* 1. Ajukan Pinjaman */}
                    {bolehAjukanPinjaman ? (
                        <Link
                            href={route('portal.pinjaman.create')}
                            className={`group flex flex-col justify-between p-3.5 sm:p-4 rounded-2xl bg-white border border-slate-100 hover:border-brand-green/40 shadow-sm hover:shadow-md transition-all active:scale-[0.98] ${focusRing}`}
                        >
                            <div className="flex items-center justify-between">
                                <div className="w-10 h-10 rounded-xl bg-brand-green-light text-brand-green flex items-center justify-center group-hover:bg-brand-green group-hover:text-white transition-colors shadow-sm">
                                    <Plus size={20} strokeWidth={2.5} />
                                </div>
                                <span className="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-brand-green-dark border border-emerald-100">
                                    Tersedia
                                </span>
                            </div>
                            <div className="mt-3">
                                <p className="text-sm font-bold text-slate-800 group-hover:text-brand-green transition-colors">
                                    Ajukan Pinjaman
                                </p>
                                <p className="text-[11px] text-slate-500 mt-0.5">
                                    Plafon siap digunakan
                                </p>
                            </div>
                        </Link>
                    ) : (
                        <div
                            className="flex flex-col justify-between p-3.5 sm:p-4 rounded-2xl bg-slate-50/80 border border-slate-200/80 opacity-70 cursor-not-allowed select-none"
                        >
                            <div className="flex items-center justify-between">
                                <div className="w-10 h-10 rounded-xl bg-slate-200 text-slate-500 flex items-center justify-center">
                                    <Plus size={20} />
                                </div>
                                <span className="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-slate-200 text-slate-600">
                                    {pengajuanBerjalan
                                        ? 'Diproses'
                                        : limitTersedia <= 0
                                        ? 'Plafon Rp0'
                                        : 'Terkunci'}
                                </span>
                            </div>
                            <div className="mt-3">
                                <p className="text-sm font-bold text-slate-700">
                                    Ajukan Pinjaman
                                </p>
                                <p className="text-[11px] text-slate-400 mt-0.5 truncate">
                                    {pengajuanBerjalan
                                        ? 'Dalam proses approval'
                                        : limitTersedia <= 0
                                        ? 'Plafon limit habis'
                                        : 'Belum memenuhi syarat'}
                                </p>
                            </div>
                        </div>
                    )}

                    {/* 2. Tambah Limit */}
                    {bolehAjukanLimit ? (
                        <Link
                            href={route('portal.pengajuan-limit.create')}
                            className={`group flex flex-col justify-between p-3.5 sm:p-4 rounded-2xl bg-white border border-slate-100 hover:border-blue-400/40 shadow-sm hover:shadow-md transition-all active:scale-[0.98] ${focusRing}`}
                        >
                            <div className="flex items-center justify-between">
                                <div className="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center group-hover:bg-blue-600 group-hover:text-white transition-colors shadow-sm">
                                    <TrendingUp size={20} />
                                </div>
                                <span className="text-[10px] font-bold px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 border border-blue-100">
                                    Plafon
                                </span>
                            </div>
                            <div className="mt-3">
                                <p className="text-sm font-bold text-slate-800 group-hover:text-blue-600 transition-colors">
                                    Tambah Limit
                                </p>
                                <p className="text-[11px] text-slate-500 mt-0.5">
                                    Ajukan kenaikan
                                </p>
                            </div>
                        </Link>
                    ) : (
                        <div
                            className="flex flex-col justify-between p-3.5 sm:p-4 rounded-2xl bg-slate-50/80 border border-slate-200/80 opacity-70 cursor-not-allowed select-none"
                        >
                            <div className="flex items-center justify-between">
                                <div className="w-10 h-10 rounded-xl bg-slate-200 text-slate-500 flex items-center justify-center">
                                    <TrendingUp size={20} />
                                </div>
                                <span className="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-slate-200 text-slate-600">
                                    {pengajuanLimitBerjalan ? 'Diproses' : 'Terkunci'}
                                </span>
                            </div>
                            <div className="mt-3">
                                <p className="text-sm font-bold text-slate-700">
                                    Tambah Limit
                                </p>
                                <p className="text-[11px] text-slate-400 mt-0.5 truncate">
                                    {pengajuanLimitBerjalan ? 'Sedang diverifikasi' : 'Belum memenuhi'}
                                </p>
                            </div>
                        </div>
                    )}

                    {/* 3. Ubah Tenor */}
                    {bolehUbahTenor ? (
                        <Link
                            href={route('portal.percepatan.create')}
                            className={`group flex flex-col justify-between p-3.5 sm:p-4 rounded-2xl bg-white border border-slate-100 hover:border-amber-400/40 shadow-sm hover:shadow-md transition-all active:scale-[0.98] ${focusRing}`}
                        >
                            <div className="flex items-center justify-between">
                                <div className="w-10 h-10 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center group-hover:bg-amber-600 group-hover:text-white transition-colors shadow-sm">
                                    <Repeat size={19} />
                                </div>
                                <span className="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-200">
                                    Tersedia
                                </span>
                            </div>
                            <div className="mt-3">
                                <p className="text-sm font-bold text-slate-800 group-hover:text-amber-700 transition-colors">
                                    Ubah Tenor
                                </p>
                                <p className="text-[11px] text-slate-500 mt-0.5">
                                    Percepat / perpanjang
                                </p>
                            </div>
                        </Link>
                    ) : (
                        <div
                            className="flex flex-col justify-between p-3.5 sm:p-4 rounded-2xl bg-slate-50/80 border border-slate-200/80 opacity-70 cursor-not-allowed select-none"
                        >
                            <div className="flex items-center justify-between">
                                <div className="w-10 h-10 rounded-xl bg-slate-200 text-slate-500 flex items-center justify-center">
                                    <Repeat size={19} />
                                </div>
                                <span className="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-slate-200 text-slate-600">
                                    {pengajuanPercepatanMenunggu.length > 0
                                        ? 'Diproses'
                                        : pinjamanAktifCount === 0
                                        ? 'Nonaktif'
                                        : 'Sudah Dipakai'}
                                </span>
                            </div>
                            <div className="mt-3">
                                <p className="text-sm font-bold text-slate-700">
                                    Ubah Tenor
                                </p>
                                <p className="text-[11px] text-slate-400 mt-0.5 truncate">
                                    {pengajuanPercepatanMenunggu.length > 0
                                        ? 'Sedang diproses'
                                        : pinjamanAktifCount === 0
                                        ? 'Khusus pinjaman aktif'
                                        : 'Hak telah digunakan'}
                                </p>
                            </div>
                        </div>
                    )}

                    {/* 4. Tata Cara & Bantuan */}
                    <button
                        type="button"
                        onClick={() => window.dispatchEvent(new CustomEvent('open-panduan'))}
                        className={`group text-left flex flex-col justify-between p-3.5 sm:p-4 rounded-2xl bg-white border border-slate-100 hover:border-purple-400/40 shadow-sm hover:shadow-md transition-all active:scale-[0.98] ${focusRing}`}
                    >
                        <div className="flex items-center justify-between">
                            <div className="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center group-hover:bg-purple-600 group-hover:text-white transition-colors shadow-sm">
                                <Info size={20} />
                            </div>
                            <span className="text-[10px] font-bold px-2 py-0.5 rounded-full bg-purple-50 text-purple-700 border border-purple-100">
                                Panduan
                            </span>
                        </div>
                        <div className="mt-3">
                            <p className="text-sm font-bold text-slate-800 group-hover:text-purple-600 transition-colors">
                                Tata Cara
                            </p>
                            <p className="text-[11px] text-slate-500 mt-0.5">
                                Syarat & aturan
                            </p>
                        </div>
                    </button>
                </div>

                {/* =====================================================
                    PENDING APPLICATION TRACKER (3-Jenjang Visual Stepper)
                ====================================================== */}
                {pengajuanBerjalan && (() => {
                    const currentStep =
                        pengajuanBerjalan.status === 'approved_bendahara' ? 3
                        : pengajuanBerjalan.status === 'approved_admin' ? 2
                        : 1;

                    return (
                        <div className="bg-white rounded-2xl border border-amber-200/90 shadow-sm p-4 sm:p-5 relative overflow-hidden">
                            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-4 border-b border-slate-100">
                                <div>
                                    <div className="flex items-center gap-2">
                                        <span className="w-2.5 h-2.5 rounded-full bg-amber-500 animate-ping" />
                                        <p className="text-xs font-bold text-amber-800 uppercase tracking-wider">
                                            Pelacakan Pengajuan Pinjaman (Sedang Diproses)
                                        </p>
                                    </div>
                                    <div className="mt-1 flex items-baseline gap-2">
                                        <span className="text-xl sm:text-2xl font-bold text-slate-800">
                                            <Nominal nilai={pengajuanBerjalan.nominal} tampil={nominalTampil} />
                                        </span>
                                        <span className="text-xs text-slate-400">
                                            &bull; Diajukan {pengajuanBerjalan.tanggal_pengajuan}
                                        </span>
                                    </div>
                                </div>

                                <Link
                                    href={route('portal.riwayat')}
                                    className={`self-start sm:self-auto inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-50 hover:bg-slate-100 text-xs font-semibold text-slate-600 transition-colors ${focusRing}`}
                                >
                                    <span>Lihat di Riwayat</span>
                                    <ArrowRight size={13} />
                                </Link>
                            </div>

                            {/* 3-Step Visual Stepper */}
                            <div className="pt-5 pb-1">
                                <div className="relative flex items-center justify-between">
                                    {/* Connecting Bar */}
                                    <div className="absolute left-8 right-8 top-1/2 -translate-y-1/2 h-1 bg-slate-100 -z-0">
                                        <div
                                            className="h-full bg-brand-green transition-all duration-500"
                                            style={{
                                                width: currentStep === 3 ? '100%' : currentStep === 2 ? '50%' : '0%',
                                            }}
                                        />
                                    </div>

                                    {/* Step 1: Pemeriksaan Admin */}
                                    <div className="relative z-10 flex flex-col items-center">
                                        <div
                                            className={`w-9 h-9 rounded-full flex items-center justify-center shadow-sm transition-colors ${
                                                currentStep > 1
                                                    ? 'bg-brand-green text-white'
                                                    : 'bg-amber-500 text-white ring-4 ring-amber-100 animate-pulse'
                                            }`}
                                        >
                                            {currentStep > 1 ? <CheckCircle2 size={18} /> : <Clock size={16} />}
                                        </div>
                                        <span className="mt-2 text-xs font-bold text-slate-800 text-center">
                                            Admin
                                        </span>
                                        <span className="text-[10px] text-slate-400 hidden sm:block">
                                            {currentStep > 1 ? 'Selesai diverifikasi' : 'Sedang diperiksa'}
                                        </span>
                                    </div>

                                    {/* Step 2: Pemeriksaan Bendahara */}
                                    <div className="relative z-10 flex flex-col items-center">
                                        <div
                                            className={`w-9 h-9 rounded-full flex items-center justify-center shadow-sm transition-colors ${
                                                currentStep > 2
                                                    ? 'bg-brand-green text-white'
                                                    : currentStep === 2
                                                    ? 'bg-amber-500 text-white ring-4 ring-amber-100 animate-pulse'
                                                    : 'bg-slate-100 text-slate-400 border border-slate-200'
                                            }`}
                                        >
                                            {currentStep > 2 ? (
                                                <CheckCircle2 size={18} />
                                            ) : (
                                                <Clock size={16} />
                                            )}
                                        </div>
                                        <span className="mt-2 text-xs font-bold text-slate-800 text-center">
                                            Bendahara
                                        </span>
                                        <span className="text-[10px] text-slate-400 hidden sm:block">
                                            {currentStep > 2
                                                ? 'Selesai diverifikasi'
                                                : currentStep === 2
                                                ? 'Sedang diverifikasi'
                                                : 'Menunggu Admin'}
                                        </span>
                                    </div>

                                    {/* Step 3: Persetujuan Ketua */}
                                    <div className="relative z-10 flex flex-col items-center">
                                        <div
                                            className={`w-9 h-9 rounded-full flex items-center justify-center shadow-sm transition-colors ${
                                                currentStep === 3
                                                    ? 'bg-amber-500 text-white ring-4 ring-amber-100 animate-pulse'
                                                    : 'bg-slate-100 text-slate-400 border border-slate-200'
                                            }`}
                                        >
                                            <ShieldCheck size={16} />
                                        </div>
                                        <span className="mt-2 text-xs font-bold text-slate-800 text-center">
                                            Ketua Koperasi
                                        </span>
                                        <span className="text-[10px] text-slate-400 hidden sm:block">
                                            {currentStep === 3 ? 'Tahap persetujuan' : 'Menunggu Bendahara'}
                                        </span>
                                    </div>
                                </div>

                                {/* Status Explanation Note */}
                                <div className="mt-5 p-3 rounded-xl bg-amber-50/80 border border-amber-200/60 text-xs text-amber-800 flex items-center gap-2">
                                    <Info size={15} className="shrink-0 text-amber-600" />
                                    <span>
                                        {statusPengajuanLabel[pengajuanBerjalan.status]?.text ?? 'Menunggu pemeriksaan'}.
                                        {pengajuanBerjalan.nominal_disetujui_bendahara && (
                                            <span className="font-semibold">
                                                {' '}Usulan Bendahara:{' '}
                                                <Nominal nilai={pengajuanBerjalan.nominal_disetujui_bendahara} tampil={nominalTampil} />
                                            </span>
                                        )}
                                        {' '}&bull; Pengajuan pinjaman baru dikunci hingga proses ini selesai diputuskan.
                                    </span>
                                </div>
                            </div>
                        </div>
                    );
                })()}

                {/* Not Eligible / Reason Locked Alert */}
                {!bisaAjukan && !pengajuanBerjalan && (
                    <div className="flex items-start gap-3 bg-amber-50 border border-amber-200 rounded-2xl p-4">
                        <Info className="text-amber-600 shrink-0 mt-0.5" size={18} />
                        <div className="min-w-0">
                            <p className="text-sm font-bold text-amber-900">
                                Pengajuan Pinjaman Belum Memenuhi Ketentuan
                            </p>
                            <p className="text-xs text-amber-800 mt-1 leading-relaxed">
                                {alasanTidakBisa || 'Anda belum dapat mengajukan pinjaman saat ini sesuai ketentuan koperasi.'}
                            </p>
                        </div>
                    </div>
                )}

                {/* Limit Exhausted Alert */}
                {limitTersedia <= 0 && bisaAjukan && !pengajuanBerjalan && (
                    <div className="flex items-start gap-3 bg-blue-50 border border-blue-200 rounded-2xl p-4">
                        <Info className="text-blue-600 shrink-0 mt-0.5" size={18} />
                        <div className="min-w-0">
                            <p className="text-sm font-bold text-blue-900">
                                Plafon Pinjaman Anda Saat Ini Telah Digunakan Seluruhnya (Rp0)
                            </p>
                            <p className="text-xs text-blue-800 mt-1 leading-relaxed">
                                Pengajuan pinjaman baru dibuka kembali setelah angsuran berjalan tersisa maksimal 2 bulan, atau Anda dapat mengajukan penambahan limit melalui menu Tambah Limit.
                            </p>
                        </div>
                    </div>
                )}

                {/* Limit Request in Progress */}
                {pengajuanLimitBerjalan && (
                    <AlertLimitBerjalan pengajuan={pengajuanLimitBerjalan} limitSaatIni={limitMaksimal} tampil={nominalTampil} />
                )}

                {/* Tenor Change in Progress */}
                {pinjamanAktifCount > 0 && pengajuanPercepatanMenunggu.length > 0 && (
                    <div className="flex items-start gap-3 bg-amber-50 border border-amber-200 rounded-2xl p-4">
                        <Repeat className="text-amber-600 shrink-0 mt-0.5" size={18} />
                        <div className="min-w-0">
                            <p className="text-sm font-bold text-amber-800">
                                Pengajuan Perubahan Tenor / Pelunasan Dalam Proses
                            </p>
                            <div className="mt-1 space-y-1">
                                {pengajuanPercepatanMenunggu.map((pp) => (
                                    <p key={pp.id} className="text-xs text-amber-700">
                                        {tipeLabel[pp.tipe] || pp.tipe} &bull; Pinjaman{' '}
                                        <Nominal nilai={pp.pinjaman_nominal} tampil={nominalTampil} /> &bull;{' '}
                                        {pp.tenor_lama} &rarr; {pp.tenor_baru ?? 'lunas'} bulan &bull;{' '}
                                        {statusPengajuanLabel[pp.status]?.text ?? pp.status}
                                    </p>
                                ))}
                            </div>
                        </div>
                    </div>
                )}

                {/* Rejected Loan Alert */}
                {pengajuanDitolak && !pengajuanBerjalan && (
                    <div className="flex items-start gap-3 bg-red-50 border border-red-200 rounded-2xl p-4">
                        <XCircle className="text-red-600 shrink-0 mt-0.5" size={18} />
                        <div className="min-w-0">
                            <p className="text-sm font-bold text-red-800">
                                Pengajuan Pinjaman <Nominal nilai={pengajuanDitolak.nominal} tampil={nominalTampil} /> Ditolak
                            </p>
                            {pengajuanDitolak.catatan && (
                                <p className="text-xs text-red-700 mt-1 leading-relaxed">
                                    Alasan: {pengajuanDitolak.catatan}
                                </p>
                            )}
                            <p className="text-xs text-red-600/80 mt-1">
                                Anda dapat mengajukan kembali pinjaman setelah menyesuaikan nominal atau persyaratan.
                            </p>
                        </div>
                    </div>
                )}

                {/* =====================================================
                    MAIN CONTENT 2-COLUMN GRID
                ====================================================== */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    {/* =================================================
                        LEFT COLUMN (Primary Operations & Activity)
                    ================================================== */}
                    <div className="lg:col-span-2 space-y-6">

                        {/* =============================================
                            SMART DUE-DATE TICKET
                        ============================================== */}
                        {pinjamanAktifCount > 0 && angsuranBerikutnya && (
                            <Link
                                href={route('portal.riwayat')}
                                className={`group relative flex flex-col sm:flex-row items-stretch bg-gradient-to-r from-emerald-50/90 via-white to-emerald-50/60 border border-emerald-200/90 rounded-2xl shadow-sm hover:shadow-md transition-all overflow-hidden ${focusRing}`}
                            >
                                {/* Perforated ticket cutouts */}
                                <span aria-hidden="true" className="hidden sm:block absolute -left-3 top-1/2 -translate-y-1/2 w-5 h-5 rounded-full bg-slate-50 border-r border-emerald-200/80" />
                                <span aria-hidden="true" className="hidden sm:block absolute -right-3 top-1/2 -translate-y-1/2 w-5 h-5 rounded-full bg-slate-50 border-l border-emerald-200/80" />

                                <div className="flex items-center gap-3.5 flex-1 p-4 sm:p-5">
                                    <div className="w-11 h-11 rounded-xl bg-brand-green text-white flex items-center justify-center shrink-0 shadow-sm">
                                        <CalendarDays size={20} />
                                    </div>
                                    <div className="min-w-0">
                                        <div className="flex items-center gap-2">
                                            <span className="text-[10px] font-bold uppercase tracking-wider text-brand-green-dark bg-emerald-100/70 px-2 py-0.5 rounded-md">
                                                Jatuh Tempo Berikutnya
                                            </span>
                                        </div>
                                        <p className="text-sm sm:text-base font-bold text-slate-800 mt-1 truncate">
                                            Angsuran ke-{angsuranBerikutnya.cicilan_ke}
                                            <span className="text-slate-400 font-normal"> &bull; </span>
                                            {angsuranBerikutnya.tanggal_jatuh_tempo}
                                        </p>
                                        <p className="text-xs text-slate-500 mt-0.5">
                                            Pembayaran dipotong via payroll atau transfer rutin
                                        </p>
                                    </div>
                                </div>

                                {/* Ticket Perforation Divider */}
                                <div className="flex sm:flex-col items-center justify-between sm:justify-center gap-3 px-4 py-3 sm:px-6 sm:py-4 bg-emerald-100/30 sm:border-l-2 sm:border-dashed sm:border-emerald-200/80 border-t border-dashed border-emerald-200/80 shrink-0">
                                    <div>
                                        <p className="text-[11px] font-semibold text-slate-500 uppercase tracking-wide">
                                            Total Bayar
                                        </p>
                                        <p className="text-base sm:text-lg font-extrabold text-slate-800 tabular-nums">
                                            <Nominal nilai={angsuranBerikutnya.total_bayar} tampil={nominalTampil} />
                                        </p>
                                    </div>
                                    <div className="flex items-center gap-1 text-xs font-semibold text-brand-green group-hover:translate-x-0.5 transition-transform">
                                        <span>Rincian</span>
                                        <ArrowRight size={14} />
                                    </div>
                                </div>
                            </Link>
                        )}

                        {/* =============================================
                            ACTIVE LOAN PROGRESS CARD
                        ============================================== */}
                        {pinjamanAktifCount > 0 ? (
                            <div className="bg-white rounded-2xl border border-slate-100 p-5 sm:p-6 shadow-sm space-y-4">
                                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                    <div>
                                        <div className="flex items-center gap-2">
                                            <Wallet size={16} className="text-brand-green" />
                                            <p className="text-xs font-bold text-slate-500 uppercase tracking-wider">
                                                Status Pinjaman Berjalan {pinjamanAktifCount > 1 ? `(${pinjamanAktifCount} Pinjaman)` : ''}
                                            </p>
                                        </div>
                                        <div className="mt-1 flex items-baseline gap-2">
                                            <span className="text-2xl font-extrabold text-slate-800 tabular-nums">
                                                <Nominal nilai={aggregateSisaTotalBayar} tampil={nominalTampil} />
                                            </span>
                                            <span className="text-xs text-slate-400">
                                                sisa pokok & bunga
                                            </span>
                                        </div>
                                    </div>

                                    <div className="flex items-center gap-2">
                                        <span className="inline-flex items-center px-3 py-1 rounded-full bg-emerald-50 text-brand-green-dark border border-emerald-200 text-xs font-bold">
                                            {progress}% Lunas
                                        </span>
                                    </div>
                                </div>

                                {/* Progress Bar */}
                                <div>
                                    <div className="w-full h-2.5 bg-slate-100 rounded-full overflow-hidden">
                                        <div
                                            className="h-full bg-gradient-to-r from-brand-green to-emerald-400 rounded-full transition-all duration-500"
                                            style={{ width: `${progress}%` }}
                                        />
                                    </div>
                                    <div className="flex items-center justify-between mt-2 text-xs text-slate-500">
                                        <span>
                                            {aggregateTotalAngsuran - aggregateTotalSisaAngsuran} angsuran terbayar
                                        </span>
                                        <span>
                                            Sisa {aggregateTotalSisaAngsuran} angsuran
                                        </span>
                                    </div>
                                </div>

                                {/* Metric Highlights */}
                                <div className="grid grid-cols-2 gap-3 pt-2">
                                    <div className="bg-slate-50 rounded-xl p-3 border border-slate-100">
                                        <p className="text-xs text-slate-500">Cicilan per bulan</p>
                                        <p className="text-sm font-bold text-slate-800 mt-0.5 tabular-nums">
                                            <Nominal nilai={cicilanPerBulan} tampil={nominalTampil} />
                                        </p>
                                    </div>
                                    <div className="bg-slate-50 rounded-xl p-3 border border-slate-100">
                                        <p className="text-xs text-slate-500">Total pinjaman awal</p>
                                        <p className="text-sm font-bold text-slate-800 mt-0.5 tabular-nums">
                                            <Nominal nilai={aggregateTotalNominal} tampil={nominalTampil} />
                                        </p>
                                    </div>
                                </div>

                                {/* Notice if used early acceleration */}
                                {pengajuanPercepatanMenunggu.length === 0 && pinjamanAktifList.some((p) => p.sudah_pakai_percepatan) && (
                                    <div className="flex items-start gap-2.5 p-3 rounded-xl bg-slate-50 border border-slate-200/80 text-xs text-slate-500">
                                        <Info size={16} className="text-slate-400 shrink-0 mt-0.5" />
                                        <p className="leading-relaxed">
                                            Hak perubahan tenor atau pelunasan dipercepat telah digunakan pada salah satu pinjaman berjalan.
                                        </p>
                                    </div>
                                )}
                            </div>
                        ) : !pengajuanBerjalan && (
                            <div className="bg-white rounded-2xl border border-slate-100 p-5 sm:p-6 shadow-sm">
                                <div className="space-y-1">
                                    <div className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 text-brand-green-dark text-xs font-semibold">
                                        <CheckCircle2 size={14} />
                                        Bebas Beban Pinjaman
                                    </div>
                                    <h2 className="text-base font-bold text-slate-800">
                                        Saat ini Anda tidak memiliki pinjaman aktif
                                    </h2>
                                    <p className="text-xs text-slate-500 max-w-lg leading-relaxed">
                                        Plafon limit pinjaman Anda sebesar <Nominal nilai={limitMaksimal} tampil={nominalTampil} /> siap diajukan kapan saja melalui menu di atas sesuai kebutuhan Anda.
                                    </p>
                                </div>
                            </div>
                        )}

                        {/* =============================================
                            RECENT ACTIVITY FEED
                        ============================================== */}
                        <div className="bg-white rounded-2xl border border-slate-100 overflow-hidden shadow-sm">
                            <div className="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                                <div>
                                    <h3 className="text-sm font-bold text-slate-800">
                                        Aktivitas Terbaru
                                    </h3>
                                    <p className="text-xs text-slate-400 mt-0.5">
                                        Transaksi simpanan & pembayaran terakhir
                                    </p>
                                </div>

                                <Link
                                    href={route('portal.riwayat')}
                                    className={`text-xs font-semibold text-brand-green flex items-center gap-1 rounded px-2 py-1 hover:text-brand-green-dark hover:bg-emerald-50 transition-colors ${focusRing}`}
                                >
                                    Lihat riwayat
                                    <ArrowRight size={13} />
                                </Link>
                            </div>

                            {riwayatGabungan.length === 0 ? (
                                <div className="text-center py-10 px-4">
                                    <p className="text-sm text-slate-400">
                                        Belum ada aktivitas transaksi.
                                    </p>
                                </div>
                            ) : (
                                <div className="divide-y divide-slate-100">
                                    {riwayatGabungan.map((item, i) => (
                                        <div key={i} className="flex items-center gap-3.5 px-5 py-3.5 hover:bg-slate-50/60 transition-colors">
                                            {item.tipe === 'simpanan' ? (
                                                <div className="w-9 h-9 rounded-full bg-brand-green-light text-brand-green-dark flex items-center justify-center shrink-0">
                                                    <ArrowDownCircle size={16} />
                                                </div>
                                            ) : (
                                                <div className="w-9 h-9 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                                                    <CheckCircle2 size={16} />
                                                </div>
                                            )}

                                            <div className="flex-1 min-w-0">
                                                <p className="text-sm font-semibold text-slate-800 truncate">
                                                    {item.label}
                                                </p>
                                                <p className="text-xs text-slate-400 mt-0.5">
                                                    {item.tanggal_format}
                                                </p>
                                            </div>

                                            <p className="text-sm font-bold text-slate-800 shrink-0 tabular-nums">
                                                <Nominal nilai={item.nominal} tampil={nominalTampil} />
                                            </p>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>

                    {/* =================================================
                        RIGHT COLUMN (Financial Breakdown & Reference)
                    ================================================== */}
                    <div className="space-y-6 sm:space-y-8">

                        {/* =============================================
                            MINI ASSET BREAKDOWN (Rincian Simpanan)
                        ============================================== */}
                        <div className="bg-white rounded-2xl border border-slate-100 p-5 shadow-sm space-y-4">
                            <div className="flex items-center justify-between">
                                <div className="flex items-center gap-2">
                                    <div className="w-8 h-8 rounded-lg bg-emerald-50 text-brand-green flex items-center justify-center">
                                        <PiggyBank size={18} />
                                    </div>
                                    <div>
                                        <h3 className="text-sm font-bold text-slate-800">Rincian Simpanan</h3>
                                        <p className="text-[11px] text-slate-400">Total akumulasi aset</p>
                                    </div>
                                </div>
                                <span className="text-sm font-extrabold text-slate-800 tabular-nums">
                                    <Nominal nilai={totalSimpanan} tampil={nominalTampil} />
                                </span>
                            </div>

                            {/* Proportional Progress Bar */}
                            {totalSimpanan > 0 && (
                                <div className="space-y-1.5 pt-1">
                                    <div className="w-full h-3 rounded-full overflow-hidden flex bg-slate-100">
                                        <div
                                            className="h-full bg-brand-green"
                                            style={{ width: `${Math.round((simpananWajib / totalSimpanan) * 100)}%` }}
                                            title={`Simpanan Wajib: ${Math.round((simpananWajib / totalSimpanan) * 100)}%`}
                                        />
                                        <div
                                            className="h-full bg-brand-navy"
                                            style={{ width: `${Math.round((simpananPokok / totalSimpanan) * 100)}%` }}
                                            title={`Simpanan Pokok: ${Math.round((simpananPokok / totalSimpanan) * 100)}%`}
                                        />
                                    </div>
                                    <div className="flex items-center justify-between text-[11px] text-slate-500">
                                        <span className="flex items-center gap-1.5">
                                            <span className="w-2 h-2 rounded-full bg-brand-green inline-block" />
                                            Wajib ({totalSimpanan > 0 ? Math.round((simpananWajib / totalSimpanan) * 100) : 0}%)
                                        </span>
                                        <span className="flex items-center gap-1.5">
                                            <span className="w-2 h-2 rounded-full bg-brand-navy inline-block" />
                                            Pokok ({totalSimpanan > 0 ? Math.round((simpananPokok / totalSimpanan) * 100) : 0}%)
                                        </span>
                                    </div>
                                </div>
                            )}

                            {/* Itemized Simpanan */}
                            <div className="divide-y divide-slate-100 pt-1">
                                <div className="py-2.5 flex items-center justify-between text-xs">
                                    <span className="text-slate-600 font-medium">Simpanan Wajib</span>
                                    <span className="font-bold text-slate-800 tabular-nums">
                                        <Nominal nilai={simpananWajib} tampil={nominalTampil} />
                                    </span>
                                </div>
                                <div className="py-2.5 flex items-center justify-between text-xs">
                                    <span className="text-slate-600 font-medium">Simpanan Pokok</span>
                                    <span className="font-bold text-slate-800 tabular-nums">
                                        <Nominal nilai={simpananPokok} tampil={nominalTampil} />
                                    </span>
                                </div>
                            </div>

                            {/* Setting Simpanan Details */}
                            {settingSimpanan.length > 0 && (
                                <details className="group pt-2 border-t border-slate-100">
                                    <summary className="flex items-center gap-2 cursor-pointer select-none list-none text-xs font-semibold text-slate-500 hover:text-slate-700">
                                        <span>Ketentuan Tarif Simpanan</span>
                                        <ChevronRight size={13} className="ml-auto group-open:rotate-90 transition-transform" />
                                    </summary>
                                    <div className="mt-2.5 space-y-1.5 bg-slate-50 rounded-xl p-3 border border-slate-100 text-xs">
                                        {settingSimpanan.map((s, i) => (
                                            <div key={i} className="flex items-center justify-between text-slate-600">
                                                <span>{s.label}</span>
                                                <span className="font-semibold text-slate-800">{formatRupiah(s.nominal)}</span>
                                            </div>
                                        ))}
                                    </div>
                                </details>
                            )}
                        </div>

                        {/* =============================================
                            KETENTUAN PINJAMAN & TENOR
                        ============================================== */}
                        <div className="bg-white rounded-2xl border border-slate-100 p-5 shadow-sm space-y-3">
                            <div className="flex items-center gap-2">
                                <div className="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                                    <Percent size={16} />
                                </div>
                                <div>
                                    <h3 className="text-sm font-bold text-slate-800">Ketentuan & Plafon</h3>
                                    <p className="text-[11px] text-slate-400">Aturan tenor pinjaman</p>
                                </div>
                            </div>

                            <div className="space-y-1.5 pt-1">
                                {tabelTenor.map((t, i) => (
                                    <div key={i} className="flex items-center justify-between text-xs py-1.5 border-b border-slate-50 last:border-0">
                                        <span className="text-slate-500">
                                            {formatRupiah(t.nominal_min)} &ndash; {formatRupiah(t.nominal_max)}
                                        </span>
                                        <span className="font-semibold text-slate-700 bg-slate-50 px-2 py-0.5 rounded">
                                            Maks. {t.tenor_maksimal_bulan} bulan
                                        </span>
                                    </div>
                                ))}
                            </div>

                            {/* Alur Persetujuan */}
                            <div className="mt-3 pt-3 border-t border-slate-100 flex items-start gap-2 text-xs text-slate-500 bg-slate-50/70 rounded-xl p-3">
                                <ShieldCheck size={16} className="text-brand-green shrink-0 mt-0.5" />
                                <p className="leading-relaxed">
                                    Pengajuan pinjaman diproses berjenjang: verifikasi Admin, pemeriksaan Bendahara, dan persetujuan Ketua Koperasi.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AnggotaLayout>
    );
}
