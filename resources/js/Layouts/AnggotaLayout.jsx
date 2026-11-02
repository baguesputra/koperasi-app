import { Link, usePage } from '@inertiajs/react';
import { useState, useRef, useEffect } from 'react';
import { User, LogOut, ChevronDown, Home, History, Plus, Info, ShieldCheck } from 'lucide-react';
import Panduan from '@/Pages/Portal/Panduan';
import MobileHeader from '@/Components/ui/MobileHeader';
import SecurityGuard from '@/Components/security/SecurityGuard';

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
            if (e.key !== 'Escape') return;
            if (showPanduan) {
                setShowPanduan(false);
            }
            setDropdownOpen(false);
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
                aria-current={active ? 'page' : undefined}
                className="shell-tab"
            >
                <Icon size={22} strokeWidth={active ? 2.5 : 2} aria-hidden="true" />
                <span>{label}</span>
            </Link>
        );
    }

    return (
        <div className="min-h-screen bg-slate-50 flex flex-col">
            {/* One flush bar for the portal: identity left, the account right. */}
            {!hideNavbar && (
            <header className={`shell-nav ${mobileTitle ? 'hidden sm:block' : ''}`}>
                <div className="shell-nav__inner">
                    <Link href={route('portal.dashboard')} className="shell-brand">
                        <img
                            src="/images/logo-ui.webp"
                            alt="Koperasi App"
                            width="24"
                            height="24"
                            className="shell-brand__logo"
                        />
                        <span className="shell-brand__name">Koperasi App</span>
                    </Link>

                    <div className="relative" ref={dropdownRef}>
                        <button
                            type="button"
                            onClick={() => setDropdownOpen(!dropdownOpen)}
                            aria-expanded={dropdownOpen}
                            aria-haspopup="menu"
                            className="shell-account"
                        >
                            {auth.user?.foto_url && !avatarGagal ? (
                                <span className="shell-account__avatar-slot">
                                    <span className="shell-account__initial" aria-hidden="true">
                                        {initial}
                                    </span>
                                    <img
                                        src={auth.user.foto_url}
                                        alt={auth.user.name}
                                        loading="lazy"
                                        referrerPolicy="no-referrer"
                                        onError={() => setAvatarGagal(true)}
                                        className="shell-account__avatar"
                                    />
                                </span>
                            ) : (
                                <span className="shell-account__initial" aria-hidden="true">
                                    {initial}
                                </span>
                            )}
                            <span className="shell-account__name">{firstName}</span>
                            <ChevronDown
                                size={14}
                                aria-hidden="true"
                                className="shell-account__chevron"
                            />
                        </button>

                        {dropdownOpen && (
                            <div className="shell-menu" role="menu">
                                <div className="shell-menu__head">
                                    <p className="shell-menu__status">Anggota aktif</p>
                                    <p className="shell-menu__name">{auth.user?.name}</p>
                                    <p className="shell-menu__email">{auth.user?.email}</p>
                                </div>

                                <div className="shell-menu__group">
                                    {auth.user?.anggota_status && (
                                        <Link
                                            href={route('portal.profil')}
                                            onClick={() => setDropdownOpen(false)}
                                            role="menuitem"
                                            className="shell-menu__item"
                                        >
                                            <User size={15} aria-hidden="true" />
                                            Profil Saya
                                        </Link>
                                    )}
                                    {isPengurus && (
                                        <Link
                                            href={route('dashboard')}
                                            onClick={() => setDropdownOpen(false)}
                                            role="menuitem"
                                            className="shell-menu__item shell-menu__item--accent"
                                        >
                                            <ShieldCheck size={15} aria-hidden="true" />
                                            Dashboard Koperasi
                                        </Link>
                                    )}
                                    <button
                                        type="button"
                                        role="menuitem"
                                        onClick={() => {
                                            setDropdownOpen(false);
                                            setShowPanduan(true);
                                        }}
                                        className="shell-menu__item"
                                    >
                                        <Info size={15} aria-hidden="true" />
                                        Tata Cara & Panduan
                                    </button>
                                </div>

                                <div className="shell-menu__foot">
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
                                            className="shell-menu__item shell-menu__item--danger"
                                        >
                                            <LogOut size={15} aria-hidden="true" />
                                            Keluar
                                        </button>
                                    </form>
                                </div>
                            </div>
                        )}
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
                className="shell-tabs sm:hidden"
            >
                <div className="relative">
                    <div className="grid grid-cols-4 items-end px-2 pt-1 pb-1">
                        <TabItem href={route('portal.dashboard')} active={isCurrentRoute('portal.dashboard')} icon={Home} label="Beranda" />
                        <TabItem href={route('portal.riwayat')} active={isCurrentRoute('portal.riwayat')} icon={History} label="Riwayat" />
                        <div className="flex justify-center">
                            <Link
                                href={route('portal.pinjaman.create')}
                                aria-label="Ajukan pinjaman"
                                className="shell-fab"
                            >
                                <Plus size={24} strokeWidth={2.75} aria-hidden="true" />
                                <span className="shell-fab__label">Ajukan</span>
                            </Link>
                        </div>
                        <TabItem href={route('portal.profil')} active={isCurrentRoute('portal.profil')} icon={User} label="Profil" />
                    </div>
                </div>
            </nav>
            )}

            {anggotaAktif && showPanduan && <Panduan onClose={() => setShowPanduan(false)} />}
            <SecurityGuard />
        </div>
    );
}
