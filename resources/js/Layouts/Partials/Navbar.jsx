import { Link, usePage } from '@inertiajs/react';

const menuGroups = [
    {
        label: 'Utama',
        items: [
            { label: 'Dashboard', href: route('dashboard'), roles: ['staff', 'admin'] },
        ],
    },
    {
        label: 'Data & Transaksi',
        items: [
            { label: 'Anggota', href: '#', roles: ['staff', 'admin'] },
            { label: 'Simpanan', href: '#', roles: ['staff', 'admin'] },
            { label: 'Pinjaman', href: '#', roles: ['staff', 'admin'] },
        ],
    },
    {
        label: 'Approval & Laporan',
        items: [
            { label: 'Approval Pinjaman', href: '#', roles: ['admin'] },
            { label: 'Laporan', href: '#', roles: ['admin'] },
        ],
    },
];

export default function Navbar() {
    const { auth } = usePage().props;
    const userRoles = auth.user?.roles ?? [];

    const visibleGroups = menuGroups
        .map((group) => ({
            ...group,
            items: group.items.filter((item) =>
                item.roles.some((role) => userRoles.includes(role))
            ),
        }))
        .filter((group) => group.items.length > 0);

    return (
        <nav className="bg-slate-900 text-slate-100 px-6 py-3.5 flex items-center gap-8">
            <div className="flex items-center gap-2">
                <div className="w-7 h-7 rounded-md bg-emerald-500 flex items-center justify-center font-bold text-slate-900 text-sm">
                    K
                </div>
                <span className="font-semibold text-sm">Koperasi App</span>
            </div>

            <div className="flex items-center gap-6">
                {visibleGroups.map((group) => (
                    <div key={group.label} className="flex items-center gap-1">
                        <span className="text-[10px] uppercase tracking-wider text-slate-500 mr-1 hidden xl:inline">
                            {group.label}
                        </span>
                        {group.items.map((item) => (
                            <Link
                                key={item.label}
                                href={item.href}
                                className="text-sm px-3 py-1.5 rounded-md text-slate-200 hover:bg-slate-800 hover:text-white transition-colors font-medium"
                            >
                                {item.label}
                            </Link>
                        ))}
                    </div>
                ))}
            </div>
        </nav>
    );
}