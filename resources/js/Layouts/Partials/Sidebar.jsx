import { Link, usePage } from '@inertiajs/react';
import {
    LayoutDashboard,
    Users,
    PiggyBank,
    HandCoins,
    ClipboardCheck,
    FileBarChart,
} from 'lucide-react';

const menuGroups = [
    {
        label: 'Utama',
        items: [
            { label: 'Dashboard', href: route('dashboard'), icon: LayoutDashboard, roles: ['staff', 'admin'] },
        ],
    },
    {
        label: 'Data & Transaksi',
        items: [
            { label: 'Anggota', href: '#', icon: Users, roles: ['staff', 'admin'] },
            { label: 'Simpanan', href: '#', icon: PiggyBank, roles: ['staff', 'admin'] },
            { label: 'Pinjaman', href: '#', icon: HandCoins, roles: ['staff', 'admin'] },
        ],
    },
    {
        label: 'Approval & Laporan',
        items: [
            { label: 'Approval Pinjaman', href: '#', icon: ClipboardCheck, roles: ['admin'] },
            { label: 'Laporan', href: '#', icon: FileBarChart, roles: ['admin'] },
        ],
    },
];

export default function Sidebar() {
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
        <aside className="w-64 bg-slate-900 text-slate-100 min-h-screen flex flex-col">
            <div className="px-6 py-5 flex items-center gap-2 border-b border-slate-800">
                <div className="w-8 h-8 rounded-lg bg-emerald-500 flex items-center justify-center font-bold text-slate-900">
                    K
                </div>
                <span className="text-base font-semibold tracking-tight">
                    Koperasi App
                </span>
            </div>

            <nav className="flex-1 px-3 py-4 space-y-6 overflow-y-auto">
                {visibleGroups.map((group) => (
                    <div key={group.label}>
                        <p className="px-3 mb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                            {group.label}
                        </p>
                        <div className="space-y-1">
                            {group.items.map((item) => {
                                const Icon = item.icon;
                                const isCurrent = item.href !== '#' && route().current(item.href.split('/').pop());

                                return (
                                    <Link
                                        key={item.label}
                                        href={item.href}
                                        className={`flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors ${
                                            isCurrent
                                                ? 'bg-emerald-500 text-slate-900'
                                                : 'text-slate-300 hover:bg-slate-800 hover:text-white'
                                        }`}
                                    >
                                        <Icon size={18} strokeWidth={2} />
                                        {item.label}
                                    </Link>
                                );
                            })}
                        </div>
                    </div>
                ))}
            </nav>

            <div className="px-6 py-4 border-t border-slate-800 text-xs text-slate-500">
                Sistem Koperasi Simpan Pinjam
            </div>
        </aside>
    );
}