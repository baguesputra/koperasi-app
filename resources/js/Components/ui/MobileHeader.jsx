import { Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

export default function MobileHeader({ title, backHref, onBack, action = null, className = '' }) {
    function handleBack() {
        if (onBack) {
            onBack();
        } else if (!backHref && typeof window !== 'undefined') {
            window.history.back();
        }
    }

    return (
        <header
            className={`sm:hidden sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-slate-100 shadow-xs pt-[env(safe-area-inset-top)] ${className}`}
        >
            <div className="h-13 px-3 flex items-center justify-between gap-2">
                {backHref ? (
                    <Link
                        href={backHref}
                        aria-label="Kembali"
                        className="w-10 h-10 -ml-1 rounded-full flex items-center justify-center text-slate-600 hover:text-brand-navy hover:bg-slate-100 active:scale-95 transition-all shrink-0 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-green"
                    >
                        <ArrowLeft size={20} />
                    </Link>
                ) : (
                    <button
                        type="button"
                        onClick={handleBack}
                        aria-label="Kembali"
                        className="w-10 h-10 -ml-1 rounded-full flex items-center justify-center text-slate-600 hover:text-brand-navy hover:bg-slate-100 active:scale-95 transition-all shrink-0 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-green"
                    >
                        <ArrowLeft size={20} />
                    </button>
                )}

                <h1 className="text-sm font-bold text-slate-800 truncate text-center flex-1">
                    {title}
                </h1>

                <div className="w-10 flex items-center justify-end shrink-0">
                    {action || <div className="w-10" aria-hidden="true" />}
                </div>
            </div>
        </header>
    );
}

