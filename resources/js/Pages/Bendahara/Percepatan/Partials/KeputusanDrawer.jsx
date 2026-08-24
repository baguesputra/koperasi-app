import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import Button from '@/Components/ui/Button';
import { formatRupiah } from '@/Utils/formatCurrency';

const tipeLabel = {
    percepat: 'Percepat Pelunasan',
    perpanjang: 'Perpanjang Tenor',
    lunas_total: 'Lunas Sekarang',
};

export default function KeputusanDrawer({ pengajuan, onClose }) {
    const [aksi, setAksi] = useState(null);
    const [showTable, setShowTable] = useState(true);
    const { data, setData, post, processing, errors } = useForm({ catatan: '' });

    function submit(e) {
        e.preventDefault();
        const url = aksi === 'approve'
            ? route('bendahara.percepatan.approve', pengajuan.id)
            : route('bendahara.percepatan.reject', pengajuan.id);
        post(url, {
            preserveScroll: true,
            onSuccess: () => onClose(),
        });
    }

    const bisaDiproses = pengajuan.status === 'diajukan';
    const isApproved = pengajuan.status === 'approved_bendahara' || pengajuan.status === 'aktif';
    const isRejected = pengajuan.status === 'ditolak';

    const tipe = tipeLabel[pengajuan.tipe] ?? pengajuan.tipe;
    const preview = pengajuan.preview;
    const angsuranBaru = pengajuan.angsuran_baru;

    return (
        <div className="space-y-4">
            {pengajuan.catatan_bendahara && (
                <div className="bg-slate-50 border border-slate-200 rounded-xl p-4">
                    <p className="text-xs font-semibold text-slate-400 mb-1">Catatan Bendahara</p>
                    <p className="text-sm text-slate-700">{pengajuan.catatan_bendahara}</p>
                </div>
            )}

            <div className="bg-brand-navy rounded-2xl p-5 text-white">
                <p className="text-xs text-slate-300 mb-1">Jenis Pengajuan</p>
                <p className="text-xl font-bold mb-4">{tipe}</p>

                <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-3 border-t border-white/10">
                    <div>
                        <p className="text-xs text-slate-300 mb-1">Tenor Saat Ini</p>
                        <p className="text-lg font-bold">{pengajuan.tenor_lama} bulan</p>
                    </div>
                    {pengajuan.tenor_baru && (
                        <div>
                            <p className="text-xs text-slate-300 mb-1">Tenor Diminta</p>
                            <p className="text-lg font-bold text-brand-green-light">{pengajuan.tenor_baru} bulan</p>
                        </div>
                    )}
                    <div>
                        <p className="text-xs text-slate-300 mb-1">Tanggal Pengajuan</p>
                        <p className="text-lg font-bold">{pengajuan.tanggal_pengajuan}</p>
                    </div>
                </div>
            </div>

            <div className="bg-slate-50 rounded-xl p-5 border border-slate-200">
                <p className="text-sm font-bold text-slate-700 mb-3">Data Anggota</p>
                <div className="grid grid-cols-2 md:grid-cols-3 gap-x-6 gap-y-4">
                    <div>
                        <p className="text-xs text-slate-400 mb-1">Nama</p>
                        <p className="text-base font-semibold text-slate-800">{pengajuan.anggota.nama}</p>
                    </div>
                    <div>
                        <p className="text-xs text-slate-400 mb-1">No. Anggota</p>
                        <p className="text-base font-semibold text-slate-800">{pengajuan.anggota.no_anggota}</p>
                    </div>
                    {pengajuan.anggota.cabang && (
                        <div>
                            <p className="text-xs text-slate-400 mb-1">Cabang</p>
                            <p className="text-base font-semibold text-slate-800">{pengajuan.anggota.cabang}</p>
                        </div>
                    )}
                    {pengajuan.anggota.jabatan && (
                        <div>
                            <p className="text-xs text-slate-400 mb-1">Jabatan</p>
                            <p className="text-base font-semibold text-slate-800">{pengajuan.anggota.jabatan === 'hod' ? 'HOD' : 'Staff'}</p>
                        </div>
                    )}
                    {pengajuan.anggota.lama_keanggotaan_tahun && (
                        <div>
                            <p className="text-xs text-slate-400 mb-1">Lama Keanggotaan</p>
                            <p className="text-base font-semibold text-slate-800">{pengajuan.anggota.lama_keanggotaan_tahun} tahun</p>
                        </div>
                    )}
                    <div>
                        <p className="text-xs text-slate-400 mb-1">Sisa Angsuran</p>
                        <p className="text-base font-semibold text-slate-800">{pengajuan.pinjaman.sisa_angsuran}x</p>
                    </div>
                </div>
            </div>

            <div className="bg-slate-50 rounded-xl p-4">
                <p className="text-xs text-slate-400 mb-3">Alasan Pengajuan</p>
                <p className="text-sm text-slate-700">{pengajuan.keterangan || '-'}</p>
            </div>

            {preview && (
                <div className="bg-slate-50 rounded-xl p-4 border border-slate-200">
                    <p className="text-xs text-slate-400 mb-3">Simulasi Perubahan (Bunga Menurun / Declining Balance)</p>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div className="bg-white rounded-lg p-4 border border-slate-200">
                            <p className="text-xs text-slate-400 mb-1">Sisa Pokok</p>
                            <p className="text-lg font-bold text-slate-800 break-all">{formatRupiah(preview.sisa_pokok)}</p>
                        </div>
                        {preview.bunga !== undefined && (
                            <div className="bg-amber-50 rounded-lg p-4 border border-amber-100">
                                <p className="text-xs text-slate-400 mb-1">Bunga 1 Bulan</p>
                                <p className="text-lg font-bold text-amber-700 break-all">{formatRupiah(preview.bunga)}</p>
                            </div>
                        )}
                        {preview.total_bayar && (
                            <div className="bg-brand-green/10 rounded-lg p-4 border border-brand-green/20">
                                <p className="text-xs text-slate-400 mb-1">Total Bayar (Lunas Sekarang)</p>
                                <p className="text-lg font-bold text-brand-green break-all">{formatRupiah(preview.total_bayar)}</p>
                            </div>
                        )}
                        {preview.jadwal && preview.jadwal.length > 0 && (
                            <div className="md:col-span-3">
                                <p className="text-xs text-slate-400 mb-2">Detail Angsuran Baru</p>
                                <div className="overflow-x-auto">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="text-left text-slate-400 border-b border-slate-200">
                                                <th className="py-2 px-3 w-12 text-center">Ke</th>
                                                <th className="py-2 px-3 text-right">Pokok</th>
                                                <th className="py-2 px-3 text-right">Bunga</th>
                                                <th className="py-2 px-3 text-right">Total Bayar</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {preview.jadwal.map((a) => (
                                                <tr key={a.cicilan_ke} className="border-b border-slate-50 hover:bg-slate-50">
                                                    <td className="py-2 px-3 text-center font-medium text-slate-700">{a.cicilan_ke}</td>
                                                    <td className="py-2 px-3 text-right text-slate-700">{formatRupiah(a.nominal_pokok)}</td>
                                                    <td className="py-2 px-3 text-right text-amber-600">{formatRupiah(a.nominal_bunga)}</td>
                                                    <td className="py-2 px-3 text-right font-semibold text-slate-800">{formatRupiah(a.total_bayar)}</td>
                                                </tr>
                                            ))}
                                            <tr className="font-bold bg-white border-t-2 border-slate-200">
                                                <td className="py-2 px-3">Total</td>
                                                <td className="py-2 px-3 text-right">{formatRupiah(preview.jadwal.reduce((s, a) => s + a.nominal_pokok, 0))}</td>
                                                <td className="py-2 px-3 text-right text-amber-600">{formatRupiah(preview.jadwal.reduce((s, a) => s + a.nominal_bunga, 0))}</td>
                                                <td className="py-2 px-3 text-right">{formatRupiah(preview.jadwal.reduce((s, a) => s + a.total_bayar, 0))}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            )}

            {angsuranBaru && angsuranBaru.length > 0 && (
                <div className="border border-slate-200 rounded-xl overflow-hidden">
                    <button
                        type="button"
                        onClick={() => setShowTable(!showTable)}
                        className="w-full px-4 py-3 bg-slate-50 flex items-center justify-between text-sm font-semibold text-slate-700 hover:bg-slate-100 transition-colors"
                    >
                        <span className="flex items-center gap-2">
                            <svg className={`w-5 h-5 transition-transform ${showTable ? 'rotate-180' : ''}`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                            </svg>
                            Detail Jadwal Angsuran Baru
                        </span>
                        <span className="text-xs text-slate-400">{angsuranBaru.length} cicilan</span>
                    </button>

                    {showTable && (
                        <div className="overflow-x-auto p-3">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="text-left text-slate-400 border-b border-slate-200">
                                        <th className="py-2 px-3 w-12 text-center">Ke</th>
                                        <th className="py-2 px-3 text-right">Pokok</th>
                                        <th className="py-2 px-3 text-right">Bunga</th>
                                        <th className="py-2 px-3 text-right">Total Bayar</th>
                                        <th className="py-2 px-3 text-center">Jatuh Tempo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {angsuranBaru.map((a) => (
                                        <tr key={a.id} className="border-b border-slate-50 hover:bg-slate-50">
                                            <td className="py-2 px-3 text-center font-medium text-slate-700">{a.cicilan_ke}</td>
                                            <td className="py-2 px-3 text-right text-slate-700">{formatRupiah(a.nominal_pokok)}</td>
                                            <td className="py-2 px-3 text-right text-amber-600">{formatRupiah(a.nominal_bunga)}</td>
                                            <td className="py-2 px-3 text-right font-semibold text-slate-800">{formatRupiah(a.total_bayar)}</td>
                                            <td className="py-2 px-3 text-center text-slate-600 whitespace-nowrap">
                                                {a.tanggal_jatuh_tempo ? new Date(a.tanggal_jatuh_tempo).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) : '-'}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                                <tfoot className="bg-slate-50 border-t-2 border-slate-200">
                                    <tr className="font-bold text-slate-800">
                                        <td className="py-2 px-3">Total</td>
                                        <td className="py-2 px-3 text-right">{formatRupiah(angsuranBaru.reduce((sum, a) => sum + a.nominal_pokok, 0))}</td>
                                        <td className="py-2 px-3 text-right text-amber-600">{formatRupiah(angsuranBaru.reduce((sum, a) => sum + a.nominal_bunga, 0))}</td>
                                        <td className="py-2 px-3 text-right">{formatRupiah(angsuranBaru.reduce((sum, a) => sum + a.total_bayar, 0))}</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    )}
                </div>
            )}

            {pengajuan.nominal_final && (
                <div className="bg-brand-green/5 border border-brand-green/20 rounded-xl p-4">
                    <p className="text-xs text-brand-green-dark mb-1">Nominal Final Pelunasan</p>
                    <p className="text-xl font-bold text-brand-green">{formatRupiah(pengajuan.nominal_final)}</p>
                </div>
            )}

            <div className="pt-4 border-t border-slate-100">
                {bisaDiproses && !aksi && (
                    <div className="flex items-center gap-3">
                        <Button variant="primary" onClick={() => setAksi('approve')}>
                            Setujui
                        </Button>
                        <Button variant="danger" onClick={() => setAksi('reject')}>
                            Tolak
                        </Button>
                    </div>
                )}

                {bisaDiproses && aksi && (
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

                {(isApproved || isRejected) && (
                    <span className={`inline-flex items-center px-3 py-1.5 text-sm font-semibold rounded-lg ${
                        isApproved ? 'bg-brand-green/10 text-brand-green' : 'bg-red-50 text-red-600'
                    }`}>
                        {isApproved ? 'Disetujui' : 'Ditolak'}
                    </span>
                )}
            </div>
        </div>
    );
}