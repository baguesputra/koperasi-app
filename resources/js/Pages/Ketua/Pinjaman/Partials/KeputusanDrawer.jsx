import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import { AlertCircle, CheckCircle2 } from 'lucide-react';
import Button from '@/Components/ui/Button';
import StatusBadge from '@/Components/ui/StatusBadge';
import { formatRupiah } from '@/Utils/formatCurrency';

const jabatanLabel = { staff: 'Staff', hod: 'HOD' };

function calculateDueDate(tanggalPengajuan, cicilanKe) {
    const date = new Date(tanggalPengajuan);
    date.setMonth(date.getMonth() + cicilanKe);
    date.setDate(0);
    return date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
}

export default function KeputusanDrawer({ pinjaman, onClose }) {
    const [aksi, setAksi] = useState(null);
    const [showTable, setShowTable] = useState(true);
    const { data, setData, post, processing, errors } = useForm({ catatan: '' });

    const bisaDiproses = pinjaman.status === 'approved_bendahara';

    function submit(e) {
        e.preventDefault();
        const url = aksi === 'approve'
            ? route('ketua.pinjaman.approve', pinjaman.id)
            : route('ketua.pinjaman.reject', pinjaman.id);
        post(url, {
            preserveScroll: true,
            onSuccess: () => onClose(),
        });
    }

    return (
        <div className="space-y-4">
            {pinjaman.catatan_bendahara && (
                <div className="bg-amber-50 border border-amber-200 rounded-xl p-4">
                    <div className="flex items-start gap-2.5">
                        <CheckCircle2 size={18} className="text-amber-600 shrink-0 mt-0.5" />
                        <div className="flex-1">
                            <p className="text-xs font-bold text-amber-700 mb-1">Sudah Disetujui Bendahara</p>
                            <p className="text-xs text-amber-600 mb-2">Catatan dari Bendahara:</p>
                            <p className="text-sm text-slate-700">{pinjaman.catatan_bendahara}</p>
                        </div>
                    </div>
                </div>
            )}

            <div className="bg-brand-navy rounded-2xl p-5 text-white">
                <p className="text-xs text-slate-300 mb-1">Nominal Pengajuan</p>
                <p className="text-3xl font-bold mb-2">{formatRupiah(pinjaman.nominal)}</p>
                <p className="text-sm text-slate-300 italic mb-4">{pinjaman.terbilang}</p>

                <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-3 border-t border-white/10">
                    <div>
                        <p className="text-xs text-slate-300 mb-1">Tenor</p>
                        <p className="text-lg font-bold">{pinjaman.tenor_bulan} bulan</p>
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
            </div>

            <div className="bg-slate-50 rounded-xl p-5 border border-slate-200">
                <p className="text-sm font-bold text-slate-700 mb-3">Data Anggota</p>
                <div className="grid grid-cols-2 md:grid-cols-3 gap-x-6 gap-y-4">
                    <div>
                        <p className="text-xs text-slate-400 mb-1">Nama</p>
                        <p className="text-base font-semibold text-slate-800">{pinjaman.anggota.nama}</p>
                    </div>
                    <div>
                        <p className="text-xs text-slate-400 mb-1">No. Anggota</p>
                        <p className="text-base font-semibold text-slate-800">{pinjaman.anggota.no_anggota}</p>
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

            <div className="bg-slate-50 rounded-xl p-4">
                <p className="text-xs text-slate-400 mb-3">Simulasi Total Angsuran (Bunga Menurun / Declining Balance)</p>
                <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div className="bg-white rounded-lg p-4 border border-slate-200">
                        <p className="text-xs text-slate-400 mb-1">Total Pokok</p>
                        <p className="text-lg font-bold text-slate-800 break-all">{formatRupiah(pinjaman.total_pokok_angsuran)}</p>
                    </div>
                    <div className="bg-amber-50 rounded-lg p-4 border border-amber-100">
                        <p className="text-xs text-slate-400 mb-1">Total Bunga</p>
                        <p className="text-lg font-bold text-amber-700 break-all">{formatRupiah(pinjaman.total_bunga_angsuran)}</p>
                    </div>
                    <div className="bg-brand-green/10 rounded-lg p-4 border border-brand-green/20">
                        <p className="text-xs text-slate-400 mb-1">Total Bayar</p>
                        <p className="text-lg font-bold text-brand-green break-all">{formatRupiah(pinjaman.total_angsuran)}</p>
                    </div>
                </div>
            </div>

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
                        Detail Jadwal Angsuran
                    </span>
                    <span className="text-xs text-slate-400">{pinjaman.jadwal_angsuran?.length ?? 0} cicilan</span>
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
                                {pinjaman.jadwal_angsuran?.map((a) => (
                                    <tr key={a.cicilan_ke} className="border-b border-slate-50 hover:bg-slate-50">
                                        <td className="py-2 px-3 text-center font-medium text-slate-700">{a.cicilan_ke}</td>
                                        <td className="py-2 px-3 text-right text-slate-700">{formatRupiah(a.nominal_pokok)}</td>
                                        <td className="py-2 px-3 text-right text-amber-600">{formatRupiah(a.nominal_bunga)}</td>
                                        <td className="py-2 px-3 text-right font-semibold text-slate-800">{formatRupiah(a.total_bayar)}</td>
                                        <td className="py-2 px-3 text-center text-slate-600 whitespace-nowrap">
                                            {pinjaman.tanggal_pengajuan && calculateDueDate(pinjaman.tanggal_pengajuan, a.cicilan_ke)}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                            <tfoot className="bg-slate-50 border-t-2 border-slate-200">
                                <tr className="font-bold text-slate-800">
                                    <td className="py-2 px-3">Total</td>
                                    <td className="py-2 px-3 text-right">{formatRupiah(pinjaman.total_pokok_angsuran)}</td>
                                    <td className="py-2 px-3 text-right text-amber-600">{formatRupiah(pinjaman.total_bunga_angsuran)}</td>
                                    <td className="py-2 px-3 text-right">{formatRupiah(pinjaman.total_angsuran)}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                )}
            </div>

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
                                <label className="block text-sm font-semibold text-slate-600 mb-2">
                                    Catatan {aksi === 'approve' ? 'Persetujuan' : 'Penolakan'}
                                </label>
                                <textarea
                                    value={data.catatan}
                                    onChange={(e) => setData('catatan', e.target.value)}
                                    rows={3}
                                    placeholder={aksi === 'approve' ? 'Contoh: Layak dicairkan, saldo mencukupi.' : 'Contoh: Belum memenuhi ketentuan.'}
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
