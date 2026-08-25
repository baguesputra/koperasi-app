import Sidebar from './Partials/Sidebar';
import { Link, usePage } from '@inertiajs/react';
import { Settings, ChevronDown, Menu, X } from 'lucide-react';
import Dropdown from '@/Components/Dropdown';
import FlashToast from '@/Components/ui/FlashToast';
import { useEffect, useRef, useState } from 'react';
import { Transition } from '@headlessui/react';

export default function SidebarLayout({ children }) {
    const { auth } = usePage().props;
    const initial = auth.user?.name?.charAt(0)?.toUpperCase() ?? '?';
    const bisaPengaturan = auth.user?.permissions?.includes('pengaturan.kelola');
    const bisaAjukanPinjaman = auth.user?.permissions?.includes('portal.akses');

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
                <header className="h-16 sticky top-0 z-40 bg-slate-50 px-4 flex items-center gap-2 shrink-0">
                    <button
                        onClick={() => setMobileSidebarOpen(true)}
                        className="lg:hidden inline-flex items-center justify-center p-2 text-slate-500 hover:text-slate-700 hover:bg-slate-100 rounded-lg"
                        aria-label="Buka menu navigasi"
                    >
                        <Menu size={24} />
                    </button>

                    <div className="flex items-center gap-1 ml-auto">
                        {bisaPengaturan && (
                            <Link
                                href={route('pengaturan.index')}
                                className="p-2 rounded-lg text-slate-400 hover:text-brand-navy hover:bg-white transition-colors"
                                title="Pengaturan"
                            >
                                <Settings size={20} />
                            </Link>
                        )}

                        <Dropdown>
                            <Dropdown.Trigger>
                                <button
                                    type="button"
                                    className="flex items-center gap-3 rounded-full py-1 pl-1 pr-2 hover:bg-slate-100 transition-colors"
                                >
                                    <div className="w-9 h-9 rounded-full bg-brand-green text-white flex items-center justify-center text-sm font-bold">
                                        {initial}
                                    </div>
                                    <div className="hidden sm:block leading-tight text-left">
                                        <p className="text-sm font-semibold text-slate-800">{auth.user?.name}</p>
                                        <p className="text-xs text-slate-400 capitalize">
                                            {auth.user?.roles?.[0]?.replace('_', ' ') ?? '-'}
                                        </p>
                                    </div>
                                    <ChevronDown size={16} className="text-slate-400" />
                                </button>
                            </Dropdown.Trigger>

                            <Dropdown.Content>
                                {bisaAjukanPinjaman && (
                                    <>
                                        <Dropdown.Link href={route('portal.dashboard')}>
                                            Pengajuan Pinjaman
                                        </Dropdown.Link>
                                        <Dropdown.Link href={route('dashboard')}>
                                            Dashboard Koperasi
                                        </Dropdown.Link>
                                    </>
                                )}
                                <Dropdown.Link href={route('profile.edit')}>
                                    Profile
                                </Dropdown.Link>
                                <form method="POST" action={route('logout')}>
    <input
        type="hidden"
        name="_token"
        value={document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content')}
    />

    <button
        type="submit"
        className="block w-full px-4 py-2 text-start text-sm leading-5 text-red-600 transition duration-150 ease-in-out hover:text-red-700 hover:bg-gray-100 focus:bg-gray-100 focus:outline-none"
    >
        Keluar
    </button>
</form>
                            </Dropdown.Content>
                        </Dropdown>
                    </div>
                </header>

                <main className="flex-1 p-4 sm:p-6 lg:p-8 max-w-[1400px] w-full mx-auto">
                    <FlashToast />
                    {children}
                </main>
            </div>
        </div>
    );
}