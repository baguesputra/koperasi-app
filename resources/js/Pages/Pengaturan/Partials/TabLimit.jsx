import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Pencil, Check, X } from 'lucide-react';

function formatRupiah(angka) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka);
}

export default function TabLimit({ limitPinjaman }) {
    const [editId, setEditId] = useState(null);
    const { data, setData, put, processing } = useForm({ limit_maksimal: '' });

    function mulaiEdit(item) {
        setEditId(item.id);
        setData('limit_maksimal', item.limit_maksimal);
    }

    function simpan(id) {
        put(route('pengaturan.limit.update', id), { onSuccess: () => setEditId(null), preserveScroll: true });
    }

    return (
        <div className="divide-y divide-slate-100">
            {limitPinjaman.map((item) => (
                <div key={item.id} className="flex items-center justify-between py-3.5 first:pt-0 last:pb-0">
                    <p className="text-base text-slate-700">{item.label}</p>
                    {editId === item.id ? (
                        <div className="flex items-center gap-2">
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
                        <div className="flex items-center gap-3">
                            <span className="text-base font-bold text-slate-800">{formatRupiah(item.limit_maksimal)}</span>
                            <button onClick={() => mulaiEdit(item)} className="text-slate-400 hover:text-brand-navy">
                                <Pencil size={16} />
                            </button>
                        </div>
                    )}
                </div>
            ))}
        </div>
    );
}