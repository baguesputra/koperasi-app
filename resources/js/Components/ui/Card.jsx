export default function Button({
    children,
    variant = 'primary',
    size = 'md',
    className = '',
    disabled = false,
    ...props
}) {
    const base = 'inline-flex items-center justify-center gap-2 font-semibold rounded-xl transition-colors disabled:opacity-50 disabled:cursor-not-allowed';

    const variants = {
        primary: 'bg-brand-green text-white hover:bg-brand-green-dark active:bg-brand-green-dark',
        secondary: 'bg-brand-navy text-white hover:bg-brand-navy-light',
        outline: 'border-2 border-brand-navy text-brand-navy hover:bg-slate-50',
        ghost: 'text-brand-navy hover:bg-slate-100',
        danger: 'bg-red-500 text-white hover:bg-red-600',
    };

    // Ukuran lebih besar dari standar - target tap area minimal 44px tinggi
    const sizes = {
        sm: 'px-4 py-2 text-sm min-h-[40px]',
        md: 'px-5 py-3 text-base min-h-[48px]',
        lg: 'px-6 py-4 text-lg min-h-[56px]',
    };

    return (
        <button
            className={`${base} ${variants[variant]} ${sizes[size]} ${className}`}
            disabled={disabled}
            {...props}
        >
            {children}
        </button>
    );
}