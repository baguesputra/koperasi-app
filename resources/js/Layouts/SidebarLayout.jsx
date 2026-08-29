import Sidebar from './Partials/Sidebar';
import { Menu } from 'lucide-react';
import FlashToast from '@/Components/ui/FlashToast';
import { useEffect, useRef, useState } from 'react';
import { Transition } from '@headlessui/react';

export default function SidebarLayout({ children }) {
    const [collapsed, setCollapsed] = useState(
        () => typeof window !== 'undefined' && localStorage.getItem('sidebar-collapsed') === '1',
    );
    const [hovered, setHovered] = useState(false);
    const [menutup, setMenutup] = useState(false);
    const [mobileSidebarOpen, setMobileSidebarOpen] = useState(false);
    const timerMenutup = useRef(null);

    const expanding = collapsed && hovered;
    const elevating = collapsed && (hovered || menutup);

    useEffect(() => () => clearTimeout(timerMenutup.current), []);

    function sidebarMasuk() {
        clearTimeout(timerMenutup.current);
        if (collapsed) {
            setHovered(true);
            setMenutup(false);
        }
    }

    function sidebarKeluar() {
        setHovered(false);
        if (!collapsed) {
            return;
        }
        setMenutup(true);
        timerMenutup.current = setTimeout(() => setMenutup(false), 320);
    }

    const toggleSidebar = () =>
        setCollapsed((prev) => {
            const next = !prev;
            localStorage.setItem('sidebar-collapsed', next ? '1' : '0');
            clearTimeout(timerMenutup.current);
            setHovered(false);
            setMenutup(false);
            return next;
        });

    const closeMobileSidebar = () => setMobileSidebarOpen(false);

    return (
        <div className="flex min-h-screen bg-slate-50">
            {/* Desktop Sidebar */}
<div
                 onMouseEnter={sidebarMasuk}
                 onMouseLeave={sidebarKeluar}
className={`hidden lg:block sticky top-0 h-screen shrink-0 p-3 transition-[width] duration-300 ${
                      collapsed ? 'w-[88px]' : 'w-[280px]'
                  } ${elevating ? 'z-50' : ''}`}
             >
                <Sidebar collapsed={collapsed} expanding={expanding} onToggle={toggleSidebar} />
            </div>

            {/* Mobile Sidebar Overlay */}
            <Transition show={mobileSidebarOpen} leave="duration-200">
                <div className="lg:hidden fixed inset-0 z-50 flex">
                    <Transition.Child
                        enter="ease-out duration-300"
                        enterFrom="opacity-0"
                        enterTo="opacity-100"
                        leave="ease-in duration-200"
                        leaveFrom="opacity-100"
                        leaveTo="opacity-0"
                    >
                        <div className="absolute inset-0 bg-slate-900/50" onClick={closeMobileSidebar} />
                    </Transition.Child>

                    <Transition.Child
                        enter="ease-out duration-300"
                        enterFrom="-translate-x-full"
                        enterTo="translate-x-0"
                        leave="ease-in duration-200"
                        leaveFrom="translate-x-0"
                        leaveTo="-translate-x-full"
                    >
                        <aside className="relative w-64 h-full bg-white border-r border-slate-200 shadow-xl flex flex-col overflow-hidden">
                            <Sidebar collapsed={false} expanding={false} onToggle={toggleSidebar} />
                        </aside>
                    </Transition.Child>
                </div>
            </Transition>

            <div className="flex-1 flex flex-col min-w-0">
                <div className="lg:hidden sticky top-0 pt-[env(safe-area-inset-top)] z-40 bg-slate-50 h-12 px-4 flex items-center shrink-0">
<button
                         onClick={() => setMobileSidebarOpen(true)}
                         className="min-h-[44px] min-w-[44px] p-2 -ml-2 rounded-lg text-slate-600 hover:text-slate-800 hover:bg-slate-100 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-green/40"
                         aria-label="Buka menu navigasi"
                     >
                        <Menu size={22} />
                    </button>
                    <div className="flex items-center gap-2 mx-auto">
                        <img src="/images/logo.png" alt="Koperasi App" className="w-7 h-7" />
                        <span className="text-sm font-bold text-slate-800">Koperasi App</span>
                    </div>
                    <span className="w-[38px]" aria-hidden="true" />
                </div>

                <main className="flex-1 p-4 sm:p-6 lg:p-8 max-w-[1400px] w-full mx-auto">
                    <FlashToast />
                    {children}
                </main>
            </div>
        </div>
    );
}