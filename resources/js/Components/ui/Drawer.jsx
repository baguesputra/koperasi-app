import {
    Dialog,
    DialogPanel,
    Transition,
    TransitionChild,
} from '@headlessui/react';
import { X } from 'lucide-react';
import { useEffect, useState } from 'react';

export default function Drawer({
    children,
    show = false,
    title,
    onClose = () => {},
    maxWidth = 'xl',
    mobileMode = 'fullscreen',
}) {
    const maxWidthClass = {
        lg: 'max-w-lg',
        xl: 'max-w-xl',
        '2xl': 'max-w-2xl',
        '3xl': 'max-w-3xl',
    }[maxWidth];

    const [isMobile, setIsMobile] = useState(false);

    useEffect(() => {
        const checkMobile = () => setIsMobile(window.innerWidth < 1024);
        checkMobile();
        window.addEventListener('resize', checkMobile);
        return () => window.removeEventListener('resize', checkMobile);
    }, []);

    const isFullscreenMobile = mobileMode === 'fullscreen' && isMobile;

    const panelClass = `relative flex h-full w-full flex-col bg-white shadow-xl ${
        isFullscreenMobile
            ? 'max-w-full w-full rounded-none'
            : maxWidthClass
    }`;

    return (
        <Transition show={show} leave="duration-200">
            <Dialog as="div" className="fixed inset-0 z-50 overflow-hidden" onClose={onClose}>
                <TransitionChild
                    enter="ease-out duration-300"
                    enterFrom="opacity-0"
                    enterTo="opacity-100"
                    leave="ease-in duration-200"
                    leaveFrom="opacity-100"
                    leaveTo="opacity-0"
                >
                    <div className="absolute inset-0 bg-slate-900/50" />
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
                        <DialogPanel className={panelClass}>
                            <div className="flex items-center justify-between px-5 py-4 border-b border-slate-100 shrink-0">
                                <h2 className="text-lg font-bold text-slate-800">{title}</h2>
                                <button
                                    onClick={onClose}
                                    className="inline-flex items-center justify-center w-9 h-9 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors"
                                    title="Tutup"
                                >
                                    <X size={20} />
                                </button>
                            </div>
                            <div className="flex-1 overflow-y-auto px-5 py-4">
                                {children}
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </Dialog>
        </Transition>
    );
}