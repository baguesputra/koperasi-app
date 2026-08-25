import AppLayout from '@/Layouts/AppLayout';
import { Head, usePage, useForm, router } from '@inertiajs/react';
import { Wallet, HeartHandshake, PiggyBank, Landmark, ArrowDownCircle, ArrowUpCircle, Plus, ChevronLeft, ChevronRight } from 'lucide-react';
import { useMemo, useState } from 'react';
import Card from '@/Components/ui/Card';
import Button from '@/Components/ui/Button';
import Pagination from '@/Components/ui/Pagination';
import PageHeader from '@/Components/ui/PageHeader';
import { formatRupiah } from '@/Utils/formatCurrency';

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
};

const kantongLabel = {
    pinjaman: 'Dana Pinjaman',
    dana_sosial: 'Dana Sosial',
    pengembalian_simpanan: 'Pengembalian Simpanan',
    simpanan: 'Simpanan Anggota',
};

export default function Index({
    saldoPinjaman,
    saldoDanaSosial,
    totalSimpananOutstanding,
    totalAkumulasiSimpanan,
    totalKeseluruhan,
    kantongAktif,
    bulanFilter,
    ringkasanPeriode,
    riwayat,
}) {
    const { auth } = usePage().props;
    const bisaTopup = auth.user?.permissions?.includes('kas.topup');
    const [showForm, setShowForm] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        kantong: kantongAktif,
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
        post(route('kas-koperasi.topup'), {
            onSuccess: () => {
                reset('jumlah', 'keterangan');
                setShowForm(false);
            },
        });
    }

    const widgets = [
        {
            label: 'Saldo Dana Pinjaman',
            value: formatRupiah(saldoPinjaman),
            icon: Wallet,
            iconClass: 'bg-brand-green-light text-brand-green-dark',
            highlight: false,
        },
        {
            label: 'Saldo Dana Sosial',
            value: formatRupiah(saldoDanaSosial),
            icon: HeartHandshake,
            iconClass: 'bg-amber-50 text-amber-700',
            highlight: false,
        },
        {
            label: 'Total Simpanan Anggota',
            value: formatRupiah(totalSimpananOutstanding),
            icon: PiggyBank,
            iconClass: 'bg-brand-navy/5 text-brand-navy',
            highlight: false,
        },
        {
            label: 'Total Keseluruhan',
            value: formatRupiah(totalKeseluruhan),
            icon: Landmark,
            iconClass: 'bg-white/10 text-brand-green',
            highlight: true,
        },
    ];

    const tab = [
        { key: 'pinjaman', label: 'Dana Pinjaman' },
        { key: 'dana_sosial', label: 'Dana Sosial' },
        { key: 'pengembalian_simpanan', label: 'Pengembalian Simpanan' },
    ];

    return (
        <AppLayout>
            <Head title="Kas Koperasi" />

            <PageHeader title="Kas Koperasi" subtitle="Saldo dan riwayat mutasi keuangan koperasi">
                <div className="flex flex-wrap items-center gap-2">
                    <div className="flex items-center rounded-xl border border-slate-300 bg-white overflow-hidden">
                        <button
                            onClick={() => geserBulan(-1)}
                            aria-label={`Bulan sebelum ${labelBulan}`}
                            className={`px-2.5 py-2.5 text-slate-500 hover:bg-slate-50 hover:text-slate-700 transition-colors ${fokusRing}`}
                        >
                            <ChevronLeft size={18} />
                        </button>
                        <input
                            type="month"
                            value={bulanFilter}
                            onChange={(e) => e.target.value && ubahBulan(e.target.value)}
                            aria-label="Pilih bulan"
                            className="w-[9.5rem] px-2 py-2.5 text-sm font-semibold text-slate-700 border-x border-slate-300 bg-white focus:border-brand-green outline-none"
                        />
                        <button
                            onClick={() => geserBulan(1)}
                            aria-label={`Bulan setelah ${labelBulan}`}
                            className={`px-2.5 py-2.5 text-slate-500 hover:bg-slate-50 hover:text-slate-700 transition-colors ${fokusRing}`}
                        >
                            <ChevronRight size={18} />
                        </button>
                    </div>
                    {bulanFilter !== kunciBulanIni && (
                        <button
                            onClick={() => ubahBulan(kunciBulanIni)}
                            className={`px-4 py-2.5 text-sm font-semibold text-brand-green-dark bg-brand-green-light rounded-xl hover:bg-brand-green/20 transition-colors ${fokusRing}`}
                        >
                            Bulan ini
                        </button>
                    )}
                </div>
            </PageHeader>

            {/* Widget ringkasan */}
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                {widgets.map((w) => (
                    <div
                        key={w.label}
                        className={
                            w.highlight
                                ? 'bg-brand-navy rounded-2xl p-5 text-white'
                                : 'bg-white rounded-2xl border border-slate-100 p-5'
                        }
                    >
                        <div className={`w-10 h-10 rounded-xl flex items-center justify-center mb-3 ${w.iconClass}`}>
                            <w.icon size={20} aria-hidden="true" />
                        </div>
                        <p className={`text-sm ${w.highlight ? 'text-slate-300' : 'text-slate-400'}`}>{w.label}</p>
                        <p className="text-xl font-bold mt-0.5">{w.value}</p>
                        {w.label === 'Total Simpanan Anggota' && (
                            <p className="mt-2 text-xs text-slate-400">
                                Gross akumulasi semua anggota:{' '}
                                <span className="font-mono">{formatRupiah(totalAkumulasiSimpanan)}</span>
                            </p>
                        )}
                    </div>
                ))}
            </div>

            {/* Tab kantong + tombol topup */}
            <div className="flex items-center justify-between gap-3 flex-wrap mb-5">
                <div className="relative max-w-full">
                    <div className="flex items-center gap-2 bg-slate-100 p-1 rounded-xl w-fit max-w-full overflow-x-auto scrollbar-hide">
                        {tab.map((t) => (
                            <button
                                key={t.key}
                                onClick={() => pindahTab(t.key)}
                                aria-current={kantongAktif === t.key ? 'true' : undefined}
                                className={`px-4 py-2 text-sm font-semibold rounded-lg whitespace-nowrap transition-colors shrink-0 ${fokusRing} ${
                                    kantongAktif === t.key ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700'
                                }`}
                            >
                                {t.label}
                            </button>
                        ))}
                    </div>
                    <div aria-hidden="true" className="pointer-events-none absolute inset-y-0 right-0 w-8 bg-gradient-to-l from-slate-100 to-transparent rounded-r-xl" />
                </div>

                {bisaTopup && kantongAktif !== 'pengembalian_simpanan' && !showForm && (
                    <Button variant="primary" onClick={bukaForm}>
                        <Plus size={18} aria-hidden="true" />
                        Topup {kantongLabel[kantongAktif] ?? 'Kantong'}
                    </Button>
                )}
            </div>

            {showForm && (
                <Card className="mb-5">
                    <form onSubmit={submit} className="flex flex-col sm:flex-row sm:flex-wrap sm:items-end gap-3">
                        <div>
                            <label htmlFor="topup-jumlah" className="block text-sm font-semibold text-slate-600 mb-1.5">
                                Jumlah (Rp)
                            </label>
                            <input
                                id="topup-jumlah"
                                type="number"
                                min="1"
                                value={data.jumlah}
                                onChange={(e) => setData('jumlah', e.target.value)}
                                className={`w-full sm:w-44 px-3 py-2.5 text-base rounded-xl border border-slate-300 bg-white focus:border-brand-green outline-none ${fokusRing}`}
                                autoFocus
                            />
                            {errors.jumlah && <p className="text-xs text-red-600 mt-1">{errors.jumlah}</p>}
                        </div>
                        <div className="flex-1 min-w-[220px]">
                            <label htmlFor="topup-keterangan" className="block text-sm font-semibold text-slate-600 mb-1.5">
                                Keterangan
                            </label>
                            <input
                                id="topup-keterangan"
                                type="text"
                                value={data.keterangan}
                                onChange={(e) => setData('keterangan', e.target.value)}
                                placeholder={`Contoh: Topup ${labelBulan} dari keuntungan`}
                                className={`w-full px-3 py-2.5 text-base rounded-xl border border-slate-300 bg-white placeholder:text-slate-400 focus:border-brand-green outline-none ${fokusRing}`}
                            />
                        </div>
                        <div className="flex items-center gap-2">
                            <Button type="submit" variant="primary" disabled={processing}>
                                {processing ? 'Menyimpan...' : 'Simpan'}
                            </Button>
                            <Button type="button" variant="ghost" onClick={() => setShowForm(false)}>
                                Batal
                            </Button>
                        </div>
                    </form>
                </Card>
            )}

            {/* Filter periode + ringkasan arus kas */}
            <div className="flex items-center justify-between flex-wrap gap-3 mb-5">
                <p className="text-sm text-slate-500">
                    Arus kas <span className="font-semibold text-slate-700">{labelBulan}</span>
                </p>
                <div className="flex items-center gap-2">
                    <span className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-semibold whitespace-nowrap bg-brand-green-light text-brand-green-dark">
                        Masuk +{formatRupiah(ringkasanPeriode.total_masuk)}
                    </span>
                    <span className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-semibold whitespace-nowrap bg-red-50 text-red-700">
                        Keluar -{formatRupiah(ringkasanPeriode.total_keluar)}
                    </span>
                </div>
            </div>

            <Card padding="none">
                {riwayat.data.length === 0 ? (
                    <div className="text-center py-12 px-4">
                        <Wallet size={28} aria-hidden="true" className="mx-auto text-slate-300 mb-3" />
                        <p className="text-base text-slate-500">
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
                    <div className="divide-y divide-slate-50">
                        {riwayat.data.map((r) => (
                            <div key={r.id} className="flex items-center gap-3 sm:gap-4 px-4 sm:px-5 py-4 hover:bg-slate-50 transition-colors">
                                {r.tipe === 'masuk' ? (
                                    <ArrowDownCircle size={22} aria-hidden="true" className="text-brand-green shrink-0" />
                                ) : (
                                    <ArrowUpCircle size={22} aria-hidden="true" className="text-red-500 shrink-0" />
                                )}
                                <div className="flex-1 min-w-0">
                                    <div className="flex items-center gap-2 flex-wrap">
                                        <p className="text-base font-semibold text-slate-700">
                                            {kategoriLabel[r.kategori] ?? r.kategori}
                                        </p>
                                        {r.kantong !== kantongAktif && (
                                            <span className="text-[10px] font-semibold uppercase tracking-wide px-1.5 py-0.5 rounded bg-slate-100 text-slate-500 whitespace-nowrap">
                                                dari {kantongLabel[r.kantong] ?? r.kantong}
                                            </span>
                                        )}
                                    </div>
                                    {r.sub_judul && (
                                        <p className="text-xs italic text-slate-500 mt-0.5">{r.sub_judul}</p>
                                    )}
                                    <p className="text-sm text-slate-400 mt-0.5 break-words">
                                        {r.tanggal}{r.keterangan ? ` \u2022 ${r.keterangan}` : ''}
                                    </p>
                                </div>
                                <div className="text-right shrink-0 pl-2">
                                    <p className={`text-base font-bold whitespace-nowrap ${r.tipe === 'masuk' ? 'text-brand-green' : 'text-red-600'}`}>
                                        {r.tipe === 'masuk' ? '+' : '-'} {formatRupiah(r.jumlah)}
                                    </p>
                                    <p className="text-xs text-slate-400 whitespace-nowrap">Saldo: {formatRupiah(r.saldo_setelah)}</p>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </Card>

            <Pagination links={riwayat.links} />
        </AppLayout>
    );
}

