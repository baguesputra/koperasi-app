import AppLayout from '@/Layouts/AppLayout';
import { Head, router, usePage } from '@inertiajs/react';
import { CheckCircle2, Clock, Search, Wallet, ChevronRight, X, RefreshCw, Printer, HandCoins, Plus } from 'lucide-react';
import { Suspense, lazy, useEffect, useRef, useState } from 'react';
import useDebouncedValue from '@/Utils/useDebouncedValue';
import Button from '@/Components/ui/Button';
import Card from '@/Components/ui/Card';
import FotoAnggota from '@/Components/ui/FotoAnggota';
import StatusBadge from '@/Components/ui/StatusBadge';
import { UnifiedLedgerStrip } from '@/Components/ui/SummaryLayout';
import PageHeader from '@/Components/ui/PageHeader';
import TextField from '@/Components/ui/TextField';
import Select from '@/Components/ui/Select';
import Drawer from '@/Components/ui/Drawer';
import { DrawerBody } from '@/Components/ui/DrawerSection';
import DetailDrawer from './Partials/DetailDrawer';
import { formatRupiah } from '@/Utils/formatCurrency';
import Pagination from '@/Components/ui/Pagination';

const AjukanPenghapusanDrawer = lazy(() =>
    import('@/Pages/Persetujuan/Partials/PenghapusanDrawers').then((m) => ({ default: m.AjukanPenghapusanDrawer }))
);

function buildStatusOptions(statistik = {}) {
    return [
        { value: '', label: 'Semua Status' },
        { value: 'diajukan', label: '1. Diajukan (Menunggu Sekretaris)' },
        { value: 'approved_admin', label: '2. Diteruskan Sekretaris (Menunggu Bendahara)' },
        { value: 'approved_bendahara', label: '3. Disetujui Bendahara (Menunggu Ketua)' },
        { value: 'approved_ketua', label: '4. Disetujui Ketua (Menunggu Pencairan)' },
        { value: 'aktif', label: 'Aktif' },
        { value: 'menunggak', label: `Menunggak (${statistik.menunggak ?? 0})` },
        { value: 'lunas', label: 'Lunas' },
        { value: 'ditolak', label: 'Ditolak' },
        { value: 'dihapusbukukan', label: 'Dihapusbukukan' },
    ];
}

function progresAngsuran(angsuran = []) {
    const total = angsuran.length;
    if (!total) return null;
    const lunas = angsuran.filter((a) => a.status === 'lunas').length;
    return { lunas, total, persen: Math.round((lunas / total) * 100) };
}

export default function Index({
    pinjaman,
    filters,
    statistik,
    cabangAktif,
    daftarCabang = [],
    bisaAjukanPenghapusan: bisaAjukanProp = false,
    ajukanInitialPinjaman = null,
    pinjamanMenunggak = [],
}) {
    const { auth } = usePage().props;
    const roles = auth?.user?.roles ?? [];
    const userPermissions = auth?.user?.permissions ?? [];
    const isPengawasSaja = roles.includes('pengawas') && !roles.some((r) => ['super_admin', 'sekretaris', 'bendahara', 'ketua_koperasi'].includes(r));
    const bisaAjukanPenghapusan = Boolean(bisaAjukanProp || userPermissions.includes('penghapusan-pinjaman.ajukan'));
    const bisaCetakBaris = (p) => !isPengawasSaja || Boolean(p.is_milik_sendiri);
    const daftarCabangAman = Array.isArray(daftarCabang) ? daftarCabang : Object.values(daftarCabang ?? {});
    const statusOptions = buildStatusOptions(statistik);
    const [cari, setCari] = useState(filters.cari ?? '');
    const cariDebounced = useDebouncedValue(cari);
    const pertama = useRef(true);
    const [detailPinjaman, setDetailPinjaman] = useState(null);
    const [drawerOpen, setDrawerOpen] = useState(false);
    const [ajukanOpen, setAjukanOpen] = useState(Boolean(ajukanInitialPinjaman));
    const [initialPinjamanHapus, setInitialPinjamanHapus] = useState(ajukanInitialPinjaman);
    const [loadingMenunggak, setLoadingMenunggak] = useState(false);

    useEffect(() => {
        if (ajukanInitialPinjaman) {
            setInitialPinjamanHapus(ajukanInitialPinjaman);
            setAjukanOpen(true);
        }
    }, [ajukanInitialPinjaman]);

    function bukaAjukanPenghapusan(pinjamanDipilih = null) {
        setDrawerOpen(false);
        setInitialPinjamanHapus(pinjamanDipilih);
        setAjukanOpen(true);
        if (!pinjamanDipilih) {
            setLoadingMenunggak(true);
            router.reload({
                only: ['pinjamanMenunggak'],
                preserveState: true,
                preserveScroll: true,
                onFinish: () => setLoadingMenunggak(false),
            });
        }
    }

    const totalMenunggu = (statistik.diajukan ?? 0) + (statistik.approved_admin ?? 0) + (statistik.approved_bendahara ?? 0) + (statistik.approved_ketua ?? 0);
    const filterAktif = Boolean((filters.cari ?? '') || (filters.status ?? '') || cabangAktif);

    function terapkanFilter(overrides = {}) {
        router.get(
            route('pinjaman.index'),
            { cari, status: filters.status ?? '', cabang: cabangAktif ?? '', ...overrides },
            { preserveState: true, replace: true }
        );
    }

    function resetFilter() {
        setCari('');
        router.get(route('pinjaman.index'), {}, { preserveState: true, replace: true });
    }

    useEffect(() => {
        if (pertama.current) {
            pertama.current = false;

            return;
        }

        if ((cariDebounced ?? '') !== (filters.cari ?? '')) {
            terapkanFilter({ cari: cariDebounced ?? '' });
        }
    }, [cariDebounced]);

    useEffect(() => {
        setCari(filters.cari ?? '');
    }, [filters.cari]);

    function bukaDetail(p) {
        setDetailPinjaman(p);
        setDrawerOpen(true);
    }

    function bukaCetak(e, p) {
        e.stopPropagation();
        fetch(route('pinjaman.cetak-bukti', { pinjaman: p.id, download: 1 }))
            .then((res) => {
                if (!res.ok) throw new Error('Gagal mengunduh PDF');
                return res.blob();
            })
            .then((blob) => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `pinjaman-${p.no_karyawan}.pdf`;
                document.body.appendChild(a);
                a.click();
                a.remove();
                window.URL.revokeObjectURL(url);
            })
            .catch(() => {
                alert('Gagal mengunduh PDF. Silakan coba lagi.');
            });
    }

    return (
        <AppLayout>
            <Head title="Pinjaman" />

            <PageHeader
                title="Pinjaman"
                subtitle={`${pinjaman.total} pengajuan tercatat`}
                actions={
                    bisaAjukanPenghapusan ? (
                        <Button type="button" size="sm" variant="outline" onClick={() => bukaAjukanPenghapusan(null)}>
                            <Plus size={15} aria-hidden="true" />
                            Ajukan Penghapusan
                        </Button>
                    ) : null
                }
            />

            {/* Ringkasan Eksekutif Ringkas */}
            <UnifiedLedgerStrip
                columns={4}
                items={[
                    {
                        key: 'aktif',
                        variant: 'anchor',
                        label: 'Pinjaman Aktif',
                        icon: HandCoins,
                        value: formatRupiah(statistik.total_nominal_aktif),
                        active: filters.status === 'aktif' || filters.status === 'menunggak',
                        onClick: () => terapkanFilter({ status: filters.status === 'aktif' ? '' : 'aktif' }),
                        badge:
                            filters.status === 'aktif' ? (
                                <span className="px-1.5 py-0.5 rounded bg-emerald-400/20 text-emerald-200 border border-emerald-300/35 text-[10px] font-semibold">
                                    Aktif ✓
                                </span>
                            ) : (statistik.menunggak ?? 0) > 0 ? (
                                <button
                                    type="button"
                                    onClick={(e) => {
                                        e.stopPropagation();
                                        terapkanFilter({ status: filters.status === 'menunggak' ? '' : 'menunggak' });
                                    }}
                                    className={`px-1.5 py-0.5 rounded text-[10px] font-bold transition-colors border ${
                                        filters.status === 'menunggak'
                                            ? 'bg-rose-500 text-white border-rose-400'
                                            : 'bg-rose-400/20 text-rose-200 border-rose-300/35 hover:bg-rose-400/35'
                                    }`}
                                >
                                    {statistik.menunggak} Menunggak
                                </button>
                            ) : null,
                        meta: `${statistik.aktif} akad aktif berjalan`,
                    },
                    {
                        key: 'total',
                        label: 'Total Pengajuan',
                        icon: Wallet,
                        tone: 'navy',
                        value: statistik.total,
                        privacy: false,
                        active: !filters.status,
                        onClick: () => terapkanFilter({ status: '' }),
                        meta: `${statistik.aktif} aktif • ${statistik.lunas} lunas`,
                    },
                    {
                        key: 'antrean',
                        label: 'Antrean Approval',
                        icon: Clock,
                        tone: 'amber',
                        value: totalMenunggu,
                        privacy: false,
                        active: ['diajukan', 'approved_admin', 'approved_bendahara', 'approved_ketua'].includes(
                            filters.status
                        ),
                        badge:
                            totalMenunggu > 0 ? (
                                <span className="px-1.5 py-0.5 rounded-full bg-amber-50 border border-amber-200/80 text-[10px] font-bold text-amber-700">
                                    Proses
                                </span>
                            ) : null,
                        onClick: () => {
                            if (
                                ['diajukan', 'approved_admin', 'approved_bendahara', 'approved_ketua'].includes(
                                    filters.status
                                )
                            ) {
                                terapkanFilter({ status: '' });
                            } else if ((statistik.diajukan ?? 0) > 0) {
                                terapkanFilter({ status: 'diajukan' });
                            } else if ((statistik.approved_admin ?? 0) > 0) {
                                terapkanFilter({ status: 'approved_admin' });
                            } else if ((statistik.approved_bendahara ?? 0) > 0) {
                                terapkanFilter({ status: 'approved_bendahara' });
                            } else if ((statistik.approved_ketua ?? 0) > 0) {
                                terapkanFilter({ status: 'approved_ketua' });
                            } else {
                                terapkanFilter({ status: 'diajukan' });
                            }
                        },
                        pills: [
                            ...((statistik.diajukan ?? 0) > 0
                                ? [{ label: `Sekr ${statistik.diajukan}`, tone: 'amber' }]
                                : []),
                            ...((statistik.approved_admin ?? 0) > 0
                                ? [{ label: `Bend ${statistik.approved_admin}`, tone: 'sky' }]
                                : []),
                            ...((statistik.approved_bendahara ?? 0) > 0
                                ? [{ label: `Ketua ${statistik.approved_bendahara}`, tone: 'navy' }]
                                : []),
                            ...((statistik.approved_ketua ?? 0) > 0
                                ? [{ label: `Cair ${statistik.approved_ketua}`, tone: 'green' }]
                                : []),
                        ],
                        meta: totalMenunggu === 0 ? 'Semua antrean tuntas' : undefined,
                    },
                    {
                        key: 'lunas',
                        label: 'Pinjaman Lunas',
                        icon: CheckCircle2,
                        tone: 'green',
                        value: statistik.lunas,
                        privacy: false,
                        active: filters.status === 'lunas',
                        onClick: () => terapkanFilter({ status: filters.status === 'lunas' ? '' : 'lunas' }),
                        meta:
                            (statistik.ditolak ?? 0) > 0
                                ? `${statistik.ditolak} pengajuan ditolak`
                                : 'Pelunasan selesai',
                    },
                ]}
            />

            <Card padding="sm" className="mb-4 overflow-hidden">
                <div className="pb-3 border-b border-slate-100">
                    <div className="flex flex-col md:flex-row md:items-center gap-2">
                        <div className="flex-1 relative group">
                            <Search className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-brand-green transition-colors pointer-events-none" size={16} />
                            <TextField
                                type="search"
                                size="sm"
                                value={cari}
                                onChange={(e) => setCari(e.target.value)}
                                placeholder="Cari nama atau no. karyawan..."
                                aria-label="Cari pinjaman"
                                className="pl-10 pr-8 text-sm rounded-full border-slate-200 bg-white shadow-[0_1px_2px_rgba(15,23,42,0.03)] transition-all"
                            />
                            {cari && (
                                <button
                                    type="button"
                                    onClick={() => setCari('')}
                                    aria-label="Hapus pencarian"
                                    className="absolute right-2 top-1/2 -translate-y-1/2 w-6 h-6 inline-flex items-center justify-center rounded-full text-slate-400 hover:text-slate-600 hover:bg-slate-200/70"
                                >
                                    <X size={14} />
                                </button>
                            )}
                        </div>
                        <div className="flex gap-2">
                            <Select
                                size="sm"
                                value={cabangAktif ?? ''}
                                onChange={(e) => terapkanFilter({ cabang: e.target.value })}
                                aria-label="Filter cabang"
                                className="flex-1 min-w-0 md:flex-none md:w-40 text-sm rounded-full border-slate-200 bg-white"
                            >
                                <option value="">Semua Cabang</option>
                                {daftarCabangAman.map((c) => (
                                    <option key={c} value={c}>{c}</option>
                                ))}
                            </Select>
                            <Select
                                size="sm"
                                value={filters.status ?? ''}
                                onChange={(e) => terapkanFilter({ status: e.target.value })}
                                aria-label="Filter status"
                                className="flex-1 min-w-0 md:flex-none md:w-64 text-sm rounded-full border-slate-200 bg-white"
                            >
                                {statusOptions.map((s) => (
                                    <option key={s.value} value={s.value}>{s.label}</option>
                                ))}
                            </Select>
                            {filterAktif && (
                                <Button type="button" variant="ghost" size="sm" className="shrink-0 rounded-full" onClick={resetFilter}>
                                    <RefreshCw size={14} />
                                    Reset
                                </Button>
                            )}
                        </div>
                    </div>
                </div>
                <div className="overflow-x-auto">
                    <table className="w-full table-sticky-first table-head-static text-sm">
                        <thead>
                            <tr className="text-left bg-[#F8FAFC] border-b border-slate-200/80">
                                <th className="px-4 py-2.5 text-[11px] font-bold uppercase tracking-wider text-slate-500 whitespace-nowrap">Anggota</th>
                                <th className="px-4 py-2.5 text-[11px] font-bold uppercase tracking-wider text-slate-500 whitespace-nowrap">Nominal</th>
                                <th className="hidden lg:table-cell px-4 py-2.5 text-[11px] font-bold uppercase tracking-wider text-slate-500 whitespace-nowrap">Tenor</th>
                                <th className="hidden lg:table-cell px-4 py-2.5 text-[11px] font-bold uppercase tracking-wider text-slate-500 whitespace-nowrap">Tanggal</th>
                                <th className="px-4 py-2.5 text-[11px] font-bold uppercase tracking-wider text-slate-500 whitespace-nowrap">Status</th>
                                <th className="px-4 py-2.5 text-right text-[11px] font-bold uppercase tracking-wider text-slate-500 whitespace-nowrap"><span className="sr-only">Cetak</span></th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100 bg-white">
                            {pinjaman.data.length === 0 ? (
                                <tr>
                                    <td colSpan={6} className="px-4 py-10 text-center">
                                        <p className="text-sm font-semibold text-slate-600">Tidak ada data ditemukan</p>
                                        <p className="text-sm text-slate-500 mt-1">Coba ubah kata kunci atau filter.</p>
                                        {filterAktif && (
                                            <Button type="button" variant="outline" size="sm" className="mt-3 rounded-full" onClick={resetFilter}>
                                                Tampilkan semua
                                            </Button>
                                        )}
                                    </td>
                                </tr>
                            ) : (
                                pinjaman.data.map((p) => {
                                    const progres = progresAngsuran(p.angsuran);
                                    return (
                                        <tr key={p.id} onClick={() => bukaDetail(p)} onKeyDown={(e) => { if (e.key === 'Enter') bukaDetail(p); }} tabIndex={0} title="Klik untuk rincian" className="group hover:bg-[#F2FBF5] transition-colors cursor-pointer focus-visible:outline-none focus-visible:bg-[#F2FBF5]">
                                            <td className="px-4 py-2.5">
                                                <div className="flex items-center gap-2.5 min-w-0">
                                                    <FotoAnggota nama={p.nama} fotoUrl={p.foto_url} ukuran="sm" />
                                                    <div className="min-w-0">
                                                        <p className="text-sm font-semibold text-slate-800 truncate max-w-[160px] sm:max-w-[200px] group-hover:text-brand-navy transition-colors">{p.nama}</p>
                                                        <p className="text-xs text-slate-400 truncate">{p.no_karyawan}{p.cabang ? ` • ${p.cabang}` : ''}</p>
                                                    </div>
                                                    <ChevronRight size={15} className="ml-1 shrink-0 text-slate-300 opacity-0 -translate-x-1 group-hover:opacity-100 group-hover:translate-x-0 transition-all" aria-hidden="true" />
                                                </div>
                                                {p.pelunasan_resign_total > 0 && (
                                                    <p className="mt-1 flex items-center gap-1 text-xs text-rose-600">
                                                        <Wallet size={12} />
                                                        <span className="italic truncate privacy-blur">Lunas resign: -{formatRupiah(p.pelunasan_resign_total)}</span>
                                                    </p>
                                                )}
                                            </td>
                                            <td className="px-4 py-2.5 whitespace-nowrap">
                                                <p className="font-bold text-slate-800 tabular-nums privacy-blur">{formatRupiah(p.nominal)}</p>
                                                {progres && (
                                                    <div className="mt-1.5 w-28" title={`${progres.lunas}/${progres.total} cicilan lunas`}>
                                                        <div className="h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                                            <div className="h-full rounded-full bg-gradient-to-r from-brand-green to-brand-green-dark transition-all" style={{ width: `${progres.persen}%` }} />
                                                        </div>
                                                        <p className="text-[11px] text-slate-400 tabular-nums mt-0.5">{progres.lunas}/{progres.total} lunas</p>
                                                    </div>
                                                )}
                                            </td>
                                            <td className="hidden lg:table-cell px-4 py-2.5 text-slate-600 whitespace-nowrap">{p.tenor_bulan} bln</td>
                                            <td className="hidden lg:table-cell px-4 py-2.5 text-slate-600 whitespace-nowrap">{p.tanggal_pengajuan}</td>
                                            <td className="px-4 py-2.5">
                                                <div className="flex flex-wrap items-center gap-1.5">
                                                    <StatusBadge status={p.status} />
                                                    {p.status === 'aktif' && p.jumlah_terlambat > 0 && (
                                                        <span className="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-rose-50 text-rose-700 border border-rose-200/80">
                                                            Menunggak {p.jumlah_terlambat}x
                                                        </span>
                                                    )}
                                                    {p.penghapusan_aktif && (
                                                        <span className="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-800 border border-amber-200/80">
                                                            Proses Penghapusan
                                                        </span>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="px-4 py-2.5 text-right" onClick={(e) => e.stopPropagation()}>
                                                {p.status === 'aktif' && bisaCetakBaris(p) ? (
                                                    <button
                                                        onClick={(e) => bukaCetak(e, p)}
                                                        aria-label={`Cetak bukti pinjaman ${p.nama}`}
                                                        title="Cetak bukti"
                                                        className="inline-flex items-center justify-center w-9 h-9 rounded-xl border border-transparent text-slate-400 transition-all duration-200 hover:text-brand-green-dark hover:bg-brand-green-light hover:border-brand-green/30 hover:shadow-sm"
                                                    >
                                                        <Printer size={16} />
                                                    </button>
                                                ) : (
                                                    <span className="inline-block w-9" aria-hidden="true" />
                                                )}
                                            </td>
                                        </tr>
                                    );
                                })
                            )}
                        </tbody>
                    </table>
                </div>
            </Card>

            <Pagination links={pinjaman.links} />

            <Drawer show={drawerOpen} title={detailPinjaman ? `Pinjaman #${detailPinjaman.id} — ${detailPinjaman.nama}` : 'Rincian Pinjaman'} onClose={() => setDrawerOpen(false)} maxWidth="3xl">
                {detailPinjaman && (
                    <DetailDrawer
                        key={detailPinjaman.id}
                        pinjaman={detailPinjaman}
                        angsuran={detailPinjaman.angsuran ?? []}
                        pelunasan_resign={detailPinjaman.pelunasan_resign ?? { total: 0, tanggal: null }}
                        jurnal_pelunasan={detailPinjaman.jurnal_pelunasan ?? []}
                        onAjukanPenghapusan={(p) =>
                            bukaAjukanPenghapusan({
                                pinjaman_id: p.id,
                                nomor_dokumen: p.nomor_dokumen ?? `#${p.id}`,
                                nominal_pinjaman: p.nominal,
                                total_pokok_terbayar: p.total_pokok_terbayar ?? 0,
                                sisa_cicilan: p.sisa_cicilan ?? 0,
                                sisa_pokok: p.sisa_pokok ?? p.nominal,
                                sisa_bunga: p.sisa_bunga ?? 0,
                                jumlah_terlambat: p.jumlah_terlambat ?? 0,
                                anggota: {
                                    nama: p.nama,
                                    no_karyawan: p.no_karyawan,
                                    cabang: p.cabang,
                                    foto_url: p.foto_url,
                                },
                            })
                        }
                    />
                )}
            </Drawer>

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
                        <Suspense
                            fallback={
                                <DrawerBody>
                                    <div className="space-y-3 animate-pulse">
                                        <div className="h-24 rounded-2xl bg-slate-100" />
                                        <div className="h-24 rounded-2xl bg-slate-100" />
                                    </div>
                                </DrawerBody>
                            }
                        >
                            <AjukanPenghapusanDrawer
                                pinjamanMenunggak={pinjamanMenunggak}
                                loading={loadingMenunggak}
                                initialPinjaman={initialPinjamanHapus}
                                onClose={() => setAjukanOpen(false)}
                            />
                        </Suspense>
                    )}
                </Drawer>
            )}
        </AppLayout>
    );
}
