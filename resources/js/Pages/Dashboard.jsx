import { Suspense, lazy } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Head, Link, usePage } from '@inertiajs/react';
import {
    Users, PiggyBank, HandCoins, Wallet, TrendingUp, Landmark,
    ClipboardCheck, FileClock, Gauge,
    ChevronRight,
    HandCoins as PinjamanIcon, CheckCircle2,
} from 'lucide-react';
import Card from '@/Components/ui/Card';
import StatWidget from '@/Components/ui/StatWidget';
import PageHeader from '@/Components/ui/PageHeader';
import DashboardClock from '@/Components/ui/DashboardClock';
import PrivacyWrapper from '@/Components/ui/PrivacyWrapper';
import { usePrivacy } from '@/Contexts/PrivacyContext';
import { formatRupiah, formatRupiahSingkat } from '@/Utils/formatCurrency';
import { statusStyle } from '@/Utils/status';

const DashboardTrenChart = lazy(() => import('@/Pages/Dashboard/Partials/DashboardTrenChart'));
const DashboardKasChart = lazy(() => import('@/Pages/Dashboard/Partials/DashboardKasChart'));

const focusRing =
    'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-green focus-visible:ring-offset-2';

function ChartSkeleton({ className = '' }) {
    return (
        <Card className={className}>
            <div className="flex items-center justify-between mb-4">
                <div>
                    <div className="h-4 w-44 bg-slate-200 rounded animate-pulse mb-1.5" />
                    <div className="h-3 w-28 bg-slate-100 rounded animate-pulse" />
                </div>
            </div>
            <div className="h-[280px] w-full bg-slate-50/50 rounded-xl flex items-end justify-between gap-2 p-6">
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

export default function Dashboard({ stats, actionable, grafikTren, grafikKas, aktivitasTerbaru, rincianAntrean }) {
    const { auth } = usePage().props;
    const { isPrivacyActive } = usePrivacy();
    const userPermissions = auth.user?.permissions ?? [];

    function bisaAkses(permission) {
        return permission === null || userPermissions.includes(permission);
    }

    const labelBulan = new Intl.DateTimeFormat('id-ID', { month: 'long', year: 'numeric' }).format(new Date());
    const widgets = [
        { label: 'Kas Operasional Gabungan', value: formatRupiah(stats.kas_operasional ?? stats.total_keseluruhan), icon: Wallet, tone: 'navy', caption: 'Bank + kas kecil' },
        { label: 'Saldo Bank', value: formatRupiah(stats.saldo_bank ?? 0), icon: Landmark, tone: 'navy', caption: 'Rekening bank operasional' },
        { label: 'Kas Kecil', value: formatRupiah(stats.saldo_kas_kecil ?? 0), icon: Wallet, tone: 'amber', caption: 'Uang tunai operasional' },
        { label: 'Total Simpanan (Aktif)', value: formatRupiah(stats.total_simpanan_outstanding), icon: PiggyBank, tone: 'green', caption: 'Pokok + wajib' },
        { label: 'Pinjaman Outstanding', value: formatRupiah(stats.pinjaman_outstanding), icon: HandCoins, tone: 'amber', caption: 'Status aktif' },
        { label: 'Pendapatan Bunga Bulan Ini', value: formatRupiah(stats.pendapatan_bunga_bulan_ini), icon: TrendingUp, tone: 'green', caption: labelBulan },
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
            label: 'Limit', icon: Gauge,
            aksi: [
                { label: 'Verifikasi', value: actionable.pengajuan_limit_bendahara, href: route('bendahara.pengajuan-limit.index'), permission: 'limit.tinjau-bendahara' },
                { label: 'Persetujuan', value: actionable.pengajuan_limit, href: route('ketua.pengajuan-limit.index'), permission: 'limit.approve-ketua' },
            ],
        },
        {
            label: 'Anggota', icon: Users,
            aksi: [
                { label: 'Aktivasi', value: actionable.aktivasi_anggota, href: route('ketua.aktivasi.index'), permission: 'aktivasi.approve-ketua' },
                { label: 'Simpanan', value: actionable.anggota_belum_simpanan, href: route('bendahara.simpanan.index'), permission: 'simpanan.konfirmasi' },
            ],
        },
    ]
        .map((g) => ({ ...g, tampil: g.aksi.filter((a) => bisaAkses(a.permission)), total: 0 }))
        .filter((g) => g.tampil.length > 0)
        .map((g) => ({ ...g, total: g.tampil.reduce((s, a) => s + a.value, 0) }))
        .sort((a, b) => b.total - a.total);

    const grupMenunggu = grupAksi.filter((g) => g.total > 0);
    const jumlahMenunggu = grupMenunggu.length;
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

    return (
        <AppLayout>
            <Head title="Dashboard" />

            <PageHeader title="Dashboard" subtitle={`Ringkasan aktivitas koperasi • periode ${labelBulan}`}>
                <DashboardClock />
            </PageHeader>

            {/* Tren + Stat Widget */}
            <div className="mb-6">
                <Suspense fallback={<ChartSkeleton />}>
                    <DashboardTrenChart
                        grafikTren={grafikTren}
                        bisaAkses={bisaAkses}
                        isPrivacyActive={isPrivacyActive}
                        labelBulan={labelBulan}
                    />
                </Suspense>

                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-4">
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:col-span-2 lg:col-span-3">
                        {widgets.map((w) => (
                            <StatWidget compact key={w.label} label={w.label} value={w.value} icon={w.icon} tone={w.tone} caption={w.caption} />
                        ))}
                    </div>
                    <div className="bg-brand-navy rounded-2xl p-5 text-white flex flex-col justify-between sm:col-span-2 lg:col-span-1 lg:min-h-full">
                        <div>
                            <div className="w-10 h-10 rounded-xl bg-white/10 text-brand-green flex items-center justify-center mb-3">
                                <Landmark size={20} />
                            </div>
                            <p className="text-sm text-slate-300">Saldo Total</p>
                            <p className="text-3xl font-bold mt-1 leading-tight">
                                <PrivacyWrapper>{formatRupiah(stats.total_keseluruhan)}</PrivacyWrapper>
                            </p>
                        </div>
                        <p className="text-xs text-slate-300 mt-4 leading-snug">Kas operasional + simpanan anggota aktif</p>
                    </div>
                </div>
            </div>

            {/* Perlu Ditindaklanjuti */}
            <div className="mb-6">
                <div className="flex items-center justify-between mb-3">
                    <p className="text-base font-bold text-slate-700">Perlu Ditindaklanjuti</p>
                    <p className="text-xs text-slate-400">
                        {semuaBeres ? 'Semua beres' : `${jumlahMenunggu} domain menunggu aksi`}
                    </p>
                </div>
                {semuaBeres ? (
                    <div className="flex items-center gap-3 bg-brand-green-light/40 border border-brand-green/20 rounded-xl px-4 py-3.5">
                        <CheckCircle2 size={18} aria-hidden="true" className="text-brand-green-dark shrink-0" />
                        <p className="text-sm font-semibold text-brand-green-dark">Semua antrean sudah ditangani. Tidak ada aksi menunggu.</p>
                    </div>
                ) : (
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        {grupAksi.map((grup) => {
                            const Icon = grup.icon;
                            const sibuk = grup.total > 0;
                            return (
                                <div
                                    key={grup.label}
                                    className={`relative flex items-center gap-2.5 rounded-xl border px-3.5 py-3 ${sibuk ? 'bg-amber-50 border-amber-200' : 'bg-white border-slate-100 opacity-70'}`}
                                >
                                    {sibuk && (
                                        <span className="absolute top-2 right-2 flex h-1.5 w-1.5" aria-hidden="true">
                                            <span className="relative inline-flex h-1.5 w-1.5 rounded-full bg-red-500" />
                                        </span>
                                    )}
                                    <Icon size={18} aria-hidden="true" className={`shrink-0 ${sibuk ? 'text-amber-600' : 'text-slate-300'}`} />
                                    <div className="min-w-0 flex-1">
                                        <p className={`text-xl font-bold leading-none ${sibuk ? 'text-amber-700' : 'text-slate-400'}`}>
                                            {grup.total}
                                        </p>
                                        <p className="text-xs font-medium leading-tight mt-1 text-slate-600">
                                            {grup.label}
                                        </p>
                                        <div className="flex flex-wrap gap-1.5 mt-2">
                                            {grup.tampil.map((a) => (
                                                <Link
                                                    key={a.label}
                                                    href={a.href}
                                                    className={`inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-white border text-xs font-bold transition-colors ${a.value > 0 ? 'border-amber-200 text-amber-700 hover:bg-amber-100' : 'border-slate-200 text-slate-400'} ${focusRing}`}
                                                >
                                                    {a.label} {a.value}
                                                    <ChevronRight size={12} aria-hidden="true" />
                                                </Link>
                                            ))}
                                        </div>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                )}
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-5">
                {/* Aktivitas Terbaru */}
                <Card className="lg:col-span-1 sm:p-6">
                    <div className="flex items-center justify-between gap-3 mb-4">
                        <p className="text-base font-bold text-slate-700">Aktivitas Terbaru</p>
                        {bisaAkses('pinjaman.lihat') && aktivitasTerbaru.length > 0 && (
                            <Link
                                href={route('pinjaman.index')}
                                className={`inline-flex items-center gap-0.5 text-xs font-bold text-brand-green-dark hover:underline ${focusRing} rounded`}
                            >
                                Lihat semua
                                <ChevronRight size={12} aria-hidden="true" />
                            </Link>
                        )}
                    </div>

                    {aktivitasTerbaru.length === 0 ? (
                        <p className="text-sm text-slate-400 text-center py-8">Belum ada aktivitas.</p>
                    ) : (
                        <div className="space-y-3">
                            {aktivitasTerbaru.map((item, i) => {
                                const Icon = item.tipe === 'pinjaman' ? PinjamanIcon : CheckCircle2;
                                const relatif = waktuRelatif(item.tanggal_iso);
                                return (
                                    <div key={i} className="flex items-start gap-3">
                                        <div className={`w-8 h-8 rounded-full flex items-center justify-center shrink-0 ${statusStyle[item.status]}`}>
                                            <Icon size={14} />
                                        </div>
                                        <div className="flex-1 min-w-0">
                                            <p className="text-sm font-semibold text-slate-700 truncate">{item.nama}</p>
                                            <p className="text-xs text-slate-400 truncate">
                                                <PrivacyWrapper maskType="blur">{item.keterangan}</PrivacyWrapper>
                                            </p>
                                            <p className="text-xs text-slate-400 mt-0.5">{relatif ? `${relatif} • ${item.tanggal_format}` : item.tanggal_format}</p>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    )}
                </Card>

                {/* Mutasi Kas */}
                <Suspense fallback={<ChartSkeleton className="lg:col-span-2 sm:p-6" />}>
                    <DashboardKasChart
                        grafikKas={grafikKas}
                        bisaAkses={bisaAkses}
                        isPrivacyActive={isPrivacyActive}
                    />
                </Suspense>
            </div>
        </AppLayout>
    );
}