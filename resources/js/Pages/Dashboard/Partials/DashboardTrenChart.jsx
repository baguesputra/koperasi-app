import { Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import { AreaChart, Area, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';
import Card from '@/Components/ui/Card';
import { formatRupiah, formatRupiahSingkat } from '@/Utils/formatCurrency';

const focusRing =
    'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-green focus-visible:ring-offset-2';

export default function DashboardTrenChart({ grafikTren, bisaAkses, isPrivacyActive, labelBulan }) {
    return (
        <Card className="border-slate-200/80 shadow-2xs sm:p-6">
            <div className="flex items-start justify-between gap-3 flex-wrap mb-5">
                <div>
                    <h2 className="text-base font-bold text-slate-800 tracking-tight">Tren Simpanan &amp; Pinjaman</h2>
                    <p className="text-xs text-slate-400 mt-0.5">Akumulasi bulanan 6 bulan terakhir • {labelBulan}</p>
                </div>

                <div className="flex items-center gap-4 flex-wrap">
                    <div className="flex items-center gap-3 bg-slate-50 border border-slate-200/70 rounded-full px-3 py-1">
                        <div className="flex items-center gap-1.5">
                            <span className="w-2 h-2 rounded-full bg-brand-green" />
                            <span className="text-xs font-medium text-slate-600">Simpanan</span>
                        </div>
                        <span className="w-px h-3 bg-slate-200" aria-hidden="true" />
                        <div className="flex items-center gap-1.5">
                            <span className="w-2 h-2 rounded-full bg-brand-navy" />
                            <span className="text-xs font-medium text-slate-600">Pinjaman Cair</span>
                        </div>
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
            </div>

            <div className={`transition-all duration-300 ${isPrivacyActive ? 'privacy-chart' : ''}`}>
                <ResponsiveContainer width="100%" height={260}>
                    <AreaChart key={`tren-chart-${isPrivacyActive ? 'locked' : 'unlocked'}`} data={grafikTren} margin={{ top: 4, right: 4, left: -12, bottom: 0 }}>
                        <defs>
                            <linearGradient id="colorSimpanan" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="5%" stopColor="#1FA24C" stopOpacity={0.22} />
                                <stop offset="95%" stopColor="#1FA24C" stopOpacity={0} />
                            </linearGradient>
                            <linearGradient id="colorPinjaman" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="5%" stopColor="#0F1E36" stopOpacity={0.18} />
                                <stop offset="95%" stopColor="#0F1E36" stopOpacity={0} />
                            </linearGradient>
                        </defs>
                        <CartesianGrid strokeDasharray="3 3" stroke="#f1f5f9" vertical={false} />
                        <XAxis dataKey="bulan" tick={{ fontSize: 12, fill: '#64748b' }} axisLine={false} tickLine={false} dy={6} />
                        <YAxis
                            tick={{ fontSize: 12, fill: '#94a3b8' }}
                            axisLine={false}
                            tickLine={false}
                            tickFormatter={isPrivacyActive ? () => '••••' : formatRupiahSingkat}
                        />
                        <Tooltip
                            formatter={(value) => (isPrivacyActive ? '••••••••' : formatRupiah(value))}
                            contentStyle={{
                                borderRadius: 12,
                                border: '1px solid rgba(15, 23, 42, 0.08)',
                                boxShadow: '0 10px 25px -5px rgba(15, 23, 42, 0.1)',
                                fontSize: 12,
                                fontWeight: 600,
                            }}
                        />
                        <Area type="monotone" dataKey="simpanan" name="Simpanan" stroke="#1FA24C" fillOpacity={1} fill="url(#colorSimpanan)" strokeWidth={2.2} />
                        <Area type="monotone" dataKey="pinjaman" name="Pinjaman Cair" stroke="#0F1E36" fillOpacity={1} fill="url(#colorPinjaman)" strokeWidth={2.2} />
                    </AreaChart>
                </ResponsiveContainer>
            </div>
        </Card>
    );
}

