import { useMemo, useState } from 'react';
import { Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import {
    ComposedChart, Bar, Line, ReferenceLine,
    XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer,
} from 'recharts';
import { formatRupiah, formatRupiahSingkat } from '@/Utils/formatCurrency';

const focusRing =
    'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-navy/25';

const kasSeries = [
    { key: 'masuk', name: 'Kas Masuk', color: '#1FA24C', type: 'bar' },
    { key: 'keluar', name: 'Kas Keluar', color: '#64748B', type: 'bar' },
    { key: 'net', name: 'Arus Kas Bersih', color: '#0F1E36', type: 'line' },
];

const MASUK_BREAKDOWN = [
    { key: 'angsuran', label: 'Angsuran Pinjaman', dot: '#1FA24C' },
    { key: 'simpanan', label: 'Simpanan Pokok & Wajib', dot: '#10B981' },
    { key: 'setor_tabungan', label: 'Setor Tabungan', dot: '#0EA5E9' },
    { key: 'dana_sosial', label: 'Iuran Dana Sosial', dot: '#6366F1' },
    { key: 'topup', label: 'Topup & Lainnya', dot: '#0F1E36' },
];

const KELUAR_BREAKDOWN = [
    { key: 'pencairan', label: 'Pencairan Pinjaman', dot: '#475569' },
    { key: 'tarik_tabungan', label: 'Tarik Tabungan', dot: '#F59E0B' },
    { key: 'pengeluaran', label: 'Beban & Santunan', dot: '#EF4444' },
    { key: 'resign_shu', label: 'Resign & Pembagian SHU', dot: '#8B5CF6' },
];

function formatSumbuKas(nilai) {
    const n = Number(nilai ?? 0);
    if (!Number.isFinite(n) || n === 0) {
        return '0';
    }
    const sign = n < 0 ? '-' : '';
    return `${sign}${formatRupiahSingkat(Math.abs(n))}`;
}

function formatSignedRupiah(nilai) {
    const n = Number(nilai ?? 0);
    if (n > 0) return `+${formatRupiah(n)}`;
    if (n < 0) return `-${formatRupiah(Math.abs(n))}`;
    return formatRupiah(0);
}

function CustomKasTooltip({ active, payload, label, isPrivacyActive }) {
    if (!active || !payload || payload.length === 0) {
        return null;
    }

    const row = payload[0]?.payload ?? {};
    const masuk = Number(row.masuk ?? 0);
    const keluar = Number(row.keluar ?? 0);
    const net = Number(row.net ?? (masuk - keluar));
    const surplus = net >= 0;

    const rincianMasuk = MASUK_BREAKDOWN.filter((item) => Number(row[item.key] ?? 0) > 0);
    const rincianKeluar = KELUAR_BREAKDOWN.filter((item) => Number(row[item.key] ?? 0) > 0);
    const mask = (val) => (isPrivacyActive ? '••••••••' : val);

    return (
        <div className="rounded-xl bg-white border border-slate-200/90 shadow-lg px-3.5 py-3 text-xs min-w-[235px]">
            <div className="flex items-center justify-between gap-3 pb-2 mb-2 border-b border-slate-100">
                <p className="font-bold text-slate-800">{row.bulan ?? label}</p>
                <span
                    className={`inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold ${
                        surplus ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'
                    }`}
                >
                    {surplus ? 'Surplus' : 'Defisit'}
                </span>
            </div>

            <div className="space-y-2">
                <div>
                    <div className="flex items-center justify-between gap-4 font-semibold text-slate-700">
                        <span className="inline-flex items-center gap-1.5">
                            <span className="w-2 h-2 rounded-xs bg-[#1FA24C] shrink-0" />
                            Kas Masuk
                        </span>
                        <span className="tabular-nums text-emerald-700">{mask(formatRupiah(masuk))}</span>
                    </div>
                    {rincianMasuk.length > 0 && (
                        <div className="mt-1 pl-3.5 space-y-0.5 text-[11px] text-slate-500">
                            {rincianMasuk.map((item) => (
                                <div key={item.key} className="flex items-center justify-between gap-3">
                                    <span>{item.label}</span>
                                    <span className="tabular-nums text-slate-600">{mask(formatRupiah(row[item.key]))}</span>
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                <div>
                    <div className="flex items-center justify-between gap-4 font-semibold text-slate-700">
                        <span className="inline-flex items-center gap-1.5">
                            <span className="w-2 h-2 rounded-xs bg-[#64748B] shrink-0" />
                            Kas Keluar
                        </span>
                        <span className="tabular-nums text-slate-800">{mask(formatRupiah(keluar))}</span>
                    </div>
                    {rincianKeluar.length > 0 && (
                        <div className="mt-1 pl-3.5 space-y-0.5 text-[11px] text-slate-500">
                            {rincianKeluar.map((item) => (
                                <div key={item.key} className="flex items-center justify-between gap-3">
                                    <span>{item.label}</span>
                                    <span className="tabular-nums text-slate-600">{mask(formatRupiah(row[item.key]))}</span>
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                <div className="pt-2 border-t border-slate-100 flex items-center justify-between gap-4 font-bold">
                    <span className="text-slate-700">Arus Kas Bersih</span>
                    <span className={`tabular-nums ${surplus ? 'text-emerald-700' : 'text-rose-600'}`}>
                        {mask(formatSignedRupiah(net))}
                    </span>
                </div>
            </div>
        </div>
    );
}

export default function DashboardKasChart({ grafikKas = [], bisaAkses, isPrivacyActive, labelBulan }) {
    const [hiddenSeries, setHiddenSeries] = useState({});
    const [range, setRange] = useState(6);

    function toggleSeries(key) {
        setHiddenSeries((prev) => {
            const next = { ...prev, [key]: !prev[key] };
            const activeCount = kasSeries.filter((s) => !next[s.key]).length;
            return activeCount === 0 ? prev : next;
        });
    }

    const dataTampil = useMemo(
        () => (Array.isArray(grafikKas) ? grafikKas.slice(-range) : []),
        [grafikKas, range],
    );

    const ringkasan = useMemo(() => {
        const totalMasuk = dataTampil.reduce((sum, r) => sum + Number(r.masuk ?? 0), 0);
        const totalKeluar = dataTampil.reduce((sum, r) => sum + Number(r.keluar ?? 0), 0);
        const netTotal = totalMasuk - totalKeluar;
        const rataNet = dataTampil.length > 0 ? netTotal / dataTampil.length : 0;

        const terakhir = dataTampil[dataTampil.length - 1];
        const sebelumnya = dataTampil[dataTampil.length - 2];
        const masukTerakhir = Number(terakhir?.masuk ?? 0);
        const masukSebelumnya = Number(sebelumnya?.masuk ?? 0);
        const deltaMasukPct = masukSebelumnya > 0
            ? Math.round(((masukTerakhir - masukSebelumnya) / masukSebelumnya) * 100)
            : null;

        return { totalMasuk, totalKeluar, netTotal, rataNet, deltaMasukPct };
    }, [dataTampil]);

    const mask = (text) => (isPrivacyActive ? '••••••••' : text);

    return (
        <div className="rounded-2xl bg-white border border-slate-200/85 shadow-[0_1px_3px_rgba(15,23,42,0.04),0_6px_18px_-6px_rgba(15,23,42,0.06)] p-5 sm:p-6">
            <div className="flex items-start justify-between gap-3 flex-wrap mb-4">
                <div>
                    <h2 className="text-base font-bold text-slate-800 tracking-tight">Arus Kas Operasional Koperasi</h2>
                    <p className="text-xs text-slate-400 mt-0.5">
                        Kas masuk riil vs kas keluar riil &amp; arus bersih (tanpa transfer internal kas) &bull; {range} bulan terakhir{labelBulan ? ` • ${labelBulan}` : ''}
                    </p>
                </div>

                <div className="flex items-center gap-3 flex-wrap">
                    <div
                        role="group"
                        aria-label="Rentang waktu grafik arus kas"
                        className="flex items-center gap-0.5 bg-white border border-slate-200/85 rounded-lg p-0.5"
                    >
                        {[3, 6, 12].map((bln) => {
                            const active = range === bln;
                            return (
                                <button
                                    key={bln}
                                    type="button"
                                    onClick={() => setRange(bln)}
                                    aria-pressed={active}
                                    className={`px-2.5 py-1 rounded-md text-xs transition-colors cursor-pointer ${
                                        active
                                            ? 'bg-[#0F1E36] text-white font-semibold'
                                            : 'text-slate-500 font-medium hover:text-[#0F1E36]'
                                    } ${focusRing}`}
                                >
                                    {bln} Bln
                                </button>
                            );
                        })}
                    </div>

                    {bisaAkses('laporan.lihat') && (
                        <Link
                            href={route('laporan.show', 'arus-kas')}
                            className={`group/link inline-flex items-center gap-0.5 text-xs font-semibold text-slate-500 hover:text-[#0F1E36] transition-colors ${focusRing} rounded`}
                        >
                            Laporan Arus Kas
                            <ChevronRight
                                size={13}
                                aria-hidden="true"
                                className="group-hover/link:translate-x-0.5 transition-transform duration-150"
                            />
                        </Link>
                    )}
                </div>
            </div>

            {/* KPI Strip Analitik Arus Kas */}
            <div className="mb-4 border-y border-slate-100 py-3.5 grid grid-cols-2 lg:grid-cols-4 gap-x-4 gap-y-3 lg:gap-x-0 lg:divide-x lg:divide-slate-100">
                <div className="lg:px-4 lg:first:pl-0 lg:last:pr-0 min-w-0">
                    <div className="flex items-center justify-between gap-2">
                        <p className="text-[11px] font-medium text-slate-500">Total Kas Masuk</p>
                        {ringkasan.deltaMasukPct !== null && !isPrivacyActive && (
                            <span
                                className={`text-[10px] font-semibold tabular-nums px-1.5 py-0.5 rounded-full ${
                                    ringkasan.deltaMasukPct >= 0
                                        ? 'bg-emerald-50 text-emerald-700'
                                        : 'bg-rose-50 text-rose-700'
                                }`}
                            >
                                {ringkasan.deltaMasukPct >= 0 ? '+' : ''}{ringkasan.deltaMasukPct}% bln ini
                            </span>
                        )}
                    </div>
                    <p className="text-sm sm:text-base font-bold text-[#0F1E36] tabular-nums mt-1 truncate">
                        {mask(formatRupiah(ringkasan.totalMasuk))}
                    </p>
                </div>

                <div className="lg:px-4 lg:first:pl-0 lg:last:pr-0 min-w-0">
                    <p className="text-[11px] font-medium text-slate-500">Total Kas Keluar</p>
                    <p className="text-sm sm:text-base font-bold text-[#0F1E36] tabular-nums mt-1 truncate">
                        {mask(formatRupiah(ringkasan.totalKeluar))}
                    </p>
                </div>

                <div className="lg:px-4 lg:first:pl-0 lg:last:pr-0 min-w-0">
                    <div className="flex items-center justify-between gap-2">
                        <p className="text-[11px] font-medium text-slate-500">Arus Kas Bersih</p>
                        <span
                            className={`text-[10px] font-semibold px-1.5 py-0.5 rounded-full ${
                                ringkasan.netTotal >= 0
                                    ? 'bg-emerald-50 text-emerald-700'
                                    : 'bg-rose-50 text-rose-700'
                            }`}
                        >
                            {ringkasan.netTotal >= 0 ? 'Surplus' : 'Defisit'}
                        </span>
                    </div>
                    <p
                        className={`text-sm sm:text-base font-bold tabular-nums mt-1 truncate ${
                            isPrivacyActive
                                ? 'text-[#0F1E36]'
                                : ringkasan.netTotal >= 0
                                    ? 'text-emerald-700'
                                    : 'text-rose-600'
                        }`}
                    >
                        {mask(formatSignedRupiah(ringkasan.netTotal))}
                    </p>
                </div>

                <div className="lg:px-4 lg:first:pl-0 lg:last:pr-0 min-w-0">
                    <p className="text-[11px] font-medium text-slate-500">Rata-rata Net / Bulan</p>
                    <p
                        className={`text-sm sm:text-base font-bold tabular-nums mt-1 truncate ${
                            isPrivacyActive
                                ? 'text-[#0F1E36]'
                                : ringkasan.rataNet >= 0
                                    ? 'text-emerald-700'
                                    : 'text-rose-600'
                        }`}
                    >
                        {mask(formatSignedRupiah(Math.round(ringkasan.rataNet)))}
                    </p>
                </div>
            </div>

            {/* Filter Seri Legend */}
            <div
                role="group"
                aria-label="Filter seri grafik arus kas"
                className="flex items-center justify-end gap-4 mb-3 flex-wrap"
            >
                {kasSeries.map((s) => {
                    const isHidden = Boolean(hiddenSeries[s.key]);
                    return (
                        <button
                            key={s.key}
                            type="button"
                            onClick={() => toggleSeries(s.key)}
                            aria-pressed={!isHidden}
                            title={isHidden ? `Tampilkan ${s.name}` : `Sembunyikan ${s.name}`}
                            className={`inline-flex items-center gap-1.5 text-xs font-medium transition-colors cursor-pointer rounded ${
                                isHidden
                                    ? 'text-slate-300 line-through'
                                    : 'text-slate-600 hover:text-[#0F1E36]'
                            } ${focusRing}`}
                        >
                            <span
                                className={s.type === 'line' ? 'w-3 h-0.5 rounded-full shrink-0' : 'w-2 h-2 rounded-xs shrink-0'}
                                style={{ backgroundColor: isHidden ? '#cbd5e1' : s.color }}
                            />
                            <span>{s.name}</span>
                        </button>
                    );
                })}
            </div>

            <div className={`transition-all duration-300 ${isPrivacyActive ? 'privacy-chart' : ''}`}>
                <ResponsiveContainer width="100%" height={280}>
                    <ComposedChart
                        key={`kas-chart-${range}-${isPrivacyActive ? 'locked' : 'unlocked'}`}
                        data={dataTampil}
                        margin={{ top: 6, right: 6, left: -10, bottom: 0 }}
                        barGap={4}
                        barCategoryGap={range === 12 ? '20%' : '26%'}
                    >
                        <CartesianGrid strokeDasharray="3 3" stroke="#f1f5f9" vertical={false} />
                        <XAxis
                            dataKey={range === 12 ? 'bulan_singkat' : 'bulan'}
                            tick={{ fontSize: 12, fill: '#64748b' }}
                            axisLine={false}
                            tickLine={false}
                            dy={6}
                        />
                        <YAxis
                            tick={{ fontSize: 12, fill: '#94a3b8' }}
                            axisLine={false}
                            tickLine={false}
                            tickFormatter={isPrivacyActive ? () => '••••' : formatSumbuKas}
                        />
                        <Tooltip
                            cursor={{ fill: 'rgba(15, 30, 54, 0.04)' }}
                            content={<CustomKasTooltip isPrivacyActive={isPrivacyActive} />}
                        />
                        {!hiddenSeries.net && (
                            <ReferenceLine y={0} stroke="#cbd5e1" strokeDasharray="4 4" />
                        )}
                        {!hiddenSeries.masuk && (
                            <Bar
                                dataKey="masuk"
                                name="Kas Masuk"
                                fill="#1FA24C"
                                maxBarSize={24}
                                radius={[4, 4, 0, 0]}
                            />
                        )}
                        {!hiddenSeries.keluar && (
                            <Bar
                                dataKey="keluar"
                                name="Kas Keluar"
                                fill="#64748B"
                                maxBarSize={24}
                                radius={[4, 4, 0, 0]}
                            />
                        )}
                        {!hiddenSeries.net && (
                            <Line
                                type="monotone"
                                dataKey="net"
                                name="Arus Kas Bersih"
                                stroke="#0F1E36"
                                strokeWidth={2.2}
                                dot={{ r: 3.5, fill: '#0F1E36', stroke: '#ffffff', strokeWidth: 1.5 }}
                                activeDot={{ r: 5, fill: '#0F1E36', stroke: '#ffffff', strokeWidth: 2 }}
                            />
                        )}
                    </ComposedChart>
                </ResponsiveContainer>
            </div>
        </div>
    );
}
