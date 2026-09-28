import AnggotaLayout from '@/Layouts/AnggotaLayout';
import { Head, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { AlertCircle, BadgeCheck, CheckCircle2, Clock, FileText, ShieldCheck, UserCheck, X } from 'lucide-react';
import { formatRupiah } from '@/Utils/formatCurrency';
import { withIdempotencyKey } from '@/Utils/idempotency';

const focusRing =
    'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-green focus-visible:ring-offset-2';

function DataRow({ label, value }) {
    return (
        <div className="flex items-start justify-between gap-4 py-2.5 border-b border-slate-100 last:border-0">
            <span className="text-sm text-slate-500 shrink-0">{label}</span>
            <span className="text-sm font-semibold text-slate-800 text-right">{value || '-'}</span>
        </div>
    );
}

export default function Create({
    anggota, poinSyarat, versiSyarat,
    simpananPokok, simpananWajib, danaSosial, limitAwal,
    pengajuanBerjalan, ditolakTerakhir,
}) {
    const { flash } = usePage().props;
    const terkirim = flash.status;
    const { post, processing, errors } = useForm({ data_benar: false, setuju_syarat: false });
    const [form, setForm] = useState({ data_benar: false, setuju_syarat: false });
    const [showModal, setShowModal] = useState(false);

    function submit(e) {
        e.preventDefault();
        post(route('portal.aktivasi.store'), withIdempotencyKey({
            data: form,
            onSuccess: () => setShowModal(false),
        }));
    }

    const bisaKirim = form.data_benar && form.setuju_syarat && !pengajuanBerjalan && !processing;

    return (
        <AnggotaLayout>
            <Head title="Pengajuan Aktivasi Keanggotaan" />

            <div className="max-w-3xl mx-auto">
                <div className="text-center mb-6">
                    <div className="w-14 h-14 rounded-2xl bg-brand-navy text-white flex items-center justify-center mx-auto mb-3">
                        <UserCheck size={26} />
                    </div>
                    <h1 className="text-xl sm:text-2xl font-bold text-slate-800">Pengajuan Aktivasi Keanggotaan</h1>
                    <p className="text-sm text-slate-500 mt-1">
                        Akun Anda terdaftar di sistem, namun status keanggotaan belum aktif.
                        Lengkapi pengajuan di bawah agar dapat menggunakan seluruh layanan koperasi.
                    </p>
                </div>

                {terkirim && (
                    <div className="flex items-start gap-3 bg-brand-green-light border border-brand-green/20 rounded-2xl p-4 mb-5">
                        <CheckCircle2 className="text-brand-green shrink-0 mt-0.5" size={20} />
                        <p className="text-sm font-semibold text-brand-green-dark">{terkirim}</p>
                    </div>
                )}

                {pengajuanBerjalan && (
                    <div className="flex items-start gap-3 bg-amber-50 border border-amber-200 rounded-2xl p-4 mb-5">
                        <Clock className="text-amber-600 shrink-0 mt-0.5" size={20} />
                        <div>
                            <p className="text-sm font-semibold text-amber-800">
                                Pengajuan tertanggal {pengajuanBerjalan.tanggal_pengajuan} sedang menunggu persetujuan Ketua.
                            </p>
                            <p className="text-sm text-amber-700 mt-1">
                                Keputusan akan disampaikan melalui WhatsApp. Tidak perlu mengajukan ulang.
                            </p>
                        </div>
                    </div>
                )}

                {ditolakTerakhir && !pengajuanBerjalan && (
                    <div className="flex items-start gap-3 bg-red-50 border border-red-200 rounded-2xl p-4 mb-5">
                        <AlertCircle className="text-red-500 shrink-0 mt-0.5" size={20} />
                        <div>
                            <p className="text-sm font-semibold text-red-700">
                                Pengajuan sebelumnya ditolak ({ditolakTerakhir.tanggal}).
                            </p>
                            {ditolakTerakhir.catatan && (
                                <p className="text-sm text-red-600 mt-1 italic">&ldquo;{ditolakTerakhir.catatan}&rdquo;</p>
                            )}
                            <p className="text-sm text-red-600 mt-1">Perbaiki data lalu ajukan ulang di bawah.</p>
                        </div>
                    </div>
                )}

                {/* Data keanggotaan dari GATE */}
                <div className="bg-white rounded-2xl border border-slate-200 overflow-hidden mb-5">
                    <div className="px-5 py-4 border-b border-slate-100 flex items-center gap-2.5">
                        <BadgeCheck size={18} className="text-brand-navy" />
                        <div>
                            <p className="text-base font-bold text-slate-800">Data Keanggotaan</p>
                            <p className="text-xs text-slate-500">Bersumber dari data karyawan (GATE), tidak dapat diubah di sini</p>
                        </div>
                    </div>
                    <div className="px-5 py-3">
                        <DataRow label="Nama" value={anggota.nama} />
                        <DataRow label="No. Anggota" value={anggota.no_anggota} />
                        <DataRow label="No. Karyawan" value={anggota.no_karyawan} />
                        <DataRow label="Cabang" value={anggota.cabang} />
                        <DataRow label="Unit Bisnis" value={anggota.unit_bisnis} />
                        <DataRow label="Jabatan" value={anggota.jabatan} />
                        <DataRow label="Departemen" value={anggota.department} />
                        <DataRow label="No. HP" value={anggota.no_hp} />
                        <DataRow label="Alamat" value={anggota.alamat} />
                    </div>
                </div>

                {/* Ringkasan iuran */}
                <div className="bg-brand-navy rounded-2xl p-5 text-white mb-5">
                    <p className="text-base font-bold mb-3">Ringkasan Kewajiban Iuran</p>
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div className="bg-white/5 rounded-xl p-4 border border-white/10">
                            <p className="text-xs text-slate-300 mb-1">Simpanan Pokok (sekali)</p>
                            <p className="text-xl font-bold">{formatRupiah(simpananPokok)}</p>
                        </div>
                        <div className="bg-white/5 rounded-xl p-4 border border-white/10">
                            <p className="text-xs text-slate-300 mb-1">Simpanan Wajib / bulan</p>
                            <p className="text-xl font-bold">{formatRupiah(simpananWajib + danaSosial)}</p>
                            <p className="text-xs text-slate-300 mt-1">
                                {formatRupiah(simpananWajib)} simpanan + {formatRupiah(danaSosial)} dana sosial
                            </p>
                        </div>
                        <div className="bg-brand-green/20 rounded-xl p-4 border border-brand-green/30">
                            <p className="text-xs text-brand-green/80 mb-1">Limit pinjaman awal</p>
                            <p className="text-xl font-bold text-brand-green-light">{formatRupiah(limitAwal)}</p>
                            <p className="text-xs text-brand-green/70 mt-1">Tahun pertama keanggotaan</p>
                        </div>
                    </div>
                </div>

                {/* Alur persetujuan */}
                <div className="bg-white rounded-2xl border border-slate-200 p-5 mb-5">
                    <div className="flex items-center gap-2 mb-3">
                        <ShieldCheck size={18} className="text-brand-navy" />
                        <p className="text-base font-bold text-slate-800">Alur Persetujuan</p>
                    </div>
                    <div className="space-y-2.5">
                        {['Pengajuan terkirim beserta pernyataan persetujuan di bawah', 'Ditinjau dan diputuskan final oleh Ketua Koperasi', 'Keputusan disampaikan via WhatsApp; bila disetujui, simpanan pokok tercatat otomatis dan akun aktif'].map((t, i) => (
                            <div key={i} className="flex gap-3">
                                <div className="w-6 h-6 rounded-full bg-brand-navy/10 text-brand-navy flex items-center justify-center text-xs font-bold shrink-0">{i + 1}</div>
                                <p className="text-sm text-slate-600">{t}</p>
                            </div>
                        ))}
                    </div>
                </div>

                {!pengajuanBerjalan && (
                    <form onSubmit={submit} className="bg-white rounded-2xl border border-slate-200 p-5">
                        <p className="text-base font-bold text-slate-800 mb-3">Pernyataan Pengajuan</p>

                        <label htmlFor="data-benar" className="flex items-start gap-3 cursor-pointer p-3.5 rounded-xl border-2 border-slate-200 has-checked:border-brand-green has-checked:bg-brand-green-light/40 transition-colors mb-3">
                            <input
                                id="data-benar"
                                type="checkbox"
                                checked={form.data_benar}
                                onChange={(e) => setForm({ ...form, data_benar: e.target.checked })}
                                className="mt-0.5 w-5 h-5 rounded border-slate-300 text-brand-green focus:ring-2 focus:ring-brand-green/40 cursor-pointer shrink-0"
                            />
                            <span className="text-sm text-slate-700 leading-relaxed">
                                Data keanggotaan di atas adalah <span className="font-semibold">benar</span> sesuai data diri saya.
                            </span>
                        </label>

                        <button
                            type="button"
                            onClick={() => setShowModal(true)}
                            className={`w-full flex items-center justify-center gap-2 py-3 text-sm font-semibold rounded-xl border-2 border-slate-200 text-slate-700 hover:bg-slate-50 transition-colors mb-3 ${focusRing}`}
                        >
                            <FileText size={16} />
                            Baca Syarat &amp; Ketentuan (Versi {versiSyarat})
                        </button>

                        <label htmlFor="setuju-syarat" className="flex items-start gap-3 cursor-pointer p-3.5 rounded-xl border-2 border-slate-200 has-checked:border-brand-green has-checked:bg-brand-green-light/40 transition-colors mb-4">
                            <input
                                id="setuju-syarat"
                                type="checkbox"
                                checked={form.setuju_syarat}
                                onChange={(e) => setForm({ ...form, setuju_syarat: e.target.checked })}
                                className="mt-0.5 w-5 h-5 rounded border-slate-300 text-brand-green focus:ring-2 focus:ring-brand-green/40 cursor-pointer shrink-0"
                            />
                            <span className="text-sm text-slate-700 leading-relaxed">
                                Demikian pengajuan ini saya isi dengan benar. Saya <span className="font-semibold">tunduk dan patuh</span> pada
                                Anggaran Dasar, Anggaran Rumah Tangga, peraturan khusus, dan kebijakan lainnya yang berlaku
                                di Koperasi Karya Mandiri.
                            </span>
                        </label>

                        {(errors.data_benar || errors.setuju_syarat || errors.pengajuan) && (
                            <div className="flex items-start gap-2.5 bg-red-50 border border-red-200 rounded-xl p-3.5 mb-4">
                                <AlertCircle size={18} className="text-red-500 shrink-0 mt-0.5" />
                                <div className="text-sm font-medium text-red-700 space-y-1">
                                    {errors.data_benar && <p>{errors.data_benar}</p>}
                                    {errors.setuju_syarat && <p>{errors.setuju_syarat}</p>}
                                    {errors.pengajuan && <p>{errors.pengajuan}</p>}
                                </div>
                            </div>
                        )}

                        <button
                            type="submit"
                            disabled={!bisaKirim}
                            className={`w-full py-3.5 text-base font-bold rounded-2xl bg-brand-green text-white hover:bg-brand-green-dark transition-colors disabled:opacity-50 disabled:cursor-not-allowed ${focusRing}`}
                        >
                            {processing ? 'Mengirim...' : 'Kirim Pengajuan Aktivasi'}
                        </button>
                    </form>
                )}

                {showModal && (
                    <div className="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm overflow-y-auto">
                        <div className="min-h-full flex items-start sm:items-center justify-center p-4">
                            <div className="bg-white rounded-2xl w-full max-w-2xl my-8 shadow-2xl" role="dialog" aria-modal="true" aria-labelledby="sk-title">
                                <div className="sticky top-0 bg-white border-b border-slate-100 px-6 py-5 flex items-start justify-between gap-4 rounded-t-2xl z-10">
                                    <div className="flex items-start gap-3">
                                        <div className="w-10 h-10 rounded-xl bg-brand-green-light flex items-center justify-center shrink-0">
                                            <FileText size={20} className="text-brand-green" />
                                        </div>
                                        <div>
                                            <h2 id="sk-title" className="text-lg font-bold text-slate-800">Syarat &amp; Ketentuan Keanggotaan</h2>
                                            <p className="text-xs text-slate-500 mt-0.5">Versi {versiSyarat}</p>
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        onClick={() => setShowModal(false)}
                                        className={`p-2 -m-2 text-slate-400 hover:text-slate-600 transition-colors shrink-0 rounded-lg ${focusRing}`}
                                        aria-label="Tutup"
                                    >
                                        <X size={20} />
                                    </button>
                                </div>

                                <div className="px-6 py-5 max-h-[55vh] overflow-y-auto">
                                    <div className="space-y-2.5">
                                        {poinSyarat.map((p, i) => (
                                            <div key={i} className="border border-slate-100 rounded-xl p-4">
                                                <div className="flex items-start gap-3">
                                                    <div className="w-7 h-7 rounded-lg bg-brand-navy text-white flex items-center justify-center text-sm font-bold shrink-0">
                                                        {i + 1}
                                                    </div>
                                                    <div className="flex-1 min-w-0">
                                                        <p className="text-sm font-bold text-slate-800">{p.judul}</p>
                                                        <p className="text-sm text-slate-600 mt-1.5 leading-relaxed">{p.deskripsi}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>

                                <div className="sticky bottom-0 bg-white border-t border-slate-100 px-6 py-5 rounded-b-2xl">
                                    <button
                                        type="button"
                                        onClick={() => { setForm({ ...form, setuju_syarat: true }); setShowModal(false); }}
                                        className={`w-full py-3.5 text-sm font-bold rounded-xl bg-brand-navy text-white hover:bg-brand-navy-light transition-colors ${focusRing}`}
                                    >
                                        Saya Sudah Membaca &amp; Memahami
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </AnggotaLayout>
    );
}
