import AnggotaLayout from '@/Layouts/AnggotaLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Clock, CheckCircle2, XCircle, Camera } from 'lucide-react';
import { withIdempotencyKey } from '@/Utils/idempotency';

function formatRupiah(angka) {
    if (angka === null || angka === undefined) return '-';
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka);
}

const focusRing =
    'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-green focus-visible:ring-offset-2';

const jenisOpsi = [
    { value: 'sakit', label: 'Santunan Sakit', deskripsi: 'Rawat jalan minimal 3 hari atau rawat inap minimal 1 hari. Wajib melampirkan foto surat keterangan rumah sakit.' },
    { value: 'lahiran_khitan', label: 'Santunan Kelahiran / Khitan', deskripsi: 'Bagi anggota atau keluarga anggota yang melahirkan atau melaksanakan khitan.' },
    { value: 'duka', label: 'Santunan Duka', deskripsi: 'Meninggal dunia: orang tua, suami, istri, atau anak anggota.' },
    { value: 'bahagia_menikah', label: 'Santunan Pernikahan', deskripsi: 'Pernikahan anggota. Pengajuan disampaikan setelah acara pernikahan dilaksanakan.' },
];

const subTipeOpsi = [
    { value: 'rajal', label: 'Rawat Jalan (minimal 3 hari)' },
    { value: 'opname', label: 'Rawat Inap (minimal 1 hari)' },
];

const hubunganOpsi = [
    { value: 'orang_tua', label: 'Orang Tua' },
    { value: 'suami', label: 'Suami' },
    { value: 'istri', label: 'Istri' },
    { value: 'anak', label: 'Anak' },
];

const statusStyle = {
    diajukan: 'bg-amber-50 text-amber-700',
    approved_bendahara: 'bg-blue-50 text-blue-700',
    disetujui: 'bg-brand-green-light text-brand-green-dark',
    ditolak: 'bg-red-50 text-red-600',
};

const statusIcon = { diajukan: Clock, approved_bendahara: Clock, disetujui: CheckCircle2, ditolak: XCircle };
const statusLabel = { diajukan: 'Menunggu Verifikasi Bendahara', approved_bendahara: 'Menunggu Keputusan Ketua', disetujui: 'Disetujui', ditolak: 'Ditolak' };

export default function Create({ pengajuanBerjalan, riwayat }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        jenis: '', sub_tipe: '', hubungan: '', tanggal_kejadian: '', lama_hari: '', keterangan: '', foto: null,
    });

    const sakit = data.jenis === 'sakit';
    const duka = data.jenis === 'duka';
    const minimalHari = data.sub_tipe === 'opname' ? 1 : 3;

    function submit(e) {
        e.preventDefault();
        post(route('portal.klaim-dana-sosial.store'), withIdempotencyKey({ onSuccess: () => reset() }));
    }

    return (
        <AnggotaLayout>
            <Head title="Pengajuan Santunan Dana Sosial" />

            <Link
                href={route('portal.dashboard')}
                className={`inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 hover:text-brand-navy mb-5 rounded ${focusRing}`}
            >
                <ArrowLeft size={16} />
                Kembali ke Beranda
            </Link>

            <div className="mb-6">
                <h1 className="text-xl sm:text-2xl font-bold text-slate-800">Pengajuan Santunan Dana Sosial</h1>
                <p className="text-base text-slate-400 mt-1">
                    Santunan kemanusiaan bagi anggota: sakit, kelahiran/khitan, duka, dan pernikahan.
                    Besaran santunan ditetapkan oleh Bendahara dan diputuskan final oleh Ketua Koperasi.
                </p>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-5">
                <div className="bg-white rounded-2xl border border-slate-100 p-6">
                    {pengajuanBerjalan ? (
                        <div className="flex items-start gap-3 bg-amber-50 border border-amber-100 rounded-xl p-4">
                            <Clock className="text-amber-600 shrink-0 mt-0.5" size={20} />
                            <div>
                                <p className="text-sm text-amber-800">
                                    Pengajuan santunan Bapak/Ibu ({pengajuanBerjalan.jenis_label}, {pengajuanBerjalan.tanggal_pengajuan}) masih dalam proses.
                                    Mohon menunggu keputusan sebelum mengajukan kembali.
                                </p>
                                <p className="text-sm text-amber-700 mt-1.5">
                                    Perkembangan keputusan disampaikan melalui WhatsApp dan dapat dipantau pada riwayat di bawah.
                                </p>
                            </div>
                        </div>
                    ) : (
                        <form onSubmit={submit} encType="multipart/form-data">
                            <div className="mb-5">
                                <label className="block text-base font-semibold text-slate-700 mb-2">Jenis Santunan</label>
                                <div className="space-y-2">
                                    {jenisOpsi.map((o) => (
                                        <label key={o.value} className={`flex items-start gap-3 p-3.5 rounded-xl border cursor-pointer transition-colors ${data.jenis === o.value ? 'border-brand-green bg-brand-green-light/30' : 'border-slate-200 hover:border-slate-300'}`}>
                                            <input
                                                type="radio"
                                                name="jenis"
                                                value={o.value}
                                                checked={data.jenis === o.value}
                                                onChange={(e) => setData({ ...data, jenis: e.target.value, sub_tipe: '', hubungan: '', lama_hari: '' })}
                                                className="w-5 h-5 mt-0.5 text-brand-green focus:ring-brand-green/30"
                                            />
                                            <span>
                                                <span className="block text-base font-semibold text-slate-800">{o.label}</span>
                                                <span className="block text-sm text-slate-500 mt-0.5">{o.deskripsi}</span>
                                            </span>
                                        </label>
                                    ))}
                                </div>
                                {errors.jenis && <p className="text-sm text-red-600 mt-1.5">{errors.jenis}</p>}
                            </div>

                            {sakit && (
                                <>
                                    <div className="mb-5">
                                        <label className="block text-base font-semibold text-slate-700 mb-2">Jenis Perawatan</label>
                                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                            {subTipeOpsi.map((o) => (
                                                <label key={o.value} className={`flex items-center gap-2.5 p-3 rounded-xl border cursor-pointer ${data.sub_tipe === o.value ? 'border-brand-green bg-brand-green-light/30' : 'border-slate-200'}`}>
                                                    <input
                                                        type="radio"
                                                        name="sub_tipe"
                                                        value={o.value}
                                                        checked={data.sub_tipe === o.value}
                                                        onChange={(e) => setData('sub_tipe', e.target.value)}
                                                        className="w-5 h-5 text-brand-green focus:ring-brand-green/30"
                                                    />
                                                    <span className="text-sm font-semibold text-slate-700">{o.label}</span>
                                                </label>
                                            ))}
                                        </div>
                                        {errors.sub_tipe && <p className="text-sm text-red-600 mt-1.5">{errors.sub_tipe}</p>}
                                    </div>
                                    <div className="mb-5">
                                        <label htmlFor="lama-hari" className="block text-base font-semibold text-slate-700 mb-2">Lama Perawatan (hari)</label>
                                        <p className="text-xs text-slate-400 -mt-1 mb-2">Minimal {minimalHari} hari sesuai jenis perawatan yang dipilih.</p>
                                        <input
                                            id="lama-hari"
                                            type="number"
                                            min={minimalHari}
                                            value={data.lama_hari}
                                            onChange={(e) => setData('lama_hari', e.target.value)}
                                            placeholder={String(minimalHari)}
                                            className={`w-full px-4 py-3 text-base rounded-xl border border-slate-300 focus:border-brand-green focus:ring-2 focus:ring-brand-green/20 outline-none transition-colors ${focusRing}`}
                                        />
                                        {errors.lama_hari && <p className="text-sm text-red-600 mt-1.5">{errors.lama_hari}</p>}
                                    </div>
                                </>
                            )}

                            {duka && (
                                <div className="mb-5">
                                    <label className="block text-base font-semibold text-slate-700 mb-2">Hubungan Keluarga</label>
                                    <div className="grid grid-cols-2 gap-2">
                                        {hubunganOpsi.map((o) => (
                                            <label key={o.value} className={`flex items-center gap-2.5 p-3 rounded-xl border cursor-pointer ${data.hubungan === o.value ? 'border-brand-green bg-brand-green-light/30' : 'border-slate-200'}`}>
                                                <input
                                                    type="radio"
                                                    name="hubungan"
                                                    value={o.value}
                                                    checked={data.hubungan === o.value}
                                                    onChange={(e) => setData('hubungan', e.target.value)}
                                                    className="w-5 h-5 text-brand-green focus:ring-brand-green/30"
                                                />
                                                <span className="text-sm font-semibold text-slate-700">{o.label}</span>
                                            </label>
                                        ))}
                                    </div>
                                    {errors.hubungan && <p className="text-sm text-red-600 mt-1.5">{errors.hubungan}</p>}
                                </div>
                            )}

                            {data.jenis !== '' && (
                                <>
                                    <div className="mb-5">
                                        <label htmlFor="tanggal-kejadian" className="block text-base font-semibold text-slate-700 mb-2">
                                            Tanggal {data.jenis === 'bahagia_menikah' ? 'Pernikahan' : 'Kejadian'}
                                        </label>
                                        {data.jenis === 'bahagia_menikah' && (
                                            <p className="text-xs text-slate-400 -mt-1 mb-2">Pengajuan disampaikan setelah acara pernikahan dilaksanakan.</p>
                                        )}
                                        <input
                                            id="tanggal-kejadian"
                                            type="date"
                                            max={new Date().toISOString().slice(0, 10)}
                                            value={data.tanggal_kejadian}
                                            onChange={(e) => setData('tanggal_kejadian', e.target.value)}
                                            className={`w-full px-4 py-3 text-base rounded-xl border border-slate-300 focus:border-brand-green focus:ring-2 focus:ring-brand-green/20 outline-none transition-colors ${focusRing}`}
                                        />
                                        {errors.tanggal_kejadian && <p className="text-sm text-red-600 mt-1.5">{errors.tanggal_kejadian}</p>}
                                    </div>

                                    <div className="mb-5">
                                        <label htmlFor="keterangan" className="block text-base font-semibold text-slate-700 mb-2">Keterangan</label>
                                        <p className="text-xs text-slate-400 -mt-1 mb-2">Minimal 10 karakter, mohon dijelaskan secara rinci.</p>
                                        <textarea
                                            id="keterangan"
                                            value={data.keterangan}
                                            onChange={(e) => setData('keterangan', e.target.value)}
                                            rows={4}
                                            placeholder="Jelaskan kronologi kejadian secara singkat dan jelas"
                                            className={`w-full px-4 py-3 text-base rounded-xl border border-slate-300 focus:border-brand-green focus:ring-2 focus:ring-brand-green/20 outline-none transition-colors ${focusRing}`}
                                        />
                                        {errors.keterangan && <p className="text-sm text-red-600 mt-1.5">{errors.keterangan}</p>}
                                    </div>

                                    <div className="mb-6">
                                        <label htmlFor="foto" className="block text-base font-semibold text-slate-700 mb-2">
                                            Dokumen Pendukung {sakit ? '(Surat Keterangan Rumah Sakit)' : '(Foto)'}
                                        </label>
                                        <p className="text-xs text-slate-400 -mt-1 mb-2">Wajib. Format JPG/PNG, maksimal 5 MB.</p>
                                        <label className={`flex items-center gap-3 p-4 rounded-xl border border-dashed border-slate-300 cursor-pointer hover:border-brand-green transition-colors ${focusRing}`}>
                                            <Camera size={20} className="text-slate-400 shrink-0" />
                                            <span className="text-sm text-slate-500 truncate">
                                                {data.foto ? data.foto.name : 'Pilih berkas gambar...'}
                                            </span>
                                            <input
                                                id="foto"
                                                type="file"
                                                accept="image/jpeg,image/png,image/jpg"
                                                onChange={(e) => setData('foto', e.target.files[0] ?? null)}
                                                className="hidden"
                                            />
                                        </label>
                                        {errors.foto && <p className="text-sm text-red-600 mt-1.5">{errors.foto}</p>}
                                    </div>

                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className={`w-full py-3.5 text-base font-bold rounded-2xl bg-brand-green text-white hover:bg-brand-green-dark transition-colors disabled:opacity-50 ${focusRing}`}
                                    >
                                        {processing ? 'Mengirim...' : 'Kirim Pengajuan'}
                                    </button>
                                </>
                            )}
                        </form>
                    )}
                </div>

                <div className="bg-white rounded-2xl border border-slate-100 p-6">
                    <p className="text-base font-bold text-slate-700 mb-4">Riwayat Pengajuan</p>

                    {riwayat.length === 0 ? (
                        <p className="text-base text-slate-400 text-center py-8">Belum ada riwayat pengajuan.</p>
                    ) : (
                        <div className="divide-y divide-slate-50">
                            {riwayat.map((r) => {
                                const Icon = statusIcon[r.status];
                                return (
                                    <div key={r.id} className="py-3.5">
                                        <div className="flex items-center justify-between mb-1 gap-2">
                                            <p className="text-base font-semibold text-slate-800">{r.jenis_label}</p>
                                            <span className={`flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold shrink-0 ${statusStyle[r.status]}`}>
                                                <Icon size={12} />
                                                {statusLabel[r.status]}
                                            </span>
                                        </div>
                                        <p className="text-sm text-slate-400">{r.tanggal_pengajuan} &bull; Kejadian {r.tanggal_kejadian}</p>
                                        {(r.nominal_bendahara || r.nominal_final) && (
                                            <p className="text-sm text-slate-500 mt-1">
                                                {r.nominal_bendahara ? `Usulan Bendahara ${formatRupiah(r.nominal_bendahara)}` : ''}
                                                {r.nominal_final ? ` → Disetujui ${formatRupiah(r.nominal_final)}` : ''}
                                            </p>
                                        )}
                                        {r.catatan_bendahara && (
                                            <p className="text-sm text-slate-500 mt-1 italic">"Bendahara: {r.catatan_bendahara}"</p>
                                        )}
                                        {r.catatan_ketua && (
                                            <p className="text-sm text-slate-500 mt-1 italic">"Ketua: {r.catatan_ketua}"</p>
                                        )}
                                    </div>
                                );
                            })}
                        </div>
                    )}
                </div>
            </div>
        </AnggotaLayout>
    );
}
