import AppLayout from '@/Layouts/AppLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { BookOpenText, ChevronRight, Percent, Search, Users, Wallet, X } from 'lucide-react';
import Card from '@/Components/ui/Card';
import FotoAnggota from '@/Components/ui/FotoAnggota';
import PageHeader from '@/Components/ui/PageHeader';
import Pagination from '@/Components/ui/Pagination';
import Select from '@/Components/ui/Select';
import StatusBadge from '@/Components/ui/StatusBadge';
import TextField from '@/Components/ui/TextField';
import { formatRupiah } from '@/Utils/formatCurrency';
import useDebouncedValue from '@/Utils/useDebouncedValue';

export default function Index({
    rekening,
    filters = {},
    daftarCabang = [],
    statistik = {
        total_saldo_aktif: 0,
        jumlah_aktif: 0,
        jumlah_diajukan: 0,
        total_bunga_tahun_ini: 0,
        suku_bunga_aktif: 2,
    },
}) {
    const daftarCabangAman = Array.isArray(daftarCabang) ? daftarCabang : Object.values(daftarCabang ?? {});
    const [cari, setCari] = useState(filters.cari ?? '');
    const cariDebounced = useDebouncedValue(cari);
    const pertama = useRef(true);

    const filterAktif = Boolean((filters.cari ?? '') || (filters.cabang ?? '') || (filters.status ?? ''));

    function terapkan(overrides = {}) {
        router.get(
            route('tabungan.index'),
            {
                cari,
                cabang: filters.cabang ?? '',
                status: filters.status ?? '',
                ...overrides,
            },
            { preserveState: true, replace: true }
        );
    }

    function resetFilter() {
        setCari('');
        router.get(route('tabungan.index'), {}, { preserveState: true, replace: true });
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

    return (
        <AppLayout>
            <Head title="Tabungan Anggota" />

            <PageHeader
                title="Tabungan Anggota"
                subtitle="Daftar rekening tabungan sukarela berbunga, saldo, dan riwayat bunga anggota"
            />

            <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
                <div className="rounded-2xl p-4 border bg-white border-slate-200/80 shadow-xs flex flex-col justify-between">
                    <div className="flex items-center justify-between gap-2">
                        <span className="text-xs font-semibold text-slate-500">Total Saldo Tabungan</span>
                        <span className="w-8 h-8 rounded-xl bg-brand-navy/5 text-brand-navy flex items-center justify-center shrink-0">
                            <Wallet size={16} />
                        </span>
                    </div>
                    <div className="mt-2">
                        <p className="text-xl sm:text-2xl font-bold text-slate-800 tabular-nums privacy-blur">
                            {formatRupiah(statistik.total_saldo_aktif)}
                        </p>
                        <p className="text-xs text-slate-400 mt-1">Rekening aktif</p>
                    </div>
                </div>

                <div className="rounded-2xl p-4 border bg-white border-slate-200/80 shadow-xs flex flex-col justify-between">
                    <div className="flex items-center justify-between gap-2">
                        <span className="text-xs font-semibold text-slate-500">Rekening Aktif</span>
                        <span className="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center shrink-0">
                            <Users size={16} />
                        </span>
                    </div>
                    <div className="mt-2">
                        <p className="text-xl sm:text-2xl font-bold text-slate-800 tabular-nums">
                            {statistik.jumlah_aktif}
                        </p>
                        <p className="text-xs text-slate-400 mt-1">
                            {statistik.jumlah_diajukan} pengajuan menunggu
                        </p>
                    </div>
                </div>

                <div className="rounded-2xl p-4 border bg-white border-slate-200/80 shadow-xs flex flex-col justify-between">
                    <div className="flex items-center justify-between gap-2">
                        <span className="text-xs font-semibold text-slate-500">Bunga Terbukukan</span>
                        <span className="w-8 h-8 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center shrink-0">
                            <BookOpenText size={16} />
                        </span>
                    </div>
                    <div className="mt-2">
                        <p className="text-xl sm:text-2xl font-bold text-slate-800 tabular-nums privacy-blur">
                            {formatRupiah(statistik.total_bunga_tahun_ini)}
                        </p>
                        <p className="text-xs text-slate-400 mt-1">Akumulasi tahun berjalan</p>
                    </div>
                </div>

                <div className="rounded-2xl p-4 border bg-white border-slate-200/80 shadow-xs flex flex-col justify-between">
                    <div className="flex items-center justify-between gap-2">
                        <span className="text-xs font-semibold text-slate-500">Suku Bunga Aktif</span>
                        <span className="w-8 h-8 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center shrink-0">
                            <Percent size={16} />
                        </span>
                    </div>
                    <div className="mt-2">
                        <p className="text-xl sm:text-2xl font-bold text-slate-800 tabular-nums">
                            {statistik.suku_bunga_aktif}% / thn
                        </p>
                        <p className="text-xs text-slate-400 mt-1">Saldo harian, kredit bulanan</p>
                    </div>
                </div>
            </div>

            <Card padding="sm" className="mb-4">
                <div className="flex flex-col sm:flex-row gap-2.5 items-stretch sm:items-center">
                    <div className="relative flex-1 group">
                        <Search size={15} aria-hidden="true" className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-brand-green transition-colors pointer-events-none select-none" />
                        <TextField
                            size="sm"
                            value={cari}
                            onChange={(e) => setCari(e.target.value)}
                            placeholder="Cari nama anggota, NIK, atau nomor rekening..."
                            className="pl-10 pr-8"
                        />
                    </div>
                    <div className="w-full sm:w-44">
                        <Select size="sm" value={filters.cabang ?? ''} onChange={(e) => terapkan({ cabang: e.target.value })}>
                            <option value="">Semua Cabang</option>
                            {daftarCabangAman.map((c) => (
                                <option key={c} value={c}>{c}</option>
                            ))}
                        </Select>
                    </div>
                    <div className="w-full sm:w-40">
                        <Select size="sm" value={filters.status ?? ''} onChange={(e) => terapkan({ status: e.target.value })}>
                            <option value="">Semua Status</option>
                            <option value="aktif">Aktif</option>
                            <option value="diajukan">Diajukan</option>
                            <option value="ditutup">Ditutup</option>
                            <option value="ditolak">Ditolak</option>
                        </Select>
                    </div>
                    {filterAktif && (
                        <button
                            type="button"
                            onClick={resetFilter}
                            className="inline-flex items-center justify-center gap-1 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:text-slate-900 rounded-lg border border-slate-200 hover:bg-slate-50"
                        >
                            <X size={13} /> Reset
                        </button>
                    )}
                </div>
            </Card>

            <Card padding="none" className="overflow-hidden">
                <div className="overflow-x-auto">
                    <table className="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr className="bg-[#F8FAFC] border-b border-slate-200/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                <th className="px-4 py-3">Anggota</th>
                                <th className="px-4 py-3">No. Rekening</th>
                                <th className="px-4 py-3">Cabang</th>
                                <th className="px-4 py-3">Tgl Buka</th>
                                <th className="px-4 py-3 text-right">Saldo</th>
                                <th className="px-4 py-3">Status</th>
                                <th className="px-4 py-3 text-right"></th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {rekening.data.length === 0 ? (
                                <tr>
                                    <td colSpan={7} className="px-4 py-12 text-center text-sm text-slate-400">
                                        Belum ada data rekening tabungan anggota.
                                    </td>
                                </tr>
                            ) : (
                                rekening.data.map((r) => (
                                    <tr
                                        key={r.id}
                                        onClick={() => router.visit(route('tabungan.show', r.id))}
                                        className="hover:bg-[#F2FBF5] transition-colors cursor-pointer group"
                                    >
                                        <td className="px-4 py-2.5">
                                            <div className="flex items-center gap-2.5">
                                                <FotoAnggota src={r.anggota?.foto_url} nama={r.anggota?.nama} size="sm" />
                                                <div className="min-w-0">
                                                    <p className="font-semibold text-slate-800 truncate">{r.anggota?.nama ?? '-'}</p>
                                                    <p className="text-xs text-slate-500 truncate">{r.anggota?.no_karyawan ?? '-'}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-4 py-2.5 font-mono text-xs font-semibold text-brand-navy whitespace-nowrap">
                                            {r.no_rekening || '-'}
                                        </td>
                                        <td className="px-4 py-2.5 text-slate-600 whitespace-nowrap">{r.anggota?.cabang ?? '-'}</td>
                                        <td className="px-4 py-2.5 text-slate-600 whitespace-nowrap">{r.tanggal_buka || r.tanggal_pengajuan || '-'}</td>
                                        <td className="px-4 py-2.5 text-right font-bold text-slate-800 tabular-nums privacy-blur whitespace-nowrap">
                                            {formatRupiah(r.saldo)}
                                        </td>
                                        <td className="px-4 py-2.5 whitespace-nowrap">
                                            <StatusBadge status={r.status} />
                                        </td>
                                        <td className="px-4 py-2.5 text-right">
                                            <Link
                                                href={route('tabungan.show', r.id)}
                                                onClick={(e) => e.stopPropagation()}
                                                className="inline-flex items-center justify-center w-7 h-7 rounded-lg text-slate-400 group-hover:text-brand-navy"
                                            >
                                                <ChevronRight size={16} />
                                            </Link>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                <div className="px-4 py-3 border-t border-slate-100">
                    <Pagination links={rekening.links} />
                </div>
            </Card>
        </AppLayout>
    );
}
