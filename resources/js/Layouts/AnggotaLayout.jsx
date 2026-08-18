import { Link, usePage } from '@inertiajs/react';
import { useState, useRef, useEffect } from 'react';
import { User, LogOut, ChevronDown } from 'lucide-react';

export default function AnggotaLayout({ children }) {
    const { auth } = usePage().props;
    const initial = auth.user?.name?.charAt(0)?.toUpperCase() ?? '?';
    const permissions = auth.user?.permissions ?? [];
    const bisaKembaliKeCoop = permissions.includes('portal.akses');
    const [dropdownOpen, setDropdownOpen] = useState(false);
    const dropdownRef = useRef(null);

    useEffect(() => {
        function handleClickOutside(e) {
            if (dropdownRef.current && !dropdownRef.current.contains(e.target)) {
                setDropdownOpen(false);
            }
        }
        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    return (
        <div className="min-h-screen bg-slate-50">
            <header className="sticky top-0 z-50 bg-white border-b-2 border-brand-navy">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
                    <Link href={route('portal.dashboard')} className="flex items-center gap-2.5">
                        <img src="/images/logo.png" alt="Koperasi App" className="w-8 h-8" />
                        <span className="font-bold text-base text-slate-800">Koperasi App</span>
                    </Link>

                    <div className="relative" ref={dropdownRef}>
                        <button
                            onClick={() => setDropdownOpen(!dropdownOpen)}
                            className="flex items-center gap-2 pl-1 pr-2 py-1 rounded-full hover:bg-slate-100 transition-colors"
                        >
                            <div className="w-9 h-9 rounded-full bg-brand-green text-white flex items-center justify-center text-sm font-bold">
                                {initial}
                            </div>
                            <ChevronDown size={16} className={`text-slate-400 transition-transform ${dropdownOpen ? 'rotate-180' : ''}`} />
                        </button>

                        {dropdownOpen && (
                            <div className="absolute right-0 mt-2 w-56 bg-white rounded-xl border border-slate-100 shadow-lg overflow-hidden">
                                <div className="px-4 py-3 border-b border-slate-100">
                                    <p className="text-sm font-semibold text-slate-800 truncate">{auth.user?.name}</p>
                                    <p className="text-xs text-slate-400">{auth.user?.email}</p>
                                </div>
                                <Link
                                    href={route('portal.profil')}
                                    className="flex items-center gap-2.5 px-4 py-3 text-sm text-slate-600 hover:bg-slate-50 transition-colors"
                                >
                                    <User size={16} />
                                    Lihat Profil
                                </Link>
                                {bisaKembaliKeCoop && (
                                    <Link
                                        href={route('dashboard')}
                                        className="flex items-center gap-2.5 px-4 py-3 text-sm text-slate-600 hover:bg-slate-50 transition-colors"
                                    >
                                        <User size={16} />
                                        Dashboard Koperasi
                                    </Link>
                                )}
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
                            </div>
                        )}
                    </div>
                </div>
            </header>

            <main className="max-w-7xl mx-auto px-4 sm:px-6 py-6 sm:py-8">
                {children}
            </main>
        </div>
    );
}