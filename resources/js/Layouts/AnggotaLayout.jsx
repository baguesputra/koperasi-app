import { Link, usePage } from '@inertiajs/react';
import { useState, useRef, useEffect } from 'react';
import { User, LogOut, ChevronDown, Home, History, Plus, Info, ShieldCheck } from 'lucide-react';
import Panduan from '@/Pages/Portal/Panduan';
import MobileHeader from '@/Components/ui/MobileHeader';

export default function AnggotaLayout({
    children,
    mobileTitle = null,
    backHref = null,
    mobileAction = null,
    headerContent = null,
    hideNavbar = false,
}) {
    const { auth } = usePage().props;
    const initial = auth.user?.name?.charAt(0)?.toUpperCase() ?? '?';
    const firstName = auth.user?.name?.split(' ')[0] ?? 'Anggota';
    const roles = auth.user?.roles ?? [];
    const isPengurus = roles.some(r => ['super_admin', 'admin', 'bendahara', 'ketua_koperasi', 'pengawas'].includes(r));
    const anggotaAktif = (auth.user?.anggota_status ?? 'aktif') === 'aktif';
    const [dropdownOpen, setDropdownOpen] = useState(false);
    const [showPanduan, setShowPanduan] = useState(false);
    const [avatarGagal, setAvatarGagal] = useState(false);
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
            {/* Floating Glass Pill Navbar (Option 2) */}
            {!hideNavbar && (
            <header
                className={`sticky top-3 sm:top-5 z-40 px-4 sm:px-6 max-w-7xl mx-auto w-full transition-all ${
                    mobileTitle ? 'hidden sm:block' : ''
                }`}
            >
                <div className="bg-white/65 backdrop-blur-xl backdrop-saturate-150 rounded-2xl border border-slate-200/60 shadow-[0_8px_30px_rgba(15,30,54,0.06)] px-4 sm:px-6 h-14 sm:h-16 flex items-center justify-between gap-4 transition-all">
                    {/* Left: Brand Identity */}
                    <div className="flex items-center gap-3">
                        <Link
                            href={route('portal.dashboard')}
                            className="flex items-center gap-2.5 group rounded-xl focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-green"
                        >
                            <img
                                src="/images/logo.png"
                                alt="Koperasi App"
                                className="w-7 h-7 sm:w-8 sm:h-8 object-contain transition-transform group-hover:scale-105"
                            />
                            <div className="flex items-center gap-2">
                                <span className="font-bold text-sm sm:text-base text-slate-800 tracking-tight">
                                    Koperasi App
                                </span>
                                <span className="inline-flex items-center px-2 py-0.5 rounded-full bg-slate-100/70 text-[10px] font-semibold text-slate-600 tracking-wider uppercase border border-slate-200/40">
                                    Portal Anggota
                                </span>
                            </div>
                        </Link>
                    </div>

                    {/* Right: User Capsule & Dropdown */}
                    <div className="flex items-center gap-2 shrink-0">
                        <div className="relative" ref={dropdownRef}>
                            <button
                                type="button"
                                onClick={() => setDropdownOpen(!dropdownOpen)}
                                aria-expanded={dropdownOpen}
                                className={`flex items-center gap-2 sm:gap-2.5 pl-1.5 pr-2.5 sm:pr-3 py-1 rounded-xl bg-white/50 hover:bg-white/90 border border-slate-200/70 hover:border-slate-300 transition-all ${
                                    dropdownOpen ? 'ring-2 ring-brand-green/20 border-brand-green bg-white/90' : ''
                                } focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-green`}
                            >
                                {auth.user?.foto_url && !avatarGagal ? (
                                    <img
                                        src={auth.user.foto_url}
                                        alt={auth.user.name}
                                        loading="lazy"
                                        referrerPolicy="no-referrer"
                                        onError={() => setAvatarGagal(true)}
                                        className="w-7 h-7 sm:w-8 sm:h-8 rounded-full object-cover shadow-xs border border-slate-200"
                                    />
                                ) : (
                                    <div className="w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-brand-green text-white flex items-center justify-center text-xs sm:text-sm font-bold shadow-xs">
                                        {initial}
                                    </div>
                                )}
                                <div className="text-left hidden sm:block max-w-[130px] truncate">
                                    <span className="block text-xs font-semibold text-slate-700 truncate leading-tight">
                                        {firstName}
                                    </span>
                                </div>
                                <ChevronDown
                                    size={14}
                                    className={`text-slate-400 transition-transform duration-200 ${dropdownOpen ? 'rotate-180 text-slate-600' : ''}`}
                                />
                            </button>

                            {dropdownOpen && (
                                <div className="absolute right-0 mt-2.5 w-64 bg-white rounded-2xl border border-slate-100 shadow-[0_10px_30px_rgba(15,30,54,0.08)] overflow-hidden z-50 animate-in fade-in zoom-in-95 duration-150">
                                    <div className="px-4 py-3.5 bg-slate-50/70 border-b border-slate-100">
                                        <div className="flex items-center gap-1.5">
                                            <span className="w-2 h-2 rounded-full bg-brand-green" />
                                            <p className="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                                Anggota Aktif
                                            </p>
                                        </div>
                                        <p className="text-sm font-bold text-slate-800 truncate mt-1">
                                            {auth.user?.name}
                                        </p>
                                        <p className="text-xs text-slate-500 truncate">
                                            {auth.user?.email}
                                        </p>
                                    </div>

                                    <div className="py-1">
                                        {auth.user?.anggota_status && (
                                            <Link
                                                href={route('portal.profil')}
                                                onClick={() => setDropdownOpen(false)}
                                                className="flex items-center gap-2.5 px-4 py-2.5 text-xs font-medium text-slate-700 hover:bg-slate-50 transition-colors"
                                            >
                                                <User size={15} className="text-slate-400" />
                                                Profil Saya
                                            </Link>
                                        )}
                                        {isPengurus && (
                                            <Link
                                                href={route('dashboard')}
                                                onClick={() => setDropdownOpen(false)}
                                                className="flex items-center gap-2.5 px-4 py-2.5 text-xs font-medium text-slate-700 hover:bg-slate-50 transition-colors"
                                            >
                                                <ShieldCheck size={15} className="text-brand-green" />
                                                Dashboard Koperasi
                                            </Link>
                                        )}
                                        <button
                                            type="button"
                                            onClick={() => {
                                                setDropdownOpen(false);
                                                setShowPanduan(true);
                                            }}
                                            className="w-full flex items-center gap-2.5 px-4 py-2.5 text-xs font-medium text-slate-700 hover:bg-slate-50 transition-colors text-left"
                                        >
                                            <Info size={15} className="text-slate-400" />
                                            Tata Cara & Panduan
                                        </button>
                                    </div>

                                    <div className="border-t border-slate-100 p-1">
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
                                                className="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-medium text-red-600 hover:bg-red-50 hover:text-red-700 transition-colors text-left"
                                            >
                                                <LogOut size={15} />
                                                Keluar
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </header>
            )}

            {/* Mobile Header khusus HP jika di sub-halaman */}
            {mobileTitle && (
                <MobileHeader
                    title={mobileTitle}
                    backHref={backHref}
                    action={mobileAction}
                />
            )}

            <main className="flex-1 w-full max-w-7xl sm:mx-auto px-4 sm:px-6 pt-6 sm:pt-8 pb-32 sm:pb-12">
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
