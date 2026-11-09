import { ChevronLeft, ChevronRight } from 'lucide-react';
import { router } from '@inertiajs/react';

export default function Pagination({ links, routeName, params = {}, preserveScroll = true }) {
    if (!links || links.length <= 3) return null;

    const renderLabel = (label) => {
        const teks = String(label ?? '').toLowerCase();

        if (teks.includes('laquo') || teks.includes('lsaquo') || teks.includes('previous') || teks.includes('sebelumnya')) {
            return <ChevronLeft size={14} />;
        }

        if (teks.includes('raquo') || teks.includes('rsaquo') || teks.includes('next') || teks.includes('berikutnya')) {
            return <ChevronRight size={14} />;
        }

        return label;
    };

    return (
        <div className="flex items-center justify-center gap-1 sm:gap-1.5 mt-4 sm:mt-5">
            {links.map((link, i) => (
                <button
                    key={i}
                    disabled={!link.url}
                    onClick={() => link.url && router.get(link.url, {}, { preserveState: true, preserveScroll })}
                    className={`min-h-[36px] min-w-[36px] sm:min-h-[32px] sm:min-w-[32px] px-2.5 py-1.5 sm:px-3 sm:py-1 text-xs sm:text-sm font-semibold rounded-lg transition-colors inline-flex items-center justify-center ${
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