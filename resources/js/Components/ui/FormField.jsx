export default function FormField({ label, error, children, hint, required = false }) {
    return (
        <div className="mb-3 sm:mb-3.5">
            <label className="block text-xs sm:text-sm font-semibold text-slate-700 mb-1 sm:mb-1.5">
                {label}
                {required && <span className="text-rose-500 ml-0.5" aria-hidden="true">*</span>}
            </label>
            {children}
            {hint && !error && (
                <p className="mt-1 text-[11px] sm:text-xs text-slate-400">{hint}</p>
            )}
            {error && (
                <p role="alert" className="mt-1 text-[11px] sm:text-xs font-medium text-red-600">{error}</p>
            )}
        </div>
    );
}
