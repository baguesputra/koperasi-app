import Sidebar from './Partials/Sidebar';
import { Link, usePage } from '@inertiajs/react';
import { Settings, ChevronDown } from 'lucide-react';
import Dropdown from '@/Components/Dropdown';
import { useState } from 'react';

export default function SidebarLayout({ children }) {
    const { auth } = usePage().props;
    const initial = auth.user?.name?.charAt(0)?.toUpperCase() ?? '?';
    const bisaPengaturan = auth.user?.permissions?.includes('pengaturan.kelola');
    const bisaAjukanPinjaman = auth.user?.permissions?.includes('portal.akses');

    const [collapsed, setCollapsed] = useState(
        () => typeof window !== 'undefined' && localStorage.getItem('sidebar-collapsed') === '1',
    );
    const toggleSidebar = () =>
        setCollapsed((prev) => {
            const next = !prev;
            localStorage.setItem('sidebar-collapsed', next ? '1' : '0');
            return next;
        });

    return (
        <div className="flex min-h-screen bg-slate-50">
            <div className="sticky top-0 h-screen shrink-0 p-3">
                <Sidebar collapsed={collapsed} onToggle={toggleSidebar} />
            </div>

            <div className="flex-1 flex flex-col min-w-0">
                <header className="h-16 sticky top-0 z-40 bg-slate-50 px-6 flex items-center justify-end gap-4 shrink-0">
                    {bisaPengaturan && (
                        <>
                            <Link
                                href={route('pengaturan.index')}
                                className="flex items-center text-slate-400 hover:text-brand-navy transition-colors"
                                title="Pengaturan"
                            >
                                <Settings size={20} />
                            </Link>
                            <span className="w-0.5 h-6 bg-slate-300" />
                        </>
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
                                <div className="leading-tight text-left">
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
                </header>

                <main className="flex-1 p-6 lg:p-8 max-w-[1400px] w-full mx-auto">
                    {children}
                </main>
            </div>
        </div>
    );
}