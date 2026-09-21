import AppLayout from '@/Layouts/AppLayout';
import { Head, router } from '@inertiajs/react';
import { CheckCircle2, Clock, BadgeCheck, Search, XCircle, Wallet, ChevronRight, ChevronDown, X, RefreshCw, Printer } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import useDebouncedValue from '@/Utils/useDebouncedValue';
import Button from '@/Components/ui/Button';
import Card from '@/Components/ui/Card';
import FotoAnggota from '@/Components/ui/FotoAnggota';
import StatWidget from '@/Components/ui/StatWidget';
import StatusBadge from '@/Components/ui/StatusBadge';
import PageHeader from '@/Components/ui/PageHeader';
import TextField from '@/Components/ui/TextField';
import Select from '@/Components/ui/Select';
import Drawer from '@/Components/ui/Drawer';
import DetailDrawer from './Partials/DetailDrawer';
import { formatRupiah } from '@/Utils/formatCurrency';
import Pagination from '@/Components/ui/Pagination';

const statusOptions = [
    { value: '', label: 'Semua Status' },
    { value: 'diajukan', label: 'Diajukan' },
    { value: 'approved_bendahara', label: 'Disetujui Bendahara' },
    { value: 'aktif', label: 'Aktif' },
    { value: 'lunas', label: 'Lunas' },
    { value: 'ditolak', label: 'Ditolak' },
];

function progresAngsuran(angsuran = []) {
    const total = angsuran.length;
    if (!total) return null;
    const lunas = angsuran.filter((a) => a.status === 'lunas').length;
    return { lunas, total, persen: Math.round((lunas / total) * 100) };
}

export default function Index({ pinjaman, filters, statistik, cabangAktif, daftarCabang }) {
    const [cari, setCari] = useState(filters.cari ?? '');
    const cariDebounced = useDebouncedValue(cari);
    const pertama = useRef(true);
    const [detailPinjaman, setDetailPinjaman] = useState(null);
    const [drawerOpen, setDrawerOpen] = useState(false);
    const [rincianTerbuka, setRincianTerbuka] = useState(false);

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

            <PageHeader title="Pinjaman" subtitle={`${pinjaman.total} pengajuan tercatat`} />

            <div className="rounded-2xl bg-gradient-to-r from-brand-navy to-brand-navy-light text-white px-5 py-4 mb-3 shadow-md shadow-brand-navy/20">
                <div className="flex items-center gap-3">
                    <span className="w-11 h-11 rounded-2xl bg-white/15 inline-flex items-center justify-center shrink-0">
                        <Wallet size={22} />
                    </span>
                    <div className="min-w-0 flex-1">
                        <p className="text-xs text-white/70">Total Pengajuan Pinjaman</p>
                        <p className="text-2xl font-bold tabular-nums leading-tight">{statistik.total}</p>
                    </div>
                    <button
                        type="button"
                        onClick={() => setRincianTerbuka((v) => !v)}
                        aria-expanded={rincianTerbuka}
                        className="inline-flex items-center gap-1.5 text-xs font-semibold text-white/80 hover:text-white bg-white/10 hover:bg-white/20 rounded-full px-3 py-1.5 transition-colors shrink-0"
                    >
                        Rincian
                        <ChevronDown size={14} className={`transition-transform ${rincianTerbuka ? 'rotate-180' : ''}`} />
                    </button>
                </div>
                {rincianTerbuka && (
                    <dl className="grid grid-cols-2 sm:grid-cols-3 gap-2 mt-3 pt-3 border-t border-white/15 text-sm">
                        <div className="rounded-xl bg-white/10 px-3 py-2">
                            <dt className="text-xs text-white/70">Diajukan</dt>
                            <dd className="font-bold tabular-nums">{statistik.diajukan}</dd>
                        </div>
                        <div className="rounded-xl bg-white/10 px-3 py-2">
                            <dt className="text-xs text-white/70">Acc Bendahara</dt>
                            <dd className="font-bold tabular-nums">{statistik.approved_bendahara}</dd>
                        </div>
                        <div className="rounded-xl bg-white/10 px-3 py-2">
                            <dt className="text-xs text-white/70">Aktif</dt>
                            <dd className="font-bold tabular-nums">{statistik.aktif}</dd>
                        </div>
                        <div className="rounded-xl bg-white/10 px-3 py-2">
                            <dt className="text-xs text-white/70">Lunas</dt>
                            <dd className="font-bold tabular-nums">{statistik.lunas}</dd>
                        </div>
                        <div className="rounded-xl bg-white/10 px-3 py-2">
                            <dt className="text-xs text-white/70">Ditolak</dt>
                            <dd className="font-bold tabular-nums">{statistik.ditolak}</dd>
                        </div>
                        <div className="rounded-xl bg-white/10 px-3 py-2">
                            <dt className="text-xs text-white/70">Halaman ini</dt>
                            <dd className="font-bold tabular-nums">{pinjaman.data.length}/{pinjaman.total}</dd>
                        </div>
                    </dl>
                )}
            </div>

            <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
                <div className="transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md rounded-xl">
                    <StatWidget compact label="Menunggu" value={statistik.diajukan + statistik.approved_bendahara} icon={Clock} tone="amber" />
                </div>
                <div className="transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md rounded-xl">
                    <StatWidget compact label="Aktif" value={statistik.aktif} icon={BadgeCheck} tone="green" />
                </div>
                <div className="transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md rounded-xl">
                    <StatWidget compact label="Lunas" value={statistik.lunas} icon={CheckCircle2} tone="green" />
                </div>
                <div className="transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md rounded-xl">
                    <StatWidget compact label="Ditolak" value={statistik.ditolak} icon={XCircle} tone="rose" />
                </div>
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
                                aria-label="Cari pinjaman"
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
                                onChange={(e) => terapkanFilter({ cabang: e.target.value })}
                                aria-label="Filter cabang"
                                className="flex-1 min-w-0 md:flex-none md:w-40 text-sm rounded-full border-slate-200 bg-slate-50/60"
                            >
                                <option value="">Semua Cabang</option>
                                {daftarCabang.map((c) => (
                                    <option key={c} value={c}>{c}</option>
                                ))}
                            </Select>
                            <Select
                                size="sm"
                                value={filters.status ?? ''}
                                onChange={(e) => terapkanFilter({ status: e.target.value })}
                                aria-label="Filter status"
                                className="flex-1 min-w-0 md:flex-none md:w-44 text-sm rounded-full border-slate-200 bg-slate-50/60"
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
                            <tr className="text-left bg-gradient-to-r from-slate-50 to-white border-b border-slate-200/80">
                                <th className="px-4 py-2.5 text-xs font-bold uppercase tracking-wide text-slate-500 whitespace-nowrap">Anggota</th>
                                <th className="px-4 py-2.5 text-xs font-bold uppercase tracking-wide text-slate-500 whitespace-nowrap">Nominal</th>
                                <th className="hidden lg:table-cell px-4 py-2.5 text-xs font-bold uppercase tracking-wide text-slate-500 whitespace-nowrap">Tenor</th>
                                <th className="hidden lg:table-cell px-4 py-2.5 text-xs font-bold uppercase tracking-wide text-slate-500 whitespace-nowrap">Tanggal</th>
                                <th className="px-4 py-2.5 text-xs font-bold uppercase tracking-wide text-slate-500">Status</th>
                                <th className="px-4 py-2.5 text-right text-xs font-bold uppercase tracking-wide text-slate-500"><span className="sr-only">Cetak</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            {pinjaman.data.length === 0 ? (
                                <tr>
                                    <td colSpan={6} className="px-4 py-10 text-center">
                                        <p className="text-sm font-semibold text-slate-600">Tidak ada data ditemukan</p>
                                        <p className="text-sm text-slate-400 mt-1">Coba ubah kata kunci atau filter.</p>
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
                                        <tr key={p.id} onClick={() => bukaDetail(p)} onKeyDown={(e) => { if (e.key === 'Enter') bukaDetail(p); }} tabIndex={0} title="Klik untuk rincian" className="group border-b border-slate-50 last:border-0 hover:bg-gradient-to-r hover:from-brand-green-light/40 hover:to-transparent transition-all cursor-pointer focus-visible:outline-none focus-visible:bg-brand-green-light/50">
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
                                                        <span className="italic truncate">Lunas resign: -{formatRupiah(p.pelunasan_resign_total)}</span>
                                                    </p>
                                                )}
                                            </td>
                                            <td className="px-4 py-2.5 whitespace-nowrap">
                                                <p className="font-bold text-slate-800 tabular-nums">{formatRupiah(p.nominal)}</p>
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
                                                <StatusBadge status={p.status} />
                                            </td>
                                            <td className="px-4 py-2.5 text-right" onClick={(e) => e.stopPropagation()}>
                                                {p.status === 'aktif' ? (
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
                    />
                )}
            </Drawer>
        </AppLayout>
    );
}
