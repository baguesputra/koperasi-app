const selectSizes = {
    sm: 'px-4 py-2.5 text-base rounded-xl',
    md: 'px-4 py-3 text-base rounded-xl',
};

export default function Select({ size = 'md', className = '', children, ...props }) {
    return (
        <select
            className={`w-full ${selectSizes[size]} border border-slate-300 bg-white focus:border-brand-green focus:ring-2 focus:ring-brand-green/20 outline-none transition-colors ${className}`}
            {...props}
        >
            {children}
        </select>
    );
}