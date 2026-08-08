import { Link, usePage } from '@inertiajs/react';
import {
    LayoutDashboard, Users, PiggyBank, HandCoins,
    ClipboardCheck, CalendarCheck, HeartHandshake,
    Wallet, FileBarChart, Settings,
} from 'lucide-react';

const menuGroups = [
    {
        label: 'Utama',
        items: [
            { label: 'Dashboard', routeName: 'dashboard', icon: LayoutDashboard, roles: ['admin', 'bendahara', 'ketua_koperasi'] },
        ],
    },
    {
        label: 'Data',
        items: [
            { label: 'Anggota', routeName: 'anggota.index', icon: Users, roles: ['admin', 'bendahara', 'ketua_koperasi'] },
            { label: 'Simpanan', routeName: 'simpanan.index', icon: PiggyBank, roles: ['admin', 'bendahara', 'ketua_koperasi'] },
            { label: 'Pinjaman', routeName: 'pinjaman.index', icon: HandCoins, roles: ['admin', 'bendahara', 'ketua_koperasi'] },
        ],
    },
    {
        label: 'Proses Bendahara',
        items: [
            { label: 'Approval Pinjaman', routeName: 'bendahara.pinjaman.index', icon: ClipboardCheck, roles: ['bendahara'] },
            { label: 'Angsuran', routeName: 'bendahara.angsuran.index', icon: CalendarCheck, roles: ['bendahara'] },
            { label: 'Konfirmasi Simpanan', routeName: 'bendahara.simpanan.index', icon: HeartHandshake, roles: ['bendahara'] },
        ],
    },
    {
        label: 'Proses Ketua',
        items: [
            { label: 'Approval Pinjaman', routeName: 'ketua.pinjaman.index', icon: ClipboardCheck, roles: ['ketua_koperasi'] },
        ],
    },
    {
        label: 'Keuangan',
        items: [
            { label: 'Kas Koperasi', routeName: 'kas-koperasi.index', icon: Wallet, roles: ['admin', 'bendahara', 'ketua_koperasi'] },
            { label: 'Laporan', routeName: null, icon: FileBarChart, roles: ['admin', 'bendahara', 'ketua_koperasi'] },
        ],
    },
    {
        label: 'Administrasi',
        items: [
            { label: 'Pengaturan', routeName: 'pengaturan.index', icon: Settings, roles: ['admin'] },
        ],
    },
];

export default function Sidebar() {
    const { auth } = usePage().props;
    const userRoles = auth.user?.roles ?? [];

    const visibleGroups = menuGroups
        .map((group) => ({
            ...group,
            items: group.items.filter((item) => item.roles.some((r) => userRoles.includes(r))),
        }))
        .filter((group) => group.items.length > 0);

    return (
        <aside className="w-64 bg-white border-r border-slate-200 min-h-screen flex flex-col">
            {/* Header logo - background putih, garis bawah navy tebal, sejajar dengan Topbar */}
            <div className="h-16 px-5 flex items-center gap-2.5 border-b-2 border-brand-navy shrink-0">
                <img src="/images/logo.png" alt="Koperasi App" className="w-8 h-8" />
                <span className="text-base font-bold text-slate-800">Koperasi App</span>
            </div>

            <nav className="flex-1 px-3 py-5 space-y-6 overflow-y-auto">
                {visibleGroups.map((group) => (
                    <div key={group.label}>
                        <p className="px-3 mb-2 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                            {group.label}
                        </p>
                        <div className="space-y-1">
                            {group.items.map((item) => {
                                const Icon = item.icon;
                                const href = item.routeName ? route(item.routeName) : '#';
                                const isActive = item.routeName && route().current(item.routeName);

                                return (
                                    <Link
                                        key={item.label + group.label}
                                        href={href}
                                        className={`flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-colors ${
                                            isActive
                                                ? 'bg-brand-navy text-white'
                                                : 'text-slate-600 hover:bg-slate-50'
                                        }`}
                                    >
                                        <Icon size={18} strokeWidth={2} className={isActive ? 'text-brand-green' : ''} />
                                        {item.label}
                                    </Link>
                                );
                            })}
                        </div>
                    </div>
                ))}
            </nav>

            <div className="px-5 py-4 border-t border-slate-100 text-xs text-slate-400 shrink-0">
                Sistem Koperasi Simpan Pinjam
            </div>
        </aside>
    );
}