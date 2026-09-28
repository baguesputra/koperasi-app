import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Pencil, Check, X } from 'lucide-react';
import { formatRupiah } from '@/Utils/formatCurrency';
import TextField from '@/Components/ui/TextField';

const KETERANGAN = {
    pagu_pinjaman_bulanan: 'Batas total pencairan pinjaman per bulan kalender. Pencairan ditolak bila melebihi sisa pagu.',
    cadangan_sosial_bulan: 'Dana yang wajib tersisa di kas operasional dan tidak boleh dipakai pinjaman.',
};

export default function TabKas({ settingKas }) {
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
        post(route('pengaturan.kas.update', id), { onSuccess: () => setEditId(null), preserveScroll: true });
    }

    return (
        <div>
            <p className="text-sm text-slate-400 mb-4">
                Kas operasional = gabungan Dana Pinjaman + Dana Sosial + Simpanan.
                Pencairan ditolak bila melebihi sisa pagu atau menyisakan kas di bawah cadangan sosial.
            </p>
            <ul className="divide-y divide-slate-100">
                {(settingKas ?? []).map((item) => (
                    <li key={item.id} className="py-3.5 first:pt-0 last:pb-0">
                        <div className="flex items-center justify-between gap-3">
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
                                        <button onClick={() => simpan(item.id)} disabled={processing} aria-label="Simpan" title="Simpan" className="w-8 h-8 inline-flex items-center justify-center rounded-lg text-brand-green-dark bg-brand-green-light hover:shadow-sm transition-all">
                                            <Check size={16} />
                                        </button>
                                        <button onClick={batal} aria-label="Batal" title="Batal" className="w-8 h-8 inline-flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors">
                                            <X size={16} />
                                        </button>
                                    </div>
                                    {errors.nominal && <p className="text-xs text-red-600 mt-1 text-right">{errors.nominal}</p>}
                                </div>
                            ) : (
                                <div className="flex items-center gap-2 shrink-0">
                                    <span className="text-sm font-bold text-slate-800 tabular-nums">{formatRupiah(item.nominal)}</span>
                                    <button onClick={() => mulaiEdit(item)} aria-label={`Ubah ${item.label}`} title="Ubah" className="w-8 h-8 inline-flex items-center justify-center rounded-lg text-slate-400 hover:text-brand-navy hover:bg-slate-100 transition-colors">
                                        <Pencil size={15} />
                                    </button>
                                </div>
                            )}
                        </div>
                        <p className="text-xs text-slate-400 mt-1">{KETERANGAN[item.kunci] ?? ''}</p>
                    </li>
                ))}
                {(settingKas ?? []).length === 0 && (
                    <li className="py-6 text-center text-sm text-slate-400">
                        Pengaturan kas belum tersedia. Jalankan migrasi dan seeder terlebih dahulu.
                    </li>
                )}
            </ul>
        </div>
    );
}
