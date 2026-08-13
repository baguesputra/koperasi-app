import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import Button from '@/Components/ui/Button';
import { formatRupiah } from '@/Utils/formatCurrency';

const jabatanLabel = { staff: 'Staff', hod: 'HOD' };

export default function KeputusanDrawer({ pinjaman, onClose }) {
    const [aksi, setAksi] = useState(null); // 'approve' | 'reject' | null
    const { data, setData, post, processing, errors } = useForm({ catatan: '' });

    function submit(e) {
        e.preventDefault();
        const url = aksi === 'approve'
            ? route('bendahara.pinjaman.approve', pinjaman.id)
            : route('bendahara.pinjaman.reject', pinjaman.id);
        post(url, {
            preserveScroll: true,
            onSuccess: () => onClose(),
        });
    }

    return (
        <div>
            {/* Ringkasan Nominal */}
            <div className="bg-brand-navy rounded-2xl p-4 text-white mb-4">
                <p className="text-xs text-slate-300 mb-1">Nominal Pengajuan</p>
                <p className="text-2xl font-bold">{formatRupiah(pinjaman.nominal)}</p>
                <p className="text-sm text-slate-300 mt-1 italic">{pinjaman.terbilang}</p>
            </div>

            <div className="grid grid-cols-2 gap-x-4 gap-y-3 mb-4">
                <div>
                    <p className="text-xs text-slate-400">Tenor</p>
                    <p className="text-sm font-semibold text-slate-800">{pinjaman.tenor_bulan} bulan</p>
                </div>
                <div>
                    <p className="text-xs text-slate-400">Bunga</p>
                    <p className="text-sm font-semibold text-slate-800">{pinjaman.persentase_bunga}% / bulan</p>
                </div>
                <div>
                    <p className="text-xs text-slate-400">Tanggal Pengajuan</p>
                    <p className="text-sm font-semibold text-slate-800">{pinjaman.tanggal_pengajuan}</p>
                </div>
                <div>
                    <p className="text-xs text-slate-400">Reloan</p>
                    <p className="text-sm font-semibold text-slate-800">
                        {pinjaman.sudah_pakai_privilege_reloan ? 'Ya' : 'Tidak'}
                    </p>
                </div>
            </div>

            {/* Keperluan */}
            <div className="mb-4">
                <p className="text-xs text-slate-400 mb-1">Keperluan Peminjaman</p>
                <p className="text-sm text-slate-700">{pinjaman.keperluan || '-'}</p>
            </div>

            {/* Rekening */}
            <div className="bg-slate-50 rounded-xl p-3 mb-4">
                <p className="text-xs text-slate-400 mb-1">Rekening Tujuan Pencairan</p>
                <p className="text-sm font-bold text-slate-800">{pinjaman.rekening.bank}</p>
                <p className="text-sm text-slate-600">{pinjaman.rekening.no_rekening}</p>
                <p className="text-sm text-slate-400">a.n. {pinjaman.rekening.atas_nama}</p>
            </div>

            {/* Data Anggota */}
            <div className="grid grid-cols-2 gap-x-4 gap-y-2 mb-4">
                <div>
                    <p className="text-xs text-slate-400">Nama</p>
                    <p className="text-sm font-semibold text-slate-800">{pinjaman.anggota.nama}</p>
                </div>
                <div>
                    <p className="text-xs text-slate-400">No Anggota</p>
                    <p className="text-sm font-semibold text-slate-800">{pinjaman.anggota.no_anggota}</p>
                </div>
                <div>
                    <p className="text-xs text-slate-400">Cabang</p>
                    <p className="text-sm font-semibold text-slate-800">{pinjaman.anggota.cabang}</p>
                </div>
                <div>
                    <p className="text-xs text-slate-400">Jabatan</p>
                    <p className="text-sm font-semibold text-slate-800">{jabatanLabel[pinjaman.anggota.jabatan]}</p>
                </div>
                <div>
                    <p className="text-xs text-slate-400">Lama Keanggotaan</p>
                    <p className="text-sm font-semibold text-slate-800">{pinjaman.anggota.lama_keanggotaan_tahun} tahun</p>
                </div>
            </div>

            {pinjaman.catatan_bendahara && (
                <div className="mb-5">
                    <p className="text-xs text-slate-400 mb-1">Catatan Bendahara</p>
                    <p className="text-sm text-slate-700">{pinjaman.catatan_bendahara}</p>
                </div>
            )}

            {/* Keputusan */}
            <div className="pt-4 border-t border-slate-100">
                {!aksi ? (
                    <div className="flex items-center gap-3">
                        <Button variant="primary" onClick={() => setAksi('approve')}>
                            Setujui
                        </Button>
                        <Button variant="danger" onClick={() => setAksi('reject')}>
                            Tolak
                        </Button>
                    </div>
                ) : (
                    <form onSubmit={submit}>
                        <label className="block text-sm font-semibold text-slate-600 mb-2">
                            Catatan {aksi === 'approve' ? 'Persetujuan' : 'Penolakan'}
                        </label>
                        <textarea
                            value={data.catatan}
                            onChange={(e) => setData('catatan', e.target.value)}
                            rows={3}
                            placeholder={aksi === 'approve' ? 'Contoh: Data lengkap, memenuhi syarat.' : 'Contoh: Dokumen belum lengkap.'}
                            className="w-full px-4 py-2.5 text-base rounded-xl border border-slate-300 focus:border-brand-green focus:ring-2 focus:ring-brand-green/20 outline-none transition-colors"
                            autoFocus
                        />
                        {errors.catatan && <p className="text-sm text-red-600 mt-1.5">{errors.catatan}</p>}

                        <div className="flex items-center gap-3 mt-4">
                            <Button
                                type="submit"
                                variant={aksi === 'approve' ? 'primary' : 'danger'}
                                disabled={processing}
                            >
                                {processing ? 'Memproses...' : `Konfirmasi ${aksi === 'approve' ? 'Setujui' : 'Tolak'}`}
                            </Button>
                            <Button type="button" variant="ghost" onClick={() => setAksi(null)}>
                                Batal
                            </Button>
                        </div>
                    </form>
                )}
            </div>
        </div>
    );
}