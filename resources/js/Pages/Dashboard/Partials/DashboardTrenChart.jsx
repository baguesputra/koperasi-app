import { useState } from 'react';
import { Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import { AreaChart, Area, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';
import { formatRupiah, formatRupiahSingkat } from '@/Utils/formatCurrency';

const focusRing =
    'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-navy/25';

const trenSeries = [
    { key: 'simpanan', name: 'Simpanan', color: '#1FA24C', gradientId: 'colorSimpanan', startOpacity: 0.2 },
    { key: 'pinjaman', name: 'Pinjaman Cair', color: '#0F1E36', gradientId: 'colorPinjaman', startOpacity: 0.14 },
];

function CustomTrenTooltip({ active, payload, label, isPrivacyActive }) {
    if (!active || !payload || payload.length === 0) {
        return null;
    }

    return (
        <div className="rounded-xl bg-white border border-slate-200/90 shadow-md px-3.5 py-2.5 text-xs min-w-[185px]">
            <p className="font-semibold text-slate-800 pb-1.5 mb-1.5 border-b border-slate-100">{label}</p>
            <div className="space-y-1">
                {payload.map((entry) => (
                    <div key={entry.dataKey} className="flex items-center justify-between gap-4">
                        <span className="inline-flex items-center gap-1.5 text-slate-500">
                            <span className="w-2 h-2 rounded-full shrink-0" style={{ backgroundColor: entry.color }} />
                            {entry.name}
                        </span>
                        <span className="font-semibold text-slate-800 tabular-nums">
                            {isPrivacyActive ? '••••••••' : formatRupiah(entry.value ?? 0)}
                        </span>
                    </div>
                ))}
            </div>
        </div>
    );
}

export default function DashboardTrenChart({ grafikTren = [], bisaAkses, isPrivacyActive, labelBulan }) {
    const [hiddenSeries, setHiddenSeries] = useState({});
    const [range, setRange] = useState(6);

    function toggleSeries(key) {
        setHiddenSeries((prev) => {
            const next = { ...prev, [key]: !prev[key] };
            const activeCount = trenSeries.filter((s) => !next[s.key]).length;
            return activeCount === 0 ? prev : next;
        });
    }

    const dataTampil = Array.isArray(grafikTren) ? grafikTren.slice(-range) : [];

    return (
        <div className="rounded-2xl bg-white border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.03),0_4px_16px_-4px_rgba(15,23,42,0.04)] p-5 sm:p-6">
            <div className="flex items-start justify-between gap-3 flex-wrap mb-5">
                <div>
                    <h2 className="text-base font-bold text-slate-800 tracking-tight">Tren Simpanan &amp; Pinjaman</h2>
                    <p className="text-xs text-slate-400 mt-0.5">Akumulasi bulanan {range} bulan terakhir • {labelBulan}</p>
                </div>

                <div className="flex items-center gap-2.5 flex-wrap">
                    <div
                        role="group"
                        aria-label="Rentang waktu grafik tren"
                        className="flex items-center gap-0.5 bg-slate-100/80 border border-slate-200/70 rounded-lg p-0.5"
                    >
                        {[3, 6].map((bln) => {
                            const active = range === bln;
                            return (
                                <button
                                    key={bln}
                                    type="button"
                                    onClick={() => setRange(bln)}
                                    aria-pressed={active}
                                    className={`px-2.5 py-1 rounded-md text-xs font-medium transition-colors cursor-pointer ${
                                        active
                                            ? 'bg-white text-slate-800 shadow-2xs'
                                            : 'text-slate-500 hover:text-slate-700'
                                    } ${focusRing}`}
                                >
                                    {bln} Bln
                                </button>
                            );
                        })}
                    </div>

                    <div
                        role="group"
                        aria-label="Filter seri grafik tren"
                        className="flex items-center gap-1 bg-slate-50 border border-slate-200/70 rounded-full p-0.5"
                    >
                        {trenSeries.map((s) => {
                            const isHidden = Boolean(hiddenSeries[s.key]);
                            return (
                                <button
                                    key={s.key}
                                    type="button"
                                    onClick={() => toggleSeries(s.key)}
                                    aria-pressed={!isHidden}
                                    title={isHidden ? `Tampilkan ${s.name}` : `Sembunyikan ${s.name}`}
                                    className={`inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium transition-colors cursor-pointer ${
                                        isHidden
                                            ? 'text-slate-400 hover:text-slate-600'
                                            : 'bg-white text-slate-700 shadow-2xs'
                                    } ${focusRing}`}
                                >
                                    <span
                                        className="w-2 h-2 rounded-full shrink-0"
                                        style={{ backgroundColor: isHidden ? '#cbd5e1' : s.color }}
                                    />
                                    <span>{s.name}</span>
                                </button>
                            );
                        })}
                    </div>

                    {bisaAkses('laporan.lihat') && (
                        <Link
                            href={route('laporan.show', 'arus-kas')}
                            className={`inline-flex items-center gap-1 text-xs font-semibold text-slate-500 hover:text-slate-800 transition-colors ${focusRing} rounded`}
                        >
                            Laporan Arus Kas
                            <ChevronRight size={13} aria-hidden="true" />
                        </Link>
                    )}
                </div>
            </div>

            <div className={`transition-all duration-300 ${isPrivacyActive ? 'privacy-chart' : ''}`}>
                <ResponsiveContainer width="100%" height={260}>
                    <AreaChart key={`tren-chart-${range}-${isPrivacyActive ? 'locked' : 'unlocked'}`} data={dataTampil} margin={{ top: 4, right: 4, left: -12, bottom: 0 }}>
                        <defs>
                            {trenSeries.map((s) => (
                                <linearGradient key={s.gradientId} id={s.gradientId} x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="5%" stopColor={s.color} stopOpacity={s.startOpacity} />
                                    <stop offset="95%" stopColor={s.color} stopOpacity={0} />
                                </linearGradient>
                            ))}
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
                            cursor={{ stroke: '#cbd5e1', strokeWidth: 1, strokeDasharray: '3 3' }}
                            content={<CustomTrenTooltip isPrivacyActive={isPrivacyActive} />}
                        />
                        {trenSeries.map((s) =>
                            !hiddenSeries[s.key] ? (
                                <Area
                                    key={s.key}
                                    type="monotone"
                                    dataKey={s.key}
                                    name={s.name}
                                    stroke={s.color}
                                    fillOpacity={1}
                                    fill={`url(#${s.gradientId})`}
                                    strokeWidth={2.2}
                                />
                            ) : null
                        )}
                    </AreaChart>
                </ResponsiveContainer>
            </div>
        </div>
    );
}
