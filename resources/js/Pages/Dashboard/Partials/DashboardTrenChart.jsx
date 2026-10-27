import { Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import { AreaChart, Area, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';
import Card from '@/Components/ui/Card';
import { formatRupiah, formatRupiahSingkat } from '@/Utils/formatCurrency';

const focusRing =
    'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-green focus-visible:ring-offset-2';

export default function DashboardTrenChart({ grafikTren, bisaAkses, isPrivacyActive, labelBulan }) {
    return (
        <Card>
            <div className="flex items-center justify-between gap-3 flex-wrap mb-3">
                <div>
                    <p className="text-base font-bold text-slate-700">Tren Simpanan &amp; Pinjaman</p>
                    <p className="text-xs text-slate-400 mt-0.5">Enam bulan terakhir • periode {labelBulan}</p>
                </div>

                <div className="flex items-center gap-4 flex-wrap">
                    <div className="flex items-center gap-1.5">
                        <span className="w-2.5 h-2.5 rounded-full bg-brand-green" />
                        <span className="text-xs text-slate-500">Simpanan</span>
                    </div>
                    <div className="flex items-center gap-1.5">
                        <span className="w-2.5 h-2.5 rounded-full bg-brand-navy" />
                        <span className="text-xs text-slate-500">Pinjaman Cair</span>
                    </div>
                    {bisaAkses('laporan.lihat') && (
                        <Link
                            href={route('laporan.show', 'arus-kas')}
                            className={`inline-flex items-center gap-0.5 text-xs font-bold text-brand-green-dark hover:underline ${focusRing} rounded`}
                        >
                            Buka Laporan
                            <ChevronRight size={12} aria-hidden="true" />
                        </Link>
                    )}
                </div>
            </div>

            <div className={`transition-all duration-300 ${isPrivacyActive ? 'privacy-chart' : ''}`}>
                <ResponsiveContainer width="100%" height={280}>
                    <AreaChart key={`tren-chart-${isPrivacyActive ? 'locked' : 'unlocked'}`} data={grafikTren} margin={{ left: -10 }}>
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
                        <YAxis
                            tick={{ fontSize: 12, fill: '#94a3b8' }}
                            axisLine={false}
                            tickLine={false}
                            tickFormatter={isPrivacyActive ? () => '••••' : formatRupiahSingkat}
                        />
                        <Tooltip
                            formatter={(value) => (isPrivacyActive ? '••••••••' : formatRupiah(value))}
                            contentStyle={{ borderRadius: 12, border: '1px solid #e2e8f0', fontSize: 13 }}
                        />
                        <Area type="monotone" dataKey="simpanan" name="Simpanan" stroke="#1FA24C" fillOpacity={1} fill="url(#colorSimpanan)" strokeWidth={2} />
                        <Area type="monotone" dataKey="pinjaman" name="Pinjaman Cair" stroke="#0F1E36" fillOpacity={1} fill="url(#colorPinjaman)" strokeWidth={2} />
                    </AreaChart>
                </ResponsiveContainer>
            </div>
        </Card>
    );
}

