const TONES = {
    amber: 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-200/80',
    emerald: 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-200/80',
    slate: 'bg-slate-100 text-slate-600 ring-1 ring-inset ring-slate-200/80',
};

const ACTIVE_SIDEBAR_TONE = 'bg-white/15 text-white ring-1 ring-inset ring-white/20';

/**
 * Counter pill modern untuk antrean menunggu di Sidebar dan tab halaman approval.
 */
export default function CountBadge({
    count = 0,
    tone = 'amber',
    active = false,
    context = 'sidebar',
    max = 99,
    className = '',
}) {
    if (!count || count <= 0) {
        return null;
    }

    const display = count > max ? `${max}+` : count;
    const toneClass =
        context === 'sidebar' && active
            ? ACTIVE_SIDEBAR_TONE
            : (TONES[tone] ?? TONES.amber);

    return (
        <span
            className={`inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full text-[11px] font-bold leading-none tabular-nums shrink-0 transition-colors duration-150 ${toneClass} ${className}`}
        >
            {display}
        </span>
    );
}

/**
 * Indikator titik ringkas saat sidebar dalam mode ciut (collapsed).
 */
export function CountDot({ active = false, tone = 'amber', className = '' }) {
    const dotBg = tone === 'emerald' ? 'bg-brand-green' : 'bg-amber-500';
    const ringColor = active ? 'ring-brand-navy' : 'ring-white';

    return (
        <span
            aria-hidden="true"
            className={`inline-block w-2 h-2 rounded-full ring-2 ${ringColor} ${dotBg} ${className}`}
        />
    );
}
