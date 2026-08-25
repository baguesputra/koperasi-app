import AnggotaLayout from '@/Layouts/AnggotaLayout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { ArrowLeft, Clock, CheckCircle2, XCircle } from 'lucide-react';

function formatRupiah(angka) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka);
}

const focusRing =
    'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-green focus-visible:ring-offset-2';

const statusStyle = {
    diajukan: 'bg-amber-50 text-amber-700',
    disetujui: 'bg-brand-green-light text-brand-green-dark',
    ditolak: 'bg-red-50 text-red-600',
};

const statusIcon = { diajukan: Clock, disetujui: CheckCircle2, ditolak: XCircle };
const statusLabel = { diajukan: 'Menunggu', disetujui: 'Disetujui', ditolak: 'Ditolak' };

export default function Create({ limitSaatIni, riwayat }) {
    const { props } = usePage();
    const { data, setData, post, processing, errors, reset } = useForm({
        limit_diminta: '', keterangan: '',
    });

    const adaPengajuanMenunggu = riwayat.some((r) => r.status === 'diajukan');

    function submit(e) {
        e.preventDefault();
        post(route('portal.pengajuan-limit.store'), { onSuccess: () => reset() });
    }

    return (
        <AnggotaLayout>
            <Head title="Ajukan Tambah Limit" />

            <Link
                href={route('portal.dashboard')}
                className={`inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 hover:text-brand-navy mb-5 rounded ${focusRing}`}
            >
                <ArrowLeft size={16} />
                Kembali ke Beranda
            </Link>

            <div className="mb-6">
                <h1 className="text-xl sm:text-2xl font-bold text-slate-800">Ajukan Tambah Limit</h1>
                <p className="text-base text-slate-400 mt-1">
                    Limit pinjaman Anda saat ini: <span className="font-semibold text-slate-600">{formatRupiah(limitSaatIni)}</span>
                </p>
            </div>

            {props.flash.status && (
                <div className="mb-5 flex items-start gap-3 bg-brand-green-light border border-brand-green/25 rounded-xl p-4">
                    <CheckCircle2 size={20} className="text-brand-green-dark shrink-0 mt-0.5" />
                    <p className="text-sm font-semibold text-brand-green-dark">{props.flash.status}</p>
                </div>
            )}

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-5">
                <div className="bg-white rounded-2xl border border-slate-100 p-6">
                    {adaPengajuanMenunggu ? (
                        <div className="flex items-start gap-3 bg-amber-50 border border-amber-100 rounded-xl p-4">
                            <Clock className="text-amber-600 shrink-0 mt-0.5" size={20} />
                            <div>
                                <p className="text-sm text-amber-800">
                                    Anda masih memiliki pengajuan limit yang belum diproses. Tunggu keputusannya sebelum mengajukan lagi.
                                </p>
                                <p className="text-sm text-amber-700 mt-1.5">
                                    Keputusan akan tampil di Beranda (kartu Limit Tersedia) dan di riwayat di bawah.
                                </p>
                            </div>
                        </div>
                    ) : (
                        <form onSubmit={submit}>
                            <div className="mb-5">
                                <label htmlFor="limit-diminta" className="block text-base font-semibold text-slate-700 mb-2">
                                    Limit yang Diminta
                                </label>
                                <div className="relative">
                                    <span className="absolute left-4 top-1/2 -translate-y-1/2 text-lg font-semibold text-slate-400">Rp</span>
                                    <input
                                        id="limit-diminta"
                                        type="number"
                                        value={data.limit_diminta}
                                        onChange={(e) => setData('limit_diminta', e.target.value)}
                                        placeholder="0"
                                        className={`w-full pl-12 pr-4 py-3 text-xl font-bold rounded-xl border border-slate-300 focus:border-brand-green focus:ring-2 focus:ring-brand-green/20 outline-none transition-colors ${focusRing}`}
                                    />
                                </div>
                                {errors.limit_diminta && <p className="text-sm text-red-600 mt-1.5">{errors.limit_diminta}</p>}
                            </div>

                            <div className="mb-6">
                                <label htmlFor="keterangan" className="block text-base font-semibold text-slate-700 mb-2">
                                    Alasan / Keterangan
                                </label>
                                <p className="text-xs text-slate-400 -mt-1 mb-2">Minimal 10 karakter.</p>
                                <textarea
                                    id="keterangan"
                                    value={data.keterangan}
                                    onChange={(e) => setData('keterangan', e.target.value)}
                                    rows={4}
                                    placeholder="Jelaskan alasan Anda membutuhkan limit lebih besar"
                                    className={`w-full px-4 py-3 text-base rounded-xl border border-slate-300 focus:border-brand-green focus:ring-2 focus:ring-brand-green/20 outline-none transition-colors ${focusRing}`}
                                />
                                {errors.keterangan && <p className="text-sm text-red-600 mt-1.5">{errors.keterangan}</p>}
                            </div>

                            <button
                                type="submit"
                                disabled={processing}
                                className={`w-full py-3.5 text-base font-bold rounded-2xl bg-brand-green text-white hover:bg-brand-green-dark transition-colors disabled:opacity-50 ${focusRing}`}
                            >
                                {processing ? 'Mengirim...' : 'Kirim Pengajuan'}
                            </button>
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
                                        <div className="flex items-center justify-between mb-1">
                                            <p className="text-base font-semibold text-slate-800">{formatRupiah(r.limit_diminta)}</p>
                                            <span className={`flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold ${statusStyle[r.status]}`}>
                                                <Icon size={12} />
                                                {statusLabel[r.status]}
                                            </span>
                                        </div>
                                        <p className="text-sm text-slate-400">{r.tanggal_pengajuan}</p>
                                        {r.catatan_ketua && (
                                            <p className="text-sm text-slate-500 mt-1 italic">"{r.catatan_ketua}"</p>
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