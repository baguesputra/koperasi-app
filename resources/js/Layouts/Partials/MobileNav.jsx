import { Link, usePage, router } from '@inertiajs/react';
import { Menu, X, ChevronDown, ChevronUp, LogOut } from 'lucide-react';
import { useState, useEffect } from 'react';
import Dropdown from '@/Components/Dropdown';
import { Transition } from '@headlessui/react';

const menuGroups = [
    [
        { label: 'Dashboard', routeName: 'dashboard', roles: ['admin', 'bendahara', 'ketua_koperasi'] },
    ],
    [
        { label: 'Anggota', routeName: 'anggota.index', roles: ['admin', 'bendahara', 'ketua_koperasi'] },
        { label: 'Simpanan', routeName: null, roles: ['admin', 'bendahara'] },
        { label: 'Pinjaman', routeName: 'pinjaman.index', roles: ['admin', 'bendahara', 'ketua_koperasi'] },
    ],
    [
        { label: 'Approval Pinjaman', routeName: 'approval-pinjaman', roles: ['bendahara', 'ketua_koperasi'] },
        { label: 'Angsuran', routeName: 'bendahara.angsuran.index', roles: ['bendahara'] },
        { label: 'Konfirmasi Simpanan', routeName: 'bendahara.simpanan.index', roles: ['bendahara'] },
        { label: 'Kas Koperasi', routeName: 'kas-koperasi.index', roles: ['admin', 'bendahara', 'ketua_koperasi'] },
        { label: 'Laporan', routeName: null, roles: ['admin', 'bendahara', 'ketua_koperasi'] },
        { label: 'Pengaturan', routeName: 'pengaturan.index', roles: ['admin'] },
    ],
];

function resolve(item, userRoles) {
    if (item.routeName === 'approval-pinjaman') {
        const actualRoute = userRoles.includes('bendahara') ? 'bendahara.pinjaman.index' : 'ketua.pinjaman.index';
        return { href: route(actualRoute), isActive: route().current(actualRoute) || route().current('bendahara.pinjaman.show') || route().current('ketua.pinjaman.show') };
    }

    if (!item.routeName) {
        return { href: '#', isActive: false };
    }

    return { href: route(item.routeName), isActive: route().current(item.routeName) };
}

export default function MobileNav() {
    const { auth } = usePage().props;
    const userRoles = auth.user?.roles ?? [];
    const initial = auth.user?.name?.charAt(0)?.toUpperCase() ?? '?';
    const [isOpen, setIsOpen] = useState(false);
    const [expandedGroup, setExpandedGroup] = useState(null);

    useEffect(() => {
        const handler = () => setIsOpen(false);
        router.on('finish', handler);
        return () => router.off('finish', handler);
    }, []);

    const visibleGroups = menuGroups
        .map((items) => items.filter((item) => item.roles.some((r) => userRoles.includes(r))))
        .filter((items) => items.length > 0);

    if (visibleGroups.length === 0) return null;

    return (
        <>
<button
                 onClick={() => setIsOpen(!isOpen)}
                 className="lg:hidden inline-flex items-center justify-center min-h-[44px] min-w-[44px] p-2 text-slate-500 hover:text-slate-700 hover:bg-slate-100 rounded-lg"
                 aria-label={isOpen ? 'Tutup menu' : 'Buka menu'}
                 aria-expanded={isOpen}
             >
                {isOpen ? <X size={24} /> : <Menu size={24} />}
            </button>

            <Transition show={isOpen} leave="duration-150">
                <div className="lg:hidden fixed inset-0 z-40 bg-slate-900/50" onClick={() => setIsOpen(false)} />
            </Transition>

            <Transition show={isOpen} leave="duration-200">
                <Transition.Child
                    enter="ease-out duration-300"
                    enterFrom="translate-x-full"
                    enterTo="translate-x-0"
                    leave="ease-in duration-200"
                    leaveFrom="translate-x-0"
                    leaveTo="translate-x-full"
                >
                    <div className="lg:hidden fixed inset-y-0 right-0 z-50 w-full max-w-sm bg-white shadow-xl overflow-y-auto pt-[env(safe-area-inset-top)] pb-[env(safe-area-inset-bottom)]">
                    <div className="flex items-center justify-between px-4 py-3 border-b border-slate-100">
                        <div className="flex items-center gap-2.5">
                            <img src="/images/logo.png" alt="Koperasi App" className="w-8 h-8" />
                            <span className="font-bold text-base tracking-tight text-slate-800">Koperasi App</span>
                        </div>
<button
                             onClick={() => setIsOpen(false)}
                             className="inline-flex items-center justify-center w-11 h-11 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors"
                             aria-label="Tutup menu"
                         >
                            <X size={20} />
                        </button>
                    </div>

                    <nav className="px-4 py-4 space-y-4">
                        {visibleGroups.map((items, groupIndex) => (
                            <div key={groupIndex} className="space-y-1">
                                {groupIndex > 0 && <hr className="border-slate-100" />}

                                {items.map((item) => {
                                    const { href, isActive } = resolve(item, userRoles);
                                    const hasSubmenu = item.routeName === 'approval-pinjaman';

                                    if (hasSubmenu) {
                                        const subItems = [
                                            { label: 'Menunggu Tinjauan', routeName: 'bendahara.pinjaman.index', roles: ['bendahara'] },
                                            { label: 'Menunggu Approval', routeName: 'ketua.pinjaman.index', roles: ['ketua_koperasi'] },
                                        ].filter((si) => si.roles.some((r) => userRoles.includes(r)));

                                        const isGroupExpanded = expandedGroup === groupIndex;

                                        return (
                                            <div key={item.label}>
<button
                                                     onClick={() => setExpandedGroup(isGroupExpanded ? null : groupIndex)}
                                                     className={`w-full flex items-center justify-between min-h-[44px] px-3 py-3 rounded-lg text-sm font-semibold transition-colors ${
                                                         isActive || isGroupExpanded ? 'bg-brand-green text-white' : 'text-slate-600 hover:bg-slate-100'
                                                     }`}
                                                     aria-expanded={isGroupExpanded}
                                                 >
                                                    <span>{item.label}</span>
                                                    {isGroupExpanded ? <ChevronUp size={16} className="text-white" /> : <ChevronDown size={16} />}
                                                </button>

                                                <Transition.Child
                    enter="ease-out duration-300"
                    enterFrom="translate-y-4 scale-95"
                    enterTo="translate-y-0 scale-100"
                    leave="ease-in duration-150"
                    leaveFrom="translate-y-0 scale-100"
                    leaveTo="translate-y-4 scale-95"
                >
                    <div className="mt-1 ml-3 space-y-1 border-l-2 border-slate-100 pl-3">
                        {subItems.map((si) => {
                            const { href: subHref, isActive: subActive } = resolve(si, userRoles);
                            return (
<Link
                                                     key={si.label}
                                                     href={subHref}
                                                     onClick={() => setIsOpen(false)}
                                                     className={`block min-h-[44px] px-3 py-3 rounded-lg text-sm font-medium transition-colors ${
                                                         subActive ? 'bg-brand-green/10 text-brand-green' : 'text-slate-600 hover:bg-slate-50'
                                                     }`}
                                                 >
                                    {si.label}
                                </Link>
                            );
                        })}
                    </div>
                </Transition.Child>
                                            </div>
                                        );
                                    }

                                    return (
                                        <Link
                                            key={item.label}
                                            href={href}
                                            onClick={() => setIsOpen(false)}
                                            className={`block px-3 py-2.5 rounded-lg text-sm font-semibold transition-colors ${
                                                isActive ? 'bg-brand-green text-white' : 'text-slate-600 hover:bg-slate-100'
                                            }`}
                                        >
                                            {item.label}
                                        </Link>
                                    );
                                })}
                            </div>
                        ))}

                        <hr className="border-slate-100" />

                        <div className="flex items-center gap-3 px-3 py-2">
                            <div className="w-9 h-9 rounded-full bg-brand-green text-white flex items-center justify-center text-sm font-bold">
                                {initial}
                            </div>
                            <div className="flex-1 min-w-0">
                                <p className="text-sm font-semibold text-slate-800 truncate">{auth.user?.name}</p>
                                <p className="text-xs text-slate-400 capitalize truncate">
                                    {auth.user?.roles?.[0]?.replace('_', ' ') ?? '-'}
                                </p>
                            </div>
                        </div>

<form method="POST" action={route('logout')}>
                             <input type="hidden" name="_token" value={document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')} />
                             <button
                                 type="submit"
                                 onClick={() => setIsOpen(false)}
                                 className="w-full flex items-center gap-3 min-h-[44px] px-3 py-3 rounded-lg text-sm font-semibold text-red-600 hover:bg-red-50 transition-colors"
                             >
                                 <LogOut size={18} />
                                 Keluar
                             </button>
                         </form>
                    </nav>
                </div>
            </Transition.Child>
        </Transition>
    </>
);
}