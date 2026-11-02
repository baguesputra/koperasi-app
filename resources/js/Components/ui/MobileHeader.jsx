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
        <header className={`shell-nav sm:hidden ${className}`}>
            <div className="shell-nav__inner">
                {backHref ? (
                    <Link href={backHref} aria-label="Kembali" className="shell-back">
                        <ArrowLeft size={20} aria-hidden="true" />
                    </Link>
                ) : (
                    <button type="button" onClick={handleBack} aria-label="Kembali" className="shell-back">
                        <ArrowLeft size={20} aria-hidden="true" />
                    </button>
                )}

                <h1 className="shell-sub-title">{title}</h1>

                <div className="w-11 flex items-center justify-end shrink-0">
                    {action || <div className="w-11" aria-hidden="true" />}
                </div>
            </div>
        </header>
    );
}
