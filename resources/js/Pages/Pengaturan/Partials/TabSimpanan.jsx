import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Pencil, Check, X } from 'lucide-react';
import { formatRupiah } from '@/Utils/formatCurrency';
import TextField from '@/Components/ui/TextField';

export default function TabSimpanan({ settingSimpanan }) {
    const [editId, setEditId] = useState(null);
    const { data, setData, post, processing, errors } = useForm({ nominal: '' });

    function mulaiEdit(item) {
        setEditId(item.id);
        setData('nominal', item.nominal);
    }

    function batal() {
        setEditId(null);
    }

    function simpan(id) {
        post(route('pengaturan.simpanan.update', id), { onSuccess: () => setEditId(null), preserveScroll: true });
    }

    return (
        <div>
            <p className="text-sm text-slate-400 mb-4">
                Simpanan Pokok otomatis tercatat saat anggota baru ditambahkan. Simpanan Wajib &amp; Dana Sosial dipakai saat konfirmasi simpanan bulanan.
            </p>
            <ul className="divide-y divide-slate-100">
                {settingSimpanan.map((item) => (
                    <li key={item.id} className="flex items-center justify-between gap-3 py-3.5 first:pt-0 last:pb-0">
                        <p className="text-sm text-slate-700 min-w-0">{item.label}</p>
                        {editId === item.id ? (
                            <div className="shrink-0">
                                <div className="flex items-center gap-1.5">
                                    <TextField
                                        size="sm"
                                        type="number"
                                        min="0"
                                        value={data.nominal}
                                        onChange={(e) => setData('nominal', e.target.value)}
                                        aria-label={`Nominal ${item.label}`}
                                        className="w-36 text-right tabular-nums"
                                        autoFocus
                                    />
                                    <button onClick={() => simpan(item.id)} disabled={processing} aria-label="Simpan" title="Simpan" className="min-h-[40px] min-w-[40px] inline-flex items-center justify-center rounded-lg text-brand-green-dark bg-brand-green-light hover:shadow-sm transition-all">
                                        <Check size={16} />
                                    </button>
                                    <button onClick={batal} aria-label="Batal" title="Batal" className="min-h-[40px] min-w-[40px] inline-flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors">
                                        <X size={16} />
                                    </button>
                                </div>
                                {errors.nominal && <p className="text-xs text-red-600 mt-1 text-right">{errors.nominal}</p>}
                            </div>
                        ) : (
                            <div className="flex items-center gap-2 shrink-0">
                                <span className="text-sm font-bold text-slate-800 tabular-nums">{formatRupiah(item.nominal)}</span>
                                <button onClick={() => mulaiEdit(item)} aria-label={`Ubah ${item.label}`} title="Ubah" className="min-h-[40px] min-w-[40px] inline-flex items-center justify-center rounded-lg text-slate-400 hover:text-brand-navy hover:bg-slate-100 transition-colors">
                                    <Pencil size={15} />
                                </button>
                            </div>
                        )}
                    </li>
                ))}
            </ul>
        </div>
    );
}
