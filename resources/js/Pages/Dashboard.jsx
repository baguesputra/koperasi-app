import { Suspense, lazy } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Head, Link, usePage } from '@inertiajs/react';
import {
    Users, PiggyBank, HandCoins, Wallet, HeartHandshake, Landmark,
    ClipboardCheck, FileClock, Gauge,
    ChevronRight,
    HandCoins as PinjamanIcon, CheckCircle2,
} from 'lucide-react';
import Card from '@/Components/ui/Card';
import StatWidget from '@/Components/ui/StatWidget';
import DashboardClock from '@/Components/ui/DashboardClock';
import PrivacyWrapper from '@/Components/ui/PrivacyWrapper';
import { usePrivacy } from '@/Contexts/PrivacyContext';
import { formatRupiah } from '@/Utils/formatCurrency';
import { statusLabel, statusStyle } from '@/Utils/status';

const DashboardTrenChart = lazy(() => import('@/Pages/Dashboard/Partials/DashboardTrenChart'));
const DashboardKasChart = lazy(() => import('@/Pages/Dashboard/Partials/DashboardKasChart'));

const focusRing =
    'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-green focus-visible:ring-offset-2';

function ChartSkeleton({ className = '' }) {
    return (
        <Card className={`border-slate-200/80 shadow-2xs sm:p-6 ${className}`}>
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
        </Card>
    );
}

export default function Dashboard({ stats, actionable, grafikTren, grafikKas, aktivitasTerbaru }) {
    const { auth } = usePage().props;
    const { isPrivacyActive } = usePrivacy();
    const userPermissions = auth.user?.permissions ?? [];

    function bisaAkses(permission) {
        return permission === null || userPermissions.includes(permission);
    }

    const labelBulan = new Intl.DateTimeFormat('id-ID', { month: 'long', year: 'numeric' }).format(new Date());
    const widgets = [
        { label: 'Kas Operasional Gabungan', value: formatRupiah(stats.kas_operasional ?? 0), icon: Wallet, tone: 'navy', caption: 'Saldo bank + kas kecil' },
        { label: 'Saldo Bank', value: formatRupiah(stats.saldo_bank ?? 0), icon: Landmark, tone: 'navy', caption: 'Rekening bank operasional' },
        { label: 'Kas Kecil', value: formatRupiah(stats.saldo_kas_kecil ?? 0), icon: Wallet, tone: 'amber', caption: 'Uang tunai operasional' },
        { label: 'Total Simpanan (Aktif)', value: formatRupiah(stats.total_simpanan_outstanding), icon: PiggyBank, tone: 'green', caption: 'Pokok + wajib anggota aktif' },
        { label: 'Total Pinjaman', value: formatRupiah(stats.pinjaman_outstanding), icon: HandCoins, tone: 'amber', caption: 'Pinjaman berstatus aktif' },
        { label: 'Saldo Dana Sosial', value: formatRupiah(stats.saldo_dana_sosial ?? 0), icon: HeartHandshake, tone: 'green', caption: 'Dana sosial tersedia' },
    ];

    const grupAksi = [
        {
            label: 'Pinjaman', icon: ClipboardCheck,
            aksi: [
                { label: 'Verifikasi Awal', value: actionable.menunggu_tinjauan_admin, href: route('admin.pinjaman.index'), permission: 'pinjaman.tinjau-admin' },
                { label: 'Verifikasi', value: actionable.menunggu_tinjauan_bendahara, href: route('bendahara.pinjaman.index'), permission: 'pinjaman.tinjau-bendahara' },
                { label: 'Persetujuan', value: actionable.menunggu_approval_ketua, href: route('ketua.pinjaman.index'), permission: 'pinjaman.approve-ketua' },
            ],
        },
        {
            label: 'Perubahan Tenor', icon: FileClock,
            aksi: [
                { label: 'Verifikasi', value: actionable.perubahan_tenor_bendahara ?? actionable.perubahan_tenor, href: route('bendahara.percepatan.index'), permission: 'pinjaman.tinjau-bendahara' },
                { label: 'Persetujuan', value: actionable.perubahan_tenor_ketua ?? actionable.perubahan_tenor, href: route('ketua.percepatan.index'), permission: 'pinjaman.approve-ketua' },
            ],
        },
        {
            label: 'Kenaikan Limit', icon: Gauge,
            aksi: [
                { label: 'Verifikasi', value: actionable.pengajuan_limit_bendahara, href: route('bendahara.pengajuan-limit.index'), permission: 'limit.tinjau-bendahara' },
                { label: 'Persetujuan', value: actionable.pengajuan_limit, href: route('ketua.pengajuan-limit.index'), permission: 'limit.approve-ketua' },
            ],
        },
        {
            label: 'Keanggotaan', icon: Users,
            aksi: [
                { label: 'Aktivasi', value: actionable.aktivasi_anggota, href: route('ketua.aktivasi.index'), permission: 'aktivasi.approve-ketua' },
                { label: 'Simpanan Wajib', value: actionable.anggota_belum_simpanan, href: route('bendahara.simpanan.index'), permission: 'simpanan.konfirmasi' },
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

            {/* 1. Banner Sambutan Pengurus (Clean White, lebar penuh) */}
            <section
                aria-label="Sambutan pengurus koperasi"
                className="rounded-xl bg-gradient-to-r from-white via-white to-brand-green-light/35 border border-slate-200/80 shadow-[0_2px_10px_-3px_rgba(15,30,54,0.06),0_1px_2px_rgba(15,30,54,0.03)] p-4 sm:px-5 sm:py-4 mb-3.5 sm:mb-4"
            >
                <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-3.5 sm:gap-4">
                    <div className="flex items-center gap-3.5 sm:gap-4 min-w-0">
                        <img
                            src="/images/logo-hero.webp"
                            alt="Logo Koperasi Karya Mandiri"
                            width="76"
                            height="76"
                            className="w-14 h-14 sm:w-[72px] sm:h-[72px] lg:w-[76px] lg:h-[76px] object-contain shrink-0"
                        />
                        <div className="min-w-0">
                            <p className="text-[11px] font-semibold uppercase tracking-wider text-brand-green-dark">
                                Koperasi Karyawan • Duta Mall Banjarmasin
                            </p>
                            <h1 className="text-lg sm:text-xl lg:text-[22px] font-extrabold text-brand-navy tracking-tight leading-snug mt-0.5">
                                Selamat Datang, Pengurus Koperasi Karya Mandiri
                            </h1>
                            <p className="text-xs sm:text-sm text-slate-500 mt-0.5">
                                Ringkasan posisi keuangan &amp; operasional koperasi • {labelBulan}
                            </p>
                        </div>
                    </div>

                    <div className="flex flex-wrap items-center gap-2 shrink-0">
                        <DashboardClock />
                        {bisaAkses('kas.lihat') && (
                            <Link
                                href={route('kas-koperasi.index')}
                                className={`inline-flex items-center gap-1 px-3 py-1.5 rounded-full bg-brand-navy hover:bg-brand-navy-light text-white text-xs font-semibold shadow-sm transition-colors shrink-0 ${focusRing}`}
                            >
                                Buku Kas
                                <ChevronRight size={13} aria-hidden="true" />
                            </Link>
                        )}
                    </div>
                </div>
            </section>

            {/* 2. Ringkasan Posisi Keuangan (6 KPI Cards penuh 3x2) */}
            <section aria-label="Ringkasan posisi keuangan" className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5 sm:gap-3 mb-5">
                {widgets.map((w) => (
                    <StatWidget
                        compact
                        key={w.label}
                        label={w.label}
                        value={w.value}
                        icon={w.icon}
                        tone={w.tone}
                        caption={w.caption}
                    />
                ))}
            </section>

            {/* 2. Perlu Ditindaklanjuti (Actionable Queue) */}
            {grupAksi.length > 0 && (
                <section aria-label="Antrean perlu ditindaklanjuti" className="mb-6">
                    <div className="flex items-center justify-between gap-2 mb-3">
                        <div className="flex items-center gap-2">
                            <h2 className="text-sm font-bold text-slate-800 tracking-tight">Perlu Ditindaklanjuti</h2>
                            {!semuaBeres && (
                                <span className="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-100/80 text-amber-800 tabular-nums">
                                    {totalItemMenunggu} antrean
                                </span>
                            )}
                        </div>
                        <p className="text-xs font-medium text-slate-500">
                            {semuaBeres ? 'Semua beres' : `${jumlahMenunggu} domain menunggu aksi`}
                        </p>
                    </div>

                    {semuaBeres ? (
                        <div className="flex items-center gap-3 bg-white border border-slate-200/80 rounded-xl px-3.5 py-3 shadow-2xs">
                            <div className="w-8 h-8 rounded-lg bg-emerald-50 border border-emerald-200/60 text-brand-green-dark flex items-center justify-center shrink-0">
                                <CheckCircle2 size={16} aria-hidden="true" />
                            </div>
                            <p className="text-sm font-medium text-slate-700">
                                Semua antrean sudah ditangani. Tidak ada pengajuan yang menunggu tindakan.
                            </p>
                        </div>
                    ) : (
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-3">
                            {grupAksi.map((grup) => {
                                const Icon = grup.icon;
                                const sibuk = grup.total > 0;
                                return (
                                    <div
                                        key={grup.label}
                                        className={`rounded-xl border p-3.5 sm:p-4 flex flex-col justify-between ${
                                            sibuk
                                                ? 'bg-gradient-to-br from-white via-white to-amber-50/30 border-slate-200/90 shadow-[0_2px_10px_-3px_rgba(15,30,54,0.06)]'
                                                : 'bg-gradient-to-br from-white to-slate-50/50 border-slate-200/70 opacity-85'
                                        }`}
                                    >
                                        <div className="flex items-center justify-between gap-2 pb-3 mb-2.5 border-b border-slate-100">
                                            <div className="flex items-center gap-2.5 min-w-0">
                                                <div
                                                    className={`w-8 h-8 rounded-lg border flex items-center justify-center shrink-0 ${
                                                        sibuk
                                                            ? 'bg-gradient-to-br from-amber-50 to-orange-100/70 border-amber-200/80 text-amber-700'
                                                            : 'bg-gradient-to-br from-brand-navy/5 to-sky-50 border-slate-200/80 text-slate-500'
                                                    }`}
                                                >
                                                    <Icon size={16} aria-hidden="true" />
                                                </div>
                                                <p className="text-sm font-bold text-slate-800 truncate">{grup.label}</p>
                                            </div>
                                            <span
                                                className={`inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold tabular-nums shrink-0 ${
                                                    sibuk
                                                        ? 'bg-amber-50 border border-amber-200/80 text-amber-800'
                                                        : 'bg-slate-100 text-slate-500'
                                                }`}
                                            >
                                                {sibuk && <span className="w-1.5 h-1.5 rounded-full bg-amber-500" aria-hidden="true" />}
                                                {grup.total}
                                            </span>
                                        </div>

                                        <div className="space-y-1.5">
                                            {grup.tampil.map((a) => {
                                                const aktif = a.value > 0;
                                                return (
                                                    <Link
                                                        key={a.label}
                                                        href={a.href}
                                                        className={`flex items-center justify-between gap-2 px-2.5 py-1.5 rounded-xl text-xs font-semibold transition-all ${
                                                            aktif
                                                                ? 'bg-gradient-to-r from-amber-50/60 to-white hover:from-amber-50 hover:to-orange-50/40 text-slate-700 hover:text-amber-900 border border-amber-200/60 hover:border-amber-300/80 shadow-2xs'
                                                                : 'text-slate-500 hover:bg-brand-green-light/55 hover:text-brand-navy'
                                                        } ${focusRing}`}
                                                    >
                                                        <span className="truncate">{a.label}</span>
                                                        <span className="inline-flex items-center gap-1 shrink-0">
                                                            <span
                                                                className={`tabular-nums px-1.5 py-0.2 rounded-md text-[11px] font-bold ${
                                                                    aktif ? 'bg-amber-100/80 text-amber-800' : 'text-slate-500'
                                                                }`}
                                                            >
                                                                {a.value}
                                                            </span>
                                                            <ChevronRight size={13} aria-hidden="true" className="text-slate-400" />
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

            {/* 3. Grafik Tren Simpanan & Pinjaman */}
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

            {/* 4. Mutasi Kas Koperasi & Aktivitas Terbaru */}
            <section aria-label="Mutasi kas dan aktivitas terbaru" className="grid grid-cols-1 lg:grid-cols-3 gap-5">
                {/* Mutasi Kas (Kiri, 2 Kolom) */}
                <Suspense fallback={<ChartSkeleton className="lg:col-span-2" />}>
                    <DashboardKasChart
                        grafikKas={grafikKas}
                        bisaAkses={bisaAkses}
                        isPrivacyActive={isPrivacyActive}
                    />
                </Suspense>

                {/* Aktivitas Terbaru (Kanan, 1 Kolom) */}
                <Card className="lg:col-span-1 border-slate-200/80 shadow-2xs sm:p-6 flex flex-col">
                    <div className="flex items-start justify-between gap-3 mb-4">
                        <div>
                            <h2 className="text-base font-bold text-slate-800 tracking-tight">Aktivitas Terbaru</h2>
                            <p className="text-xs text-slate-500 mt-0.5">Pengajuan &amp; pembayaran terkini</p>
                        </div>
                        {bisaAkses('pinjaman.lihat') && aktivitasTerbaru.length > 0 && (
                            <Link
                                href={route('pinjaman.index')}
                                className={`inline-flex items-center gap-0.5 text-xs font-semibold text-brand-green-dark hover:text-brand-green transition-colors ${focusRing} rounded shrink-0`}
                            >
                                Lihat semua
                                <ChevronRight size={13} aria-hidden="true" />
                            </Link>
                        )}
                    </div>

                    {aktivitasTerbaru.length === 0 ? (
                        <div className="flex-1 flex items-center justify-center py-10">
                            <p className="text-sm text-slate-500 text-center">Belum ada aktivitas tercatat.</p>
                        </div>
                    ) : (
                        <div className="divide-y divide-slate-100 -my-1">
                            {aktivitasTerbaru.map((item, i) => {
                                const Icon = item.tipe === 'pinjaman' ? PinjamanIcon : CheckCircle2;
                                const relatif = waktuRelatif(item.tanggal_iso);
                                const labelStatus = statusLabel[item.status] ?? item.status;
                                return (
                                    <div key={i} className="flex items-start gap-3 -mx-2 px-2 py-3 first:pt-1.5 last:pb-1.5 rounded-xl hover:bg-brand-green-light/25 transition-colors">
                                        <div
                                            className={`w-8 h-8 rounded-lg flex items-center justify-center shrink-0 mt-0.5 ${
                                                statusStyle[item.status] ?? 'bg-slate-100 text-slate-600'
                                            }`}
                                        >
                                            <Icon size={15} />
                                        </div>
                                        <div className="flex-1 min-w-0">
                                            <div className="flex items-center justify-between gap-2">
                                                <p className="text-sm font-semibold text-slate-800 truncate">{item.nama}</p>
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
                                            <p className="text-[11px] text-slate-500 mt-0.5 tabular-nums">
                                                {relatif ? `${relatif} • ${item.tanggal_format}` : item.tanggal_format}
                                            </p>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    )}
                </Card>
            </section>
        </AppLayout>
    );
}