import { Link, usePage } from '@inertiajs/react';
import {
    LayoutDashboard, Users, PiggyBank, HandCoins,
    ClipboardCheck, CalendarCheck, HeartHandshake,
    Wallet, FileBarChart, 
} from 'lucide-react';

const menuGroups = [
    {
        label: 'Utama',
        items: [
            { label: 'Dashboard', routeName: 'dashboard', icon: LayoutDashboard, permission: null },
        ],
    },
    {
        label: 'Data',
        items: [
            { label: 'Anggota', routeName: 'anggota.index', icon: Users, permission: 'anggota.lihat' },
            { label: 'Simpanan', routeName: 'simpanan.index', icon: PiggyBank, permission: 'simpanan.lihat' },
            { label: 'Pinjaman', routeName: 'pinjaman.index', icon: HandCoins, permission: 'pinjaman.lihat' },
        ],
    },
    {
        label: 'Proses Bendahara',
        items: [
            { label: 'Approval Pinjaman', routeName: 'bendahara.pinjaman.index', icon: ClipboardCheck, permission: 'pinjaman.tinjau-bendahara' },
            { label: 'Angsuran', routeName: 'bendahara.angsuran.index', icon: CalendarCheck, permission: 'angsuran.konfirmasi' },
            { label: 'Konfirmasi Simpanan', routeName: 'bendahara.simpanan.index', icon: HeartHandshake, permission: 'simpanan.konfirmasi' },
        ],
    },
    {
        label: 'Proses Ketua',
        items: [
            { label: 'Approval Pinjaman', routeName: 'ketua.pinjaman.index', icon: ClipboardCheck, permission: 'pinjaman.approve-ketua' },
        ],
    },
    {
        label: 'Keuangan',
        items: [
            { label: 'Kas Koperasi', routeName: 'kas-koperasi.index', icon: Wallet, permission: 'kas.lihat' },
            { label: 'Laporan', routeName: null, icon: FileBarChart, permission: 'laporan.lihat' },
        ],
    },
];

export default function Sidebar() {
    const { auth, notifications } = usePage().props;
    const userPermissions = auth.user?.permissions ?? [];
    const badgeAngka = {
        'bendahara.pinjaman.index': notifications?.menunggu_tinjauan_bendahara ?? 0,
        'ketua.pinjaman.index': notifications?.menunggu_approval_ketua ?? 0,
    };

    function bisaAkses(permission) {
        return permission === null || userPermissions.includes(permission);
    }

    const visibleGroups = menuGroups
        .map((group) => ({
            ...group,
            items: group.items.filter((item) => bisaAkses(item.permission)),
        }))
        .filter((group) => group.items.length > 0);

    return (
        <aside className="w-64 bg-white border-r border-slate-200 min-h-screen flex flex-col">
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
                                const badge = item.routeName ? badgeAngka[item.routeName] : 0;

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
                                        <span className="flex-1 truncate">{item.label}</span>
                                        {badge > 0 && (
                                            <span className="shrink-0 min-w-[20px] h-5 px-1.5 rounded-full bg-red-500 text-white flex items-center justify-center text-xs font-bold">
                                                {badge}
                                            </span>
                                        )}
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