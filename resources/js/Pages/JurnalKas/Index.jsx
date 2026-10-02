import AppLayout from '@/Layouts/AppLayout';
import { Head, router, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { Wallet, Landmark, PiggyBank, HeartHandshake, Search, X, CalendarDays, ChevronLeft, ChevronRight, BookOpenText, ArrowDownCircle } from 'lucide-react';
import Card from '@/Components/ui/Card';
import PageHeader from '@/Components/ui/PageHeader';
import Select from '@/Components/ui/Select';
import TextField from '@/Components/ui/TextField';
import useDebouncedValue from '@/Utils/useDebouncedValue';
import { formatRupiah, formatRupiahSingkat } from '@/Utils/formatCurrency';

const fokusRing = 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-green/40';

const kantongMeta = {
    pinjaman: { label: 'Pinjaman', ikon: Wallet },
    dana_sosial: { label: 'Dana Sosial', ikon: HeartHandshake },
    simpanan: { label: 'Simpanan', ikon: PiggyBank },
    pengembalian_simpanan: { label: 'Pengembalian', ikon: PiggyBank },
    bank: { label: 'Bank', ikon: Landmark },
    kas_kecil: { label: 'Kas Kecil', ikon: Wallet },
};

const akunMeta = {
    bank: { label: 'Bank', warna: 'bg-brand-navy/10 text-brand-navy', ikon: Landmark },
    kas_kecil: { label: 'Kas Kecil', warna: 'bg-amber-50 text-amber-700', ikon: Wallet },
    audit: { label: 'Non-kas', warna: 'bg-slate-100 text-slate-500', ikon: BookOpenText },
};

const tabAkun = [
    { key: '', label: 'Semua' },
    { key: 'bank', label: 'Bank' },
    { key: 'kas_kecil', label: 'Kas Kecil' },
];

export default function Index({ filters = {}, bulanFilter, kantongOptions = {}, kategoriOptions = {}, ringkasanPeriode, riwayat = [], nonKas = [] }) {
    const { auth } = usePage().props;
    const bisaLihat = auth.user?.permissions?.includes('jurnal.lihat');
    const [cari, setCari] = useState(filters.cari ?? '');
    const cariDebounced = useDebouncedValue(cari);
    const pertama = useRef(true);

    const labelBulan = useMemo(() => {
        if (!bulanFilter) return '';
        const [tahun, bulanAngka] = bulanFilter.split('-').map(Number);
        if (!bulanAngka) return '';
        return new Date(tahun, bulanAngka - 1, 1).toLocaleDateString('id-ID', { month: 'long', year: 'numeric' });
    }, [bulanFilter]);

    const bulanIni = new Date();
    const kunciBulanIni = `${bulanIni.getFullYear()}-${String(bulanIni.getMonth() + 1).padStart(2, '0')}`;
    const akun = filters.akun ?? '';
    const akunLabel = akun === 'bank' ? ' Bank' : akun === 'kas_kecil' ? ' Kas Kecil' : '';

    const saldoAwal = akun === 'bank'
        ? (ringkasanPeriode.saldo_awal?.bank ?? 0)
        : akun === 'kas_kecil'
            ? (ringkasanPeriode.saldo_awal?.kas_kecil ?? 0)
            : (ringkasanPeriode.saldo_awal?.bank ?? 0) + (ringkasanPeriode.saldo_awal?.kas_kecil ?? 0);
    const saldoAkhir = akun === 'bank'
        ? (ringkasanPeriode.saldo_akhir?.bank ?? 0)
        : akun === 'kas_kecil'
            ? (ringkasanPeriode.saldo_akhir?.kas_kecil ?? 0)
            : (ringkasanPeriode.saldo_akhir?.bank ?? 0) + (ringkasanPeriode.saldo_akhir?.kas_kecil ?? 0);
    const saldoValid = ringkasanPeriode.saldo_valid !== false;

    function terapkan(overrides = {}) {
        router.get(
            route('jurnal-kas.index'),
            { kantong: filters.kantong ?? '', kategori: filters.kategori ?? '', bulan: bulanFilter, cari, akun, ...overrides },
            { preserveState: true, replace: true }
        );
    }

    function geserBulan(delta) {
        const [tahun, bulanAngka] = bulanFilter.split('-').map(Number);
        const d = new Date(tahun, bulanAngka - 1 + delta, 1);
        terapkan({ bulan: `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}` });
    }

    function resetFilter() {
        setCari('');
        router.get(route('jurnal-kas.index'), {}, { preserveState: true, replace: true });
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

    if (!bisaLihat) {
        return (
            <AppLayout>
                <Head title="Jurnal Kas" />
                <PageHeader title="Jurnal Kas" subtitle="Buku kas kronologis transaksi keuangan" />
                <Card><p className="text-sm text-slate-500">Anda tidak memiliki akses ke halaman ini.</p></Card>
            </AppLayout>
        );
    }

    const filterAktif = Boolean((filters.cari ?? '') || (filters.kantong ?? '') || (filters.kategori ?? ''));

    function badgeAkun(r) {
        const meta = akunMeta[r.akun] ?? akunMeta.bank;
        return (
            <span className={`inline-flex items-center gap-1 text-[10px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded-full whitespace-nowrap ${meta.warna}`}>
                <meta.ikon size={11} aria-hidden="true" />
                {meta.label}
            </span>
        );
    }

    function barisKeterangan(r) {
        return (
            <div className="min-w-0">
                <div className="flex items-center gap-1.5 flex-wrap">
                    <span className="text-sm font-semibold text-slate-700">{kategoriOptions[r.kategori] ?? r.kategori}</span>
                    <span className="text-[10px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded-full bg-slate-100 text-slate-500 whitespace-nowrap">
                        {kantongMeta[r.kantong]?.label ?? r.kantong}
                    </span>
                </div>
                {r.sub_judul && <p className="text-xs italic text-slate-500 mt-0.5 truncate">{r.sub_judul}</p>}
                {r.keterangan && <p className="text-xs text-slate-400 mt-0.5 truncate">{r.keterangan}</p>}
            </div>
        );
    }

    const kolomSaldo = saldoValid;

    return (
        <AppLayout>
            <Head title="Jurnal Kas" />

            <PageHeader title="Jurnal Kas" subtitle="Buku kas kronologis transaksi keuangan koperasi" />

            {/* Strip ringkasan periode + tab akun kas */}
            <div className="rounded-2xl border border-slate-200 bg-white shadow-md mb-4">
                <dl className="grid grid-cols-2 md:grid-cols-4 divide-x divide-y md:divide-y-0 divide-slate-100 text-sm">
                    <div className="px-4 py-3">
                        <dt className="text-xs text-slate-400">Saldo awal{akunLabel} {labelBulan}</dt>
                        <dd className="font-bold tabular-nums text-slate-800">{saldoValid ? formatRupiah(saldoAwal) : '—'}</dd>
                    </div>
                    <div className="px-4 py-3">
                        <dt className="text-xs text-slate-400">Masuk {labelBulan}</dt>
                        <dd className="font-bold tabular-nums text-brand-green-dark">+ {formatRupiah(ringkasanPeriode.total_masuk)}</dd>
                    </div>
                    <div className="px-4 py-3">
                        <dt className="text-xs text-slate-400">Keluar {labelBulan}</dt>
                        <dd className="font-bold tabular-nums text-red-600">- {formatRupiah(ringkasanPeriode.total_keluar)}</dd>
                    </div>
                    <div className="px-4 py-3">
                        <dt className="text-xs text-slate-400">Saldo akhir{akunLabel} {labelBulan}</dt>
                        <dd className="font-bold tabular-nums text-slate-800">{saldoValid ? formatRupiah(saldoAkhir) : '—'}</dd>
                    </div>
                </dl>
                <div className="flex items-center gap-1 px-3 py-2 border-t border-slate-100" role="tablist" aria-label="Akun kas">
                    {tabAkun.map((t) => (
                        <button
                            key={t.key}
                            role="tab"
                            aria-selected={akun === t.key}
                            onClick={() => terapkan({ akun: t.key })}
                            className={`px-3 py-1.5 rounded-full text-xs font-bold transition-colors ${fokusRing} ${
                                akun === t.key ? 'bg-brand-navy text-white' : 'text-slate-500 hover:bg-slate-100'
                            }`}
                        >
                            {t.label}
                        </button>
                    ))}
                </div>
            </div>

            {/* Filter bar */}
            <Card padding="sm" className="shadow-md border-slate-200/70 mb-4">
                <div className="flex flex-col md:flex-row md:items-center gap-2 pb-3 mb-1 border-b border-slate-100">
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-2 flex-1">
                        <Select size="sm" value={filters.kantong ?? ''} onChange={(e) => terapkan({ kantong: e.target.value })} aria-label="Filter kantong" className={fokusRing}>
                            <option value="">Semua kantong</option>
                            {Object.entries(kantongOptions).map(([key, label]) => (
                                <option key={key} value={key}>{kantongMeta[key]?.label ?? label}</option>
                            ))}
                        </Select>
                        <Select size="sm" value={filters.kategori ?? ''} onChange={(e) => terapkan({ kategori: e.target.value })} aria-label="Filter kategori" className={fokusRing}>
                            <option value="">Semua kategori</option>
                            {Object.entries(kategoriOptions).map(([key, label]) => (
                                <option key={key} value={key}>{label}</option>
                            ))}
                        </Select>
                        <div className="flex items-center rounded-xl border border-slate-300 bg-white overflow-hidden col-span-2 md:col-span-1">
                            <button onClick={() => geserBulan(-1)} aria-label="Bulan sebelumnya" className={`px-2 py-2.5 text-slate-500 hover:text-slate-700 ${fokusRing}`}>
                                <ChevronLeft size={16} />
                            </button>
                            <input
                                type="month"
                                value={bulanFilter}
                                onChange={(e) => e.target.value && terapkan({ bulan: e.target.value })}
                                aria-label="Pilih bulan"
                                className="w-full px-1 py-2.5 text-base text-slate-700 border-x border-slate-200 bg-transparent focus:outline-none"
                            />
                            <button onClick={() => geserBulan(1)} aria-label="Bulan berikutnya" className={`px-2 py-2.5 text-slate-500 hover:text-slate-700 ${fokusRing}`}>
                                <ChevronRight size={16} />
                            </button>
                        </div>
                        <div className="relative col-span-2 md:col-span-1">
                            <Search size={16} className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
                            <TextField
                                size="sm"
                                value={cari}
                                onChange={(e) => setCari(e.target.value)}
                                placeholder="Cari keterangan..."
                                aria-label="Cari keterangan"
                                className="pl-10 pr-9"
                            />
                            {cari && (
                                <button onClick={() => setCari('')} aria-label="Hapus pencarian" className="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                    <X size={16} />
                                </button>
                            )}
                        </div>
                    </div>
                    <div className="flex items-center gap-2 shrink-0">
                        {bulanFilter !== kunciBulanIni && (
                            <button onClick={() => terapkan({ bulan: kunciBulanIni })} className="text-xs font-bold text-brand-green-dark bg-brand-green-light rounded-full px-2.5 py-1.5 hover:bg-brand-green/20 transition-colors whitespace-nowrap">
                                Bulan ini
                            </button>
                        )}
                        {filterAktif && (
                            <button onClick={resetFilter} className="text-xs font-bold text-slate-500 hover:text-slate-700 rounded-full px-2.5 py-1.5 whitespace-nowrap">
                                Reset
                            </button>
                        )}
                        <span className="inline-flex items-center px-2.5 py-1.5 rounded-full text-xs font-bold whitespace-nowrap bg-brand-green-light text-brand-green-dark tabular-nums">
                            +{formatRupiahSingkat(ringkasanPeriode.total_masuk)}
                        </span>
                        <span className="inline-flex items-center px-2.5 py-1.5 rounded-full text-xs font-bold whitespace-nowrap bg-red-50 text-red-700 tabular-nums">
                            -{formatRupiahSingkat(ringkasanPeriode.total_keluar)}
                        </span>
                    </div>
                </div>
                <div className="flex items-center gap-1.5 min-w-0 px-1 pt-1 flex-wrap">
                    <CalendarDays size={15} className="text-slate-400 shrink-0" />
                    <p className="text-sm text-slate-500 truncate">
                        Buku kas <span className="font-bold text-slate-800">{labelBulan}</span>
                    </p>
                    {!saldoValid && (
                        <p className="text-[11px] text-amber-600 bg-amber-50 border border-amber-200 rounded-full px-2 py-0.5">
                            Kolom saldo disembunyikan — filter aktif membatasi baris yang ditampilkan
                        </p>
                    )}
                </div>
            </Card>

            {/* Tabel buku kas */}
            <Card padding="sm" className="shadow-md border-slate-200/70 mb-4">
                {riwayat.length === 0 ? (
                    <div className="text-center py-12 px-4">
                        <BookOpenText size={28} aria-hidden="true" className="mx-auto text-slate-300 mb-3" />
                        <p className="text-sm font-semibold text-slate-600">
                            Belum ada jurnal kas pada filter ini untuk {labelBulan}.
                        </p>
                    </div>
                ) : (
                    <>
                        {/* Desktop: tabel */}
                        <table className="hidden md:table w-full text-sm border-collapse">
                            <thead>
                                <tr className="text-left text-[11px] uppercase tracking-wide text-slate-400 border-b border-slate-200">
                                    <th scope="col" className="py-2 pr-2 font-semibold w-24">Tgl</th>
                                    <th scope="col" className="py-2 pr-2 font-semibold w-32">No. Bukti</th>
                                    <th scope="col" className="py-2 pr-2 font-semibold">Keterangan</th>
                                    <th scope="col" className="py-2 pr-2 font-semibold w-28">Akun</th>
                                    <th scope="col" className="py-2 pr-2 font-semibold text-right w-36">Masuk</th>
                                    <th scope="col" className="py-2 pr-2 font-semibold text-right w-36">Keluar</th>
                                    <th scope="col" className={`py-2 font-semibold text-right ${kolomSaldo ? 'w-40' : 'w-16'}`}>Saldo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr className="border-b border-slate-100 bg-slate-50/70">
                                    <td colSpan={4} className="py-2 pr-2 text-xs font-bold text-slate-500">
                                        Saldo awal{akunLabel} {labelBulan}
                                    </td>
                                    <td colSpan={2} className="py-2 pr-2 text-right text-xs text-slate-400">
                                        {saldoValid ? '' : 'saldo terbatas filter'}
                                    </td>
                                    <td className="py-2 text-right font-bold tabular-nums text-slate-600">
                                        {saldoValid ? formatRupiah(saldoAwal) : '—'}
                                    </td>
                                </tr>
                                {riwayat.map((r) => (
                                    <tr key={r.id} className="border-b border-slate-50 hover:bg-gradient-to-r hover:from-slate-50 hover:to-transparent transition-all align-top">
                                        <td className="py-2.5 pr-2 text-xs text-slate-500 whitespace-nowrap tabular-nums">{r.tanggal}</td>
                                        <td className="py-2.5 pr-2 text-xs text-slate-500 whitespace-nowrap tabular-nums font-mono">{r.no_bukti ?? '—'}</td>
                                        <td className="py-2.5 pr-2">{barisKeterangan(r)}</td>
                                        <td className="py-2.5 pr-2">{badgeAkun(r)}</td>
                                        <td className="py-2.5 pr-2 text-right whitespace-nowrap tabular-nums">
                                            {r.tipe === 'masuk'
                                                ? <span className="font-bold text-brand-green-dark">+ {formatRupiah(r.jumlah)}</span>
                                                : <span className="text-slate-300">—</span>}
                                        </td>
                                        <td className="py-2.5 pr-2 text-right whitespace-nowrap tabular-nums">
                                            {r.tipe === 'keluar'
                                                ? <span className="font-bold text-red-600">- {formatRupiah(r.jumlah)}</span>
                                                : <span className="text-slate-300">—</span>}
                                        </td>
                                        <td className="py-2.5 text-right whitespace-nowrap tabular-nums text-slate-600">
                                            {kolomSaldo ? formatRupiah(r.saldo_setelah) : '—'}
                                        </td>
                                    </tr>
                                ))}
                                <tr className="border-t-2 border-slate-200 bg-slate-50/70">
                                    <td colSpan={4} className="py-2.5 pr-2 text-xs font-bold text-slate-600">
                                        Total {labelBulan}
                                    </td>
                                    <td className="py-2.5 pr-2 text-right font-bold tabular-nums text-brand-green-dark">+ {formatRupiah(ringkasanPeriode.total_masuk)}</td>
                                    <td className="py-2.5 pr-2 text-right font-bold tabular-nums text-red-600">- {formatRupiah(ringkasanPeriode.total_keluar)}</td>
                                    <td className="py-2.5 text-right font-bold tabular-nums text-slate-700">
                                        {saldoValid ? formatRupiah(saldoAkhir) : '—'}
                                    </td>
                                </tr>
                                {saldoValid && (
                                    <tr className="bg-slate-50/70 -mt-px">
                                        <td colSpan={6} className="py-1 pr-2 text-xs text-slate-400 text-right">
                                            Saldo akhir{akunLabel} {labelBulan}
                                        </td>
                                        <td className="py-1 text-right text-xs font-bold tabular-nums text-slate-700">
                                            {formatRupiah(saldoAkhir)}
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>

                        {/* Mobile: kartu */}
                        <ul className="md:hidden divide-y divide-slate-50">
                            <li className="flex items-center justify-between gap-3 py-2.5 bg-slate-50/70 rounded-lg px-2 -mx-2">
                                <span className="text-xs font-bold text-slate-500">Saldo awal{akunLabel} {labelBulan}</span>
                                <span className="text-sm font-bold tabular-nums text-slate-600">{saldoValid ? formatRupiah(saldoAwal) : '—'}</span>
                            </li>
                            {riwayat.map((r) => (
                                <li key={r.id} className="py-3">
                                    <div className="flex items-start justify-between gap-3">
                                        <div className="flex-1 min-w-0">
                                            <div className="flex items-center gap-1.5 flex-wrap">
                                                <span className="text-[11px] text-slate-400 tabular-nums">{r.tanggal}</span>
                                                <span className="text-[10px] text-slate-400 font-mono tabular-nums">{r.no_bukti ?? '—'}</span>
                                            </div>
                                            <div className="mt-0.5">{barisKeterangan(r)}</div>
                                            <div className="mt-1">{badgeAkun(r)}</div>
                                        </div>
                                        <div className="text-right shrink-0">
                                            <p className={`text-sm font-bold whitespace-nowrap tabular-nums ${r.tipe === 'masuk' ? 'text-brand-green-dark' : 'text-red-600'}`}>
                                                {r.tipe === 'masuk' ? '+' : '-'} {formatRupiah(r.jumlah)}
                                            </p>
                                            {kolomSaldo && <p className="text-[11px] text-slate-400 whitespace-nowrap tabular-nums">Saldo: {formatRupiah(r.saldo_setelah)}</p>}
                                        </div>
                                    </div>
                                </li>
                            ))}
                            <li className="flex items-center justify-between gap-3 py-2.5 bg-slate-50/70 rounded-lg px-2 -mx-2">
                                <span className="text-xs font-bold text-slate-600">Total {labelBulan}</span>
                                <span className="text-xs tabular-nums font-bold text-slate-700">
                                    <span className="text-brand-green-dark">+{formatRupiahSingkat(ringkasanPeriode.total_masuk)}</span>
                                    {' / '}
                                    <span className="text-red-600">-{formatRupiahSingkat(ringkasanPeriode.total_keluar)}</span>
                                </span>
                            </li>
                            <li className="flex items-center justify-between gap-3 py-2.5 bg-slate-50/70 rounded-lg px-2 -mx-2">
                                <span className="text-xs font-bold text-slate-500">Saldo akhir{akunLabel} {labelBulan}</span>
                                <span className="text-sm font-bold tabular-nums text-slate-700">{saldoValid ? formatRupiah(saldoAkhir) : '—'}</span>
                            </li>
                        </ul>
                    </>
                )}
            </Card>

            {/* Transaksi non-kas */}
            {nonKas.length > 0 && (
                <Card padding="sm" className="shadow-md border-slate-200/70 mb-4">
                    <details>
                        <summary className="cursor-pointer flex items-center gap-2 text-sm font-bold text-slate-600 select-none py-1">
                            <ArrowDownCircle size={16} className="text-slate-400" aria-hidden="true" />
                            Transaksi non-kas ({nonKas.length})
                            <span className="text-xs font-normal text-slate-400">— catatan pembukuan tanpa gerak kas</span>
                        </summary>
                        <table className="w-full text-sm border-collapse mt-2">
                            <thead>
                                <tr className="text-left text-[11px] uppercase tracking-wide text-slate-400 border-b border-slate-200">
                                    <th scope="col" className="py-2 pr-2 font-semibold w-24">Tgl</th>
                                    <th scope="col" className="py-2 pr-2 font-semibold w-32">No. Bukti</th>
                                    <th scope="col" className="py-2 pr-2 font-semibold">Keterangan</th>
                                    <th scope="col" className="py-2 pr-2 font-semibold text-right w-36">Masuk</th>
                                    <th scope="col" className="py-2 font-semibold text-right w-36">Keluar</th>
                                </tr>
                            </thead>
                            <tbody>
                                {nonKas.map((r) => (
                                    <tr key={r.id} className="border-b border-slate-50 align-top">
                                        <td className="py-2.5 pr-2 text-xs text-slate-500 whitespace-nowrap tabular-nums">{r.tanggal}</td>
                                        <td className="py-2.5 pr-2 text-xs text-slate-500 whitespace-nowrap tabular-nums font-mono">{r.no_bukti ?? '—'}</td>
                                        <td className="py-2.5 pr-2">{barisKeterangan(r)}</td>
                                        <td className="py-2.5 pr-2 text-right whitespace-nowrap tabular-nums">
                                            {r.tipe === 'masuk'
                                                ? <span className="font-bold text-slate-500">+ {formatRupiah(r.jumlah)}</span>
                                                : <span className="text-slate-300">—</span>}
                                        </td>
                                        <td className="py-2.5 text-right whitespace-nowrap tabular-nums">
                                            {r.tipe === 'keluar'
                                                ? <span className="font-bold text-slate-500">- {formatRupiah(r.jumlah)}</span>
                                                : <span className="text-slate-300">—</span>}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </details>
                </Card>
            )}
        </AppLayout>
    );
}
