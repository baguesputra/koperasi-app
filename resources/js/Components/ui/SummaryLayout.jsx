import PrivacyWrapper from '@/Components/ui/PrivacyWrapper';

const ICON_TONES = {
    navy: 'bg-[#0F1E36]/8 text-[#0F1E36] border-[#0F1E36]/15',
    green: 'bg-emerald-50 text-emerald-700 border-emerald-200/70',
    amber: 'bg-amber-50 text-amber-700 border-amber-200/70',
    rose: 'bg-rose-50 text-rose-600 border-rose-200/70',
    sky: 'bg-sky-50 text-sky-700 border-sky-200/70',
    slate: 'bg-slate-100 text-slate-600 border-slate-200/80',
};

const PILL_TONES = {
    amber: 'bg-amber-50 text-amber-700 border-amber-200/80',
    sky: 'bg-sky-50 text-sky-700 border-sky-200/80',
    green: 'bg-emerald-50 text-emerald-700 border-emerald-200/80',
    rose: 'bg-rose-50 text-rose-700 border-rose-200/80',
    slate: 'bg-slate-100 text-slate-600 border-slate-200/80',
    navy: 'bg-[#0F1E36]/8 text-[#0F1E36] border-[#0F1E36]/15',
};

const ACTIVE_BAR_TONES = {
    navy: 'bg-[#0F1E36]',
    green: 'bg-emerald-600',
    amber: 'bg-amber-500',
    rose: 'bg-rose-500',
    sky: 'bg-sky-600',
};

function shouldMaskValue(value, privacy) {
    if (typeof privacy === 'boolean') return privacy;
    return typeof value === 'string' && (value.includes('Rp') || value.includes('IDR'));
}

export function UnifiedLedgerStrip({ items = [], columns = 4, className = '' }) {
    const gridColsCls =
        columns === 3
            ? 'grid-cols-1 md:grid-cols-3 divide-y md:divide-y-0 md:divide-x divide-slate-100'
            : 'grid-cols-2 lg:grid-cols-4 divide-y lg:divide-y-0 sm:divide-x divide-slate-100';

    return (
        <div
            className={`rounded-2xl border border-slate-200/85 bg-white shadow-xs overflow-hidden mb-3.5 ${className}`}
        >
            <div className={`grid ${gridColsCls}`}>
                {items.map((item) => {
                    const Icon = item.icon;
                    const isAnchor = item.variant === 'anchor';
                    const isClickable = typeof item.onClick === 'function';
                    const Comp = isClickable ? 'button' : 'div';
                    const maskVal = shouldMaskValue(item.value, item.privacy);
                    const iconCls = ICON_TONES[item.tone] || ICON_TONES.navy;
                    const barCls = ACTIVE_BAR_TONES[item.tone] || ACTIVE_BAR_TONES.navy;

                    if (isAnchor) {
                        return (
                            <Comp
                                key={item.key}
                                type={isClickable ? 'button' : undefined}
                                onClick={item.onClick}
                                className={`relative overflow-hidden bg-gradient-to-br from-[#0B1527] via-[#0F1E36] to-[#13294B] text-white px-4 py-3 text-left flex flex-col justify-between min-w-0 ${
                                    isClickable
                                        ? 'cursor-pointer hover:brightness-110 transition-all'
                                        : ''
                                }`}
                            >
                                <div className="pointer-events-none absolute -top-10 -right-10 w-28 h-28 rounded-full bg-emerald-400/12 blur-2xl" />
                                {item.active && (
                                    <span className="absolute inset-x-0 top-0 h-[2.5px] bg-emerald-400" />
                                )}

                                <div className="relative flex items-center justify-between gap-2 w-full">
                                    <span className="text-[11px] font-semibold uppercase tracking-wider text-slate-300/90 truncate">
                                        {item.label}
                                    </span>
                                    <div className="flex items-center gap-1.5 shrink-0">
                                        {item.badge}
                                        {Icon && (
                                            <span className="w-6 h-6 rounded-lg bg-white/10 border border-white/15 flex items-center justify-center text-emerald-300">
                                                <Icon size={13} />
                                            </span>
                                        )}
                                    </div>
                                </div>

                                <div className="relative mt-1.5">
                                    <div className="text-lg xl:text-xl font-extrabold tracking-tight text-white tabular-nums leading-tight whitespace-nowrap truncate">
                                        {maskVal ? <PrivacyWrapper>{item.value}</PrivacyWrapper> : item.value}
                                    </div>
                                    {item.meta && (
                                        <div className="mt-1 text-[11px] text-slate-300/80 truncate leading-tight">
                                            {item.meta}
                                        </div>
                                    )}
                                </div>
                            </Comp>
                        );
                    }

                    return (
                        <Comp
                            key={item.key}
                            type={isClickable ? 'button' : undefined}
                            onClick={item.onClick}
                            className={`relative px-4 py-3 text-left flex flex-col justify-between min-w-0 transition-colors ${
                                item.active
                                    ? 'bg-slate-50/90'
                                    : isClickable
                                      ? 'cursor-pointer hover:bg-slate-50/75'
                                      : ''
                            }`}
                        >
                            {item.active && (
                                <span className={`absolute inset-x-0 top-0 h-[2.5px] ${barCls}`} />
                            )}

                            <div className="flex items-center justify-between gap-2 w-full">
                                <span className="text-[11px] font-semibold uppercase tracking-wider text-slate-500 truncate">
                                    {item.label}
                                </span>
                                <div className="flex items-center gap-1.5 shrink-0">
                                    {item.badge}
                                    {Icon && (
                                        <span className={`w-6 h-6 rounded-lg border flex items-center justify-center shrink-0 ${iconCls}`}>
                                            <Icon size={13} />
                                        </span>
                                    )}
                                </div>
                            </div>

                            <div className="mt-1.5">
                                <div
                                    className={`text-lg xl:text-xl font-bold tabular-nums leading-tight whitespace-nowrap truncate ${
                                        item.valueTone || 'text-[#0F1E36]'
                                    }`}
                                >
                                    {maskVal ? <PrivacyWrapper>{item.value}</PrivacyWrapper> : item.value}
                                </div>

                                {item.progress && (
                                    <div className="mt-1.5 h-1 rounded-full bg-slate-100 overflow-hidden">
                                        <div
                                            className={`h-full rounded-full transition-all duration-300 ${
                                                item.progress.tone === 'danger'
                                                    ? 'bg-rose-500'
                                                    : item.progress.tone === 'warning'
                                                      ? 'bg-amber-500'
                                                      : 'bg-emerald-600'
                                            }`}
                                            style={{ width: `${Math.min(100, Math.max(0, Number(item.progress.value) || 0))}%` }}
                                        />
                                    </div>
                                )}

                                {Array.isArray(item.pills) && item.pills.length > 0 && (
                                    <div className="mt-1 flex flex-wrap gap-1">
                                        {item.pills.map((pill, idx) => (
                                            <span
                                                key={pill.label || idx}
                                                className={`inline-flex items-center px-1.5 py-0.5 rounded border text-[10px] font-semibold leading-none ${
                                                    PILL_TONES[pill.tone] || PILL_TONES.slate
                                                }`}
                                            >
                                                {pill.label}
                                            </span>
                                        ))}
                                    </div>
                                )}

                                {item.meta && (
                                    <div className="mt-1 text-[11px] text-slate-500 truncate leading-tight">
                                        {item.meta}
                                    </div>
                                )}
                            </div>
                        </Comp>
                    );
                })}
            </div>
        </div>
    );
}
