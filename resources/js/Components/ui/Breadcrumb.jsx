import { Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';

export default function Breadcrumb({ items }) {
    return (
        <nav className="flex items-center gap-1.5 text-sm font-semibold text-slate-400 mb-4 flex-wrap">
            {items.map((item, i) => {
                const last = i === items.length - 1;
                return (
                    <div key={i} className="flex items-center gap-1.5">
                        {i > 0 && <ChevronRight size={14} className="text-slate-300" />}
                        {item.href && !last ? (
                            <Link href={item.href} className="text-slate-500 hover:text-brand-navy transition-colors">
                                {item.label}
                            </Link>
                        ) : (
                            <span className={last ? 'text-slate-700' : ''}>{item.label}</span>
                        )}
                    </div>
                );
            })}
        </nav>
    );
}