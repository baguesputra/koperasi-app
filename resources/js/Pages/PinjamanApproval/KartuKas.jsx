import { Landmark, AlertTriangle } from 'lucide-react';
import PrivacyWrapper from '@/Components/ui/PrivacyWrapper';
import { formatRupiah } from '@/Utils/formatCurrency';

export default function KartuKas({ kas, nominalTahap, label = 'Nominal tahap ini' }) {
    const snapshot = kas?.saldo_bendahara ?? kas?.saldo_ketua ?? null;
    const saldoBankSekarang = kas?.saldo_sekarang ?? kas?.saldo_bank ?? 0;
    const sisaProyeksi = saldoBankSekarang - (nominalTahap || 0);
    const defisit = nominalTahap > 0 && sisaProyeksi < 0;

    return (
        <div className={`rounded-xl border p-4 transition-colors ${defisit ? 'bg-red-50/40 border-red-200' : 'bg-white border-slate-200'}`}>
            <div className="flex items-center justify-between mb-3">
                <div className="flex items-center gap-2">
                    <Landmark size={18} className={defisit ? 'text-red-600' : 'text-brand-navy'} />
                    <p className="text-sm font-bold text-slate-800">Saldo Bank & Proyeksi Pencairan</p>
                </div>
                {defisit && (
                    <span className="inline-flex items-center gap-1 text-[11px] font-bold text-red-600 bg-red-100 px-2 py-0.5 rounded-full">
                        <AlertTriangle size={12} />
                        Saldo Kurang
                    </span>
                )}
            </div>

            <div className="space-y-1.5 text-sm">
                <div className="flex items-center justify-between gap-4">
                    <span className="text-slate-500">Saldo bank saat ini</span>
                    <span className="font-semibold text-slate-800">
                        <PrivacyWrapper>{formatRupiah(saldoBankSekarang)}</PrivacyWrapper>
                    </span>
                </div>

                {snapshot !== null && (
                    <div className="flex items-center justify-between gap-4">
                        <span className="text-slate-500">Saldo bank saat snapshot tahap</span>
                        <span className="font-semibold text-slate-800">
                            <PrivacyWrapper>{formatRupiah(snapshot)}</PrivacyWrapper>
                        </span>
                    </div>
                )}

                {nominalTahap > 0 && (
                    <>
                        <div className="flex items-center justify-between gap-4">
                            <span className="text-slate-500">{label}</span>
                            <span className="font-semibold text-slate-800">
                                <PrivacyWrapper>{formatRupiah(nominalTahap)}</PrivacyWrapper>
                            </span>
                        </div>
                        <div className="flex items-center justify-between gap-4 pt-1.5 border-t border-slate-100">
                            <span className="text-slate-500">Proyeksi sisa saldo bank</span>
                            <span className={`font-bold ${defisit ? 'text-red-600' : 'text-brand-green-dark'}`}>
                                <PrivacyWrapper>{formatRupiah(sisaProyeksi)}</PrivacyWrapper>
                            </span>
                        </div>
                    </>
                )}

                {kas?.sisa_ketua !== null && kas?.sisa_ketua !== undefined && (
                    <div className="flex items-center justify-between gap-4 pt-1.5 border-t border-slate-100">
                        <span className="text-slate-500">Sisa saldo bank setelah cair (final)</span>
                        <span className="font-bold text-slate-800">
                            <PrivacyWrapper>{formatRupiah(kas.sisa_ketua)}</PrivacyWrapper>
                        </span>
                    </div>
                )}
            </div>
        </div>
    );
}
