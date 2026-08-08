import { Link, usePage } from '@inertiajs/react';
import { Home, FileText, History, User, LogOut } from 'lucide-react';

const menuUtama = [
    { label: 'Beranda', href: route('portal.dashboard'), icon: Home, match: 'portal.dashboard' },
    { label: 'Ajukan', href: route('portal.pinjaman.create'), icon: FileText, match: 'portal.pinjaman.create' },
    { label: 'Riwayat', href: route('portal.riwayat'), icon: History, match: 'portal.riwayat' },
    { label: 'Profil', href: '#', icon: User, match: 'portal.profil' },
];

export default function AnggotaLayout({ children }) {
    const { auth } = usePage().props;
    const initial = auth.user?.name?.charAt(0)?.toUpperCase() ?? '?';

    return (
        <div className="min-h-screen bg-slate-50 pb-20 lg:pb-0">
            {/* Satu bar navigasi - responsif, bukan 2 bar terpisah */}
            <header className="sticky top-0 z-50 bg-white border-b border-slate-200">
                <div className="max-w-3xl mx-auto px-4 sm:px-6 py-3 flex items-center justify-between gap-4">
                    <div className="flex items-center gap-2.5 shrink-0">
                        <img src="/images/logo.png" alt="Koperasi App" className="w-8 h-8" />
                        <span className="font-bold text-base text-slate-800 hidden sm:inline">
                            Koperasi App
                        </span>
                    </div>

                    {/* Menu inline - HANYA tampil di layar besar, jadi 1 baris dengan header */}
                    <nav className="hidden lg:flex items-center gap-1 flex-1 justify-center">
                        {menuUtama.map((item) => {
                            const Icon = item.icon;
                            const isActive = item.href !== '#' && route().current(item.match);
                            return (
                                <Link
                                    key={item.label}
                                    href={item.href}
                                    className={`flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold transition-colors ${
                                        isActive
                                            ? 'bg-brand-green-light text-brand-green-dark'
                                            : 'text-slate-500 hover:bg-slate-100'
                                    }`}
                                >
                                    <Icon size={16} />
                                    {item.label}
                                </Link>
                            );
                        })}
                    </nav>

                    <div className="flex items-center gap-3 shrink-0">
                        <div className="w-9 h-9 rounded-full bg-brand-green text-white flex items-center justify-center text-sm font-bold">
                            {initial}
                        </div>
                        <Link
                            href={route('logout')}
                            method="post"
                            as="button"
                            className="text-slate-400 hover:text-red-600 transition-colors"
                        >
                            <LogOut size={18} />
                        </Link>
                    </div>
                </div>
            </header>

            {/* Konten */}
            <main className="max-w-3xl mx-auto px-4 sm:px-6 py-6">
                {children}
            </main>

            {/* Bottom navigation - HANYA tampil di layar kecil, satu-satunya nav di mobile */}
            <nav className="lg:hidden fixed bottom-0 inset-x-0 bg-white border-t border-slate-200 px-2 py-2 z-50">
                <div className="max-w-3xl mx-auto flex items-center justify-around">
                    {menuUtama.map((item) => {
                        const Icon = item.icon;
                        const isActive = item.href !== '#' && route().current(item.match);
                        return (
                            <Link
                                key={item.label}
                                href={item.href}
                                className={`flex flex-col items-center gap-1 px-4 py-1.5 rounded-xl transition-colors ${
                                    isActive ? 'text-brand-green' : 'text-slate-400'
                                }`}
                            >
                                <Icon size={22} strokeWidth={isActive ? 2.5 : 2} />
                                <span className={`text-xs ${isActive ? 'font-bold' : 'font-medium'}`}>
                                    {item.label}
                                </span>
                            </Link>
                        );
                    })}
                </div>
            </nav>
        </div>
    );
}