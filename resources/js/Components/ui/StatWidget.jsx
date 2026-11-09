import PrivacyWrapper from '@/Components/ui/PrivacyWrapper';

export default function StatWidget({ label, value, icon: Icon, tone = 'navy', compact = false, caption, privacy }) {
    const tones = {
        navy: 'bg-slate-100 text-brand-navy border-slate-200/60',
        green: 'bg-emerald-50 text-brand-green-dark border-emerald-200/60',
        amber: 'bg-amber-50 text-amber-700 border-amber-200/60',
        rose: 'bg-rose-50 text-rose-700 border-rose-200/60',
    };

    // Otomatis proteksi jika mengandung format rupiah, kecuali di-override eksplisit
    const isRupiah = typeof value === 'string' && (value.includes('Rp') || value.includes('IDR'));
    const shouldMask = privacy !== undefined ? Boolean(privacy) : isRupiah;

    const renderedValue = shouldMask ? (
        <PrivacyWrapper>{value}</PrivacyWrapper>
    ) : (
        value
    );

    if (compact) {
        return (
            <div className="bg-white rounded-xl border border-slate-200/80 shadow-2xs p-3.5 sm:p-3 flex flex-col justify-between min-h-[92px] sm:min-h-[86px] transition-colors hover:border-slate-300/90">
                <div className="flex items-start justify-between gap-2">
                    <p className="text-xs font-semibold text-slate-500 leading-snug">{label}</p>
                    <div className={`w-7 h-7 rounded-lg border flex items-center justify-center shrink-0 ${tones[tone]}`}>
                        <Icon size={14} strokeWidth={2} />
                    </div>
                </div>
                <div className="mt-2 min-w-0">
                    <p className="text-base sm:text-lg font-bold text-slate-900 leading-tight tabular-nums tracking-tight truncate">
                        {renderedValue}
                    </p>
                    {caption && <p className="text-[11px] font-medium text-slate-500 mt-0.5 truncate">{caption}</p>}
                </div>
            </div>
        );
    }

    return (
        <div className="bg-white rounded-xl border border-slate-200/80 shadow-2xs p-4 sm:p-4.5">
            <div className={`w-9 h-9 rounded-lg border flex items-center justify-center mb-2.5 ${tones[tone]}`}>
                <Icon size={18} />
            </div>
            <p className="text-xs font-semibold text-slate-500">{label}</p>
            <p className="text-lg sm:text-xl font-bold text-slate-900 mt-0.5 tabular-nums tracking-tight">{renderedValue}</p>
            {caption && <p className="text-xs text-slate-500 mt-0.5">{caption}</p>}
        </div>
    );
}
