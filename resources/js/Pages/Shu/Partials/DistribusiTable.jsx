import { router } from '@inertiajs/react';
import { useState } from 'react';
import { ArrowDown, ArrowUp, Calculator, Search } from 'lucide-react';
import { useEffect, useRef } from 'react';
import Card from '@/Components/ui/Card';
import Select from '@/Components/ui/Select';
import Pagination from '@/Components/ui/Pagination';
import useDebouncedValue from '@/Utils/useDebouncedValue';
import { formatRupiah } from '@/Utils/formatCurrency';
import { barisLive } from '@/Utils/shuLive';

const KOLOM_SORT = [
    { kunci: 'no', label: 'No', grup: null },
    { kunci: 'nama', label: 'Nama Anggota', grup: null },
    { kunci: 'simp_wajib', label: 'Simp Wajib', grup: 'Partisipasi Anggota' },
    { kunci: 'simp_pokok', label: 'Simp Pokok', grup: 'Partisipasi Anggota' },
    { kunci: 'modal', label: 'Modal (SP + SW)', grup: 'Partisipasi Anggota' },
    { kunci: 'transaksi', label: 'Transaksi', grup: 'Partisipasi Anggota' },
    { kunci: 'shu_modal', label: 'Atas Modal', grup: 'SHU Anggota', live: true },
    { kunci: 'shu_transaksi', label: 'Atas Transaksi', grup: 'SHU Anggota', live: true },
    { kunci: 'shu_bruto', label: 'Jumlah SHU', grup: null, live: true },
    { kunci: 'shu_bersih', label: 'SHU Diserahkan', grup: null, live: true },
];

/**
 * Tabel distribusi mirror sheet Excel (Simp Wajib/Pokok/Modal/Transaksi/G/H/I/J).
 * Baris yang tampil ikut preview live saat persen diubah (belum tersimpan).
 */
export default function DistribusiTable({ periode, detail, sums = {}, filters = {}, live, isPreview, bisaBayar = false, onBayar, onKeKomponen }) {
    const [cari, setCari] = useState(filters.cari ?? '');
    const cariDebounced = useDebouncedValue(cari);
    const pertama = useRef(true);

    useEffect(() => {
        if (pertama.current) {
            pertama.current = false;
            return;
        }
        if ((cariDebounced ?? '') !== (filters.cari ?? '')) {
            muat({ cari: cariDebounced ?? '' });
        }
    }, [cariDebounced]);

    function muat(overrides = {}) {
        router.get(
            route('shu.show', periode.id),
            { cari, bayar: filters.bayar, sort: filters.sort, arah: filters.arah, tab: 'distribusi', ...overrides },
            { preserveState: true, replace: true }
        );
    }

    function klikSort(kunci) {
        const arahBaru = filters.sort === kunci && (filters.arah ?? 'desc') === 'desc' ? 'asc' : 'desc';
        muat({ sort: kunci, arah: arahBaru });
    }

    function IkonSort({ kunci }) {
        const aktif = (filters.sort ?? 'no') === kunci;
        if (!aktif) return null;
        return (filters.arah ?? (kunci === 'no' || kunci === 'nama' ? 'asc' : 'desc')) === 'desc'
            ? <ArrowDown size={12} className="inline ml-1" aria-hidden="true" />
            : <ArrowUp size={12} className="inline ml-1" aria-hidden="true" />;
    }

    function thClass(kunci) {
        if (kunci === 'nama') return 'px-4 py-2.5';
        if (kunci === 'no') return 'px-4 py-2.5 w-12';
        return 'px-4 py-2.5 text-right';
    }

    return (
        <Card padding="none">
            <div className="flex flex-wrap items-center gap-2 px-4 py-3 border-b border-slate-100">
                <div className="relative flex-1 min-w-[180px]">
                    <Search size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" aria-hidden="true" />
                    <input
                        value={cari}
                        onChange={(e) => setCari(e.target.value)}
                        placeholder="Cari nama / no karyawan..."
                        aria-label="Cari anggota"
                        className="w-full rounded-xl border border-slate-200 pl-9 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-green/30"
                    />
                </div>
                <Select
                    value={filters.bayar ?? ''}
                    onChange={(e) => muat({ bayar: e.target.value })}
                    aria-label="Filter status bayar"
                >
                    <option value="">Semua status</option>
                    <option value="belum">Belum dibayar</option>
                    <option value="sudah">Sudah dibayar</option>
                </Select>
                {isPreview && (
                    <span className="text-xs font-bold px-2 py-1 rounded-full bg-amber-100 text-amber-700">
                        Kolom SHU = preview live
                    </span>
                )}
            </div>

            <div className="overflow-x-auto">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="text-center text-xs uppercase tracking-wide text-slate-400 border-b border-slate-100">
                            <th colSpan={2} className="py-1.5" />
                            <th colSpan={4} className="py-1.5 border-x border-slate-100">Partisipasi Anggota</th>
                            <th colSpan={2} className="py-1.5 border-x border-slate-100">SHU Anggota</th>
                            <th colSpan={bisaBayar ? 4 : 3} className="py-1.5" />
                        </tr>
                        <tr className="text-left text-xs uppercase tracking-wide text-slate-400 border-b border-slate-100">
                            {KOLOM_SORT.map((k) => (
                                <th key={k.kunci} className={thClass(k.kunci)}>
                                    <button
                                        type="button"
                                        onClick={() => klikSort(k.kunci)}
                                        className="uppercase tracking-wide hover:text-slate-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-green/40 rounded"
                                        aria-label={`Urut ${k.label}`}
                                    >
                                        {k.label}
                                        <IkonSort kunci={k.kunci} />
                                    </button>
                                </th>
                            ))}
                            <th className="px-4 py-2.5">Status</th>
                            {bisaBayar && <th className="px-4 py-2.5"><span className="sr-only">Aksi</span></th>}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-50">
                        {detail.data.map((d) => {
                            const liveRow = live
                                ? barisLive(d.modal, d.transaksi, live.totalModal, live.totalTransaksi, live.poolSimpanan, live.poolTransaksi)
                                : null;
                            const atasModal = liveRow ? liveRow.shuModal : d.shu_modal;
                            const atasTransaksi = liveRow ? liveRow.shuTransaksi : d.shu_transaksi;
                            const bruto = liveRow ? liveRow.bruto : d.shu_bruto;
                            const bersih = liveRow ? liveRow.bersih : d.shu_bersih;
                            const beda = liveRow && Math.abs(bersih - d.shu_bersih) >= 1;
                            const angkaLive = `px-4 py-2.5 text-right tabular-nums${beda ? ' text-amber-700' : ''}`;
                            return (
                                <tr key={d.id} className="hover:bg-slate-50/60">
                                    <td className="px-4 py-2.5 tabular-nums text-slate-500">{d.no}</td>
                                    <td className="px-4 py-2.5">
                                        <p className="font-semibold text-slate-800">{d.nama}</p>
                                        <p className="text-xs text-slate-400">{d.no_karyawan} • {d.cabang}</p>
                                    </td>
                                    <td className="px-4 py-2.5 text-right tabular-nums text-slate-500">{formatRupiah(d.simp_wajib)}</td>
                                    <td className="px-4 py-2.5 text-right tabular-nums text-slate-500">{formatRupiah(d.simp_pokok)}</td>
                                    <td className="px-4 py-2.5 text-right tabular-nums">{formatRupiah(d.modal)}</td>
                                    <td className="px-4 py-2.5 text-right tabular-nums">{formatRupiah(d.transaksi)}</td>
                                    <td className={angkaLive}>{formatRupiah(atasModal)}</td>
                                    <td className={angkaLive}>{formatRupiah(atasTransaksi)}</td>
                                    <td className={angkaLive}>{formatRupiah(bruto)}</td>
                                    <td className={`${angkaLive} font-bold`}>{formatRupiah(bersih)}</td>
                                    <td className="px-4 py-2.5">
                                        <span className={`text-xs font-bold px-2 py-0.5 rounded-full ${d.status_bayar === 'sudah' ? 'bg-brand-green-light text-brand-green-dark' : 'bg-slate-100 text-slate-500'}`}>
                                            {d.status_bayar === 'sudah' ? `Dibayar ${d.dibayar_pada ?? ''}` : 'Belum'}
                                        </span>
                                    </td>
                                    {bisaBayar && (
                                        <td className="px-4 py-2.5 text-right">
                                            {d.status_bayar !== 'sudah' && (
                                                <button
                                                    type="button"
                                                    onClick={() => onBayar?.(d.id)}
                                                    className="text-sm font-semibold text-brand-green-dark hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-green/40 rounded px-1"
                                                >
                                                    Bayar
                                                </button>
                                            )}
                                        </td>
                                    )}
                                </tr>
                            );
                        })}
                    </tbody>
                    <tfoot>
                        <tr className="border-t-2 border-slate-200 bg-slate-50/70 font-bold">
                            <td colSpan={2} className="px-4 py-2.5">Total{detail.total > detail.data.length ? ` (${detail.total} anggota)` : ''}</td>
                            <td className="px-4 py-2.5 text-right tabular-nums">{formatRupiah(sums.wajib ?? 0)}</td>
                            <td className="px-4 py-2.5 text-right tabular-nums">{formatRupiah(sums.pokok ?? 0)}</td>
                            <td className="px-4 py-2.5 text-right tabular-nums">{formatRupiah(sums.modal ?? 0)}</td>
                            <td className="px-4 py-2.5 text-right tabular-nums">{formatRupiah(sums.transaksi ?? 0)}</td>
                            <td colSpan={2} />
                            <td className="px-4 py-2.5 text-right tabular-nums">{formatRupiah(sums.bruto ?? 0)}</td>
                            <td className="px-4 py-2.5 text-right tabular-nums">{formatRupiah(sums.bersih ?? 0)}</td>
                            <td colSpan={bisaBayar ? 2 : 1} />
                        </tr>
                    </tfoot>
                </table>
                {detail.data.length === 0 && (
                    <div className="px-4 py-10 text-center">
                        <p className="text-sm font-semibold text-slate-700">Belum ada rincian anggota</p>
                        <p className="text-sm text-slate-400 mt-1 mb-3">
                            Rincian dihitung dari simpanan & pinjaman cair. Klik tombol di bawah untuk menghitung sekarang.
                        </p>
                        <button
                            type="button"
                            onClick={onKeKomponen}
                            className="inline-flex items-center gap-1.5 rounded-full bg-brand-navy px-4 py-2 text-sm font-semibold text-white hover:opacity-90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-green/40"
                        >
                            <Calculator size={16} aria-hidden="true" />
                            Hitung di Tab Komponen
                        </button>
                    </div>
                )}
            </div>

            <div className="px-4 py-3">
                <Pagination links={detail.links} />
            </div>
        </Card>
    );
}
