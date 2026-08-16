import AppLayout from '@/Layouts/AppLayout';
import { Head, usePage, useForm } from '@inertiajs/react';
import { Wallet, HeartHandshake, ArrowDownCircle, ArrowUpCircle, Plus } from 'lucide-react';
import { useState } from 'react';
import Card from '@/Components/ui/Card';
import Button from '@/Components/ui/Button';

function formatRupiah(angka) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka);
}

const kategoriLabel = {
    topup_bulanan: 'Topup Saldo',
    pencairan_pinjaman: 'Pencairan Pinjaman',
    pembayaran_angsuran: 'Pembayaran Angsuran',
    dana_sosial_bulanan: 'Dana Sosial Bulanan',
    pengeluaran_koperasi: 'Pengeluaran Koperasi',
    pengeluaran_dana_sosial: 'Pengeluaran Dana Sosial',
};

export default function Index({ saldoPinjaman, saldoDanaSosial, riwayat }) {
    const { auth } = usePage().props;
    const bisaTopup = auth.user?.permissions?.includes('kas.topup');
    const [showForm, setShowForm] = useState(null); // 'pinjaman' | 'dana_sosial' | null

    const { data, setData, post, processing, errors, reset } = useForm({
        kantong: '', jumlah: '', keterangan: '',
    });

    function bukaForm(kantong) {
        setData('kantong', kantong);
        setShowForm(kantong);
    }

    function submit(e) {
        e.preventDefault();
        post(route('kas-koperasi.topup'), {
            onSuccess: () => { reset(); setShowForm(null); },
        });
    }

    return (
        <AppLayout>
            <Head title="Kas Koperasi" />

            <div className="mb-6">
                <h1 className="text-2xl font-bold text-slate-800">Kas Koperasi</h1>
                <p className="text-base text-slate-400 mt-1">Saldo dan riwayat mutasi keuangan koperasi</p>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-5">
                {/* Kantong Pinjaman */}
                <Card>
                    <div className="flex items-center justify-between mb-4">
                        <div className="flex items-center gap-3">
                            <div className="w-12 h-12 rounded-2xl bg-brand-green-light text-brand-green-dark flex items-center justify-center">
                                <Wallet size={24} />
                            </div>
                            <div>
                                <p className="text-sm text-slate-500">Saldo Dana Pinjaman</p>
                                <p className="text-2xl font-bold text-slate-800">{formatRupiah(saldoPinjaman)}</p>
                            </div>
                        </div>
                        {bisaTopup && (
                            <Button variant="outline" size="sm" onClick={() => bukaForm('pinjaman')}>
                                <Plus size={16} />
                                Topup
                            </Button>
                        )}
                    </div>

                    {showForm === 'pinjaman' && (
                        <form onSubmit={submit} className="pt-4 border-t border-slate-100 flex flex-wrap items-end gap-3">
                            <div>
                                <label className="block text-sm font-semibold text-slate-600 mb-1.5">Jumlah</label>
                                <input type="number" value={data.jumlah} onChange={(e) => setData('jumlah', e.target.value)} className="w-40 px-3 py-2 text-base rounded-xl border border-slate-300 bg-white focus:border-brand-green outline-none" autoFocus />
                                {errors.jumlah && <p className="text-xs text-red-600 mt-1">{errors.jumlah}</p>}
                            </div>
                            <div className="flex-1 min-w-[160px]">
                                <label className="block text-sm font-semibold text-slate-600 mb-1.5">Keterangan</label>
                                <input type="text" value={data.keterangan} onChange={(e) => setData('keterangan', e.target.value)} className="w-full px-3 py-2 text-base rounded-xl border border-slate-300 bg-white focus:border-brand-green outline-none" />
                            </div>
                            <Button type="submit" variant="primary" size="sm" disabled={processing}>Simpan</Button>
                        </form>
                    )}
                </Card>

                {/* Kantong Dana Sosial */}
                <Card>
                    <div className="flex items-center justify-between mb-4">
                        <div className="flex items-center gap-3">
                            <div className="w-12 h-12 rounded-2xl bg-amber-50 text-amber-700 flex items-center justify-center">
                                <HeartHandshake size={24} />
                            </div>
                            <div>
                                <p className="text-sm text-slate-500">Saldo Dana Sosial</p>
                                <p className="text-2xl font-bold text-slate-800">{formatRupiah(saldoDanaSosial)}</p>
                            </div>
                        </div>
                        {bisaTopup && (
                            <Button variant="outline" size="sm" onClick={() => bukaForm('dana_sosial')}>
                                <Plus size={16} />
                                Topup
                            </Button>
                        )}
                    </div>

                    {showForm === 'dana_sosial' && (
                        <form onSubmit={submit} className="pt-4 border-t border-slate-100 flex flex-wrap items-end gap-3">
                            <div>
                                <label className="block text-sm font-semibold text-slate-600 mb-1.5">Jumlah</label>
                                <input type="number" value={data.jumlah} onChange={(e) => setData('jumlah', e.target.value)} className="w-40 px-3 py-2 text-base rounded-xl border border-slate-300 bg-white focus:border-brand-green outline-none" autoFocus />
                                {errors.jumlah && <p className="text-xs text-red-600 mt-1">{errors.jumlah}</p>}
                            </div>
                            <div className="flex-1 min-w-[160px]">
                                <label className="block text-sm font-semibold text-slate-600 mb-1.5">Keterangan</label>
                                <input type="text" value={data.keterangan} onChange={(e) => setData('keterangan', e.target.value)} className="w-full px-3 py-2 text-base rounded-xl border border-slate-300 bg-white focus:border-brand-green outline-none" />
                            </div>
                            <Button type="submit" variant="primary" size="sm" disabled={processing}>Simpan</Button>
                        </form>
                    )}
                </Card>
            </div>

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
                                            {r.tanggal} &bull; {r.kantong === 'pinjaman' ? 'Kantong Pinjaman' : 'Kantong Dana Sosial'}
                                            {r.keterangan ? ` \u2022 ${r.keterangan}` : ''}
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