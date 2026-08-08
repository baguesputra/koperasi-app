import { Link, usePage } from '@inertiajs/react';
import { LogOut } from 'lucide-react';

const menuGroups = [
    [
        { label: 'Dashboard', href: 'dashboard', roles: ['admin', 'bendahara', 'ketua_koperasi'] },
    ],
    [
        { label: 'Anggota', href: 'anggota.index', roles: ['admin', 'bendahara', 'ketua_koperasi'] },
        { label: 'Simpanan', href: '#', roles: ['admin', 'bendahara'] },
        { label: 'Pinjaman', href: '#', roles: ['admin', 'bendahara', 'ketua_koperasi'] },
    ],
    [
        { label: 'Approval Pinjaman', href: 'approval-pinjaman', roles: ['bendahara', 'ketua_koperasi'] },
        { label: 'Laporan', href: '#', roles: ['admin', 'bendahara', 'ketua_koperasi'] },
        { label: 'Pengaturan', href: 'pengaturan.index', roles: ['admin'] },
    ],
];

export default function Navbar() {
    const { auth } = usePage().props;
    const userRoles = auth.user?.roles ?? [];
    const initial = auth.user?.name?.charAt(0)?.toUpperCase() ?? '?';

    function resolveHref(item) {
        if (item.href === '#') return '#';

        // Kasus khusus: "Approval Pinjaman" beda route name tergantung role
        if (item.href === 'approval-pinjaman') {
            return userRoles.includes('bendahara')
                ? route('bendahara.pinjaman.index')
                : route('ketua.pinjaman.index');
        }

        return route(item.href);
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
                            {groupIndex > 0 && (
                                <span className="w-0.5 h-6 bg-slate-300 mx-3" />
                            )}
                            {items.map((item) => {
                                const href = resolveHref(item);
                                const isActive = href !== '#' && route().current(item.href.includes('.') ? item.href : undefined);

                                return (
                                    <Link
                                        key={item.label}
                                        href={href}
                                        className={`text-sm font-semibold px-3.5 py-2 rounded-lg transition-colors ${
                                            isActive
                                                ? 'bg-brand-green text-white'
                                                : 'text-slate-600 hover:bg-slate-100'
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