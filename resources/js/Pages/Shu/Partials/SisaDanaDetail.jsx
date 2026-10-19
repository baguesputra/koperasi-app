import Card from '@/Components/ui/Card';
import { formatRupiah } from '@/Utils/formatCurrency';
import { Landmark, AlertCircle } from 'lucide-react';

/**
 * Detail Jumlah Sisa Dana yang Tidak Diserahkan (Tetap di Kas Koperasi)
 * Merefleksikan pembukuan Excel Komponen Pembagian SHU 2024:
 * 1. Sheet Komponen SHU sel M14-O15: "Jumlah sisa dana yg tidak di serahkan (SHU Anggota, SHU Pengurus, SHU Pengawas)"
 * 2. Sheet Distribusi SHU sel J247-J248: "Sisa dana yg tidak di bagi" (selisih pembulatan FLOOR 1.000 anggota)
 * 3. Sheet Komponen SHU sel H15-H17: "saldo yg di alokasikan" - "saldo yg di bagikan" = "sisa dana" (Pengurus)
 * 4. Sheet Komponen SHU sel M9-M11: "saldo yg di alokasikan" - "saldo yg di bagikan" = "sisa dana" (Pengawas)
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
                        <p className="text-base font-bold text-slate-800">Jumlah sisa dana yg tidak di serahkan</p>
                        <p className="text-xs font-medium text-emerald-800">
                            SHU Anggota, SHU Pengurus, SHU Pengawas
                        </p>
                        <p className="text-[11px] text-slate-400 mt-0.5">
                            Dana yang tidak dibagikan dan tetap berada di rekening bank kas koperasi (tidak ditarik).
                        </p>
                    </div>
                </div>
                <div className="text-right">
                    <span className="text-xs font-semibold uppercase tracking-wider text-slate-400 block">
                        Jumlah Sisa Dana
                    </span>
                    <span className="text-lg font-bold text-emerald-700 tabular-nums">{formatRupiah(total)}</span>
                </div>
            </div>

            <div className="overflow-x-auto">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="text-left text-xs uppercase tracking-wide text-slate-400 border-b border-slate-100">
                            <th className="py-2 pr-3">Komponen</th>
                            <th className="py-2 px-3 text-right">Saldo yg Dialokasikan</th>
                            <th className="py-2 px-3 text-right">Saldo yg Dibagikan</th>
                            <th className="py-2 pl-3 text-right">Sisa Dana</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                        <tr>
                            <td className="py-2.5 pr-3">
                                <p className="font-semibold text-slate-700">SHU Anggota</p>
                                <p className="text-xs text-slate-400">
                                    Sisa dana yg tidak di bagi (pembulatan FLOOR 1.000 — sheet Distribusi SHU sel J247/J248)
                                </p>
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
                                <p className="font-semibold text-slate-700">SHU Pengurus Koperasi</p>
                                <p className="text-xs text-slate-400">
                                    sel H15: saldo yg di alokasikan − sel H16: saldo yg di bagikan = sel H17: sisa dana
                                </p>
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
                                <p className="font-semibold text-slate-700">SHU Pengawas</p>
                                <p className="text-xs text-slate-400">
                                    sel M9: saldo yg di alokasikan − sel M10: saldo yg di bagikan = sel M11: sisa dana
                                </p>
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
                            <td colSpan={3} className="py-2.5 pr-3">
                                <p className="text-slate-800">Jumlah sisa dana yg tidak di serahkan</p>
                                <p className="text-xs font-normal text-slate-500">
                                    Rumus Excel sel O15: O11 (Pengawas) + K17 (Pengurus) + J247 (Sisa dana yg tidak di bagi)
                                </p>
                            </td>
                            <td className="py-2.5 pl-3 text-right tabular-nums text-emerald-800 text-base">
                                {formatRupiah(total)}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div className="mt-3 pt-2.5 border-t border-emerald-100 flex items-start gap-2 text-xs text-slate-500">
                <AlertCircle size={15} className="text-emerald-600 shrink-0 mt-0.5" />
                <p>
                    Sesuai format Excel <strong>Komponen Pembagian SHU 2024</strong> (sheet <em>Komponen SHU</em> sel M14–O15 dan sheet <em>Distribusi SHU 2024</em> sel J248),
                    dana ini merupakan <strong>"Jumlah sisa dana yg tidak di serahkan"</strong> yang tidak ditarik kas keluar sehingga secara otomatis tetap berada di rekening bank koperasi.
                </p>
            </div>
        </Card>
    );
}
