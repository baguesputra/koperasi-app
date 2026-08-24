import AnggotaLayout from '@/Layouts/AnggotaLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useState, useEffect, useRef } from 'react';
import {
    ArrowLeft, AlertCircle, Check, ShieldCheck,
    Phone, Clock, Wallet, X, FileText,
} from 'lucide-react';
import { formatRupiah } from '@/Utils/formatCurrency';
import { withIdempotencyKey } from '@/Utils/idempotency';

const focusRing =
    'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-green focus-visible:ring-offset-2';

export default function Create({ bisaAjukan, alasanTidakBisa, limitMaksimal, limitTersedia, sisaAngsuranAktif, cicilanPokokAktif, rekeningTersimpan, poinSyarat = [], versiSyarat = '' }) {
    const [nominal, setNominal] = useState('');
    const [nominalValid, setNominalValid] = useState(false);
    const [tenorMaksimal, setTenorMaksimal] = useState(null);
    const [tenorDipilih, setTenorDipilih] = useState(null);
    const [jadwal, setJadwal] = useState([]);
    const [totalDibayar, setTotalDibayar] = useState(0);
    const [error, setError] = useState('');
    const [loadingNominal, setLoadingNominal] = useState(false);
    const [loadingSubmit, setLoadingSubmit] = useState(false);

    const [keperluan, setKeperluan] = useState('');
    const [rekeningMode, setRekeningMode] = useState(rekeningTersimpan.length > 0 ? 'tersimpan' : 'baru');
    const [rekeningId, setRekeningId] = useState(rekeningTersimpan.find((r) => r.is_default)?.id ?? rekeningTersimpan[0]?.id ?? null);
    const [namaBank, setNamaBank] = useState('');
    const [noRekening, setNoRekening] = useState('');
    const [atasNama, setAtasNama] = useState('');

    const [showModalPersetujuan, setShowModalPersetujuan] = useState(false);
    const [setujuSyarat, setSetujuSyarat] = useState(false);

    const tenorRef = useRef(null);
    const ringkasanRef = useRef(null);

    if (!bisaAjukan) {
        return (
            <AnggotaLayout>
                <Head title="Ajukan Pinjaman" />
                <div className="max-w-2xl mx-auto flex items-start gap-3 bg-amber-50 border border-amber-100 rounded-2xl p-5">
                    <AlertCircle className="text-amber-600 shrink-0 mt-0.5" size={22} />
                    <div>
                        <p className="text-base font-semibold text-amber-800 mb-1">Belum bisa mengajukan pinjaman</p>
                        <p className="text-sm text-amber-700">{alasanTidakBisa}</p>
                    </div>
                </div>
                <Link href={route('portal.dashboard')} className="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 hover:text-brand-navy mt-5 max-w-2xl mx-auto">
                    <ArrowLeft size={16} />
                    Kembali ke Beranda
                </Link>
            </AnggotaLayout>
        );
    }

    useEffect(() => {
        if (!nominal || Number(nominal) <= 0) {
            setNominalValid(false);
            setTenorMaksimal(null);
            setTenorDipilih(null);
            return;
        }

        setError('');
        const timeout = setTimeout(async () => {
            setLoadingNominal(true);
            try {
                const res = await window.axios.post(route('portal.pinjaman.cek-nominal'), { nominal });
                setTenorMaksimal(res.data.tenor_maksimal);
                setNominalValid(true);
                setTimeout(() => tenorRef.current?.scrollIntoView({ behavior: 'smooth', block: 'center' }), 100);
            } catch (e) {
                setNominalValid(false);
                setTenorMaksimal(null);
                setError(e.response?.data?.pesan ?? 'Terjadi kesalahan, coba lagi.');
            } finally {
                setLoadingNominal(false);
            }
        }, 600);

        return () => clearTimeout(timeout);
    }, [nominal]);

    async function pilihTenor(bulan) {
        setTenorDipilih(bulan);
        try {
            const res = await window.axios.post(route('portal.pinjaman.simulasi'), {
                nominal, tenor_bulan: bulan,
            });
            setJadwal(res.data.jadwal);
            setTotalDibayar(res.data.total_dibayar);
            setTimeout(() => ringkasanRef.current?.scrollIntoView({ behavior: 'smooth', block: 'start' }), 100);
        } catch (e) {
            setError('Terjadi kesalahan menghitung simulasi.');
        }
    }

    function submitPengajuan() {
        setError('');
        setShowModalPersetujuan(true);
        setSetujuSyarat(false);
    }

function kirimPengajuan() {
        if (!setujuSyarat) return;
        setLoadingSubmit(true);
        router.post(
            route('portal.pinjaman.store'),
            {
                nominal,
                tenor_bulan: tenorDipilih,
                keperluan,
                rekening_mode: rekeningMode,
                rekening_id: rekeningId,
                nama_bank: namaBank,
                no_rekening: noRekening,
                atas_nama: atasNama,
                persetujuan: true,
            },
            withIdempotencyKey({
                onError: (errors) => {
                    setError(Object.values(errors)[0] ?? 'Terjadi kesalahan, coba lagi.');
                    setLoadingSubmit(false);
                    setShowModalPersetujuan(false);
                    setSetujuSyarat(false);
                },
                onFinish: () => {
                    setLoadingSubmit(false);
                },
            })
        );
    }

    const opsiTenor = tenorMaksimal ? Array.from({ length: tenorMaksimal }, (_, i) => i + 1) : [];

    return (
        <AnggotaLayout>
            <Head title="Ajukan Pinjaman" />

            <Link href={route('portal.dashboard')} className="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 hover:text-brand-navy mb-5">
                <ArrowLeft size={16} />
                Batal, kembali ke Beranda
            </Link>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div className="lg:col-span-2 space-y-5">
                    {error && (
                        <div className="flex items-start gap-2.5 bg-red-50 border border-red-100 rounded-xl p-4">
                            <AlertCircle className="text-red-500 shrink-0 mt-0.5" size={18} />
                            <p className="text-sm text-red-700">{error}</p>
                        </div>
                    )}

                    <div className="bg-white rounded-2xl border border-slate-100 p-6">
                        <p className="text-base font-bold text-slate-800 mb-1">Berapa nominal yang ingin dipinjam?</p>
                        {sisaAngsuranAktif > 0 ? (
                            <div className="bg-amber-50 border border-amber-200 rounded-xl p-3 mb-4">
                                <p className="text-xs font-semibold text-amber-800 mb-1">Pengajuan Lebih Awal (Reloan)</p>
                                <p className="text-xs text-amber-700 leading-relaxed">
                                    Pinjaman aktif Anda masih berjalan ({sisaAngsuranAktif} cicilan × {formatRupiah(cicilanPokokAktif)} = {formatRupiah(sisaAngsuranAktif * cicilanPokokAktif)}).
                                    Limit efektif dikurangi sisa cicilan pokok yang berjalan.
                                </p>
                                <div className="flex items-center justify-between mt-2 pt-2 border-t border-amber-200">
                                    <span className="text-xs text-amber-700">Limit tersedia Anda:</span>
                                    <span className="text-sm font-bold text-amber-900">{formatRupiah(limitTersedia)}</span>
                                </div>
                                <p className="text-xs text-amber-600 mt-1">
                                    (Limit kategori: {formatRupiah(limitMaksimal)})
                                </p>
                            </div>
                        ) : (
                            <p className="text-sm text-slate-400 mb-4">
                                Limit maksimal Anda: <span className="font-semibold text-slate-600">{formatRupiah(limitMaksimal)}</span>
                            </p>
                        )}

                        <div className="relative">
                            <span className="absolute left-4 top-1/2 -translate-y-1/2 text-lg font-semibold text-slate-400">Rp</span>
                            <input
                                type="number"
                                value={nominal}
                                onChange={(e) => setNominal(e.target.value)}
                                placeholder="0"
                                autoFocus
                                max={limitTersedia}
                                className="w-full pl-12 pr-12 py-4 text-2xl font-bold rounded-xl border border-slate-300 focus:border-brand-green focus:ring-2 focus:ring-brand-green/20 outline-none transition-colors"
                            />
                            {loadingNominal && (
                                <div className="absolute right-4 top-1/2 -translate-y-1/2 w-5 h-5 border-2 border-brand-green border-t-transparent rounded-full animate-spin" />
                            )}
                            {nominalValid && !loadingNominal && (
                                <Check className="absolute right-4 top-1/2 -translate-y-1/2 text-brand-green" size={22} />
                            )}
                        </div>
                        <p className="text-xs text-slate-400 mt-2">Maksimal: {formatRupiah(limitTersedia)}</p>
                    </div>

                    {nominalValid && tenorMaksimal && (
                        <div ref={tenorRef} className="bg-white rounded-2xl border border-slate-100 p-6 animate-in fade-in slide-in-from-top-2 duration-300">
                            <p className="text-base font-bold text-slate-800 mb-1">Pilih lama cicilan (tenor)</p>
                            <p className="text-sm text-slate-400 mb-4">
                                Tenor maksimal untuk nominal ini: {tenorMaksimal} bulan
                            </p>

                            <div className="grid grid-cols-4 sm:grid-cols-6 gap-2.5">
                                {opsiTenor.map((bulan) => (
                                    <button
                                        key={bulan}
                                        onClick={() => pilihTenor(bulan)}
                                        className={`py-3 rounded-xl text-base font-bold border-2 transition-colors ${
                                            tenorDipilih === bulan
                                                ? 'border-brand-green bg-brand-green-light text-brand-green-dark'
                                                : 'border-slate-200 text-slate-600 hover:border-slate-300'
                                        }`}
                                    >
                                        {bulan}
                                    </button>
                                ))}
                            </div>
                        </div>
                    )}

                    {tenorDipilih && jadwal.length > 0 && (
                        <div ref={ringkasanRef} className="bg-white rounded-2xl border border-slate-100 p-6 animate-in fade-in slide-in-from-top-2 duration-300">
                            <StepRingkasan
                                nominal={nominal}
                                tenorDipilih={tenorDipilih}
                                jadwal={jadwal}
                                totalDibayar={totalDibayar}
                                keperluan={keperluan}
                                setKeperluan={setKeperluan}
                                rekeningTersimpan={rekeningTersimpan}
                                rekeningMode={rekeningMode}
                                setRekeningMode={setRekeningMode}
                                rekeningId={rekeningId}
                                setRekeningId={setRekeningId}
                                namaBank={namaBank}
                                setNamaBank={setNamaBank}
                                noRekening={noRekening}
                                setNoRekening={setNoRekening}
                                atasNama={atasNama}
                                setAtasNama={setAtasNama}
                                onSubmit={submitPengajuan}
                                loading={loadingSubmit}
                            />
                        </div>
                    )}
                </div>

                <div className="space-y-4">
                    <div className="bg-brand-navy rounded-2xl p-5 text-white">
                        <div className="flex items-center gap-2 mb-3">
                            <ShieldCheck size={20} className="text-brand-green" />
                            <p className="text-sm font-bold">Proses Persetujuan</p>
                        </div>
                        <div className="space-y-3">
                            <div className="flex gap-3">
                                <div className="w-6 h-6 rounded-full bg-white/15 flex items-center justify-center text-xs font-bold shrink-0">1</div>
                                <p className="text-sm text-slate-300">Pengajuan ditinjau oleh Bendahara Koperasi</p>
                            </div>
                            <div className="flex gap-3">
                                <div className="w-6 h-6 rounded-full bg-white/15 flex items-center justify-center text-xs font-bold shrink-0">2</div>
                                <p className="text-sm text-slate-300">Disetujui final oleh Ketua Koperasi</p>
                            </div>
                            <div className="flex gap-3">
                                <div className="w-6 h-6 rounded-full bg-brand-green/20 flex items-center justify-center text-xs font-bold shrink-0">
                                    <Wallet size={12} className="text-brand-green" />
                                </div>
                                <p className="text-sm text-slate-300">Dana dicairkan &amp; jadwal cicilan aktif</p>
                            </div>
                        </div>
                    </div>

                    <div className="bg-white rounded-2xl border border-slate-100 p-5">
                        <div className="flex items-center gap-2 mb-2">
                            <Clock size={18} className="text-amber-500" />
                            <p className="text-sm font-bold text-slate-700">Tidak Ada Batas Waktu</p>
                        </div>
                        <p className="text-sm text-slate-500">
                            Proses peninjauan tidak memiliki batas waktu pasti, tergantung antrean pengajuan yang masuk.
                        </p>
                    </div>

                    <div className="bg-white rounded-2xl border border-slate-100 p-5">
                        <div className="flex items-center gap-2 mb-2">
                            <Phone size={18} className="text-brand-navy" />
                            <p className="text-sm font-bold text-slate-700">Ada Pertanyaan?</p>
                        </div>
                        <p className="text-sm text-slate-500">
                            Hubungi Bendahara Koperasi untuk informasi lebih lanjut mengenai status pengajuan Anda.
                        </p>
                    </div>
                </div>
            </div>

            {showModalPersetujuan && (
                <div className="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm overflow-y-auto animate-in fade-in duration-200">
                    <div className="min-h-full flex items-start sm:items-center justify-center p-4">
                        <div
                            className="bg-white rounded-2xl w-full max-w-2xl my-8 shadow-2xl"
                            role="dialog"
                            aria-modal="true"
                            aria-labelledby="sk-title"
                        >
                            <div className="sticky top-0 bg-white border-b border-slate-100 px-6 py-5 flex items-start justify-between gap-4 rounded-t-2xl z-10">
                                <div className="flex items-start gap-3">
                                    <div className="w-10 h-10 rounded-xl bg-brand-green-light flex items-center justify-center shrink-0">
                                        <FileText size={20} className="text-brand-green" />
                                    </div>
                                    <div>
                                        <h2 id="sk-title" className="text-lg font-bold text-slate-800">Persetujuan Syarat &amp; Ketentuan</h2>
                                        <p className="text-xs text-slate-500 mt-0.5">Versi {versiSyarat} &bull; Berlaku efektif per {new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })}</p>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    onClick={() => setShowModalPersetujuan(false)}
                                    className={`p-2 -m-2 text-slate-400 hover:text-slate-600 transition-colors shrink-0 rounded-lg ${focusRing}`}
                                    aria-label="Tutup tanpa mengirim"
                                >
                                    <X size={20} />
                                </button>
                            </div>

                            <div className="px-6 py-5 max-h-[55vh] overflow-y-auto">
                                <div className="bg-amber-50 border border-amber-200 rounded-xl p-3.5 mb-4 flex items-start gap-2.5">
                                    <ShieldCheck size={18} className="text-amber-600 shrink-0 mt-0.5" />
                                    <p className="text-xs text-amber-800 leading-relaxed">
                                        Mohon membaca keseluruhan ketentuan berikut secara cermat sebelum melanjutkan.
                                        Dengan menekan tombol <span className="font-bold">&ldquo;Konfirmasi &amp; Kirim Pengajuan&rdquo;</span>,
                                        Anda menyatakan telah membaca, memahami, dan menyetujui seluruh ketentuan yang berlaku.
                                    </p>
                                </div>

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

                            <div className="sticky bottom-0 bg-white border-t border-slate-100 px-6 py-5 space-y-4 rounded-b-2xl">
                                <label htmlFor="persetujuan-sk" className="flex items-start gap-3 cursor-pointer group">
                                    <input
                                        id="persetujuan-sk"
                                        type="checkbox"
                                        checked={setujuSyarat}
                                        onChange={(e) => setSetujuSyarat(e.target.checked)}
                                        className="mt-0.5 w-5 h-5 rounded border-slate-300 text-brand-green focus:ring-2 focus:ring-brand-green/40 cursor-pointer shrink-0"
                                    />
                                    <span className="text-sm text-slate-700 leading-relaxed">
                                        Saya menyatakan telah <span className="font-semibold">membaca, memahami, dan menyetujui</span> seluruh
                                        syarat dan ketentuan pinjaman sebagaimana tercantum di atas, termasuk kebenaran data yang
                                        saya sampaikan serta kewajiban pelunasan sesuai jadwal yang ditetapkan.
                                    </span>
                                </label>

                                <div className="flex flex-col sm:flex-row gap-3">
                                    <button
                                        type="button"
                                        onClick={() => setShowModalPersetujuan(false)}
                                        disabled={loadingSubmit}
                                        className={`sm:flex-1 py-3.5 text-sm font-bold rounded-xl border-2 border-slate-200 text-slate-700 hover:bg-slate-50 transition-colors disabled:opacity-50 ${focusRing}`}
                                    >
                                        Kembali
                                    </button>
                                    <button
                                        type="button"
                                        onClick={kirimPengajuan}
                                        disabled={!setujuSyarat || loadingSubmit}
                                        className={`sm:flex-[2] py-3.5 text-sm font-bold rounded-xl bg-brand-green text-white hover:bg-brand-green-dark transition-colors disabled:opacity-50 disabled:cursor-not-allowed ${focusRing}`}
                                    >
                                        {loadingSubmit ? 'Mengirim...' : 'Konfirmasi & Kirim Pengajuan'}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </AnggotaLayout>
    );
}

function StepRingkasan({
    nominal, tenorDipilih, jadwal, totalDibayar,
    keperluan, setKeperluan,
    rekeningTersimpan, rekeningMode, setRekeningMode,
    rekeningId, setRekeningId,
    namaBank, setNamaBank, noRekening, setNoRekening, atasNama, setAtasNama,
    onSubmit, loading,
}) {
    const bisaSubmit = keperluan.trim().length >= 5 &&
        (rekeningMode === 'tersimpan' ? rekeningId : (namaBank && noRekening && atasNama));

    return (
        <div>
            <p className="text-base font-bold text-slate-800 mb-4">Ringkasan Pengajuan</p>

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

            <p className="text-sm font-semibold text-slate-600 mb-2.5">Simulasi cicilan per bulan:</p>
            <div className="divide-y divide-slate-50 mb-6 max-h-48 overflow-y-auto border border-slate-100 rounded-xl">
                {jadwal.map((c) => (
                    <div key={c.cicilan_ke} className="flex items-center justify-between px-4 py-2.5">
                        <span className="text-sm text-slate-500">Cicilan ke-{c.cicilan_ke}</span>
                        <span className="text-base font-semibold text-slate-800">{formatRupiah(c.total_bayar)}</span>
                    </div>
                ))}
            </div>

            <div className="mb-5">
                <label className="block text-sm font-semibold text-slate-700 mb-2">
                    Keperluan Peminjaman <span className="text-red-500">*</span>
                </label>
                <textarea
                    value={keperluan}
                    onChange={(e) => setKeperluan(e.target.value)}
                    rows={2}
                    placeholder="Contoh: Biaya pendidikan anak"
                    className="w-full px-4 py-3 text-base rounded-xl border border-slate-300 focus:border-brand-green focus:ring-2 focus:ring-brand-green/20 outline-none transition-colors"
                />
            </div>

            <div className="mb-6">
                <label className="block text-sm font-semibold text-slate-700 mb-2">
                    Rekening Tujuan Pencairan <span className="text-red-500">*</span>
                </label>

                {rekeningTersimpan.length > 0 && (
                    <div className="space-y-2 mb-3">
                        {rekeningTersimpan.map((r) => (
                            <label
                                key={r.id}
                                className={`flex items-center gap-3 p-3 rounded-xl border-2 cursor-pointer transition-colors ${
                                    rekeningMode === 'tersimpan' && rekeningId === r.id
                                        ? 'border-brand-green bg-brand-green-light'
                                        : 'border-slate-200 hover:border-slate-300'
                                }`}
                            >
                                <input
                                    type="radio"
                                    checked={rekeningMode === 'tersimpan' && rekeningId === r.id}
                                    onChange={() => { setRekeningMode('tersimpan'); setRekeningId(r.id); }}
                                    className="w-4 h-4 text-brand-green"
                                />
                                <div>
                                    <p className="text-sm font-semibold text-slate-800">{r.nama_bank} &bull; {r.no_rekening}</p>
                                    <p className="text-xs text-slate-400">a.n. {r.atas_nama}</p>
                                </div>
                            </label>
                        ))}

                        <label
                            className={`flex items-center gap-3 p-3 rounded-xl border-2 cursor-pointer transition-colors ${
                                rekeningMode === 'baru' ? 'border-brand-green bg-brand-green-light' : 'border-slate-200 hover:border-slate-300'
                            }`}
                        >
                            <input
                                type="radio"
                                checked={rekeningMode === 'baru'}
                                onChange={() => setRekeningMode('baru')}
                                className="w-4 h-4 text-brand-green"
                            />
                            <p className="text-sm font-semibold text-slate-800">Gunakan rekening baru</p>
                        </label>
                    </div>
                )}

                {(rekeningMode === 'baru' || rekeningTersimpan.length === 0) && (
                    <div className="space-y-3 p-4 bg-slate-50 rounded-xl">
                        <input
                            type="text"
                            value={namaBank}
                            onChange={(e) => setNamaBank(e.target.value)}
                            placeholder="Nama Bank (contoh: BCA)"
                            className="w-full px-4 py-2.5 text-base rounded-xl border border-slate-300 focus:border-brand-green outline-none"
                        />
                        <input
                            type="text"
                            value={noRekening}
                            onChange={(e) => setNoRekening(e.target.value)}
                            placeholder="Nomor Rekening"
                            className="w-full px-4 py-2.5 text-base rounded-xl border border-slate-300 focus:border-brand-green outline-none"
                        />
                        <input
                            type="text"
                            value={atasNama}
                            onChange={(e) => setAtasNama(e.target.value)}
                            placeholder="Nama Pemilik Rekening"
                            className="w-full px-4 py-2.5 text-base rounded-xl border border-slate-300 focus:border-brand-green outline-none"
                        />
                        <p className="text-xs text-slate-400">Rekening ini akan otomatis tersimpan untuk pengajuan berikutnya.</p>
                    </div>
                )}
            </div>

            <button
                onClick={onSubmit}
                disabled={loading || !bisaSubmit}
                className="w-full py-4 text-base font-bold rounded-2xl bg-brand-green text-white hover:bg-brand-green-dark transition-colors disabled:opacity-50"
            >
                {loading ? 'Mengirim...' : 'Ajukan Sekarang'}
            </button>
        </div>
    );
}