import { Link, usePage } from '@inertiajs/react';
import {
    LayoutDashboard, Users, PiggyBank, HandCoins,
    ClipboardCheck, CalendarCheck, HeartHandshake,
    Wallet, FileBarChart, Receipt, TrendingUp,
    ChevronLeft, ChevronRight,
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
        label: 'Pemrosesan',
        items: [
            { label: 'Tinjau Pinjaman', routeName: 'bendahara.pinjaman.index', icon: ClipboardCheck, permission: 'pinjaman.tinjau-bendahara' },
            { label: 'Persetujuan Pinjaman', routeName: 'ketua.pinjaman.index', icon: ClipboardCheck, permission: 'pinjaman.approve-ketua' },
            { label: 'Angsuran', routeName: 'bendahara.angsuran.index', icon: CalendarCheck, permission: 'angsuran.konfirmasi' },
            { label: 'Konfirmasi Simpanan', routeName: 'bendahara.simpanan.index', icon: HeartHandshake, permission: 'simpanan.konfirmasi' },
            { label: 'Pengajuan Limit', routeName: 'ketua.pengajuan-limit.index', icon: TrendingUp, permission: 'pinjaman.approve-ketua' },
        ],
    },
    {
        label: 'Keuangan',
        items: [
            { label: 'Kas Koperasi', routeName: 'kas-koperasi.index', icon: Wallet, permission: 'kas.lihat' },
            { label: 'Pengeluaran', routeName: 'pengeluaran.index', icon: Receipt, permission: 'kas.lihat' },
            { label: 'Laporan', routeName: null, icon: FileBarChart, permission: 'laporan.lihat', disabled: true },
        ],
    },
];

export default function Sidebar({ collapsed = false, onToggle }) {
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
        <aside className={`${collapsed ? 'w-16' : 'w-64'} h-full bg-white border border-slate-200 shadow-sm rounded-2xl flex flex-col transition-all duration-300 overflow-hidden`}>
            <div className={`h-16 flex items-center shrink-0 overflow-hidden ${collapsed ? 'justify-center px-2' : 'justify-start px-5 gap-2.5'}`}>
                <img src="/images/logo.png" alt="Koperasi App" className="w-8 h-8 shrink-0" />
                <span className={`text-base font-bold text-slate-800 truncate transition-all duration-300 ${collapsed ? 'opacity-0 max-w-0' : 'opacity-100 max-w-[160px]'}`}>Koperasi App</span>
            </div>

            <nav className="flex-1 px-3 py-5 space-y-6 overflow-y-auto scrollbar-hide">
                {visibleGroups.map((group) => (
                    <div key={group.label}>
                        <p className={`px-3 mb-2 text-[11px] font-bold uppercase tracking-wider text-slate-400 ${collapsed ? 'hidden' : ''}`}>
                            {group.label}
                        </p>
                        <div className="space-y-1">
                            {group.items.map((item) => {
                                const Icon = item.icon;
                                const href = item.routeName ? route(item.routeName) : '#';
                                const isActive = item.routeName && route().current(item.routeName);
                                const badge = item.routeName ? badgeAngka[item.routeName] : 0;

                                if (item.disabled) {
                                    return (
                                        <div
                                            key={item.label + group.label}
                                            title={collapsed ? item.label : 'Segera'}
                                            className={`flex items-center ${collapsed ? 'gap-0 justify-center' : 'gap-3'} px-3 py-2.5 rounded-xl text-sm font-semibold text-slate-300 cursor-not-allowed`}
                                        >
                                            <Icon size={18} strokeWidth={2} />
                                            <span className={`flex-1 truncate transition-all duration-300 whitespace-nowrap overflow-hidden ${collapsed ? 'opacity-0 max-w-0' : 'opacity-100 max-w-[200px]'}`}>{item.label}</span>
                                        </div>
                                    );
                                }

                                return (
                                    <Link
                                        key={item.label + group.label}
                                        href={href}
                                        title={collapsed ? item.label : undefined}
                                        className={`flex items-center ${collapsed ? 'gap-0 justify-center' : 'gap-3'} px-3 py-2.5 rounded-xl text-sm font-semibold transition-colors ${
                                            isActive
                                                ? 'bg-brand-navy text-white'
                                                : 'text-slate-600 hover:bg-slate-50'
                                        }`}
                                    >
                                        <span className="relative shrink-0">
                                            <Icon size={18} strokeWidth={2} className={isActive ? 'text-brand-green' : ''} />
                                            {badge > 0 && (
                                                collapsed ? (
                                                    <span className="absolute -top-1 -right-1 w-2.5 h-2.5 rounded-full bg-red-500" />
                                                ) : (
                                                    <span className="absolute -top-1.5 -right-1.5 min-w-[16px] h-4 px-1 rounded-full bg-red-500 text-white flex items-center justify-center text-[10px] font-bold">
                                                        {badge}
                                                    </span>
                                                )
                                            )}
                                        </span>
                                        <span className={`flex-1 truncate transition-all duration-300 whitespace-nowrap overflow-hidden ${collapsed ? 'opacity-0 max-w-0' : 'opacity-100 max-w-[200px]'}`}>{item.label}</span>
                                    </Link>
                                );
                            })}
                        </div>
                    </div>
                ))}
            </nav>

            {!collapsed ? (
                <div className="px-5 py-4 border-t border-slate-100 text-xs text-slate-400 shrink-0 flex items-center justify-between">
                    <span>Sistem Koperasi Simpan Pinjam</span>
                    <button
                        type="button"
                        onClick={onToggle}
                        title="Ciutkan sidebar"
                        className="text-slate-400 hover:text-brand-navy transition-colors shrink-0"
                    >
                        <ChevronLeft size={18} />
                    </button>
                </div>
            ) : (
                <button
                    type="button"
                    onClick={onToggle}
                    title="Buka sidebar"
                    className="mx-auto mb-3 flex items-center justify-center w-9 h-9 rounded-xl text-slate-400 hover:text-brand-navy hover:bg-slate-50 transition-colors shrink-0"
                >
                    <ChevronRight size={18} />
                </button>
            )}
        </aside>
    );
}