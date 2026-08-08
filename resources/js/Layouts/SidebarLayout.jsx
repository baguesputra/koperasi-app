import Sidebar from './Partials/Sidebar';
import { Link, usePage } from '@inertiajs/react';
import { LogOut } from 'lucide-react';

export default function SidebarLayout({ children }) {
    const { auth } = usePage().props;
    const initial = auth.user?.name?.charAt(0)?.toUpperCase() ?? '?';

    return (
        <div className="flex min-h-screen bg-slate-50">
            <div className="sticky top-0 h-screen shrink-0">
                <Sidebar />
            </div>

            <div className="flex-1 flex flex-col min-w-0">
                {/* Topbar - tinggi h-16, garis bawah navy tebal, sejajar dengan header Sidebar */}
                <header className="h-16 sticky top-0 z-40 bg-white border-b-2 border-brand-navy px-6 flex items-center justify-end gap-4 shrink-0">
                    <div className="flex items-center gap-3">
                        <div className="w-9 h-9 rounded-full bg-brand-green text-white flex items-center justify-center text-sm font-bold">
                            {initial}
                        </div>
                        <div className="leading-tight">
                            <p className="text-sm font-semibold text-slate-800">{auth.user?.name}</p>
                            <p className="text-xs text-slate-400 capitalize">
                                {auth.user?.roles?.[0]?.replace('_', ' ') ?? '-'}
                            </p>
                        </div>
                    </div>

                    <span className="w-0.5 h-6 bg-slate-300" />

                    <Link
                        href={route('logout')}
                        method="post"
                        as="button"
                        className="flex items-center text-slate-400 hover:text-red-600 transition-colors"
                    >
                        <LogOut size={18} />
                    </Link>
                </header>

                <main className="flex-1 p-6 lg:p-8 max-w-[1400px] w-full mx-auto">
                    {children}
                </main>
            </div>
        </div>
    );
}