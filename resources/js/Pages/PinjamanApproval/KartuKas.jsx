import { Wallet } from 'lucide-react';
import { formatRupiah } from '@/Utils/formatCurrency';

export default function KartuKas({ kas, nominalTahap, label = 'Nominal tahap ini' }) {
    const snapshot = kas?.saldo_bendahara ?? kas?.saldo_ketua ?? null;
    const sisaSnapshot = snapshot !== null && nominalTahap ? snapshot - nominalTahap : null;

    return (
        <div className="bg-white rounded-xl border border-slate-200 p-4">
            <div className="flex items-center gap-2 mb-3">
                <Wallet size={18} className="text-brand-navy" />
                <p className="text-sm font-bold text-slate-700">Kas Dana Pinjaman</p>
            </div>
            <div className="space-y-1.5 text-sm">
                <div className="flex items-center justify-between gap-4">
                    <span className="text-slate-500">Saldo kas saat ini</span>
                    <span className="font-semibold text-slate-800">{formatRupiah(kas?.saldo_sekarang ?? 0)}</span>
                </div>
                {snapshot !== null && (
                    <div className="flex items-center justify-between gap-4">
                        <span className="text-slate-500">Saldo saat snapshot tahap</span>
                        <span className="font-semibold text-slate-800">{formatRupiah(snapshot)}</span>
                    </div>
                )}
                {nominalTahap > 0 && (
                    <>
                        <div className="flex items-center justify-between gap-4">
                            <span className="text-slate-500">{label}</span>
                            <span className="font-semibold text-slate-800">{formatRupiah(nominalTahap)}</span>
                        </div>
                        <div className="flex items-center justify-between gap-4 pt-1.5 border-t border-slate-100">
                            <span className="text-slate-500">Proyeksi sisa kas</span>
                            <span className={`font-bold ${(kas?.saldo_sekarang ?? 0) - nominalTahap < 0 ? 'text-red-600' : 'text-brand-green-dark'}`}>
                                {formatRupiah((kas?.saldo_sekarang ?? 0) - nominalTahap)}
                            </span>
                        </div>
                    </>
                )}
                {kas?.sisa_ketua !== null && kas?.sisa_ketua !== undefined && (
                    <div className="flex items-center justify-between gap-4 pt-1.5 border-t border-slate-100">
                        <span className="text-slate-500">Sisa kas setelah cair (final)</span>
                        <span className="font-bold text-slate-800">{formatRupiah(kas.sisa_ketua)}</span>
                    </div>
                )}
                {sisaSnapshot !== null && kas?.sisa_ketua === null && (
                    <p className="text-xs text-slate-400">Rincian snapshot tahap tersimpan dan tetap tampil setelah pencairan final.</p>
                )}
            </div>
        </div>
    );
}
