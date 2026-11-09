const inputSizes = {
    sm: 'px-3 py-2 text-base sm:py-1.5 sm:px-3 sm:text-xs rounded-xl sm:rounded-lg',
    md: 'px-3.5 py-2.5 text-base sm:py-2 sm:px-3 sm:text-sm rounded-xl sm:rounded-lg',
};

export default function TextField({ size = 'md', className = '', as = 'input', rows, ...props }) {
    const Component = as;
    const baseClass = `w-full border border-slate-300 focus:border-brand-green focus:ring-2 focus:ring-brand-green/20 outline-none transition-colors`;
    const sizeClass = as === 'textarea' ? 'px-3.5 py-2.5 text-base sm:py-2 sm:px-3 sm:text-sm rounded-xl sm:rounded-lg' : inputSizes[size];

    return (
        <Component
            className={`${baseClass} ${sizeClass} ${className}`}
            rows={rows}
            {...props}
        />
    );
}