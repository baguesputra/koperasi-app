export default function FormField({ label, error, children, hint }) {
    return (
        <div className="mb-5">
            <label className="block text-base font-semibold text-slate-700 mb-2">
                {label}
            </label>
            {children}
            {hint && !error && (
                <p className="mt-1.5 text-sm text-slate-400">{hint}</p>
            )}
            {error && (
                <p className="mt-1.5 text-sm font-medium text-red-600">{error}</p>
            )}
        </div>
    );
}