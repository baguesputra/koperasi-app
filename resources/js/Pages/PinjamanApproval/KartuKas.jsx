import { Landmark, AlertTriangle, ShieldCheck } from 'lucide-react';
import { DrawerSection } from '@/Components/ui/DrawerSection';
import PrivacyWrapper from '@/Components/ui/PrivacyWrapper';
import { formatRupiah } from '@/Utils/formatCurrency';

export default function KartuKas({ kas, nominalTahap, label = 'Nominal tahap ini' }) {
    const snapshot = kas?.saldo_bendahara ?? kas?.saldo_ketua ?? null;
    const saldoBankSekarang = kas?.saldo_sekarang ?? kas?.saldo_bank ?? 0;
    const sisaProyeksi = saldoBankSekarang - (nominalTahap || 0);
    const defisit = nominalTahap > 0 && sisaProyeksi < 0;
    const hasFooter = snapshot !== null || (kas?.sisa_ketua !== null && kas?.sisa_ketua !== undefined);

    const statusBadge = nominalTahap > 0 && (
        <span
            className={`inline-flex items-center gap-1 text-[11px] font-bold px-2 py-0.5 rounded-full border ${
                defisit
                    ? 'bg-rose-50 text-rose-700 border-rose-200/80'
                    : 'bg-brand-green-light text-brand-green-dark border-emerald-200/80'
            }`}
        >
            {defisit ? <AlertTriangle size={12} aria-hidden="true" /> : <ShieldCheck size={12} aria-hidden="true" />}
            <span>{defisit ? 'Saldo Kurang' : 'Aman'}</span>
        </span>
    );

    return (
        <DrawerSection
            title="Saldo Bank & Proyeksi Pencairan"
            icon={Landmark}
            tone={defisit ? 'rose' : 'emerald'}
            action={statusBadge}
            padding="none"
        >
            <div
                className={`grid grid-cols-1 ${
                    nominalTahap > 0 ? 'sm:grid-cols-3' : ''
                } divide-y sm:divide-y-0 sm:divide-x divide-slate-100`}
            >
                <div className="p-3.5 sm:px-4">
                    <p className="text-[11px] font-medium text-slate-500">Saldo bank saat ini</p>
                    <p className="text-sm sm:text-base font-bold text-slate-900 tabular-nums mt-0.5">
                        <PrivacyWrapper>{formatRupiah(saldoBankSekarang)}</PrivacyWrapper>
                    </p>
                </div>

                {nominalTahap > 0 && (
                    <>
                        <div className="p-3.5 sm:px-4">
                            <p className="text-[11px] font-medium text-slate-500">{label}</p>
                            <p className="text-sm sm:text-base font-bold text-slate-800 tabular-nums mt-0.5">
                                <PrivacyWrapper>{formatRupiah(nominalTahap)}</PrivacyWrapper>
                            </p>
                        </div>

                        <div className={`p-3.5 sm:px-4 ${defisit ? 'bg-rose-50/40' : 'bg-emerald-50/30'}`}>
                            <p className={`text-[11px] font-medium ${defisit ? 'text-rose-700' : 'text-emerald-800'}`}>
                                Proyeksi sisa bank
                            </p>
                            <p className={`text-sm sm:text-base font-extrabold tabular-nums mt-0.5 ${defisit ? 'text-rose-600' : 'text-brand-green-dark'}`}>
                                <PrivacyWrapper>{formatRupiah(sisaProyeksi)}</PrivacyWrapper>
                            </p>
                        </div>
                    </>
                )}
            </div>

            {hasFooter && (
                <div className="bg-[#F8FAFC] border-t border-slate-100 px-4 py-2.5 space-y-1 text-xs">
                    {snapshot !== null && (
                        <div className="flex items-center justify-between gap-4">
                            <span className="text-slate-500">Saldo bank saat snapshot tahap</span>
                            <span className="font-semibold text-slate-800 tabular-nums">
                                <PrivacyWrapper>{formatRupiah(snapshot)}</PrivacyWrapper>
                            </span>
                        </div>
                    )}
                    {kas?.sisa_ketua !== null && kas?.sisa_ketua !== undefined && (
                        <div className="flex items-center justify-between gap-4">
                            <span className="text-slate-500">Sisa saldo bank setelah cair (final)</span>
                            <span className="font-bold text-slate-800 tabular-nums">
                                <PrivacyWrapper>{formatRupiah(kas.sisa_ketua)}</PrivacyWrapper>
                            </span>
                        </div>
                    )}
                </div>
            )}
        </DrawerSection>
    );
}
