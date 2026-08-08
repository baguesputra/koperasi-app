import AppLayout from '@/Layouts/AppLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Pencil, Trash2, Plus, Check, X } from 'lucide-react';
import Card from '@/Components/ui/Card';
import Button from '@/Components/ui/Button';
import TextField from '@/Components/ui/TextField';

function formatRupiah(angka) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka);
}

export default function Index({ limitPinjaman, tabelTenor, bungaSaatIni }) {
    return (
        <AppLayout>
            <Head title="Pengaturan" />

            <div className="mb-6">
                <h1 className="text-2xl font-bold text-slate-800">Pengaturan</h1>
                <p className="text-base text-slate-400 mt-1">
                    Kelola limit pinjaman, tenor, dan bunga yang berlaku
                </p>
            </div>

            <div className="space-y-6">
                <SettingBunga bungaSaatIni={bungaSaatIni} />
                <SettingLimit limitPinjaman={limitPinjaman} />
                <SettingTenor tabelTenor={tabelTenor} />
            </div>
        </AppLayout>
    );
}

function SettingBunga({ bungaSaatIni }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        persentase: bungaSaatIni?.persentase ?? '',
    });

    function submit(e) {
        e.preventDefault();
        post(route('pengaturan.bunga.update'), { onSuccess: () => reset() });
    }

    return (
        <Card>
            <h2 className="text-lg font-bold text-slate-800 mb-1">Persentase Bunga</h2>
            <p className="text-sm text-slate-400 mb-4">
                Bunga dihitung menurun dari sisa pokok tiap bulan. Perubahan hanya berlaku untuk pengajuan baru, tidak memengaruhi pinjaman yang sudah berjalan.
            </p>

            <div className="flex items-center gap-3 mb-4 p-4 bg-slate-50 rounded-xl">
                <span className="text-sm text-slate-500">Saat ini berlaku:</span>
                <span className="text-lg font-bold text-brand-navy">{bungaSaatIni?.persentase}% / bulan</span>
            </div>

            <form onSubmit={submit} className="flex items-end gap-3">
                <div className="flex-1 max-w-xs">
                    <label className="block text-sm font-semibold text-slate-600 mb-1.5">
                        Persentase Baru (%)
                    </label>
                    <TextField
                        type="number"
                        step="0.01"
                        value={data.persentase}
                        onChange={(e) => setData('persentase', e.target.value)}
                        placeholder="Contoh: 1.5"
                    />
                    {errors.persentase && <p className="text-sm text-red-600 mt-1">{errors.persentase}</p>}
                </div>
                <Button type="submit" variant="primary" disabled={processing}>
                    Simpan
                </Button>
            </form>
        </Card>
    );
}

function SettingLimit({ limitPinjaman }) {
    const [editId, setEditId] = useState(null);
    const { data, setData, put, processing } = useForm({ limit_maksimal: '' });

    function mulaiEdit(item) {
        setEditId(item.id);
        setData('limit_maksimal', item.limit_maksimal);
    }

    function simpan(id) {
        put(route('pengaturan.limit.update', id), {
            onSuccess: () => setEditId(null),
            preserveScroll: true,
        });
    }

    return (
        <Card padding="none">
            <div className="p-5 border-b border-slate-100">
                <h2 className="text-lg font-bold text-slate-800">Limit Maksimal Pinjaman</h2>
                <p className="text-sm text-slate-400 mt-1">
                    Berdasarkan kategori jabatan & lama keanggotaan
                </p>
            </div>

            <table className="w-full">
                <tbody>
                    {limitPinjaman.map((item) => (
                        <tr key={item.id} className="border-b border-slate-50 last:border-0">
                            <td className="px-5 py-4 text-base text-slate-700">{item.label}</td>
                            <td className="px-5 py-4 text-right">
                                {editId === item.id ? (
                                    <div className="flex items-center justify-end gap-2">
                                        <input
                                            type="number"
                                            value={data.limit_maksimal}
                                            onChange={(e) => setData('limit_maksimal', e.target.value)}
                                            className="w-40 px-3 py-1.5 text-base rounded-lg border border-slate-300 text-right focus:border-brand-green outline-none"
                                            autoFocus
                                        />
                                        <button onClick={() => simpan(item.id)} disabled={processing} className="text-brand-green hover:text-brand-green-dark">
                                            <Check size={20} />
                                        </button>
                                        <button onClick={() => setEditId(null)} className="text-slate-400 hover:text-slate-600">
                                            <X size={20} />
                                        </button>
                                    </div>
                                ) : (
                                    <div className="flex items-center justify-end gap-3">
                                        <span className="text-base font-bold text-slate-800">
                                            {formatRupiah(item.limit_maksimal)}
                                        </span>
                                        <button onClick={() => mulaiEdit(item)} className="text-slate-400 hover:text-brand-navy">
                                            <Pencil size={16} />
                                        </button>
                                    </div>
                                )}
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </Card>
    );
}

function SettingTenor({ tabelTenor }) {
    const [showForm, setShowForm] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({
        nominal_min: '',
        nominal_max: '',
        tenor_maksimal_bulan: '',
    });

    function submit(e) {
        e.preventDefault();
        post(route('pengaturan.tenor.store'), {
            onSuccess: () => {
                reset();
                setShowForm(false);
            },
        });
    }

    function hapus(id) {
        if (confirm('Hapus rentang tenor ini?')) {
            router.delete(route('pengaturan.tenor.destroy', id), { preserveScroll: true });
        }
    }

    return (
        <Card padding="none">
            <div className="p-5 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 className="text-lg font-bold text-slate-800">Tabel Tenor</h2>
                    <p className="text-sm text-slate-400 mt-1">
                        Batas tenor maksimal berdasarkan rentang nominal pinjaman
                    </p>
                </div>
                <Button variant="outline" size="sm" onClick={() => setShowForm(!showForm)}>
                    <Plus size={16} />
                    Tambah
                </Button>
            </div>

            {showForm && (
                <form onSubmit={submit} className="p-5 bg-slate-50 border-b border-slate-100 flex flex-wrap items-end gap-3">
                    <div>
                        <label className="block text-sm font-semibold text-slate-600 mb-1.5">Nominal Min</label>
                        <input
                            type="number"
                            value={data.nominal_min}
                            onChange={(e) => setData('nominal_min', e.target.value)}
                            className="w-36 px-3 py-2 text-base rounded-lg border border-slate-300 focus:border-brand-green outline-none"
                        />
                        {errors.nominal_min && <p className="text-xs text-red-600 mt-1">{errors.nominal_min}</p>}
                    </div>
                    <div>
                        <label className="block text-sm font-semibold text-slate-600 mb-1.5">Nominal Max</label>
                        <input
                            type="number"
                            value={data.nominal_max}
                            onChange={(e) => setData('nominal_max', e.target.value)}
                            className="w-36 px-3 py-2 text-base rounded-lg border border-slate-300 focus:border-brand-green outline-none"
                        />
                        {errors.nominal_max && <p className="text-xs text-red-600 mt-1">{errors.nominal_max}</p>}
                    </div>
                    <div>
                        <label className="block text-sm font-semibold text-slate-600 mb-1.5">Tenor Maks. (bulan)</label>
                        <input
                            type="number"
                            value={data.tenor_maksimal_bulan}
                            onChange={(e) => setData('tenor_maksimal_bulan', e.target.value)}
                            className="w-32 px-3 py-2 text-base rounded-lg border border-slate-300 focus:border-brand-green outline-none"
                        />
                        {errors.tenor_maksimal_bulan && <p className="text-xs text-red-600 mt-1">{errors.tenor_maksimal_bulan}</p>}
                    </div>
                    <Button type="submit" variant="primary" size="sm" disabled={processing}>
                        Simpan
                    </Button>
                </form>
            )}

            <table className="w-full">
                <thead>
                    <tr className="border-b border-slate-100 text-left">
                        <th className="px-5 py-3 text-sm font-semibold text-slate-500">Rentang Nominal</th>
                        <th className="px-5 py-3 text-sm font-semibold text-slate-500">Tenor Maksimal</th>
                        <th className="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    {tabelTenor.map((item) => (
                        <tr key={item.id} className="border-b border-slate-50 last:border-0">
                            <td className="px-5 py-3.5 text-base text-slate-700">
                                {formatRupiah(item.nominal_min)} — {formatRupiah(item.nominal_max)}
                            </td>
                            <td className="px-5 py-3.5 text-base font-semibold text-slate-800">
                                {item.tenor_maksimal_bulan} bulan
                            </td>
                            <td className="px-5 py-3.5 text-right">
                                <button onClick={() => hapus(item.id)} className="text-slate-400 hover:text-red-600">
                                    <Trash2 size={16} />
                                </button>
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </Card>
    );
}