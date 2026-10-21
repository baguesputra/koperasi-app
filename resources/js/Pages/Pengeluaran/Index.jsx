import AppLayout from '@/Layouts/AppLayout';
import { Head, router, usePage, useForm } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { Plus, Search, X, RefreshCw, Wallet, ReceiptText, CalendarDays } from 'lucide-react';
import useDebouncedValue from '@/Utils/useDebouncedValue';
import Card from '@/Components/ui/Card';
import Button from '@/Components/ui/Button';
import PageHeader from '@/Components/ui/PageHeader';
import TextField from '@/Components/ui/TextField';
import FormField from '@/Components/ui/FormField';
import Select from '@/Components/ui/Select';
import Pagination from '@/Components/ui/Pagination';
import ChipNominal from '@/Components/ui/ChipNominal';
import { formatRupiah } from '@/Utils/formatCurrency';
import { withIdempotencyKey } from '@/Utils/idempotency';

export default function Index({ pengeluaran, filters = {}, tipeList = {}, akunList = {}, totalTampil, bulanAktif, totalBulan }) {
    const { auth } = usePage().props;
    const bisaCatat = auth.user?.permissions?.includes('kas.topup');
    const [showForm, setShowForm] = useState(false);
    const [cari, setCari] = useState(filters.cari ?? '');
    const cariDebounced = useDebouncedValue(cari);
    const pertama = useRef(true);
    const hariIni = new Date().toISOString().slice(0, 10);

    const { data, setData, post, processing, errors, reset } = useForm({
        tipe: 'lain_lain',
        akun: 'kas_kecil',
        jumlah: '',
        keterangan: '',
        tanggal: hariIni,
    });

    const filterAktif = Boolean((filters.cari ?? '') || (filters.bulan ?? '') || (filters.tipe ?? ''));

    function terapkan(overrides = {}) {
        router.get(
            route('pengeluaran.index'),
            { cari, bulan: filters.bulan, tipe: filters.tipe, ...overrides },
            { preserveState: true, replace: true }
        );
    }

    function resetFilter() {
        setCari('');
        router.get(route('pengeluaran.index'), {}, { preserveState: true, replace: true });
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

    const [tahunBulan, bulanAngka] = (bulanAktif ?? '').split('-').map(Number);
    const labelBulan = bulanAngka
        ? new Date(tahunBulan, bulanAngka - 1, 1).toLocaleDateString('id-ID', { month: 'long', year: 'numeric' })
        : '';

    return (
        <AppLayout>
            <Head title="Pengeluaran" />

            <PageHeader title="Pengeluaran" subtitle="Catat keluar kas">
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
                        <Wallet size={22} />
                    </span>
                    <div className="min-w-0 flex-1">
                        <p className="text-xs text-white/70">
                            Total Pengeluaran{filterAktif ? ' (hasil filter)' : ''}
                        </p>
                        <p className="text-2xl font-bold tabular-nums leading-tight privacy-blur">- {formatRupiah(totalTampil)}</p>
                    </div>
                </div>
                <dl className="grid grid-cols-2 gap-2 mt-3 pt-3 border-t border-white/15 text-sm">
                    <div className="rounded-xl bg-white/10 px-3 py-2">
                        <dt className="text-xs text-white/70">Bulan {labelBulan}</dt>
                        <dd className="font-bold tabular-nums privacy-blur">- {formatRupiah(totalBulan)}</dd>
                    </div>
                </dl>
            </div>

            {showForm && (
                <Card className="mb-4 border-brand-green/30 shadow-md">
                    <form onSubmit={submit}>
                        <div className="flex items-center gap-2 mb-3">
                            <span className="w-8 h-8 rounded-xl bg-brand-green-light text-brand-green-dark inline-flex items-center justify-center shrink-0">
                                <ReceiptText size={16} />
                            </span>
                            <div>
                                <p className="text-sm font-bold text-slate-800">Pengeluaran</p>
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
                                <ChipNominal grup="pengeluaran" nilai={data.jumlah} onPilih={(n) => setData('jumlah', n)} />
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
                            <FormField label="Tipe Pengeluaran" error={errors.tipe} required>
                                <Select
                                    size="sm"
                                    value={data.tipe}
                                    onChange={(e) => setData('tipe', e.target.value)}
                                    aria-label="Tipe pengeluaran"
                                    required
                                >
                                    {Object.entries(tipeList).map(([key, label]) => (
                                        <option key={key} value={key}>{label}</option>
                                    ))}
                                </Select>
                            </FormField>
                            <FormField label="Sumber Dana" error={errors.akun} required hint="Bank atau Kas Kecil — saldo mengikuti sumber terpilih.">
                                <Select
                                    size="sm"
                                    value={data.akun}
                                    onChange={(e) => setData('akun', e.target.value)}
                                    aria-label="Sumber dana"
                                    required
                                >
                                    {Object.entries(akunList).map(([key, label]) => (
                                        <option key={key} value={key}>{label}</option>
                                    ))}
                                </Select>
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
                                {processing ? 'Menyimpan...' : <>Simpan • <span className="privacy-blur">{data.jumlah ? formatRupiah(Number(data.jumlah)) : 'Rp 0'}</span></>}
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
                            <Select
                                size="sm"
                                value={filters.tipe ?? ''}
                                onChange={(e) => terapkan({ tipe: e.target.value })}
                                aria-label="Filter tipe pengeluaran"
                                className="flex-1 min-w-0 md:flex-none md:w-40 text-sm rounded-full border-slate-200 bg-slate-50/60"
                            >
                                <option value="">Semua tipe</option>
                                {Object.entries(tipeList).map(([key, label]) => (
                                    <option key={key} value={key}>{label}</option>
                                ))}
                            </Select>
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
                                <th className="px-4 py-2.5 text-xs font-bold uppercase tracking-wide text-slate-500 whitespace-nowrap">Tipe</th>
                                <th className="hidden sm:table-cell px-4 py-2.5 text-xs font-bold uppercase tracking-wide text-slate-500 whitespace-nowrap">Sumber</th>
                                <th className="hidden sm:table-cell px-4 py-2.5 text-xs font-bold uppercase tracking-wide text-slate-500 whitespace-nowrap">Dicatat Oleh</th>
                                <th className="px-4 py-2.5 text-right text-xs font-bold uppercase tracking-wide text-slate-500 whitespace-nowrap">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            {pengeluaran.data.length === 0 ? (
                                <tr>
                                    <td colSpan={6} className="px-4 py-10 text-center">
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
                                        <td className="px-4 py-2.5 whitespace-nowrap">
                                            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-600">
                                                {p.tipe_label}
                                            </span>
                                        </td>
                                        <td className="hidden sm:table-cell px-4 py-2.5 whitespace-nowrap">
                                            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-brand-navy/10 text-brand-navy">
                                                {p.akun_label}
                                            </span>
                                        </td>
                                        <td className="hidden sm:table-cell px-4 py-2.5 whitespace-nowrap">
                                            <div className="flex items-center gap-2">
                                                <span className="w-7 h-7 rounded-full bg-slate-100 text-slate-600 inline-flex items-center justify-center text-xs font-bold shrink-0" aria-hidden="true">
                                                    {p.input_oleh.charAt(0).toUpperCase()}
                                                </span>
                                                <span className="text-sm text-slate-500 truncate max-w-[140px]">{p.input_oleh}</span>
                                            </div>
                                        </td>
                                        <td className="px-4 py-2.5 text-right font-bold text-rose-600 tabular-nums whitespace-nowrap privacy-blur">
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
