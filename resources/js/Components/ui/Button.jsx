export const buttonBase = 'inline-flex items-center justify-center gap-2 font-semibold rounded-xl sm:rounded-lg transition-all duration-150 active:scale-[0.99] disabled:opacity-50 disabled:cursor-not-allowed';

export const buttonVariants = {
    primary: 'bg-gradient-to-b from-brand-green to-brand-green-dark text-white shadow-xs hover:brightness-105 active:brightness-95',
    secondary: 'bg-gradient-to-b from-brand-navy-light to-brand-navy text-white shadow-xs hover:brightness-110',
    outline: 'border-2 border-brand-navy/80 text-brand-navy bg-white hover:bg-brand-navy/5 hover:border-brand-navy',
    neutral: 'border border-slate-300 bg-white text-slate-700 shadow-2xs hover:bg-slate-50 hover:border-slate-400 hover:text-slate-900 active:bg-slate-100',
    ghost: 'text-brand-navy hover:bg-brand-navy/6',
    danger: 'bg-gradient-to-b from-red-500 to-red-600 text-white shadow-xs hover:brightness-105',
    dangerSoft: 'border border-rose-200 bg-rose-50/70 text-rose-700 hover:bg-rose-100/80 hover:border-rose-300 hover:text-rose-800 active:bg-rose-100',
};

// Responsive: finger-friendly touch target di mobile (min 44px), compact & sleek di desktop (32px - 40px)
export const buttonSizes = {
    sm: 'px-3 py-1.5 text-xs min-h-[38px] sm:min-h-[32px] sm:px-2.5 sm:py-1',
    md: 'px-4 py-2.5 text-sm min-h-[44px] sm:min-h-[36px] sm:py-1.5 sm:px-3.5 sm:text-xs md:text-sm',
    lg: 'px-5 py-3 text-base min-h-[48px] sm:min-h-[40px] sm:py-2 sm:px-4 sm:text-sm',
    drawer: 'px-4 py-2.5 text-sm min-h-[44px] sm:min-h-[40px] sm:px-5 sm:py-2 font-semibold',
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