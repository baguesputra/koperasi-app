const inputSizes = {
    sm: 'px-4 py-2.5 text-base rounded-xl',
    md: 'px-4 py-3 text-base rounded-xl',
};

export default function TextField({ size = 'md', className = '', as = 'input', rows, ...props }) {
    const Component = as;
    const baseClass = `w-full border border-slate-300 focus:border-brand-green focus:ring-2 focus:ring-brand-green/20 outline-none transition-colors`;
    const sizeClass = as === 'textarea' ? 'px-4 py-2.5 text-base rounded-xl' : inputSizes[size];

    return (
        <Component
            className={`${baseClass} ${sizeClass} ${className}`}
            rows={rows}
            {...props}
        />
    );
}