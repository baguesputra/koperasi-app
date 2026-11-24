import AppLayout from '@/Layouts/AppLayout';
import { Head, router } from '@inertiajs/react';
import {
    ChevronRight, HandCoins, Landmark, Plus, Search, ShieldCheck, Wallet, X,
} from 'lucide-react';
import { Suspense, lazy, useEffect, useMemo, useState } from 'react';
import Button from '@/Components/ui/Button';
import Card from '@/Components/ui/Card';
import CountBadge from '@/Components/ui/CountBadge';
import Drawer from '@/Components/ui/Drawer';
import { DrawerBody } from '@/Components/ui/DrawerSection';
import FotoAnggota from '@/Components/ui/FotoAnggota';
import PageHeader from '@/Components/ui/PageHeader';
import StatusBadge from '@/Components/ui/StatusBadge';
import TextField from '@/Components/ui/TextField';
import { formatRupiah } from '@/Utils/formatCurrency';

const drawerLoaders = {
    pinjaman_admin: () => import('@/Pages/Admin/Pinjaman/Partials/KeputusanDrawer'),
    pinjaman_bendahara: () => import('@/Pages/Bendahara/Pinjaman/Partials/KeputusanDrawer'),
    pinjaman_ketua: () => import('@/Pages/Ketua/Pinjaman/Partials/KeputusanDrawer'),
    percepatan_bendahara: () => import('@/Pages/Bendahara/Percepatan/Partials/KeputusanDrawer'),
    percepatan_ketua: () => import('@/Pages/Ketua/Percepatan/Partials/KeputusanDrawer'),
    limit_bendahara: () => import('@/Pages/Bendahara/PengajuanLimit/Partials/KeputusanDrawer'),
    limit_ketua: () => import('@/Pages/Ketua/PengajuanLimit/Partials/KeputusanDrawer'),
    aktivasi: () => import('@/Pages/Ketua/Aktivasi/Partials/KeputusanDrawer'),
    pencairan: () => import('./Partials/PencairanDrawers'),
    tabungan: () => import('./Partials/TabunganDrawers'),
    penghapusan: () => import('./Partials/PenghapusanDrawers'),
};

const LAZY_DRAWERS = {
    pinjaman_admin: lazy(drawerLoaders.pinjaman_admin),
    pinjaman_bendahara: lazy(drawerLoaders.pinjaman_bendahara),
    pinjaman_ketua: lazy(drawerLoaders.pinjaman_ketua),
    percepatan_bendahara: lazy(drawerLoaders.percepatan_bendahara),
    percepatan_ketua: lazy(drawerLoaders.percepatan_ketua),
    limit_bendahara: lazy(drawerLoaders.limit_bendahara),
    limit_ketua: lazy(drawerLoaders.limit_ketua),
    aktivasi: lazy(drawerLoaders.aktivasi),
    pencairan: lazy(drawerLoaders.pencairan),
    tabungan: lazy(drawerLoaders.tabungan),
    penghapusan_detail: lazy(() => drawerLoaders.penghapusan().then((m) => ({ default: m.PenghapusanDetailDrawer }))),
    penghapusan_ajukan: lazy(() => drawerLoaders.penghapusan().then((m) => ({ default: m.AjukanPenghapusanDrawer }))),
};

const JENIS_BADGE_STYLE = {
    navy: 'bg-brand-navy/8 text-brand-navy border-brand-navy/15',
    emerald: 'bg-emerald-50 text-emerald-700 border-emerald-200/80',
    sky: 'bg-sky-50 text-sky-700 border-sky-200/80',
    violet: 'bg-violet-50 text-violet-700 border-violet-200/80',
    amber: 'bg-amber-50 text-amber-700 border-amber-200/80',
    teal: 'bg-teal-50 text-teal-700 border-teal-200/80',
    rose: 'bg-rose-50 text-rose-700 border-rose-200/80',
};

const LIKUIDITAS_BADGE = {
    sehat: { label: 'Likuiditas Sehat', cls: 'bg-emerald-50 text-emerald-700 border-emerald-200/80' },
    waspada: { label: 'Kas Ketat', cls: 'bg-amber-50 text-amber-700 border-amber-200/80' },
    defisit: { label: 'Proyeksi Defisit', cls: 'bg-rose-50 text-rose-700 border-rose-200/80' },
};

const STAGE_LABELS = {
    admin: 'Sekretaris',
    bendahara: 'Bendahara',
    ketua: 'Ketua',
};

const PARTIAL_PROPS = ['tabs', 'viewAktif', 'kategoriFilter', 'tahapFilter', 'totalMenunggu', 'inbox', 'notifications'];

function resolveDrawerLoaderKey(item) {
    if (!item) return null;
    if (item.kategori === 'pinjaman') return `pinjaman_${item.tahap || 'admin'}`;
    if (item.kategori === 'pencairan') return 'pencairan';
    if (item.kategori === 'tabungan') return 'tabungan';
    if (item.kategori === 'percepatan') return `percepatan_${item.tahap || 'ketua'}`;
    if (item.kategori === 'limit') return `limit_${item.tahap || 'ketua'}`;
    if (item.kategori === 'aktivasi') return 'aktivasi';
    if (item.kategori === 'penghapusan') return 'penghapusan';
    return null;
}

function DrawerSkeleton() {
    return (
        <DrawerBody>
            <div className="space-y-4 animate-pulse">
                <div className="h-28 rounded-2xl bg-slate-100" />
                <div className="h-36 rounded-2xl bg-slate-100" />
                <div className="h-24 rounded-2xl bg-slate-100" />
            </div>
        </DrawerBody>
    );
}

export default function Index({
    tabs = [],
    viewAktif = 'menunggu',
    kategoriFilter = 'semua',
    tahapFilter = 'semua',
    totalMenunggu = 0,
    inbox = {},
}) {
    const [kategori, setKategori] = useState(kategoriFilter);
    const [tahap, setTahap] = useState(tahapFilter);
    const [cari, setCari] = useState('');
    const [itemDipilih, setItemDipilih] = useState(null);
    const [drawerOpen, setDrawerOpen] = useState(false);
    const [ajukanOpen, setAjukanOpen] = useState(false);
    const [pendingView, setPendingView] = useState(null);

    useEffect(() => {
        setKategori(kategoriFilter);
        setTahap(tahapFilter);
    }, [kategoriFilter, tahapFilter]);

    const antrean = inbox.antrean ?? [];
    const riwayat = inbox.riwayat ?? [];
    const sedangBerjalanPenghapusan = inbox.sedangBerjalanPenghapusan ?? [];
    const kasRingkas = inbox.kasRingkas ?? null;
    const ketentuanAktivasi = inbox.ketentuanAktivasi ?? null;
    const bisaAjukanPenghapusan = Boolean(inbox.bisaAjukanPenghapusan);
    const pinjamanMenunggak = inbox.pinjamanMenunggak ?? [];
    const peranPenghapusan = inbox.peranPenghapusan ?? 'Pengurus';

    const displayView = pendingView ?? viewAktif;
    const isNavigatingView = pendingView !== null;

    const baseItems = useMemo(() => {
        if (viewAktif === 'riwayat') return riwayat;
        if (viewAktif === 'berjalan') return sedangBerjalanPenghapusan;
        return antrean;
    }, [viewAktif, antrean, riwayat, sedangBerjalanPenghapusan]);

    // Hitung jumlah item per kategori dari list aktif (untuk filter chip dinamis)
    const kategoriCounts = useMemo(() => {
        const counts = {};
        for (const item of baseItems) {
            counts[item.kategori] = (counts[item.kategori] ?? 0) + 1;
        }
        return counts;
    }, [baseItems]);

    const filterChips = useMemo(() => (
        tabs
            .map((t) => ({
                key: t.key,
                label: t.label,
                count: viewAktif === 'menunggu' ? (t.count ?? kategoriCounts[t.key] ?? 0) : (kategoriCounts[t.key] ?? 0),
            }))
            .filter((chip) => chip.count > 0 || chip.key === kategori)
    ), [tabs, viewAktif, kategoriCounts, kategori]);

    // Filter tahap hanya muncul jika ada >1 jenjang berbeda di kategori yang terpilih (mis. Super Admin)
    const stageOptions = useMemo(() => {
        const scoped = kategori === 'semua' ? baseItems : baseItems.filter((i) => i.kategori === kategori);
        const found = new Set(scoped.map((i) => i.tahap).filter(Boolean));
        return ['admin', 'bendahara', 'ketua'].filter((k) => found.has(k) || tahap === k);
    }, [baseItems, kategori, tahap]);

    const itemsTampil = useMemo(() => {
        const q = cari.trim().toLowerCase();
        return baseItems.filter((item) => {
            if (kategori !== 'semua' && item.kategori !== kategori) return false;
            if (tahap !== 'semua' && item.tahap !== tahap) return false;
            if (!q) return true;
            return (
                item.anggota?.nama?.toLowerCase().includes(q)
                || item.anggota?.no_karyawan?.toLowerCase().includes(q)
                || item.jenis_label?.toLowerCase().includes(q)
                || item.ringkasan_utama?.toLowerCase().includes(q)
                || item.ringkasan_sub?.toLowerCase().includes(q)
            );
        });
    }, [baseItems, kategori, tahap, cari]);

    const tampilkanStripFilter = filterChips.length >= 2 || kategori !== 'semua' || stageOptions.length >= 2;

    function syncUrl(nextKategori, nextTahap) {
        if (typeof window === 'undefined') return;
        const url = new URL(window.location.href);
        if (nextKategori && nextKategori !== 'semua') url.searchParams.set('kategori', nextKategori);
        else url.searchParams.delete('kategori');

        if (nextTahap && nextTahap !== 'semua') url.searchParams.set('tahap', nextTahap);
        else url.searchParams.delete('tahap');

        window.history.replaceState(window.history.state, '', url.toString());
    }

    function pilihKategori(nextKey) {
        setKategori(nextKey);
        setTahap('semua');
        syncUrl(nextKey, 'semua');
    }

    function pilihTahap(nextStage) {
        setTahap(nextStage);
        syncUrl(kategori, nextStage);
    }

    function prefetchView(nextView) {
        if (nextView === viewAktif || typeof router.prefetch !== 'function') return;
        router.prefetch(
            route('persetujuan.index', { view: nextView }),
            { method: 'get', only: PARTIAL_PROPS },
            { cacheFor: '30s' }
        );
    }

    function pindahView(nextView) {
        if (nextView === viewAktif) return;
        setKategori('semua');
        setTahap('semua');
        router.visit(route('persetujuan.index', { view: nextView }), {
            method: 'get',
            only: PARTIAL_PROPS,
            preserveState: true,
            preserveScroll: true,
            onStart: () => setPendingView(nextView),
            onFinish: () => setPendingView(null),
        });
    }

    function preloadRowDrawer(item) {
        const key = resolveDrawerLoaderKey(item);
        if (key) drawerLoaders[key]?.();
    }

    function bukaDrawer(item) {
        preloadRowDrawer(item);
        setItemDipilih(item);
        setDrawerOpen(true);
    }

    function tutupDrawer() {
        setDrawerOpen(false);
    }

    function resetSemuaFilter() {
        setCari('');
        setKategori('semua');
        setTahap('semua');
        syncUrl('semua', 'semua');
    }

    function drawerMeta(item) {
        if (!item) return { title: '', description: '', badge: null, maxWidth: '2xl' };
        const nama = item.anggota?.nama ?? 'Anggota';
        const status = item.detail?.status ?? item.status;
        if (item.kategori === 'pinjaman') {
            const prefix = item.tahap === 'admin' ? 'Verifikasi Pinjaman' : item.tahap === 'bendahara' ? 'Tinjau Pinjaman' : 'Approval Final';
            return { title: `${prefix} — ${nama}`, description: '', badge: <StatusBadge status={status} />, maxWidth: '3xl' };
        }
        if (item.kategori === 'pencairan') {
            if (item.jenis_kode === 'cair_pinjaman') {
                return status === 'approved_ketua'
                    ? {
                        title: `Pencairan Pinjaman — ${nama}`,
                        description: `${item.anggota?.no_karyawan ?? ''}${item.anggota?.cabang ? ` • ${item.anggota.cabang}` : ''}`,
                        badge: <StatusBadge status={status} />,
                        maxWidth: '2xl',
                    }
                    : {
                        title: `Rincian Pencairan #${item.detail?.id ?? ''}`,
                        description: '',
                        badge: <StatusBadge status={status} />,
                        maxWidth: 'xl',
                    };
            }
            return {
                title: item.detail?.jenis === 'tutup_rekening' ? 'Cairkan & Tutup Rekening Tabungan' : 'Cairkan Penarikan Tabungan',
                description: `${nama} • ${item.detail?.no_rekening ?? ''}`,
                badge: <StatusBadge status={status} />,
                maxWidth: '2xl',
            };
        }
        if (item.kategori === 'tabungan') {
            return {
                title: `${item.jenis_label} — ${nama}`,
                description: item.detail?.no_rekening ?? item.anggota?.cabang ?? '',
                badge: <StatusBadge status={status} />,
                maxWidth: '2xl',
            };
        }
        if (item.kategori === 'percepatan') {
            return {
                title: `${item.tahap === 'bendahara' ? 'Tinjau' : 'Approval'} Perubahan Tenor — ${nama}`,
                description: '',
                badge: <StatusBadge status={status === 'aktif' ? 'disetujui' : status} />,
                maxWidth: '3xl',
            };
        }
        if (item.kategori === 'limit') {
            return {
                title: `Pengajuan Limit — ${nama}`,
                description: '',
                badge: <StatusBadge status={status} />,
                maxWidth: '3xl',
            };
        }
        if (item.kategori === 'aktivasi') {
            return {
                title: `Aktivasi — ${nama}`,
                description: '',
                badge: <StatusBadge status={status === 'diajukan' ? 'pending' : status} />,
                maxWidth: '2xl',
            };
        }
        return {
            title: `Penghapusan Pinjaman #${item.detail?.pinjaman_id ?? ''} — ${nama}`,
            description: '',
            badge: <StatusBadge status={status} />,
            maxWidth: '2xl',
        };
    }

    const activeDrawerProps = drawerMeta(itemDipilih);
    const likuiditasInfo = kasRingkas ? (LIKUIDITAS_BADGE[kasRingkas.status_likuiditas] ?? LIKUIDITAS_BADGE.sehat) : null;

    function renderActiveDrawerContent(item) {
        if (!item) return null;
        if (item.kategori === 'pinjaman') {
            const Cmp = LAZY_DRAWERS[`pinjaman_${item.tahap || 'admin'}`];
            return <Cmp key={item.uid} pinjaman={item.detail} onClose={tutupDrawer} />;
        }
        if (item.kategori === 'pencairan') {
            const Cmp = LAZY_DRAWERS.pencairan;
            return <Cmp key={item.uid} item={item} kasRingkas={kasRingkas} onClose={tutupDrawer} />;
        }
        if (item.kategori === 'tabungan') {
            const Cmp = LAZY_DRAWERS.tabungan;
            return <Cmp key={item.uid} item={item} onClose={tutupDrawer} />;
        }
        if (item.kategori === 'percepatan') {
            const Cmp = LAZY_DRAWERS[`percepatan_${item.tahap || 'ketua'}`];
            return <Cmp key={item.uid} pengajuan={item.detail} onClose={tutupDrawer} />;
        }
        if (item.kategori === 'limit') {
            const Cmp = LAZY_DRAWERS[`limit_${item.tahap || 'ketua'}`];
            return <Cmp key={item.uid} pengajuan={item.detail} onClose={tutupDrawer} />;
        }
        if (item.kategori === 'aktivasi') {
            const Cmp = LAZY_DRAWERS.aktivasi;
            return <Cmp key={item.uid} pengajuan={item.detail} ketentuanFinansial={ketentuanAktivasi} onClose={tutupDrawer} />;
        }
        const Cmp = LAZY_DRAWERS.penghapusan_detail;
        return <Cmp key={item.uid} detail={item.detail} peranSaya={peranPenghapusan} onClose={tutupDrawer} />;
    }

    return (
        <AppLayout>
            <Head title="Pusat Persetujuan" />

            <PageHeader
                title="Pusat Persetujuan"
                subtitle={
                    totalMenunggu > 0
                        ? `${totalMenunggu} antrean menunggu tindakan atau pencairan Anda`
                        : 'Semua antrean persetujuan dan pencairan telah tertangani'
                }
                actions={
                    bisaAjukanPenghapusan ? (
                        <Button
                            size="sm"
                            variant="outline"
                            onMouseEnter={() => drawerLoaders.penghapusan()}
                            onClick={() => {
                                drawerLoaders.penghapusan();
                                setAjukanOpen(true);
                            }}
                        >
                            <Plus size={15} /> Ajukan Penghapusan
                        </Button>
                    ) : null
                }
            />

            {/* Kartu Posisi Kas & Beban Antrean (khusus role keuangan) */}
            {kasRingkas && (
                <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
                    <div className="rounded-2xl p-4 border bg-white border-slate-200/80 shadow-xs flex flex-col justify-between">
                        <div className="flex items-center justify-between gap-2">
                            <span className="text-xs font-semibold text-slate-500">Saldo Bank Koperasi</span>
                            <span className="w-8 h-8 rounded-xl bg-brand-navy/5 text-brand-navy flex items-center justify-center shrink-0">
                                <Landmark size={16} />
                            </span>
                        </div>
                        <div className="mt-2.5">
                            <p className="text-xl sm:text-2xl font-bold text-slate-800 tabular-nums privacy-blur">
                                {formatRupiah(kasRingkas.saldo_bank)}
                            </p>
                            <p className="text-xs text-slate-400 mt-1">Rekening operasional utama</p>
                        </div>
                    </div>

                    <div className="rounded-2xl p-4 border bg-white border-slate-200/80 shadow-xs flex flex-col justify-between">
                        <div className="flex items-center justify-between gap-2">
                            <span className="text-xs font-semibold text-slate-500">Saldo Kas Kecil</span>
                            <span className="w-8 h-8 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center shrink-0">
                                <Wallet size={16} />
                            </span>
                        </div>
                        <div className="mt-2.5">
                            <p className="text-xl sm:text-2xl font-bold text-slate-800 tabular-nums privacy-blur">
                                {formatRupiah(kasRingkas.saldo_kas_kecil)}
                            </p>
                            <p className="text-xs text-slate-400 mt-1">Kas tunai di kantor</p>
                        </div>
                    </div>

                    <div className="rounded-2xl p-4 border bg-white border-slate-200/80 shadow-xs flex flex-col justify-between">
                        <div className="flex items-center justify-between gap-2">
                            <span className="text-xs font-semibold text-slate-500">Beban Antrean Pinjaman</span>
                            <span className="w-8 h-8 rounded-xl bg-sky-50 text-sky-700 flex items-center justify-center shrink-0">
                                <HandCoins size={16} />
                            </span>
                        </div>
                        <div className="mt-2.5">
                            <p className="text-xl sm:text-2xl font-bold text-slate-800 tabular-nums privacy-blur">
                                {formatRupiah(kasRingkas.total_antrean_pinjaman)}
                            </p>
                            <p className="text-xs text-slate-400 mt-1">
                                {(kasRingkas.jumlah_antrean_pinjaman ?? 0) > 0
                                    ? `${kasRingkas.jumlah_antrean_pinjaman} pengajuan berjalan • ${kasRingkas.persen_terpakai ?? 0}% saldo bank`
                                    : 'Belum ada antrean pinjaman'}
                            </p>
                        </div>
                    </div>

                    <div className="rounded-2xl p-4 border bg-white border-slate-200/80 shadow-xs flex flex-col justify-between">
                        <div className="flex items-center justify-between gap-1.5 flex-wrap">
                            <span className="text-xs font-semibold text-slate-500">Proyeksi Sisa Bank</span>
                            {likuiditasInfo && (
                                <span className={`inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full border text-[11px] font-semibold shrink-0 ${likuiditasInfo.cls}`}>
                                    <ShieldCheck size={12} />
                                    {likuiditasInfo.label}
                                </span>
                            )}
                        </div>
                        <div className="mt-2.5">
                            <p className={`text-xl sm:text-2xl font-bold tabular-nums privacy-blur ${kasRingkas.sisa_proyeksi < 0 ? 'text-rose-600' : 'text-brand-green-dark'}`}>
                                {formatRupiah(kasRingkas.sisa_proyeksi)}
                            </p>
                            <p className="text-xs text-slate-400 mt-1">Estimasi saldo bank pasca pencairan</p>
                        </div>
                    </div>
                </div>
            )}

            {/* Unified Inbox Card */}
            <Card padding="none" className="overflow-hidden relative">
                {isNavigatingView && (
                    <div
                        aria-hidden="true"
                        className="absolute inset-x-0 top-0 z-10 h-0.5 bg-gradient-to-r from-brand-green via-brand-navy to-brand-green animate-pulse"
                    />
                )}

                {/* Baris Toolbar 1: View Mode + Search */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 px-3.5 py-2.5 bg-white border-b border-slate-200/80">
                    <div className="flex flex-wrap items-center gap-1.5">
                        <button
                            type="button"
                            onMouseEnter={() => prefetchView('menunggu')}
                            onClick={() => pindahView('menunggu')}
                            className={`inline-flex items-center gap-2 px-3.5 py-1.5 text-xs sm:text-sm font-semibold rounded-xl transition-all ${
                                displayView === 'menunggu'
                                    ? 'bg-gradient-to-r from-brand-navy to-brand-navy-light text-white shadow-xs'
                                    : 'text-slate-600 hover:bg-slate-100/70 hover:text-brand-navy'
                            }`}
                        >
                            <span>Menunggu Tindakan</span>
                            {totalMenunggu > 0 && <CountBadge count={totalMenunggu} active={displayView === 'menunggu'} />}
                        </button>

                        {sedangBerjalanPenghapusan.length > 0 && (
                            <button
                                type="button"
                                onMouseEnter={() => prefetchView('berjalan')}
                                onClick={() => pindahView('berjalan')}
                                className={`inline-flex items-center gap-2 px-3.5 py-1.5 text-xs sm:text-sm font-semibold rounded-xl transition-all ${
                                    displayView === 'berjalan'
                                        ? 'bg-gradient-to-r from-brand-navy to-brand-navy-light text-white shadow-xs'
                                        : 'text-slate-600 hover:bg-slate-100/70 hover:text-brand-navy'
                                }`}
                            >
                                <span>Pantau Penghapusan</span>
                                <CountBadge count={sedangBerjalanPenghapusan.length} tone="slate" active={displayView === 'berjalan'} />
                            </button>
                        )}

                        <button
                            type="button"
                            onMouseEnter={() => prefetchView('riwayat')}
                            onClick={() => pindahView('riwayat')}
                            className={`inline-flex items-center gap-2 px-3.5 py-1.5 text-xs sm:text-sm font-semibold rounded-xl transition-all ${
                                displayView === 'riwayat'
                                    ? 'bg-gradient-to-r from-brand-navy to-brand-navy-light text-white shadow-xs'
                                    : 'text-slate-600 hover:bg-slate-100/70 hover:text-brand-navy'
                            }`}
                        >
                            <span>Riwayat Keputusan</span>
                        </button>
                    </div>

                    <div className="relative w-full sm:w-64">
                        <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
                        <TextField
                            type="search"
                            size="sm"
                            value={cari}
                            onChange={(e) => setCari(e.target.value)}
                            placeholder="Cari nama, NIK, atau jenis..."
                            className="pl-8 pr-8 text-xs rounded-xl"
                        />
                        {cari && (
                            <button
                                type="button"
                                onClick={() => setCari('')}
                                aria-label="Hapus pencarian"
                                className="absolute right-2 top-1/2 -translate-y-1/2 w-5 h-5 inline-flex items-center justify-center rounded-full text-slate-400 hover:text-slate-600"
                            >
                                <X size={13} />
                            </button>
                        )}
                    </div>
                </div>

                {/* Baris Toolbar 2: Dynamic Filter Chips (hanya muncul bila ada >= 2 kategori aktif / multi-jenjang) */}
                {tampilkanStripFilter && (
                    <div className="flex flex-wrap items-center justify-between gap-2 px-3.5 py-2 bg-[#F8FAFC] border-b border-slate-200/70">
                        <div className="flex flex-wrap items-center gap-1.5">
                            <button
                                type="button"
                                onClick={() => pilihKategori('semua')}
                                className={`inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border transition-all ${
                                    kategori === 'semua'
                                        ? 'bg-brand-navy text-white border-brand-navy shadow-2xs'
                                        : 'bg-white text-slate-600 border-slate-200/80 hover:border-slate-300'
                                }`}
                            >
                                <span>Semua</span>
                                <span className={`tabular-nums text-[11px] ${kategori === 'semua' ? 'text-emerald-300' : 'text-slate-400'}`}>
                                    {baseItems.length}
                                </span>
                            </button>

                            {filterChips.map((chip) => {
                                const aktif = kategori === chip.key;
                                return (
                                    <button
                                        key={chip.key}
                                        type="button"
                                        onClick={() => pilihKategori(chip.key)}
                                        className={`inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border transition-all ${
                                            aktif
                                                ? 'bg-brand-green-light/70 text-brand-navy border-brand-green/50'
                                                : 'bg-white text-slate-600 border-slate-200/80 hover:border-slate-300 hover:text-brand-navy'
                                        }`}
                                    >
                                        <span>{chip.label}</span>
                                        <span className={`tabular-nums text-[11px] ${aktif ? 'text-brand-green-dark font-bold' : 'text-slate-400'}`}>
                                            {chip.count}
                                        </span>
                                    </button>
                                );
                            })}
                        </div>

                        {stageOptions.length >= 2 && (
                            <div className="inline-flex items-center gap-1">
                                <span className="text-[11px] font-semibold text-slate-400 mr-1">Jenjang:</span>
                                <button
                                    type="button"
                                    onClick={() => pilihTahap('semua')}
                                    className={`px-2 py-0.5 rounded-md text-[11px] font-semibold transition-colors ${
                                        tahap === 'semua' ? 'bg-slate-800 text-white' : 'text-slate-500 hover:bg-white'
                                    }`}
                                >
                                    Semua
                                </button>
                                {stageOptions.map((st) => (
                                    <button
                                        key={st}
                                        type="button"
                                        onClick={() => pilihTahap(st)}
                                        className={`px-2 py-0.5 rounded-md text-[11px] font-semibold transition-colors ${
                                            tahap === st ? 'bg-slate-800 text-white' : 'text-slate-500 hover:bg-white'
                                        }`}
                                    >
                                        {STAGE_LABELS[st] ?? st}
                                    </button>
                                ))}
                            </div>
                        )}
                    </div>
                )}

                {/* Tabel Antrean Terpadu */}
                <div className={`overflow-x-auto transition-opacity duration-150 ${isNavigatingView ? 'opacity-60 pointer-events-none' : ''}`}>
                    <table className="w-full table-sticky-first text-sm">
                        <thead>
                            <tr className="text-left border-b border-slate-200/80 bg-[#F8FAFC]">
                                <th className="px-4 py-2.5 text-[11px] font-bold uppercase tracking-wider text-slate-500 whitespace-nowrap">Pemohon</th>
                                <th className="px-4 py-2.5 text-[11px] font-bold uppercase tracking-wider text-slate-500 whitespace-nowrap">Jenis Pengajuan</th>
                                <th className="px-4 py-2.5 text-[11px] font-bold uppercase tracking-wider text-slate-500 whitespace-nowrap">Ringkasan</th>
                                <th className="px-4 py-2.5 text-[11px] font-bold uppercase tracking-wider text-slate-500 whitespace-nowrap">Tanggal &amp; Status</th>
                                <th className="px-4 py-2.5 text-right text-[11px] font-bold uppercase tracking-wider text-slate-500 whitespace-nowrap">Aksi</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100 bg-white">
                            {itemsTampil.length === 0 ? (
                                <tr>
                                    <td colSpan={5} className="px-4 py-12 text-center">
                                        <p className="text-sm font-medium text-slate-500">
                                            {cari || kategori !== 'semua' || tahap !== 'semua'
                                                ? 'Tidak ada data yang cocok dengan filter saat ini.'
                                                : viewAktif === 'riwayat'
                                                    ? 'Belum ada riwayat keputusan.'
                                                    : viewAktif === 'berjalan'
                                                        ? 'Tidak ada pengajuan penghapusan pinjaman yang sedang berjalan.'
                                                        : 'Semua beres — tidak ada antrean yang menunggu tindakan Anda.'}
                                        </p>
                                        {(cari || kategori !== 'semua' || tahap !== 'semua') && (
                                            <button
                                                type="button"
                                                onClick={resetSemuaFilter}
                                                className="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-brand-navy hover:underline"
                                            >
                                                Tampilkan semua antrean
                                            </button>
                                        )}
                                    </td>
                                </tr>
                            ) : (
                                itemsTampil.map((item) => {
                                    const badgeCls = JENIS_BADGE_STYLE[item.warna] ?? JENIS_BADGE_STYLE.navy;
                                    const nominalColor = item.nada_nominal === 'emerald'
                                        ? 'text-brand-green-dark'
                                        : item.nada_nominal === 'rose'
                                            ? 'text-rose-600'
                                            : 'text-slate-800';

                                    return (
                                        <tr
                                            key={item.uid}
                                            onMouseEnter={() => preloadRowDrawer(item)}
                                            onFocus={() => preloadRowDrawer(item)}
                                            onClick={() => bukaDrawer(item)}
                                            onKeyDown={(e) => {
                                                if (e.key === 'Enter' || e.key === ' ') {
                                                    e.preventDefault();
                                                    bukaDrawer(item);
                                                }
                                            }}
                                            tabIndex={0}
                                            className="group hover:bg-[#F2FBF5] transition-colors cursor-pointer focus-visible:outline-none focus-visible:bg-[#F2FBF5]"
                                        >
                                            <td className="px-4 py-2.5">
                                                <div className="flex items-center gap-3">
                                                    <FotoAnggota
                                                        nama={item.anggota?.nama}
                                                        fotoUrl={item.anggota?.foto_url}
                                                        ukuran="sm"
                                                    />
                                                    <div className="min-w-0">
                                                        <p className="text-sm font-semibold text-slate-800 truncate">{item.anggota?.nama ?? '-'}</p>
                                                        <p className="text-xs text-slate-500 truncate">
                                                            {item.anggota?.no_karyawan ?? '-'}
                                                            {item.anggota?.cabang && item.anggota.cabang !== '-' ? ` • ${item.anggota.cabang}` : ''}
                                                        </p>
                                                    </div>
                                                </div>
                                            </td>

                                            <td className="px-4 py-2.5 whitespace-nowrap">
                                                <div className="flex flex-col items-start gap-0.5">
                                                    <span className={`inline-flex items-center px-2 py-0.5 rounded-md border text-[11px] font-semibold ${badgeCls}`}>
                                                        {item.jenis_label}
                                                    </span>
                                                    {item.tahap_label && (
                                                        <span className="text-[11px] text-slate-400">
                                                            Tahap: {item.tahap_label}
                                                        </span>
                                                    )}
                                                </div>
                                            </td>

                                            <td className="px-4 py-2.5">
                                                <p className={`text-sm font-bold truncate ${nominalColor} ${item.is_nominal ? 'tabular-nums privacy-blur' : ''}`}>
                                                    {item.ringkasan_utama}
                                                </p>
                                                <p className="text-xs text-slate-500 truncate max-w-[260px]">
                                                    {item.ringkasan_sub}
                                                </p>
                                            </td>

                                            <td className="px-4 py-2.5 whitespace-nowrap">
                                                <div className="flex flex-col items-start gap-1">
                                                    <StatusBadge status={item.status} />
                                                    <span className="text-[11px] text-slate-400">{item.tanggal}</span>
                                                </div>
                                            </td>

                                            <td className="px-4 py-2.5 text-right whitespace-nowrap">
                                                <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border border-slate-200/90 bg-white text-xs font-semibold text-brand-navy group-hover:border-brand-green/50 group-hover:bg-brand-green-light/30 transition-colors">
                                                    {item.aksi_label}
                                                    <ChevronRight size={14} className="text-slate-400 group-hover:text-brand-green-dark group-hover:translate-x-0.5 transition-all" />
                                                </span>
                                            </td>
                                        </tr>
                                    );
                                })
                            )}
                        </tbody>
                    </table>
                </div>
            </Card>

            {/* Drawer Asli Modul (Lazy-loaded saat diklik / preloaded saat hover) */}
            <Drawer
                show={drawerOpen}
                layout="split"
                title={activeDrawerProps.title}
                description={activeDrawerProps.description}
                badge={activeDrawerProps.badge}
                onClose={tutupDrawer}
                maxWidth={activeDrawerProps.maxWidth}
            >
                {itemDipilih && (
                    <Suspense fallback={<DrawerSkeleton />}>
                        {renderActiveDrawerContent(itemDipilih)}
                    </Suspense>
                )}
            </Drawer>

            {/* Drawer Ajukan Penghapusan Pinjaman */}
            {bisaAjukanPenghapusan && (
                <Drawer
                    show={ajukanOpen}
                    layout="split"
                    title="Ajukan Penghapusan Pinjaman"
                    description="Pilih pinjaman menunggak / macet untuk proses persetujuan berjenjang."
                    onClose={() => setAjukanOpen(false)}
                    maxWidth="xl"
                >
                    {ajukanOpen && (
                        <Suspense fallback={<DrawerSkeleton />}>
                            <LAZY_DRAWERS.penghapusan_ajukan
                                pinjamanMenunggak={pinjamanMenunggak}
                                onClose={() => setAjukanOpen(false)}
                            />
                        </Suspense>
                    )}
                </Drawer>
            )}
        </AppLayout>
    );
}
