const selectSizes = {
    sm: 'px-3 py-2 text-base sm:py-1.5 sm:px-3 sm:text-xs rounded-xl sm:rounded-lg',
    md: 'px-3.5 py-2.5 text-base sm:py-2 sm:px-3 sm:text-sm rounded-xl sm:rounded-lg',
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