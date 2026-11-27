import { Suspense, lazy, useMemo, useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Head, Link, usePage } from '@inertiajs/react';
import {
    PiggyBank, HandCoins, Wallet, Banknote, HeartHandshake, Landmark,
    ChevronRight, BookOpenText,
    HandCoins as PinjamanIcon, CheckCircle2,
} from 'lucide-react';
import DashboardClock from '@/Components/ui/DashboardClock';
import PrivacyWrapper from '@/Components/ui/PrivacyWrapper';
import { usePrivacy } from '@/Contexts/PrivacyContext';
import { formatRupiah } from '@/Utils/formatCurrency';
import { statusLabel, statusStyle } from '@/Utils/status';

const DashboardTrenChart = lazy(() => import('@/Pages/Dashboard/Partials/DashboardTrenChart'));
const DashboardKasChart = lazy(() => import('@/Pages/Dashboard/Partials/DashboardKasChart'));

const focusRing =
    'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-navy/25';

const panelSurface =
    'rounded-2xl bg-white border border-slate-200/95 ring-1 ring-slate-900/[0.04] shadow-[0_1px_3px_rgba(15,30,54,0.07),0_10px_26px_-10px_rgba(15,30,54,0.12)]';

function ChartSkeleton({ className = '' }) {
    return (
        <div className={`${panelSurface} p-5 sm:p-6 ${className}`}>
            <div className="flex items-center justify-between mb-5">
                <div>
                    <div className="h-4 w-44 bg-slate-200/80 rounded animate-pulse mb-1.5" />
                    <div className="h-3 w-28 bg-slate-100 rounded animate-pulse" />
                </div>
            </div>
            <div className="h-[260px] w-full bg-slate-50/60 rounded-xl flex items-end justify-between gap-2 p-6">
                <div className="w-full bg-slate-200/50 h-1/4 rounded-t animate-pulse" />
                <div className="w-full bg-slate-200/50 h-1/2 rounded-t animate-pulse" />
                <div className="w-full bg-slate-200/50 h-3/4 rounded-t animate-pulse" />
                <div className="w-full bg-slate-200/50 h-2/3 rounded-t animate-pulse" />
                <div className="w-full bg-slate-200/50 h-1/3 rounded-t animate-pulse" />
                <div className="w-full bg-slate-200/50 h-5/6 rounded-t animate-pulse" />
            </div>
        </div>
    );
}

export default function Dashboard({ stats, actionable, grafikTren, grafikKas, aktivitasTerbaru }) {
    const { auth } = usePage().props;
    const { isPrivacyActive } = usePrivacy();
    const userPermissions = auth.user?.permissions ?? [];
    const [filterAktivitas, setFilterAktivitas] = useState('semua');

    function bisaAkses(permission) {
        return permission === null || userPermissions.includes(permission);
    }

    const labelBulan = new Intl.DateTimeFormat('id-ID', { month: 'long', year: 'numeric' }).format(new Date());

    const kpiCards = [
        {
            label: 'Total Simpanan (Aktif)',
            value: formatRupiah(stats.total_simpanan_outstanding ?? 0),
            icon: PiggyBank,
            caption: 'Pokok + wajib anggota aktif',
            href: bisaAkses('simpanan.lihat') ? route('simpanan.index') : null,
            iconCls: 'bg-emerald-50 text-emerald-700 border-emerald-200/70',
        },
        {
            label: 'Total Pinjaman',
            value: formatRupiah(stats.pinjaman_outstanding ?? 0),
            icon: HandCoins,
            caption: 'Pinjaman berstatus aktif',
            href: bisaAkses('pinjaman.lihat') ? route('pinjaman.index', { status: 'aktif' }) : null,
            iconCls: 'bg-amber-50 text-amber-700 border-amber-200/70',
        },
        {
            label: 'Tabungan Anggota',
            value: formatRupiah(stats.total_tabungan_anggota ?? 0),
            icon: BookOpenText,
            caption: 'Saldo tabungan sukarela aktif',
            href: bisaAkses('tabungan.lihat') ? route('tabungan.index') : null,
            iconCls: 'bg-sky-50 text-sky-700 border-sky-200/70',
        },
        {
            label: 'Saldo Dana Sosial',
            value: formatRupiah(stats.saldo_dana_sosial ?? 0),
            icon: HeartHandshake,
            caption: 'Dana sosial tersedia',
            href: bisaAkses('laporan.lihat')
                ? route('laporan.show', 'dana-sosial')
                : (bisaAkses('kas.lihat') ? route('kas-koperasi.index') : null),
            iconCls: 'bg-rose-50 text-rose-600 border-rose-200/70',
        },
    ];

    const bisaApprovePenghapusan = bisaAkses('penghapusan-pinjaman.approve-pengawas');

    const grupAksi = [
        {
            label: 'Pinjaman',
            aksi: [
                { label: 'Verifikasi Awal', value: actionable.menunggu_tinjauan_admin ?? 0, href: route('persetujuan.index', { kategori: 'pinjaman', tahap: 'admin' }), permission: 'pinjaman.tinjau-admin', kePersetujuan: true },
                { label: 'Verifikasi', value: actionable.menunggu_tinjauan_bendahara ?? 0, href: route('persetujuan.index', { kategori: 'pinjaman', tahap: 'bendahara' }), permission: 'pinjaman.tinjau-bendahara', kePersetujuan: true },
                { label: 'Persetujuan', value: actionable.menunggu_approval_ketua ?? 0, href: route('persetujuan.index', { kategori: 'pinjaman', tahap: 'ketua' }), permission: 'pinjaman.approve-ketua', kePersetujuan: true },
                { label: 'Pencairan Pinjaman', value: actionable.menunggu_pencairan_pinjaman ?? 0, href: route('persetujuan.index', { kategori: 'pencairan', sub: 'pinjaman' }), permission: 'pinjaman.cairkan', kePersetujuan: true },
                ...(bisaApprovePenghapusan ? [{
                    label: 'Penghapusan Pinjaman',
                    value: actionable.penghapusan_pinjaman_menunggu ?? 0,
                    href: route('persetujuan.index', { kategori: 'penghapusan' }),
                    permission: 'penghapusan-pinjaman.lihat',
                    kePersetujuan: true,
                }] : []),
            ],
        },
        {
            label: 'Perubahan Tenor',
            aksi: [
                { label: 'Verifikasi', value: (actionable.perubahan_tenor_bendahara ?? actionable.perubahan_tenor) ?? 0, href: route('persetujuan.index', { kategori: 'percepatan', tahap: 'bendahara' }), permission: 'pinjaman.tinjau-bendahara', kePersetujuan: true },
                { label: 'Persetujuan', value: (actionable.perubahan_tenor_ketua ?? actionable.perubahan_tenor) ?? 0, href: route('persetujuan.index', { kategori: 'percepatan', tahap: 'ketua' }), permission: 'pinjaman.approve-ketua', kePersetujuan: true },
            ],
        },
        {
            label: 'Kenaikan Limit',
            aksi: [
                { label: 'Verifikasi', value: actionable.pengajuan_limit_bendahara ?? 0, href: route('persetujuan.index', { kategori: 'limit', tahap: 'bendahara' }), permission: 'limit.tinjau-bendahara', kePersetujuan: true },
                { label: 'Persetujuan', value: actionable.pengajuan_limit ?? 0, href: route('persetujuan.index', { kategori: 'limit', tahap: 'ketua' }), permission: 'limit.approve-ketua', kePersetujuan: true },
            ],
        },
        {
            label: 'Keanggotaan',
            aksi: [
                { label: 'Aktivasi Anggota', value: actionable.aktivasi_anggota ?? 0, href: route('persetujuan.index', { kategori: 'aktivasi' }), permission: 'aktivasi.approve-ketua', kePersetujuan: true },
                { label: 'Simpanan Wajib', value: actionable.anggota_belum_simpanan ?? 0, href: route('bendahara.simpanan.index'), permission: 'simpanan.konfirmasi', kePersetujuan: false },
                { label: 'Verifikasi Tabungan', value: actionable.tabungan_admin_menunggu ?? 0, href: route('persetujuan.index', { kategori: 'tabungan', tahap: 'admin' }), permission: 'tabungan.tinjau-admin', kePersetujuan: true },
                { label: 'Tinjau Tabungan', value: (actionable.tabungan_bendahara_menunggu ?? actionable.tabungan_menunggu) ?? 0, href: route('persetujuan.index', { kategori: 'tabungan', tahap: 'bendahara' }), permission: 'tabungan.kelola', kePersetujuan: true },
                { label: 'Pencairan Tabungan', value: actionable.pencairan_tabungan_menunggu ?? 0, href: route('persetujuan.index', { kategori: 'pencairan', sub: 'tabungan' }), permission: 'tabungan.kelola', kePersetujuan: true },
                { label: 'Pencairan Resign', value: actionable.menunggu_pencairan_resign ?? 0, href: route('persetujuan.index', { kategori: 'pencairan' }), permission: 'pinjaman.cairkan', kePersetujuan: true },
                { label: 'Persetujuan Tabungan', value: actionable.tabungan_ketua_menunggu ?? 0, href: route('persetujuan.index', { kategori: 'tabungan', tahap: 'ketua' }), permission: 'tabungan.approve-ketua', kePersetujuan: true },
            ],
        },
    ]
        .map((g) => {
            const tampil = g.aksi.filter((a) => bisaAkses(a.permission));
            const aktif = tampil.filter((a) => a.value > 0).sort((a, b) => b.value - a.value);
            const total = aktif.reduce((s, a) => s + a.value, 0);
            return { ...g, tampil, aktif, total };
        })
        .filter((g) => g.tampil.length > 0);

    const totalItemMenunggu = grupAksi.reduce((sum, g) => sum + g.total, 0);
    const semuaBeres = totalItemMenunggu === 0;
    const bisaBukaPersetujuan = grupAksi.some((g) => g.tampil.some((a) => a.kePersetujuan));
    const gridKolomAksi =
        grupAksi.length === 1
            ? 'grid-cols-1'
            : grupAksi.length === 2
                ? 'grid-cols-1 sm:grid-cols-2'
                : grupAksi.length === 3
                    ? 'grid-cols-1 sm:grid-cols-3'
                    : 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4';

    const aktivitasTersaring = useMemo(() => {
        if (filterAktivitas === 'semua') return aktivitasTerbaru;
        return aktivitasTerbaru.filter((item) => item.tipe === filterAktivitas);
    }, [aktivitasTerbaru, filterAktivitas]);

    function waktuRelatif(iso) {
        if (!iso) return '';
        const sehari = 86400000;
        const awalHari = (d) => new Date(d.getFullYear(), d.getMonth(), d.getDate()).getTime();
        const selisih = Math.round((awalHari(new Date()) - awalHari(new Date(iso))) / sehari);
        if (selisih <= 0) return 'Hari ini';
        if (selisih === 1) return 'Kemarin';
        if (selisih < 30) return `${selisih} hari lalu`;
        return '';
    }

    function formatKeteranganAktivitas(teks) {
        if (!teks) return '';
        return teks.replace(/Mengajukan pinjaman\s+(?!Rp)/i, 'Mengajukan pinjaman Rp ');
    }

    return (
        <AppLayout>
            <Head title="Dashboard" />

            {/* 1. Open-Canvas Editorial Header (Tanpa Kotak Ber-Border & Tanpa Garis Bawah) */}
            <header
                aria-label="Sambutan pengurus koperasi"
                className="mb-5"
            >
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div className="flex items-center gap-4 sm:gap-5 min-w-0">
                        <img
                            src="/images/logo-hero.webp"
                            alt="Logo Koperasi Karya Mandiri"
                            width="88"
                            height="88"
                            className="w-16 h-16 sm:w-20 sm:h-20 lg:w-[88px] lg:h-[88px] object-contain shrink-0"
                        />
                        <div className="min-w-0">
                            <p className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                                Koperasi Karyawan • Duta Mall Banjarmasin
                            </p>
                            <h1 className="text-lg sm:text-xl lg:text-[22px] font-bold text-[#0F1E36] tracking-tight leading-snug mt-0.5 truncate">
                                Selamat Datang, Pengurus Koperasi Karya Mandiri
                            </h1>
                            <p className="text-xs sm:text-sm text-slate-500 mt-0.5">
                                Ringkasan posisi keuangan &amp; operasional • {labelBulan}
                            </p>
                        </div>
                    </div>

                    <div className="flex items-center shrink-0 self-start sm:self-center">
                        <DashboardClock />
                    </div>
                </div>
            </header>

            {/* 2. Ringkasan Posisi Keuangan (Hero Likuiditas Brand + 2x2 Semantic KPI Cards) */}
            <section aria-label="Ringkasan posisi keuangan" className="mb-6">
                <div className="grid grid-cols-1 lg:grid-cols-12 gap-4 items-stretch">
                    {/* Kartu Jangkar Likuiditas: Kas Operasional Gabungan + Rincian Bank & Kas Kecil */}
                    <div className="lg:col-span-5 relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#0B1527] via-[#0F1E36] to-[#0D3522] text-white p-5 sm:p-6 shadow-[0_12px_32px_-12px_rgba(15,30,54,0.38)] border border-white/10 flex flex-col justify-between">
                        <div
                            aria-hidden="true"
                            className="pointer-events-none absolute -top-12 -right-12 w-40 h-40 rounded-full bg-emerald-400/15 blur-2xl"
                        />
                        <div
                            aria-hidden="true"
                            className="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-emerald-300/30 to-transparent"
                        />

                        <div>
                            <div className="relative flex items-center justify-between gap-2">
                                <span className="inline-flex items-center gap-2.5 min-w-0">
                                    <span className="w-8 h-8 rounded-xl bg-emerald-400/15 border border-emerald-400/25 text-emerald-300 flex items-center justify-center shrink-0">
                                        <Wallet size={16} aria-hidden="true" />
                                    </span>
                                    <span className="text-xs font-semibold text-emerald-100/90 tracking-wide truncate">
                                        Kas Operasional Gabungan
                                    </span>
                                </span>
                                {bisaAkses('kas.lihat') && (
                                    <Link
                                        href={route('kas-koperasi.index')}
                                        className="group inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-white/10 hover:bg-white/15 border border-white/15 hover:border-white/25 text-white/95 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-300/60 shrink-0"
                                    >
                                        Buku Kas
                                        <ChevronRight
                                            size={12}
                                            aria-hidden="true"
                                            className="group-hover:translate-x-0.5 transition-transform duration-150"
                                        />
                                    </Link>
                                )}
                            </div>

                            <div className="relative mt-4 min-w-0">
                                <p className="text-2xl sm:text-[28px] font-extrabold text-white leading-tight tabular-nums tracking-[-0.025em] truncate">
                                    <PrivacyWrapper>{formatRupiah(stats.kas_operasional ?? 0)}</PrivacyWrapper>
                                </p>
                                <p className="text-xs text-slate-300/80 mt-1">
                                    Total saldo likuid Bank + Kas Kecil
                                </p>
                            </div>
                        </div>

                        <div className="relative mt-5 pt-4 border-t border-white/12 grid grid-cols-2 gap-2.5">
                            {[
                                {
                                    label: 'Saldo Bank',
                                    value: formatRupiah(stats.saldo_bank ?? 0),
                                    icon: Landmark,
                                    iconColor: 'text-sky-300',
                                    href: bisaAkses('kas.lihat') ? route('kas-koperasi.index', { kanal: 'bank' }) : null,
                                },
                                {
                                    label: 'Kas Kecil',
                                    value: formatRupiah(stats.saldo_kas_kecil ?? 0),
                                    icon: Banknote,
                                    iconColor: 'text-emerald-300',
                                    href: bisaAkses('kas.lihat') ? route('kas-koperasi.index', { kanal: 'kas_kecil' }) : null,
                                },
                            ].map((sub) => {
                                const SubIcon = sub.icon;
                                return (
                                    <div
                                        key={sub.label}
                                        className="rounded-xl bg-white/[0.07] border border-white/10 px-3 py-2.5 min-w-0"
                                    >
                                        <div className="flex items-center justify-between gap-1.5">
                                            <span className="inline-flex items-center gap-1.5 text-[11px] font-medium text-slate-300/90 truncate">
                                                <SubIcon size={12} aria-hidden="true" className={`${sub.iconColor} shrink-0`} />
                                                <span className="truncate">{sub.label}</span>
                                            </span>
                                            {sub.href && (
                                                <Link
                                                    href={sub.href}
                                                    aria-label={`Lihat rincian ${sub.label}`}
                                                    className="group/link inline-flex items-center gap-0.5 text-[11px] font-medium text-white/60 hover:text-white transition-colors rounded focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-300/60 shrink-0"
                                                >
                                                    Lihat
                                                    <ChevronRight
                                                        size={11}
                                                        aria-hidden="true"
                                                        className="group-hover/link:translate-x-0.5 transition-transform duration-150"
                                                    />
                                                </Link>
                                            )}
                                        </div>
                                        <p className="text-sm sm:text-[15px] font-bold text-white tabular-nums mt-1 truncate">
                                            <PrivacyWrapper>{sub.value}</PrivacyWrapper>
                                        </p>
                                    </div>
                                );
                            })}
                        </div>
                    </div>

                    {/* Grid 2x2 Kartu KPI Semantik */}
                    <div className="lg:col-span-7 grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        {kpiCards.map((k) => {
                            const Icon = k.icon;
                            return (
                                <div
                                    key={k.label}
                                    className={`${panelSurface} p-4 sm:p-5 flex flex-col justify-between min-h-[112px]`}
                                >
                                    <div className="flex items-start justify-between gap-2.5">
                                        <p className="text-xs font-semibold text-slate-500 leading-snug pt-0.5">
                                            {k.label}
                                        </p>
                                        <span className={`w-9 h-9 rounded-xl border flex items-center justify-center shrink-0 ${k.iconCls}`}>
                                            <Icon size={17} strokeWidth={1.9} aria-hidden="true" />
                                        </span>
                                    </div>
                                    <div className="mt-3 min-w-0">
                                        <p className="text-xl sm:text-[22px] font-bold text-[#0F1E36] leading-tight tabular-nums tracking-[-0.02em] truncate">
                                            <PrivacyWrapper>{k.value}</PrivacyWrapper>
                                        </p>
                                        <div className="flex items-center justify-between gap-2 mt-1.5">
                                            <p className="text-[11px] text-slate-400 truncate">
                                                {k.caption}
                                            </p>
                                            {k.href && (
                                                <Link
                                                    href={k.href}
                                                    className={`group/link inline-flex items-center gap-0.5 text-[11px] font-semibold text-slate-500 hover:text-[#0F1E36] transition-colors shrink-0 rounded ${focusRing}`}
                                                >
                                                    Lihat
                                                    <ChevronRight
                                                        size={12}
                                                        aria-hidden="true"
                                                        className="group-hover/link:translate-x-0.5 transition-transform duration-150"
                                                    />
                                                </Link>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </div>
            </section>

            {/* 3. Perlu Ditindaklanjuti (1-Card Hairline Domain Ledger) */}
            {grupAksi.length > 0 && (
                <section aria-label="Antrean perlu ditindaklanjuti" className="mb-5">
                    {semuaBeres ? (
                        <div className="rounded-2xl bg-emerald-50/60 border border-emerald-200/75 px-4 py-2.5 flex items-center justify-between gap-3">
                            <div className="flex items-center gap-2.5 min-w-0">
                                <CheckCircle2 size={16} aria-hidden="true" className="text-emerald-700 shrink-0" />
                                <p className="text-xs sm:text-sm font-medium text-emerald-900 truncate">
                                    Semua antrean sudah ditangani. Tidak ada pengajuan yang menunggu tindakan.
                                </p>
                            </div>
                            {bisaBukaPersetujuan && (
                                <Link
                                    href={route('persetujuan.index')}
                                    className={`group/link inline-flex items-center gap-0.5 text-xs font-semibold text-emerald-800 hover:text-emerald-950 transition-colors shrink-0 rounded ${focusRing}`}
                                >
                                    Pusat Persetujuan
                                    <ChevronRight
                                        size={13}
                                        aria-hidden="true"
                                        className="group-hover/link:translate-x-0.5 transition-transform duration-150"
                                    />
                                </Link>
                            )}
                        </div>
                    ) : (
                        <div className={`${panelSurface} overflow-hidden`}>
                            <div className="px-4 sm:px-5 py-3 bg-white border-b border-slate-200/75 flex items-center justify-between gap-3 flex-wrap">
                                <div className="flex items-center gap-2.5 min-w-0">
                                    <h2 className="text-xs sm:text-[13px] font-bold text-[#0F1E36] tracking-tight">
                                        Perlu Ditindaklanjuti
                                    </h2>
                                    <span className="text-slate-300" aria-hidden="true">&bull;</span>
                                    <span className="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 tabular-nums">
                                        <span className="w-1.5 h-1.5 rounded-full bg-amber-500 shrink-0" aria-hidden="true" />
                                        {totalItemMenunggu} antrean
                                    </span>
                                </div>

                                {bisaBukaPersetujuan && (
                                    <Link
                                        href={route('persetujuan.index')}
                                        className={`group/link inline-flex items-center gap-0.5 text-xs font-semibold text-slate-500 hover:text-[#0F1E36] transition-colors shrink-0 rounded ${focusRing}`}
                                    >
                                        Pusat Persetujuan
                                        <ChevronRight
                                            size={13}
                                            aria-hidden="true"
                                            className="group-hover/link:translate-x-0.5 transition-transform duration-150"
                                        />
                                    </Link>
                                )}
                            </div>

                            <div className={`grid ${gridKolomAksi} bg-slate-200/75 gap-px`}>
                                {grupAksi.map((grup) => (
                                    <div key={grup.label} className="bg-white p-4 flex flex-col justify-start">
                                        <div className="flex items-center justify-between gap-2 mb-2.5">
                                            <p className="text-[11px] font-bold uppercase tracking-wider text-slate-500 truncate">
                                                {grup.label}
                                            </p>
                                            {grup.total > 0 ? (
                                                <span className="inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full text-[11px] font-bold bg-[#0F1E36] text-white tabular-nums shrink-0">
                                                    {grup.total}
                                                </span>
                                            ) : (
                                                <span className="text-xs font-medium text-slate-300 tabular-nums shrink-0">
                                                    0
                                                </span>
                                            )}
                                        </div>

                                        {grup.aktif.length > 0 ? (
                                            <div className="space-y-0.5">
                                                {grup.aktif.map((a) => (
                                                    <Link
                                                        key={a.label}
                                                        href={a.href}
                                                        className={`group/row flex items-center justify-between gap-2 -mx-2 px-2 py-1.5 rounded-lg text-xs font-medium text-slate-700 hover:text-[#0F1E36] hover:bg-slate-50 transition-colors ${focusRing}`}
                                                    >
                                                        <span className="inline-flex items-center gap-2 min-w-0">
                                                            <span className="w-1.5 h-1.5 rounded-full bg-amber-500 shrink-0" aria-hidden="true" />
                                                            <span className="truncate">{a.label}</span>
                                                        </span>
                                                        <span className="inline-flex items-center gap-1 shrink-0">
                                                            <span className="text-xs font-bold text-[#0F1E36] tabular-nums">
                                                                {a.value}
                                                            </span>
                                                            <ChevronRight
                                                                size={12}
                                                                aria-hidden="true"
                                                                className="text-slate-300 group-hover/row:text-slate-600 group-hover/row:translate-x-0.5 transition-all duration-150"
                                                            />
                                                        </span>
                                                    </Link>
                                                ))}
                                            </div>
                                        ) : (
                                            <div className="py-1.5 flex items-center gap-1.5 text-xs text-slate-400">
                                                <CheckCircle2 size={13} aria-hidden="true" className="text-emerald-500/80 shrink-0" />
                                                <span>Semua beres</span>
                                            </div>
                                        )}
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}
                </section>
            )}

            {/* 4. Grafik Arus Kas Operasional Koperasi (Full-Width) */}
            <section aria-label="Arus kas operasional koperasi" className="mb-5">
                <Suspense fallback={<ChartSkeleton />}>
                    <DashboardKasChart
                        grafikKas={grafikKas}
                        bisaAkses={bisaAkses}
                        isPrivacyActive={isPrivacyActive}
                        labelBulan={labelBulan}
                    />
                </Suspense>
            </section>

            {/* 5. Dana Anggota & Penyaluran Pinjaman + Aktivitas Terbaru */}
            <section aria-label="Dana anggota dan aktivitas terbaru" className="grid grid-cols-1 lg:grid-cols-3 gap-5">
                {/* Dana Anggota & Penyaluran Pinjaman (Kiri, 2 Kolom) */}
                <Suspense fallback={<ChartSkeleton className="lg:col-span-2" />}>
                    <DashboardTrenChart
                        grafikTren={grafikTren}
                        stats={stats}
                        bisaAkses={bisaAkses}
                        isPrivacyActive={isPrivacyActive}
                        labelBulan={labelBulan}
                    />
                </Suspense>

                {/* Aktivitas Terbaru — Minimalist Financial Feed (Kanan, 1 Kolom) */}
                <div className={`${panelSurface} lg:col-span-1 p-5 sm:p-6 flex flex-col`}>
                    <div className="flex items-start justify-between gap-3 mb-2">
                        <div>
                            <h2 className="text-base font-bold text-slate-800 tracking-tight">Aktivitas Terbaru</h2>
                            <p className="text-xs text-slate-400 mt-0.5">Pengajuan &amp; transaksi terkini</p>
                        </div>
                        {bisaAkses('pinjaman.lihat') && aktivitasTerbaru.length > 0 && (
                            <Link
                                href={filterAktivitas === 'tabungan' && bisaAkses('tabungan.lihat') ? route('tabungan.index') : route('pinjaman.index')}
                                className={`inline-flex items-center gap-0.5 text-xs font-semibold text-slate-500 hover:text-slate-800 transition-colors ${focusRing} rounded shrink-0`}
                            >
                                Lihat semua
                                <ChevronRight size={13} aria-hidden="true" />
                            </Link>
                        )}
                    </div>

                    {aktivitasTerbaru.length > 0 && (
                        <div className="flex items-center gap-4 border-b border-slate-200/80 mb-3 overflow-x-auto scrollbar-hide" role="tablist" aria-label="Filter aktivitas terbaru">
                            {[
                                { id: 'semua', label: 'Semua' },
                                { id: 'pinjaman', label: 'Pinjaman' },
                                { id: 'angsuran', label: 'Angsuran' },
                                { id: 'tabungan', label: 'Tabungan' },
                            ].map((tabItem) => {
                                const active = filterAktivitas === tabItem.id;
                                return (
                                    <button
                                        key={tabItem.id}
                                        type="button"
                                        role="tab"
                                        aria-selected={active}
                                        onClick={() => setFilterAktivitas(tabItem.id)}
                                        className={`pb-2 -mb-px text-xs border-b-2 transition-colors cursor-pointer whitespace-nowrap ${
                                            active
                                                ? 'border-[#0F1E36] text-[#0F1E36] font-semibold'
                                                : 'border-transparent text-slate-500 font-medium hover:text-slate-700'
                                        } ${focusRing}`}
                                    >
                                        {tabItem.label}
                                    </button>
                                );
                            })}
                        </div>
                    )}

                    {aktivitasTersaring.length === 0 ? (
                        <div className="flex-1 flex items-center justify-center py-10">
                            <p className="text-sm text-slate-400 text-center">Belum ada aktivitas tercatat.</p>
                        </div>
                    ) : (
                        <div className="divide-y divide-slate-100 -my-1">
                            {aktivitasTersaring.slice(0, 6).map((item, i) => {
                                const Icon = item.tipe === 'pinjaman'
                                    ? PinjamanIcon
                                    : item.tipe === 'tabungan'
                                        ? BookOpenText
                                        : CheckCircle2;
                                const relatif = waktuRelatif(item.tanggal_iso);
                                const labelStatus = statusLabel[item.status] ?? item.status;
                                const hrefItem = item.tipe === 'tabungan'
                                    ? (bisaAkses('tabungan.lihat') && item.rekening_tabungan_id
                                        ? route('tabungan.show', item.rekening_tabungan_id)
                                        : null)
                                    : (bisaAkses('pinjaman.lihat') && item.pinjaman_id
                                        ? route('pinjaman.show', item.pinjaman_id)
                                        : null);
                                const ItemWrap = hrefItem ? Link : 'div';
                                const itemProps = hrefItem ? { href: hrefItem } : {};

                                const itemTone = item.tipe === 'pinjaman'
                                    ? 'bg-amber-50 border-amber-200/70 text-amber-700'
                                    : item.tipe === 'tabungan'
                                        ? 'bg-sky-50 border-sky-200/70 text-sky-700'
                                        : 'bg-emerald-50 border-emerald-200/70 text-emerald-700';

                                return (
                                    <ItemWrap
                                        key={i}
                                        {...itemProps}
                                        className={`group flex items-start gap-3 -mx-2 px-2 py-2.5 first:pt-1.5 last:pb-1.5 rounded-lg hover:bg-slate-50/70 transition-colors ${
                                            hrefItem ? `cursor-pointer ${focusRing}` : ''
                                        }`}
                                    >
                                        <div className={`w-8 h-8 rounded-lg border flex items-center justify-center shrink-0 mt-0.5 ${itemTone}`}>
                                            <Icon size={15} strokeWidth={1.9} />
                                        </div>
                                        <div className="flex-1 min-w-0">
                                            <div className="flex items-center justify-between gap-2">
                                                <p className="text-sm font-semibold text-slate-800 truncate">
                                                    {item.nama}
                                                </p>
                                                <span
                                                    className={`inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold shrink-0 ${
                                                        statusStyle[item.status] ?? 'bg-slate-100 text-slate-600'
                                                    }`}
                                                >
                                                    {labelStatus}
                                                </span>
                                            </div>
                                            <p className="text-xs text-slate-500 truncate mt-0.5">
                                                <PrivacyWrapper maskType="blur">
                                                    {formatKeteranganAktivitas(item.keterangan)}
                                                </PrivacyWrapper>
                                            </p>
                                            <p className="text-[11px] text-slate-400 mt-0.5 tabular-nums">
                                                {relatif ? `${relatif} • ${item.tanggal_format}` : item.tanggal_format}
                                            </p>
                                        </div>
                                    </ItemWrap>
                                );
                            })}
                        </div>
                    )}
                </div>
            </section>
        </AppLayout>
    );
}
