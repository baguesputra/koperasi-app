import AppLayout from '@/Layouts/AppLayout';
import { Head, Link, usePage } from '@inertiajs/react';
import {
    Users, PiggyBank, HandCoins, Wallet, TrendingUp, HeartHandshake, Landmark,
    ClipboardCheck, ShieldCheck, AlertCircle, CalendarClock, FileClock, Gauge,
    ChevronRight,
    HandCoins as PinjamanIcon, CheckCircle2,
} from 'lucide-react';
import { AreaChart, Area, BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';
import Card from '@/Components/ui/Card';
import StatWidget from '@/Components/ui/StatWidget';
import PageHeader from '@/Components/ui/PageHeader';
import { formatRupiah, formatRupiahSingkat } from '@/Utils/formatCurrency';
import { statusStyle } from '@/Utils/status';

export default function Dashboard({ stats, actionable, grafikTren, grafikKas, aktivitasTerbaru }) {
    const { auth } = usePage().props;
    const aksesBendahara = auth.user?.permissions?.includes('pinjaman.tinjau-bendahara');

    const widgets = [
        { label: 'Total Anggota Aktif', value: stats.total_anggota_aktif, icon: Users, tone: 'navy' },
        { label: 'Total Simpanan', value: formatRupiah(stats.total_simpanan), icon: PiggyBank, tone: 'green' },
        { label: 'Pinjaman Outstanding', value: formatRupiah(stats.pinjaman_outstanding), icon: HandCoins, tone: 'amber' },
        { label: 'Saldo Dana Pinjaman', value: formatRupiah(stats.saldo_dana_pinjaman), icon: Wallet, tone: 'navy' },
        { label: 'Saldo Dana Sosial', value: formatRupiah(stats.saldo_dana_sosial), icon: HeartHandshake, tone: 'amber' },
        { label: 'Keuntungan Bulan Ini', value: formatRupiah(stats.keuntungan_bulan_ini), icon: TrendingUp, tone: 'green' },
    ];

    const actionItems = [
        { label: 'Menunggu Tinjauan Bendahara', value: actionable.menunggu_tinjauan_bendahara, icon: ClipboardCheck, href: route('bendahara.pinjaman.index'), urgent: actionable.menunggu_tinjauan_bendahara > 0 },
        { label: 'Menunggu Approval Ketua', value: actionable.menunggu_approval_ketua, icon: ShieldCheck, href: route('ketua.pinjaman.index'), urgent: actionable.menunggu_approval_ketua > 0 },
        { label: 'Approval Perubahan Tenor', value: actionable.perubahan_tenor, icon: FileClock, href: aksesBendahara ? route('bendahara.percepatan.index') : route('ketua.percepatan.index'), urgent: actionable.perubahan_tenor > 0 },
        { label: 'Pengajuan Limit', value: actionable.pengajuan_limit, icon: Gauge, href: route('ketua.pengajuan-limit.index'), urgent: actionable.pengajuan_limit > 0 },
        { label: 'Anggota Belum Simpanan Bulan Ini', value: actionable.anggota_belum_simpanan, icon: AlertCircle, href: route('bendahara.simpanan.index'), urgent: actionable.anggota_belum_simpanan > 0 },
        { label: 'Angsuran Jatuh Tempo Bulan Ini', value: actionable.angsuran_jatuh_tempo, icon: CalendarClock, href: route('bendahara.angsuran.index'), urgent: actionable.angsuran_jatuh_tempo > 0 },
    ];

    const jumlahMenunggu = actionItems.filter((item) => item.value > 0).length;

    return (
        <AppLayout>
            <Head title="Dashboard" />

            <PageHeader title="Dashboard" subtitle="Ringkasan aktivitas koperasi hari ini" />

            {/* Hero: Grafik Tren (full width, tanpa Card) + Stat Widget overlap */}
            <div className="relative mb-6">
                <div className="flex items-center justify-between mb-2">
                    <p className="text-base font-bold text-slate-700">Tren Simpanan &amp; Pinjaman (6 Bulan Terakhir)</p>
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

                <div className="pb-20">
                    <ResponsiveContainer width="100%" height={300}>
                        <AreaChart data={grafikTren} margin={{ left: -10, bottom: 24 }}>
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
                            <Tooltip formatter={(value) => formatRupiah(value)} contentStyle={{ borderRadius: 12, border: '1px solid #f1f5f9', fontSize: 13 }} />
                            <Area type="monotone" dataKey="simpanan" name="Simpanan" stroke="#1FA24C" fillOpacity={1} fill="url(#colorSimpanan)" strokeWidth={2} />
                            <Area type="monotone" dataKey="pinjaman" name="Pinjaman Cair" stroke="#0F1E36" fillOpacity={1} fill="url(#colorPinjaman)" strokeWidth={2} />
                        </AreaChart>
                    </ResponsiveContainer>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 -mt-16 lg:-mt-24 relative z-10">
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:col-span-2 lg:col-span-3">
                        {widgets.map((w) => (
                            <StatWidget compact key={w.label} label={w.label} value={w.value} icon={w.icon} tone={w.tone} />
                        ))}
                    </div>
                    <div className="bg-brand-navy rounded-2xl p-5 text-white flex flex-col justify-between sm:col-span-2 lg:col-span-1 lg:min-h-full">
                        <div>
                            <div className="w-10 h-10 rounded-xl bg-white/10 text-brand-green flex items-center justify-center mb-3">
                                <Landmark size={20} />
                            </div>
                            <p className="text-sm text-slate-300">Saldo Total</p>
                            <p className="text-3xl font-bold mt-1 leading-tight">{formatRupiah(stats.total_keseluruhan)}</p>
                        </div>
                        <p className="text-xs text-slate-300 mt-4 leading-snug">Dana pinjaman + dana sosial + simpanan anggota</p>
                    </div>
                </div>
            </div>

            {/* Perlu Ditindaklanjuti */}
            <div className="mb-6">
                <div className="flex items-center justify-between mb-3">
                    <p className="text-base font-bold text-slate-700">Perlu Ditindaklanjuti</p>
                    <p className="text-xs text-slate-400">
                        {jumlahMenunggu > 0 ? `${jumlahMenunggu} menunggu aksi • klik untuk ke menu` : 'Semua beres'}
                    </p>
                </div>
                <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                    {actionItems.map((item) => {
                        const Icon = item.icon;
                        return (
                            <Link
                                key={item.label}
                                href={item.href}
                                title={item.label}
                                className={`relative flex items-center gap-2.5 rounded-xl border px-3.5 py-3 transition-colors ${
                                    item.urgent
                                        ? 'bg-amber-50 border-amber-100 hover:bg-amber-100/70'
                                        : 'bg-white border-slate-100 hover:bg-slate-50'
                                }`}
                            >
                                {item.urgent && (
                                    <span className="absolute top-2 right-2 flex h-1.5 w-1.5">
                                        <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-400 opacity-75" />
                                        <span className="relative inline-flex h-1.5 w-1.5 rounded-full bg-red-500" />
                                    </span>
                                )}
                                <Icon size={18} className={`shrink-0 ${item.urgent ? 'text-amber-600' : 'text-slate-300'}`} />
                                <div className="min-w-0 flex-1">
                                    <p className={`text-xl font-bold leading-none ${item.urgent ? 'text-amber-700' : 'text-slate-300'}`}>
                                        {item.value}
                                    </p>
                                    <p className={`text-xs font-medium leading-tight mt-1 ${item.urgent ? 'text-slate-500' : 'text-slate-400'}`}>
                                        {item.label}
                                    </p>
                                </div>
                                <ChevronRight size={16} className={`shrink-0 ${item.urgent ? 'text-amber-600/60' : 'text-slate-300'}`} />
                            </Link>
                        );
                    })}
                </div>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-5">
                {/* Aktivitas Terbaru */}
                <Card className="lg:col-span-1 sm:p-6">
                    <p className="text-base font-bold text-slate-700 mb-4">Aktivitas Terbaru</p>

                    {aktivitasTerbaru.length === 0 ? (
                        <p className="text-sm text-slate-400 text-center py-8">Belum ada aktivitas.</p>
                    ) : (
                        <div className="space-y-3">
                            {aktivitasTerbaru.map((item, i) => {
                                const Icon = item.tipe === 'pinjaman' ? PinjamanIcon : CheckCircle2;
                                return (
                                    <div key={i} className="flex items-start gap-3">
                                        <div className={`w-8 h-8 rounded-full flex items-center justify-center shrink-0 ${statusStyle[item.status]}`}>
                                            <Icon size={14} />
                                        </div>
                                        <div className="flex-1 min-w-0">
                                            <p className="text-sm font-semibold text-slate-700 truncate">{item.nama}</p>
                                            <p className="text-xs text-slate-400 truncate">{item.keterangan}</p>
                                            <p className="text-xs text-slate-300 mt-0.5">{item.tanggal_format}</p>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    )}
                </Card>

                {/* Mutasi Kas */}
                <Card className="lg:col-span-2 sm:p-6">
                <p className="text-base font-bold text-slate-700 mb-4">Mutasi Kas Koperasi (6 Bulan Terakhir)</p>
                <ResponsiveContainer width="100%" height={280}>
                    <BarChart data={grafikKas} margin={{ left: -10 }} barCategoryGap="25%">
                        <CartesianGrid strokeDasharray="3 3" stroke="#f1f5f9" vertical={false} />
                        <XAxis dataKey="bulan" tick={{ fontSize: 12, fill: '#94a3b8' }} axisLine={false} tickLine={false} />
                        <YAxis tick={{ fontSize: 12, fill: '#94a3b8' }} axisLine={false} tickLine={false} tickFormatter={formatRupiahSingkat} />
                        <Tooltip
                            cursor={{ fill: 'rgba(15, 30, 54, 0.04)' }}
                            formatter={(value) => formatRupiah(value)}
                            contentStyle={{ borderRadius: 12, border: '1px solid #f1f5f9', fontSize: 13 }}
                        />
                        <Bar stackId="masuk" dataKey="topup" name="Topup Saldo" fill="#86EFAC" maxBarSize={26} />
                        <Bar stackId="masuk" dataKey="angsuran" name="Pembayaran Angsuran" fill="#1FA24C" />
                        <Bar stackId="masuk" dataKey="dana_sosial" name="Dana Sosial" fill="#F59E0B" radius={[4, 4, 0, 0]} />
                        <Bar stackId="keluar" dataKey="pencairan" name="Pencairan Pinjaman" fill="#EF4444" radius={[4, 4, 0, 0]} />
                    </BarChart>
                </ResponsiveContainer>
                <div className="flex items-center gap-5 mt-2 justify-center flex-wrap">
                    <div className="flex items-center gap-1.5">
                        <span className="w-2.5 h-2.5 rounded-sm bg-green-300" />
                        <span className="text-xs text-slate-500">Topup Saldo</span>
                    </div>
                    <div className="flex items-center gap-1.5">
                        <span className="w-2.5 h-2.5 rounded-sm bg-brand-green" />
                        <span className="text-xs text-slate-500">Pembayaran Angsuran</span>
                    </div>
                    <div className="flex items-center gap-1.5">
                        <span className="w-2.5 h-2.5 rounded-sm bg-amber-500" />
                        <span className="text-xs text-slate-500">Dana Sosial</span>
                    </div>
                    <div className="flex items-center gap-1.5">
                        <span className="w-2.5 h-2.5 rounded-sm bg-red-500" />
                        <span className="text-xs text-slate-500">Pencairan Pinjaman</span>
                    </div>
                </div>
            </Card>
            </div>
        </AppLayout>
    );
}