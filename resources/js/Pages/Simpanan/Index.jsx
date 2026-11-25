import AppLayout from '@/Layouts/AppLayout';
import { Head, router } from '@inertiajs/react';
import { Search, HeartHandshake, PiggyBank, Users, Wallet, ChevronRight, X, RefreshCw, History } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import useDebouncedValue from '@/Utils/useDebouncedValue';
import Button from '@/Components/ui/Button';
import Card from '@/Components/ui/Card';
import FotoAnggota from '@/Components/ui/FotoAnggota';
import Drawer from '@/Components/ui/Drawer';
import { DrawerHero, DrawerSection } from '@/Components/ui/DrawerSection';
import PrivacyWrapper from '@/Components/ui/PrivacyWrapper';
import StatusBadge from '@/Components/ui/StatusBadge';
import { UnifiedLedgerStrip } from '@/Components/ui/SummaryLayout';
import PageHeader from '@/Components/ui/PageHeader';
import TextField from '@/Components/ui/TextField';
import Select from '@/Components/ui/Select';
import { formatRupiah, formatRupiahSingkat } from '@/Utils/formatCurrency';
import Pagination from '@/Components/ui/Pagination';

const jenisLabel = { pokok: 'Pokok', wajib: 'Wajib', dana_sosial: 'Dana Sosial' };
const jenisTone = {
    pokok: 'bg-brand-navy/5 text-brand-navy border-brand-navy/15',
    wajib: 'bg-brand-green-light text-brand-green-dark border-emerald-200/80',
    dana_sosial: 'bg-rose-50 text-rose-700 border-rose-200/80',
};

export default function Index({
    anggota,
    filters = {},
    cabangAktif,
    statusAktif,
    daftarCabang = [],
    totalDanaSosialTerkumpul = 0,
    totalSimpananSeluruhAnggota = 0,
    totalSimpananOutstanding = 0,
    totalSimpananTampil = 0,
    totalPokok = 0,
    totalWajib = 0,
    statistikAnggota = { total: 0, aktif: 0, resign: 0 },
}) {
    const daftarCabangAman = Array.isArray(daftarCabang) ? daftarCabang : Object.values(daftarCabang ?? {});
    const [cari, setCari] = useState(filters.cari ?? '');
    const cariDebounced = useDebouncedValue(cari);
    const pertama = useRef(true);
    const [detailAnggota, setDetailAnggota] = useState(null);
    const [drawerOpen, setDrawerOpen] = useState(false);

    const filterAktif = Boolean((filters.cari ?? '') || cabangAktif || (filters.status ?? ''));

    function terapkan(overrides = {}) {
        router.get(
            route('simpanan.index'),
            {
                cari,
                cabang: cabangAktif,
                status: filters.status,
                ...overrides,
            },
            { preserveState: true, replace: true }
        );
    }

    function resetFilter() {
        setCari('');
        router.get(route('simpanan.index'), {}, { preserveState: true, replace: true });
    }

    useEffect(() => {
        if (pertama.current) {
            pertama.current = false;
            return;
        }
        if ((cariDebounced ?? '') !== (filters.cari ?? '')) {
            terapkan({ cari: cariDebounced ?? '' });
        }
    }, [cariDebounced]);

    useEffect(() => {
        setCari(filters.cari ?? '');
    }, [filters.cari]);

    function bukaDetail(a) {
        setDetailAnggota(a);
        setDrawerOpen(true);
    }

    return (
        <AppLayout>
            <Head title="Simpanan" />

            <PageHeader title="Simpanan Anggota" subtitle="Rekap pokok + wajib seluruh anggota" />

            {/* Ringkasan Eksekutif Ringkas */}
            <UnifiedLedgerStrip
                columns={4}
                items={[
                    {
                        key: 'aktif',
                        variant: 'anchor',
                        label: cabangAktif ? `Simpanan (${cabangAktif})` : 'Simpanan Aktif',
                        icon: PiggyBank,
                        value: formatRupiah(totalSimpananTampil),
                        active: filters.status === 'aktif',
                        onClick: () => terapkan({ status: filters.status === 'aktif' ? '' : 'aktif' }),
                        badge:
                            filters.status === 'aktif' ? (
                                <span className="px-1.5 py-0.5 rounded bg-emerald-400/20 text-emerald-200 border border-emerald-300/35 text-[10px] font-semibold">
                                    Aktif ✓
                                </span>
                            ) : null,
                        meta: (
                            <>
                                Pokok <PrivacyWrapper>{formatRupiahSingkat(totalPokok)}</PrivacyWrapper> • Wajib{' '}
                                <PrivacyWrapper>{formatRupiahSingkat(totalWajib)}</PrivacyWrapper>
                            </>
                        ),
                    },
                    {
                        key: 'gross',
                        label: 'Akumulasi Gross',
                        icon: Wallet,
                        tone: 'sky',
                        value: formatRupiah(totalPokok + totalWajib),
                        meta: 'Pokok & wajib tercatat',
                    },
                    {
                        key: 'dansos',
                        label: 'Dana Sosial',
                        icon: HeartHandshake,
                        tone: 'green',
                        value: formatRupiah(totalDanaSosialTerkumpul),
                        meta: 'Iuran sosial kumulatif',
                    },
                    {
                        key: 'anggota',
                        label: 'Partisipasi Anggota',
                        icon: Users,
                        tone: 'amber',
                        value: statistikAnggota.total,
                        privacy: false,
                        active: filters.status === 'resign',
                        onClick: () => terapkan({ status: filters.status === 'resign' ? '' : 'resign' }),
                        meta: `${statistikAnggota.aktif} aktif${statistikAnggota.resign > 0 ? ` • ${statistikAnggota.resign} resign` : ''}`,
                    },
                ]}
            />

            <Card padding="sm" className="shadow-md border-slate-200/70 mb-4 overflow-hidden">
                <div className="pb-3 mb-1 border-b border-slate-100">
                    <div className="flex flex-col md:flex-row md:items-center gap-2">
                        <div className="flex-1 relative group">
                            <Search className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-brand-green transition-colors pointer-events-none" size={16} />
                            <TextField
                                type="search"
                                size="sm"
                                value={cari}
                                onChange={(e) => setCari(e.target.value)}
                                placeholder="Cari nama atau no. karyawan..."
                                aria-label="Cari anggota"
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
                                onChange={(e) => terapkan({ cabang: e.target.value })}
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
                                onChange={(e) => terapkan({ status: e.target.value })}
                                aria-label="Filter status anggota"
                                className="flex-1 min-w-0 md:flex-none md:w-40 text-sm rounded-full border-slate-200 bg-white"
                            >
                                <option value="">Semua Status</option>
                                <option value="aktif">Anggota Aktif</option>
                                <option value="resign">Anggota Resign</option>
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
                                <th className="hidden sm:table-cell px-4 py-2.5 text-[11px] font-bold uppercase tracking-wider text-slate-500 whitespace-nowrap">Cabang</th>
                                <th className="hidden md:table-cell px-4 py-2.5 text-[11px] font-bold uppercase tracking-wider text-slate-500 whitespace-nowrap">Status</th>
                                <th className="px-4 py-2.5 text-right text-[11px] font-bold uppercase tracking-wider text-slate-500 whitespace-nowrap">Total</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100 bg-white">
                            {anggota.data.length === 0 ? (
                                <tr>
                                    <td colSpan={4} className="px-4 py-10 text-center">
                                        <p className="text-sm font-semibold text-slate-600">
                                            {cabangAktif ? `Belum ada anggota di cabang ${cabangAktif}.` : 'Tidak ada data ditemukan'}
                                        </p>
                                        <p className="text-sm text-slate-500 mt-1">Coba ubah kata kunci atau filter.</p>
                                        {filterAktif && (
                                            <Button type="button" variant="outline" size="sm" className="mt-3 rounded-full" onClick={resetFilter}>
                                                Tampilkan semua
                                            </Button>
                                        )}
                                    </td>
                                </tr>
                            ) : (
                                anggota.data.map((a) => (
                                    <tr key={a.id} onClick={() => bukaDetail(a)} onKeyDown={(e) => { if (e.key === 'Enter') bukaDetail(a); }} tabIndex={0} title="Klik untuk rincian" className="group hover:bg-[#F2FBF5] transition-colors cursor-pointer focus-visible:outline-none focus-visible:bg-[#F2FBF5]">
                                        <td className="px-4 py-2.5">
                                            <div className="flex items-center gap-2.5 min-w-0">
                                                <FotoAnggota nama={a.nama} fotoUrl={a.foto_url} ukuran="sm" />
                                                <div className="min-w-0">
                                                    <p className="text-sm font-semibold text-slate-800 truncate max-w-[180px] sm:max-w-[240px] group-hover:text-brand-navy transition-colors">{a.nama}</p>
                                                    <p className="text-xs text-slate-400 truncate">{a.no_karyawan}</p>
                                                </div>
                                                <ChevronRight size={15} className="ml-1 shrink-0 text-slate-300 opacity-0 -translate-x-1 group-hover:opacity-100 group-hover:translate-x-0 transition-all" aria-hidden="true" />
                                            </div>
                                        </td>
                                        <td className="hidden sm:table-cell px-4 py-2.5 text-slate-600 whitespace-nowrap">{a.cabang}</td>
                                        <td className="hidden md:table-cell px-4 py-2.5">
                                            <StatusBadge status={a.status} />
                                        </td>
                                        <td className="px-4 py-2.5 text-right font-bold text-slate-800 tabular-nums whitespace-nowrap privacy-blur">
                                            {formatRupiah(a.total_simpanan)}
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            </Card>

            <Pagination links={anggota.links} />

            <Drawer
                show={drawerOpen}
                title={`Simpanan — ${detailAnggota?.nama ?? ''}`}
                badge={detailAnggota ? <StatusBadge status={detailAnggota.status} /> : null}
                onClose={() => setDrawerOpen(false)}
            >
                {detailAnggota && (
                    <div className="space-y-3.5">
                        <DrawerHero>
                            <div className="flex items-center gap-3">
                                <FotoAnggota nama={detailAnggota.nama} fotoUrl={detailAnggota.foto_url} ukuran="md" className="bg-white/15 ring-white/20 shrink-0" />
                                <div className="min-w-0 flex-1">
                                    <p className="text-sm sm:text-base font-bold text-white truncate">{detailAnggota.nama}</p>
                                    <p className="text-xs text-slate-300 truncate mt-0.5">
                                        <span className="font-mono">{detailAnggota.no_karyawan}</span> • {detailAnggota.cabang}
                                    </p>
                                </div>
                            </div>
                        </DrawerHero>

                        <DrawerSection title="Total Simpanan" icon={PiggyBank} tone="emerald" padding="none">
                            <div className="px-4 py-3.5">
                                <p className="text-2xl sm:text-[26px] font-extrabold text-slate-900 tabular-nums privacy-blur">
                                    {formatRupiah(detailAnggota.total_simpanan)}
                                </p>
                            </div>
                            <div className="grid grid-cols-2 divide-x divide-slate-100 border-t border-slate-100">
                                <div className="px-4 py-2.5">
                                    <p className="text-[11px] font-medium text-slate-500">Simpanan Pokok</p>
                                    <p className="text-sm font-bold text-slate-800 tabular-nums mt-0.5 privacy-blur">
                                        {formatRupiah(detailAnggota.simpanan_pokok ?? 0)}
                                    </p>
                                </div>
                                <div className="px-4 py-2.5">
                                    <p className="text-[11px] font-medium text-slate-500">Simpanan Wajib</p>
                                    <p className="text-sm font-bold text-slate-800 tabular-nums mt-0.5 privacy-blur">
                                        {formatRupiah(detailAnggota.simpanan_wajib ?? 0)}
                                    </p>
                                </div>
                            </div>
                        </DrawerSection>

                        {detailAnggota.alokasi_pelunasan_resign > 0 && (
                            <DrawerSection
                                title="Pelunasan saat Resign"
                                description="Dialokasikan untuk pelunasan pinjaman"
                                icon={Wallet}
                                tone="rose"
                                padding="none"
                            >
                                <div className="grid grid-cols-3 divide-x divide-slate-100">
                                    <div className="px-3.5 py-2.5">
                                        <p className="text-[11px] text-slate-500">Dari Pokok</p>
                                        <p className="text-sm font-bold text-slate-800 tabular-nums mt-0.5 privacy-blur">
                                            -{formatRupiah(detailAnggota.alokasi_dari_pokok)}
                                        </p>
                                    </div>
                                    <div className="px-3.5 py-2.5">
                                        <p className="text-[11px] text-slate-500">Dari Wajib</p>
                                        <p className="text-sm font-bold text-slate-800 tabular-nums mt-0.5 privacy-blur">
                                            -{formatRupiah(detailAnggota.alokasi_dari_wajib)}
                                        </p>
                                    </div>
                                    <div className="px-3.5 py-2.5 bg-rose-50/40">
                                        <p className="text-[11px] text-rose-700 font-medium">Total</p>
                                        <p className="text-sm font-bold text-rose-600 tabular-nums mt-0.5 privacy-blur">
                                            -{formatRupiah(detailAnggota.alokasi_pelunasan_resign)}
                                        </p>
                                    </div>
                                </div>
                                {detailAnggota.tanggal_resign && (
                                    <div className="px-4 py-2 bg-[#F8FAFC] border-t border-slate-100 text-xs text-slate-500">
                                        Tanggal proses: {detailAnggota.tanggal_resign}
                                    </div>
                                )}
                            </DrawerSection>
                        )}

                        <DrawerSection title={`Riwayat Simpanan (${detailAnggota.riwayat.length})`} icon={History} padding="none">
                            {detailAnggota.riwayat.length === 0 ? (
                                <p className="py-8 text-center text-sm text-slate-500">Belum ada riwayat simpanan.</p>
                            ) : (
                                <ul className="divide-y divide-slate-100">
                                    {detailAnggota.riwayat.map((r, i) => (
                                        <li key={i} className="flex items-center justify-between gap-3 px-4 py-2.5 hover:bg-brand-green-light/25 transition-colors">
                                            <div className="flex items-center gap-2.5 min-w-0">
                                                <span className={`px-2.5 py-0.5 text-xs font-bold rounded-full border shrink-0 ${jenisTone[r.jenis] ?? 'bg-slate-100 text-slate-600 border-slate-200'}`}>
                                                    {jenisLabel[r.jenis] ?? r.jenis}
                                                </span>
                                                <p className="text-xs text-slate-500 truncate">{r.bulan_periode} • {r.tanggal_input}</p>
                                            </div>
                                            <p className="text-sm font-bold text-slate-800 tabular-nums whitespace-nowrap privacy-blur">{formatRupiah(r.jumlah)}</p>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </DrawerSection>
                    </div>
                )}
            </Drawer>
        </AppLayout>
    );
}
