import AppLayout from '@/Layouts/AppLayout';
import { Head, router, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { Wallet, Landmark, PiggyBank, HeartHandshake, ArrowDownCircle, ArrowUpCircle, Search, X, CalendarDays, ChevronLeft, ChevronRight, BookOpenText } from 'lucide-react';
import Card from '@/Components/ui/Card';
import PageHeader from '@/Components/ui/PageHeader';
import StatWidget from '@/Components/ui/StatWidget';
import Pagination from '@/Components/ui/Pagination';
import Select from '@/Components/ui/Select';
import TextField from '@/Components/ui/TextField';
import useDebouncedValue from '@/Utils/useDebouncedValue';
import { formatRupiah, formatRupiahSingkat } from '@/Utils/formatCurrency';

const fokusRing = 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-green/40';

const kantongMeta = {
    pinjaman: { label: 'Dana Pinjaman', ikon: Wallet, tone: 'green' },
    dana_sosial: { label: 'Dana Sosial', ikon: HeartHandshake, tone: 'amber' },
    simpanan: { label: 'Simpanan (Kas)', ikon: PiggyBank, tone: 'blue' },
    pengembalian_simpanan: { label: 'Pengembalian Simpanan', ikon: PiggyBank, tone: 'navy' },
    bank: { label: 'Bank', ikon: Landmark, tone: 'navy' },
    kas_kecil: { label: 'Kas Kecil', ikon: Wallet, tone: 'amber' },
};

export default function Index({ saldo = {}, filters = {}, bulanFilter, kantongOptions = {}, kategoriOptions = {}, ringkasanPeriode, riwayat }) {
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

    function terapkan(overrides = {}) {
        router.get(
            route('jurnal-kas.index'),
            { kantong: filters.kantong ?? '', kategori: filters.kategori ?? '', bulan: bulanFilter, cari, ...overrides },
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
                <PageHeader title="Jurnal Kas" subtitle="Buku besar seluruh mutasi kas koperasi" />
                <Card><p className="text-sm text-slate-500">Anda tidak memiliki akses ke halaman ini.</p></Card>
            </AppLayout>
        );
    }

    const filterAktif = Boolean((filters.cari ?? '') || (filters.kantong ?? '') || (filters.kategori ?? '') || (filters.bulan ?? ''));

    return (
        <AppLayout>
            <Head title="Jurnal Kas" />

            <PageHeader title="Jurnal Kas" subtitle="Buku besar seluruh mutasi kas koperasi" />

            <div className="rounded-2xl bg-gradient-to-r from-brand-navy to-brand-navy-light text-white px-5 py-4 mb-3 shadow-md shadow-brand-navy/20">
                <div className="flex items-center gap-3">
                    <span className="w-11 h-11 rounded-2xl bg-white/15 inline-flex items-center justify-center shrink-0">
                        <BookOpenText size={22} />
                    </span>
                    <div className="min-w-0 flex-1">
                        <p className="text-xs text-white/70">Uang fisik (Bank + Kas Kecil){filterAktif ? ' — hasil filter' : ''}</p>
                        <p className="text-2xl font-bold tabular-nums leading-tight">{formatRupiah((saldo.bank ?? 0) + (saldo.kas_kecil ?? 0))}</p>
                    </div>
                </div>
                <dl className="grid grid-cols-2 gap-2 mt-3 pt-3 border-t border-white/15 text-sm">
                    <div className="rounded-xl bg-white/10 px-3 py-2">
                        <dt className="text-xs text-white/70">Masuk {labelBulan}</dt>
                        <dd className="font-bold tabular-nums">+ {formatRupiah(ringkasanPeriode.total_masuk)}</dd>
                    </div>
                    <div className="rounded-xl bg-white/10 px-3 py-2">
                        <dt className="text-xs text-white/70">Keluar {labelBulan}</dt>
                        <dd className="font-bold tabular-nums">- {formatRupiah(ringkasanPeriode.total_keluar)}</dd>
                    </div>
                </dl>
            </div>

            <div className="grid grid-cols-2 lg:grid-cols-3 gap-3 mb-4">
                <StatWidget compact label="Simpanan Outstanding" value={formatRupiah(saldo.outstanding ?? 0)} icon={PiggyBank} tone="navy" />
                <StatWidget compact label="Bank" value={formatRupiah(saldo.bank ?? 0)} icon={Landmark} tone="navy" />
                <StatWidget compact label="Kas Kecil" value={formatRupiah(saldo.kas_kecil ?? 0)} icon={Wallet} tone="amber" />
            </div>

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
                <div className="flex items-center gap-1.5 min-w-0 px-1 pt-1">
                    <CalendarDays size={15} className="text-slate-400 shrink-0" />
                    <p className="text-sm text-slate-500 truncate">
                        Buku besar <span className="font-bold text-slate-800">{labelBulan}</span>
                    </p>
                </div>
                {riwayat.data.length === 0 ? (
                    <div className="text-center py-12 px-4">
                        <Wallet size={28} aria-hidden="true" className="mx-auto text-slate-300 mb-3" />
                        <p className="text-sm font-semibold text-slate-600">
                            Belum ada jurnal pada filter ini untuk {labelBulan}.
                        </p>
                    </div>
                ) : (
                    <ul className="divide-y divide-slate-50">
                        {riwayat.data.map((r) => (
                            <li key={r.id} className="flex items-center gap-3 px-1 py-3 hover:bg-gradient-to-r hover:from-slate-50 hover:to-transparent rounded-xl transition-all">
                                {r.tipe === 'masuk' ? (
                                    <ArrowDownCircle size={22} aria-hidden="true" className="text-brand-green shrink-0" />
                                ) : (
                                    <ArrowUpCircle size={22} aria-hidden="true" className="text-red-500 shrink-0" />
                                )}
                                <div className="flex-1 min-w-0">
                                    <div className="flex items-center gap-2 flex-wrap">
                                        <p className="text-sm font-semibold text-slate-700">
                                            {kategoriOptions[r.kategori] ?? r.kategori}
                                        </p>
                                        <span className="text-[10px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded-full bg-slate-100 text-slate-500 whitespace-nowrap">
                                            {kantongMeta[r.kantong]?.label ?? r.kantong}
                                        </span>
                                    </div>
                                    {r.sub_judul && (
                                        <p className="text-xs italic text-slate-500 mt-0.5 truncate">{r.sub_judul}</p>
                                    )}
                                    <p className="text-xs text-slate-400 mt-0.5 truncate">
                                        {r.tanggal}{r.keterangan ? ` • ${r.keterangan}` : ''}
                                    </p>
                                </div>
                                <div className="text-right shrink-0 pl-2">
                                    <p className={`text-sm font-bold whitespace-nowrap tabular-nums ${r.tipe === 'masuk' ? 'text-brand-green-dark' : 'text-red-600'}`}>
                                        {r.tipe === 'masuk' ? '+' : '-'} {formatRupiah(r.jumlah)}
                                    </p>
                                    <p className="text-[11px] text-slate-400 whitespace-nowrap tabular-nums">Saldo: {formatRupiah(r.saldo_setelah)}</p>
                                </div>
                            </li>
                        ))}
                    </ul>
                )}
            </Card>

            <Pagination links={riwayat.links} />
        </AppLayout>
    );
}
