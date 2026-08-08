import { Link, usePage } from '@inertiajs/react';
import { LogOut } from 'lucide-react';

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
        { label: 'Kas Koperasi', routeName: 'kas-koperasi.index', roles: ['admin', 'bendahara', 'ketua_koperasi'] },
        { label: 'Laporan', routeName: null, roles: ['admin', 'bendahara', 'ketua_koperasi'] },
        { label: 'Pengaturan', routeName: 'pengaturan.index', roles: ['admin'] },
    ],
];

export default function Navbar() {
    const { auth } = usePage().props;
    const userRoles = auth.user?.roles ?? [];
    const initial = auth.user?.name?.charAt(0)?.toUpperCase() ?? '?';

    function resolve(item) {
        if (item.routeName === 'approval-pinjaman') {
            const actualRoute = userRoles.includes('bendahara') ? 'bendahara.pinjaman.index' : 'ketua.pinjaman.index';
            return { href: route(actualRoute), isActive: route().current(actualRoute) || route().current('bendahara.pinjaman.show') || route().current('ketua.pinjaman.show') };
        }

        if (!item.routeName) {
            return { href: '#', isActive: false };
        }

        return { href: route(item.routeName), isActive: route().current(item.routeName) };
    }

    const visibleGroups = menuGroups
        .map((items) => items.filter((item) => item.roles.some((r) => userRoles.includes(r))))
        .filter((items) => items.length > 0);

    return (
        <nav className="bg-white border-b border-slate-200 px-6 py-3 flex items-center justify-between">
            <div className="flex items-center">
                <div className="flex items-center gap-2.5 mr-8">
                    <img src="/images/logo.png" alt="Koperasi App" className="w-8 h-8" />
                    <span className="font-bold text-base tracking-tight text-slate-800 hidden sm:inline">
                        Koperasi App
                    </span>
                </div>

                <div className="flex items-center">
                    {visibleGroups.map((items, groupIndex) => (
                        <div key={groupIndex} className="flex items-center">
                            {groupIndex > 0 && <span className="w-0.5 h-6 bg-slate-300 mx-3" />}
                            {items.map((item) => {
                                const { href, isActive } = resolve(item);
                                return (
                                    <Link
                                        key={item.label}
                                        href={href}
                                        className={`text-sm font-semibold px-3.5 py-2 rounded-lg transition-colors ${
                                            isActive ? 'bg-brand-green text-white' : 'text-slate-600 hover:bg-slate-100'
                                        }`}
                                    >
                                        {item.label}
                                    </Link>
                                );
                            })}
                        </div>
                    ))}
                </div>
            </div>

            <div className="flex items-center gap-4">
                <div className="w-9 h-9 rounded-full bg-brand-green text-white flex items-center justify-center text-sm font-bold">
                    {initial}
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
            </div>
        </nav>
    );
}