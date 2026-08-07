export default function TextField({ className = '', ...props }) {
    return (
        <input
            className={`w-full px-4 py-3 text-base rounded-xl border border-slate-300 focus:border-brand-green focus:ring-2 focus:ring-brand-green/20 outline-none transition-colors ${className}`}
            {...props}
        />
    );
}