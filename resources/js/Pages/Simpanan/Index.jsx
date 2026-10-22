import AppLayout from '@/Layouts/AppLayout';
import { Head, router } from '@inertiajs/react';
import { Search, HeartHandshake, PiggyBank, Users, Wallet, ChevronRight, X, RefreshCw } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import useDebouncedValue from '@/Utils/useDebouncedValue';
import Button from '@/Components/ui/Button';
import Card from '@/Components/ui/Card';
import FotoAnggota from '@/Components/ui/FotoAnggota';
import Drawer from '@/Components/ui/Drawer';
import StatusBadge from '@/Components/ui/StatusBadge';
import PageHeader from '@/Components/ui/PageHeader';
import TextField from '@/Components/ui/TextField';
import Select from '@/Components/ui/Select';
import { formatRupiah } from '@/Utils/formatCurrency';
import Pagination from '@/Components/ui/Pagination';

const jenisLabel = { pokok: 'Pokok', wajib: 'Wajib', dana_sosial: 'Dana Sosial' };
const jenisTone = {
    pokok: 'bg-brand-navy/5 text-brand-navy',
    wajib: 'bg-brand-green-light text-brand-green-dark',
    dana_sosial: 'bg-rose-50 text-rose-700',
};

function Seksi({ judul, children }) {
    return (
        <section className="rounded-2xl border border-slate-200/70 bg-white p-4 shadow-sm">
            <h3 className="text-sm font-bold text-slate-800 mb-3">{judul}</h3>
            {children}
        </section>
    );
}

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

            {/* KPI Stat Grid Terpadu — Menghilangkan Duplikasi Banner */}
            <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
                {/* 1. Total Simpanan Aktif */}
                <button
                    type="button"
                    onClick={() => terapkan({ status: filters.status === 'aktif' ? '' : 'aktif' })}
                    className={`text-left rounded-2xl p-4 border transition-all duration-200 cursor-pointer group flex flex-col justify-between ${
                        filters.status === 'aktif'
                            ? 'bg-blue-50/40 border-brand-navy/40 ring-2 ring-brand-navy/10 shadow-md'
                            : 'bg-white border-slate-200/80 hover:border-slate-300 shadow-xs hover:shadow-md'
                    }`}
                >
                    <div className="flex items-center justify-between gap-2">
                        <span className="text-xs font-semibold text-slate-500 group-hover:text-brand-navy transition-colors">
                            {cabangAktif ? `Simpanan (${cabangAktif})` : 'Total Simpanan Aktif'}
                        </span>
                        <span className="w-8 h-8 rounded-xl bg-brand-navy/5 text-brand-navy flex items-center justify-center shrink-0">
                            <PiggyBank size={16} />
                        </span>
                    </div>
                    <div className="mt-2">
                        <p className="text-xl sm:text-2xl font-bold text-slate-800 tabular-nums leading-tight privacy-blur">
                            {formatRupiah(totalSimpananTampil)}
                        </p>
                        <p className="text-xs text-slate-400 mt-1 truncate">
                            {cabangAktif ? `Outstanding cabang ${cabangAktif}` : 'Outstanding anggota aktif'}
                        </p>
                    </div>
                </button>

                {/* 2. Komposisi Simpanan (Pokok & Wajib) */}
                <div className="rounded-2xl p-4 border bg-white border-slate-200/80 shadow-xs flex flex-col justify-between">
                    <div className="flex items-center justify-between gap-2">
                        <span className="text-xs font-semibold text-slate-500">
                            Komposisi Simpanan
                        </span>
                        <span className="w-8 h-8 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center shrink-0">
                            <Wallet size={16} />
                        </span>
                    </div>
                    <div className="mt-2">
                        <p className="text-xl sm:text-2xl font-bold text-slate-800 tabular-nums leading-tight privacy-blur">
                            {formatRupiah(totalPokok + totalWajib)}
                        </p>
                        <div className="flex items-center gap-1.5 mt-1.5 flex-wrap text-[11px]">
                            <span className="font-semibold text-brand-navy bg-brand-navy/5 px-1.5 py-0.5 rounded privacy-blur" title="Simpanan Pokok">
                                Pokok {formatRupiah(totalPokok)}
                            </span>
                            <span className="font-semibold text-emerald-800 bg-emerald-50 px-1.5 py-0.5 rounded privacy-blur" title="Simpanan Wajib">
                                Wajib {formatRupiah(totalWajib)}
                            </span>
                        </div>
                    </div>
                </div>

                {/* 3. Dana Sosial Terkumpul */}
                <div className="rounded-2xl p-4 border bg-white border-slate-200/80 shadow-xs flex flex-col justify-between">
                    <div className="flex items-center justify-between gap-2">
                        <span className="text-xs font-semibold text-slate-500">
                            Dana Sosial
                        </span>
                        <span className="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center shrink-0">
                            <HeartHandshake size={16} />
                        </span>
                    </div>
                    <div className="mt-2">
                        <p className="text-xl sm:text-2xl font-bold text-slate-800 tabular-nums leading-tight privacy-blur">
                            {formatRupiah(totalDanaSosialTerkumpul)}
                        </p>
                        <p className="text-xs text-slate-400 mt-1 truncate">
                            Iuran sosial kumulatif
                        </p>
                    </div>
                </div>

                {/* 4. Partisipasi Anggota */}
                <button
                    type="button"
                    onClick={() => terapkan({ status: filters.status === 'resign' ? '' : 'resign' })}
                    className={`text-left rounded-2xl p-4 border transition-all duration-200 cursor-pointer group flex flex-col justify-between ${
                        filters.status === 'resign'
                            ? 'bg-amber-50/40 border-amber-400 ring-2 ring-amber-400/20 shadow-md'
                            : 'bg-white border-slate-200/80 hover:border-slate-300 shadow-xs hover:shadow-md'
                    }`}
                >
                    <div className="flex items-center justify-between gap-2">
                        <span className="text-xs font-semibold text-slate-500 group-hover:text-amber-800 transition-colors">
                            Partisipasi Anggota
                        </span>
                        <span className="w-8 h-8 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center shrink-0">
                            <Users size={16} />
                        </span>
                    </div>
                    <div className="mt-2">
                        <p className="text-xl sm:text-2xl font-bold text-slate-800 tabular-nums leading-tight">
                            {statistikAnggota.total}
                        </p>
                        <div className="flex items-center gap-1.5 mt-1.5 flex-wrap text-[11px]">
                            <span className="font-semibold text-emerald-800 bg-emerald-50 px-1.5 py-0.5 rounded">
                                {statistikAnggota.aktif} aktif
                            </span>
                            <span className="font-semibold text-slate-600 bg-slate-100 px-1.5 py-0.5 rounded">
                                {statistikAnggota.resign} resign
                            </span>
                        </div>
                    </div>
                </button>
            </div>

            <Card padding="sm" className="shadow-md border-slate-200/70 mb-4">
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
                                className="pl-10 pr-8 text-sm rounded-full border-slate-200 bg-slate-50/60 focus:bg-white shadow-inner focus:shadow-md transition-all"
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
                                className="flex-1 min-w-0 md:flex-none md:w-40 text-sm rounded-full border-slate-200 bg-slate-50/60"
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
                                className="flex-1 min-w-0 md:flex-none md:w-40 text-sm rounded-full border-slate-200 bg-slate-50/60"
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
                            <tr className="text-left bg-gradient-to-r from-slate-50 to-white border-b border-slate-200/80">
                                <th className="px-4 py-2.5 text-xs font-bold uppercase tracking-wide text-slate-500 whitespace-nowrap">Anggota</th>
                                <th className="hidden sm:table-cell px-4 py-2.5 text-xs font-bold uppercase tracking-wide text-slate-500 whitespace-nowrap">Cabang</th>
                                <th className="hidden md:table-cell px-4 py-2.5 text-xs font-bold uppercase tracking-wide text-slate-500">Status</th>
                                <th className="px-4 py-2.5 text-right text-xs font-bold uppercase tracking-wide text-slate-500 whitespace-nowrap">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            {anggota.data.length === 0 ? (
                                <tr>
                                    <td colSpan={4} className="px-4 py-10 text-center">
                                        <p className="text-sm font-semibold text-slate-600">
                                            {cabangAktif ? `Belum ada anggota di cabang ${cabangAktif}.` : 'Tidak ada data ditemukan'}
                                        </p>
                                        <p className="text-sm text-slate-400 mt-1">Coba ubah kata kunci atau filter.</p>
                                        {filterAktif && (
                                            <Button type="button" variant="outline" size="sm" className="mt-3 rounded-full" onClick={resetFilter}>
                                                Tampilkan semua
                                            </Button>
                                        )}
                                    </td>
                                </tr>
                            ) : (
                                anggota.data.map((a) => (
                                    <tr key={a.id} onClick={() => bukaDetail(a)} onKeyDown={(e) => { if (e.key === 'Enter') bukaDetail(a); }} tabIndex={0} title="Klik untuk rincian" className="group border-b border-slate-50 last:border-0 hover:bg-gradient-to-r hover:from-brand-green-light/40 hover:to-transparent transition-all cursor-pointer focus-visible:outline-none focus-visible:bg-brand-green-light/50">
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

            <Drawer show={drawerOpen} title={`Simpanan — ${detailAnggota?.nama ?? ''}`} onClose={() => setDrawerOpen(false)}>
                {detailAnggota && (
                    <div className="space-y-3">
                        <div className="flex items-center gap-3 rounded-2xl bg-gradient-to-r from-brand-navy to-brand-navy-light text-white px-4 py-3 shadow-md shadow-brand-navy/20">
                            <FotoAnggota nama={detailAnggota.nama} fotoUrl={detailAnggota.foto_url} ukuran="md" className="bg-white/15 ring-white/20" />
                            <div className="min-w-0 flex-1">
                                <p className="text-sm font-bold truncate">{detailAnggota.nama}</p>
                                <p className="text-xs text-white/70 truncate">{detailAnggota.no_karyawan} • {detailAnggota.cabang}</p>
                            </div>
                            <StatusBadge status={detailAnggota.status} />
                        </div>

                        <div className="rounded-2xl border border-brand-green/30 bg-brand-green-light/40 p-4">
                            <p className="text-xs font-semibold uppercase tracking-wide text-brand-green-dark/70">Total Simpanan</p>
                            <p className="text-3xl font-bold text-slate-800 tabular-nums mt-0.5 privacy-blur">{formatRupiah(detailAnggota.total_simpanan)}</p>
                            <div className="grid grid-cols-2 gap-2 mt-3 pt-3 border-t border-brand-green/20">
                                <div className="rounded-xl bg-white/80 p-2 text-center">
                                    <p className="text-[11px] font-semibold text-slate-500">Pokok</p>
                                    <p className="text-sm font-bold text-slate-800 tabular-nums privacy-blur">{formatRupiah(detailAnggota.simpanan_pokok ?? 0)}</p>
                                </div>
                                <div className="rounded-xl bg-white/80 p-2 text-center">
                                    <p className="text-[11px] font-semibold text-slate-500">Wajib</p>
                                    <p className="text-sm font-bold text-slate-800 tabular-nums privacy-blur">{formatRupiah(detailAnggota.simpanan_wajib ?? 0)}</p>
                                </div>
                            </div>
                        </div>

                        {detailAnggota.alokasi_pelunasan_resign > 0 && (
                            <Seksi judul="Pelunasan saat Resign">
                                <div className="flex items-start gap-2.5">
                                    <span className="w-9 h-9 rounded-xl bg-rose-50 text-rose-600 inline-flex items-center justify-center shrink-0">
                                        <Wallet size={17} />
                                    </span>
                                    <div className="flex-1 min-w-0">
                                        <p className="text-sm font-semibold text-rose-700">Dialokasikan untuk pelunasan pinjaman</p>
                                        <div className="grid grid-cols-3 gap-2 mt-2 text-sm">
                                            <div className="rounded-xl bg-slate-50 px-2.5 py-1.5">
                                                <p className="text-[11px] text-slate-400">Dari Pokok</p>
                                                <p className="font-bold text-slate-800 tabular-nums privacy-blur">-{formatRupiah(detailAnggota.alokasi_dari_pokok)}</p>
                                            </div>
                                            <div className="rounded-xl bg-slate-50 px-2.5 py-1.5">
                                                <p className="text-[11px] text-slate-400">Dari Wajib</p>
                                                <p className="font-bold text-slate-800 tabular-nums privacy-blur">-{formatRupiah(detailAnggota.alokasi_dari_wajib)}</p>
                                            </div>
                                            <div className="rounded-xl bg-rose-50 px-2.5 py-1.5">
                                                <p className="text-[11px] text-rose-400">Total</p>
                                                <p className="font-bold text-rose-600 tabular-nums privacy-blur">-{formatRupiah(detailAnggota.alokasi_pelunasan_resign)}</p>
                                            </div>
                                        </div>
                                        {detailAnggota.tanggal_resign && (
                                            <p className="text-xs text-slate-400 mt-2">Tanggal proses: {detailAnggota.tanggal_resign}</p>
                                        )}
                                    </div>
                                </div>
                            </Seksi>
                        )}

                        <Seksi judul={`Riwayat (${detailAnggota.riwayat.length})`}>
                            {detailAnggota.riwayat.length === 0 ? (
                                <p className="py-6 text-center text-sm text-slate-400">Belum ada riwayat simpanan.</p>
                            ) : (
                                <ul className="divide-y divide-slate-50 -mx-1">
                                    {detailAnggota.riwayat.map((r, i) => (
                                        <li key={i} className="flex items-center justify-between gap-3 px-1 py-2.5">
                                            <div className="flex items-center gap-2.5 min-w-0">
                                                <span className={`px-2.5 py-1 text-xs font-bold rounded-full shrink-0 ${jenisTone[r.jenis] ?? 'bg-slate-100 text-slate-600'}`}>
                                                    {jenisLabel[r.jenis] ?? r.jenis}
                                                </span>
                                                <p className="text-xs text-slate-400 truncate">{r.bulan_periode} • {r.tanggal_input}</p>
                                            </div>
                                            <p className="text-sm font-bold text-slate-800 tabular-nums whitespace-nowrap privacy-blur">{formatRupiah(r.jumlah)}</p>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </Seksi>
                    </div>
                )}
            </Drawer>
        </AppLayout>
    );
}
