import { Link, usePage } from '@inertiajs/react';

const menuGroups = [
    [
        { label: 'Dashboard', href: route('dashboard'), roles: ['admin', 'bendahara', 'ketua_koperasi'] },
    ],
    [
        { label: 'Anggota', href: '#', roles: ['admin'] },
        { label: 'Simpanan', href: '#', roles: ['admin', 'bendahara'] },
        { label: 'Pinjaman', href: '#', roles: ['admin', 'bendahara', 'ketua_koperasi'] },
    ],
    [
        { label: 'Approval Pinjaman', href: '#', roles: ['bendahara', 'ketua_koperasi'] },
        { label: 'Laporan', href: '#', roles: ['admin', 'bendahara', 'ketua_koperasi'] },
    ],
];

export default function Navbar() {
    const { auth } = usePage().props;
    const userRoles = auth.user?.roles ?? [];

    const visibleGroups = menuGroups
        .map((items) => items.filter((item) => item.roles.some((r) => userRoles.includes(r))))
        .filter((items) => items.length > 0);

    return (
        <nav className="bg-white border-b border-slate-200 px-6 py-3 flex items-center">
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
                            <span className="w-px h-5 bg-slate-200 mx-3" />
                        )}
                        {items.map((item) => {
                            const isActive = item.href !== '#' && route().current(item.href);
                            return (
                                <Link
                                    key={item.label}
                                    href={item.href}
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
        </nav>
    );
}