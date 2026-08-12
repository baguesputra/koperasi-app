import AppLayout from '@/Layouts/AppLayout';
import { Head, usePage, useForm } from '@inertiajs/react';
import { Wallet, ArrowDownCircle, ArrowUpCircle, Plus } from 'lucide-react';
import { useState } from 'react';
import Card from '@/Components/ui/Card';
import Button from '@/Components/ui/Button';
import { formatRupiah } from '@/Utils/formatCurrency';

const kategoriLabel = {
    topup_bulanan: 'Topup Saldo',
    pencairan_pinjaman: 'Pencairan Pinjaman',
    pembayaran_angsuran: 'Pembayaran Angsuran',
};

// Ambang batas peringatan - bisa disesuaikan nanti
const BATAS_PERINGATAN = 10_000_000;

export default function Index({ saldoSaatIni, riwayat }) {
    const { auth } = usePage().props;
    const bisaTopup = auth.user?.roles?.includes('bendahara');
    const [showForm, setShowForm] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        jumlah: '',
        keterangan: '',
    });

    function submit(e) {
        e.preventDefault();
        post(route('kas-koperasi.topup'), {
            onSuccess: () => {
                reset();
                setShowForm(false);
            },
        });
    }

    const saldoRendah = saldoSaatIni < BATAS_PERINGATAN;

    return (
        <AppLayout>
            <Head title="Kas Koperasi" />

            <div className="mb-6">
                <h1 className="text-2xl font-bold text-slate-800">Kas Koperasi</h1>
                <p className="text-base text-slate-400 mt-1">Saldo dana pinjaman dan riwayat mutasi</p>
            </div>

            {/* Kartu Saldo */}
            <Card className={`mb-5 ${saldoRendah ? 'border-amber-200 bg-amber-50' : ''}`}>
                <div className="flex items-center justify-between flex-wrap gap-4">
                    <div className="flex items-center gap-4">
                        <div className={`w-14 h-14 rounded-2xl flex items-center justify-center ${
                            saldoRendah ? 'bg-amber-100 text-amber-700' : 'bg-brand-green-light text-brand-green-dark'
                        }`}>
                            <Wallet size={28} />
                        </div>
                        <div>
                            <p className="text-sm text-slate-500">Saldo Saat Ini</p>
                            <p className="text-3xl font-bold text-slate-800">{formatRupiah(saldoSaatIni)}</p>
                            {saldoRendah && (
                                <p className="text-sm font-semibold text-amber-700 mt-1">
                                    Saldo di bawah batas aman, pertimbangkan topup segera.
                                </p>
                            )}
                        </div>
                    </div>

                    {bisaTopup && (
                        <Button variant="primary" onClick={() => setShowForm(!showForm)}>
                            <Plus size={18} />
                            Topup Saldo
                        </Button>
                    )}
                </div>

                {showForm && (
                    <form onSubmit={submit} className="mt-5 pt-5 border-t border-slate-200 flex flex-wrap items-end gap-3">
                        <div>
                            <label className="block text-sm font-semibold text-slate-600 mb-1.5">Jumlah Topup</label>
                            <input
                                type="number"
                                value={data.jumlah}
                                onChange={(e) => setData('jumlah', e.target.value)}
                                placeholder="0"
                                className="w-48 px-4 py-2.5 text-base rounded-xl border border-slate-300 bg-white focus:border-brand-green outline-none"
                                autoFocus
                            />
                            {errors.jumlah && <p className="text-sm text-red-600 mt-1">{errors.jumlah}</p>}
                        </div>
                        <div className="flex-1 min-w-[200px]">
                            <label className="block text-sm font-semibold text-slate-600 mb-1.5">Keterangan (opsional)</label>
                            <input
                                type="text"
                                value={data.keterangan}
                                onChange={(e) => setData('keterangan', e.target.value)}
                                placeholder="Contoh: Topup bulan Agustus dari keuntungan"
                                className="w-full px-4 py-2.5 text-base rounded-xl border border-slate-300 bg-white focus:border-brand-green outline-none"
                            />
                        </div>
                        <Button type="submit" variant="primary" disabled={processing}>
                            {processing ? 'Menyimpan...' : 'Simpan'}
                        </Button>
                    </form>
                )}
            </Card>

            {/* Riwayat Mutasi */}
            <Card padding="none">
                <div className="p-5 border-b border-slate-100">
                    <h2 className="text-lg font-bold text-slate-800">Riwayat Mutasi</h2>
                </div>

                {riwayat.length === 0 ? (
                    <p className="text-base text-slate-400 text-center py-10">Belum ada riwayat mutasi.</p>
                ) : (
                    <div className="divide-y divide-slate-50">
                        {riwayat.map((r) => (
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
                                <p className={`text-base font-bold ${r.tipe === 'masuk' ? 'text-brand-green' : 'text-red-600'}`}>
                                    {r.tipe === 'masuk' ? '+' : '-'} {formatRupiah(r.jumlah)}
                                </p>
                            </div>
                        ))}
                    </div>
                )}
            </Card>
        </AppLayout>
    );
}