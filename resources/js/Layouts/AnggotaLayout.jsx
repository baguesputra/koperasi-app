import { Link, usePage } from '@inertiajs/react';
import { useState, useRef, useEffect } from 'react';
import { User, LogOut, ChevronDown, Home, History, Plus, HeartHandshake, Info } from 'lucide-react';
import Panduan from '@/Pages/Portal/Panduan';

export default function AnggotaLayout({ children }) {
    const { auth } = usePage().props;
    const initial = auth.user?.name?.charAt(0)?.toUpperCase() ?? '?';
    const roles = auth.user?.roles ?? [];
    const isPengurus = roles.some(r => ['admin', 'bendahara', 'ketua_koperasi'].includes(r));
    const anggotaAktif = (auth.user?.anggota_status ?? 'aktif') === 'aktif';
    const [dropdownOpen, setDropdownOpen] = useState(false);
    const [showPanduan, setShowPanduan] = useState(false);
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

    useEffect(() => {
        function handleEsc(e) {
            if (e.key === 'Escape' && showPanduan) {
                setShowPanduan(false);
            }
        }
        document.addEventListener('keydown', handleEsc);
        return () => document.removeEventListener('keydown', handleEsc);
    }, [showPanduan]);

    const isCurrentRoute = (name) => route().current(name);

    function TabItem({ href, active, icon: Icon, label }) {
        return (
            <Link
                href={href}
                className={`flex flex-col items-center justify-center gap-1 min-h-[56px] py-2 text-[11px] font-semibold transition-colors ${
                    active ? 'text-brand-green' : 'text-slate-500 hover:text-slate-700'
                }`}
            >
                <Icon size={21} />
                <span className="leading-none">{label}</span>
            </Link>
        );
    }

    return (
        <div className="min-h-screen bg-slate-50 flex flex-col">
            <header className="sticky top-0 z-50 bg-white border-b-2 border-brand-navy pt-[env(safe-area-inset-top)]">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 h-14 sm:h-16 flex items-center justify-between">
                    <div className="flex items-center gap-3 sm:gap-5">
                        <Link href={route('portal.dashboard')} className="flex items-center gap-2.5">
                            <img src="/images/logo.png" alt="Koperasi App" className="w-7 h-7 sm:w-8 sm:h-8" />
                            <span className="font-bold text-sm sm:text-base text-slate-800">Koperasi App</span>
                        </Link>
                    </div>

                    <div className="flex items-center gap-1">
                        {anggotaAktif && (
                            <button
                                type="button"
                                onClick={() => setShowPanduan(true)}
                                aria-label="Tata cara"
                                title="Tata cara"
                                className="w-10 h-10 rounded-full text-slate-500 hover:text-brand-navy hover:bg-slate-100 transition-colors flex items-center justify-center focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-green focus-visible:ring-offset-2"
                            >
                                <Info size={20} />
                            </button>
                        )}
                        <div className="relative" ref={dropdownRef}>
                        <button
                            onClick={() => setDropdownOpen(!dropdownOpen)}
                            className="flex items-center gap-2 min-h-[44px] min-w-[44px] pl-1 pr-2 py-1 rounded-full hover:bg-slate-100 transition-colors"
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
                                {auth.user?.anggota_status && (
                                    <Link
                                        href={route('portal.profil')}
                                        className="flex items-center gap-2.5 px-4 py-3 text-sm text-slate-600 hover:bg-slate-50 transition-colors"
                                    >
                                        <User size={16} />
                                        Lihat Profil
                                    </Link>
                                )}
                                {isPengurus && (
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
                                        className="block w-full px-4 py-3 text-start text-sm leading-5 text-red-600 transition duration-150 ease-in-out hover:text-red-700 hover:bg-gray-100 focus:bg-gray-100 focus:outline-none"
                                    >
                                        Keluar
                                    </button>
                                </form>
                            </div>
                        )}
                        </div>
                    </div>
                </div>
            </header>

            <main className="flex-1 w-full max-w-7xl sm:mx-auto px-4 sm:px-6 pt-5 sm:py-8 pb-32 sm:pb-8">
                <div className="mx-auto w-full max-w-lg sm:max-w-none">
                    {children}
                </div>
            </main>

            {anggotaAktif && (
            <nav aria-label="Navigasi utama" className="sm:hidden fixed bottom-0 inset-x-0 z-50 overflow-visible" style={{ paddingBottom: 'env(safe-area-inset-bottom)' }}>
                <div className="relative overflow-visible bg-white border-t border-slate-200 shadow-[0_-4px_24px_rgba(15,30,54,0.08)]">
                    <div className="grid grid-cols-5 items-end px-2 pt-1 pb-1 overflow-visible">
                        <TabItem href={route('portal.dashboard')} active={isCurrentRoute('portal.dashboard')} icon={Home} label="Beranda" />
                        <TabItem href={route('portal.riwayat')} active={isCurrentRoute('portal.riwayat')} icon={History} label="Riwayat" />
                        <div className="flex justify-center overflow-visible">
                            <Link
                                href={route('portal.pinjaman.create')}
                                aria-label="Ajukan pinjaman"
                                className="-mt-10 w-[68px] h-[68px] rounded-full text-white flex flex-col items-center justify-center gap-0.5 shadow-[0_10px_24px_rgba(31,162,76,0.5)] ring-4 ring-white hover:brightness-95 active:scale-95 transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-green focus-visible:ring-offset-2"
                                style={{ backgroundColor: '#1FA24C' }}
                            >
                                <Plus size={28} strokeWidth={2.6} color="#ffffff" />
                                <span className="text-[10px] font-bold leading-none text-white">Ajukan</span>
                            </Link>
                        </div>
                        <TabItem href={route('portal.klaim-dana-sosial.create')} active={isCurrentRoute('portal.klaim-dana-sosial.create')} icon={HeartHandshake} label="Santunan" />
                        <TabItem href={route('portal.profil')} active={isCurrentRoute('portal.profil')} icon={User} label="Profil" />
                    </div>
                </div>
            </nav>
            )}

            {anggotaAktif && showPanduan && <Panduan onClose={() => setShowPanduan(false)} />}
        </div>
    );
}
