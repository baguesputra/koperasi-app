import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import { AlertCircle, CheckCircle2, HeartHandshake, Calendar, Camera } from 'lucide-react';
import Button from '@/Components/ui/Button';
import ChipNominal from '@/Components/ui/ChipNominal';
import StatusBadge from '@/Components/ui/StatusBadge';
import { formatRupiah } from '@/Utils/formatCurrency';
import { withIdempotencyKey } from '@/Utils/idempotency';

export default function KeputusanDrawer({ klaim, tahap, onClose }) {
    const [aksi, setAksi] = useState(null);
    const { data, setData, post, processing, errors } = useForm({
        catatan: '',
        nominal: String(tahap === 'ketua' ? (klaim.nominal_bendahara ?? '') : ''),
    });

    const routeBase = tahap === 'ketua' ? 'ketua.klaim-dana-sosial' : 'bendahara.klaim-dana-sosial';
    const bisaDiproses = tahap === 'ketua' ? klaim.status === 'approved_bendahara' : klaim.status === 'diajukan';

    function submit(e) {
        e.preventDefault();
        const url = aksi === 'approve'
            ? route(`${routeBase}.approve`, klaim.id)
            : route(`${routeBase}.reject`, klaim.id);
        post(url, withIdempotencyKey({
            preserveScroll: true,
            onSuccess: () => onClose(),
        }));
    }

    return (
        <div className="space-y-4">
            <div className="bg-brand-navy rounded-2xl p-5 text-white">
                <div className="flex items-start justify-between gap-4 mb-4">
                    <div>
                        <p className="text-xs text-slate-300 mb-1">Pemohon</p>
                        <p className="text-xl font-bold">{klaim.anggota.nama}</p>
                        <p className="text-sm text-slate-300 mt-0.5">{klaim.anggota.no_karyawan} &bull; {klaim.anggota.cabang}</p>
                    </div>
                    <StatusBadge status={klaim.status} />
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4 border-t border-white/10">
                    <div className="bg-white/5 rounded-xl p-4">
                        <p className="text-xs text-slate-300 mb-1">Jenis Santunan</p>
                        <p className="text-lg font-bold">{klaim.jenis_label}</p>
                        <p className="text-xs text-slate-300 mt-1">
                            Kejadian {klaim.tanggal_kejadian}
                            {klaim.sub_tipe ? ` • ${klaim.sub_tipe === 'rajal' ? 'Rawat Jalan' : 'Rawat Inap'}` : ''}
                            {klaim.lama_hari ? ` • ${klaim.lama_hari} hari` : ''}
                            {klaim.hubungan ? ` • ${klaim.hubungan}` : ''}
                        </p>
                    </div>
                    <div className="bg-white/5 rounded-xl p-4">
                        <p className="text-xs text-slate-300 mb-1">Tanggal Pengajuan</p>
                        <p className="text-lg font-bold">{klaim.tanggal_pengajuan}</p>
                        <p className="text-xs text-slate-300 mt-1">Lama keanggotaan {klaim.anggota.lama_keanggotaan_tahun} tahun</p>
                    </div>
                </div>
            </div>

            <div className="bg-slate-50 rounded-xl p-4 border border-slate-200">
                <p className="text-xs text-slate-400 mb-2">Keterangan Pemohon</p>
                <p className="text-sm text-slate-700">{klaim.keterangan}</p>
            </div>

            {klaim.foto_url && (
                <div className="bg-white rounded-xl border border-slate-200 p-4">
                    <div className="flex items-center gap-2 mb-3">
                        <Camera className="text-brand-navy" size={18} />
                        <p className="text-sm font-bold text-slate-700">Dokumen Pendukung</p>
                    </div>
                    <a href={klaim.foto_url} target="_blank" rel="noopener noreferrer">
                        <img src={klaim.foto_url} alt="Dokumen pendukung" className="max-h-72 rounded-lg border border-slate-200 object-contain" />
                    </a>
                </div>
            )}

            {klaim.nominal_bendahara && (
                <div className="flex items-center gap-3 bg-blue-50 border border-blue-200 rounded-xl p-4">
                    <HeartHandshake size={18} className="text-blue-600 shrink-0" />
                    <div>
                        <p className="text-xs font-bold text-blue-700">Usulan Bendahara</p>
                        <p className="text-lg font-bold text-slate-800">{formatRupiah(klaim.nominal_bendahara)}</p>
                    </div>
                </div>
            )}

            {klaim.nominal_final && (
                <div className="flex items-center gap-3 bg-brand-green-light/40 border border-brand-green/20 rounded-xl p-4">
                    <CheckCircle2 size={18} className="text-brand-green-dark shrink-0" />
                    <div>
                        <p className="text-xs font-bold text-brand-green-dark">Santunan Disetujui</p>
                        <p className="text-lg font-bold text-slate-800">{formatRupiah(klaim.nominal_final)}</p>
                    </div>
                </div>
            )}

            {klaim.catatan_bendahara && (
                <div className="bg-slate-50 rounded-xl p-4 border border-slate-200">
                    <p className="text-xs text-slate-400 mb-1">Catatan Bendahara</p>
                    <p className="text-sm text-slate-700">{klaim.catatan_bendahara}</p>
                </div>
            )}

            {klaim.catatan_ketua && (
                <div className="bg-slate-50 rounded-xl p-4 border border-slate-200">
                    <p className="text-xs text-slate-400 mb-1">Catatan Ketua</p>
                    <p className="text-sm text-slate-700">{klaim.catatan_ketua}</p>
                </div>
            )}

            <div className="pt-4 border-t border-slate-100">
                {errors.keputusan && (
                    <div className="flex items-start gap-2.5 bg-red-50 border border-red-100 rounded-xl p-3 mb-4">
                        <AlertCircle size={18} className="text-red-500 shrink-0 mt-0.5" />
                        <p className="text-sm font-medium text-red-700">{errors.keputusan}</p>
                    </div>
                )}

                {bisaDiproses ? (
                    <>
                        {!aksi ? (
                            <div className="flex items-center gap-3">
                                <Button variant="primary" onClick={() => setAksi('approve')}>
                                    {tahap === 'ketua' ? 'Setujui' : 'Verifikasi'}
                                </Button>
                                <Button variant="danger" onClick={() => setAksi('reject')}>
                                    Tolak
                                </Button>
                            </div>
                        ) : (
                            <form onSubmit={submit}>
                                {aksi === 'approve' && (
                                    <div className="mb-4">
                                        <label className="block text-sm font-semibold text-slate-600 mb-2">
                                            Nominal Santunan yang {tahap === 'ketua' ? 'Disetujui' : 'Diusulkan'}
                                        </label>
                                        <div className="relative">
                                            <span className="absolute left-4 top-1/2 -translate-y-1/2 text-base font-semibold text-slate-400">Rp</span>
                                            <input
                                                type="number"
                                                min={1}
                                                value={data.nominal}
                                                onChange={(e) => setData('nominal', e.target.value)}
                                                className="w-full pl-12 pr-4 py-2.5 text-lg font-bold rounded-xl border border-slate-300 focus:border-brand-green focus:ring-2 focus:ring-brand-green/20 outline-none transition-colors"
                                                autoFocus
                                            />
                                        </div>
                                        <ChipNominal grup="santunan" nilai={data.nominal} onPilih={(n) => setData('nominal', n)} />
                                        {errors.nominal && <p className="text-sm text-red-600 mt-1.5">{errors.nominal}</p>}
                                        {tahap === 'ketua' && klaim.nominal_bendahara && (
                                            <p className="text-xs text-slate-400 mt-1.5 flex items-center gap-1">
                                                <Calendar size={12} />
                                                Usulan Bendahara {formatRupiah(klaim.nominal_bendahara)} — dapat disesuaikan.
                                            </p>
                                        )}
                                    </div>
                                )}
                                <label className="block text-sm font-semibold text-slate-600 mb-2">
                                    Catatan {aksi === 'approve' ? (tahap === 'ketua' ? 'Persetujuan' : 'Verifikasi') : 'Penolakan'}
                                </label>
                                <textarea
                                    value={data.catatan}
                                    onChange={(e) => setData('catatan', e.target.value)}
                                    rows={3}
                                    placeholder={aksi === 'approve' ? 'Contoh: Dokumen lengkap dan memenuhi ketentuan.' : 'Contoh: Dokumen belum memenuhi ketentuan.'}
                                    className="w-full px-4 py-2.5 text-base rounded-xl border border-slate-300 focus:border-brand-green focus:ring-2 focus:ring-brand-green/20 outline-none transition-colors"
                                />
                                {errors.catatan && <p className="text-sm text-red-600 mt-1.5">{errors.catatan}</p>}

                                <div className="flex items-center gap-3 mt-4">
                                    <Button
                                        type="submit"
                                        variant={aksi === 'approve' ? 'primary' : 'danger'}
                                        disabled={processing}
                                    >
                                        {processing ? 'Memproses...' : `Konfirmasi ${aksi === 'approve' ? (tahap === 'ketua' ? 'Setujui' : 'Verifikasi') : 'Tolak'}`}
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
                        <StatusBadge status={klaim.status} />
                    </div>
                )}
            </div>
        </div>
    );
}
