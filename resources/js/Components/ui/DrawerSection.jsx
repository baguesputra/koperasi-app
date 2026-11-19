import { forwardRef } from 'react';

export function DrawerBody({ children, className = '' }) {
    return (
        <div className={`flex-1 min-h-0 overflow-y-auto px-4 py-4 sm:px-5 sm:py-4 space-y-3.5 ${className}`}>
            {children}
        </div>
    );
}

export const drawerFooterButtonClass = 'min-h-[44px] sm:min-h-[40px] px-4 sm:px-5 py-2 text-sm font-semibold';

export function DrawerFooter({ children, className = '' }) {
    return (
        <div
            className={`shrink-0 bg-white border-t border-slate-200/80 px-4 py-3 pb-[calc(0.75rem+env(safe-area-inset-bottom))] sm:px-5 sm:py-3.5 shadow-[0_-4px_16px_-4px_rgba(15,30,54,0.06)] ${className}`}
        >
            {children}
        </div>
    );
}

export function DrawerHero({ children, className = '' }) {
    return (
        <div
            className={`rounded-2xl bg-gradient-to-br from-[#0B1527] via-[#0F1E36] to-[#0A2E1A] p-4 sm:p-5 text-white ring-1 ring-inset ring-white/10 shadow-[0_6px_20px_-6px_rgba(15,30,54,0.35)] ${className}`}
        >
            {children}
        </div>
    );
}

const sectionTones = {
    default: {
        card: 'border-slate-200/80',
        header: 'border-slate-100',
        icon: 'bg-gradient-to-br from-brand-navy/10 to-sky-100/80 text-brand-navy',
    },
    emerald: {
        card: 'border-emerald-200/80',
        header: 'bg-emerald-50/45 border-emerald-100/80',
        icon: 'bg-brand-green-light text-brand-green-dark',
    },
    amber: {
        card: 'border-amber-200/85',
        header: 'bg-amber-50/50 border-amber-100/80',
        icon: 'bg-amber-100/70 text-amber-700',
    },
    sky: {
        card: 'border-sky-200/85',
        header: 'bg-sky-50/50 border-sky-100/80',
        icon: 'bg-sky-100/70 text-sky-700',
    },
    rose: {
        card: 'border-rose-200/85',
        header: 'bg-rose-50/50 border-rose-100/80',
        icon: 'bg-rose-100/70 text-rose-700',
    },
};

export const DrawerSection = forwardRef(function DrawerSection(
    {
        title,
        description,
        icon: Icon,
        action,
        tone = 'default',
        padding = 'normal',
        children,
        className = '',
    },
    ref
) {
    const toneStyle = sectionTones[tone] ?? sectionTones.default;
    const paddingClass =
        padding === 'none' ? '' : padding === 'sm' ? 'p-3 sm:p-3.5' : 'p-3.5 sm:p-4';

    return (
        <section
            ref={ref}
            className={`rounded-xl bg-white border shadow-[0_1px_3px_rgba(15,30,54,0.04)] overflow-hidden ${toneStyle.card} ${className}`}
        >
            {(title || action) && (
                <div className={`flex items-center justify-between gap-2 px-4 py-2.5 sm:py-3 border-b ${toneStyle.header}`}>
                    <div className="flex items-center gap-2.5 min-w-0">
                        {Icon && (
                            <span className={`w-7 h-7 rounded-lg inline-flex items-center justify-center shrink-0 ${toneStyle.icon}`}>
                                <Icon size={15} aria-hidden="true" />
                            </span>
                        )}
                        <div className="min-w-0">
                            {title && (
                                <h3 className="text-xs font-bold uppercase tracking-wider text-slate-700 truncate">
                                    {title}
                                </h3>
                            )}
                            {description && (
                                <p className="text-xs text-slate-500 font-normal normal-case tracking-normal mt-0.5">
                                    {description}
                                </p>
                            )}
                        </div>
                    </div>
                    {action && <div className="shrink-0">{action}</div>}
                </div>
            )}
            <div className={paddingClass}>{children}</div>
        </section>
    );
});

const fieldGridCols = {
    2: 'grid-cols-2',
    3: 'grid-cols-2 sm:grid-cols-3',
    4: 'grid-cols-2 sm:grid-cols-4',
};

export function DrawerFieldGrid({ cols = 2, children, className = '' }) {
    return (
        <div className={`grid ${fieldGridCols[cols] ?? fieldGridCols[2]} gap-x-4 gap-y-3 ${className}`}>
            {children}
        </div>
    );
}

const fieldColSpans = {
    1: '',
    2: 'col-span-2',
    3: 'col-span-2 sm:col-span-3',
    4: 'col-span-2 sm:col-span-4',
};

export function DrawerField({
    label,
    value,
    sub,
    mono = false,
    privacy = false,
    colSpan = 1,
    className = '',
}) {
    const displayValue = value === null || value === undefined || value === '' ? '-' : value;

    return (
        <div className={`min-w-0 ${fieldColSpans[colSpan] ?? ''} ${className}`}>
            <p className="text-[11px] font-medium text-slate-500 truncate">{label}</p>
            <p
                className={`text-sm font-semibold text-slate-800 mt-0.5 break-words ${mono ? 'font-mono' : ''} ${
                    privacy ? 'privacy-blur tabular-nums' : ''
                }`}
            >
                {displayValue}
            </p>
            {sub && <p className="text-[11px] text-slate-500 mt-0.5 truncate">{sub}</p>}
        </div>
    );
}

const kvTones = {
    default: 'text-slate-800',
    emerald: 'text-brand-green-dark',
    rose: 'text-rose-600',
    amber: 'text-amber-700',
    navy: 'text-brand-navy',
};

export function DrawerKeyValue({ rows = [], className = '' }) {
    return (
        <dl className={`divide-y divide-slate-100 text-xs sm:text-sm ${className}`}>
            {rows.map((row) => (
                <div key={row.label} className="flex items-center justify-between gap-3 py-2 first:pt-0 last:pb-0">
                    <dt className={`text-slate-500 shrink-0 ${row.emphasize ? 'font-semibold text-slate-700' : ''}`}>
                        {row.label}
                    </dt>
                    <dd
                        className={`text-right tabular-nums min-w-0 ${
                            row.bold || row.emphasize ? 'font-bold' : 'font-semibold'
                        } ${kvTones[row.tone] ?? kvTones.default} ${row.mono ? 'font-mono' : ''} ${
                            row.privacy ? 'privacy-blur' : ''
                        }`}
                    >
                        {row.value}
                    </dd>
                </div>
            ))}
        </dl>
    );
}

const noteTones = {
    sky: {
        card: 'bg-sky-50/45 border-sky-200/80',
        title: 'text-sky-900',
        icon: 'bg-sky-100/80 text-sky-700',
    },
    amber: {
        card: 'bg-amber-50/45 border-amber-200/80',
        title: 'text-amber-900',
        icon: 'bg-amber-100/80 text-amber-700',
    },
    indigo: {
        card: 'bg-indigo-50/45 border-indigo-200/80',
        title: 'text-indigo-900',
        icon: 'bg-indigo-100/80 text-indigo-700',
    },
    emerald: {
        card: 'bg-emerald-50/45 border-emerald-200/80',
        title: 'text-brand-green-dark',
        icon: 'bg-emerald-100/80 text-brand-green-dark',
    },
    slate: {
        card: 'bg-white border-slate-200/80',
        title: 'text-slate-700',
        icon: 'bg-slate-100 text-brand-navy',
    },
};

export function DrawerNoteCallout({ role, label, note, timestamp, tone = 'sky', icon: Icon, className = '' }) {
    const style = noteTones[tone] ?? noteTones.sky;
    const heading = label ?? (role ? `Catatan ${role}` : 'Catatan');

    return (
        <div
            className={`rounded-xl border p-3.5 shadow-[0_1px_3px_rgba(15,30,54,0.04)] ${style.card} ${className}`}
        >
            <div className="flex items-center justify-between gap-2 mb-1">
                <div className="flex items-center gap-2 min-w-0">
                    {Icon && (
                        <span className={`w-6 h-6 rounded-md inline-flex items-center justify-center shrink-0 ${style.icon}`}>
                            <Icon size={13} aria-hidden="true" />
                        </span>
                    )}
                    <p className={`text-xs font-bold truncate ${style.title}`}>{heading}</p>
                </div>
                {timestamp && <span className="text-[11px] text-slate-500 shrink-0">{timestamp}</span>}
            </div>
            <p className="text-sm text-slate-700 leading-relaxed">{note}</p>
        </div>
    );
}
