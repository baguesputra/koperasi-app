import AnggotaLayout from '@/Layouts/AnggotaLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import { ArrowLeft, TrendingDown, TrendingUp, CheckCircle2, AlertCircle } from 'lucide-react';

function formatRupiah(angka) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka);
}

const opsi = [
    { tipe: 'percepat', label: 'Percepat Pelunasan', desc: 'Kurangi tenor, cicilan lebih besar per bulan', icon: TrendingDown },
    { tipe: 'perpanjang', label: 'Perpanjang Tenor', desc: 'Tambah tenor, cicilan lebih ringan per bulan', icon: TrendingUp },
    { tipe: 'lunas_total', label: 'Lunas Sekarang', desc: 'Bayar seluruh sisa pokok sekaligus, bunga hanya 1 bulan', icon: CheckCircle2 },
];

export default function Create({ pinjaman }) {
    const [tipeDipilih, setTipeDipilih] = useState(null);
    const [preview, setPreview] = useState(null);
    const [loadingPreview, setLoadingPreview] = useState(false);
    const { data, setData, post, processing, errors } = useForm({
        tipe: '', tenor_baru: '', keterangan: '',
    });

    useEffect(() => {
        setPreview(null);

        if (!data.tipe) return;
        if ((data.tipe === 'percepat' || data.tipe === 'perpanjang') && !data.tenor_baru) return;

        const timeout = setTimeout(async () => {
            setLoadingPreview(true);
            try {
                const res = await window.axios.post(route('portal.percepatan.preview'), {
                    tipe: data.tipe,
                    tenor_baru: data.tenor_baru || null,
                });
                setPreview(res.data);
            } catch (e) {
                setPreview(null);
            } finally {
                setLoadingPreview(false);
            }
        }, 500);

        return () => clearTimeout(timeout);
    }, [data.tipe, data.tenor_baru]);

    if (!pinjaman) {
        return (
            <AnggotaLayout>
                <Head title="Perubahan Tenor" />
                <div className="flex items-start gap-3 bg-amber-50 border border-amber-100 rounded-2xl p-5 max-w-xl">
                    <AlertCircle className="text-amber-600 shrink-0 mt-0.5" size={22} />
                    <p className="text-sm text-amber-800">Anda tidak memiliki pinjaman aktif saat ini.</p>
                </div>
                <Link href={route('portal.dashboard')} className="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 hover:text-brand-navy mt-5">
                    <ArrowLeft size={16} />
                    Kembali ke Beranda
                </Link>
            </AnggotaLayout>
        );
    }

    if (pinjaman.sudah_pakai_percepatan) {
        return (
            <AnggotaLayout>
                <Head title="Perubahan Tenor" />
                <div className="flex items-start gap-3 bg-amber-50 border border-amber-100 rounded-2xl p-5 max-w-xl">
                    <AlertCircle className="text-amber-600 shrink-0 mt-0.5" size={22} />
                    <p className="text-sm text-amber-800">Pinjaman ini sudah pernah menggunakan hak perubahan tenor/pelunasan dipercepat.</p>
                </div>
                <Link href={route('portal.dashboard')} className="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 hover:text-brand-navy mt-5">
                    <ArrowLeft size={16} />
                    Kembali ke Beranda
                </Link>
            </AnggotaLayout>
        );
    }

    function pilihTipe(tipe) {
        setTipeDipilih(tipe);
        setData({ ...data, tipe, tenor_baru: '' });
    }

    function submit(e) {
        e.preventDefault();
        post(route('portal.percepatan.store'));
    }

    return (
        <AnggotaLayout>
            <Head title="Ajukan Perubahan Tenor" />

            <Link href={route('portal.dashboard')} className="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 hover:text-brand-navy mb-5">
                <ArrowLeft size={16} />
                Kembali ke Beranda
            </Link>

            <div className="mb-6">
                <h1 className="text-xl sm:text-2xl font-bold text-slate-800">Ajukan Perubahan Tenor</h1>
                <p className="text-base text-slate-400 mt-1">
                    Pinjaman aktif: {formatRupiah(pinjaman.nominal)} &bull; Tenor {pinjaman.tenor_bulan} bulan &bull; Sisa {pinjaman.sisa_angsuran} cicilan
                </p>
            </div>

            <div className="max-w-2xl">
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-5">
                    {opsi.map((o) => {
                        const Icon = o.icon;
                        const aktif = tipeDipilih === o.tipe;
                        return (
                            <button
                                key={o.tipe}
                                onClick={() => pilihTipe(o.tipe)}
                                className={`text-left p-4 rounded-2xl border-2 transition-colors ${
                                    aktif ? 'border-brand-green bg-brand-green-light' : 'border-slate-200 bg-white hover:border-slate-300'
                                }`}
                            >
                                <Icon size={22} className={aktif ? 'text-brand-green-dark' : 'text-slate-400'} />
                                <p className="text-sm font-bold text-slate-800 mt-2">{o.label}</p>
                                <p className="text-xs text-slate-500 mt-1">{o.desc}</p>
                            </button>
                        );
                    })}
                </div>

                {tipeDipilih && (
                    <form onSubmit={submit} className="bg-white rounded-2xl border border-slate-100 p-6">
                        {(tipeDipilih === 'percepat' || tipeDipilih === 'perpanjang') && (
                            <div className="mb-5">
                                <label className="block text-base font-semibold text-slate-700 mb-2">Tenor Baru (bulan)</label>
                                <input
                                    type="number"
                                    value={data.tenor_baru}
                                    onChange={(e) => setData('tenor_baru', e.target.value)}
                                    placeholder={tipeDipilih === 'percepat' ? `Kurang dari ${pinjaman.tenor_bulan} bulan` : `Lebih dari ${pinjaman.tenor_bulan} bulan`}
                                    className="w-full px-4 py-3 text-base rounded-xl border border-slate-300 focus:border-brand-green focus:ring-2 focus:ring-brand-green/20 outline-none transition-colors"
                                    autoFocus
                                />
                                {errors.tenor_baru && <p className="text-sm text-red-600 mt-1.5">{errors.tenor_baru}</p>}
                            </div>
                        )}

                        {loadingPreview && (
                            <div className="mb-5 flex items-center gap-2 text-sm text-slate-400">
                                <div className="w-4 h-4 border-2 border-brand-green border-t-transparent rounded-full animate-spin" />
                                Menghitung simulasi...
                            </div>
                        )}

                        {preview && !loadingPreview && (
                            <div className="mb-5 bg-slate-50 rounded-xl p-4">
                                <p className="text-sm font-semibold text-slate-600 mb-2">Simulasi Perhitungan</p>
                                <p className="text-sm text-slate-500 mb-3">
                                    Sisa Pokok Saat Ini: <span className="font-semibold text-slate-700">{formatRupiah(preview.sisa_pokok)}</span>
                                </p>

                                {tipeDipilih === 'lunas_total' ? (
                                    <div className="bg-white rounded-lg p-3 border border-slate-200">
                                        <div className="flex justify-between text-sm mb-1">
                                            <span className="text-slate-500">Bunga (1 bulan)</span>
                                            <span className="font-semibold text-slate-700">{formatRupiah(preview.bunga)}</span>
                                        </div>
                                        <div className="flex justify-between pt-1.5 border-t border-slate-100">
                                            <span className="text-sm font-semibold text-slate-700">Total Harus Dibayar</span>
                                            <span className="text-lg font-bold text-brand-navy">{formatRupiah(preview.total_bayar)}</span>
                                        </div>
                                    </div>
                                ) : (
                                    <div className="max-h-48 overflow-y-auto divide-y divide-slate-200 border border-slate-200 rounded-lg bg-white">
                                        {preview.jadwal?.map((c) => (
                                            <div key={c.cicilan_ke} className="flex justify-between px-3 py-2 text-sm">
                                                <span className="text-slate-500">Cicilan ke-{c.cicilan_ke}</span>
                                                <span className="font-semibold text-slate-700">{formatRupiah(c.total_bayar)}</span>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>
                        )}

                        <div className="mb-6">
                            <label className="block text-base font-semibold text-slate-700 mb-2">Alasan Pengajuan</label>
                            <textarea
                                value={data.keterangan}
                                onChange={(e) => setData('keterangan', e.target.value)}
                                rows={3}
                                placeholder="Jelaskan alasan Anda mengajukan perubahan ini"
                                className="w-full px-4 py-3 text-base rounded-xl border border-slate-300 focus:border-brand-green focus:ring-2 focus:ring-brand-green/20 outline-none transition-colors"
                            />
                            {errors.keterangan && <p className="text-sm text-red-600 mt-1.5">{errors.keterangan}</p>}
                        </div>

                        {errors.tipe && (
                            <div className="mb-4 flex items-start gap-2.5 bg-red-50 border border-red-100 rounded-xl p-4">
                                <AlertCircle className="text-red-500 shrink-0 mt-0.5" size={18} />
                                <p className="text-sm text-red-700">{errors.tipe}</p>
                            </div>
                        )}

                        <button
                            type="submit"
                            disabled={processing}
                            className="w-full py-3.5 text-base font-bold rounded-2xl bg-brand-green text-white hover:bg-brand-green-dark transition-colors disabled:opacity-50"
                        >
                            {processing ? 'Mengirim...' : 'Kirim Pengajuan'}
                        </button>
                    </form>
                )}
            </div>
        </AnggotaLayout>
    );
}