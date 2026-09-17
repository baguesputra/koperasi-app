import { useForm, router } from '@inertiajs/react';
import { useState } from 'react';
import { Plus, Trash2 } from 'lucide-react';
import Button from '@/Components/ui/Button';
import FormField from '@/Components/ui/FormField';
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
            <div className="flex items-center justify-between gap-2 mb-4">
                <p className="text-sm text-slate-400">Batas tenor maksimal berdasarkan rentang nominal pinjaman</p>
                <Button variant="outline" size="sm" className="rounded-full shrink-0" onClick={() => setShowForm(!showForm)}>
                    <Plus size={16} />
                    Tambah
                </Button>
            </div>

            {showForm && (
                <form onSubmit={submit} className="mb-4 p-4 bg-slate-50/80 border border-slate-100 rounded-2xl flex flex-wrap items-end gap-3">
                    <div className="w-36">
                        <FormField label="Nominal Min" error={errors.nominal_min} required>
                            <TextField size="sm" type="number" min="0" value={data.nominal_min} onChange={(e) => setData('nominal_min', e.target.value)} required className="tabular-nums" />
                        </FormField>
                    </div>
                    <div className="w-36">
                        <FormField label="Nominal Max" error={errors.nominal_max} required>
                            <TextField size="sm" type="number" min="0" value={data.nominal_max} onChange={(e) => setData('nominal_max', e.target.value)} required className="tabular-nums" />
                        </FormField>
                    </div>
                    <div className="w-32">
                        <FormField label="Tenor Maks (bln)" error={errors.tenor_maksimal_bulan} required>
                            <TextField size="sm" type="number" min="1" value={data.tenor_maksimal_bulan} onChange={(e) => setData('tenor_maksimal_bulan', e.target.value)} required className="tabular-nums" />
                        </FormField>
                    </div>
                    <div className="pb-4 flex items-center gap-2">
                        <Button type="submit" size="sm" disabled={processing} className="rounded-full shadow-md shadow-brand-green/25">
                            {processing ? 'Menyimpan...' : 'Simpan'}
                        </Button>
                        <Button type="button" variant="ghost" size="sm" className="rounded-full" onClick={() => setShowForm(false)}>
                            Batal
                        </Button>
                    </div>
                </form>
            )}

            {tabelTenor.length === 0 ? (
                <p className="text-sm text-slate-400 text-center py-8">Belum ada rentang tenor.</p>
            ) : (
                <ul className="divide-y divide-slate-100">
                    {tabelTenor.map((item) => (
                        <li key={item.id} className="flex items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                            <p className="text-sm text-slate-700 tabular-nums">
                                {formatRupiah(item.nominal_min)} &mdash; {formatRupiah(item.nominal_max)}
                            </p>
                            <div className="flex items-center gap-2 shrink-0">
                                <span className="text-sm font-bold text-slate-800 tabular-nums">{item.tenor_maksimal_bulan} bln</span>
                                <button onClick={() => hapus(item.id)} aria-label={`Hapus rentang ${item.tenor_maksimal_bulan} bulan`} title="Hapus" className="w-8 h-8 inline-flex items-center justify-center rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition-colors">
                                    <Trash2 size={15} />
                                </button>
                            </div>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
