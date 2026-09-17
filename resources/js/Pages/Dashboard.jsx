import AppLayout from '@/Layouts/AppLayout';
import { Head, Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import {
    Users, PiggyBank, HandCoins, TrendingUp, Landmark,
    ClipboardCheck, ShieldCheck, AlertCircle, CalendarClock, FileClock, Gauge,
    ChevronRight, ChevronDown,
} from 'lucide-react';
import { AreaChart, Area, BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';
import Card from '@/Components/ui/Card';
import StatWidget from '@/Components/ui/StatWidget';
import PageHeader from '@/Components/ui/PageHeader';
import { formatRupiah, formatRupiahSingkat } from '@/Utils/formatCurrency';
import { statusStyle } from '@/Utils/status';

const focusRing =
    'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-green focus-visible:ring-offset-2';

// Satu sumber seri Mutasi Kas: warna Bar & legenda dibaca dari sini.
// Semantik: masuk = keluarga hijau/navy/langit, keluar = netral (bukan merah/error).
const kasSeries = [
    { key: 'topup', name: 'Topup Saldo', color: '#0F1E36', stack: 'masuk' },
    { key: 'angsuran', name: 'Pembayaran Angsuran', color: '#1FA24C', stack: 'masuk' },
    { key: 'dana_sosial', name: 'Dana Sosial', color: '#0EA5E9', stack: 'masuk', radius: [4, 4, 0, 0] },
    { key: 'pencairan', name: 'Pencairan Pinjaman', color: '#64748B', stack: 'keluar', radius: [4, 4, 0, 0] },
];

export default function Dashboard({ stats, actionable, grafikTren, grafikKas, aktivitasTerbaru }) {
    const { auth } = usePage().props;
    const userPermissions = auth.user?.permissions ?? [];
    const [rincianTerbuka, setRincianTerbuka] = useState(false);

    function bisaAkses(permission) {
        return permission === null || userPermissions.includes(permission);
    }

    const namaDepan = (auth.user?.name ?? '').split(' ')[0];
    const hariIni = new Date().toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });

    const widgets = [
        { label: 'Anggota Aktif', value: stats.total_anggota_aktif, icon: Users, tone: 'navy' },
        { label: 'Total Simpanan', value: formatRupiah(stats.total_simpanan_outstanding), icon: PiggyBank, tone: 'green' },
        { label: 'Pinjaman Outstanding', value: formatRupiah(stats.pinjaman_outstanding), icon: HandCoins, tone: 'amber' },
        { label: 'Untung Bulan Ini', value: formatRupiah(stats.keuntungan_bulan_ini), icon: TrendingUp, tone: 'green' },
    ];

    const actionItems = [
        { label: 'Tinjauan Bendahara', value: actionable.menunggu_tinjauan_bendahara, icon: ClipboardCheck, href: route('bendahara.pinjaman.index'), urgent: actionable.menunggu_tinjauan_bendahara > 0, permission: 'pinjaman.tinjau-bendahara' },
        { label: 'Approval Ketua', value: actionable.menunggu_approval_ketua, icon: ShieldCheck, href: route('ketua.pinjaman.index'), urgent: actionable.menunggu_approval_ketua > 0, permission: 'pinjaman.approve-ketua' },
        { label: 'Perubahan Tenor (Bendahara)', value: actionable.perubahan_tenor, icon: FileClock, href: route('bendahara.percepatan.index'), urgent: actionable.perubahan_tenor > 0, permission: 'pinjaman.tinjau-bendahara' },
        { label: 'Perubahan Tenor (Ketua)', value: actionable.perubahan_tenor, icon: FileClock, href: route('ketua.percepatan.index'), urgent: actionable.perubahan_tenor > 0, permission: 'pinjaman.approve-ketua' },
        { label: 'Pengajuan Limit', value: actionable.pengajuan_limit, icon: Gauge, href: route('ketua.pengajuan-limit.index'), urgent: actionable.pengajuan_limit > 0, permission: 'pinjaman.approve-ketua' },
        { label: 'Belum Setor Simpanan', value: actionable.anggota_belum_simpanan, icon: AlertCircle, href: route('bendahara.simpanan.index'), urgent: actionable.anggota_belum_simpanan > 0, permission: 'simpanan.konfirmasi' },
        { label: 'Angsuran Jatuh Tempo', value: actionable.angsuran_jatuh_tempo, icon: CalendarClock, href: route('bendahara.angsuran.index'), urgent: actionable.angsuran_jatuh_tempo > 0, permission: 'angsuran.konfirmasi' },
    ].filter((item) => bisaAkses(item.permission));

    const jumlahMenunggu = actionItems.filter((item) => item.value > 0).length;

    const totalKasMasuk = grafikKas.reduce((s, r) => s + (r.topup ?? 0) + (r.angsuran ?? 0) + (r.dana_sosial ?? 0), 0);
    const totalKasKeluar = grafikKas.reduce((s, r) => s + (r.pencairan ?? 0), 0);
    const trenKosong = grafikTren.every((r) => (r.simpanan ?? 0) === 0 && (r.pinjaman ?? 0) === 0);
    const kasKosong = totalKasMasuk === 0 && totalKasKeluar === 0;

    return (
        <AppLayout>
            <Head title="Dashboard" />

            <PageHeader
                title={namaDepan ? `Halo, ${namaDepan}` : 'Dashboard'}
                subtitle={`${hariIni} • ${jumlahMenunggu > 0 ? `${jumlahMenunggu} menunggu aksi` : 'Semua beres'}`}
            />

            <div className="rounded-2xl bg-gradient-to-r from-brand-navy to-brand-navy-light text-white px-5 py-4 mb-3 shadow-md shadow-brand-navy/20">
                <div className="flex items-center gap-3">
                    <span className="w-11 h-11 rounded-2xl bg-white/15 inline-flex items-center justify-center shrink-0">
                        <Landmark size={22} />
                    </span>
                    <div className="min-w-0 flex-1">
                        <p className="text-xs text-white/70">Saldo Total Koperasi</p>
                        <p className="text-2xl font-bold tabular-nums leading-tight">{formatRupiah(stats.total_keseluruhan)}</p>
                    </div>
                    <button
                        type="button"
                        onClick={() => setRincianTerbuka((v) => !v)}
                        aria-expanded={rincianTerbuka}
                        className="inline-flex items-center gap-1.5 text-xs font-semibold text-white/80 hover:text-white bg-white/10 hover:bg-white/20 rounded-full px-3 py-1.5 transition-colors shrink-0"
                    >
                        Rincian
                        <ChevronDown size={14} className={`transition-transform ${rincianTerbuka ? 'rotate-180' : ''}`} />
                    </button>
                </div>
                {rincianTerbuka && (
                    <dl className="grid grid-cols-2 gap-2 mt-3 pt-3 border-t border-white/15 text-sm">
                        <div className="rounded-xl bg-white/10 px-3 py-2">
                            <dt className="text-xs text-white/70">Dana pinjaman</dt>
                            <dd className="font-bold tabular-nums">{formatRupiah(stats.saldo_dana_pinjaman)}</dd>
                        </div>
                        <div className="rounded-xl bg-white/10 px-3 py-2">
                            <dt className="text-xs text-white/70">Dana sosial</dt>
                            <dd className="font-bold tabular-nums">{formatRupiah(stats.saldo_dana_sosial)}</dd>
                        </div>
                        <div className="rounded-xl bg-white/10 px-3 py-2">
                            <dt className="text-xs text-white/70">Simpanan outstanding</dt>
                            <dd className="font-bold tabular-nums">{formatRupiah(stats.total_simpanan_outstanding)}</dd>
                        </div>
                        <div className="rounded-xl bg-white/10 px-3 py-2">
                            <dt className="text-xs text-white/70">Gross akumulasi (audit)</dt>
                            <dd className="font-bold tabular-nums">{formatRupiah(stats.total_simpanan_akumulasi)}</dd>
                        </div>
                    </dl>
                )}
            </div>

            <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
                {widgets.map((w) => (
                    <div key={w.label} className="transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md rounded-xl">
                        <StatWidget compact label={w.label} value={w.value} icon={w.icon} tone={w.tone} />
                    </div>
                ))}
            </div>

            {/* Perlu Ditindaklanjuti */}
            <div className="mb-6">
                <div className="flex items-center justify-between mb-3">
                    <p className="text-base font-bold text-slate-700">Perlu Ditindaklanjuti</p>
                    <p className="text-xs text-slate-400">
                        {jumlahMenunggu > 0 ? `${jumlahMenunggu} menunggu aksi • klik untuk ke menu` : 'Semua beres'}
                    </p>
                </div>
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    {actionItems.map((item) => {
                        const Icon = item.icon;
                        return (
                            <Link
                                key={item.label}
                                href={item.href}
                                title={item.label}
                                className={`relative flex items-center gap-2.5 rounded-xl border px-3.5 py-3 transition-colors ${focusRing} ${
                                    item.urgent
                                        ? 'bg-amber-50 border-amber-200 hover:bg-amber-100/70'
                                        : 'bg-white border-slate-200 hover:bg-slate-50'
                                }`}
                            >
                                {item.urgent && (
                                    <span className="absolute top-2 right-2 flex h-1.5 w-1.5">
                                        <span className="absolute inline-flex h-full w-full animate-ping motion-reduce:hidden rounded-full bg-red-400 opacity-75" />
                                        <span className="relative inline-flex h-1.5 w-1.5 rounded-full bg-red-500" />
                                    </span>
                                )}
                                <Icon size={18} aria-hidden="true" className={`shrink-0 ${item.urgent ? 'text-amber-600' : 'text-slate-400'}`} />
                                <div className="min-w-0 flex-1">
                                    <p className={`text-xl font-bold leading-none tabular-nums ${item.urgent ? 'text-amber-700' : 'text-slate-600'}`}>
                                        {item.value}
                                    </p>
                                    <p className={`text-xs font-medium leading-tight mt-1 ${item.urgent ? 'text-slate-600' : 'text-slate-500'}`}>
                                        {item.label}
                                    </p>
                                </div>
                                <ChevronRight size={16} aria-hidden="true" className={`shrink-0 ${item.urgent ? 'text-amber-600/60' : 'text-slate-400'}`} />
                            </Link>
                        );
                    })}
                </div>
            </div>

            {/* Tren Simpanan & Pinjaman */}
            <div className="mb-6">
                <Card>
                    <div className="flex items-center justify-between gap-3 flex-wrap mb-3">
                        <div>
                            <p className="text-base font-bold text-slate-700">Tren Simpanan &amp; Pinjaman</p>
                            <p className="text-xs text-slate-400 mt-0.5">Enam bulan terakhir</p>
                        </div>

                        <div className="flex items-center gap-4">
                            <div className="flex items-center gap-1.5">
                                <span className="w-2.5 h-2.5 rounded-full bg-brand-green" />
                                <span className="text-xs text-slate-500">Simpanan</span>
                            </div>
                            <div className="flex items-center gap-1.5">
                                <span className="w-2.5 h-2.5 rounded-full bg-brand-navy" />
                                <span className="text-xs text-slate-500">Pinjaman Cair</span>
                            </div>
                        </div>
                    </div>

                    {trenKosong ? (
                        <p className="text-sm text-slate-400 text-center py-16">Belum ada simpanan atau pencairan 6 bulan terakhir.</p>
                    ) : (
                        <div role="img" aria-label="Grafik tren simpanan dan pinjaman cair enam bulan terakhir">
                            <ResponsiveContainer width="100%" height={280}>
                                <AreaChart data={grafikTren} margin={{ left: -10 }}>
                                    <defs>
                                        <linearGradient id="colorSimpanan" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="5%" stopColor="#1FA24C" stopOpacity={0.25} />
                                            <stop offset="95%" stopColor="#1FA24C" stopOpacity={0} />
                                        </linearGradient>
                                        <linearGradient id="colorPinjaman" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="5%" stopColor="#0F1E36" stopOpacity={0.25} />
                                            <stop offset="95%" stopColor="#0F1E36" stopOpacity={0} />
                                        </linearGradient>
                                    </defs>
                                    <CartesianGrid strokeDasharray="3 3" stroke="#f1f5f9" />
                                    <XAxis dataKey="bulan" tick={{ fontSize: 12, fill: '#94a3b8' }} axisLine={false} tickLine={false} />
                                    <YAxis tick={{ fontSize: 12, fill: '#94a3b8' }} axisLine={false} tickLine={false} tickFormatter={formatRupiahSingkat} />
                                    <Tooltip formatter={(value) => formatRupiah(value)} contentStyle={{ borderRadius: 12, border: '1px solid #e2e8f0', fontSize: 13 }} />
                                    <Area type="monotone" dataKey="simpanan" name="Simpanan" stroke="#1FA24C" fillOpacity={1} fill="url(#colorSimpanan)" strokeWidth={2} />
                                    <Area type="monotone" dataKey="pinjaman" name="Pinjaman Cair" stroke="#0F1E36" fillOpacity={1} fill="url(#colorPinjaman)" strokeWidth={2} />
                                </AreaChart>
                            </ResponsiveContainer>
                        </div>
                    )}
                </Card>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-5">
                {/* Aktivitas Terbaru */}
                <Card className="lg:col-span-1 sm:p-6">
                    <p className="text-base font-bold text-slate-700 mb-4">Aktivitas Terbaru</p>

                    {aktivitasTerbaru.length === 0 ? (
                        <p className="text-sm text-slate-400 text-center py-8">Belum ada aktivitas.</p>
                    ) : (
                        <div className="space-y-3">
                            {aktivitasTerbaru.map((item, i) => (
                                <div key={i} className="flex items-start gap-3">
                                    <div className={`w-8 h-8 rounded-full flex items-center justify-center shrink-0 text-xs font-bold ${statusStyle[item.status] ?? 'text-slate-600 bg-slate-100'}`} aria-hidden="true">
                                        {item.nama.charAt(0).toUpperCase()}
                                    </div>
                                    <div className="flex-1 min-w-0">
                                        <p className="text-sm font-semibold text-slate-700 truncate">{item.nama}</p>
                                        <p className="text-xs text-slate-400 truncate">{item.keterangan}</p>
                                        <p className="text-xs text-slate-400 mt-0.5 tabular-nums">{item.tanggal_format}</p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </Card>

                {/* Mutasi Kas */}
                <Card className="lg:col-span-2 sm:p-6">
                <div className="flex items-center justify-between gap-3 flex-wrap mb-1">
                    <div>
                        <p className="text-base font-bold text-slate-700">Mutasi Kas Koperasi</p>
                        <p className="text-xs text-slate-400 mt-0.5">Enam bulan terakhir</p>
                    </div>
                    <div className="flex items-center gap-2">
                        <span className="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-brand-green-light text-brand-green-dark tabular-nums">
                            +{formatRupiahSingkat(totalKasMasuk)}
                        </span>
                        <span className="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-600 tabular-nums">
                            -{formatRupiahSingkat(totalKasKeluar)}
                        </span>
                    </div>
                </div>
                {kasKosong ? (
                    <p className="text-sm text-slate-400 text-center py-16">Belum ada mutasi kas 6 bulan terakhir.</p>
                ) : (
                    <>
                        <div className="min-h-[280px]" role="img" aria-label="Grafik mutasi kas koperasi enam bulan terakhir">
                            <ResponsiveContainer width="100%" height={280}>
                            <BarChart data={grafikKas} margin={{ left: -10 }} barCategoryGap="25%">
                                <CartesianGrid strokeDasharray="3 3" stroke="#f1f5f9" vertical={false} />
                                <XAxis dataKey="bulan" tick={{ fontSize: 12, fill: '#94a3b8' }} axisLine={false} tickLine={false} />
                                <YAxis tick={{ fontSize: 12, fill: '#94a3b8' }} axisLine={false} tickLine={false} tickFormatter={formatRupiahSingkat} />
                                <Tooltip
                                    cursor={{ fill: 'rgba(15, 30, 54, 0.04)' }}
                                    formatter={(value) => formatRupiah(value)}
                                    contentStyle={{ borderRadius: 12, border: '1px solid #e2e8f0', fontSize: 13 }}
                                />
                                {kasSeries.map((s) => (
                                    <Bar
                                        key={s.key}
                                        stackId={s.stack}
                                        dataKey={s.key}
                                        name={s.name}
                                        fill={s.color}
                                        maxBarSize={26}
                                        radius={s.radius ?? [0, 0, 0, 0]}
                                    />
                                ))}
                            </BarChart>
                        </ResponsiveContainer>
                        </div>
                        <div className="flex items-center gap-5 mt-3 justify-center flex-wrap">
                            {kasSeries.map((s) => (
                                <div key={s.key} className="flex items-center gap-1.5">
                                    <span className="w-2.5 h-2.5 rounded-sm" style={{ backgroundColor: s.color }} />
                                    <span className="text-xs text-slate-500">{s.name}</span>
                                </div>
                            ))}
                        </div>
                    </>
                )}
            </Card>
            </div>
        </AppLayout>
    );
}
