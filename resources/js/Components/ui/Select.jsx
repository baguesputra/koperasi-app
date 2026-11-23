import { forwardRef } from 'react';
import { resolveFieldTokens } from './TextField';

const Select = forwardRef(function Select(
    { size = 'md', className = '', invalid = false, disabled = false, children, ...props },
    ref,
) {
    const effectiveSize = size === 'sm' ? 'sm' : 'md';
    const tokens = resolveFieldTokens(effectiveSize, className, invalid);

    const classes = [
        'w-full border text-slate-800',
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
        <select
            ref={ref}
            disabled={disabled}
            aria-invalid={invalid || undefined}
            className={classes}
            {...props}
        >
            {children}
        </select>
    );
});

export default Select;
