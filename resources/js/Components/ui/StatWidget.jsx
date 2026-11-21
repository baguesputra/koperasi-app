import { Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import PrivacyWrapper from '@/Components/ui/PrivacyWrapper';

export default function StatWidget({
    label,
    value,
    icon: Icon,
    tone = 'navy',
    compact = false,
    caption,
    privacy,
    href = null,
}) {
    const tones = {
        navy: 'bg-slate-100/80 text-brand-navy border-slate-200/80',
        green: 'bg-emerald-50/80 text-brand-green-dark border-emerald-200/60',
        amber: 'bg-amber-50/80 text-amber-700 border-amber-200/60',
        rose: 'bg-rose-50/80 text-rose-700 border-rose-200/60',
    };

    // Otomatis proteksi jika mengandung format rupiah, kecuali di-override eksplisit
    const isRupiah = typeof value === 'string' && (value.includes('Rp') || value.includes('IDR'));
    const shouldMask = privacy !== undefined ? Boolean(privacy) : isRupiah;

    const renderedValue = shouldMask ? (
        <PrivacyWrapper>{value}</PrivacyWrapper>
    ) : (
        value
    );

    const Wrapper = href ? Link : 'div';
    const wrapperProps = href ? { href } : {};
    const interactiveClass = href
        ? 'group cursor-pointer transition-colors duration-150 hover:bg-slate-50/60 hover:border-slate-300/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-navy/20'
        : '';

    if (compact) {
        return (
            <Wrapper
                {...wrapperProps}
                className={`bg-white rounded-xl border border-slate-200/80 shadow-[0_1px_3px_rgba(15,30,54,0.04)] p-3.5 sm:p-4 flex flex-col justify-between min-h-[92px] sm:min-h-[88px] ${interactiveClass}`}
            >
                <div className="flex items-start justify-between gap-2">
                    <p className="text-xs font-medium text-slate-500 group-hover:text-slate-700 transition-colors leading-snug">
                        {label}
                    </p>
                    <div className={`w-7 h-7 rounded-lg border flex items-center justify-center shrink-0 ${tones[tone]}`}>
                        <Icon size={14} strokeWidth={2} />
                    </div>
                </div>
                <div className="mt-2 min-w-0">
                    <p className="text-base sm:text-lg font-bold text-slate-900 leading-tight tabular-nums tracking-tight truncate">
                        {renderedValue}
                    </p>
                    {caption && (
                        <div className="flex items-center justify-between gap-1 mt-0.5">
                            <p className="text-[11px] text-slate-400 group-hover:text-slate-500 transition-colors truncate">
                                {caption}
                            </p>
                            {href && (
                                <ChevronRight
                                    size={12}
                                    className="text-slate-300 opacity-0 group-hover:opacity-100 group-hover:text-slate-500 transition-opacity shrink-0"
                                />
                            )}
                        </div>
                    )}
                </div>
            </Wrapper>
        );
    }

    return (
        <Wrapper
            {...wrapperProps}
            className={`bg-white rounded-xl border border-slate-200/80 shadow-[0_1px_3px_rgba(15,30,54,0.04)] p-4 sm:p-4.5 block ${interactiveClass}`}
        >
            <div className={`w-9 h-9 rounded-lg border flex items-center justify-center mb-2.5 ${tones[tone]}`}>
                <Icon size={18} />
            </div>
            <p className="text-xs font-medium text-slate-500">{label}</p>
            <p className="text-lg sm:text-xl font-bold text-slate-900 mt-0.5 tabular-nums tracking-tight">{renderedValue}</p>
            {caption && <p className="text-xs text-slate-400 mt-0.5">{caption}</p>}
        </Wrapper>
    );
}
