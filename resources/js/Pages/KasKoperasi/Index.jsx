import AppLayout from '@/Layouts/AppLayout';
import { Head, usePage, useForm, router } from '@inertiajs/react';
import { Wallet, HeartHandshake, PiggyBank, ArrowDownCircle, ArrowUpCircle, Plus, ChevronLeft, ChevronRight, Landmark, ReceiptText, CalendarDays } from 'lucide-react';
import { useMemo, useState } from 'react';
import Card from '@/Components/ui/Card';
import Button from '@/Components/ui/Button';
import Pagination from '@/Components/ui/Pagination';
import PageHeader from '@/Components/ui/PageHeader';
import StatWidget from '@/Components/ui/StatWidget';
import TextField from '@/Components/ui/TextField';
import FormField from '@/Components/ui/FormField';
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
    pinjaman: 'Dana Pinjaman',
    dana_sosial: 'Dana Sosial',
    iuran: 'Dana Iuran',
    pengembalian_simpanan: 'Pengembalian Simpanan',
    simpanan: 'Simpanan Anggota',
    fisik: 'Bank & Kas Kecil',
    bank: 'Bank',
    kas_kecil: 'Kas Kecil',
};

const kantongIkon = {
    pinjaman: Wallet,
    dana_sosial: HeartHandshake,
    iuran: PiggyBank,
    pengembalian_simpanan: PiggyBank,
    fisik: Landmark,
};

const chipNominal = [500_000, 1_000_000, 2_500_000, 5_000_000, 10_000_000];

export default function Index({
    saldoPinjaman,
    saldoDanaSosial,
    saldoSimpanan,
    totalSimpananOutstanding,
    totalKeseluruhan,
    kasOperasional,
    infoPagu,
    saldoBank,
    saldoKasKecil,
    poolPinjaman,
    kantongAktif,
    bulanFilter,
    ringkasanPeriode,
    riwayat,
}) {
    const { auth } = usePage().props;
    const bisaTopup = auth.user?.permissions?.includes('kas.topup');
    const [showForm, setShowForm] = useState(false);
    const [showSisih, setShowSisih] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        kantong: kantongAktif,
        jumlah: '',
        keterangan: '',
    });

    const sisihForm = useForm({
        jumlah: '',
        keterangan: '',
    });

    function pindahTab(kantong) {
        router.get(route('kas-koperasi.index'), { kantong, bulan: bulanFilter }, { preserveState: true });
    }

    function ubahBulan(bulan) {
        router.get(route('kas-koperasi.index'), { kantong: kantongAktif, bulan }, { preserveState: true });
    }

    function geserBulan(delta) {
        const [tahun, bulanAngka] = bulanFilter.split('-').map(Number);
        const d = new Date(tahun, bulanAngka - 1 + delta, 1);
        ubahBulan(`${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`);
    }

    const labelBulan = useMemo(() => {
        const [tahun, bulanAngka] = bulanFilter.split('-').map(Number);
        return new Date(tahun, bulanAngka - 1, 1).toLocaleDateString('id-ID', { month: 'long', year: 'numeric' });
    }, [bulanFilter]);

    const bulanIni = new Date();
    const kunciBulanIni = `${bulanIni.getFullYear()}-${String(bulanIni.getMonth() + 1).padStart(2, '0')}`;

    function bukaForm() {
        setData('kantong', kantongAktif);
        setShowForm(true);
    }

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

    const tab = [
        { key: 'pinjaman', label: 'Dana Pinjaman' },
        { key: 'iuran', label: 'Dana Iuran' },
        { key: 'pengembalian_simpanan', label: 'Pengembalian Simpanan' },
        { key: 'fisik', label: 'Bank & Kas Kecil' },
    ];

    return (
        <AppLayout>
            <Head title="Kas Koperasi" />

            <PageHeader title="Kas Koperasi" subtitle="Saldo dan riwayat mutasi keuangan koperasi" />

            <div className="rounded-2xl bg-gradient-to-r from-brand-navy to-brand-navy-light text-white px-5 py-4 mb-3 shadow-md shadow-brand-navy/20">
                <div className="flex items-center gap-3">
                    <span className="w-11 h-11 rounded-2xl bg-white/15 inline-flex items-center justify-center shrink-0">
                        <Landmark size={22} />
                    </span>
                    <div className="min-w-0 flex-1">
                        <p className="text-xs text-white/70">Kas Operasional Gabungan</p>
                        <p className="text-2xl font-bold tabular-nums leading-tight">{formatRupiah(kasOperasional ?? totalKeseluruhan)}</p>
                        {infoPagu && (
                            <p className="text-xs text-white/70 mt-0.5">
                                Layak cair {labelBulan}: {formatRupiah(infoPagu.layak)} &bull; Pagu {formatRupiah(infoPagu.pagu)} &bull; Cadangan {formatRupiah(infoPagu.cadangan)}
                            </p>
                        )}
                    </div>
                </div>
            </div>

            <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
                <div className="transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md rounded-xl">
                    <StatWidget compact label="Dana Pinjaman" value={formatRupiah(saldoPinjaman)} icon={Wallet} tone="green" />
                </div>
                <div className="transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md rounded-xl">
                    <StatWidget compact label="Dana Sosial" value={formatRupiah(saldoDanaSosial)} icon={HeartHandshake} tone="amber" />
                </div>
                <div className="transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md rounded-xl">
                    <StatWidget compact label="Simpanan (Kas)" value={formatRupiah(saldoSimpanan)} icon={PiggyBank} tone="blue" />
                </div>
                <div className="transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md rounded-xl">
                    <StatWidget compact label="Simpanan Outstanding" value={formatRupiah(totalSimpananOutstanding)} icon={PiggyBank} tone="navy" />
                </div>
            </div>

            <div className="grid grid-cols-2 gap-3 mb-4">
                <div className="transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md rounded-xl">
                    <StatWidget compact label="Bank" value={formatRupiah(saldoBank ?? poolPinjaman ?? 0)} icon={Landmark} tone="navy" />
                </div>
                <div className="transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md rounded-xl">
                    <StatWidget compact label="Kas Kecil" value={formatRupiah(saldoKasKecil ?? 0)} icon={Wallet} tone="amber" />
                </div>
            </div>

            <div className="flex items-center justify-between gap-3 flex-wrap mb-4">
                <div className="flex items-center gap-1.5 bg-slate-100 p-1 rounded-full w-fit max-w-full overflow-x-auto">
                    {tab.map((t) => {
                        const Ikon = kantongIkon[t.key] ?? Wallet;
                        const aktif = kantongAktif === t.key;
                        return (
                            <button
                                key={t.key}
                                onClick={() => pindahTab(t.key)}
                                aria-pressed={aktif}
                                className={`inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold rounded-full whitespace-nowrap transition-all shrink-0 ${
                                    aktif ? 'bg-white text-slate-800 shadow-md' : 'text-slate-500 hover:text-slate-700'
                                }`}
                            >
                                <Ikon size={15} />
                                {t.label}
                            </button>
                        );
                    })}
                </div>

                {bisaTopup && kantongAktif !== 'pengembalian_simpanan' && kantongAktif !== 'fisik' && !showForm && (
                    <Button size="sm" onClick={bukaForm} className="rounded-full shadow-md shadow-brand-green/25 hover:-translate-y-px active:translate-y-0">
                        <Plus size={16} aria-hidden="true" />
                        Topup {kantongLabel[kantongAktif] ?? 'Kantong'}
                    </Button>
                )}
                {bisaTopup && kantongAktif === 'fisik' && !showSisih && (
                    <Button size="sm" onClick={() => setShowSisih(true)} className="rounded-full shadow-md shadow-brand-green/25 hover:-translate-y-px active:translate-y-0">
                        <Plus size={16} aria-hidden="true" />
                        Sisih Kas Kecil
                    </Button>
                )}
            </div>

            {showForm && (
                <Card className="mb-4 border-brand-green/30 shadow-md">
                    <form onSubmit={submit}>
                        <div className="flex items-center gap-2 mb-3">
                            <span className="w-8 h-8 rounded-xl bg-brand-green-light text-brand-green-dark inline-flex items-center justify-center shrink-0">
                                <ReceiptText size={16} />
                            </span>
                            <div>
                                <p className="text-sm font-bold text-slate-800">Topup {kantongLabel[kantongAktif]}</p>
                                <p className="text-xs text-slate-400">Masuk jurnal kas sebagai topup bulanan</p>
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
                                <div className="flex flex-wrap gap-1.5 mt-2">
                                    {chipNominal.map((n) => (
                                        <button
                                            key={n}
                                            type="button"
                                            onClick={() => sisihForm.setData('jumlah', String(n))}
                                            className={`px-2.5 py-1 text-xs font-bold rounded-full border transition-colors tabular-nums ${
                                                String(sisihForm.data.jumlah) === String(n)
                                                    ? 'bg-brand-navy text-white border-brand-navy'
                                                    : 'bg-slate-50 text-slate-600 border-slate-200 hover:border-brand-green/50 hover:text-brand-green-dark'
                                            }`}
                                        >
                                            {formatRupiahSingkat(n)}
                                        </button>
                                    ))}
                                </div>
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
                <div className="pb-3 mb-1 border-b border-slate-100">
                    <div className="flex flex-col md:flex-row md:items-center gap-2">
                        <div className="flex items-center gap-1.5 min-w-0">
                            <CalendarDays size={15} className="text-slate-400 shrink-0" />
                            <p className="text-sm text-slate-500 truncate">
                                Arus kas <span className="font-bold text-slate-800">{labelBulan}</span>
                            </p>
                            {bulanFilter !== kunciBulanIni && (
                                <button
                                    onClick={() => ubahBulan(kunciBulanIni)}
                                    className="text-xs font-bold text-brand-green-dark bg-brand-green-light rounded-full px-2.5 py-1 hover:bg-brand-green/20 transition-colors shrink-0"
                                >
                                    Bulan ini
                                </button>
                            )}
                        </div>
                        <div className="flex items-center gap-2 md:ml-auto">
                            <div className="flex items-center rounded-full border border-slate-200 bg-slate-50/60 overflow-hidden">
                                <button
                                    onClick={() => geserBulan(-1)}
                                    aria-label={`Bulan sebelum ${labelBulan}`}
                                    className={`px-2.5 py-2 text-slate-500 hover:bg-white hover:text-slate-700 transition-colors ${fokusRing}`}
                                >
                                    <ChevronLeft size={16} />
                                </button>
                                <input
                                    type="month"
                                    value={bulanFilter}
                                    onChange={(e) => e.target.value && ubahBulan(e.target.value)}
                                    aria-label="Pilih bulan"
                                    className="w-[8.5rem] px-1 py-2 text-sm font-semibold text-slate-700 border-x border-slate-200 bg-transparent focus:outline-none"
                                />
                                <button
                                    onClick={() => geserBulan(1)}
                                    aria-label={`Bulan setelah ${labelBulan}`}
                                    className={`px-2.5 py-2 text-slate-500 hover:bg-white hover:text-slate-700 transition-colors ${fokusRing}`}
                                >
                                    <ChevronRight size={16} />
                                </button>
                            </div>
                            <span className="inline-flex items-center px-2.5 py-1.5 rounded-full text-xs font-bold whitespace-nowrap bg-brand-green-light text-brand-green-dark tabular-nums">
                                +{formatRupiahSingkat(ringkasanPeriode.total_masuk)}
                            </span>
                            <span className="inline-flex items-center px-2.5 py-1.5 rounded-full text-xs font-bold whitespace-nowrap bg-red-50 text-red-700 tabular-nums">
                                -{formatRupiahSingkat(ringkasanPeriode.total_keluar)}
                            </span>
                        </div>
                    </div>
                </div>
                {riwayat.data.length === 0 ? (
                    <div className="text-center py-12 px-4">
                        <Wallet size={28} aria-hidden="true" className="mx-auto text-slate-300 mb-3" />
                        <p className="text-sm font-semibold text-slate-600">
                            Belum ada mutasi di {kantongLabel[kantongAktif]} untuk {labelBulan}.
                        </p>
                        {bulanFilter !== kunciBulanIni && (
                            <p className="text-sm text-slate-400 mt-1">
                                Coba{' '}
                                <button onClick={() => ubahBulan(kunciBulanIni)} className={`font-semibold text-brand-green hover:text-brand-green-dark ${fokusRing} rounded`}>
                                    kembali ke bulan ini
                                </button>
                                .
                            </p>
                        )}
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
                                            {kategoriLabel[r.kategori] ?? r.kategori}
                                        </p>
                                        {r.kantong !== kantongAktif && (
                                            <span className="text-[10px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded-full bg-slate-100 text-slate-500 whitespace-nowrap">
                                                dari {kantongLabel[r.kantong] ?? r.kantong}
                                            </span>
                                        )}
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
