import { BarChart, Bar, XAxis, YAxis, Tooltip, ResponsiveContainer } from 'recharts';
import Card from '@/Components/ui/Card';
import { formatRupiah } from '@/Utils/formatCurrency';
import { KOMPONEN_LABEL, KOMPONEN_ORDER, PCT_COL } from '@/Utils/shuLive';

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

export default function RingkasanShu({ periode, persen, preview, ringkasan, isPreview }) {
    const komponen = preview.komponen ?? {};
    const pengurusPengawas = (komponen.pengurus ?? 0) + (komponen.pengawas ?? 0);
    const danaDibagi = (preview.pool_anggota ?? 0) + pengurusPengawas;
    const dataBar = [{
        nama: 'alokasi',
        ...Object.fromEntries(KOMPONEN_ORDER.map((k) => [k, (periode.total_shu || 0) > 0 ? (komponen[k] / periode.total_shu) * 100 : 0])),
    }];

    return (
        <div className="space-y-4">
            <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
                <Card><Stat label="Total SHU" value={formatRupiah(periode.total_shu)} hint={`Laba Rugi YTD: ${formatRupiah(periode.total_laba)}`} /></Card>
                <Card><Stat label="Pool anggota" value={formatRupiah(preview.pool_anggota)} hint={`${ringkasan.anggota} anggota • modal ${formatRupiah(preview.total_modal ?? ringkasan.total_modal)}`} /></Card>
                <Card><Stat label="Dana dibagikan" value={formatRupiah(danaDibagi)} hint={`Pengurus+pengawas ${formatRupiah(pengurusPengawas)}`} /></Card>
                <Card><Stat label="Dana tak dibagi" value={formatRupiah(preview.dana_tak_dibagi)} hint="Cadangan + pendidikan + wilker" /></Card>
                <Card><Stat label="Diserahkan (floor 1000)" value={formatRupiah(ringkasan.total_bersih)} hint={`Bruto ${formatRupiah(ringkasan.total_bruto)}`} /></Card>
                <Card><Stat label="Terbayar" value={formatRupiah(ringkasan.terbayar)} hint={`${ringkasan.sudah_bayar} dari ${ringkasan.anggota} anggota`} /></Card>
            </div>

            <Card>
                <div className="flex items-center gap-2 mb-1">
                    <p className="text-base font-bold text-slate-800">Alokasi 8 komponen</p>
                    {isPreview && (
                        <span className="text-xs font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">
                            Preview — belum disimpan
                        </span>
                    )}
                </div>
                <p className="text-sm text-slate-500 mb-3">
                    Dana tak dibagi (cadangan + pendidikan + wilker): {formatRupiah(preview.dana_tak_dibagi)} •
                    Sisa pembulatan belum diserahkan: {formatRupiah(ringkasan.belum_diserahkan)}
                </p>

                <div className="h-16" role="img" aria-label="Diagram alokasi SHU per komponen">
                    <ResponsiveContainer width="100%" height="100%">
                        <BarChart data={dataBar} layout="vertical" margin={{ top: 0, right: 0, bottom: 0, left: 0 }}>
                            <XAxis type="number" hide domain={[0, 100]} />
                            <YAxis type="category" dataKey="nama" hide />
                            <Tooltip
                                formatter={(nilai, kunci) => [`${Number(nilai).toFixed(1)}% (${formatRupiah(komponen[kunci] ?? 0)})`, KOMPONEN_LABEL[kunci] ?? kunci]}
                            />
                            {KOMPONEN_ORDER.map((k) => (
                                <Bar key={k} dataKey={k} stackId="alokasi" fill={warna(k)} />
                            ))}
                        </BarChart>
                    </ResponsiveContainer>
                </div>

                <div className="overflow-x-auto mt-2">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="text-left text-xs uppercase tracking-wide text-slate-400 border-b border-slate-100">
                                <th className="py-2 pr-2">Komponen</th>
                                <th className="py-2 px-2 text-right">Persen</th>
                                <th className="py-2 pl-2 text-right">Nominal</th>
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
                        </tbody>
                    </table>
                </div>
            </Card>
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
