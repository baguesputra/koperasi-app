import { BarChart, Bar, XAxis, YAxis, Tooltip, ResponsiveContainer } from 'recharts';
import Card from '@/Components/ui/Card';
import { formatRupiah } from '@/Utils/formatCurrency';
import { KOMPONEN_LABEL, KOMPONEN_ORDER, PCT_COL } from '@/Utils/shuLive';
import SisaDanaDetail from './SisaDanaDetail';

const WARNA = {
    cadangan: '#0F766E',
    jasa_simpanan: '#16A34A',
    jasa_transaksi: '#2563EB',
    pengurus: '#9333EA',
    pengawas: '#C026D3',
    pendidikan: '##B45309',
    sosial: '#DC2626',
    wilker: '#475569',
};

function warna(kunci) {
    return (WARNA[kunci] ?? '#94A3B8').replace('##', '#');
}

export default function RingkasanShu({ periode, persen, custom = [], preview, ringkasan, isPreview }) {
    const komponen = preview.komponen ?? {};
    const customList = preview.custom ?? custom ?? [];
    const pengurusPengawas = (komponen.pengurus ?? 0) + (komponen.pengawas ?? 0);
    const danaDibagi = (preview.pool_anggota ?? 0) + pengurusPengawas;
    const customKeys = customList.map((c) => c.slug).filter(Boolean);
    const sisaDana = ringkasan.sisa_dana ?? {
        anggota: ringkasan.belum_diserahkan ?? 0,
        pengurus: 0,
        pengawas: 0,
        total: ringkasan.belum_diserahkan ?? 0,
    };
    const dataBar = [{
        nama: 'alokasi',
        ...Object.fromEntries(KOMPONEN_ORDER.map((k) => [k, (periode.total_shu || 0) > 0 ? (komponen[k] / periode.total_shu) * 100 : 0])),
        ...Object.fromEntries(customKeys.map((s) => [s, (periode.total_shu || 0) > 0 ? ((komponen[s] ?? 0) / periode.total_shu) * 100 : 0])),
    }];
    const jumlahKomponen = KOMPONEN_ORDER.length + customKeys.length;

    return (
        <div className="space-y-4">
            <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
                <Card><Stat label="Selisih Hasil Usaha" value={formatRupiah(periode.total_shu)} hint={`Sumber: Laba Rugi YTD ${formatRupiah(periode.total_laba)}`} /></Card>
                <Card><Stat label="Simpanan + Transaksi untuk Anggota" value={formatRupiah(preview.pool_anggota)} hint={`${ringkasan.anggota} anggota • modal ${formatRupiah(preview.total_modal ?? ringkasan.total_modal)}`} /></Card>
                <Card><Stat label="Dana yang Dibagikan" value={formatRupiah(danaDibagi)} hint={`Pengurus + pengawas ${formatRupiah(pengurusPengawas)}`} /></Card>
                <Card><Stat label="Dana yang Tidak Dibagi" value={formatRupiah(preview.dana_tak_dibagi)} hint="Total SHU − dana dibagikan (termasuk tambahan)" /></Card>
                <Card><Stat label="SHU Diserahkan" value={formatRupiah(ringkasan.total_bersih)} hint={`Jumlah SHU ${formatRupiah(ringkasan.total_bruto)}, dibulatkan ke bawah ribuan`} /></Card>
                <Card><Stat label="Sudah Diserahkan" value={formatRupiah(ringkasan.terbayar)} hint={`${ringkasan.sudah_bayar} dari ${ringkasan.anggota} anggota`} /></Card>
                <Card><Stat label="Jumlah Sisa Dana yg Tidak Diserahkan" value={formatRupiah(sisaDana.total)} hint="SHU Anggota, Pengurus & Pengawas (O15)" /></Card>
                <Card><Stat label="Sisa Dana yg Tidak Dibagi" value={formatRupiah(sisaDana.anggota)} hint="SHU Anggota FLOOR 1.000 (sel J248)" /></Card>
            </div>

            <Card>
                <div className="flex items-center gap-2 mb-1">
                    <p className="text-base font-bold text-slate-800">Alokasi {jumlahKomponen} komponen</p>
                    {isPreview && (
                        <span className="text-xs font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">
                            Preview — belum disimpan
                        </span>
                    )}
                </div>
                <p className="text-sm text-slate-500 mb-3">
                    Dana yang tidak dibagi: {formatRupiah(preview.dana_tak_dibagi)} •
                    Sisa dana yg tidak di bagi (Anggota): {formatRupiah(ringkasan.belum_diserahkan)}
                </p>

                <div className="h-16" role="img" aria-label="Diagram alokasi SHU per komponen">
                    <ResponsiveContainer width="100%" height="100%">
                        <BarChart data={dataBar} layout="vertical" margin={{ top: 0, right: 0, bottom: 0, left: 0 }}>
                            <XAxis type="number" hide domain={[0, 100]} />
                            <YAxis type="category" dataKey="nama" hide />
                            <Tooltip
                                shared={false}
                                cursor={false}
                                content={({ active, payload }) => {
                                    if (!active || !payload || !payload.length) return null;
                                    const item = payload[0];
                                    const kunci = item.dataKey;
                                    const customItem = customList.find((c) => c.slug === kunci);
                                    const label = customItem?.label ?? KOMPONEN_LABEL[kunci] ?? kunci;
                                    const pct = Number(item.value ?? 0).toFixed(1);
                                    const nominal = komponen[kunci] ?? customItem?.nominal ?? 0;
                                    const warnaItem = item.color ?? warna(kunci);

                                    return (
                                        <div className="rounded-xl border border-slate-200 bg-white/95 backdrop-blur-xs px-3 py-2 shadow-lg text-xs pointer-events-none">
                                            <div className="flex items-center gap-2">
                                                <span className="inline-block w-3 h-3 rounded-xs shrink-0 shadow-2xs" style={{ backgroundColor: warnaItem }} />
                                                <span className="font-bold text-slate-800">{label}</span>
                                            </div>
                                            <div className="mt-1 flex items-center gap-2 text-slate-600">
                                                <span className="font-bold text-brand-green-dark tabular-nums">{pct}%</span>
                                                <span className="text-slate-300">•</span>
                                                <span className="font-semibold text-slate-700 tabular-nums">{formatRupiah(nominal)}</span>
                                            </div>
                                        </div>
                                    );
                                }}
                            />
                            {KOMPONEN_ORDER.map((k) => (
                                <Bar
                                    key={k}
                                    dataKey={k}
                                    stackId="alokasi"
                                    fill={warna(k)}
                                    activeBar={{ fillOpacity: 0.82 }}
                                />
                            ))}
                            {customKeys.map((s) => (
                                <Bar
                                    key={s}
                                    dataKey={s}
                                    stackId="alokasi"
                                    fill={warna(s)}
                                    activeBar={{ fillOpacity: 0.82 }}
                                />
                            ))}
                        </BarChart>
                    </ResponsiveContainer>
                </div>

                <div className="overflow-x-auto mt-2">
                    <table className="w-full text-sm">
                        <thead>
                                <tr className="text-left text-xs uppercase tracking-wide text-slate-400 border-b border-slate-100">
                                    <th className="py-2 pr-2">Komponen</th>
                                    <th className="py-2 px-2 text-right">Besaran Persentase</th>
                                    <th className="py-2 pl-2 text-right">Nilainya</th>
                                </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-50">
                            {KOMPONEN_ORDER.map((k) => (
                                <tr key={k}>
                                    <td className="py-2 pr-2">
                                        <span className="inline-block w-2.5 h-2.5 rounded-sm mr-2" style={{ backgroundColor: warna(k) }} aria-hidden="true" />
                                        {KOMPONEN_LABEL[k]}
                                    </td>
                                    <td className="py-2 px-2 text-right tabular-nums">
                                        {Math.round((persen?.[PCT_COL[k]] ?? 0) * 10000) / 100}%
                                    </td>
                                    <td className="py-2 pl-2 text-right tabular-nums font-semibold">{formatRupiah(komponen[k] ?? 0)}</td>
                                </tr>
                            ))}
                            {customList.map((c) => (
                                <tr key={c.slug}>
                                    <td className="py-2 pr-2">
                                        <span className="inline-block w-2.5 h-2.5 rounded-sm mr-2" style={{ backgroundColor: warna(c.slug) }} aria-hidden="true" />
                                        {c.label} <span className="text-xs text-slate-400">(tambahan)</span>
                                    </td>
                                    <td className="py-2 px-2 text-right tabular-nums">
                                        {Math.round((c.persen ?? 0) * 10000) / 100}%
                                    </td>
                                    <td className="py-2 pl-2 text-right tabular-nums font-semibold">{formatRupiah(c.nominal ?? komponen[c.slug] ?? 0)}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </Card>

            <SisaDanaDetail sisaDana={sisaDana} />
        </div>
    );
}

function Stat({ label, value, hint }) {
    return (
        <div>
            <p className="text-xs text-slate-500">{label}</p>
            <p className="text-lg font-bold tabular-nums text-slate-800 leading-tight">{value}</p>
            {hint && <p className="text-xs text-slate-400 mt-0.5">{hint}</p>}
        </div>
    );
}
