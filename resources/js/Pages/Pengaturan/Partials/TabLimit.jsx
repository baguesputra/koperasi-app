import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Pencil, Check, X, Plus, Trash2 } from 'lucide-react';
import { formatRupiah } from '@/Utils/formatCurrency';
import TextField from '@/Components/ui/TextField';
import Button from '@/Components/ui/Button';

function teksRentang(item) {
    const min = item.min_bulan ?? 0;
    if (item.maks_bulan === null || item.maks_bulan === undefined) {
        return `≥ ${min} bln`;
    }
    if (min === item.maks_bulan) {
        return `${min} bln`;
    }
    return `${min}–${item.maks_bulan} bln`;
}

function FormRentang({ awal, onSimpan, onBatal }) {
    const { data, setData, put, post, processing, errors } = useForm({
        label: awal?.label ?? '',
        min_bulan: awal?.min_bulan ?? '',
        maks_bulan: awal?.maks_bulan ?? '',
        limit_maksimal: awal?.limit_maksimal ?? '',
    });

    function simpan() {
        const payload = {
            ...data,
            maks_bulan: data.maks_bulan === '' ? null : data.maks_bulan,
        };
        const options = { preserveScroll: true, onSuccess: onSimpan };
        if (awal?.id) {
            put(route('pengaturan.limit.update', awal.id), payload, options);
        } else {
            post(route('pengaturan.limit.store'), payload, options);
        }
    }

    return (
        <div className="mt-2 rounded-xl border border-slate-200 bg-slate-50/60 p-3 space-y-2.5">
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                <label className="block">
                    <span className="block text-xs font-semibold text-slate-500 mb-1">Label</span>
                    <TextField
                        size="sm"
                        value={data.label}
                        onChange={(e) => setData('label', e.target.value)}
                        placeholder="Anggota 1-3 Tahun"
                        className="w-full"
                        autoFocus
                    />
                    {errors.label && <p className="text-xs text-red-600 mt-1">{errors.label}</p>}
                </label>
                <label className="block">
                    <span className="block text-xs font-semibold text-slate-500 mb-1">Limit Maksimal (Rp)</span>
                    <TextField
                        size="sm"
                        type="number"
                        min="0"
                        value={data.limit_maksimal}
                        onChange={(e) => setData('limit_maksimal', e.target.value)}
                        placeholder="5000000"
                        className="w-full text-right tabular-nums"
                    />
                    {errors.limit_maksimal && <p className="text-xs text-red-600 mt-1">{errors.limit_maksimal}</p>}
                </label>
                <label className="block">
                    <span className="block text-xs font-semibold text-slate-500 mb-1">Masa Min (bulan)</span>
                    <TextField
                        size="sm"
                        type="number"
                        min="0"
                        value={data.min_bulan}
                        onChange={(e) => setData('min_bulan', e.target.value)}
                        placeholder="12"
                        className="w-full text-right tabular-nums"
                    />
                    {errors.min_bulan && <p className="text-xs text-red-600 mt-1">{errors.min_bulan}</p>}
                </label>
                <label className="block">
                    <span className="block text-xs font-semibold text-slate-500 mb-1">Masa Maks (bulan, kosong = tanpa batas)</span>
                    <TextField
                        size="sm"
                        type="number"
                        min="0"
                        value={data.maks_bulan ?? ''}
                        onChange={(e) => setData('maks_bulan', e.target.value)}
                        placeholder="35"
                        className="w-full text-right tabular-nums"
                    />
                    {errors.maks_bulan && <p className="text-xs text-red-600 mt-1">{errors.maks_bulan}</p>}
                </label>
            </div>
            <div className="flex items-center gap-1.5">
                <button onClick={simpan} disabled={processing} aria-label="Simpan" title="Simpan" className="min-h-[40px] min-w-[40px] inline-flex items-center justify-center rounded-lg text-brand-green-dark bg-brand-green-light hover:shadow-sm transition-all">
                    <Check size={16} />
                </button>
                <button onClick={onBatal} aria-label="Batal" title="Batal" className="min-h-[40px] min-w-[40px] inline-flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors">
                    <X size={16} />
                </button>
            </div>
        </div>
    );
}

export default function TabLimit({ limitPinjaman }) {
    const [editId, setEditId] = useState(null);
    const [tambah, setTambah] = useState(false);
    const hapusForm = useForm({});

    function hapus(id) {
        if (!window.confirm('Hapus rentang limit ini? Anggota pada rentang tersebut memakai baris lain atau batas default.')) {
            return;
        }
        hapusForm.delete(route('pengaturan.limit.destroy', id), { preserveScroll: true });
    }

    return (
        <div>
            <p className="text-sm text-slate-400 mb-4">
                Batas nominal pinjaman per masa keanggotaan (bulan). Rentang tidak boleh bertabrakan; kosongkan
                batas atas bila tanpa batas. Kosongkan nominal bila kategori tidak dipakai.
            </p>
            <ul className="divide-y divide-slate-100">
                {limitPinjaman.map((item) => (
                    <li key={item.id} className="py-3.5 first:pt-0 last:pb-0">
                        <div className="flex items-center justify-between gap-3">
                            <div className="min-w-0">
                                <p className="text-sm text-slate-700 truncate">{item.label}</p>
                                <p className="text-xs text-slate-400 tabular-nums">Masa {teksRentang(item)}</p>
                            </div>
                            {editId === item.id ? null : (
                                <div className="flex items-center gap-2 shrink-0">
                                    <span className="text-sm font-bold text-slate-800 tabular-nums">{formatRupiah(item.limit_maksimal)}</span>
                                    <button onClick={() => { setEditId(item.id); setTambah(false); }} aria-label={`Ubah ${item.label}`} title="Ubah" className="min-h-[40px] min-w-[40px] inline-flex items-center justify-center rounded-lg text-slate-400 hover:text-brand-navy hover:bg-slate-100 transition-colors">
                                        <Pencil size={15} />
                                    </button>
                                    <button onClick={() => hapus(item.id)} aria-label={`Hapus ${item.label}`} title="Hapus" className="min-h-[40px] min-w-[40px] inline-flex items-center justify-center rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition-colors">
                                        <Trash2 size={15} />
                                    </button>
                                </div>
                            )}
                        </div>
                        {editId === item.id && (
                            <FormRentang awal={item} onSimpan={() => setEditId(null)} onBatal={() => setEditId(null)} />
                        )}
                    </li>
                ))}
            </ul>
            {tambah ? (
                <FormRentang awal={null} onSimpan={() => setTambah(false)} onBatal={() => setTambah(false)} />
            ) : (
                <Button type="button" variant="outline" size="sm" className="mt-3 rounded-full" onClick={() => { setTambah(true); setEditId(null); }}>
                    <Plus size={15} />
                    Tambah Rentang
                </Button>
            )}
        </div>
    );
}
