import Card from '@/Components/ui/Card';
import { formatRupiah } from '@/Utils/formatCurrency';
import { Landmark, AlertCircle } from 'lucide-react';

/**
 * Detail Sisa Dana yang Tidak Diserahkan (Tetap di Kas Koperasi)
 * Merefleksikan Cell O15 sheet Komponen SHU & Cell J247 sheet Distribusi SHU:
 * 1. Sisa Pembulatan Anggota (FLOOR 1.000)
 * 2. Sisa Alokasi Pengurus (Alokasi persentase - Pembagian riil personil)
 * 3. Sisa Alokasi Pengawas (Alokasi persentase - Pembagian riil personil)
 */
export default function SisaDanaDetail({ sisaDana, className = '' }) {
    if (!sisaDana) return null;

    const anggota = sisaDana.anggota ?? 0;
    const pengurus = sisaDana.pengurus ?? 0;
    const pengawas = sisaDana.pengawas ?? 0;
    const total = sisaDana.total ?? (anggota + pengurus + pengawas);

    return (
        <Card className={`border-emerald-200/80 bg-gradient-to-br from-white to-emerald-50/30 ${className}`}>
            <div className="flex flex-wrap items-center justify-between gap-3 mb-3 pb-3 border-b border-emerald-100">
                <div className="flex items-center gap-2.5">
                    <span className="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-700 inline-flex items-center justify-center shrink-0">
                        <Landmark size={17} />
                    </span>
                    <div>
                        <p className="text-base font-bold text-slate-800">Detail Sisa Dana yang Tidak Diserahkan</p>
                        <p className="text-xs text-slate-500">
                            Dana yang tidak dibagikan dan tetap mengendap di rekening bank/kas koperasi (memupuk modal).
                        </p>
                    </div>
                </div>
                <div className="text-right">
                    <span className="text-xs font-semibold uppercase tracking-wider text-slate-400 block">Total Sisa Dana</span>
                    <span className="text-lg font-bold text-emerald-700 tabular-nums">{formatRupiah(total)}</span>
                </div>
            </div>

            <div className="overflow-x-auto">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="text-left text-xs uppercase tracking-wide text-slate-400 border-b border-slate-100">
                            <th className="py-2 pr-3">Komponen / Sumber Sisa</th>
                            <th className="py-2 px-3 text-right">Alokasi Plafon</th>
                            <th className="py-2 px-3 text-right">Dibagikan Riil</th>
                            <th className="py-2 pl-3 text-right">Sisa Dana</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                        <tr>
                            <td className="py-2.5 pr-3">
                                <p className="font-semibold text-slate-700">Pembulatan SHU Anggota</p>
                                <p className="text-xs text-slate-400">Selisih pembulatan ke bawah kelipatan Rp 1.000 (FLOOR)</p>
                            </td>
                            <td className="py-2.5 px-3 text-right tabular-nums text-slate-600">
                                {formatRupiah((sisaDana.alokasi_anggota ?? 0) || (sisaDana.total_bruto ?? 0) || '-')}
                            </td>
                            <td className="py-2.5 px-3 text-right tabular-nums text-slate-600">
                                {formatRupiah((sisaDana.total_bersih ?? 0) || '-')}
                            </td>
                            <td className="py-2.5 pl-3 text-right tabular-nums font-bold text-emerald-700">
                                {formatRupiah(anggota)}
                            </td>
                        </tr>
                        <tr>
                            <td className="py-2.5 pr-3">
                                <p className="font-semibold text-slate-700">SHU Bagian Pengurus</p>
                                <p className="text-xs text-slate-400">Selisih alokasi persentase vs pembagian personil</p>
                            </td>
                            <td className="py-2.5 px-3 text-right tabular-nums text-slate-600">
                                {formatRupiah(sisaDana.alokasi_pengurus ?? 0)}
                            </td>
                            <td className="py-2.5 px-3 text-right tabular-nums text-slate-600">
                                {formatRupiah(sisaDana.dibagikan_pengurus ?? 0)}
                            </td>
                            <td className="py-2.5 pl-3 text-right tabular-nums font-bold text-emerald-700">
                                {formatRupiah(pengurus)}
                            </td>
                        </tr>
                        <tr>
                            <td className="py-2.5 pr-3">
                                <p className="font-semibold text-slate-700">SHU Bagian Pengawas</p>
                                <p className="text-xs text-slate-400">Selisih alokasi persentase vs pembagian personil</p>
                            </td>
                            <td className="py-2.5 px-3 text-right tabular-nums text-slate-600">
                                {formatRupiah(sisaDana.alokasi_pengawas ?? 0)}
                            </td>
                            <td className="py-2.5 px-3 text-right tabular-nums text-slate-600">
                                {formatRupiah(sisaDana.dibagikan_pengawas ?? 0)}
                            </td>
                            <td className="py-2.5 pl-3 text-right tabular-nums font-bold text-emerald-700">
                                {formatRupiah(pengawas)}
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr className="border-t-2 border-emerald-200 bg-emerald-50/40 font-bold">
                            <td colSpan={3} className="py-2.5 pr-3 text-slate-700">
                                Jumlah Sisa Dana yang Tidak Diserahkan
                            </td>
                            <td className="py-2.5 pl-3 text-right tabular-nums text-emerald-800">
                                {formatRupiah(total)}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div className="mt-3 pt-2.5 border-t border-emerald-100 flex items-start gap-2 text-xs text-slate-500">
                <AlertCircle size={15} className="text-emerald-600 shrink-0 mt-0.5" />
                <p>
                    Sesuai dengan pembukuan Excel <strong>Komponen Pembagian SHU 2024</strong> (sheet <em>Komponen SHU</em> sel O15),
                    sisa dana ini tidak dikeluarkan dari kas koperasi sehingga secara otomatis tetap berada di rekening bank koperasi.
                </p>
            </div>
        </Card>
    );
}

