import AppLayout from '@/Layouts/AppLayout';
import { Head, Link } from '@inertiajs/react';
import {
    Users, PiggyBank, HandCoins, Wallet, TrendingUp, HeartHandshake,
    ClipboardCheck, ShieldCheck, AlertCircle, CalendarClock,
    HandCoins as PinjamanIcon, CheckCircle2,
} from 'lucide-react';
import { AreaChart, Area, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';

function formatRupiah(angka) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka);
}

function formatRupiahSingkat(angka) {
    if (angka >= 1_000_000) return `${(angka / 1_000_000).toFixed(1)}jt`;
    if (angka >= 1_000) return `${(angka / 1_000).toFixed(0)}rb`;
    return angka;
}

const statusLabel = {
    diajukan: 'Diajukan',
    approved_bendahara: 'Disetujui Bendahara',
    aktif: 'Aktif',
    lunas: 'Lunas',
    ditolak: 'Ditolak',
};

const statusStyle = {
    aktif: 'text-blue-600 bg-blue-50',
    lunas: 'text-brand-green-dark bg-brand-green-light',
    ditolak: 'text-red-600 bg-red-50',
    diajukan: 'text-amber-700 bg-amber-50',
    approved_bendahara: 'text-amber-700 bg-amber-50',
};

export default function Dashboard({ stats, actionable, grafikTren, aktivitasTerbaru }) {
    const widgets = [
        { label: 'Total Anggota Aktif', value: stats.total_anggota_aktif, icon: Users, tone: 'bg-brand-navy/5 text-brand-navy' },
        { label: 'Total Simpanan', value: formatRupiah(stats.total_simpanan), icon: PiggyBank, tone: 'bg-brand-green-light text-brand-green-dark' },
        { label: 'Pinjaman Outstanding', value: formatRupiah(stats.pinjaman_outstanding), icon: HandCoins, tone: 'bg-amber-50 text-amber-700' },
        { label: 'Saldo Kas Koperasi', value: formatRupiah(stats.saldo_kas), icon: Wallet, tone: 'bg-brand-navy/5 text-brand-navy' },
        { label: 'Keuntungan Bulan Ini', value: formatRupiah(stats.keuntungan_bulan_ini), icon: TrendingUp, tone: 'bg-brand-green-light text-brand-green-dark' },
        { label: 'Dana Sosial Terkumpul', value: formatRupiah(stats.total_dana_sosial), icon: HeartHandshake, tone: 'bg-amber-50 text-amber-700' },
    ];

    const actionItems = [
        { label: 'Menunggu Tinjauan Bendahara', value: actionable.menunggu_tinjauan_bendahara, icon: ClipboardCheck, href: route('bendahara.pinjaman.index'), urgent: actionable.menunggu_tinjauan_bendahara > 0 },
        { label: 'Menunggu Approval Ketua', value: actionable.menunggu_approval_ketua, icon: ShieldCheck, href: route('ketua.pinjaman.index'), urgent: actionable.menunggu_approval_ketua > 0 },
        { label: 'Anggota Belum Simpanan Bulan Ini', value: actionable.anggota_belum_simpanan, icon: AlertCircle, href: route('bendahara.simpanan.index'), urgent: actionable.anggota_belum_simpanan > 0 },
        { label: 'Angsuran Jatuh Tempo Bulan Ini', value: actionable.angsuran_jatuh_tempo, icon: CalendarClock, href: route('bendahara.angsuran.index'), urgent: actionable.angsuran_jatuh_tempo > 0 },
    ];

    return (
        <AppLayout>
            <Head title="Dashboard" />

            <div className="mb-6">
                <h1 className="text-2xl font-bold text-slate-800">Dashboard</h1>
                <p className="text-base text-slate-400 mt-1">Ringkasan aktivitas koperasi hari ini</p>
            </div>

            {/* Widget Utama */}
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                {widgets.map((w) => {
                    const Icon = w.icon;
                    return (
                        <div key={w.label} className="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                            <div className={`w-11 h-11 rounded-xl flex items-center justify-center mb-3 ${w.tone}`}>
                                <Icon size={22} />
                            </div>
                            <p className="text-sm text-slate-500">{w.label}</p>
                            <p className="text-xl font-bold text-slate-800 mt-1">{w.value}</p>
                        </div>
                    );
                })}
            </div>

            {/* Perlu Ditindaklanjuti */}
            <div className="mb-6">
                <p className="text-base font-bold text-slate-700 mb-3">Perlu Ditindaklanjuti</p>
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    {actionItems.map((item) => {
                        const Icon = item.icon;
                        return (
                            <Link
                                key={item.label}
                                href={item.href}
                                className={`rounded-2xl border p-4 transition-colors ${
                                    item.urgent
                                        ? 'bg-amber-50 border-amber-100 hover:bg-amber-100/70'
                                        : 'bg-white border-slate-100 hover:bg-slate-50'
                                }`}
                            >
                                <div className="flex items-center justify-between mb-2">
                                    <Icon size={18} className={item.urgent ? 'text-amber-600' : 'text-slate-400'} />
                                    <span className={`text-2xl font-bold ${item.urgent ? 'text-amber-700' : 'text-slate-700'}`}>
                                        {item.value}
                                    </span>
                                </div>
                                <p className="text-sm font-medium text-slate-600">{item.label}</p>
                            </Link>
                        );
                    })}
                </div>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-5">
                {/* Grafik Tren */}
                <div className="lg:col-span-2 bg-white rounded-2xl border border-slate-100 p-5 sm:p-6">
                    <p className="text-base font-bold text-slate-700 mb-4">Tren Simpanan &amp; Pinjaman (6 Bulan Terakhir)</p>
                    <ResponsiveContainer width="100%" height={260}>
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
                            <Tooltip formatter={(value) => formatRupiah(value)} contentStyle={{ borderRadius: 12, border: '1px solid #f1f5f9', fontSize: 13 }} />
                            <Area type="monotone" dataKey="simpanan" name="Simpanan" stroke="#1FA24C" fillOpacity={1} fill="url(#colorSimpanan)" strokeWidth={2} />
                            <Area type="monotone" dataKey="pinjaman" name="Pinjaman Cair" stroke="#0F1E36" fillOpacity={1} fill="url(#colorPinjaman)" strokeWidth={2} />
                        </AreaChart>
                    </ResponsiveContainer>
                    <div className="flex items-center gap-5 mt-2 justify-center">
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

                {/* Aktivitas Terbaru */}
                <div className="bg-white rounded-2xl border border-slate-100 p-5 sm:p-6">
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
                </div>
            </div>
        </AppLayout>
    );
}