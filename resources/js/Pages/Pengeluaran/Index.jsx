import AppLayout from '@/Layouts/AppLayout';
import { Head, router, usePage, useForm } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { Plus, Search, X, RefreshCw, Wallet, HeartHandshake, ReceiptText, CalendarDays } from 'lucide-react';
import useDebouncedValue from '@/Utils/useDebouncedValue';
import Card from '@/Components/ui/Card';
import Button from '@/Components/ui/Button';
import PageHeader from '@/Components/ui/PageHeader';
import TextField from '@/Components/ui/TextField';
import FormField from '@/Components/ui/FormField';
import Pagination from '@/Components/ui/Pagination';
import { formatRupiah, formatRupiahSingkat } from '@/Utils/formatCurrency';
import { withIdempotencyKey } from '@/Utils/idempotency';

const chipNominal = [50_000, 100_000, 250_000, 500_000, 1_000_000];
const jenisMeta = {
    koperasi: { label: 'Koperasi', ikon: Wallet },
    dana_sosial: { label: 'Dana Sosial', ikon: HeartHandshake },
};

export default function Index({ pengeluaran, jenisAktif, filters = {}, totalKoperasi, totalDanaSosial, totalTampil }) {
    const { auth } = usePage().props;
    const bisaCatat = auth.user?.permissions?.includes('kas.topup');
    const [showForm, setShowForm] = useState(false);
    const [cari, setCari] = useState(filters.cari ?? '');
    const cariDebounced = useDebouncedValue(cari);
    const pertama = useRef(true);
    const hariIni = new Date().toISOString().slice(0, 10);

    const { data, setData, post, processing, errors, reset } = useForm({
        jenis: jenisAktif,
        jumlah: '',
        keterangan: '',
        tanggal: hariIni,
    });

    const filterAktif = Boolean((filters.cari ?? '') || (filters.bulan ?? ''));

    function terapkan(overrides = {}) {
        router.get(
            route('pengeluaran.index'),
            { jenis: jenisAktif, cari, bulan: filters.bulan, ...overrides },
            { preserveState: true, replace: true }
        );
    }

    function pindahTab(jenis) {
        router.get(route('pengeluaran.index'), { jenis }, { preserveState: true, replace: true });
    }

    function resetFilter() {
        setCari('');
        router.get(route('pengeluaran.index'), { jenis: jenisAktif }, { preserveState: true, replace: true });
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

    function bukaForm() {
        setData('jenis', jenisAktif);
        setShowForm(true);
    }

    function submit(e) {
        e.preventDefault();
        post(
            route('pengeluaran.store'),
            withIdempotencyKey({
                preserveScroll: true,
                onSuccess: () => { reset('jumlah', 'keterangan'); setShowForm(false); },
            })
        );
    }

    const meta = jenisMeta[jenisAktif] ?? jenisMeta.koperasi;
    const IkonJenis = meta.ikon;

    return (
        <AppLayout>
            <Head title="Pengeluaran" />

            <PageHeader title="Pengeluaran" subtitle="Catat keluar kas koperasi & dana sosial">
                {bisaCatat && !showForm && (
                    <Button size="sm" onClick={bukaForm} className="rounded-full shadow-md shadow-brand-green/25 hover:-translate-y-px active:translate-y-0">
                        <Plus size={16} />
                        Catat Pengeluaran
                    </Button>
                )}
            </PageHeader>

            <div className="rounded-2xl bg-gradient-to-r from-brand-navy to-brand-navy-light text-white px-5 py-4 mb-3 shadow-md shadow-brand-navy/20">
                <div className="flex items-center gap-3">
                    <span className="w-11 h-11 rounded-2xl bg-white/15 inline-flex items-center justify-center shrink-0">
                        <IkonJenis size={22} />
                    </span>
                    <div className="min-w-0 flex-1">
                        <p className="text-xs text-white/70">
                            Total {meta.label}{filterAktif ? ' (hasil filter)' : ''}
                        </p>
                        <p className="text-2xl font-bold tabular-nums leading-tight">- {formatRupiah(totalTampil)}</p>
                    </div>
                </div>
                <dl className="grid grid-cols-2 gap-2 mt-3 pt-3 border-t border-white/15 text-sm">
                    <div className="rounded-xl bg-white/10 px-3 py-2">
                        <dt className="text-xs text-white/70">Koperasi (global)</dt>
                        <dd className="font-bold tabular-nums">- {formatRupiah(totalKoperasi)}</dd>
                    </div>
                    <div className="rounded-xl bg-white/10 px-3 py-2">
                        <dt className="text-xs text-white/70">Dana sosial (global)</dt>
                        <dd className="font-bold tabular-nums">- {formatRupiah(totalDanaSosial)}</dd>
                    </div>
                </dl>
            </div>

            <div className="flex items-center gap-1.5 bg-slate-100 p-1 rounded-full w-fit mb-4 max-w-full overflow-x-auto">
                {Object.entries(jenisMeta).map(([key, m]) => {
                    const Ikon = m.ikon;
                    const aktif = jenisAktif === key;
                    const total = key === 'koperasi' ? totalKoperasi : totalDanaSosial;
                    return (
                        <button
                            key={key}
                            onClick={() => pindahTab(key)}
                            aria-pressed={aktif}
                            className={`inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold rounded-full whitespace-nowrap transition-all ${
                                aktif ? 'bg-white text-slate-800 shadow-md' : 'text-slate-500 hover:text-slate-700'
                            }`}
                        >
                            <Ikon size={15} />
                            {m.label}
                            <span className={`text-xs tabular-nums ${aktif ? 'text-slate-400' : 'text-slate-400'}`}>
                                {formatRupiahSingkat(total)}
                            </span>
                        </button>
                    );
                })}
            </div>

            {showForm && (
                <Card className="mb-4 border-brand-green/30 shadow-md">
                    <form onSubmit={submit}>
                        <div className="flex items-center gap-2 mb-3">
                            <span className="w-8 h-8 rounded-xl bg-brand-green-light text-brand-green-dark inline-flex items-center justify-center shrink-0">
                                <ReceiptText size={16} />
                            </span>
                            <div>
                                <p className="text-sm font-bold text-slate-800">Pengeluaran {meta.label}</p>
                                <p className="text-xs text-slate-400">Tersimpan ke jurnal kas keluar</p>
                            </div>
                        </div>
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-x-3">
                            <FormField label="Jumlah (Rp)" error={errors.jumlah} required>
                                <div className="relative">
                                    <span className="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm font-bold text-slate-400 pointer-events-none">Rp</span>
                                    <TextField
                                        size="sm"
                                        type="number"
                                        min="1"
                                        value={data.jumlah}
                                        onChange={(e) => setData('jumlah', e.target.value)}
                                        placeholder="250000"
                                        autoFocus
                                        required
                                        className="pl-10 tabular-nums"
                                    />
                                </div>
                                <div className="flex flex-wrap gap-1.5 mt-2">
                                    {chipNominal.map((n) => (
                                        <button
                                            key={n}
                                            type="button"
                                            onClick={() => setData('jumlah', String(n))}
                                            className={`px-2.5 py-1 text-xs font-bold rounded-full border transition-colors tabular-nums ${
                                                String(data.jumlah) === String(n)
                                                    ? 'bg-brand-navy text-white border-brand-navy'
                                                    : 'bg-slate-50 text-slate-600 border-slate-200 hover:border-brand-green/50 hover:text-brand-green-dark'
                                            }`}
                                        >
                                            {formatRupiahSingkat(n)}
                                        </button>
                                    ))}
                                </div>
                            </FormField>
                            <FormField label="Tanggal" error={errors.tanggal} required>
                                <div className="relative">
                                    <CalendarDays size={15} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
                                    <TextField
                                        size="sm"
                                        type="date"
                                        value={data.tanggal}
                                        onChange={(e) => setData('tanggal', e.target.value)}
                                        max={hariIni}
                                        required
                                        className="pl-9"
                                    />
                                </div>
                            </FormField>
                        </div>
                        <FormField label="Keterangan" error={errors.keterangan} required>
                            <TextField
                                size="sm"
                                as="textarea"
                                rows={2}
                                value={data.keterangan}
                                onChange={(e) => setData('keterangan', e.target.value)}
                                placeholder="Contoh: Biaya ATK dan operasional kantor"
                                required
                            />
                        </FormField>
                        <div className="flex items-center gap-2">
                            <Button type="submit" size="sm" disabled={processing} className="rounded-full shadow-md shadow-brand-green/25">
                                {processing ? 'Menyimpan...' : `Simpan • ${data.jumlah ? formatRupiah(Number(data.jumlah)) : 'Rp 0'}`}
                            </Button>
                            <Button type="button" variant="ghost" size="sm" className="rounded-full" onClick={() => setShowForm(false)}>
                                Batal
                            </Button>
                        </div>
                    </form>
                </Card>
            )}

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
                                placeholder="Cari keterangan atau pencatat..."
                                aria-label="Cari pengeluaran"
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
                            <TextField
                                size="sm"
                                type="month"
                                value={filters.bulan ?? ''}
                                onChange={(e) => terapkan({ bulan: e.target.value })}
                                aria-label="Filter bulan"
                                className="flex-1 min-w-0 md:flex-none md:w-44 text-sm rounded-full border-slate-200 bg-slate-50/60"
                            />
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
                                <th className="px-4 py-2.5 text-xs font-bold uppercase tracking-wide text-slate-500 whitespace-nowrap">Tanggal</th>
                                <th className="px-4 py-2.5 text-xs font-bold uppercase tracking-wide text-slate-500">Keterangan</th>
                                <th className="hidden sm:table-cell px-4 py-2.5 text-xs font-bold uppercase tracking-wide text-slate-500 whitespace-nowrap">Dicatat Oleh</th>
                                <th className="px-4 py-2.5 text-right text-xs font-bold uppercase tracking-wide text-slate-500 whitespace-nowrap">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            {pengeluaran.data.length === 0 ? (
                                <tr>
                                    <td colSpan={4} className="px-4 py-10 text-center">
                                        <p className="text-sm font-semibold text-slate-600">Belum ada pengeluaran tercatat</p>
                                        <p className="text-sm text-slate-400 mt-1">Coba ubah kata kunci atau bulan.</p>
                                        {filterAktif && (
                                            <Button type="button" variant="outline" size="sm" className="mt-3 rounded-full" onClick={resetFilter}>
                                                Tampilkan semua
                                            </Button>
                                        )}
                                    </td>
                                </tr>
                            ) : (
                                pengeluaran.data.map((p) => (
                                    <tr key={p.id} className="border-b border-slate-50 last:border-0 hover:bg-gradient-to-r hover:from-rose-50/60 hover:to-transparent transition-all">
                                        <td className="px-4 py-2.5 whitespace-nowrap">
                                            <p className="text-sm font-semibold text-slate-700">{p.tanggal}</p>
                                            <p className="text-xs text-slate-400">{p.hari}</p>
                                        </td>
                                        <td className="px-4 py-2.5">
                                            <p className="text-sm text-slate-700 max-w-[280px] truncate" title={p.keterangan}>{p.keterangan}</p>
                                        </td>
                                        <td className="hidden sm:table-cell px-4 py-2.5 whitespace-nowrap">
                                            <div className="flex items-center gap-2">
                                                <span className="w-7 h-7 rounded-full bg-slate-100 text-slate-600 inline-flex items-center justify-center text-xs font-bold shrink-0" aria-hidden="true">
                                                    {p.input_oleh.charAt(0).toUpperCase()}
                                                </span>
                                                <span className="text-sm text-slate-500 truncate max-w-[140px]">{p.input_oleh}</span>
                                            </div>
                                        </td>
                                        <td className="px-4 py-2.5 text-right font-bold text-rose-600 tabular-nums whitespace-nowrap">
                                            - {formatRupiah(p.jumlah)}
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            </Card>

            <Pagination links={pengeluaran.links} />
        </AppLayout>
    );
}
