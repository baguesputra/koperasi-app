export const buttonBase = 'inline-flex items-center justify-center gap-2 font-semibold rounded-xl sm:rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed';

export const buttonVariants = {
    primary: 'bg-brand-green text-white hover:bg-brand-green-dark active:bg-brand-green-dark',
    secondary: 'bg-brand-navy text-white hover:bg-brand-navy-light',
    outline: 'border-2 border-brand-navy text-brand-navy hover:bg-slate-50',
    ghost: 'text-brand-navy hover:bg-slate-100',
    danger: 'bg-red-500 text-white hover:bg-red-600',
};

// Responsive: finger-friendly touch target di mobile (min 44px), compact & sleek di desktop (32px - 40px)
export const buttonSizes = {
    sm: 'px-3 py-1.5 text-xs min-h-[38px] sm:min-h-[32px] sm:px-2.5 sm:py-1',
    md: 'px-4 py-2.5 text-sm min-h-[44px] sm:min-h-[36px] sm:py-1.5 sm:px-3.5 sm:text-xs md:text-sm',
    lg: 'px-5 py-3 text-base min-h-[48px] sm:min-h-[40px] sm:py-2 sm:px-4 sm:text-sm',
};

export default function Button({
    children,
    variant = 'primary',
    size = 'md',
    className = '',
    disabled = false,
    ...props
}) {
    return (
        <button
            className={`${buttonBase} ${buttonVariants[variant]} ${buttonSizes[size]} ${className}`}
            disabled={disabled}
            {...props}
        >
            {children}
        </button>
    );
}