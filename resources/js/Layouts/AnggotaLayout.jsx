import { Link, usePage } from '@inertiajs/react';
import { useState, useRef, useEffect } from 'react';
import { User, LogOut, ChevronDown, Home, History, Plus, Info } from 'lucide-react';
import Panduan from '@/Pages/Portal/Panduan';
import MobileHeader from '@/Components/ui/MobileHeader';

export default function AnggotaLayout({ children, mobileTitle = null, backHref = null, mobileAction = null }) {
    const { auth } = usePage().props;
    const initial = auth.user?.name?.charAt(0)?.toUpperCase() ?? '?';
    const roles = auth.user?.roles ?? [];
    const isPengurus = roles.some(r => ['super_admin', 'admin', 'bendahara', 'ketua_koperasi', 'pengawas'].includes(r));
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

    useEffect(() => {
        function handleOpenPanduan() {
            setShowPanduan(true);
        }
        window.addEventListener('open-panduan', handleOpenPanduan);
        return () => window.removeEventListener('open-panduan', handleOpenPanduan);
    }, []);

    const isCurrentRoute = (name) => route().current(name);

    function TabItem({ href, active, icon: Icon, label }) {
        return (
            <Link
                href={href}
                className={`flex flex-col items-center justify-center gap-1 min-h-[52px] py-1.5 text-[11px] font-semibold transition-all active:scale-95 ${
                    active ? 'text-brand-green font-bold' : 'text-slate-500 hover:text-slate-700'
                }`}
            >
                <Icon size={22} strokeWidth={active ? 2.5 : 2} />
                <span className="leading-none">{label}</span>
            </Link>
        );
    }

    return (
        <div className="min-h-screen bg-slate-50 flex flex-col">
            {/* Header hanya tampil di desktop/tablet. Di HP/mobile disembunyikan agar tampilan full screen seperti APK native */}
            <header className="hidden sm:block sticky top-0 z-50 bg-white border-b-2 border-brand-navy pt-[env(safe-area-inset-top)]">
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

            {/* Mobile Header khusus HP jika di sub-halaman */}
            {mobileTitle && (
                <MobileHeader
                    title={mobileTitle}
                    backHref={backHref}
                    action={mobileAction}
                />
            )}

            <main className={`flex-1 w-full max-w-7xl sm:mx-auto px-4 sm:px-6 pb-32 sm:pb-8 ${
                mobileTitle ? 'pt-4 sm:pt-8' : 'pt-[calc(env(safe-area-inset-top)+1rem)] sm:pt-8'
            }`}>
                <div className="mx-auto w-full max-w-lg sm:max-w-none">
                    {children}
                </div>
            </main>

            {anggotaAktif && (
            <nav
                aria-label="Navigasi utama"
                className="sm:hidden fixed bottom-0 inset-x-0 z-50 overflow-visible bg-white/95 backdrop-blur-md border-t border-slate-200/90 shadow-[0_-4px_20px_rgba(15,30,54,0.06)] pb-[calc(env(safe-area-inset-bottom)+0.25rem)]"
            >
                <div className="relative overflow-visible">
                    <div className="grid grid-cols-4 items-end px-2 pt-1 pb-1 overflow-visible">
                        <TabItem href={route('portal.dashboard')} active={isCurrentRoute('portal.dashboard')} icon={Home} label="Beranda" />
                        <TabItem href={route('portal.riwayat')} active={isCurrentRoute('portal.riwayat')} icon={History} label="Riwayat" />
                        <div className="flex justify-center overflow-visible">
                            <Link
                                href={route('portal.pinjaman.create')}
                                aria-label="Ajukan pinjaman"
                                className="-mt-10 w-[64px] h-[64px] rounded-full text-white flex flex-col items-center justify-center gap-0.5 shadow-[0_8px_20px_rgba(31,162,76,0.45)] ring-4 ring-white hover:brightness-95 active:scale-95 transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-green"
                                style={{ backgroundColor: '#1FA24C' }}
                            >
                                <Plus size={26} strokeWidth={2.8} color="#ffffff" />
                                <span className="text-[10px] font-bold leading-none text-white">Ajukan</span>
                            </Link>
                        </div>
                        <TabItem href={route('portal.profil')} active={isCurrentRoute('portal.profil')} icon={User} label="Profil" />
                    </div>
                </div>
            </nav>
            )}

            {anggotaAktif && showPanduan && <Panduan onClose={() => setShowPanduan(false)} />}
        </div>
    );
}
