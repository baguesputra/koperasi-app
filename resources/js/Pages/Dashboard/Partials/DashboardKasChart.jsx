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
        <Card className="lg:col-span-2 border-slate-200/80 shadow-2xs sm:p-6 flex flex-col justify-between">
            <div>
                <div className="flex items-start justify-between gap-3 flex-wrap mb-5">
                    <div>
                        <h2 className="text-base font-bold text-slate-800 tracking-tight">Mutasi Kas Koperasi</h2>
                        <p className="text-xs text-slate-400 mt-0.5">Arus masuk &amp; pencairan 6 bulan terakhir</p>
                    </div>
                    {bisaAkses('laporan.lihat') && (
                        <Link
                            href={route('laporan.show', 'arus-kas')}
                            className={`inline-flex items-center gap-1 text-xs font-semibold text-brand-green-dark hover:text-brand-green transition-colors ${focusRing} rounded`}
                        >
                            Laporan Arus Kas
                            <ChevronRight size={13} aria-hidden="true" />
                        </Link>
                    )}
                </div>

                <div className={`min-h-[260px] transition-all duration-300 ${isPrivacyActive ? 'privacy-chart' : ''}`}>
                    <ResponsiveContainer width="100%" height={260}>
                        <BarChart key={`kas-chart-${isPrivacyActive ? 'locked' : 'unlocked'}`} data={grafikKas} margin={{ top: 4, right: 4, left: -12, bottom: 0 }} barCategoryGap="26%">
                            <CartesianGrid strokeDasharray="3 3" stroke="#f1f5f9" vertical={false} />
                            <XAxis dataKey="bulan" tick={{ fontSize: 12, fill: '#64748b' }} axisLine={false} tickLine={false} dy={6} />
                            <YAxis
                                tick={{ fontSize: 12, fill: '#94a3b8' }}
                                axisLine={false}
                                tickLine={false}
                                tickFormatter={isPrivacyActive ? () => '••••' : formatRupiahSingkat}
                            />
                            <Tooltip
                                cursor={{ fill: 'rgba(15, 30, 54, 0.04)' }}
                                formatter={(value) => (isPrivacyActive ? '••••••••' : formatRupiah(value))}
                                contentStyle={{
                                    borderRadius: 12,
                                    border: '1px solid rgba(15, 23, 42, 0.08)',
                                    boxShadow: '0 10px 25px -5px rgba(15, 23, 42, 0.1)',
                                    fontSize: 12,
                                    fontWeight: 600,
                                }}
                            />
                            {kasSeries.map((s) => (
                                <Bar
                                    key={s.key}
                                    stackId={s.stack}
                                    dataKey={s.key}
                                    name={s.name}
                                    fill={s.color}
                                    maxBarSize={24}
                                    radius={s.radius ?? [0, 0, 0, 0]}
                                />
                            ))}
                        </BarChart>
                    </ResponsiveContainer>
                </div>
            </div>

            <div className="flex items-center gap-x-4 gap-y-2 mt-4 pt-3.5 border-t border-slate-100 justify-center flex-wrap">
                {kasSeries.map((s) => (
                    <div key={s.key} className="flex items-center gap-1.5">
                        <span className="w-2.5 h-2.5 rounded-xs shrink-0" style={{ backgroundColor: s.color }} />
                        <span className="text-xs font-medium text-slate-500">{s.name}</span>
                    </div>
                ))}
            </div>
        </Card>
    );
}

