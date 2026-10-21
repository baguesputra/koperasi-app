import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import Button from '@/Components/ui/Button';
import StatusBadge from '@/Components/ui/StatusBadge';
import { formatRupiah } from '@/Utils/formatCurrency';
import { withIdempotencyKey } from '@/Utils/idempotency';
import JejakNominal from '@/Pages/PinjamanApproval/JejakNominal';
import RincianBebanPinjaman from '@/Pages/PinjamanApproval/RincianBebanPinjaman';

const jabatanLabel = { staff: 'Staff', hod: 'HOD' };

export default function KeputusanDrawer({ pinjaman, onClose }) {
    const [aksi, setAksi] = useState(null); // 'approve' | 'reject' | null
    const { data, setData, post, processing, errors } = useForm({
        catatan: '',
    });

    const bisaDiproses = pinjaman.status === 'diajukan';

    function submit(e) {
        e.preventDefault();
        const url = aksi === 'approve'
            ? route('admin.pinjaman.approve', pinjaman.id)
            : route('admin.pinjaman.reject', pinjaman.id);
        post(url, withIdempotencyKey({
            preserveScroll: true,
            onSuccess: () => onClose(),
        }));
    }

    return (
        <div className="space-y-4">
            <div className="bg-brand-navy rounded-2xl p-5 text-white">
                <p className="text-xs text-slate-300 mb-1">Nominal Diminta Anggota</p>
                <p className="text-3xl font-bold mb-2">{formatRupiah(pinjaman.nominal_diminta)}</p>
                <p className="text-sm text-slate-300 italic mb-4">{pinjaman.terbilang}</p>

                <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-3 border-t border-white/10">
                    <div>
                        <p className="text-xs text-slate-300 mb-1">Tenor Diminta</p>
                        <p className="text-lg font-bold">{pinjaman.tenor_diminta} bulan</p>
                    </div>
                    <div>
                        <p className="text-xs text-slate-300 mb-1">Bunga</p>
                        <p className="text-lg font-bold">{pinjaman.persentase_bunga}% / bulan</p>
                    </div>
                    <div>
                        <p className="text-xs text-slate-300 mb-1">Tanggal Pengajuan</p>
                        <p className="text-lg font-bold">{pinjaman.tanggal_pengajuan}</p>
                    </div>
                </div>

                {pinjaman.mandiri_bendahara && (
                    <p className="text-xs text-amber-300 mt-3">
                        Pengajuan mandiri oleh Bendahara — tahap Bendahara dilewati otomatis bila diteruskan.
                    </p>
                )}
            </div>

            <JejakNominal pinjaman={pinjaman} />

            <div className="bg-slate-50 rounded-xl p-5 border border-slate-200">
                <p className="text-sm font-bold text-slate-700 mb-3">Data Anggota</p>
                <div className="grid grid-cols-2 md:grid-cols-3 gap-x-6 gap-y-4">
                    <div>
                        <p className="text-xs text-slate-400 mb-1">Nama</p>
                        <p className="text-base font-semibold text-slate-800">{pinjaman.anggota.nama}</p>
                    </div>
                    <div>
                        <p className="text-xs text-slate-400 mb-1">No. Karyawan</p>
                        <p className="text-base font-semibold text-slate-800">{pinjaman.anggota.no_karyawan}</p>
                    </div>
                    <div>
                        <p className="text-xs text-slate-400 mb-1">Cabang</p>
                        <p className="text-base font-semibold text-slate-800">{pinjaman.anggota.cabang}</p>
                    </div>
                    <div>
                        <p className="text-xs text-slate-400 mb-1">Jabatan</p>
                        <p className="text-base font-semibold text-slate-800">{jabatanLabel[pinjaman.anggota.jabatan]}</p>
                    </div>
                    <div>
                        <p className="text-xs text-slate-400 mb-1">Lama Keanggotaan</p>
                        <p className="text-base font-semibold text-slate-800">{pinjaman.anggota.lama_keanggotaan_tahun} tahun</p>
                    </div>
                    <div>
                        <p className="text-xs text-slate-400 mb-1">Reloan</p>
                        <p className="text-base font-semibold text-slate-800">
                            {pinjaman.sudah_pakai_privilege_reloan ? 'Ya' : 'Tidak'}
                        </p>
                    </div>
                </div>
            </div>

            <RincianBebanPinjaman pinjaman={pinjaman} />

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div className="bg-slate-50 rounded-xl p-4">
                    <p className="text-xs text-slate-400 mb-2">Keperluan Peminjaman</p>
                    <p className="text-sm text-slate-700">{pinjaman.keperluan || '-'}</p>
                </div>

                <div className="bg-slate-50 rounded-xl p-4">
                    <p className="text-xs text-slate-400 mb-2">Rekening Tujuan Pencairan</p>
                    <p className="text-sm font-bold text-slate-800">{pinjaman.rekening.bank}</p>
                    <p className="text-sm text-slate-600">{pinjaman.rekening.no_rekening}</p>
                    <p className="text-xs text-slate-400">a.n. {pinjaman.rekening.atas_nama}</p>
                </div>
            </div>

            {pinjaman.catatan_admin && (
                <div className="bg-slate-50 rounded-xl p-4 border border-slate-200">
                    <p className="text-xs text-slate-400 mb-1">Catatan Admin</p>
                    <p className="text-sm text-slate-700">{pinjaman.catatan_admin}</p>
                </div>
            )}

            <div className="pt-4 border-t border-slate-100">
                {bisaDiproses ? (
                    <>
                        {!aksi ? (
                            <div className="flex items-center gap-3">
                                <Button variant="primary" onClick={() => setAksi('approve')}>
                                    Teruskan
                                </Button>
                                <Button variant="danger" onClick={() => setAksi('reject')}>
                                    Tolak
                                </Button>
                            </div>
                        ) : (
                            <form onSubmit={submit}>
                                {errors.keputusan && (
                                    <p className="text-sm text-red-600 mb-3">{errors.keputusan}</p>
                                )}
                                <label className="block text-sm font-semibold text-slate-600 mb-2">
                                    Catatan {aksi === 'approve' ? 'Penerusan' : 'Penolakan'}
                                </label>
                                <textarea
                                    value={data.catatan}
                                    onChange={(e) => setData('catatan', e.target.value)}
                                    rows={3}
                                    placeholder={aksi === 'approve' ? 'Contoh: Dokumen lengkap, diteruskan ke Bendahara.' : 'Contoh: Dokumen belum lengkap.'}
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
                                        {processing ? 'Memproses...' : `Konfirmasi ${aksi === 'approve' ? 'Teruskan' : 'Tolak'}`}
                                    </Button>
                                    <Button type="button" variant="ghost" onClick={() => setAksi(null)}>
                                        Batal
                                    </Button>
                                </div>
                            </form>
                        )}
                    </>
                ) : (
                    <div className="flex items-center gap-3">
                        <StatusBadge status={pinjaman.status} />
                    </div>
                )}
            </div>
        </div>
    );
}
