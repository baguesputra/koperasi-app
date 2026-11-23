import { Link, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { usePrivacy } from '@/Contexts/PrivacyContext';
import CountBadge, { CountDot } from '@/Components/ui/CountBadge';
import PrivacyIcon from '@/Components/ui/PrivacyIcon';
import {
    LayoutDashboard, Users, UserCheck, PiggyBank, HeartHandshake,
    BookOpenText, ArrowLeftRight, HandCoins, FileSearch, ClipboardCheck,
    BadgeCheck, CalendarCheck, Repeat, TrendingUp, FileX2,
    Wallet, Percent, FileBarChart,
    ChevronLeft, ChevronRight, ChevronDown, Settings, LogOut,
} from 'lucide-react';

const STORAGE_KEY = 'sidebar-groups-state-v1';
const fokusRing = 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-green/40';

const PENGATURAN_PERMISSIONS = [
    'pengaturan.kelola',
    'pengaturan.kebijakan.lihat',
    'pengaturan.kebijakan.kelola',
    'pengaturan.wa',
    'pengaturan.organisasi',
    'pengaturan.audit.lihat',
    'user.kelola',
];

const pinnedItems = [
    { label: 'Dashboard', routeName: 'dashboard', icon: LayoutDashboard, permission: null },
];

const menuGroups = [
    {
        id: 'keanggotaan',
        label: 'Keanggotaan',
        items: [
            { label: 'Data Anggota', routeName: 'anggota.index', icon: Users, permission: 'anggota.lihat' },
            { label: 'Aktivasi Anggota', routeName: 'ketua.aktivasi.index', icon: UserCheck, permission: 'aktivasi.approve-ketua' },
        ],
    },
    {
        id: 'simpanan-tabungan',
        label: 'Simpanan & Tabungan',
        items: [
            { label: 'Data Simpanan', routeName: 'simpanan.index', icon: PiggyBank, permission: 'simpanan.lihat' },
            { label: 'Konfirmasi Simpanan', routeName: 'bendahara.simpanan.index', icon: HeartHandshake, permission: 'simpanan.konfirmasi' },
            { label: 'Rekening Tabungan', routeName: 'tabungan.index', icon: BookOpenText, permission: 'tabungan.lihat' },
            { label: 'Proses Tabungan', routeName: 'bendahara.tabungan.index', icon: ArrowLeftRight, permission: 'tabungan.kelola' },
        ],
    },
    {
        id: 'pinjaman-kredit',
        label: 'Pinjaman',
        items: [
            { label: 'Data Pinjaman', routeName: 'pinjaman.index', icon: HandCoins, permission: 'pinjaman.lihat' },
            { label: 'Verifikasi Awal', routeName: 'admin.pinjaman.index', icon: FileSearch, permission: 'pinjaman.tinjau-admin' },
            { label: 'Tinjau Pinjaman', routeName: 'bendahara.pinjaman.index', icon: ClipboardCheck, permission: 'pinjaman.tinjau-bendahara' },
            { label: 'Setujui Pinjaman', routeName: 'ketua.pinjaman.index', icon: BadgeCheck, permission: 'pinjaman.approve-ketua' },
            { label: 'Konfirmasi Angsuran', routeName: 'bendahara.angsuran.index', icon: CalendarCheck, permission: 'angsuran.konfirmasi' },
            { label: 'Tinjau Ubah Tenor', routeName: 'bendahara.percepatan.index', icon: Repeat, permission: 'pinjaman.tinjau-bendahara' },
            { label: 'Setujui Ubah Tenor', routeName: 'ketua.percepatan.index', icon: Repeat, permission: 'pinjaman.approve-ketua' },
            { label: 'Tinjau Tambah Limit', routeName: 'bendahara.pengajuan-limit.index', icon: TrendingUp, permission: 'limit.tinjau-bendahara' },
            { label: 'Setujui Tambah Limit', routeName: 'ketua.pengajuan-limit.index', icon: TrendingUp, permission: 'limit.approve-ketua' },
            { label: 'Penghapusan Pinjaman', routeName: 'pengurus.penghapusan-pinjaman.index', icon: FileX2, permission: 'penghapusan-pinjaman.lihat' },
        ],
    },
    {
        id: 'keuangan-laporan',
        label: 'Keuangan & Laporan',
        items: [
            { label: 'Kas Koperasi', routeName: 'kas-koperasi.index', icon: Wallet, permission: 'kas.lihat' },
            { label: 'Pembagian SHU', routeName: 'shu.index', icon: Percent, permission: 'shu.lihat' },
            { label: 'Laporan', routeName: 'laporan.index', icon: FileBarChart, permission: 'laporan.lihat' },
        ],
    },
];

function bacaPreferensiGrup() {
    if (typeof window === 'undefined') {
        return {};
    }
    try {
        const raw = window.localStorage.getItem(STORAGE_KEY);
        if (!raw) {
            return {};
        }
        const parsed = JSON.parse(raw);
        return parsed && typeof parsed === 'object' ? parsed : {};
    } catch {
        return {};
    }
}

function simpanPreferensiGrup(prefs) {
    if (typeof window === 'undefined') {
        return;
    }
    try {
        window.localStorage.setItem(STORAGE_KEY, JSON.stringify(prefs));
    } catch {
        // Abaikan bila localStorage diblokir browser
    }
}

/** Aktif bila route persis sama atau satu modul (prefix sama). */
function sedangAktif(routeName) {
    if (!routeName) {
        return false;
    }
    if (route().current(routeName)) {
        return true;
    }
    const prefix = routeName.replace(/\.index$/, '');
    return prefix !== routeName && route().current(`${prefix}.*`);
}

export default function Sidebar({ collapsed = false, expanding = false, mobile = false, onToggle, onNavigate }) {
    const { auth, notifications } = usePage().props;
    const { isPrivacyActive, togglePrivacy } = usePrivacy();
    const url = usePage().url;
    const tautanAktif = useRef(null);
    const [groupPrefs, setGroupPrefs] = useState(bacaPreferensiGrup);
    const tertutup = collapsed && !expanding;
    const userPermissions = auth.user?.permissions ?? [];
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
        'bendahara.tabungan.index': notifications?.menunggu_tabungan ?? 0,
        'pengurus.penghapusan-pinjaman.index': notifications?.menunggu_penghapusan_pinjaman ?? 0,
    };

    function bisaAkses(permission) {
        return permission === null || userPermissions.includes(permission);
    }

    const visiblePinnedItems = pinnedItems.filter((item) => bisaAkses(item.permission));

    const visibleGroups = menuGroups
        .map((group) => {
            const items = group.items.filter((item) => bisaAkses(item.permission));
            const hasActive = items.some((item) => sedangAktif(item.routeName));
            const totalBadge = items.reduce((sum, item) => sum + (badgeAngka[item.routeName] ?? 0), 0);

            return {
                ...group,
                items,
                hasActive,
                totalBadge,
            };
        })
        .filter((group) => group.items.length > 0);

    const totalGroupedItems = visibleGroups.reduce((sum, group) => sum + group.items.length, 0);

    function grupDilipat(group) {
        if (tertutup) {
            return false;
        }
        const pref = groupPrefs[group.id];
        if (typeof pref === 'boolean') {
            return pref;
        }
        // Role biasa (<= 15 menu grup) buka semua grup secara default;
        // Super Admin (> 15 menu grup) buka grup aktif atau yang memiliki antrean menunggu.
        if (totalGroupedItems > 15) {
            return !(group.hasActive || group.totalBadge > 0);
        }
        return false;
    }

    function toggleGroup(groupId, currentlyCollapsed) {
        setGroupPrefs((prev) => {
            const next = { ...prev, [groupId]: !currentlyCollapsed };
            simpanPreferensiGrup(next);
            return next;
        });
    }

    // Pastikan grup dari rute tujuan terbuka otomatis saat berpindah halaman.
    useEffect(() => {
        const activeGroup = menuGroups.find((group) =>
            group.items.some((item) => sedangAktif(item.routeName)),
        );
        if (!activeGroup) {
            return;
        }
        setGroupPrefs((prev) => {
            if (prev[activeGroup.id] !== true) {
                return prev;
            }
            const next = { ...prev, [activeGroup.id]: false };
            simpanPreferensiGrup(next);
            return next;
        });
    }, [url]);

    // Gulirkan menu aktif ke area terlihat tiap pindah halaman/reload.
    // block:'nearest' = diam bila sudah terlihat; tanpa animasi.
    useEffect(() => {
        if (!tertutup) {
            tautanAktif.current?.scrollIntoView({ block: 'nearest' });
        }
    }, [url, tertutup]);

    function renderMenuItem(item, keySuffix, tersembunyi = false) {
        const Icon = item.icon;
        const href = item.routeName ? route(item.routeName) : '#';
        const isActive = sedangAktif(item.routeName);
        const badge = item.routeName ? (badgeAngka[item.routeName] ?? 0) : 0;

        if (item.disabled) {
            return (
                <div
                    key={item.label + keySuffix}
                    title={tertutup ? item.label : 'Segera'}
                    className={`flex items-center ${tertutup ? 'gap-0 justify-center' : 'gap-3'} px-2.5 py-1.5 rounded-lg text-xs font-semibold text-slate-300 cursor-not-allowed`}
                >
                    <Icon size={18} strokeWidth={2} />
                    <span className={`flex-1 truncate transition-all duration-300 whitespace-nowrap overflow-hidden ${tertutup ? 'opacity-0 max-w-0' : 'opacity-100 max-w-[170px]'}`}>
                        {item.label}
                    </span>
                </div>
            );
        }

        return (
            <Link
                key={item.label + keySuffix}
                href={href}
                ref={isActive ? tautanAktif : undefined}
                tabIndex={tersembunyi ? -1 : undefined}
                onClick={mobile ? onNavigate : undefined}
                title={tertutup ? (badge > 0 ? `${item.label} (${badge} menunggu)` : item.label) : undefined}
                className={`group flex items-center ${tertutup ? 'gap-0 justify-center' : 'gap-3'} px-2.5 py-1.5 rounded-xl text-sm font-semibold transition-all duration-150 ${fokusRing} ${
                    isActive
                        ? 'bg-gradient-to-r from-brand-navy via-brand-navy to-brand-navy-light text-white shadow-md shadow-brand-navy/20 ring-1 ring-white/10'
                        : 'text-slate-600 hover:bg-gradient-to-r hover:from-brand-green-light/70 hover:to-transparent hover:text-brand-navy'
                }`}
            >
                <span className="relative shrink-0">
                    <Icon size={18} strokeWidth={2} className={isActive ? 'text-brand-green' : 'text-slate-400 group-hover:text-brand-green transition-colors'} />
                    {badge > 0 && tertutup && (
                        <CountDot active={isActive} className="absolute -top-0.5 -right-0.5" />
                    )}
                </span>
                <span className={`flex-1 truncate transition-all duration-300 whitespace-nowrap overflow-hidden ${tertutup ? 'opacity-0 max-w-0' : 'opacity-100 max-w-[170px] min-w-0'}`}>
                    {item.label}
                </span>
                {badge > 0 && !tertutup && (
                    <CountBadge count={badge} active={isActive} context="sidebar" className="ml-auto" />
                )}
            </Link>
        );
    }

    const asideClass = mobile
        ? 'w-full h-full bg-white flex flex-col overflow-hidden'
        : `${tertutup ? 'w-16' : 'w-60'} h-full bg-white border border-slate-200/80 rounded-2xl flex flex-col transition-all duration-300 overflow-hidden ${expanding ? 'relative z-50 shadow-2xl' : 'shadow-[0_10px_28px_-6px_rgba(15,30,54,0.10),0_2px_8px_-2px_rgba(15,30,54,0.05)]'}`;

    return (
        <aside className={asideClass}>
            <div className={`h-[50px] flex items-center shrink-0 border-b border-slate-100/80 ${tertutup ? 'justify-center px-2' : 'justify-between px-3.5'}`}>
                {tertutup ? (
                    <button
                        type="button"
                        onClick={togglePrivacy}
                        title={isPrivacyActive ? 'Mode Privasi: Aktif (Klik untuk nonaktifkan • Ctrl+Shift+P)' : 'Mode Privasi: Nonaktif (Klik untuk aktifkan • Ctrl+Shift+P)'}
                        aria-label="Mode Privasi"
                        className="relative p-1.5 rounded-xl hover:bg-brand-green-light/50 transition-colors inline-flex items-center justify-center"
                    >
                        <img src="/images/logo-ui.webp" alt="Koperasi App" width="28" height="28" className="w-7 h-7 shrink-0" />
                        <span
                            className={`absolute top-0.5 right-0.5 w-2.5 h-2.5 rounded-full border-2 border-white transition-all ${
                                isPrivacyActive ? 'bg-brand-green ring-2 ring-brand-green/30' : 'bg-slate-300'
                            }`}
                        />
                    </button>
                ) : (
                    <>
                        <div className="flex items-center gap-2.5 min-w-0">
                            <img src="/images/logo-ui.webp" alt="Koperasi App" width="28" height="28" className="w-7 h-7 shrink-0" />
                            <span className="text-sm font-bold text-slate-800 truncate max-w-[135px]">
                                Koperasi App
                            </span>
                        </div>
                        <button
                            type="button"
                            onClick={togglePrivacy}
                            title={isPrivacyActive ? 'Mode Privasi: Aktif (Klik untuk nonaktifkan • Ctrl+Shift+P)' : 'Mode Privasi: Nonaktif (Klik untuk aktifkan • Ctrl+Shift+P)'}
                            aria-label="Toggle Mode Privasi"
                            aria-pressed={isPrivacyActive}
                            className={`relative inline-flex items-center justify-center w-7 h-7 rounded-lg text-slate-400 hover:text-brand-navy hover:bg-brand-green-light/60 active:scale-95 transition-all duration-150 shrink-0 ${fokusRing}`}
                        >
                            <PrivacyIcon
                                key={isPrivacyActive ? 'active' : 'inactive'}
                                active={isPrivacyActive}
                                size={18}
                                className={isPrivacyActive ? 'animate-privacy-on' : 'opacity-65 hover:opacity-100'}
                            />
                        </button>
                    </>
                )}
            </div>

            <nav className="flex-1 px-3 py-3 overflow-y-auto scrollbar-hide">
                {visiblePinnedItems.length > 0 && (
                    <div className="space-y-0.5">
                        {visiblePinnedItems.map((item) => renderMenuItem(item, 'pinned'))}
                    </div>
                )}

                {visiblePinnedItems.length > 0 && visibleGroups.length > 0 && (
                    <div className={`border-t border-slate-100 ${tertutup ? 'mx-2 my-2' : 'my-2.5'}`} />
                )}

                <div className={tertutup ? 'space-y-2' : 'space-y-2.5'}>
                    {visibleGroups.map((group, index) => {
                        const isGroupCollapsed = grupDilipat(group);
                        const groupPanelId = `sidebar-group-${group.id}`;

                        return (
                            <div key={group.id}>
                                {tertutup && index > 0 && (
                                    <div className="mx-2 mb-2 border-t border-slate-100" />
                                )}

                                {!tertutup && (
                                    <button
                                        type="button"
                                        onClick={() => toggleGroup(group.id, isGroupCollapsed)}
                                        aria-expanded={!isGroupCollapsed}
                                        aria-controls={groupPanelId}
                                        className={`w-full flex items-center justify-between gap-2 px-2 py-1 rounded-lg text-[11px] font-bold uppercase tracking-wider transition-colors ${fokusRing} ${
                                            group.hasActive && isGroupCollapsed
                                                ? 'text-brand-navy bg-brand-green-light/45'
                                                : 'text-slate-500 hover:text-brand-navy hover:bg-slate-50'
                                        }`}
                                    >
                                        <span className="flex items-center gap-1.5 min-w-0">
                                            <span className="truncate">{group.label}</span>
                                            {group.hasActive && isGroupCollapsed && (
                                                <span className="w-1.5 h-1.5 rounded-full bg-brand-green shrink-0" aria-hidden="true" />
                                            )}
                                        </span>
                                        <span className="flex items-center gap-1.5 shrink-0">
                                            {isGroupCollapsed && group.totalBadge > 0 && (
                                                <CountBadge count={group.totalBadge} context="sidebar" />
                                            )}
                                            <ChevronDown
                                                size={14}
                                                strokeWidth={2.25}
                                                className={`text-slate-400 transition-transform duration-200 ${isGroupCollapsed ? '-rotate-90' : 'rotate-0'}`}
                                            />
                                        </span>
                                    </button>
                                )}

                                <div
                                    id={groupPanelId}
                                    aria-hidden={isGroupCollapsed}
                                    className={`grid transition-[grid-template-rows,opacity] duration-200 ease-out ${
                                        isGroupCollapsed
                                            ? 'grid-rows-[0fr] opacity-0 pointer-events-none'
                                            : 'grid-rows-[1fr] opacity-100'
                                    }`}
                                >
                                    <div className="overflow-hidden">
                                        <div className={`space-y-0.5 ${!tertutup ? 'pt-0.5' : ''}`}>
                                            {group.items.map((item) => renderMenuItem(item, group.id, isGroupCollapsed))}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        );
                    })}
                </div>
            </nav>

            {/* Profil & aksi akun */}
            {tertutup ? (
                <div
                    key="kaki-ciut"
                    className="border-t border-slate-100 px-1.5 py-2.5 shrink-0 flex flex-col items-center animate-sidebar-fade"
                >
                    <Link
                        href={route('profile.edit')}
                        onClick={mobile ? onNavigate : undefined}
                        title="Profil Saya"
                        className={`w-8 h-8 rounded-full bg-brand-green text-white flex items-center justify-center text-xs font-bold hover:opacity-90 transition-opacity ${fokusRing}`}
                    >
                        {initial}
                    </Link>
                </div>
            ) : (
                <div
                    key="kaki-buka"
                    className="border-t border-slate-100 bg-[#F8FAFC] px-3 py-2 shrink-0 flex items-center gap-1 animate-sidebar-fade"
                >
                    <Link
                        href={route('profile.edit')}
                        onClick={mobile ? onNavigate : undefined}
                        title="Profil Saya"
                        className={`flex items-center gap-2 min-w-0 flex-1 rounded-xl p-1 -m-0.5 hover:bg-white/90 hover:shadow-xs transition-all ${fokusRing}`}
                    >
                        <span className="w-8 h-8 rounded-full bg-gradient-to-br from-brand-green to-brand-green-dark text-white ring-2 ring-brand-green-light flex items-center justify-center text-xs font-bold shrink-0">
                            {initial}
                        </span>
                        <span className="min-w-0 leading-tight">
                            <span className="block text-sm font-semibold text-slate-800 truncate">{auth.user?.name}</span>
                            <span className="block text-xs text-slate-500 capitalize truncate">
                                {auth.user?.label_jabatan_pengawas
                                    ? auth.user.label_jabatan_pengawas
                                    : auth.user?.roles?.[0] === 'super_admin'
                                        ? 'Super Admin'
                                        : ['admin', 'sekretaris'].includes(auth.user?.roles?.[0])
                                            ? 'Sekretaris'
                                            : (auth.user?.roles?.[0]?.replace('_', ' ') ?? '-')}
                            </span>
                        </span>
                    </Link>
                    {bisaPengaturan && (
                        <Link
                            href={route('pengaturan.index')}
                            onClick={mobile ? onNavigate : undefined}
                            title="Pengaturan"
                            className={`p-1.5 rounded-lg text-slate-400 hover:text-brand-navy hover:bg-brand-green-light/60 transition-all shrink-0 ${fokusRing}`}
                        >
                            <Settings size={17} />
                        </Link>
                    )}
                    <form method="POST" action={route('logout')} className="shrink-0">
                        <input type="hidden" name="_token" value={csrfToken} />
                        <button
                            type="submit"
                            title="Keluar"
                            className={`p-1.5 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition-colors ${fokusRing}`}
                        >
                            <LogOut size={17} />
                        </button>
                    </form>
                </div>
            )}

            {!mobile && (
                !tertutup ? (
                    <div className="px-3.5 py-2.5 border-t border-slate-100 text-xs text-slate-500 shrink-0 flex items-center justify-between">
                        <span className="truncate max-w-[135px]">Koperasi App</span>
                        <button
                            type="button"
                            onClick={onToggle}
                            title={expanding ? 'Semuhkan sidebar (konten ikut menyesuaikan)' : 'Ciutkan sidebar'}
                            className="text-slate-400 hover:text-brand-navy transition-colors shrink-0 p-1"
                        >
                            <ChevronLeft size={17} />
                        </button>
                    </div>
                ) : (
                    <button
                        type="button"
                        onClick={onToggle}
                        title="Buka sidebar"
                        className="mx-auto mb-2 flex items-center justify-center w-8 h-8 rounded-lg text-slate-400 hover:text-brand-navy hover:bg-brand-green-light/60 transition-all shrink-0"
                    >
                        <ChevronRight size={17} />
                    </button>
                )
            )}
        </aside>
    );
}
