import { useForm, router } from '@inertiajs/react';
import { useState } from 'react';
import { Plus, Trash2 } from 'lucide-react';
import Button from '@/Components/ui/Button';
import TextField from '@/Components/ui/TextField';
import { formatRupiah } from '@/Utils/formatCurrency';

export default function TabTenor({ tabelTenor }) {
    const [showForm, setShowForm] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({
        nominal_min: '', nominal_max: '', tenor_maksimal_bulan: '',
    });

    function submit(e) {
        e.preventDefault();
        post(route('pengaturan.tenor.store'), {
            onSuccess: () => { reset(); setShowForm(false); },
        });
    }

    function hapus(id) {
        if (confirm('Hapus rentang tenor ini?')) {
            router.delete(route('pengaturan.tenor.destroy', id), { preserveScroll: true });
        }
    }

    return (
        <div>
            <div className="flex items-center justify-between mb-4">
                <p className="text-sm text-slate-400">Batas tenor maksimal berdasarkan rentang nominal pinjaman</p>
                <Button variant="outline" size="sm" onClick={() => setShowForm(!showForm)}>
                    <Plus size={16} />
                    Tambah
                </Button>
            </div>

            {showForm && (
                <form onSubmit={submit} className="mb-4 p-4 bg-slate-50 rounded-xl flex flex-wrap items-end gap-3">
                    <div>
                        <label className="block text-sm font-semibold text-slate-600 mb-1.5">Nominal Min</label>
                        <TextField size="sm" type="number" value={data.nominal_min} onChange={(e) => setData('nominal_min', e.target.value)} className="w-36" />
                        {errors.nominal_min && <p className="text-xs text-red-600 mt-1">{errors.nominal_min}</p>}
                    </div>
                    <div>
                        <label className="block text-sm font-semibold text-slate-600 mb-1.5">Nominal Max</label>
                        <TextField size="sm" type="number" value={data.nominal_max} onChange={(e) => setData('nominal_max', e.target.value)} className="w-36" />
                        {errors.nominal_max && <p className="text-xs text-red-600 mt-1">{errors.nominal_max}</p>}
                    </div>
                    <div>
                        <label className="block text-sm font-semibold text-slate-600 mb-1.5">Tenor Maks. (bulan)</label>
                        <TextField size="sm" type="number" value={data.tenor_maksimal_bulan} onChange={(e) => setData('tenor_maksimal_bulan', e.target.value)} className="w-32" />
                        {errors.tenor_maksimal_bulan && <p className="text-xs text-red-600 mt-1">{errors.tenor_maksimal_bulan}</p>}
                    </div>
                    <Button type="submit" variant="primary" size="sm" disabled={processing}>Simpan</Button>
                </form>
            )}

            <div className="divide-y divide-slate-100">
                {tabelTenor.map((item) => (
                    <div key={item.id} className="flex items-center justify-between py-3 first:pt-0 last:pb-0">
                        <p className="text-base text-slate-700">
                            {formatRupiah(item.nominal_min)} &mdash; {formatRupiah(item.nominal_max)}
                        </p>
                        <div className="flex items-center gap-3">
                            <span className="text-base font-semibold text-slate-800">{item.tenor_maksimal_bulan} bulan</span>
                            <button onClick={() => hapus(item.id)} className="text-slate-400 hover:text-red-600">
                                <Trash2 size={16} />
                            </button>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}