import AppLayout from '@/Layouts/AppLayout';
import { Head, usePage, useForm, router, Link } from '@inertiajs/react';
import { Wallet, ArrowDownCircle, ArrowUpCircle, Plus, ChevronLeft, ChevronRight, Landmark, ReceiptText, CalendarDays, Search, X, FileSearch } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import Card from '@/Components/ui/Card';
import Button from '@/Components/ui/Button';
import Pagination from '@/Components/ui/Pagination';
import PageHeader from '@/Components/ui/PageHeader';
import StatWidget from '@/Components/ui/StatWidget';
import TextField from '@/Components/ui/TextField';
import FormField from '@/Components/ui/FormField';
import Select from '@/Components/ui/Select';
import useDebouncedValue from '@/Utils/useDebouncedValue';
import ChipNominal from '@/Components/ui/ChipNominal';
import { formatRupiah, formatRupiahSingkat } from '@/Utils/formatCurrency';
import { withIdempotencyKey } from '@/Utils/idempotency';

const fokusRing = 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-green/40';

const kategoriLabel = {
    saldo_awal: 'Saldo Awal',
    topup_bulanan: 'Topup Saldo',
    pencairan_pinjaman: 'Pencairan Pinjaman',
    pembayaran_angsuran: 'Pembayaran Angsuran',
    dana_sosial_bulanan: 'Dana Sosial Bulanan',
    pengeluaran_koperasi: 'Pengeluaran Koperasi',
    pengeluaran_dana_sosial: 'Pengeluaran Dana Sosial',
    pelunasan_resign_pinjaman: 'Pelunasan Resign Pinjaman',
    pelunasan_resign_simpanan: 'Pelunasan Pinjaman dari Simpanan',
    simpanan_resign_masuk: 'Simpanan Anggota (Resign)',
    return_simpanan_pokok: 'Return Simpanan Pokok',
    return_simpanan_wajib: 'Return Simpanan Wajib',
    simpanan_pokok_masuk: 'Simpanan Pokok Masuk',
    simpanan_wajib_masuk: 'Simpanan Wajib Masuk',
    transfer_ke_dana_pinjaman: 'Transfer ke Dana Pinjaman',
    terima_dari_pengembalian_simpanan: 'Terima dari Pengembalian Simpanan',
    talangan_sosial_ke_pinjaman: 'Talangan Sosial ke Pinjaman',
    talangan_simpanan_ke_pinjaman: 'Talangan Simpanan ke Pinjaman',
    terima_talangan_dari_sosial: 'Terima Talangan dari Sosial',
    terima_talangan_dari_simpanan: 'Terima Talangan dari Simpanan',
    kembali_talangan_dari_pinjaman: 'Pengembalian Talangan dari Pinjaman',
    kembali_talangan_ke_simpanan: 'Pengembalian Talangan ke Simpanan',
    kembali_talangan_ke_sosial: 'Pengembalian Talangan ke Sosial',
    sisih_kas_kecil: 'Sisih Kas Kecil',
    terima_sisih_kas_kecil: 'Terima Sisih Kas Kecil',
};

const kantongLabel = {
    pinjaman: 'Pinjaman',
    dana_sosial: 'Dana Sosial',
    pengembalian_simpanan: 'Pengembalian Simpanan',
    simpanan: 'Simpanan Anggota',
    bank: 'Bank',
    kas_kecil: 'Kas Kecil',
};

// Kanal fisik tempat uang bergerak: bank, kas_kecil, atau audit (tanpa gerak uang).
const kanalMeta = {
    bank: { label: 'Bank', ikon: Landmark, warna: 'bg-brand-navy/10 text-brand-navy' },
    kas_kecil: { label: 'Kas Kecil', ikon: Wallet, warna: 'bg-amber-50 text-amber-700' },
    audit: { label: 'Non-kas', ikon: FileSearch, warna: 'bg-slate-100 text-slate-500' },
};

export default function Index({
    saldoBank,
    saldoKasKecil,
    bulanFilter,
    filters = {},
    ringkasanPeriode,
    ringkasanKanal,
    riwayat,
}) {
    const { auth } = usePage().props;
    const bisaTopup = auth.user?.permissions?.includes('kas.topup');
    const [showForm, setShowForm] = useState(false);
    const [showSisih, setShowSisih] = useState(false);
    const [cari, setCari] = useState(filters.cari ?? '');
    const cariDebounced = useDebouncedValue(cari);
    const pertama = useRef(true);

    const { data, setData, post, processing, errors, reset } = useForm({
        jumlah: '',
        keterangan: '',
    });

    const sisihForm = useForm({
        jumlah: '',
        keterangan: '',
    });

    function terapkan(overrides = {}) {
        router.get(
            route('kas-koperasi.index'),
            { bulan: bulanFilter, kanal: filters.kanal ?? '', cari, ...overrides },
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
        router.get(route('kas-koperasi.index'), { bulan: bulanFilter }, { preserveState: true, replace: true });
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

    const labelBulan = useMemo(() => {
        const [tahun, bulanAngka] = bulanFilter.split('-').map(Number);
        return new Date(tahun, bulanAngka - 1, 1).toLocaleDateString('id-ID', { month: 'long', year: 'numeric' });
    }, [bulanFilter]);

    const bulanIni = new Date();
    const kunciBulanIni = `${bulanIni.getFullYear()}-${String(bulanIni.getMonth() + 1).padStart(2, '0')}`;
    const filterAktif = Boolean(filters.kanal || filters.cari);

    // Kelompokkan baris per tanggal (riwayat sudah urut tanggal desc).
    const grupHari = useMemo(() => {
        const peta = new Map();
        for (const r of riwayat.data) {
            if (!peta.has(r.tanggal)) peta.set(r.tanggal, { hari: r.hari, baris: [] });
            peta.get(r.tanggal).baris.push(r);
        }
        return [...peta.entries()];
    }, [riwayat.data]);

    function submit(e) {
        e.preventDefault();
        post(
            route('kas-koperasi.topup'),
            withIdempotencyKey({
                preserveScroll: true,
                onSuccess: () => {
                    reset('jumlah', 'keterangan');
                    setShowForm(false);
                },
            })
        );
    }

    function submitSisih(e) {
        e.preventDefault();
        sisihForm.post(
            route('kas-koperasi.sisih-kas-kecil'),
            withIdempotencyKey({
                preserveScroll: true,
                onSuccess: () => {
                    sisihForm.reset('jumlah', 'keterangan');
                    setShowSisih(false);
                },
            })
        );
    }

    function badgeKanal(kanal) {
        const meta = kanalMeta[kanal] ?? kanalMeta.bank;
        const Ikon = meta.ikon;
        return (
            <span className={`inline-flex items-center gap-1 text-[10px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded-full whitespace-nowrap ${meta.warna}`}>
                <Ikon size={11} aria-hidden="true" />
                {meta.label}
            </span>
        );
    }

    function badgeKantong(kantong) {
        return (
            <span className="text-[10px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded-full bg-slate-100 text-slate-500 whitespace-nowrap">
                {kantongLabel[kantong] ?? kantong}
            </span>
        );
    }

    function tampilBadge(r) {
        // Badge kantong ganda kalau labelnya beda dengan kanal (mis. Bank + Pinjaman).
        const sama = r.kantong === r.kanal || (r.kanal === 'audit' && r.kantong === 'audit');
        return (
            <>
                {badgeKanal(r.kanal)}
                {!sama && badgeKantong(r.kantong)}
            </>
        );
    }

    function barisRuteAsal(r) {
        if (!r.rute_asal) return null;
        if (!auth.user?.permissions?.includes(r.rute_asal.izin)) return null;
        return (
            <Link
                href={route(r.rute_asal.nama, r.rute_asal.id)}
                className="text-xs font-semibold text-brand-green hover:text-brand-green-dark hover:underline shrink-0"
            >
                Lihat asal
            </Link>
        );
    }

    return (
        <AppLayout>
            <Head title="Kas Koperasi" />

            <PageHeader title="Kas Koperasi" subtitle="Saldo dan mutasi kas koperasi">
                {(bisaTopup && !showForm) || (bisaTopup && !showSisih) ? (
                    <div className="flex items-center gap-2 flex-wrap">
                        {bisaTopup && !showForm && (
                            <Button size="sm" onClick={() => setShowForm(true)} className="rounded-full shadow-md shadow-brand-green/25 hover:-translate-y-px active:translate-y-0">
                                <Plus size={16} aria-hidden="true" />
                                Topup Bank
                            </Button>
                        )}
                        {bisaTopup && !showSisih && (
                            <Button size="sm" variant="secondary" onClick={() => setShowSisih(true)} className="rounded-full shadow-md hover:-translate-y-px active:translate-y-0">
                                <Plus size={16} aria-hidden="true" />
                                Sisih Kas Kecil
                            </Button>
                        )}
                    </div>
                ) : null}
            </PageHeader>

            <div className="rounded-2xl bg-gradient-to-r from-brand-navy to-brand-navy-light text-white px-5 py-4 mb-3 shadow-md shadow-brand-navy/20">
                <div className="flex items-center gap-3">
                    <span className="w-11 h-11 rounded-2xl bg-white/15 inline-flex items-center justify-center shrink-0">
                        <Landmark size={22} />
                    </span>
                    <div className="min-w-0 flex-1">
                        <p className="text-xs text-white/70">Kas (Bank + Kas Kecil)</p>
                        <p className="text-2xl font-bold tabular-nums leading-tight">{formatRupiah((saldoBank ?? 0) + (saldoKasKecil ?? 0))}</p>
                    </div>
                </div>
                <dl className="grid grid-cols-2 md:grid-cols-4 gap-2 mt-3 pt-3 border-t border-white/15 text-sm">
                    <div className="rounded-xl bg-white/10 px-3 py-2">
                        <dt className="text-xs text-white/70">Masuk {labelBulan}</dt>
                        <dd className="font-bold tabular-nums text-emerald-300">+ {formatRupiah(ringkasanPeriode.total_masuk)}</dd>
                    </div>
                    <div className="rounded-xl bg-white/10 px-3 py-2">
                        <dt className="text-xs text-white/70">Keluar {labelBulan}</dt>
                        <dd className="font-bold tabular-nums text-red-300">- {formatRupiah(ringkasanPeriode.total_keluar)}</dd>
                    </div>
                    <div className="rounded-xl bg-white/10 px-3 py-2">
                        <dt className="text-xs text-white/70">Bank</dt>
                        <dd className="font-bold tabular-nums">
                            <span className="text-emerald-300">+{formatRupiahSingkat(ringkasanKanal.bank.masuk)}</span>
                            <span className="text-white/50"> / </span>
                            <span className="text-red-300">-{formatRupiahSingkat(ringkasanKanal.bank.keluar)}</span>
                        </dd>
                    </div>
                    <div className="rounded-xl bg-white/10 px-3 py-2">
                        <dt className="text-xs text-white/70">Kas Kecil</dt>
                        <dd className="font-bold tabular-nums">
                            <span className="text-emerald-300">+{formatRupiahSingkat(ringkasanKanal.kas_kecil.masuk)}</span>
                            <span className="text-white/50"> / </span>
                            <span className="text-red-300">-{formatRupiahSingkat(ringkasanKanal.kas_kecil.keluar)}</span>
                        </dd>
                    </div>
                </dl>
            </div>

            <div className="grid grid-cols-2 gap-3 mb-4">
                <div className="transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md rounded-xl">
                    <StatWidget compact label="Saldo Bank" value={formatRupiah(saldoBank ?? 0)} icon={Landmark} tone="navy" />
                </div>
                <div className="transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md rounded-xl">
                    <StatWidget compact label="Saldo Kas Kecil" value={formatRupiah(saldoKasKecil ?? 0)} icon={Wallet} tone="amber" />
                </div>
            </div>

            {showForm && (
                <Card className="mb-4 border-brand-green/30 shadow-md">
                    <form onSubmit={submit}>
                        <div className="flex items-center gap-2 mb-3">
                            <span className="w-8 h-8 rounded-xl bg-brand-green-light text-brand-green-dark inline-flex items-center justify-center shrink-0">
                                <ReceiptText size={16} />
                            </span>
                            <div>
                                <p className="text-sm font-bold text-slate-800">Topup Bank</p>
                                <p className="text-xs text-slate-400">Masuk jurnal kas sebagai topup bulanan ke Bank</p>
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
                                        placeholder="5000000"
                                        autoFocus
                                        required
                                        className="pl-10 tabular-nums"
                                    />
                                </div>
                                <ChipNominal grup="topup" nilai={data.jumlah} onPilih={(n) => setData('jumlah', n)} />
                            </FormField>
                            <FormField label="Keterangan" error={errors.keterangan} hint={`Contoh: Topup ${labelBulan} dari pendapatan bunga`}>
                                <TextField
                                    size="sm"
                                    value={data.keterangan}
                                    onChange={(e) => setData('keterangan', e.target.value)}
                                    placeholder={`Topup ${labelBulan} dari pendapatan bunga`}
                                />
                            </FormField>
                        </div>
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

            {showSisih && (
                <Card className="mb-4 border-brand-green/30 shadow-md">
                    <form onSubmit={submitSisih}>
                        <div className="flex items-center gap-2 mb-3">
                            <span className="w-8 h-8 rounded-xl bg-brand-green-light text-brand-green-dark inline-flex items-center justify-center shrink-0">
                                <Landmark size={16} />
                            </span>
                            <div>
                                <p className="text-sm font-bold text-slate-800">Sisih Kas Kecil</p>
                                <p className="text-xs text-slate-400">Pindahkan uang fisik dari Bank ke Kas Kecil dalam satu jurnal atomik</p>
                            </div>
                        </div>
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-x-3">
                            <FormField label="Jumlah (Rp)" error={sisihForm.errors.jumlah} required>
                                <div className="relative">
                                    <span className="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm font-bold text-slate-400 pointer-events-none">Rp</span>
                                    <TextField
                                        size="sm"
                                        type="number"
                                        min="1"
                                        value={sisihForm.data.jumlah}
                                        onChange={(e) => sisihForm.setData('jumlah', e.target.value)}
                                        placeholder="1000000"
                                        autoFocus
                                        required
                                        className="pl-10 tabular-nums"
                                    />
                                </div>
                                <ChipNominal grup="sisih" nilai={sisihForm.data.jumlah} onPilih={(n) => sisihForm.setData('jumlah', n)} />
                            </FormField>
                            <FormField label="Keterangan" error={sisihForm.errors.keterangan} hint="Contoh: Kas operasional sekretariat minggu ini">
                                <TextField
                                    size="sm"
                                    value={sisihForm.data.keterangan}
                                    onChange={(e) => sisihForm.setData('keterangan', e.target.value)}
                                    placeholder="Sisih kas kecil untuk operasional"
                                />
                            </FormField>
                        </div>
                        <div className="flex items-center gap-2">
                            <Button type="submit" size="sm" disabled={sisihForm.processing} className="rounded-full shadow-md shadow-brand-green/25">
                                {sisihForm.processing ? 'Menyimpan...' : `Simpan • ${sisihForm.data.jumlah ? formatRupiah(Number(sisihForm.data.jumlah)) : 'Rp 0'}`}
                            </Button>
                            <Button type="button" variant="ghost" size="sm" className="rounded-full" onClick={() => setShowSisih(false)}>
                                Batal
                            </Button>
                        </div>
                    </form>
                </Card>
            )}

            <Card padding="sm" className="shadow-md border-slate-200/70 mb-4">
                <div className="flex flex-col md:flex-row md:items-center gap-2 pb-3 mb-1 border-b border-slate-100">
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-2 flex-1">
                        <Select size="sm" value={filters.kanal ?? ''} onChange={(e) => terapkan({ kanal: e.target.value })} aria-label="Filter akun kas" className={fokusRing}>
                            <option value="">Semua akun kas</option>
                            {Object.entries(kanalMeta).map(([key, meta]) => (
                                <option key={key} value={key}>{meta.label}</option>
                            ))}
                        </Select>
                        <div className="flex items-center rounded-xl border border-slate-300 bg-white overflow-hidden">
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
                        <div className="relative col-span-2 md:col-span-2">
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
                        Mutasi kas <span className="font-bold text-slate-800">{labelBulan}</span>
                    </p>
                </div>
                {riwayat.data.length === 0 ? (
                    <div className="text-center py-12 px-4">
                        <Wallet size={28} aria-hidden="true" className="mx-auto text-slate-300 mb-3" />
                        <p className="text-sm font-semibold text-slate-600">
                            Belum ada mutasi kas untuk {labelBulan}.
                        </p>
                        {filterAktif && (
                            <Button type="button" variant="outline" size="sm" className="mt-3 rounded-full" onClick={resetFilter}>
                                Tampilkan semua
                            </Button>
                        )}
                        {bisaTopup && !filterAktif && bulanFilter === kunciBulanIni && (
                            <div className="mt-3">
                                <Button type="button" size="sm" className="rounded-full" onClick={() => setShowForm(true)}>
                                    <Plus size={16} aria-hidden="true" />
                                    Topup Bank
                                </Button>
                            </div>
                        )}
                    </div>
                ) : (
                    <ul className="divide-y divide-slate-50">
                        {grupHari.map(([tanggal, grup]) => (
                            <li key={tanggal} className="pt-3 first:pt-1">
                                <div className="flex items-baseline gap-2 px-1 pb-1.5">
                                    <p className="text-sm font-bold text-slate-700">{tanggal}</p>
                                    <p className="text-xs text-slate-400">{grup.hari}</p>
                                    <p className="text-xs text-slate-400 ml-auto tabular-nums">{grup.baris.length} mutasi</p>
                                </div>
                                <ul className="border-t border-slate-100/70">
                                    {grup.baris.map((r) => (
                                        <li key={r.id} className="flex items-center gap-3 px-1 py-3 hover:bg-gradient-to-r hover:from-slate-50 hover:to-transparent rounded-xl transition-all">
                                            {r.tipe === 'masuk' ? (
                                                <ArrowDownCircle size={22} aria-hidden="true" className="text-brand-green shrink-0" />
                                            ) : (
                                                <ArrowUpCircle size={22} aria-hidden="true" className="text-red-500 shrink-0" />
                                            )}
                                            <div className="flex-1 min-w-0">
                                                <div className="flex items-center gap-2 flex-wrap">
                                                    <p className="text-sm font-semibold text-slate-700">
                                                        {kategoriLabel[r.kategori] ?? r.kategori}
                                                    </p>
                                                    {tampilBadge(r)}
                                                </div>
                                                {r.sub_judul && (
                                                    <p className="text-xs italic text-slate-500 mt-0.5 truncate" title={r.sub_judul}>{r.sub_judul}</p>
                                                )}
                                                <div className="flex items-center gap-2 mt-0.5">
                                                    {r.keterangan && (
                                                        <p className="text-xs text-slate-400 truncate" title={r.keterangan}>{r.keterangan}</p>
                                                    )}
                                                    {barisRuteAsal(r)}
                                                </div>
                                            </div>
                                            <div className="text-right shrink-0 pl-2">
                                                <p className={`text-sm font-bold whitespace-nowrap tabular-nums ${r.tipe === 'masuk' ? 'text-brand-green-dark' : 'text-red-600'}`}>
                                                    <span className="sr-only">{r.tipe === 'masuk' ? 'Masuk' : 'Keluar'} </span>
                                                    <span aria-hidden="true">{r.tipe === 'masuk' ? '+' : '-'} {formatRupiah(r.jumlah)}</span>
                                                </p>
                                                <p className="text-[11px] text-slate-400 whitespace-nowrap tabular-nums">
                                                    {r.kanal === 'audit'
                                                        ? 'Transaksi non-kas — kas tidak berubah'
                                                        : `${r.kanal === 'bank' ? 'Saldo Bank' : 'Saldo Kas'}: ${formatRupiah(r.saldo_setelah)}`}
                                                </p>
                                            </div>
                                        </li>
                                    ))}
                                </ul>
                            </li>
                        ))}
                    </ul>
                )}
            </Card>

            <Pagination links={riwayat.links} />
        </AppLayout>
    );
}
