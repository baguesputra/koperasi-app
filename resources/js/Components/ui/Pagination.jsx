import { ChevronLeft, ChevronRight } from 'lucide-react';
import { router } from '@inertiajs/react';

export default function Pagination({ links, routeName, params = {} }) {
    if (!links || links.length <= 3) return null;

    const renderLabel = (label) => {
        if (label === '&laquo;' || label === '&lsaquo;') return <ChevronLeft size={14} />;
        if (label === '&raquo;' || label === '&rsaquo;') return <ChevronRight size={14} />;
        return label;
    };

    return (
        <div className="flex items-center justify-center gap-1.5 mt-5">
            {links.map((link, i) => (
                <button
                    key={i}
                    disabled={!link.url}
                    onClick={() => link.url && router.get(link.url, {}, { preserveState: true })}
                    className={`px-3.5 py-2 text-sm font-semibold rounded-lg transition-colors ${
                        link.active
                            ? 'bg-brand-green text-white'
                            : link.url
                            ? 'text-slate-600 hover:bg-slate-100'
                            : 'text-slate-300 cursor-not-allowed'
                    }`}
                >
                    {renderLabel(link.label)}
                </button>
            ))}
        </div>
    );
}