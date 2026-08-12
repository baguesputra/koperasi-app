import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Pencil, Check, X } from 'lucide-react';
import { formatRupiah } from '@/Utils/formatCurrency';

export default function TabSimpanan({ settingSimpanan }) {
    const [editId, setEditId] = useState(null);
    const { data, setData, post, processing } = useForm({ nominal: '' });

    function mulaiEdit(item) {
        setEditId(item.id);
        setData('nominal', item.nominal);
    }

    function simpan(id) {
        post(route('pengaturan.simpanan.update', id), { onSuccess: () => setEditId(null), preserveScroll: true });
    }

    return (
        <div>
            <p className="text-sm text-slate-400 mb-4">
                Simpanan Pokok otomatis tercatat saat anggota baru ditambahkan. Simpanan Wajib &amp; Dana Sosial dipakai saat konfirmasi simpanan bulanan.
            </p>
            <div className="divide-y divide-slate-100">
                {settingSimpanan.map((item) => (
                    <div key={item.id} className="flex items-center justify-between py-3.5 first:pt-0 last:pb-0">
                        <p className="text-base text-slate-700">{item.label}</p>
                        {editId === item.id ? (
                            <div className="flex items-center gap-2">
                                <input
                                    type="number"
                                    value={data.nominal}
                                    onChange={(e) => setData('nominal', e.target.value)}
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
                                <span className="text-base font-bold text-slate-800">{formatRupiah(item.nominal)}</span>
                                <button onClick={() => mulaiEdit(item)} className="text-slate-400 hover:text-brand-navy">
                                    <Pencil size={16} />
                                </button>
                            </div>
                        )}
                    </div>
                ))}
            </div>
        </div>
    );
}