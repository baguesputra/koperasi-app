import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import { AlertCircle, CheckCircle2, ArrowUpRight, Minus, CreditCard, Calendar } from 'lucide-react';
import Button from '@/Components/ui/Button';
import StatusBadge from '@/Components/ui/StatusBadge';
import { formatRupiah } from '@/Utils/formatCurrency';
import { withIdempotencyKey } from '@/Utils/idempotency';

export default function KeputusanDrawer({ pengajuan, onClose }) {
    const [aksi, setAksi] = useState(null);
    const [showPinjamanTable, setShowPinjamanTable] = useState(false);
    const { data, setData, post, processing, errors } = useForm({
        catatan: '',
        limit_disetujui: String(pengajuan.limit_diminta ?? ''),
    });

    const bisaDiproses = pengajuan.status === 'diajukan';
    const selisihLimit = pengajuan.limit_diminta - pengajuan.limit_saat_ini;
    const diBawahSaatIni = Number(data.limit_disetujui) > 0 && Number(data.limit_disetujui) < pengajuan.limit_saat_ini;

    function submit(e) {
        e.preventDefault();
        const url = aksi === 'approve'
            ? route('bendahara.pengajuan-limit.approve', pengajuan.id)
            : route('bendahara.pengajuan-limit.reject', pengajuan.id);
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
                        <p className="text-xs text-slate-300 mb-1">Anggota</p>
                        <p className="text-xl font-bold">{pengajuan.anggota.nama}</p>
                        <p className="text-sm text-slate-300 mt-0.5">{pengajuan.anggota.no_karyawan}</p>
                    </div>
                    <StatusBadge status={pengajuan.status} />
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-4 border-t border-white/10">
                    <div className="bg-white/5 rounded-xl p-4">
                        <p className="text-xs text-slate-300 mb-1">Limit Saat Ini</p>
                        <p className="text-2xl font-bold">{formatRupiah(pengajuan.limit_saat_ini)}</p>
                    </div>
                    <div className="bg-white/5 rounded-xl p-4 flex items-center justify-center">
                        <ArrowUpRight className="text-amber-400" size={24} />
                    </div>
                    <div className="bg-brand-green/20 rounded-xl p-4 border border-brand-green/30">
                        <p className="text-xs text-brand-green/80 mb-1">Limit Diminta</p>
                        <p className="text-2xl font-bold text-brand-green-light">{formatRupiah(pengajuan.limit_diminta)}</p>
                        <p className="text-xs text-brand-green/70 mt-1">
                            +{formatRupiah(selisihLimit)} ({((selisihLimit / pengajuan.limit_saat_ini) * 100).toFixed(1)}%)
                        </p>
                    </div>
                </div>
            </div>

            <div className="bg-slate-50 rounded-xl p-4 border border-slate-200">
                <p className="text-xs text-slate-400 mb-2">Alasan Pengajuan</p>
                <p className="text-sm text-slate-700">{pengajuan.keterangan}</p>
            </div>

            <div className="bg-slate-50 rounded-xl p-4 border border-slate-200">
                <p className="text-sm font-bold text-slate-700 mb-3">Data Anggota</p>
                <div className="grid grid-cols-2 md:grid-cols-4 gap-x-6 gap-y-3">
                    <div>
                        <p className="text-xs text-slate-400 mb-1">No. Karyawan</p>
                        <p className="text-base font-semibold text-slate-800">{pengajuan.anggota.no_karyawan}</p>
                    </div>
                    <div>
                        <p className="text-xs text-slate-400 mb-1">Cabang</p>
                        <p className="text-base font-semibold text-slate-800">{pengajuan.anggota.cabang}</p>
                    </div>
                    <div>
                        <p className="text-xs text-slate-400 mb-1">Lama Keanggotaan</p>
                        <p className="text-base font-semibold text-slate-800">{pengajuan.anggota.lama_keanggotaan_tahun} tahun</p>
                    </div>
                    <div>
                        <p className="text-xs text-slate-400 mb-1">Tanggal Pengajuan</p>
                        <p className="text-base font-semibold text-slate-800">{pengajuan.tanggal_pengajuan}</p>
                    </div>
                </div>
            </div>

            <div className="bg-white rounded-xl border border-slate-200 p-4">
                <div className="flex items-center gap-2 mb-3">
                    <CreditCard className="text-brand-navy" size={18} />
                    <p className="text-sm font-bold text-slate-700">Status Pinjaman</p>
                </div>
                {pengajuan.pinjaman_aktif && pengajuan.pinjaman_aktif.length > 0 ? (
                    <div className="space-y-3">
                        {pengajuan.pinjaman_aktif.map((p, idx) => (
                            <div key={p.id} className="bg-slate-50 rounded-lg p-3 border border-slate-100">
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center gap-3">
                                        <div className="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                                            <CreditCard size={16} />
                                        </div>
                                        <div>
                                            <p className="font-semibold text-slate-800">Pinjaman #{idx + 1}</p>
                                            <p className="text-xs text-slate-400">
                                                {formatRupiah(p.nominal)} &bull; {p.tenor_bulan} bln
                                            </p>
                                        </div>
                                    </div>
                                    <div className="text-right">
                                        <div className="flex items-center gap-1.5 text-xs text-slate-500">
                                            <Calendar size={12} />
                                            <span>Cicilan ke-{(p.total_cicilan - p.sisa_cicilan) + 1} / {p.total_cicilan}</span>
                                        </div>
                                        <p className="text-sm font-bold text-slate-800 mt-0.5">
                                            {formatRupiah(p.sisa_total_bayar)}
                                        </p>
                                        <p className="text-xs text-slate-400">Sisa Total Bayar</p>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                ) : (
                    <div className="flex items-center justify-center py-4 text-slate-400">
                        <p className="text-sm">Tidak ada pinjaman aktif</p>
                    </div>
                )}
            </div>

            {pengajuan.pinjaman_pending && (
                <div className="bg-amber-50 border border-amber-200 rounded-xl p-4">
                    <div className="flex items-start gap-2.5">
                        <CheckCircle2 size={18} className="text-amber-600 shrink-0 mt-0.5" />
                        <div className="flex-1">
                            <p className="text-xs font-bold text-amber-700 mb-1">Pinjaman Sedang Diproses</p>
                            <p className="text-sm text-slate-700">
                                Nominal: {formatRupiah(pengajuan.pinjaman_pending.nominal)} &bull;
                                Status: {pengajuan.pinjaman_pending.status === 'diajukan' ? 'Menunggu Bendahara' : 'Menunggu Ketua'}
                            </p>
                        </div>
                    </div>
                </div>
            )}

            {pengajuan.catatan_bendahara && (
                <div className="bg-slate-50 rounded-xl p-4 border border-slate-200">
                    <p className="text-xs text-slate-400 mb-1">Catatan Bendahara</p>
                    <p className="text-sm text-slate-700">{pengajuan.catatan_bendahara}</p>
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
                                    Setujui
                                </Button>
                                <Button variant="danger" onClick={() => setAksi('reject')}>
                                    Tolak
                                </Button>
                            </div>
                        ) : (
                            <form onSubmit={submit}>
                                {aksi === 'approve' && (
                                    <div className="mb-4">
                                        <label className="block text-sm font-semibold text-slate-600 mb-1">
                                            Total Limit Baru yang Disetujui
                                        </label>
                                        <p className="text-xs text-slate-500 mb-2">
                                            Isi total limit baru secara keseluruhan (bukan nilai selisih). Wajib {'>'} {formatRupiah(pengajuan.limit_saat_ini)}.
                                        </p>
                                        <div className="relative">
                                            <span className="absolute left-4 top-1/2 -translate-y-1/2 text-base font-semibold text-slate-400">Rp</span>
                                            <input
                                                type="number"
                                                min={pengajuan.limit_saat_ini + 1}
                                                value={data.limit_disetujui}
                                                onChange={(e) => setData('limit_disetujui', e.target.value)}
                                                className="w-full pl-12 pr-4 py-2.5 text-lg font-bold rounded-xl border border-slate-300 focus:border-brand-green focus:ring-2 focus:ring-brand-green/20 outline-none transition-colors"
                                                autoFocus
                                            />
                                        </div>
                                        {Number(data.limit_disetujui) > pengajuan.limit_saat_ini && (
                                            <p className="text-xs font-semibold text-brand-green-dark mt-1.5">
                                                Penambahan limit: +{formatRupiah(Number(data.limit_disetujui) - pengajuan.limit_saat_ini)}
                                            </p>
                                        )}
                                        {errors.limit_disetujui && <p className="text-sm text-red-600 mt-1.5">{errors.limit_disetujui}</p>}
                                        {diBawahSaatIni && (
                                            <p className="text-sm text-red-600 font-semibold mt-1.5">Nominal di bawah limit saat ini ({formatRupiah(pengajuan.limit_saat_ini)}). Masukkan total limit baru yang lebih besar.</p>
                                        )}
                                    </div>
                                )}
                                <label className="block text-sm font-semibold text-slate-600 mb-2">
                                    Catatan {aksi === 'approve' ? 'Verifikasi' : 'Penolakan'}
                                </label>
                                <textarea
                                    value={data.catatan}
                                    onChange={(e) => setData('catatan', e.target.value)}
                                    rows={3}
                                    placeholder={aksi === 'approve' ? 'Contoh: Layak diteruskan ke Ketua sebesar nominal di atas.' : 'Contoh: Belum memenuhi syarat.'}
                                    className="w-full px-4 py-2.5 text-base rounded-xl border border-slate-300 focus:border-brand-green focus:ring-2 focus:ring-brand-green/20 outline-none transition-colors"
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
                    </>
                ) : (
                    <div className="flex items-center gap-3">
                        <StatusBadge status={pengajuan.status} />
                    </div>
                )}
            </div>

            {showPinjamanTable && <span className="hidden"><Minus size={12} /></span>}
        </div>
    );
}
