import { GitCommitHorizontal } from 'lucide-react';
import { DrawerSection } from '@/Components/ui/DrawerSection';
import { formatRupiah } from '@/Utils/formatCurrency';

export default function JejakNominal({ pinjaman, finalLabel = 'Final' }) {
    const baris = [
        {
            label: 'Diminta anggota',
            nilai: pinjaman.nominal_diminta,
            tenor: pinjaman.tenor_diminta,
            dot: 'bg-slate-400',
            tone: 'text-slate-800',
        },
    ];

    if (pinjaman.nominal_disetujui_bendahara) {
        baris.push({
            label: 'Usulan Bendahara',
            nilai: pinjaman.nominal_disetujui_bendahara,
            tenor: pinjaman.tenor_disetujui_bendahara,
            dot: 'bg-indigo-500',
            tone: 'text-indigo-700',
        });
    }

    if (pinjaman.nominal_disetujui) {
        baris.push({
            label: finalLabel,
            nilai: pinjaman.nominal_disetujui,
            tenor: pinjaman.tenor_disetujui,
            dot: 'bg-brand-green',
            tone: 'text-brand-green-dark',
        });
    }

    if (baris.length <= 1) return null;

    const colsClass = baris.length >= 3 ? 'sm:grid-cols-3' : 'sm:grid-cols-2';

    return (
        <DrawerSection title="Jejak Persetujuan Nominal" icon={GitCommitHorizontal} padding="none">
            <div className={`grid grid-cols-1 ${colsClass} divide-y sm:divide-y-0 sm:divide-x divide-slate-100`}>
                {baris.map((b) => (
                    <div key={b.label} className="p-3.5 sm:px-4">
                        <div className="flex items-center gap-1.5">
                            <span className={`w-2 h-2 rounded-full shrink-0 ${b.dot}`} aria-hidden="true" />
                            <span className="text-[11px] font-semibold text-slate-500">{b.label}</span>
                        </div>
                        <p className={`text-sm sm:text-base font-bold tabular-nums mt-1 ${b.tone}`}>
                            <span className="privacy-blur">{formatRupiah(b.nilai)}</span>
                        </p>
                        {b.tenor && (
                            <p className="text-xs text-slate-500 mt-0.5">{b.tenor} bulan</p>
                        )}
                    </div>
                ))}
            </div>
        </DrawerSection>
    );
}
