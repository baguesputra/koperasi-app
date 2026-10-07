import { usePage } from '@inertiajs/react';
import { formatRupiahSingkat } from '@/Utils/formatCurrency';

export default function ChipNominal({ grup, nilai, onPilih }) {
    const { chipNominal } = usePage().props;
    const daftar = chipNominal?.[grup] ?? [];

    if (daftar.length === 0) {
        return null;
    }

    return (
        <div className="flex flex-wrap gap-1.5 mt-2">
            {daftar.map((n) => (
                <button
                    key={n}
                    type="button"
                    onClick={() => onPilih(String(n))}
                    className={`px-2.5 py-1 text-xs font-bold rounded-full border transition-colors tabular-nums ${
                        String(nilai) === String(n)
                            ? 'bg-brand-navy text-white border-brand-navy'
                            : 'bg-slate-50 text-slate-600 border-slate-200 hover:border-brand-green/50 hover:text-brand-green-dark'
                    }`}
                >
                    {formatRupiahSingkat(n)}
                </button>
            ))}
        </div>
    );
}
