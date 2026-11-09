export default function PageHeader({ title, subtitle, className = '', children }) {
    return (
        <div className={`flex items-start justify-between gap-3 flex-wrap mb-4 sm:mb-5 ${className}`}>
            <div>
                <h1 className="text-xl sm:text-2xl font-bold text-slate-800 tracking-tight">{title}</h1>
                {subtitle && <p className="text-xs sm:text-sm text-slate-500 mt-0.5 sm:mt-1">{subtitle}</p>}
            </div>
            {children && <div className="shrink-0">{children}</div>}
        </div>
    );
}