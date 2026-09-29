import AnggotaLayout from '@/Layouts/AnggotaLayout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { AlertCircle, ArrowLeft, BadgeCheck, CheckCircle2, Clock, FileText, UserCheck, X } from 'lucide-react';
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

export default function Create({ anggota, poinSyarat, versiSyarat, pengajuanBerjalan, ditolakTerakhir }) {
    const { flash } = usePage().props;
    const terkirim = flash.status;
    const { data, setData, post, processing, errors } = useForm({ data_benar: false, setuju_syarat: false });
    const [showModal, setShowModal] = useState(false);
    const [syaratDibaca, setSyaratDibaca] = useState(false);

    function bukaSyarat() {
        setSyaratDibaca(true);
        setShowModal(true);
    }

    function submit(e) {
        e.preventDefault();
        post(route('portal.aktivasi.store'), withIdempotencyKey({
            onSuccess: () => setShowModal(false),
        }));
    }

    const bisaKirim = data.data_benar && data.setuju_syarat && !pengajuanBerjalan && !processing;

    return (
        <AnggotaLayout>
            <Head title="Pengajuan Aktivasi Keanggotaan" />

            <div className="max-w-3xl mx-auto">
                <Link
                    href={route('portal.aktivasi.landing')}
                    className={`inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 hover:text-brand-navy mb-5 rounded ${focusRing}`}
                >
                    <ArrowLeft size={16} />
                    Kembali ke Informasi Keanggotaan
                </Link>

                <div className="text-center mb-6">
                    <div className="w-14 h-14 rounded-2xl bg-brand-navy text-white flex items-center justify-center mx-auto mb-3">
                        <UserCheck size={26} />
                    </div>
                    <h1 className="text-xl sm:text-2xl font-bold text-slate-800">Formulir Pengajuan Aktivasi</h1>
                    <p className="text-sm text-slate-500 mt-1">
                        Bapak/Ibu dimohon memeriksa kesesuaian data di bawah ini, memberikan persetujuan
                        pada kedua pernyataan, kemudian mengirimkan pengajuan.
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
                                Pengajuan tertanggal {pengajuanBerjalan.tanggal_pengajuan} sedang dalam proses persetujuan Ketua.
                            </p>
                            <p className="text-sm text-amber-700 mt-1">
                                Keputusan akan disampaikan melalui WhatsApp. Bapak/Ibu tidak perlu mengajukan ulang.
                            </p>
                        </div>
                    </div>
                )}

                {ditolakTerakhir && !pengajuanBerjalan && (
                    <div className="flex items-start gap-3 bg-red-50 border border-red-200 rounded-2xl p-4 mb-5">
                        <AlertCircle className="text-red-500 shrink-0 mt-0.5" size={20} />
                        <div>
                            <p className="text-sm font-semibold text-red-700">
                                Pengajuan sebelumnya belum dapat disetujui ({ditolakTerakhir.tanggal}).
                            </p>
                            {ditolakTerakhir.catatan && (
                                <p className="text-sm text-red-600 mt-1 italic">&ldquo;{ditolakTerakhir.catatan}&rdquo;</p>
                            )}
                            <p className="text-sm text-red-600 mt-1">Bapak/Ibu dapat memperbaiki data dan mengajukan ulang melalui formulir di bawah ini.</p>
                        </div>
                    </div>
                )}

                {/* Data keanggotaan dari GATE */}
                <div className="bg-white rounded-2xl border border-slate-200 overflow-hidden mb-5">
                    <div className="px-5 py-4 border-b border-slate-100 flex items-center gap-2.5">
                        <BadgeCheck size={18} className="text-brand-navy" />
                        <div>
                            <p className="text-base font-bold text-slate-800">Data Keanggotaan</p>
                            <p className="text-xs text-slate-500">Bersumber dari data kepegawaian (GATE) dan tidak dapat diubah pada halaman ini</p>
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

                {!pengajuanBerjalan && (
                    <form onSubmit={submit} className="space-y-4">
                        <div className={`bg-white rounded-2xl border-2 p-5 transition-colors ${errors.data_benar ? 'border-red-300' : 'border-slate-200'}`}>
                            <div className="flex items-center gap-2.5 mb-3">
                                <div className="w-7 h-7 rounded-lg bg-brand-navy text-white flex items-center justify-center text-sm font-bold shrink-0">1</div>
                                <p className="text-base font-bold text-slate-800">Konfirmasi Data</p>
                            </div>
                            <label htmlFor="data-benar" className="flex items-start gap-3 cursor-pointer">
                                <input
                                    id="data-benar"
                                    type="checkbox"
                                    checked={data.data_benar}
                                    onChange={(e) => setData('data_benar', e.target.checked)}
                                    className="mt-0.5 w-5 h-5 rounded border-slate-300 text-brand-green focus:ring-2 focus:ring-brand-green/40 cursor-pointer shrink-0"
                                />
                                <span className="text-sm text-slate-700 leading-relaxed">
                                    Dengan ini saya menyatakan bahwa data keanggotaan di atas adalah <span className="font-semibold">benar</span> dan
                                    sesuai dengan data diri saya.
                                </span>
                            </label>
                            {errors.data_benar && <p className="text-sm text-red-600 mt-2">{errors.data_benar}</p>}
                        </div>

                        <div className={`bg-white rounded-2xl border-2 p-5 transition-colors ${errors.setuju_syarat ? 'border-red-300' : 'border-slate-200'}`}>
                            <div className="flex items-center gap-2.5 mb-3">
                                <div className="w-7 h-7 rounded-lg bg-brand-navy text-white flex items-center justify-center text-sm font-bold shrink-0">2</div>
                                <p className="text-base font-bold text-slate-800">Persetujuan Syarat</p>
                            </div>
                            <button
                                type="button"
                                onClick={bukaSyarat}
                                className={`w-full flex items-center justify-center gap-2 py-3 text-sm font-semibold rounded-xl border-2 border-slate-200 text-slate-700 hover:bg-slate-50 transition-colors mb-3 ${focusRing}`}
                            >
                                <FileText size={16} />
                                {syaratDibaca ? `Baca Ulang Syarat & Ketentuan (Versi ${versiSyarat})` : `Baca Syarat & Ketentuan Terlebih Dahulu (Versi ${versiSyarat})`}
                            </button>
                            <label htmlFor="setuju-syarat" className={`flex items-start gap-3 ${syaratDibaca ? 'cursor-pointer' : 'cursor-not-allowed opacity-60'}`}>
                                <input
                                    id="setuju-syarat"
                                    type="checkbox"
                                    disabled={!syaratDibaca}
                                    checked={data.setuju_syarat}
                                    onChange={(e) => setData('setuju_syarat', e.target.checked)}
                                    className="mt-0.5 w-5 h-5 rounded border-slate-300 text-brand-green focus:ring-2 focus:ring-brand-green/40 shrink-0 disabled:cursor-not-allowed"
                                />
                                <span className="text-sm text-slate-700 leading-relaxed">
                                    Demikian pengajuan ini saya isi dengan sebenarnya. Saya menyatakan <span className="font-semibold">tunduk dan patuh</span> terhadap
                                    Anggaran Dasar, Anggaran Rumah Tangga, peraturan khusus, serta seluruh kebijakan lain yang berlaku
                                    di Koperasi Karya Mandiri.
                                    {!syaratDibaca && (
                                        <span className="block text-xs text-amber-600 font-semibold mt-1">
                                            Mohon buka dan baca Syarat &amp; Ketentuan di atas terlebih dahulu untuk mengaktifkan persetujuan ini.
                                        </span>
                                    )}
                                </span>
                            </label>
                            {errors.setuju_syarat && <p className="text-sm text-red-600 mt-2">{errors.setuju_syarat}</p>}
                        </div>

                        {errors.pengajuan && (
                            <div className="flex items-start gap-2.5 bg-red-50 border border-red-200 rounded-xl p-3.5">
                                <AlertCircle size={18} className="text-red-500 shrink-0 mt-0.5" />
                                <p className="text-sm font-medium text-red-700">{errors.pengajuan}</p>
                            </div>
                        )}

                        <div className="sticky bottom-24 sm:static -mx-1 px-1 pb-1 sm:mx-0 sm:px-0 sm:pb-0 bg-slate-50/95 sm:bg-transparent backdrop-blur pt-2 sm:pt-0">
                            <button
                                type="submit"
                                disabled={!bisaKirim}
                                className={`w-full min-h-[52px] text-base font-bold rounded-2xl bg-brand-green text-white hover:bg-brand-green-dark active:scale-[0.99] transition-all disabled:opacity-50 disabled:cursor-not-allowed ${focusRing}`}
                            >
                                {processing ? 'Mengirim...' : 'Kirim Pengajuan'}
                            </button>
                        </div>
                    </form>
                )}

                {showModal && (
                    <div className="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm overflow-y-auto">
                        <div className="min-h-full flex items-end sm:items-center justify-center sm:p-4">
                            <div className="bg-white rounded-t-3xl sm:rounded-2xl w-full max-w-2xl mt-8 mb-0 sm:my-8 shadow-2xl max-h-[92dvh] flex flex-col" role="dialog" aria-modal="true" aria-labelledby="sk-title">
                                <div className="sm:hidden pt-2 pb-1 flex justify-center shrink-0" aria-hidden="true">
                                    <span className="w-10 h-1 rounded-full bg-slate-300" />
                                </div>
                                <div className="sticky top-0 bg-white border-b border-slate-100 px-4 sm:px-6 py-4 sm:py-5 flex items-start justify-between gap-4 rounded-t-3xl sm:rounded-t-2xl z-10">
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
                                        onClick={() => setShowModal(false)}
                                        className={`w-full py-3.5 text-sm font-bold rounded-xl bg-brand-navy text-white hover:bg-brand-navy-light transition-colors ${focusRing}`}
                                    >
                                        Tutup — Saya Telah Membaca
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
