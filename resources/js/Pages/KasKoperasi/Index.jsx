import AppLayout from '@/Layouts/AppLayout';
import { Head, usePage, useForm, router } from '@inertiajs/react';
import { Wallet, HeartHandshake, PiggyBank, Landmark, ArrowDownCircle, ArrowUpCircle, Plus } from 'lucide-react';
import { useState } from 'react';
import Card from '@/Components/ui/Card';
import Button from '@/Components/ui/Button';

function formatRupiah(angka) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka);
}

const kategoriLabel = {
    saldo_awal: 'Saldo Awal',
    topup_bulanan: 'Topup Saldo',
    pencairan_pinjaman: 'Pencairan Pinjaman',
    pembayaran_angsuran: 'Pembayaran Angsuran',
    dana_sosial_bulanan: 'Dana Sosial Bulanan',
    pengeluaran_koperasi: 'Pengeluaran Koperasi',
    pengeluaran_dana_sosial: 'Pengeluaran Dana Sosial',
};

export default function Index({
    saldoPinjaman,
    saldoDanaSosial,
    totalSimpanan,
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

    return (
        <AppLayout>
            <Head title="Kas Koperasi" />

            <div className="mb-6">
                <h1 className="text-2xl font-bold text-slate-800">Kas Koperasi</h1>
                <p className="text-base text-slate-400 mt-1">Saldo dan riwayat mutasi keuangan koperasi</p>
            </div>

            {/* 4 Widget Ringkasan */}
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div className="bg-white rounded-2xl border border-slate-100 p-5">
                    <div className="w-10 h-10 rounded-xl bg-brand-green-light text-brand-green-dark flex items-center justify-center mb-3">
                        <Wallet size={20} />
                    </div>
                    <p className="text-sm text-slate-400">Saldo Dana Pinjaman</p>
                    <p className="text-xl font-bold text-slate-800 mt-0.5">{formatRupiah(saldoPinjaman)}</p>
                </div>

                <div className="bg-white rounded-2xl border border-slate-100 p-5">
                    <div className="w-10 h-10 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center mb-3">
                        <HeartHandshake size={20} />
                    </div>
                    <p className="text-sm text-slate-400">Saldo Dana Sosial</p>
                    <p className="text-xl font-bold text-slate-800 mt-0.5">{formatRupiah(saldoDanaSosial)}</p>
                </div>

                <div className="bg-white rounded-2xl border border-slate-100 p-5">
                    <div className="w-10 h-10 rounded-xl bg-brand-navy/5 text-brand-navy flex items-center justify-center mb-3">
                        <PiggyBank size={20} />
                    </div>
                    <p className="text-sm text-slate-400">Total Simpanan Anggota</p>
                    <p className="text-xl font-bold text-slate-800 mt-0.5">{formatRupiah(totalSimpanan)}</p>
                    <p className="text-xs text-slate-300 mt-1">Hak anggota, bukan dana operasional</p>
                </div>

                <div className="bg-brand-navy rounded-2xl p-5 text-white">
                    <div className="w-10 h-10 rounded-xl bg-white/10 text-brand-green flex items-center justify-center mb-3">
                        <Landmark size={20} />
                    </div>
                    <p className="text-sm text-slate-300">Total Keseluruhan</p>
                    <p className="text-xl font-bold mt-0.5">{formatRupiah(totalKeseluruhan)}</p>
                </div>
            </div>

            {/* Tab kantong + tombol topup */}
            <div className="flex items-center justify-between flex-wrap gap-4 mb-5">
                <div className="flex items-center gap-2 bg-slate-100 p-1 rounded-xl w-fit">
                    <button
                        onClick={() => pindahTab('pinjaman')}
                        className={`px-5 py-2 text-sm font-semibold rounded-lg transition-colors ${
                            kantongAktif === 'pinjaman' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500'
                        }`}
                    >
                        Riwayat Dana Pinjaman
                    </button>
                    <button
                        onClick={() => pindahTab('dana_sosial')}
                        className={`px-5 py-2 text-sm font-semibold rounded-lg transition-colors ${
                            kantongAktif === 'dana_sosial' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500'
                        }`}
                    >
                        Riwayat Dana Sosial
                    </button>
                </div>

                {bisaTopup && (
                    <Button variant="primary" onClick={bukaForm}>
                        <Plus size={18} />
                        Topup {kantongAktif === 'pinjaman' ? 'Dana Pinjaman' : 'Dana Sosial'}
                    </Button>
                )}
            </div>

            {showForm && (
                <Card className="mb-5">
                    <form onSubmit={submit} className="flex flex-wrap items-end gap-3">
                        <div>
                            <label className="block text-sm font-semibold text-slate-600 mb-1.5">Jumlah</label>
                            <input
                                type="number"
                                value={data.jumlah}
                                onChange={(e) => setData('jumlah', e.target.value)}
                                className="w-40 px-3 py-2.5 text-base rounded-xl border border-slate-300 bg-white focus:border-brand-green outline-none"
                                autoFocus
                            />
                            {errors.jumlah && <p className="text-xs text-red-600 mt-1">{errors.jumlah}</p>}
                        </div>
                        <div className="flex-1 min-w-[220px]">
                            <label className="block text-sm font-semibold text-slate-600 mb-1.5">Keterangan</label>
                            <input
                                type="text"
                                value={data.keterangan}
                                onChange={(e) => setData('keterangan', e.target.value)}
                                placeholder="Contoh: Topup bulan Agustus dari keuntungan"
                                className="w-full px-3 py-2.5 text-base rounded-xl border border-slate-300 bg-white focus:border-brand-green outline-none"
                            />
                        </div>
                        <Button type="submit" variant="primary" disabled={processing}>
                            {processing ? 'Menyimpan...' : 'Simpan'}
                        </Button>
                    </form>
                </Card>
            )}

            {/* Filter periode + ringkasan arus kas */}
            <div className="flex items-center justify-between flex-wrap gap-4 mb-5">
                <input
                    type="month"
                    value={bulanFilter}
                    onChange={(e) => ubahBulan(e.target.value)}
                    className="px-4 py-2.5 text-base rounded-xl border border-slate-300 bg-white focus:border-brand-green outline-none"
                />

                <div className="flex items-center gap-4 text-sm">
                    <span className="text-slate-500">
                        Masuk: <span className="font-bold text-brand-green">+{formatRupiah(ringkasanPeriode.total_masuk)}</span>
                    </span>
                    <span className="text-slate-500">
                        Keluar: <span className="font-bold text-red-600">-{formatRupiah(ringkasanPeriode.total_keluar)}</span>
                    </span>
                </div>
            </div>

            <Card padding="none">
                {riwayat.data.length === 0 ? (
                    <p className="text-base text-slate-400 text-center py-10">
                        Belum ada riwayat mutasi di kantong ini untuk periode yang dipilih.
                    </p>
                ) : (
                    <div className="divide-y divide-slate-50">
                        {riwayat.data.map((r) => (
                            <div key={r.id} className="flex items-center justify-between px-5 py-4">
                                <div className="flex items-center gap-3">
                                    {r.tipe === 'masuk' ? (
                                        <ArrowDownCircle size={22} className="text-brand-green shrink-0" />
                                    ) : (
                                        <ArrowUpCircle size={22} className="text-red-500 shrink-0" />
                                    )}
                                    <div>
                                        <p className="text-base font-semibold text-slate-700">
                                            {kategoriLabel[r.kategori] ?? r.kategori}
                                        </p>
                                        <p className="text-sm text-slate-400">
                                            {r.tanggal}{r.keterangan ? ` \u2022 ${r.keterangan}` : ''}
                                        </p>
                                    </div>
                                </div>
                                <div className="text-right">
                                    <p className={`text-base font-bold ${r.tipe === 'masuk' ? 'text-brand-green' : 'text-red-600'}`}>
                                        {r.tipe === 'masuk' ? '+' : '-'} {formatRupiah(r.jumlah)}
                                    </p>
                                    <p className="text-xs text-slate-400">Saldo: {formatRupiah(r.saldo_setelah)}</p>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </Card>

            {riwayat.links && riwayat.links.length > 3 && (
                <div className="flex items-center justify-center gap-1.5 mt-5">
                    {riwayat.links.map((link, i) => (
                        <button
                            key={i}
                            disabled={!link.url}
                            onClick={() => link.url && router.get(link.url, {}, { preserveState: true })}
                            className={`px-3.5 py-2 text-sm font-semibold rounded-lg transition-colors ${
                                link.active
                                    ? 'bg-brand-green text-white'
                                    : link.url
                                    ? 'text-slate-600 hover:bg-slate-100'
                                    : 'text-slate-300 cursor-not-allowed'
                            }`}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ))}
                </div>
            )}
        </AppLayout>
    );
}