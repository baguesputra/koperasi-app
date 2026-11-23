import { Suspense, lazy, useMemo, useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Head, Link, usePage } from '@inertiajs/react';
import {
    Users, PiggyBank, HandCoins, Wallet, HeartHandshake, Landmark,
    ClipboardCheck, FileClock, Gauge,
    ChevronRight,
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
    'rounded-2xl bg-white border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.03),0_4px_16px_-4px_rgba(15,23,42,0.04)]';

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

    const widgets = [
        {
            label: 'Kas Operasional Gabungan',
            value: formatRupiah(stats.kas_operasional ?? 0),
            icon: Wallet,
            caption: 'Saldo bank + kas kecil',
            href: bisaAkses('kas.lihat') ? route('kas-koperasi.index') : null,
        },
        {
            label: 'Saldo Bank',
            value: formatRupiah(stats.saldo_bank ?? 0),
            icon: Landmark,
            caption: 'Rekening bank operasional',
            href: bisaAkses('kas.lihat') ? route('kas-koperasi.index', { kanal: 'bank' }) : null,
        },
        {
            label: 'Kas Kecil',
            value: formatRupiah(stats.saldo_kas_kecil ?? 0),
            icon: Wallet,
            caption: 'Uang tunai operasional',
            href: bisaAkses('kas.lihat') ? route('kas-koperasi.index', { kanal: 'kas_kecil' }) : null,
        },
        {
            label: 'Total Simpanan (Aktif)',
            value: formatRupiah(stats.total_simpanan_outstanding),
            icon: PiggyBank,
            caption: 'Pokok + wajib anggota aktif',
            href: bisaAkses('simpanan.lihat') ? route('simpanan.index') : null,
        },
        {
            label: 'Total Pinjaman',
            value: formatRupiah(stats.pinjaman_outstanding),
            icon: HandCoins,
            caption: 'Pinjaman berstatus aktif',
            href: bisaAkses('pinjaman.lihat') ? route('pinjaman.index', { status: 'aktif' }) : null,
        },
        {
            label: 'Saldo Dana Sosial',
            value: formatRupiah(stats.saldo_dana_sosial ?? 0),
            icon: HeartHandshake,
            caption: 'Dana sosial tersedia',
            href: bisaAkses('laporan.lihat')
                ? route('laporan.show', 'dana-sosial')
                : (bisaAkses('kas.lihat') ? route('kas-koperasi.index') : null),
        },
    ];

    const bisaApprovePenghapusan =
        bisaAkses('penghapusan-pinjaman.approve-bendahara') ||
        bisaAkses('penghapusan-pinjaman.approve-ketua') ||
        bisaAkses('penghapusan-pinjaman.approve-pengawas');

    const grupAksi = [
        {
            label: 'Pinjaman', icon: ClipboardCheck,
            aksi: [
                { label: 'Verifikasi Awal', value: actionable.menunggu_tinjauan_admin ?? 0, href: route('admin.pinjaman.index'), permission: 'pinjaman.tinjau-admin' },
                { label: 'Verifikasi', value: actionable.menunggu_tinjauan_bendahara ?? 0, href: route('bendahara.pinjaman.index'), permission: 'pinjaman.tinjau-bendahara' },
                { label: 'Persetujuan', value: actionable.menunggu_approval_ketua ?? 0, href: route('ketua.pinjaman.index'), permission: 'pinjaman.approve-ketua' },
                ...(bisaApprovePenghapusan ? [{
                    label: 'Penghapusan Pinjaman',
                    value: actionable.penghapusan_pinjaman_menunggu ?? 0,
                    href: route('pengurus.penghapusan-pinjaman.index'),
                    permission: 'penghapusan-pinjaman.lihat',
                }] : []),
            ],
        },
        {
            label: 'Perubahan Tenor', icon: FileClock,
            aksi: [
                { label: 'Verifikasi', value: (actionable.perubahan_tenor_bendahara ?? actionable.perubahan_tenor) ?? 0, href: route('bendahara.percepatan.index'), permission: 'pinjaman.tinjau-bendahara' },
                { label: 'Persetujuan', value: (actionable.perubahan_tenor_ketua ?? actionable.perubahan_tenor) ?? 0, href: route('ketua.percepatan.index'), permission: 'pinjaman.approve-ketua' },
            ],
        },
        {
            label: 'Kenaikan Limit', icon: Gauge,
            aksi: [
                { label: 'Verifikasi', value: actionable.pengajuan_limit_bendahara ?? 0, href: route('bendahara.pengajuan-limit.index'), permission: 'limit.tinjau-bendahara' },
                { label: 'Persetujuan', value: actionable.pengajuan_limit ?? 0, href: route('ketua.pengajuan-limit.index'), permission: 'limit.approve-ketua' },
            ],
        },
        {
            label: 'Keanggotaan', icon: Users,
            aksi: [
                { label: 'Aktivasi', value: actionable.aktivasi_anggota ?? 0, href: route('ketua.aktivasi.index'), permission: 'aktivasi.approve-ketua' },
                { label: 'Simpanan Wajib', value: actionable.anggota_belum_simpanan ?? 0, href: route('bendahara.simpanan.index'), permission: 'simpanan.konfirmasi' },
                { label: 'Tabungan Anggota', value: actionable.tabungan_menunggu ?? 0, href: route('bendahara.tabungan.index'), permission: 'tabungan.kelola' },
            ],
        },
    ]
        .map((g) => ({ ...g, tampil: g.aksi.filter((a) => bisaAkses(a.permission)), total: 0 }))
        .filter((g) => g.tampil.length > 0)
        .map((g) => ({ ...g, total: g.tampil.reduce((s, a) => s + a.value, 0) }))
        .sort((a, b) => b.total - a.total);

    const grupMenunggu = grupAksi.filter((g) => g.total > 0);
    const jumlahMenunggu = grupMenunggu.length;
    const totalItemMenunggu = grupMenunggu.reduce((sum, g) => sum + g.total, 0);
    const semuaBeres = jumlahMenunggu === 0;

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

            {/* 2. Ringkasan Posisi Keuangan (Unified Hairline 3x2 Metric Grid) */}
            <section aria-label="Ringkasan posisi keuangan" className="mb-6">
                <div className={`${panelSurface} overflow-hidden`}>
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 bg-slate-200/65 gap-px">
                        {widgets.map((w) => {
                            const Icon = w.icon;
                            const Wrapper = w.href ? Link : 'div';
                            const wrapperProps = w.href ? { href: w.href } : {};

                            return (
                                <Wrapper
                                    key={w.label}
                                    {...wrapperProps}
                                    className={`bg-white p-4 sm:p-5 flex flex-col justify-between min-h-[104px] ${
                                        w.href
                                            ? `group cursor-pointer transition-colors duration-150 hover:bg-slate-50/75 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand-navy/25`
                                            : ''
                                    }`}
                                >
                                    <div className="flex items-center justify-between gap-2">
                                        <p className="text-xs font-medium text-slate-500 group-hover:text-slate-700 transition-colors leading-snug truncate">
                                            {w.label}
                                        </p>
                                        <Icon
                                            size={15}
                                            strokeWidth={1.85}
                                            aria-hidden="true"
                                            className="text-slate-400 group-hover:text-slate-600 transition-colors shrink-0"
                                        />
                                    </div>
                                    <div className="mt-3 min-w-0">
                                        <p className="text-xl sm:text-[22px] font-bold text-[#0F1E36] leading-tight tabular-nums tracking-[-0.02em] truncate">
                                            <PrivacyWrapper>{w.value}</PrivacyWrapper>
                                        </p>
                                        <div className="flex items-center justify-between gap-2 mt-1">
                                            <p className="text-[11px] text-slate-400 group-hover:text-slate-500 transition-colors truncate">
                                                {w.caption}
                                            </p>
                                            {w.href && (
                                                <ChevronRight
                                                    size={13}
                                                    aria-hidden="true"
                                                    className="text-slate-300 opacity-0 group-hover:opacity-100 group-hover:text-slate-500 transition-opacity shrink-0"
                                                />
                                            )}
                                        </div>
                                    </div>
                                </Wrapper>
                            );
                        })}
                    </div>
                </div>
            </section>

            {/* 3. Perlu Ditindaklanjuti (Clean Ledger Cards) */}
            {grupAksi.length > 0 && (
                <section aria-label="Antrean perlu ditindaklanjuti" className="mb-6">
                    <div className="flex items-center justify-between gap-2 mb-3">
                        <div className="flex items-center gap-2">
                            <h2 className="text-sm font-bold text-slate-800 tracking-tight">Perlu Ditindaklanjuti</h2>
                            {!semuaBeres && (
                                <span className="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-700 tabular-nums">
                                    <span className="w-1.5 h-1.5 rounded-full bg-amber-500" aria-hidden="true" />
                                    {totalItemMenunggu} antrean
                                </span>
                            )}
                        </div>
                        <p className="text-xs font-medium text-slate-400">
                            {semuaBeres ? 'Semua beres' : `${jumlahMenunggu} domain menunggu aksi`}
                        </p>
                    </div>

                    {semuaBeres ? (
                        <div className={`${panelSurface} flex items-center gap-3 px-4 py-3.5`}>
                            <CheckCircle2 size={16} aria-hidden="true" className="text-brand-green-dark shrink-0" />
                            <p className="text-sm font-medium text-slate-600">
                                Semua antrean sudah ditangani. Tidak ada pengajuan yang menunggu tindakan.
                            </p>
                        </div>
                    ) : (
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                            {grupAksi.map((grup) => {
                                const Icon = grup.icon;
                                const sibuk = grup.total > 0;
                                return (
                                    <div
                                        key={grup.label}
                                        className={`${panelSurface} p-4 flex flex-col justify-between ${
                                            sibuk ? '' : 'opacity-80'
                                        }`}
                                    >
                                        <div className="flex items-center justify-between gap-2 pb-3 mb-2.5 border-b border-slate-100">
                                            <div className="flex items-center gap-2 min-w-0">
                                                <Icon
                                                    size={15}
                                                    strokeWidth={1.9}
                                                    aria-hidden="true"
                                                    className={sibuk ? 'text-slate-700 shrink-0' : 'text-slate-400 shrink-0'}
                                                />
                                                <p className="text-sm font-semibold text-slate-800 truncate">{grup.label}</p>
                                            </div>
                                            <span className="inline-flex items-center gap-1.5 text-xs font-bold tabular-nums shrink-0 text-slate-800">
                                                {sibuk && <span className="w-1.5 h-1.5 rounded-full bg-amber-500" aria-hidden="true" />}
                                                <span className={sibuk ? 'text-slate-900' : 'text-slate-400 font-medium'}>{grup.total}</span>
                                            </span>
                                        </div>

                                        <div className="space-y-0.5">
                                            {grup.tampil.map((a) => {
                                                const aktif = a.value > 0;
                                                return (
                                                    <Link
                                                        key={a.label}
                                                        href={a.href}
                                                        className={`group flex items-center justify-between gap-2 -mx-1.5 px-2 py-1.5 rounded-lg text-xs transition-colors hover:bg-slate-50 ${
                                                            aktif
                                                                ? 'font-medium text-slate-800'
                                                                : 'font-normal text-slate-500 hover:text-slate-700'
                                                        } ${focusRing}`}
                                                    >
                                                        <span className="truncate">{a.label}</span>
                                                        <span className="inline-flex items-center gap-1 shrink-0">
                                                            <span
                                                                className={`tabular-nums text-xs ${
                                                                    aktif ? 'font-bold text-slate-900' : 'font-medium text-slate-400'
                                                                }`}
                                                            >
                                                                {a.value}
                                                            </span>
                                                            <ChevronRight
                                                                size={13}
                                                                aria-hidden="true"
                                                                className="text-slate-300 group-hover:text-slate-500 transition-colors"
                                                            />
                                                        </span>
                                                    </Link>
                                                );
                                            })}
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    )}
                </section>
            )}

            {/* 4. Grafik Tren Simpanan & Pinjaman */}
            <section aria-label="Tren simpanan dan pinjaman" className="mb-5">
                <Suspense fallback={<ChartSkeleton />}>
                    <DashboardTrenChart
                        grafikTren={grafikTren}
                        bisaAkses={bisaAkses}
                        isPrivacyActive={isPrivacyActive}
                        labelBulan={labelBulan}
                    />
                </Suspense>
            </section>

            {/* 5. Mutasi Kas Koperasi & Aktivitas Terbaru */}
            <section aria-label="Mutasi kas dan aktivitas terbaru" className="grid grid-cols-1 lg:grid-cols-3 gap-5">
                {/* Mutasi Kas (Kiri, 2 Kolom) */}
                <Suspense fallback={<ChartSkeleton className="lg:col-span-2" />}>
                    <DashboardKasChart
                        grafikKas={grafikKas}
                        bisaAkses={bisaAkses}
                        isPrivacyActive={isPrivacyActive}
                    />
                </Suspense>

                {/* Aktivitas Terbaru — Minimalist Financial Feed (Kanan, 1 Kolom) */}
                <div className={`${panelSurface} lg:col-span-1 p-5 sm:p-6 flex flex-col`}>
                    <div className="flex items-start justify-between gap-3 mb-2">
                        <div>
                            <h2 className="text-base font-bold text-slate-800 tracking-tight">Aktivitas Terbaru</h2>
                            <p className="text-xs text-slate-400 mt-0.5">Pengajuan &amp; pembayaran terkini</p>
                        </div>
                        {bisaAkses('pinjaman.lihat') && aktivitasTerbaru.length > 0 && (
                            <Link
                                href={route('pinjaman.index')}
                                className={`inline-flex items-center gap-0.5 text-xs font-semibold text-slate-500 hover:text-slate-800 transition-colors ${focusRing} rounded shrink-0`}
                            >
                                Lihat semua
                                <ChevronRight size={13} aria-hidden="true" />
                            </Link>
                        )}
                    </div>

                    {aktivitasTerbaru.length > 0 && (
                        <div className="flex items-center gap-5 border-b border-slate-200/80 mb-3" role="tablist" aria-label="Filter aktivitas terbaru">
                            {[
                                { id: 'semua', label: 'Semua' },
                                { id: 'pinjaman', label: 'Pinjaman' },
                                { id: 'angsuran', label: 'Angsuran' },
                            ].map((tabItem) => {
                                const active = filterAktivitas === tabItem.id;
                                return (
                                    <button
                                        key={tabItem.id}
                                        type="button"
                                        role="tab"
                                        aria-selected={active}
                                        onClick={() => setFilterAktivitas(tabItem.id)}
                                        className={`pb-2 -mb-px text-xs border-b-2 transition-colors cursor-pointer ${
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
                                const Icon = item.tipe === 'pinjaman' ? PinjamanIcon : CheckCircle2;
                                const relatif = waktuRelatif(item.tanggal_iso);
                                const labelStatus = statusLabel[item.status] ?? item.status;
                                const hrefItem = bisaAkses('pinjaman.lihat') && item.pinjaman_id
                                    ? route('pinjaman.show', item.pinjaman_id)
                                    : null;
                                const ItemWrap = hrefItem ? Link : 'div';
                                const itemProps = hrefItem ? { href: hrefItem } : {};

                                return (
                                    <ItemWrap
                                        key={i}
                                        {...itemProps}
                                        className={`group flex items-start gap-3 -mx-2 px-2 py-2.5 first:pt-1.5 last:pb-1.5 rounded-lg hover:bg-slate-50/70 transition-colors ${
                                            hrefItem ? `cursor-pointer ${focusRing}` : ''
                                        }`}
                                    >
                                        <div className="w-8 h-8 rounded-lg bg-slate-100/80 border border-slate-200/60 text-slate-600 flex items-center justify-center shrink-0 mt-0.5">
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
