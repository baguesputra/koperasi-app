import AnggotaLayout from '@/Layouts/AnggotaLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { ArrowLeft, ArrowRight, AlertCircle, Check } from 'lucide-react';

function formatRupiah(angka) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka);
}

const TOTAL_STEP = 3;

export default function Create({ bisaAjukan, alasanTidakBisa, limitMaksimal }) {
    const [step, setStep] = useState(1);
    const [nominal, setNominal] = useState('');
    const [tenorMaksimal, setTenorMaksimal] = useState(null);
    const [tenorDipilih, setTenorDipilih] = useState(null);
    const [jadwal, setJadwal] = useState([]);
    const [totalDibayar, setTotalDibayar] = useState(0);
    const [error, setError] = useState('');
    const [loading, setLoading] = useState(false);

    if (!bisaAjukan) {
        return (
            <AnggotaLayout>
                <Head title="Ajukan Pinjaman" />
                <div className="flex items-start gap-3 bg-amber-50 border border-amber-100 rounded-2xl p-5 max-w-xl">
                    <AlertCircle className="text-amber-600 shrink-0 mt-0.5" size={22} />
                    <div>
                        <p className="text-base font-semibold text-amber-800 mb-1">Belum bisa mengajukan pinjaman</p>
                        <p className="text-sm text-amber-700">{alasanTidakBisa}</p>
                    </div>
                </div>
                <Link href={route('portal.dashboard')} className="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 hover:text-brand-navy mt-5">
                    <ArrowLeft size={16} />
                    Kembali ke Beranda
                </Link>
            </AnggotaLayout>
        );
    }

    async function lanjutDariNominal() {
        setError('');
        if (!nominal || Number(nominal) <= 0) {
            setError('Masukkan nominal pinjaman terlebih dahulu.');
            return;
        }

        setLoading(true);
        try {
            const res = await window.axios.post(route('portal.pinjaman.cek-nominal'), { nominal });
            setTenorMaksimal(res.data.tenor_maksimal);
            setStep(2);
        } catch (e) {
            setError(e.response?.data?.pesan ?? 'Terjadi kesalahan, coba lagi.');
        } finally {
            setLoading(false);
        }
    }

    async function lanjutDariTenor() {
        setError('');
        if (!tenorDipilih) {
            setError('Pilih tenor terlebih dahulu.');
            return;
        }

        setLoading(true);
        try {
            const res = await window.axios.post(route('portal.pinjaman.simulasi'), {
                nominal,
                tenor_bulan: tenorDipilih,
            });
            setJadwal(res.data.jadwal);
            setTotalDibayar(res.data.total_dibayar);
            setStep(3);
        } catch (e) {
            setError('Terjadi kesalahan, coba lagi.');
        } finally {
            setLoading(false);
        }
    }

    function submitPengajuan() {
        setLoading(true);
        router.post(
            route('portal.pinjaman.store'),
            { nominal, tenor_bulan: tenorDipilih },
            {
                onError: (errors) => {
                    setError(errors.pengajuan ?? 'Terjadi kesalahan, coba lagi.');
                    setLoading(false);
                },
            }
        );
    }

    return (
        <AnggotaLayout>
            <Head title="Ajukan Pinjaman" />

            <div className="max-w-xl">
                <Link href={route('portal.dashboard')} className="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 hover:text-brand-navy mb-5">
                    <ArrowLeft size={16} />
                    Batal
                </Link>

                {/* Indikator step */}
                <div className="flex items-center gap-2 mb-6">
                    {[1, 2, 3].map((s) => (
                        <div key={s} className="flex items-center flex-1">
                            <div className={`w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold shrink-0 ${
                                s < step ? 'bg-brand-green text-white' : s === step ? 'bg-brand-navy text-white' : 'bg-slate-200 text-slate-400'
                            }`}>
                                {s < step ? <Check size={16} /> : s}
                            </div>
                            {s < TOTAL_STEP && (
                                <div className={`flex-1 h-1 mx-1.5 rounded ${s < step ? 'bg-brand-green' : 'bg-slate-200'}`} />
                            )}
                        </div>
                    ))}
                </div>

                {error && (
                    <div className="flex items-start gap-2.5 bg-red-50 border border-red-100 rounded-xl p-4 mb-5">
                        <AlertCircle className="text-red-500 shrink-0 mt-0.5" size={18} />
                        <p className="text-sm text-red-700">{error}</p>
                    </div>
                )}

                {step === 1 && (
                    <StepNominal
                        nominal={nominal}
                        setNominal={setNominal}
                        limitMaksimal={limitMaksimal}
                        onLanjut={lanjutDariNominal}
                        loading={loading}
                    />
                )}

                {step === 2 && (
                    <StepTenor
                        nominal={nominal}
                        tenorMaksimal={tenorMaksimal}
                        tenorDipilih={tenorDipilih}
                        setTenorDipilih={setTenorDipilih}
                        onKembali={() => { setStep(1); setError(''); }}
                        onLanjut={lanjutDariTenor}
                        loading={loading}
                    />
                )}

                {step === 3 && (
                    <StepRingkasan
                        nominal={nominal}
                        tenorDipilih={tenorDipilih}
                        jadwal={jadwal}
                        totalDibayar={totalDibayar}
                        onKembali={() => { setStep(2); setError(''); }}
                        onSubmit={submitPengajuan}
                        loading={loading}
                    />
                )}
            </div>
        </AnggotaLayout>
    );
}

function StepNominal({ nominal, setNominal, limitMaksimal, onLanjut, loading }) {
    return (
        <div className="bg-white rounded-2xl border border-slate-100 p-6">
            <p className="text-sm font-semibold text-brand-green mb-1">Langkah 1 dari 3</p>
            <h2 className="text-xl font-bold text-slate-800 mb-2">Berapa nominal yang ingin dipinjam?</h2>
            <p className="text-sm text-slate-400 mb-5">
                Limit maksimal Anda: <span className="font-semibold text-slate-600">{formatRupiah(limitMaksimal)}</span>
            </p>

            <div className="relative mb-6">
                <span className="absolute left-4 top-1/2 -translate-y-1/2 text-lg font-semibold text-slate-400">Rp</span>
                <input
                    type="number"
                    value={nominal}
                    onChange={(e) => setNominal(e.target.value)}
                    placeholder="0"
                    autoFocus
                    className="w-full pl-12 pr-4 py-4 text-2xl font-bold rounded-xl border border-slate-300 focus:border-brand-green focus:ring-2 focus:ring-brand-green/20 outline-none transition-colors"
                />
            </div>

            <button
                onClick={onLanjut}
                disabled={loading}
                className="w-full flex items-center justify-center gap-2 py-4 text-base font-bold rounded-2xl bg-brand-green text-white hover:bg-brand-green-dark transition-colors disabled:opacity-50"
            >
                {loading ? 'Memeriksa...' : 'Lanjutkan'}
                {!loading && <ArrowRight size={18} />}
            </button>
        </div>
    );
}

function StepTenor({ nominal, tenorMaksimal, tenorDipilih, setTenorDipilih, onKembali, onLanjut, loading }) {
    const opsiTenor = Array.from({ length: tenorMaksimal }, (_, i) => i + 1);

    return (
        <div className="bg-white rounded-2xl border border-slate-100 p-6">
            <p className="text-sm font-semibold text-brand-green mb-1">Langkah 2 dari 3</p>
            <h2 className="text-xl font-bold text-slate-800 mb-2">Pilih lama cicilan (tenor)</h2>
            <p className="text-sm text-slate-400 mb-5">
                Untuk pinjaman {formatRupiah(nominal)}, tenor maksimal {tenorMaksimal} bulan
            </p>

            <div className="grid grid-cols-3 sm:grid-cols-4 gap-2.5 mb-6">
                {opsiTenor.map((bulan) => (
                    <button
                        key={bulan}
                        onClick={() => setTenorDipilih(bulan)}
                        className={`py-3.5 rounded-xl text-base font-bold border-2 transition-colors ${
                            tenorDipilih === bulan
                                ? 'border-brand-green bg-brand-green-light text-brand-green-dark'
                                : 'border-slate-200 text-slate-600 hover:border-slate-300'
                        }`}
                    >
                        {bulan} bln
                    </button>
                ))}
            </div>

            <div className="flex items-center gap-3">
                <button
                    onClick={onKembali}
                    className="px-6 py-4 text-base font-bold rounded-2xl border border-slate-300 text-slate-600 hover:bg-slate-50 transition-colors"
                >
                    Kembali
                </button>
                <button
                    onClick={onLanjut}
                    disabled={loading}
                    className="flex-1 flex items-center justify-center gap-2 py-4 text-base font-bold rounded-2xl bg-brand-green text-white hover:bg-brand-green-dark transition-colors disabled:opacity-50"
                >
                    {loading ? 'Menghitung...' : 'Lanjutkan'}
                    {!loading && <ArrowRight size={18} />}
                </button>
            </div>
        </div>
    );
}

function StepRingkasan({ nominal, tenorDipilih, jadwal, totalDibayar, onKembali, onSubmit, loading }) {
    return (
        <div className="bg-white rounded-2xl border border-slate-100 p-6">
            <p className="text-sm font-semibold text-brand-green mb-1">Langkah 3 dari 3</p>
            <h2 className="text-xl font-bold text-slate-800 mb-5">Periksa kembali pengajuan Anda</h2>

            <div className="bg-slate-50 rounded-xl p-4 mb-5 space-y-2.5">
                <div className="flex items-center justify-between">
                    <span className="text-sm text-slate-500">Nominal Pinjaman</span>
                    <span className="text-base font-bold text-slate-800">{formatRupiah(nominal)}</span>
                </div>
                <div className="flex items-center justify-between">
                    <span className="text-sm text-slate-500">Tenor</span>
                    <span className="text-base font-bold text-slate-800">{tenorDipilih} bulan</span>
                </div>
                <div className="flex items-center justify-between pt-2.5 border-t border-slate-200">
                    <span className="text-sm text-slate-500">Total yang Dibayar</span>
                    <span className="text-base font-bold text-brand-navy">{formatRupiah(totalDibayar)}</span>
                </div>
            </div>

            <p className="text-sm font-semibold text-slate-600 mb-3">Simulasi cicilan per bulan:</p>
            <div className="divide-y divide-slate-50 mb-6 max-h-64 overflow-y-auto border border-slate-100 rounded-xl">
                {jadwal.map((c) => (
                    <div key={c.cicilan_ke} className="flex items-center justify-between px-4 py-3">
                        <span className="text-sm text-slate-500">Cicilan ke-{c.cicilan_ke}</span>
                        <span className="text-base font-semibold text-slate-800">{formatRupiah(c.total_bayar)}</span>
                    </div>
                ))}
            </div>

            <div className="flex items-center gap-3">
                <button
                    onClick={onKembali}
                    className="px-6 py-4 text-base font-bold rounded-2xl border border-slate-300 text-slate-600 hover:bg-slate-50 transition-colors"
                >
                    Kembali
                </button>
                <button
                    onClick={onSubmit}
                    disabled={loading}
                    className="flex-1 py-4 text-base font-bold rounded-2xl bg-brand-green text-white hover:bg-brand-green-dark transition-colors disabled:opacity-50"
                >
                    {loading ? 'Mengirim...' : 'Ajukan Sekarang'}
                </button>
            </div>
        </div>
    );
}