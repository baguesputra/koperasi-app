import { Link, usePage } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import { usePrivacy } from '@/Contexts/PrivacyContext';
import PrivacyIcon from '@/Components/ui/PrivacyIcon';
import {
    LayoutDashboard, Users, PiggyBank, HandCoins,
    ClipboardCheck, CalendarCheck, HeartHandshake,
    Wallet, FileBarChart, TrendingUp, BookOpenText, Percent,
    ChevronLeft, ChevronRight, Repeat, Settings, LogOut
} from 'lucide-react';

const fokusRing = 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-green/40';

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
            { label: 'Verifikasi Awal Pinjaman', routeName: 'admin.pinjaman.index', icon: ClipboardCheck, permission: 'pinjaman.tinjau-admin' },
            { label: 'Tinjau Pinjaman', routeName: 'bendahara.pinjaman.index', icon: ClipboardCheck, permission: 'pinjaman.tinjau-bendahara' },
            { label: 'Persetujuan Pinjaman', routeName: 'ketua.pinjaman.index', icon: ClipboardCheck, permission: 'pinjaman.approve-ketua' },
            { label: 'Tinjau Perubahan Tenor', routeName: 'bendahara.percepatan.index', icon: Repeat, permission: 'pinjaman.tinjau-bendahara' },
            { label: 'Persetujuan Perubahan Tenor', routeName: 'ketua.percepatan.index', icon: Repeat, permission: 'pinjaman.approve-ketua' },
            { label: 'Angsuran', routeName: 'bendahara.angsuran.index', icon: CalendarCheck, permission: 'angsuran.konfirmasi' },
            { label: 'Konfirmasi Simpanan', routeName: 'bendahara.simpanan.index', icon: HeartHandshake, permission: 'simpanan.konfirmasi' },
            { label: 'Tinjau Limit', routeName: 'bendahara.pengajuan-limit.index', icon: TrendingUp, permission: 'limit.tinjau-bendahara' },
            { label: 'Pengajuan Limit', routeName: 'ketua.pengajuan-limit.index', icon: TrendingUp, permission: 'limit.approve-ketua' },
            { label: 'Aktivasi Anggota', routeName: 'ketua.aktivasi.index', icon: Users, permission: 'aktivasi.approve-ketua' },
        ],
    },
    {
        label: 'Keuangan',
        items: [
            { label: 'Kas Koperasi', routeName: 'kas-koperasi.index', icon: Wallet, permission: 'kas.lihat' },
            { label: 'Jurnal Kas', routeName: 'jurnal-kas.index', icon: BookOpenText, permission: 'jurnal.lihat' },
            { label: 'Pembagian SHU', routeName: 'shu.index', icon: Percent, permission: 'shu.lihat' },
            { label: 'Laporan', routeName: 'laporan.index', icon: FileBarChart, permission: 'laporan.lihat' },
        ],
    },
];

export default function Sidebar({ collapsed = false, expanding = false, onToggle }) {
    const { auth, notifications } = usePage().props;
    const { isPrivacyActive, togglePrivacy } = usePrivacy();
    const url = usePage().url;
    const tautanAktif = useRef(null);
    const tertutup = collapsed && !expanding;
    const userPermissions = auth.user?.permissions ?? [];
    const PENGATURAN_PERMISSIONS = [
        'pengaturan.kelola',
        'pengaturan.kebijakan.lihat',
        'pengaturan.kebijakan.kelola',
        'pengaturan.wa',
        'pengaturan.organisasi',
        'pengaturan.audit.lihat',
        'user.kelola',
    ];
    const bisaPengaturan = userPermissions.some((p) => PENGATURAN_PERMISSIONS.includes(p));
    const initial = auth.user?.name?.charAt(0)?.toUpperCase() ?? '?';
    const csrfToken = typeof document !== 'undefined'
        ? document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        : null;
    const badgeAngka = {
        'admin.pinjaman.index': notifications?.menunggu_tinjauan_admin ?? 0,
        'bendahara.pinjaman.index': notifications?.menunggu_tinjauan_bendahara ?? 0,
        'ketua.pinjaman.index': notifications?.menunggu_approval_ketua ?? 0,
        'bendahara.percepatan.index': notifications?.menunggu_perubahan_tenor_bendahara ?? 0,
        'ketua.percepatan.index': notifications?.menunggu_perubahan_tenor_ketua ?? 0,
        'bendahara.pengajuan-limit.index': notifications?.menunggu_pengajuan_limit_bendahara ?? 0,
        'ketua.pengajuan-limit.index': notifications?.menunggu_pengajuan_limit ?? 0,
        'ketua.aktivasi.index': notifications?.menunggu_aktivasi ?? 0,
    };

    function bisaAkses(permission) {
        return permission === null || userPermissions.includes(permission);
    }

    /** Aktif bila route persis sama atau satu modul (prefix sama). */
    function sedangAktif(routeName) {
        if (!routeName) return false;
        if (route().current(routeName)) return true;
        const prefix = routeName.replace(/\.index$/, '');
        return prefix !== routeName && route().current(`${prefix}.*`);
    }

    // Gulirkan menu aktif ke area terlihat tiap pindah halaman/reload.
    // block:'nearest' = diam bila sudah terlihat; tanpa animasi.
    useEffect(() => {
        if (!tertutup) {
            tautanAktif.current?.scrollIntoView({ block: 'nearest' });
        }
    }, [url, tertutup]);

    const visibleGroups = menuGroups
        .map((group) => ({
            ...group,
            items: group.items.filter((item) => bisaAkses(item.permission)),
        }))
        .filter((group) => group.items.length > 0);

    return (
        <aside className={`${tertutup ? 'w-16' : 'w-64'} h-full bg-white border border-slate-200 rounded-2xl flex flex-col transition-all duration-300 overflow-hidden ${expanding ? 'relative z-50 shadow-xl' : 'shadow-sm'}`}>
            <div className={`h-16 flex items-center shrink-0 overflow-hidden ${tertutup ? 'justify-center px-2' : 'justify-between px-4'}`}>
                {tertutup ? (
                    <button
                        type="button"
                        onClick={togglePrivacy}
                        title={isPrivacyActive ? 'Mode Privasi: Aktif (Klik untuk nonaktifkan • Ctrl+Shift+P)' : 'Mode Privasi: Nonaktif (Klik untuk aktifkan • Ctrl+Shift+P)'}
                        aria-label="Mode Privasi"
                        className="relative group p-1.5 rounded-xl hover:bg-slate-50 transition-colors"
                    >
                        <img src="/images/logo.png" alt="Koperasi App" className="w-8 h-8 shrink-0" />
                        <span
                            className={`absolute -top-0.5 -right-0.5 w-3 h-3 rounded-full border-2 border-white transition-all ${
                                isPrivacyActive ? 'bg-brand-green ring-2 ring-brand-green/30' : 'bg-slate-300'
                            }`}
                        />
                    </button>
                ) : (
                    <>
                        <div className="flex items-center gap-2.5 min-w-0">
                            <img src="/images/logo.png" alt="Koperasi App" className="w-8 h-8 shrink-0" />
                            <span className="text-base font-bold text-slate-800 truncate max-w-[135px]">
                                Koperasi App
                            </span>
                        </div>
                        <button
                            type="button"
                            onClick={togglePrivacy}
                            title={isPrivacyActive ? 'Mode Privasi: Aktif (Klik untuk nonaktifkan • Ctrl+Shift+P)' : 'Mode Privasi: Nonaktif (Klik untuk aktifkan • Ctrl+Shift+P)'}
                            aria-label="Toggle Mode Privasi"
                            aria-pressed={isPrivacyActive}
                            className={`relative inline-flex items-center justify-center w-8 h-8 rounded-lg hover:bg-slate-100 active:scale-95 transition-all duration-150 shrink-0 ${fokusRing}`}
                        >
                            <PrivacyIcon
                                key={isPrivacyActive ? 'active' : 'inactive'}
                                active={isPrivacyActive}
                                size={20}
                                className={isPrivacyActive ? 'animate-privacy-on' : 'opacity-65 hover:opacity-100'}
                            />
                        </button>
                    </>
                )}
            </div>

            <nav className="flex-1 px-3 py-5 space-y-6 overflow-y-auto scrollbar-hide">
                {visibleGroups.map((group) => (
                    <div key={group.label}>
                        <p className={`px-3 mb-2 text-[11px] font-bold uppercase tracking-wider text-slate-400 ${tertutup ? 'hidden' : ''}`}>
                            {group.label}
                        </p>
                        <div className="space-y-1">
                            {group.items.map((item) => {
                                const Icon = item.icon;
                                const href = item.routeName ? route(item.routeName) : '#';
                                const isActive = sedangAktif(item.routeName);
                                const badge = item.routeName ? badgeAngka[item.routeName] : 0;

                                if (item.disabled) {
                                    return (
                                        <div
                                            key={item.label + group.label}
                                            title={tertutup ? item.label : 'Segera'}
                                            className={`flex items-center ${tertutup ? 'gap-0 justify-center' : 'gap-3'} px-3 py-2.5 rounded-xl text-sm font-semibold text-slate-300 cursor-not-allowed`}
                                        >
                                            <Icon size={18} strokeWidth={2} />
                                            <span className={`flex-1 truncate transition-all duration-300 whitespace-nowrap overflow-hidden ${tertutup ? 'opacity-0 max-w-0' : 'opacity-100 max-w-[200px]'}`}>{item.label}</span>
                                        </div>
                                    );
                                }

                                return (
                                    <Link
                                        key={item.label + group.label}
                                        href={href}
                                        ref={isActive ? tautanAktif : undefined}
                                        title={tertutup ? item.label : undefined}
                                        className={`flex items-center ${tertutup ? 'gap-0 justify-center' : 'gap-3'} px-3 py-2.5 rounded-xl text-sm font-semibold transition-colors ${
                                            isActive
                                                ? 'bg-brand-navy text-white'
                                                : 'text-slate-600 hover:bg-slate-50'
                                        }`}
                                    >
                                        <span className="relative shrink-0">
                                            <Icon size={18} strokeWidth={2} className={isActive ? 'text-brand-green' : ''} />
                                            {badge > 0 && (
                                                tertutup ? (
                                                    <span className="absolute -top-1 -right-1 w-2.5 h-2.5 rounded-full bg-red-500" />
                                                ) : (
                                                    <span className="absolute -top-1.5 -right-1.5 min-w-[16px] h-4 px-1 rounded-full bg-red-500 text-white flex items-center justify-center text-[10px] font-bold">
                                                        {badge}
                                                    </span>
                                                )
                                            )}
                                        </span>
                                        <span className={`flex-1 truncate transition-all duration-300 whitespace-nowrap overflow-hidden ${tertutup ? 'opacity-0 max-w-0' : 'opacity-100 max-w-[200px]'}`}>{item.label}</span>
                                    </Link>
                                );
                            })}
                        </div>
                    </div>
                ))}
            </nav>


            {/* Profil & aksi akun */}
            {tertutup ? (
                <div
                    key="kaki-ciut"
                    className="border-t border-slate-100 px-2 py-4 shrink-0 flex flex-col items-center animate-sidebar-fade"
                >
                    <Link
                        href={route('profile.edit')}
                        title="Profil Saya"
                        className={`w-9 h-9 rounded-full bg-brand-green text-white flex items-center justify-center text-sm font-bold hover:opacity-90 transition-opacity ${fokusRing}`}
                    >
                        {initial}
                    </Link>
                </div>
            ) : (
                <div
                    key="kaki-buka"
                    className="border-t border-slate-100 px-4 py-3 shrink-0 flex items-center gap-1 animate-sidebar-fade"
                >
                    <Link
                        href={route('profile.edit')}
                        title="Profil Saya"
                        className={`flex items-center gap-2.5 min-w-0 flex-1 rounded-xl p-1 -m-1 hover:bg-slate-50 transition-colors ${fokusRing}`}
                    >
                        <span className="w-9 h-9 rounded-full bg-brand-green text-white flex items-center justify-center text-sm font-bold shrink-0">
                            {initial}
                        </span>
                        <span className="min-w-0 leading-tight">
                            <span className="block text-sm font-semibold text-slate-800 truncate">{auth.user?.name}</span>
                            <span className="block text-xs text-slate-400 capitalize truncate">
                                {auth.user?.roles?.[0] === 'super_admin' ? 'Super Admin' : (auth.user?.roles?.[0]?.replace('_', ' ') ?? '-')}
                            </span>
                        </span>
                    </Link>
                    {bisaPengaturan && (
                        <Link
                            href={route('pengaturan.index')}
                            title="Pengaturan"
                            className={`p-2 rounded-lg text-slate-400 hover:text-brand-navy hover:bg-slate-50 transition-colors shrink-0 ${fokusRing}`}
                        >
                            <Settings size={18} />
                        </Link>
                    )}
                    <form method="POST" action={route('logout')} className="shrink-0">
                        <input type="hidden" name="_token" value={csrfToken} />
                        <button
                            type="submit"
                            title="Keluar"
                            className={`p-2 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition-colors ${fokusRing}`}
                        >
                            <LogOut size={18} />
                        </button>
                    </form>
                </div>
            )}

            {!tertutup ? (
                <div className="px-5 py-4 border-t border-slate-100 text-xs text-slate-400 shrink-0 flex items-center justify-between">
                    <span>Sistem Koperasi Simpan Pinjam</span>
                    <button
                        type="button"
                        onClick={onToggle}
                        title={expanding ? 'Semuhkan sidebar (konten ikut menyesuaikan)' : 'Ciutkan sidebar'}
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