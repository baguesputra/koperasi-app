import {
    Dialog,
    DialogPanel,
    Transition,
    TransitionChild,
} from '@headlessui/react';
import { X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

export default function Drawer({
    children,
    show = false,
    open,
    title,
    description,
    badge = null,
    footer = null,
    surface = 'alabaster',
    layout = 'scroll',
    initialFocus,
    onClose = () => {},
    maxWidth = 'xl',
    mobileMode = 'fullscreen',
}) {
    const isOpen = Boolean(show || open);
    const panelRef = useRef(null);
    const maxWidthClass = {
        lg: 'max-w-lg',
        xl: 'max-w-xl',
        '2xl': 'max-w-2xl',
        '3xl': 'max-w-3xl',
        '4xl': 'max-w-4xl',
    }[maxWidth] ?? 'max-w-xl';

    const [isMobile, setIsMobile] = useState(false);

    useEffect(() => {
        const checkMobile = () => setIsMobile(window.innerWidth < 1024);
        checkMobile();
        window.addEventListener('resize', checkMobile);
        return () => window.removeEventListener('resize', checkMobile);
    }, []);

    const isFullscreenMobile = mobileMode === 'fullscreen' && isMobile;
    const surfaceClass = surface === 'white' ? 'bg-white' : 'bg-[#F5F7FA]';

    const panelClass = `relative flex h-full w-full flex-col ${surfaceClass} ${
        isFullscreenMobile
            ? 'max-w-full w-full rounded-none'
            : `${maxWidthClass} border-l border-slate-200/80 shadow-[-12px_0_40px_-10px_rgba(15,30,54,0.18)]`
    }`;

    useEffect(() => {
        if (!isOpen) return undefined;

        const frameId = window.requestAnimationFrame(() => {
            const panel = panelRef.current;
            if (!panel) return;

            const active = document.activeElement;
            const isInputActive = active && panel.contains(active) && ['INPUT', 'TEXTAREA', 'SELECT'].includes(active.tagName);
            if (isInputActive) return;

            const target = panel.querySelector('[data-autofocus]:not([disabled]), [autofocus]:not([disabled])');
            if (target && typeof target.focus === 'function') {
                target.focus({ preventScroll: true });
            }
        });

        return () => window.cancelAnimationFrame(frameId);
    }, [isOpen]);

    return (
        <Transition show={isOpen} leave="duration-200">
            <Dialog as="div" className="fixed inset-0 z-50 overflow-hidden" initialFocus={initialFocus} onClose={onClose}>
                <TransitionChild
                    enter="ease-out duration-300"
                    enterFrom="opacity-0"
                    enterTo="opacity-100"
                    leave="ease-in duration-200"
                    leaveFrom="opacity-100"
                    leaveTo="opacity-0"
                >
                    <div className="absolute inset-0 bg-brand-navy/45 backdrop-blur-[2px]" />
                </TransitionChild>

                <div className={`fixed inset-0 flex ${isFullscreenMobile ? 'justify-center items-end' : 'justify-end'}`}>
                    <TransitionChild
                        enter="ease-out duration-300"
                        enterFrom={isFullscreenMobile ? 'translate-y-full' : 'translate-x-full'}
                        enterTo="translate-y-0 translate-x-0"
                        leave="ease-in duration-200"
                        leaveFrom={isFullscreenMobile ? 'translate-y-0' : 'translate-x-0'}
                        leaveTo={isFullscreenMobile ? 'translate-y-full' : 'translate-x-full'}
                    >
                        <DialogPanel ref={panelRef} className={panelClass}>
                            <div className="shrink-0 bg-white border-b border-slate-200/80 relative">
                                <div className="flex items-start justify-between gap-4 px-5 py-3.5 sm:px-6 sm:py-4">
                                    <div className="min-w-0 flex-1">
                                        <h2 className="text-sm sm:text-base font-bold text-slate-900 tracking-tight truncate">{title}</h2>
                                        {description && (
                                            <p className="text-xs text-slate-500 mt-0.5 leading-relaxed truncate">{description}</p>
                                        )}
                                    </div>
                                    <div className="flex items-center gap-2 shrink-0">
                                        {badge}
                                        <button
                                            type="button"
                                            onClick={onClose}
                                            className="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition-colors shrink-0"
                                            aria-label="Tutup panel"
                                            title="Tutup (Esc)"
                                        >
                                            <X size={18} />
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {layout === 'split' ? (
                                <div className="flex-1 min-h-0 flex flex-col overflow-hidden">
                                    {children}
                                </div>
                            ) : (
                                <>
                                    <div className="flex-1 overflow-y-auto px-4 py-4 sm:px-5 sm:py-5">
                                        {children}
                                    </div>
                                    {footer && (
                                        <div className="shrink-0 bg-white border-t border-slate-200/80 px-4 py-3 pb-[calc(0.75rem+env(safe-area-inset-bottom))] sm:px-5 sm:py-3.5 shadow-[0_-4px_16px_-4px_rgba(15,30,54,0.06)]">
                                            {footer}
                                        </div>
                                    )}
                                </>
                            )}
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </Dialog>
        </Transition>
    );
}