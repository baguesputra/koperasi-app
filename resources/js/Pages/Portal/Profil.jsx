import AnggotaLayout from '@/Layouts/AnggotaLayout';
import { Head, useForm, router } from '@inertiajs/react';
import { useState } from 'react';
import {
    User, Building2, Briefcase, Calendar, Mail,
    CreditCard, Plus, Star, Trash2,
} from 'lucide-react';

const jabatanLabel = { staff: 'Staff', hod: 'HOD' };

export default function Profil({ anggota, rekening }) {
    const [showForm, setShowForm] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({
        nama_bank: '', no_rekening: '', atas_nama: '',
    });

    function submit(e) {
        e.preventDefault();
        post(route('portal.profil.rekening.store'), {
            onSuccess: () => { reset(); setShowForm(false); },
        });
    }

    function jadikanDefault(id) {
        router.put(route('portal.profil.rekening.default', id), {}, { preserveScroll: true });
    }

    function hapus(id) {
        if (confirm('Hapus rekening ini?')) {
            router.delete(route('portal.profil.rekening.destroy', id), { preserveScroll: true });
        }
    }

    const dataDiri = [
        { icon: User, label: 'Nama Lengkap', value: anggota.nama },
        { icon: Mail, label: 'Email', value: anggota.email },
        { icon: Building2, label: 'Cabang', value: anggota.cabang },
        { icon: Briefcase, label: 'Unit Bisnis & Jabatan', value: `${anggota.unit_bisnis} \u2022 ${jabatanLabel[anggota.jabatan]}` },
        { icon: Calendar, label: 'Tanggal Mulai Kerja', value: anggota.tanggal_mulai_kerja },
        { icon: Calendar, label: 'Tanggal Jadi Anggota', value: anggota.tanggal_jadi_anggota },
    ];

    return (
        <AnggotaLayout>
            <Head title="Profil" />

            <div className="mb-6">
                <h1 className="text-2xl font-bold text-slate-800">Profil Saya</h1>
                <p className="text-base text-slate-400 mt-1">{anggota.no_anggota}</p>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-5">
                {/* Data Diri */}
                <div className="bg-white rounded-2xl border border-slate-100 p-5 sm:p-6">
                    <p className="text-base font-bold text-slate-700 mb-4">Data Diri</p>
                    <div className="space-y-4">
                        {dataDiri.map((item, i) => {
                            const Icon = item.icon;
                            return (
                                <div key={i} className="flex items-start gap-3">
                                    <div className="w-9 h-9 rounded-lg bg-slate-50 text-slate-400 flex items-center justify-center shrink-0">
                                        <Icon size={16} />
                                    </div>
                                    <div>
                                        <p className="text-xs text-slate-400">{item.label}</p>
                                        <p className="text-sm font-semibold text-slate-700">{item.value}</p>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                    <p className="text-xs text-slate-400 mt-5 pt-4 border-t border-slate-100">
                        Untuk perubahan data diri, silakan hubungi Admin koperasi.
                    </p>
                </div>

                {/* Rekening */}
                <div className="bg-white rounded-2xl border border-slate-100 p-5 sm:p-6">
                    <div className="flex items-center justify-between mb-4">
                        <p className="text-base font-bold text-slate-700">Rekening Tersimpan</p>
                        <button
                            onClick={() => setShowForm(!showForm)}
                            className="flex items-center gap-1.5 text-sm font-semibold text-brand-green hover:text-brand-green-dark"
                        >
                            <Plus size={16} />
                            Tambah
                        </button>
                    </div>

                    {showForm && (
                        <form onSubmit={submit} className="mb-4 p-4 bg-slate-50 rounded-xl space-y-3">
                            <input
                                type="text"
                                value={data.nama_bank}
                                onChange={(e) => setData('nama_bank', e.target.value)}
                                placeholder="Nama Bank"
                                className="w-full px-4 py-2.5 text-sm rounded-lg border border-slate-300 focus:border-brand-green outline-none"
                            />
                            {errors.nama_bank && <p className="text-xs text-red-600">{errors.nama_bank}</p>}

                            <input
                                type="text"
                                value={data.no_rekening}
                                onChange={(e) => setData('no_rekening', e.target.value)}
                                placeholder="Nomor Rekening"
                                className="w-full px-4 py-2.5 text-sm rounded-lg border border-slate-300 focus:border-brand-green outline-none"
                            />
                            {errors.no_rekening && <p className="text-xs text-red-600">{errors.no_rekening}</p>}

                            <input
                                type="text"
                                value={data.atas_nama}
                                onChange={(e) => setData('atas_nama', e.target.value)}
                                placeholder="Nama Pemilik Rekening"
                                className="w-full px-4 py-2.5 text-sm rounded-lg border border-slate-300 focus:border-brand-green outline-none"
                            />
                            {errors.atas_nama && <p className="text-xs text-red-600">{errors.atas_nama}</p>}

                            <button
                                type="submit"
                                disabled={processing}
                                className="w-full py-2.5 text-sm font-bold rounded-lg bg-brand-green text-white hover:bg-brand-green-dark transition-colors disabled:opacity-50"
                            >
                                {processing ? 'Menyimpan...' : 'Simpan Rekening'}
                            </button>
                        </form>
                    )}

                    {rekening.length === 0 ? (
                        <p className="text-sm text-slate-400 text-center py-6">Belum ada rekening tersimpan.</p>
                    ) : (
                        <div className="space-y-2.5">
                            {rekening.map((r) => (
                                <div
                                    key={r.id}
                                    className={`flex items-center gap-3 p-3.5 rounded-xl border ${
                                        r.is_default ? 'border-brand-green bg-brand-green-light' : 'border-slate-100'
                                    }`}
                                >
                                    <div className="w-9 h-9 rounded-lg bg-white text-slate-400 flex items-center justify-center shrink-0">
                                        <CreditCard size={16} />
                                    </div>
                                    <div className="flex-1 min-w-0">
                                        <div className="flex items-center gap-2">
                                            <p className="text-sm font-semibold text-slate-800 truncate">{r.nama_bank}</p>
                                            {r.is_default && (
                                                <span className="text-xs font-semibold text-brand-green-dark bg-white px-2 py-0.5 rounded-full">
                                                    Utama
                                                </span>
                                            )}
                                        </div>
                                        <p className="text-xs text-slate-400">{r.no_rekening} &bull; a.n. {r.atas_nama}</p>
                                    </div>
                                    <div className="flex items-center gap-1 shrink-0">
                                        {!r.is_default && (
                                            <button
                                                onClick={() => jadikanDefault(r.id)}
                                                title="Jadikan utama"
                                                className="p-2 text-slate-400 hover:text-amber-500 transition-colors"
                                            >
                                                <Star size={16} />
                                            </button>
                                        )}
                                        <button
                                            onClick={() => hapus(r.id)}
                                            title="Hapus"
                                            className="p-2 text-slate-400 hover:text-red-600 transition-colors"
                                        >
                                            <Trash2 size={16} />
                                        </button>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </AnggotaLayout>
    );
}