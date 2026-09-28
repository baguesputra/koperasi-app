import { formatRupiah } from '@/Utils/formatCurrency';

export default function JejakNominal({ pinjaman, finalLabel = 'Final' }) {
    const baris = [
        { label: 'Diminta anggota', nilai: pinjaman.nominal_diminta, tenor: pinjaman.tenor_diminta, tone: 'text-slate-800' },
    ];

    if (pinjaman.nominal_disetujui_bendahara) {
        baris.push({
            label: 'Usulan Bendahara',
            nilai: pinjaman.nominal_disetujui_bendahara,
            tenor: pinjaman.tenor_disetujui_bendahara,
            tone: 'text-blue-700',
        });
    }

    if (pinjaman.nominal_disetujui) {
        baris.push({
            label: finalLabel,
            nilai: pinjaman.nominal_disetujui,
            tenor: pinjaman.tenor_disetujui,
            tone: 'text-brand-green-dark',
        });
    }

    if (baris.length <= 1) return null;

    return (
        <div className="bg-slate-50 rounded-xl p-4 border border-slate-200">
            <p className="text-sm font-bold text-slate-700 mb-2">Jejak Persetujuan Nominal</p>
            <div className="space-y-1.5 text-sm">
                {baris.map((b) => (
                    <div key={b.label} className="flex items-center justify-between gap-4">
                        <span className="text-slate-500">{b.label}</span>
                        <span className={`font-semibold ${b.tone}`}>
                            {formatRupiah(b.nilai)}{b.tenor ? ` • ${b.tenor} bln` : ''}
                        </span>
                    </div>
                ))}
            </div>
        </div>
    );
}
