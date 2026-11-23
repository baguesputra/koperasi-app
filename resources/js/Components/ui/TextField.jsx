import { forwardRef } from 'react';

const VERTICAL_SIZES = {
    sm: 'py-2 sm:py-1.5',
    md: 'py-2.5 sm:py-2',
    lg: 'py-2.5',
    xl: 'py-3.5 sm:py-4',
};

const TEXT_SIZES = {
    sm: 'text-base sm:text-xs',
    md: 'text-base sm:text-sm',
    lg: 'text-lg font-bold',
    xl: 'text-xl sm:text-2xl font-bold',
};

const HORIZONTAL_DEFAULTS = {
    sm: { left: 'pl-3', right: 'pr-3' },
    md: { left: 'pl-3.5 sm:pl-3', right: 'pr-3.5 sm:pr-3' },
    lg: { left: 'pl-4', right: 'pr-4' },
    xl: { left: 'pl-4', right: 'pr-4' },
};

const ROUNDED_SIZES = {
    sm: 'rounded-xl sm:rounded-lg',
    md: 'rounded-xl sm:rounded-lg',
    lg: 'rounded-xl',
    xl: 'rounded-xl',
};

const PREFIX_SLOT_PADDING = {
    sm: 'px-2.5 text-xs',
    md: 'px-3 text-xs sm:text-sm',
    lg: 'px-3.5 text-base',
    xl: 'px-4 text-lg',
};

const ADDON_TEXT_BOX_PADDING = {
    sm: 'px-2.5 py-2 sm:py-1.5',
    md: 'px-3 py-2.5 sm:py-2',
    lg: 'px-3.5 py-2.5',
    xl: 'px-4 py-3.5 sm:py-4',
};

export function resolveFieldTokens(size = 'md', className = '', invalid = false) {
    const sizeKey = HORIZONTAL_DEFAULTS[size] ? size : 'md';
    const hasLeft = /(?:^|\s)(?:[a-z-]+:)*(?:pl|px|p)-\S+/.test(className);
    const hasRight = /(?:^|\s)(?:[a-z-]+:)*(?:pr|px|p)-\S+/.test(className);
    const hasVertical = /(?:^|\s)(?:[a-z-]+:)*(?:py|pt|pb|p)-\S+/.test(className);
    const hasRounded = /(?:^|\s)(?:[a-z-]+:)*rounded(?:-\S+)?(?=$|\s)/.test(className);
    const hasBg = /(?:^|\s)(?:[a-z-]+:)*bg-(?!opacity|gradient|clip|origin|repeat|size)\S+/.test(className);
    const hasBorderColor = /(?:^|\s)(?:[a-z-]+:)*border-(?!0|2|4|8|collapse|separate|spacing|x|y|t|r|b|l|solid|dashed|dotted|double|none|s-|e-)\S+/.test(className);
    const hasTextSize = /(?:^|\s)(?:[a-z-]+:)*text-(?:xs|sm|base|lg|xl|\dxl|\[[^\]]+\])(?=$|\s)/.test(className);

    const hPad = HORIZONTAL_DEFAULTS[sizeKey];
    const vPad = hasVertical ? '' : VERTICAL_SIZES[sizeKey];
    const textSize = hasTextSize ? '' : TEXT_SIZES[sizeKey];
    const rounded = hasRounded ? '' : ROUNDED_SIZES[sizeKey];
    const bg = hasBg ? '' : 'bg-white disabled:bg-slate-50';
    const borderColor = hasBorderColor
        ? ''
        : invalid
            ? 'border-rose-400 hover:border-rose-500'
            : 'border-slate-200/95 hover:border-slate-300 disabled:border-slate-200/80';

    const focusClasses = invalid
        ? 'focus:border-rose-500 focus:ring-[3px] focus:ring-rose-500/15'
        : 'focus:border-brand-green focus:ring-[3px] focus:ring-brand-green/15';

    return {
        vPad,
        hLeft: hasLeft ? '' : hPad.left,
        hRight: hasRight ? '' : hPad.right,
        textSize,
        rounded,
        bg,
        borderColor,
        focusClasses,
    };
}

/**
 * Input / Textarea standar aplikasi dengan dukungan prefix/suffix terintegrasi
 * serta proteksi otomatis agar padding/radius kustom tidak tertimpa breakpoint `sm:`.
 */
const TextField = forwardRef(function TextField(
    {
        size = 'md',
        className = '',
        wrapperClassName = '',
        as = 'input',
        rows,
        prefix = null,
        suffix = null,
        invalid = false,
        disabled = false,
        ...props
    },
    ref,
) {
    const hasAddon = as === 'input' && ((prefix !== null && prefix !== undefined && prefix !== '') || (suffix !== null && suffix !== undefined && suffix !== ''));

    if (hasAddon) {
        const sizeKey = ADDON_TEXT_BOX_PADDING[size] ? size : 'md';
        const rounded = ROUNDED_SIZES[sizeKey];
        const slotPad = PREFIX_SLOT_PADDING[sizeKey];
        const innerPad = ADDON_TEXT_BOX_PADDING[sizeKey];
        const textSize = /(?:^|\s)(?:[a-z-]+:)*text-(?:xs|sm|base|lg|xl|\dxl|\[[^\]]+\])(?=$|\s)/.test(className)
            ? ''
            : TEXT_SIZES[sizeKey];

        const containerState = disabled
            ? 'bg-slate-50 border-slate-200/80 opacity-85 cursor-not-allowed'
            : invalid
                ? 'bg-white border-rose-400 focus-within:border-rose-500 focus-within:ring-[3px] focus-within:ring-rose-500/15'
                : 'bg-white border-slate-200/95 hover:border-slate-300 focus-within:border-brand-green focus-within:ring-[3px] focus-within:ring-brand-green/15';

        const renderPrefix = () => {
            if (prefix === null || prefix === undefined || prefix === '') return null;
            if (typeof prefix === 'string') {
                return (
                    <span
                        className={`self-stretch inline-flex items-center ${slotPad} bg-slate-50/90 border-r border-slate-200/80 font-semibold text-slate-500 select-none pointer-events-none shrink-0`}
                    >
                        {prefix}
                    </span>
                );
            }
            return (
                <span className="pl-3 inline-flex items-center text-slate-400 group-focus-within:text-brand-green transition-colors pointer-events-none select-none shrink-0">
                    {prefix}
                </span>
            );
        };

        const renderSuffix = () => {
            if (suffix === null || suffix === undefined || suffix === '') return null;
            if (typeof suffix === 'string') {
                return (
                    <span
                        className={`self-stretch inline-flex items-center ${slotPad} bg-slate-50/90 border-l border-slate-200/80 font-semibold text-slate-500 select-none pointer-events-none shrink-0`}
                    >
                        {suffix}
                    </span>
                );
            }
            return (
                <span className="pr-3 inline-flex items-center shrink-0">
                    {suffix}
                </span>
            );
        };

        return (
            <div
                className={`group flex items-center w-full border ${rounded} ${containerState} shadow-[0_1px_2px_rgba(15,23,42,0.03)] overflow-hidden transition-all duration-150 ${wrapperClassName}`}
            >
                {renderPrefix()}
                <input
                    ref={ref}
                    disabled={disabled}
                    aria-invalid={invalid || undefined}
                    className={`flex-1 min-w-0 w-full bg-transparent border-0 outline-none focus:ring-0 ${innerPad} ${textSize} text-slate-800 placeholder:text-slate-400/90 disabled:text-slate-400 disabled:cursor-not-allowed ${className}`}
                    {...props}
                />
                {renderSuffix()}
            </div>
        );
    }

    const Component = as;
    const effectiveSize = as === 'textarea' ? 'md' : (HORIZONTAL_DEFAULTS[size] ? size : 'md');
    const tokens = resolveFieldTokens(effectiveSize, className, invalid);

    const baseClasses = [
        'w-full border text-slate-800 placeholder:text-slate-400/90',
        'shadow-[0_1px_2px_rgba(15,23,42,0.03)] outline-none transition-all duration-150',
        'disabled:text-slate-400 disabled:cursor-not-allowed',
        tokens.bg,
        tokens.borderColor,
        tokens.rounded,
        tokens.vPad,
        tokens.hLeft,
        tokens.hRight,
        tokens.textSize,
        tokens.focusClasses,
        className,
    ]
        .filter(Boolean)
        .join(' ');

    return (
        <Component
            ref={ref}
            disabled={disabled}
            aria-invalid={invalid || undefined}
            className={baseClasses}
            rows={rows}
            {...props}
        />
    );
});

export default TextField;
