import { Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';
import Card from '@/Components/ui/Card';
import { formatRupiah, formatRupiahSingkat } from '@/Utils/formatCurrency';

const focusRing =
    'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-green focus-visible:ring-offset-2';

const kasSeries = [
    { key: 'topup', name: 'Topup Saldo', color: '#0F1E36', stack: 'masuk' },
    { key: 'angsuran', name: 'Pembayaran Angsuran', color: '#1FA24C', stack: 'masuk' },
    { key: 'dana_sosial', name: 'Dana Sosial', color: '#0EA5E9', stack: 'masuk', radius: [4, 4, 0, 0] },
    { key: 'pencairan', name: 'Pencairan Pinjaman', color: '#64748B', stack: 'keluar', radius: [4, 4, 0, 0] },
];

export default function DashboardKasChart({ grafikKas, bisaAkses, isPrivacyActive }) {
    return (
        <Card className="lg:col-span-2 sm:p-6">
            <div className="flex items-start justify-between gap-3 flex-wrap mb-4">
                <div>
                    <p className="text-base font-bold text-slate-700 mb-1">Mutasi Kas Koperasi</p>
                    <p className="text-xs text-slate-400">Enam bulan terakhir</p>
                </div>
                {bisaAkses('laporan.lihat') && (
                    <Link
                        href={route('laporan.show', 'arus-kas')}
                        className={`inline-flex items-center gap-0.5 text-xs font-bold text-brand-green-dark hover:underline ${focusRing} rounded`}
                    >
                        Buka Laporan Arus Kas
                        <ChevronRight size={12} aria-hidden="true" />
                    </Link>
                )}
            </div>
            <div className={`min-h-[280px] transition-all duration-300 ${isPrivacyActive ? 'privacy-chart' : ''}`}>
                <ResponsiveContainer width="100%" height={280}>
                    <BarChart key={`kas-chart-${isPrivacyActive ? 'locked' : 'unlocked'}`} data={grafikKas} margin={{ left: -10 }} barCategoryGap="25%">
                        <CartesianGrid strokeDasharray="3 3" stroke="#f1f5f9" vertical={false} />
                        <XAxis dataKey="bulan" tick={{ fontSize: 12, fill: '#94a3b8' }} axisLine={false} tickLine={false} />
                        <YAxis
                            tick={{ fontSize: 12, fill: '#94a3b8' }}
                            axisLine={false}
                            tickLine={false}
                            tickFormatter={isPrivacyActive ? () => '••••' : formatRupiahSingkat}
                        />
                        <Tooltip
                            cursor={{ fill: 'rgba(15, 30, 54, 0.04)' }}
                            formatter={(value) => (isPrivacyActive ? '••••••••' : formatRupiah(value))}
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
        </Card>
    );
}

