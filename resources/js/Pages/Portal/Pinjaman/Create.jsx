import AnggotaLayout from '@/Layouts/AnggotaLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState, useEffect, useRef } from 'react';
import {
    ArrowLeft, AlertCircle, Check, ShieldCheck,
    Phone, Clock, Wallet, X, FileText, Lock,
} from 'lucide-react';
import { formatRupiah } from '@/Utils/formatCurrency';
import CurrencyField from '@/Components/ui/CurrencyField';
import { withIdempotencyKey } from '@/Utils/idempotency';
import { useFeatureTour } from '@/Utils/featureTour';

const focusRing =
    'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-green focus-visible:ring-offset-2';

export default function Create({
    bisaAjukan,
    alasanTidakBisa,
    limitMaksimal,
    limitTersedia,
    sisaAngsuranAktif,
    cicilanPokokAktif,
    rekeningTersimpan = [],
    poinSyarat = [],
    versiSyarat = '',
    modeEdit = false,
    pinjamanEdit = null,
    jadwalAwal = [],
    tenorMaksimalAwal = null,
    totalDibayarAwal = 0,
}) {
    const rekeningCocok = modeEdit && pinjamanEdit
        ? rekeningTersimpan.find(
            (r) => r.no_rekening === pinjamanEdit.snapshot_no_rekening && r.nama_bank === pinjamanEdit.snapshot_bank,
        ) ?? rekeningTersimpan.find((r) => r.no_rekening === pinjamanEdit.snapshot_no_rekening)
        : null;

    const [nominal, setNominal] = useState(() => (modeEdit && pinjamanEdit ? String(pinjamanEdit.nominal) : ''));
    const [nominalValid, setNominalValid] = useState(() => Boolean(modeEdit && pinjamanEdit && tenorMaksimalAwal));
    const [tenorMaksimal, setTenorMaksimal] = useState(() => (modeEdit ? tenorMaksimalAwal : null));
    const [tenorDipilih, setTenorDipilih] = useState(() => (modeEdit && pinjamanEdit ? pinjamanEdit.tenor_bulan : null));
    const [jadwal, setJadwal] = useState(() => (modeEdit ? jadwalAwal : []));
    const [totalDibayar, setTotalDibayar] = useState(() => (modeEdit ? totalDibayarAwal : 0));
    const [error, setError] = useState('');
    const [loadingNominal, setLoadingNominal] = useState(false);
    const [loadingSubmit, setLoadingSubmit] = useState(false);

    const [keperluan, setKeperluan] = useState(() => (modeEdit && pinjamanEdit ? (pinjamanEdit.keperluan ?? '') : ''));
    const [rekeningMode, setRekeningMode] = useState(() => {
        if (modeEdit && pinjamanEdit) {
            return rekeningCocok ? 'tersimpan' : 'baru';
        }
        return rekeningTersimpan.length > 0 ? 'tersimpan' : 'baru';
    });
    const [rekeningId, setRekeningId] = useState(() => {
        if (modeEdit && rekeningCocok) {
            return rekeningCocok.id;
        }
        return rekeningTersimpan.find((r) => r.is_default)?.id ?? rekeningTersimpan[0]?.id ?? null;
    });
    const [namaBank, setNamaBank] = useState(() => (modeEdit && pinjamanEdit && !rekeningCocok ? (pinjamanEdit.snapshot_bank ?? '') : ''));
    const [noRekening, setNoRekening] = useState(() => (modeEdit && pinjamanEdit && !rekeningCocok ? (pinjamanEdit.snapshot_no_rekening ?? '') : ''));
    const [atasNama, setAtasNama] = useState(() => (modeEdit && pinjamanEdit && !rekeningCocok ? (pinjamanEdit.snapshot_atas_nama ?? '') : ''));

    const [showModalPersetujuan, setShowModalPersetujuan] = useState(false);
    const [setujuSyarat, setSetujuSyarat] = useState(false);
    const [pinTransaksi, setPinTransaksi] = useState('');
    const [pinError, setPinError] = useState('');

    const didMountRef = useRef(false);
    const tenorRef = useRef(null);
    const ringkasanRef = useRef(null);
    const { auth } = usePage().props;
    const startFeatureTour = useFeatureTour('pinjaman', auth?.user?.id, [
        { element: '[data-tour="pinjaman-nominal"]', popover: { title: 'Nominal Pinjaman', description: 'Masukkan nominal pinjaman sesuai limit tersedia.' } },
        { element: '[data-tour="pinjaman-tenor"]', waitForElement: 30000, popover: { title: 'Tenor Cicilan', description: 'Isi nominal, tunggu validasi, lalu pilih lama cicilan.' } },
        { element: '[data-tour="pinjaman-summary"]', waitForElement: 30000, popover: { title: 'Ringkasan Pengajuan', description: 'Isi keperluan dan rekening pencairan.' } },
        { element: '[data-tour="pinjaman-review"]', popover: { title: 'Tinjau Pengajuan', description: 'Klik tombol ini untuk membuka syarat. Tour tidak mengirim pengajuan.', onNextClick: (_, __, { driver }) => { document.querySelector('[data-tour="pinjaman-review"]')?.click(); window.setTimeout(() => driver.moveNext(), 0); } } },
        { element: '[data-tour="pinjaman-terms"]', waitForElement: 30000, popover: { title: 'Syarat Pengajuan', description: 'Baca syarat, centang persetujuan, lalu lanjutkan ke PIN.' } },
        { element: '[data-tour="pinjaman-terms-check"]', waitForElement: 30000, popover: { title: 'Persetujuan', description: 'Centang persetujuan untuk menampilkan kolom PIN.', onNextClick: (_, __, { driver }) => { document.querySelector('[data-tour="pinjaman-terms-check"]')?.click(); window.setTimeout(() => driver.moveNext(), 0); } } },
        { element: '[data-tour="pinjaman-pin"]', waitForElement: 30000, popover: { title: 'PIN Transaksi', description: 'Masukkan PIN 6 digit.' } },
        { element: '[data-tour="pinjaman-submit"]', popover: { title: 'Kirim Pengajuan', description: 'Tour selesai di sini. Jangan tekan tombol ini jika hanya ingin melihat alurnya.' } },
    ]);

    if (!bisaAjukan) {
        return (
            <AnggotaLayout mobileTitle="Ajukan Pinjaman" backHref={route('portal.dashboard')}>
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
        if (modeEdit && !didMountRef.current) {
            didMountRef.current = true;
            return undefined;
        }

        if (!nominal || Number(nominal) <= 0) {
            setNominalValid(false);
            setTenorMaksimal(null);
            setTenorDipilih(null);
            setJadwal([]);
            return undefined;
        }

        setError('');
        const timeout = setTimeout(async () => {
            setLoadingNominal(true);
            try {
                const res = await window.axios.post(route('portal.pinjaman.cek-nominal'), {
                    nominal,
                    ...(modeEdit && pinjamanEdit ? { pinjaman_id: pinjamanEdit.id } : {}),
                });
                const maksTenorBaru = res.data.tenor_maksimal;
                setTenorMaksimal(maksTenorBaru);
                setNominalValid(true);

                if (tenorDipilih && tenorDipilih <= maksTenorBaru) {
                    const simRes = await window.axios.post(route('portal.pinjaman.simulasi'), {
                        nominal,
                        tenor_bulan: tenorDipilih,
                    });
                    setJadwal(simRes.data.jadwal);
                    setTotalDibayar(simRes.data.total_dibayar);
                } else {
                    setTenorDipilih(null);
                    setJadwal([]);
                    setTotalDibayar(0);
                    setTimeout(() => tenorRef.current?.scrollIntoView({ behavior: 'smooth', block: 'center' }), 100);
                }
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
        setPinTransaksi('');
        setPinError('');
        setShowModalPersetujuan(true);
        setSetujuSyarat(false);
    }

    function kirimPengajuan() {
        if (!setujuSyarat) return;
        if (pinTransaksi.length !== 6) {
            setPinError('Masukkan 6 digit PIN keamanan Anda.');
            return;
        }

        setLoadingSubmit(true);
        setPinError('');

        const targetUrl = modeEdit && pinjamanEdit
            ? route('portal.pinjaman.update', pinjamanEdit.id)
            : route('portal.pinjaman.store');
        const payload = {
            nominal,
            tenor_bulan: tenorDipilih,
            keperluan,
            rekening_mode: rekeningMode,
            rekening_id: rekeningId,
            nama_bank: namaBank,
            no_rekening: noRekening,
            atas_nama: atasNama,
            persetujuan: true,
            pin: pinTransaksi,
        };
        const options = withIdempotencyKey({
            onError: (errors) => {
                if (errors.pin) {
                    setPinError(errors.pin);
                    setPinTransaksi('');
                } else {
                    setError(Object.values(errors)[0] ?? 'Terjadi kesalahan, coba lagi.');
                    setShowModalPersetujuan(false);
                    setSetujuSyarat(false);
                }
                setLoadingSubmit(false);
            },
            onFinish: () => {
                setLoadingSubmit(false);
            },
        });

        if (modeEdit && pinjamanEdit) {
            router.put(targetUrl, payload, options);
        } else {
            router.post(targetUrl, payload, options);
        }
    }

    const opsiTenor = tenorMaksimal ? Array.from({ length: tenorMaksimal }, (_, i) => i + 1) : [];

    return (
        <AnggotaLayout mobileTitle={modeEdit ? 'Ubah Pengajuan' : 'Ajukan Pinjaman'} backHref={route('portal.dashboard')}>
            <Head title={modeEdit ? 'Ubah Pengajuan Pinjaman' : 'Ajukan Pinjaman'} />

            <Link href={route('portal.dashboard')} className="hidden sm:inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 hover:text-brand-navy mb-5">
                <ArrowLeft size={16} />
                Batal, kembali ke Beranda
            </Link>
            {!modeEdit && (
                <button
                    type="button"
                    onClick={startFeatureTour}
                    className={`mb-5 ml-4 text-sm font-semibold text-brand-green-dark hover:text-brand-green ${focusRing}`}
                >
                    Ulangi Tour
                </button>
            )}

            {modeEdit && pinjamanEdit && (
                <div className={`mb-5 rounded-2xl border p-4 sm:p-5 flex items-start gap-3 ${
                    pinjamanEdit.akan_reset_verifikasi
                        ? 'bg-amber-50 border-amber-200'
                        : 'bg-sky-50/70 border-sky-200'
                }`}>
                    <AlertCircle className={`shrink-0 mt-0.5 ${pinjamanEdit.akan_reset_verifikasi ? 'text-amber-600' : 'text-sky-600'}`} size={20} />
                    <div>
                        <p className={`text-sm font-bold ${pinjamanEdit.akan_reset_verifikasi ? 'text-amber-900' : 'text-slate-900'}`}>
                            Mengubah Pengajuan Pinjaman #{pinjamanEdit.id}
                        </p>
                        <p className={`text-xs sm:text-sm mt-1 leading-relaxed ${pinjamanEdit.akan_reset_verifikasi ? 'text-amber-800' : 'text-slate-600'}`}>
                            {pinjamanEdit.akan_reset_verifikasi
                                ? 'Pengajuan Anda sudah melewati pemeriksaan awal. Menyimpan perubahan akan mereset status verifikasi kembali ke tahap awal agar nominal/tenor baru ditinjau ulang secara lengkap.'
                                : 'Perbarui nominal, tenor, keperluan, atau rekening tujuan pencairan sebelum diverifikasi pengurus.'}
                        </p>
                    </div>
                </div>
            )}

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
                                    Pinjaman aktif Anda tersisa {sisaAngsuranAktif} cicilan (maksimal 1 untuk pengajuan lebih awal) × {formatRupiah(cicilanPokokAktif)} = {formatRupiah(sisaAngsuranAktif * cicilanPokokAktif)}.
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

                        <CurrencyField
                            size="xl"
                            data-tour="pinjaman-nominal"
                            value={nominal}
                            onChange={(e) => setNominal(e.target.value)}
                            placeholder="0"
                            autoFocus
                            suffix={
                                loadingNominal ? (
                                    <div className="w-5 h-5 border-2 border-brand-green border-t-transparent rounded-full animate-spin pointer-events-none" />
                                ) : nominalValid ? (
                                    <Check className="text-brand-green pointer-events-none" size={22} />
                                ) : null
                            }
                        />
                        <p className="text-xs text-slate-400 mt-2">Maksimal: {formatRupiah(limitTersedia)}</p>
                    </div>

                    {nominalValid && tenorMaksimal && (
                        <div data-tour="pinjaman-tenor" ref={tenorRef} className="bg-white rounded-2xl border border-slate-100 p-6 animate-in fade-in slide-in-from-top-2 duration-300">
                            <p className="text-base font-bold text-slate-800 mb-1">Pilih lama cicilan (tenor)</p>
                            <p className="text-sm text-slate-400 mb-4">
                                Tenor maksimal untuk nominal ini: {tenorMaksimal} bulan
                            </p>

                            <div className="grid grid-cols-3 sm:grid-cols-6 gap-2.5">
                                {opsiTenor.map((bulan) => (
                                    <button
                                        key={bulan}
                                        onClick={() => pilihTenor(bulan)}
                                        className={`py-3 min-h-[52px] rounded-xl text-base font-bold border-2 transition-colors ${
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
                        <div data-tour="pinjaman-summary" ref={ringkasanRef} className="bg-white rounded-2xl border border-slate-100 p-6 animate-in fade-in slide-in-from-top-2 duration-300">
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
                                labelSubmit={modeEdit ? 'Simpan Perubahan Pengajuan' : 'Ajukan Sekarang'}
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
                                <p className="text-sm text-slate-300">Pengajuan diverifikasi awal oleh Sekretaris Koperasi</p>
                            </div>
                            <div className="flex gap-3">
                                <div className="w-6 h-6 rounded-full bg-white/15 flex items-center justify-center text-xs font-bold shrink-0">2</div>
                                <p className="text-sm text-slate-300">Pengajuan ditinjau oleh Bendahara Koperasi</p>
                            </div>
                            <div className="flex gap-3">
                                <div className="w-6 h-6 rounded-full bg-white/15 flex items-center justify-center text-xs font-bold shrink-0">3</div>
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
                            Hubungi pengurus Koperasi untuk informasi lebih lanjut mengenai status pengajuan Anda.
                        </p>
                    </div>
                </div>
            </div>

            {showModalPersetujuan && (
                <div className="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm overflow-y-auto animate-in fade-in duration-200">
                    <div className="min-h-full flex items-end sm:items-center justify-center sm:p-4">
                        <div
                            className="bg-white rounded-t-3xl sm:rounded-2xl w-full max-w-2xl mt-8 mb-0 sm:my-8 shadow-2xl max-h-[92dvh] flex flex-col"
                            role="dialog"
                            aria-modal="true"
                            aria-labelledby="sk-title"
                        >
                            <div className="sm:hidden pt-2 pb-1 flex justify-center shrink-0" aria-hidden="true">
                                <span className="w-10 h-1 rounded-full bg-slate-300" />
                            </div>
                            <div className="sticky top-0 bg-white border-b border-slate-100 px-4 sm:px-6 py-4 sm:py-5 flex items-start justify-between gap-4 rounded-t-3xl sm:rounded-t-2xl z-10">
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

                            <div data-tour="pinjaman-terms" className="px-6 py-5 max-h-[55vh] overflow-y-auto">
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

                            <div className="sticky bottom-0 bg-white border-t border-slate-100 px-4 sm:px-6 py-4 sm:py-5 pb-[calc(1rem+env(safe-area-inset-bottom))] sm:pb-5 space-y-4 rounded-b-none sm:rounded-b-2xl">
                                <label htmlFor="persetujuan-sk" className="flex items-start gap-3 cursor-pointer group">
                                    <input
                                        id="persetujuan-sk"
                                         data-tour="pinjaman-terms-check"
                                        type="checkbox"
                                        checked={setujuSyarat}
                                        onChange={(e) => {
                                            setSetujuSyarat(e.target.checked);
                                            if (!e.target.checked) {
                                                setPinTransaksi('');
                                                setPinError('');
                                            }
                                        }}
                                        className="mt-0.5 w-5 h-5 rounded border-slate-300 text-brand-green focus:ring-2 focus:ring-brand-green/40 cursor-pointer shrink-0"
                                    />
                                    <span className="text-sm text-slate-700 leading-relaxed">
                                        Saya menyatakan telah <span className="font-semibold">membaca, memahami, dan menyetujui</span> seluruh
                                        syarat dan ketentuan pinjaman sebagaimana tercantum di atas, termasuk kebenaran data yang
                                        saya sampaikan serta kewajiban pelunasan sesuai jadwal yang ditetapkan.
                                    </span>
                                </label>

                                {setujuSyarat && (
                                    <div className="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-2">
                                        <div className="flex items-center justify-between">
                                            <label htmlFor="pin-transaksi-input" className="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                                                <Lock size={14} className="text-brand-green" />
                                                PIN Keamanan Transaksi (6 Digit)
                                            </label>
                                            <span className="text-[11px] text-slate-400">Wajib diisi</span>
                                        </div>
                                        <p className="text-[11px] text-slate-500 leading-relaxed">
                                            Ketik 6 digit PIN akun Anda sebagai tanda tangan dan otorisasi pengesahan pengajuan pinjaman.
                                        </p>
                                        <input
                                            id="pin-transaksi-input"
                                             data-tour="pinjaman-pin"
                                            type="password"
                                            inputMode="numeric"
                                            pattern="[0-9]*"
                                            maxLength={6}
                                            value={pinTransaksi}
                                            onChange={(e) => {
                                                const val = e.target.value.replace(/\D/g, '').slice(0, 6);
                                                setPinTransaksi(val);
                                                setPinError('');
                                            }}
                                            placeholder="••••••"
                                            autoFocus
                                            className="w-full h-12 text-center text-xl font-mono tracking-[0.6em] rounded-xl border border-slate-300 focus:border-brand-green focus:ring-2 focus:ring-brand-green/30 bg-white"
                                        />
                                        {pinError && (
                                            <p className="text-xs text-red-600 font-semibold animate-pulse pt-1">
                                                {pinError}
                                            </p>
                                        )}
                                    </div>
                                )}

                                <div className="flex flex-col sm:flex-row gap-3">
                                    <button
                                        type="button"
                                        onClick={() => setShowModalPersetujuan(false)}
                                        disabled={loadingSubmit}
                                        className={`sm:flex-1 min-h-[48px] text-base font-bold rounded-xl border-2 border-slate-200 text-slate-700 hover:bg-slate-50 transition-colors disabled:opacity-50 ${focusRing}`}
                                    >
                                        Kembali
                                    </button>
                                    <button
                                        type="button"
                                        data-tour="pinjaman-submit"
                                         onClick={kirimPengajuan}
                                        disabled={!setujuSyarat || pinTransaksi.length !== 6 || loadingSubmit}
                                        className={`sm:flex-[2] min-h-[48px] text-base font-bold rounded-xl bg-brand-green text-white hover:bg-brand-green-dark transition-colors disabled:opacity-50 disabled:cursor-not-allowed ${focusRing}`}
                                    >
                                        {loadingSubmit
                                            ? 'Menyimpan...'
                                            : modeEdit
                                                ? 'Otorisasi & Simpan Perubahan'
                                                : 'Otorisasi & Kirim Pengajuan'}
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
    onSubmit, loading, labelSubmit = 'Ajukan Sekarang',
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
                            className="w-full px-4 min-h-[48px] text-base rounded-xl border border-slate-300 focus:border-brand-green outline-none"
                        />
                        <input
                            type="text"
                            inputMode="numeric"
                            value={noRekening}
                            onChange={(e) => setNoRekening(e.target.value)}
                            placeholder="Nomor Rekening"
                            className="w-full px-4 min-h-[48px] text-base rounded-xl border border-slate-300 focus:border-brand-green outline-none"
                        />
                        <input
                            type="text"
                            value={atasNama}
                            onChange={(e) => setAtasNama(e.target.value)}
                            placeholder="Nama Pemilik Rekening"
                            className="w-full px-4 min-h-[48px] text-base rounded-xl border border-slate-300 focus:border-brand-green outline-none"
                        />
                        <p className="text-xs text-slate-400">Rekening ini akan otomatis tersimpan untuk pengajuan berikutnya.</p>
                    </div>
                )}
            </div>

            <div className="sticky bottom-24 sm:static -mx-1 px-1 pb-1 sm:mx-0 sm:px-0 sm:pb-0 bg-white/95 sm:bg-transparent backdrop-blur pt-2 sm:pt-0">
                <button
                    data-tour="pinjaman-review"
                    onClick={onSubmit}
                    disabled={loading || !bisaSubmit}
                    className="w-full min-h-[52px] text-base font-bold rounded-2xl bg-brand-green text-white hover:bg-brand-green-dark active:scale-[0.99] transition-all disabled:opacity-50"
                >
                    {loading ? 'Mengirim...' : labelSubmit}
                </button>
            </div>
        </div>
    );
}