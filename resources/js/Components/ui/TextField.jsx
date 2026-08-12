const inputSizes = {
    sm: 'px-4 py-2.5 text-base rounded-xl',
    md: 'px-4 py-3 text-base rounded-xl',
};

export default function TextField({ size = 'md', className = '', ...props }) {
    return (
        <input
            className={`w-full ${inputSizes[size]} border border-slate-300 focus:border-brand-green focus:ring-2 focus:ring-brand-green/20 outline-none transition-colors ${className}`}
            {...props}
        />
    );
}