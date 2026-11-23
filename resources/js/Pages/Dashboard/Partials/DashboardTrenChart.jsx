import { useMemo, useState } from 'react';
import { Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import { AreaChart, Area, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';
import { formatRupiah, formatRupiahSingkat } from '@/Utils/formatCurrency';

const focusRing =
    'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-navy/25';

const trenSeries = [
    { key: 'simpanan', name: 'Simpanan', color: '#1FA24C', gradientId: 'colorSimpanan', startOpacity: 0.2 },
    { key: 'tabungan', name: 'Tabungan', color: '#0EA5E9', gradientId: 'colorTabungan', startOpacity: 0.16 },
    { key: 'pinjaman', name: 'Pinjaman Cair', color: '#0F1E36', gradientId: 'colorPinjaman', startOpacity: 0.12 },
];

function badgeStatusLdr(ldrPct) {
    if (ldrPct === null) {
        return { label: 'Belum ada data', cls: 'bg-slate-100 text-slate-600' };
    }
    if (ldrPct < 75) {
        return { label: 'Longgar', cls: 'bg-sky-50 text-sky-700' };
    }
    if (ldrPct <= 90) {
        return { label: 'Sehat', cls: 'bg-emerald-50 text-emerald-700' };
    }
    return { label: 'Ketat', cls: 'bg-amber-50 text-amber-700' };
}

function CustomTrenTooltip({ active, payload, label, isPrivacyActive }) {
    if (!active || !payload || payload.length === 0) {
        return null;
    }

    const row = payload[0]?.payload ?? {};
    const simpanan = Number(row.simpanan ?? 0);
    const tabungan = Number(row.tabungan ?? 0);
    const tabunganTarik = Number(row.tabungan_tarik ?? 0);
    const pinjaman = Number(row.pinjaman ?? 0);
    const akad = Number(row.pinjaman_count ?? 0);
    const selisihDanaCair = (simpanan + tabungan) - pinjaman;

    const mask = (v) => (isPrivacyActive ? '••••••••' : v);
    const formatSelisih = (n) => (n > 0 ? `+${formatRupiah(n)}` : n < 0 ? `-${formatRupiah(Math.abs(n))}` : formatRupiah(0));

    return (
        <div className="rounded-xl bg-white border border-slate-200/90 shadow-lg px-3.5 py-3 text-xs min-w-[235px]">
            <div className="flex items-center justify-between gap-3 pb-2 mb-2 border-b border-slate-100">
                <p className="font-bold text-slate-800">{row.bulan ?? label}</p>
                {akad > 0 && (
                    <span className="text-[11px] font-medium text-slate-500 tabular-nums">
                        {akad} akad
                    </span>
                )}
            </div>

            <div className="space-y-1.5">
                <div className="flex items-center justify-between gap-4">
                    <span className="inline-flex items-center gap-1.5 text-slate-500">
                        <span className="w-2 h-2 rounded-full bg-[#1FA24C] shrink-0" />
                        Simpanan Pokok &amp; Wajib
                    </span>
                    <span className="font-semibold text-slate-800 tabular-nums">{mask(formatRupiah(simpanan))}</span>
                </div>

                <div className="flex items-center justify-between gap-4">
                    <span className="inline-flex items-center gap-1.5 text-slate-500">
                        <span className="w-2 h-2 rounded-full bg-[#0EA5E9] shrink-0" />
                        Setor Tabungan
                    </span>
                    <span className="font-semibold text-slate-800 tabular-nums">{mask(formatRupiah(tabungan))}</span>
                </div>

                {tabunganTarik > 0 && (
                    <div className="flex items-center justify-between gap-4 pl-3.5 text-[11px] text-slate-500">
                        <span>Tarik Tabungan</span>
                        <span className="tabular-nums text-amber-700">-{mask(formatRupiah(tabunganTarik))}</span>
                    </div>
                )}

                <div className="flex items-center justify-between gap-4">
                    <span className="inline-flex items-center gap-1.5 text-slate-500">
                        <span className="w-2 h-2 rounded-full bg-[#0F1E36] shrink-0" />
                        Pinjaman Cair
                    </span>
                    <span className="font-semibold text-slate-800 tabular-nums">{mask(formatRupiah(pinjaman))}</span>
                </div>

                <div className="pt-2 border-t border-slate-100 flex items-center justify-between gap-4 font-semibold">
                    <span className="text-slate-600">Selisih Dana vs Cair</span>
                    <span className={`tabular-nums ${selisihDanaCair >= 0 ? 'text-emerald-700' : 'text-rose-600'}`}>
                        {mask(formatSelisih(selisihDanaCair))}
                    </span>
                </div>
            </div>
        </div>
    );
}

export default function DashboardTrenChart({ grafikTren = [], stats = {}, bisaAkses, isPrivacyActive, labelBulan }) {
    const [hiddenSeries, setHiddenSeries] = useState({});
    const [range, setRange] = useState(6);

    function toggleSeries(key) {
        setHiddenSeries((prev) => {
            const next = { ...prev, [key]: !prev[key] };
            const activeCount = trenSeries.filter((s) => !next[s.key]).length;
            return activeCount === 0 ? prev : next;
        });
    }

    const dataTampil = useMemo(
        () => (Array.isArray(grafikTren) ? grafikTren.slice(-range) : []),
        [grafikTren, range],
    );

    const ringkasan = useMemo(() => {
        const totalSimpanan = dataTampil.reduce((sum, r) => sum + Number(r.simpanan ?? 0), 0);
        const totalTabungan = dataTampil.reduce((sum, r) => sum + Number(r.tabungan ?? 0), 0);
        const totalPinjaman = dataTampil.reduce((sum, r) => sum + Number(r.pinjaman ?? 0), 0);
        const totalAkad = dataTampil.reduce((sum, r) => sum + Number(r.pinjaman_count ?? 0), 0);

        const simpananAktif = Number(stats?.total_simpanan_outstanding ?? 0);
        const tabunganAktif = Number(stats?.total_tabungan_anggota ?? 0);
        const danaSosialAktif = Number(stats?.saldo_dana_sosial ?? 0);
        const pinjamanAktif = Number(stats?.pinjaman_outstanding ?? 0);

        const basisLdr = simpananAktif + tabunganAktif;
        const ldrPct = basisLdr > 0 ? Math.round((pinjamanAktif / basisLdr) * 100) : null;

        const totalKomposisi = simpananAktif + tabunganAktif + danaSosialAktif;
        const porsiSimpanan = totalKomposisi > 0 ? (simpananAktif / totalKomposisi) * 100 : 0;
        const porsiTabungan = totalKomposisi > 0 ? (tabunganAktif / totalKomposisi) * 100 : 0;
        const porsiSosial = totalKomposisi > 0 ? Math.max(0, 100 - porsiSimpanan - porsiTabungan) : 0;

        return {
            danaMasuk: totalSimpanan + totalTabungan,
            totalSimpanan,
            totalTabungan,
            totalPinjaman,
            totalAkad,
            ldrPct,
            simpananAktif,
            tabunganAktif,
            danaSosialAktif,
            totalKomposisi,
            porsiSimpanan,
            porsiTabungan,
            porsiSosial,
        };
    }, [dataTampil, stats]);

    const ldrBadge = badgeStatusLdr(ringkasan.ldrPct);
    const mask = (text) => (isPrivacyActive ? '••••••••' : text);

    return (
        <div className="lg:col-span-2 rounded-2xl bg-white border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.03),0_4px_16px_-4px_rgba(15,23,42,0.04)] p-5 sm:p-6 flex flex-col justify-between">
            <div>
                <div className="flex items-start justify-between gap-3 flex-wrap mb-4">
                    <div>
                        <h2 className="text-base font-bold text-slate-800 tracking-tight">Dana Anggota &amp; Penyaluran Pinjaman</h2>
                        <p className="text-xs text-slate-400 mt-0.5">
                            Simpanan pokok/wajib &amp; tabungan vs pinjaman cair • {range} bulan terakhir{labelBulan ? ` • ${labelBulan}` : ''}
                        </p>
                    </div>

                    <div className="flex items-center gap-2.5 flex-wrap">
                        <div
                            role="group"
                            aria-label="Rentang waktu grafik dana dan pinjaman"
                            className="flex items-center gap-0.5 bg-slate-100/80 border border-slate-200/70 rounded-lg p-0.5"
                        >
                            {[3, 6, 12].map((bln) => {
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

                        {bisaAkses('laporan.lihat') && (
                            <Link
                                href={route('laporan.show', 'neraca')}
                                className={`inline-flex items-center gap-1 text-xs font-semibold text-slate-500 hover:text-slate-800 transition-colors ${focusRing} rounded`}
                            >
                                Laporan Neraca
                                <ChevronRight size={13} aria-hidden="true" />
                            </Link>
                        )}
                    </div>
                </div>

                {/* KPI Strip Analitik Dana vs Pinjaman */}
                <div className="mb-4 rounded-xl border border-slate-200/75 overflow-hidden">
                    <div className="grid grid-cols-1 sm:grid-cols-3 bg-slate-200/65 gap-px">
                        <div className="bg-slate-50/45 px-3.5 py-2.5">
                            <p className="text-[11px] font-medium text-slate-500">Dana Masuk Anggota</p>
                            <p className="text-sm sm:text-base font-bold text-[#0F1E36] tabular-nums mt-0.5 truncate">
                                {mask(formatRupiah(ringkasan.danaMasuk))}
                            </p>
                            <p className="text-[11px] text-slate-400 tabular-nums mt-0.5 truncate">
                                Simpanan {mask(formatRupiahSingkat(ringkasan.totalSimpanan))} &bull; Tabungan {mask(formatRupiahSingkat(ringkasan.totalTabungan))}
                            </p>
                        </div>

                        <div className="bg-slate-50/45 px-3.5 py-2.5">
                            <p className="text-[11px] font-medium text-slate-500">Pinjaman Disalurkan</p>
                            <p className="text-sm sm:text-base font-bold text-[#0F1E36] tabular-nums mt-0.5 truncate">
                                {mask(formatRupiah(ringkasan.totalPinjaman))}
                            </p>
                            <p className="text-[11px] text-slate-400 tabular-nums mt-0.5">
                                {ringkasan.totalAkad} akad dicairkan
                            </p>
                        </div>

                        <div className="bg-slate-50/45 px-3.5 py-2.5">
                            <div className="flex items-center justify-between gap-2">
                                <p className="text-[11px] font-medium text-slate-500">Rasio Penyaluran (LDR)</p>
                                <span className={`text-[10px] font-semibold px-1.5 py-0.5 rounded-full ${ldrBadge.cls}`}>
                                    {ldrBadge.label}
                                </span>
                            </div>
                            <p className="text-sm sm:text-base font-bold text-[#0F1E36] tabular-nums mt-0.5">
                                {ringkasan.ldrPct !== null ? `${ringkasan.ldrPct}%` : '-'}
                            </p>
                            <p className="text-[11px] text-slate-400 mt-0.5 truncate">
                                Pinjaman aktif &divide; Simpanan + Tabungan
                            </p>
                        </div>
                    </div>
                </div>

                {/* Filter Seri Legend */}
                <div className="flex items-center justify-end mb-3">
                    <div
                        role="group"
                        aria-label="Filter seri grafik dana anggota dan pinjaman"
                        className="flex items-center gap-1 bg-slate-50 border border-slate-200/70 rounded-full p-0.5 flex-wrap"
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
                </div>

                <div className={`transition-all duration-300 ${isPrivacyActive ? 'privacy-chart' : ''}`}>
                    <ResponsiveContainer width="100%" height={240}>
                        <AreaChart
                            key={`tren-chart-${range}-${isPrivacyActive ? 'locked' : 'unlocked'}`}
                            data={dataTampil}
                            margin={{ top: 4, right: 4, left: -12, bottom: 0 }}
                        >
                            <defs>
                                {trenSeries.map((s) => (
                                    <linearGradient key={s.gradientId} id={s.gradientId} x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="5%" stopColor={s.color} stopOpacity={s.startOpacity} />
                                        <stop offset="95%" stopColor={s.color} stopOpacity={0} />
                                    </linearGradient>
                                ))}
                            </defs>
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

            {/* Footer Mini-Bar Komposisi Dana Anggota Saat Ini */}
            {ringkasan.totalKomposisi > 0 && (
                <div className="mt-4 pt-3.5 border-t border-slate-100">
                    <div className="flex items-center justify-between gap-2 text-[11px] mb-1.5">
                        <span className="font-semibold text-slate-600">Komposisi Dana Anggota Saat Ini</span>
                        <span className="text-slate-400 tabular-nums">{mask(formatRupiah(ringkasan.totalKomposisi))}</span>
                    </div>
                    <div className="h-2 w-full rounded-full bg-slate-100 overflow-hidden flex">
                        {ringkasan.porsiSimpanan > 0 && (
                            <div
                                className="h-full bg-[#1FA24C]"
                                style={{ width: `${ringkasan.porsiSimpanan}%` }}
                                title={`Simpanan Aktif: ${ringkasan.porsiSimpanan.toFixed(1)}%`}
                            />
                        )}
                        {ringkasan.porsiTabungan > 0 && (
                            <div
                                className="h-full bg-[#0EA5E9]"
                                style={{ width: `${ringkasan.porsiTabungan}%` }}
                                title={`Tabungan Anggota: ${ringkasan.porsiTabungan.toFixed(1)}%`}
                            />
                        )}
                        {ringkasan.porsiSosial > 0 && (
                            <div
                                className="h-full bg-slate-400"
                                style={{ width: `${ringkasan.porsiSosial}%` }}
                                title={`Dana Sosial: ${ringkasan.porsiSosial.toFixed(1)}%`}
                            />
                        )}
                    </div>
                    <div className="flex items-center flex-wrap gap-x-4 gap-y-1 mt-2 text-[11px] text-slate-500">
                        <span className="inline-flex items-center gap-1.5">
                            <span className="w-2 h-2 rounded-xs bg-[#1FA24C] shrink-0" />
                            Simpanan ({ringkasan.porsiSimpanan.toFixed(0)}%) &bull; <strong className="text-slate-700 tabular-nums">{mask(formatRupiahSingkat(ringkasan.simpananAktif))}</strong>
                        </span>
                        <span className="inline-flex items-center gap-1.5">
                            <span className="w-2 h-2 rounded-xs bg-[#0EA5E9] shrink-0" />
                            Tabungan ({ringkasan.porsiTabungan.toFixed(0)}%) &bull; <strong className="text-slate-700 tabular-nums">{mask(formatRupiahSingkat(ringkasan.tabunganAktif))}</strong>
                        </span>
                        <span className="inline-flex items-center gap-1.5">
                            <span className="w-2 h-2 rounded-xs bg-slate-400 shrink-0" />
                            Dana Sosial ({ringkasan.porsiSosial.toFixed(0)}%) &bull; <strong className="text-slate-700 tabular-nums">{mask(formatRupiahSingkat(ringkasan.danaSosialAktif))}</strong>
                        </span>
                    </div>
                </div>
            )}
        </div>
    );
}
