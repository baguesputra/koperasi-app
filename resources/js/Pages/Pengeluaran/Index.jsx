import AppLayout from '@/Layouts/AppLayout';
import { Head, router, usePage, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Plus, Wallet, HeartHandshake } from 'lucide-react';
import Card from '@/Components/ui/Card';
import Button from '@/Components/ui/Button';
import Pagination from '@/Components/ui/Pagination';

function formatRupiah(angka) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka);
}

export default function Index({ pengeluaran, jenisAktif, totalKoperasi, totalDanaSosial }) {
    const { auth } = usePage().props;
    const bisaCatat = auth.user?.permissions?.includes('kas.topup');
    const [showForm, setShowForm] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        jenis: jenisAktif,
        jumlah: '',
        keterangan: '',
        tanggal: new Date().toISOString().slice(0, 10),
    });

    function pindahTab(jenis) {
        router.get(route('pengeluaran.index'), { jenis }, { preserveState: true });
    }

    function submit(e) {
        e.preventDefault();
        post(route('pengeluaran.store'), {
            onSuccess: () => { reset('jumlah', 'keterangan'); setShowForm(false); },
        });
    }

    return (
        <AppLayout>
            <Head title="Pengeluaran" />

            <div className="mb-6">
                <h1 className="text-2xl font-bold text-slate-800">Pengeluaran</h1>
                <p className="text-base text-slate-400 mt-1">Catatan pengeluaran koperasi dan dana sosial</p>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                <div className="bg-white rounded-2xl border border-slate-100 p-5">
                    <div className="w-10 h-10 rounded-xl bg-brand-green-light text-brand-green-dark flex items-center justify-center mb-3">
                        <Wallet size={20} />
                    </div>
                    <p className="text-sm text-slate-400">Total Pengeluaran Koperasi</p>
                    <p className="text-xl font-bold text-slate-800 mt-0.5">{formatRupiah(totalKoperasi)}</p>
                </div>
                <div className="bg-white rounded-2xl border border-slate-100 p-5">
                    <div className="w-10 h-10 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center mb-3">
                        <HeartHandshake size={20} />
                    </div>
                    <p className="text-sm text-slate-400">Total Pengeluaran Dana Sosial</p>
                    <p className="text-xl font-bold text-slate-800 mt-0.5">{formatRupiah(totalDanaSosial)}</p>
                </div>
            </div>

            <div className="flex items-center justify-between flex-wrap gap-4 mb-5">
                <div className="flex items-center gap-2 bg-slate-100 p-1 rounded-xl w-fit">
                    <button
                        onClick={() => pindahTab('koperasi')}
                        className={`px-5 py-2 text-sm font-semibold rounded-lg transition-colors ${
                            jenisAktif === 'koperasi' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500'
                        }`}
                    >
                        Pengeluaran Koperasi
                    </button>
                    <button
                        onClick={() => pindahTab('dana_sosial')}
                        className={`px-5 py-2 text-sm font-semibold rounded-lg transition-colors ${
                            jenisAktif === 'dana_sosial' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500'
                        }`}
                    >
                        Pengeluaran Dana Sosial
                    </button>
                </div>

                {bisaCatat && (
                    <Button variant="primary" onClick={() => { setData('jenis', jenisAktif); setShowForm(!showForm); }}>
                        <Plus size={18} />
                        Catat Pengeluaran
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
                        <div>
                            <label className="block text-sm font-semibold text-slate-600 mb-1.5">Tanggal</label>
                            <input
                                type="date"
                                value={data.tanggal}
                                onChange={(e) => setData('tanggal', e.target.value)}
                                className="px-3 py-2.5 text-base rounded-xl border border-slate-300 bg-white focus:border-brand-green outline-none"
                            />
                        </div>
                        <div className="flex-1 min-w-[220px]">
                            <label className="block text-sm font-semibold text-slate-600 mb-1.5">Keterangan</label>
                            <input
                                type="text"
                                value={data.keterangan}
                                onChange={(e) => setData('keterangan', e.target.value)}
                                placeholder="Contoh: Biaya ATK dan operasional kantor"
                                className="w-full px-3 py-2.5 text-base rounded-xl border border-slate-300 bg-white focus:border-brand-green outline-none"
                            />
                            {errors.keterangan && <p className="text-xs text-red-600 mt-1">{errors.keterangan}</p>}
                        </div>
                        <Button type="submit" variant="primary" disabled={processing}>
                            {processing ? 'Menyimpan...' : 'Simpan'}
                        </Button>
                    </form>
                </Card>
            )}

            <Card padding="none">
                <div className="overflow-x-auto">
                    <table className="w-full table-sticky-first">
                        <thead>
                            <tr className="border-b border-slate-100 text-left">
                                <th className="px-5 py-3.5 text-sm font-semibold text-slate-500">Tanggal</th>
                                <th className="px-5 py-3.5 text-sm font-semibold text-slate-500">Keterangan</th>
                                <th className="px-5 py-3.5 text-sm font-semibold text-slate-500">Dicatat Oleh</th>
                                <th className="px-5 py-3.5 text-sm font-semibold text-slate-500 text-right">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            {pengeluaran.data.length === 0 ? (
                                <tr><td colSpan={4} className="px-5 py-10 text-center text-base text-slate-400">Belum ada pengeluaran tercatat.</td></tr>
                            ) : (
                                pengeluaran.data.map((p) => (
                                    <tr key={p.id} className="border-b border-slate-50 hover:bg-slate-50 transition-colors">
                                        <td className="px-5 py-3.5 text-base text-slate-600">{p.tanggal}</td>
                                        <td className="px-5 py-3.5 text-base text-slate-700">{p.keterangan}</td>
                                        <td className="px-5 py-3.5 text-base text-slate-500">{p.input_oleh}</td>
                                        <td className="px-5 py-3.5 text-base font-bold text-red-600 text-right">- {formatRupiah(p.jumlah)}</td>
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