export default function PageHeader({ title, subtitle, className = '', children }) {
    return (
        <div className={`flex items-start justify-between gap-3 flex-wrap mb-6 ${className}`}>
            <div>
                <h1 className="text-2xl font-bold text-slate-800">{title}</h1>
                {subtitle && <p className="text-base text-slate-400 mt-1">{subtitle}</p>}
            </div>
            {children && <div className="shrink-0">{children}</div>}
        </div>
    );
}