import AnggotaLayout from '@/Layouts/AnggotaLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { Dialog, DialogPanel, Transition, TransitionChild } from '@headlessui/react';
import { useCallback, useEffect, useRef, useState } from 'react';
import {
    ArrowLeft, TrendingDown, TrendingUp, CheckCircle2, AlertCircle,
    Check, RotateCcw, FileText, X, Info,
} from 'lucide-react';
import { formatRupiah } from '@/Utils/formatCurrency';
import { withIdempotencyKey } from '@/Utils/idempotency';

const focusRing =
    'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-green focus-visible:ring-offset-2';

const MIN_KETERANGAN = 10;
const MAKS_KETERANGAN = 500;

const opsi = [
    { tipe: 'percepat', label: 'Percepat Pelunasan', desc: 'Kurangi tenor, cicilan lebih besar per bulan', icon: TrendingDown },
    { tipe: 'perpanjang', label: 'Perpanjang Tenor', desc: 'Tambah tenor, cicilan lebih ringan per bulan', icon: TrendingUp },
    { tipe: 'lunas_total', label: 'Lunas Sekarang', desc: 'Bayar seluruh sisa pokok sekaligus, bunga hanya 1 bulan', icon: CheckCircle2 },
];

export default function Create({ pinjaman }) {
    const [tipeDipilih, setTipeDipilih] = useState(null);
    const [preview, setPreview] = useState(null);
    const [loadingPreview, setLoadingPreview] = useState(false);
    const [errorPreview, setErrorPreview] = useState('');
    const [showKonfirmasi, setShowKonfirmasi] = useState(false);
    const [terkirim, setTerkirim] = useState(false);
    const { data, setData, post, processing, errors } = useForm({
        tipe: '', tenor_baru: '', keterangan: '',
    });

    // State belum ter-commit saat handler tenor berjalan, jadi tipe dibaca dari ref.
    const tipeRef = useRef(null);
    const abortRef = useRef(null);

    const muatPreview = useCallback(async (tipe, tenorBaru) => {
        abortRef.current?.abort();
        const controller = new AbortController();
        abortRef.current = controller;

        setLoadingPreview(true);
        setErrorPreview('');

        try {
            const res = await window.axios.post(
                route('portal.percepatan.preview'),
                { tipe, tenor_baru: tenorBaru },
                { signal: controller.signal }
            );
            setPreview(res.data);
        } catch (e) {
            if (controller.signal.aborted) return;
            setPreview(null);
            setErrorPreview(
                e.response?.data?.message
                ?? e.response?.data?.errors?.tenor_baru?.[0]
                ?? 'Simulasi gagal dimuat. Periksa koneksi Anda lalu coba lagi.'
            );
        } finally {
            if (!controller.signal.aborted) setLoadingPreview(false);
        }
    }, []);

    // Lunas Sekarang tidak butuh tenor, jadi langsung hitung begitu jenis dipilih.
    useEffect(() => {
        if (tipeDipilih === 'lunas_total') {
            muatPreview('lunas_total', null);
        } else {
            abortRef.current?.abort();
            setLoadingPreview(false);
            setPreview(null);
        }
    }, [tipeDipilih, muatPreview]);

    // Batalkan request yang masih berjalan saat meninggalkan halaman.
    useEffect(() => () => abortRef.current?.abort(), []);

    function pilihTipe(tipe) {
        tipeRef.current = tipe;
        setTipeDipilih(tipe);
        setData({ ...data, tipe, tenor_baru: '' });
        setPreview(null);
        setErrorPreview('');
        setTerkirim(false);
    }

    function pilihTenor(bulan) {
        setData('tenor_baru', bulan);
        setTerkirim(false);
        muatPreview(tipeRef.current, bulan);
    }

    function gantiJenis() {
        tipeRef.current = null;
        setTipeDipilih(null);
        setPreview(null);
        setErrorPreview('');
        setTerkirim(false);
        setData({ tipe: '', tenor_baru: '', keterangan: data.keterangan });
    }

    const opsiAktif = opsi.find((o) => o.tipe === tipeDipilih);

    const langkah = [
        { label: 'Pilih Jenis', done: !!tipeDipilih, active: !tipeDipilih },
        { label: 'Tenor & Simulasi', done: !!preview, active: !!tipeDipilih && !preview },
        { label: 'Alasan & Kirim', done: !!terkirim, active: !!preview && !terkirim },
    ];

    const sisaAlasan = Math.max(MIN_KETERANGAN - data.keterangan.trim().length, 0);
    const alasanCukup = data.keterangan.trim().length >= MIN_KETERANGAN;

    // Simulasi wajib ada dulu: ringkasan konfirmasi Paredit dari preview,
    // jadi kirim tanpa preview berarti angka yang dikonfirmasi bukan yang terkirim.
    const simulasiSiap = !!preview && !loadingPreview && !errorPreview;
    const tenorSiap = tipeDipilih === 'lunas_total' || !!data.tenor_baru;
    const bisaSubmit = alasanCukup && tenorSiap && simulasiSiap;

    // Submit (tombol atau Enter dari textarea) hanya membuka sheet, belum kirim.
    function bukaKonfirmasi(e) {
        e.preventDefault();
        if (!bisaSubmit) return;
        setTerkirim(true);
        setShowKonfirmasi(true);
    }

    // Ringkasan konfirmasi, semua dihitung dari data yang sudah ada di halaman.
    const totalBaru = tipeDipilih === 'lunas_total'
        ? preview?.total_bayar ?? 0
        : (preview?.jadwal ?? []).reduce((s, c) => s + (c.total_bayar ?? 0), 0);
    const cicilanBaru = tipeDipilih === 'lunas_total'
        ? null
        : preview?.jadwal?.[0]?.total_bayar ?? null;
    const tenorBaru = tipeDipilih === 'lunas_total' ? null : Number(data.tenor_baru) || null;

    if (!pinjaman) {
        return <Peringatan
            judul="Tidak ada pinjaman aktif"
            pesan="Anda tidak memiliki pinjaman aktif saat ini."
        />;
    }

    if (pinjaman.sudah_pakai_percepatan) {
        return <Peringatan
            judul="Hak perubahan tenor terpakai"
            pesan="Pinjaman ini sudah pernah menggunakan hak perubahan tenor/pelunasan dipercepat."
        />;
    }

    const opsiTenorPercepat = Array.from(
        { length: Math.max(pinjaman.tenor_bulan - 1, 0) },
        (_, i) => i + 1
    );
    const opsiTenorPerpanjang = Array.from(
        { length: Math.max(12 - pinjaman.tenor_bulan, 0) },
        (_, i) => pinjaman.tenor_bulan + i + 1
    );

    const tenorInfo = tipeDipilih === 'percepat'
        ? { judul: 'Pilih Tenor Baru', bantuan: `Harus kurang dari tenor saat ini (${pinjaman.tenor_bulan} bulan)`, opsi: opsiTenorPercepat }
        : { judul: 'Pilih Tenor Baru', bantuan: `Harus lebih dari tenor saat ini (${pinjaman.tenor_bulan} bulan)`, opsi: opsiTenorPerpanjang };

    return (
        <AnggotaLayout>
            <Head title="Ajukan Perubahan Tenor" />

            <Link href={route('portal.dashboard')} className={`inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 hover:text-brand-navy mb-5 ${focusRing}`}>
                <ArrowLeft size={16} aria-hidden="true" />
                Batal, kembali ke Beranda
            </Link>

            <div className="mb-6">
                <h1 className="text-xl sm:text-2xl font-bold text-slate-800">Ajukan Perubahan Tenor</h1>
                <p className="text-base text-slate-400 mt-1">
                    Pilih jenis perubahan, atur tenor baru, lalu tulis alasan pengajuan Anda.
                </p>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            <div className="lg:col-span-2">
                {/* Stepper: label di bawah pill di layar sempit, samping di layar lebar. */}
                <ol className="grid grid-cols-3 gap-2 mb-6" aria-label="Progres pengajuan perubahan tenor">
                    {langkah.map((s, i) => (
                        <li key={s.label} className="flex flex-col sm:flex-row sm:items-center gap-1.5 sm:gap-2 min-w-0">
                            <span
                                aria-current={s.active ? 'step' : undefined}
                                className={`w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold shrink-0 transition-colors ${
                                    s.done
                                        ? 'bg-brand-green text-white'
                                        : s.active
                                            ? 'border-2 border-brand-green text-brand-green-dark bg-white'
                                            : 'border-2 border-slate-200 text-slate-400 bg-white'
                                }`}
                            >
                                {s.done ? <Check size={14} aria-hidden="true" /> : i + 1}
                            </span>

                            <span className={`text-[11px] sm:text-xs font-semibold leading-tight min-w-0 ${s.active || s.done ? 'text-slate-700' : 'text-slate-400'}`}>
                                {s.label}
                            </span>

                            {i < langkah.length - 1 && (
                                <span aria-hidden="true" className={`hidden sm:block flex-1 h-0.5 rounded ${langkah[i].done ? 'bg-brand-green' : 'bg-slate-200'}`} />
                            )}
                        </li>
                    ))}
                </ol>

                {/* Pilihan jenis */}
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-5" role="group" aria-label="Jenis perubahan tenor">
                    {opsi.map((o) => {
                        const Icon = o.icon;
                        const aktif = tipeDipilih === o.tipe;
                        return (
                            <button
                                key={o.tipe}
                                type="button"
                                onClick={() => pilihTipe(o.tipe)}
                                aria-pressed={aktif}
                                className={`text-left p-4 rounded-2xl border-2 transition-colors ${focusRing} ${
                                    aktif ? 'border-brand-green bg-brand-green-light' : 'border-slate-200 bg-white hover:border-slate-300'
                                }`}
                            >
                                <Icon size={22} aria-hidden="true" className={aktif ? 'text-brand-green-dark' : 'text-slate-400'} />
                                <p className="text-sm font-bold text-slate-800 mt-2">{o.label}</p>
                                <p className="text-xs text-slate-500 mt-1 leading-snug">{o.desc}</p>
                            </button>
                        );
                    })}
                </div>

                {/* Recap jenis terpilih */}
                {opsiAktif && (
                    <div className="flex items-center justify-between gap-3 bg-brand-green-light border border-brand-green/25 rounded-xl px-4 py-3 mb-5">
                        <div className="flex items-center gap-3 min-w-0">
                            <opsiAktif.icon size={18} aria-hidden="true" className="text-brand-green-dark shrink-0" />
                            <div className="min-w-0">
                                <p className="text-xs font-bold uppercase tracking-wide text-brand-green-dark">
                                    Jenis dipilih
                                </p>
                                <p className="text-sm font-semibold text-slate-800 truncate">
                                    {opsiAktif.label}
                                </p>
                            </div>
                        </div>

                        <button
                            type="button"
                            onClick={gantiJenis}
                            className={`shrink-0 inline-flex items-center gap-1.5 min-h-[36px] px-3 py-1.5 text-xs font-bold rounded-lg border border-brand-green/40 text-brand-green-dark hover:bg-white/70 transition-colors ${focusRing}`}
                        >
                            <RotateCcw size={12} aria-hidden="true" />
                            Ganti
                        </button>
                    </div>
                )}

                {tipeDipilih && (
                    <form onSubmit={bukaKonfirmasi} className="bg-white rounded-2xl border border-slate-100 p-5 sm:p-6">
                        {errorPreview && (
                            <div role="alert" className="mb-5 flex items-start gap-2.5 bg-red-50 border border-red-100 rounded-xl p-4">
                                <AlertCircle size={18} aria-hidden="true" className="text-red-500 shrink-0 mt-0.5" />
                                <div className="min-w-0 flex-1">
                                    <p className="text-sm text-red-700">{errorPreview}</p>
                                    <button
                                        type="button"
                                        onClick={() => muatPreview(tipeRef.current, data.tenor_baru || null)}
                                        className={`mt-2 inline-flex items-center gap-1.5 text-sm font-semibold text-red-700 hover:text-red-800 underline ${focusRing}`}
                                    >
                                        <RotateCcw size={14} aria-hidden="true" />
                                        Coba lagi
                                    </button>
                                </div>
                            </div>
                        )}

                        {(tipeDipilih === 'percepat' || tipeDipilih === 'perpanjang') && (
                            <div className="mb-5">
                                <p className="text-base font-semibold text-slate-700 mb-1">{tenorInfo.judul}</p>
                                <p className="text-sm text-slate-400 mb-3">{tenorInfo.bantuan}</p>
                                <div className="grid grid-cols-3 sm:grid-cols-6 gap-2.5" role="group" aria-label="Pilihan tenor dalam bulan">
                                    {tenorInfo.opsi.map((bulan) => (
                                        <button
                                            key={bulan}
                                            type="button"
                                            onClick={() => pilihTenor(bulan)}
                                            aria-pressed={Number(data.tenor_baru) === bulan}
                                            className={`min-h-[52px] rounded-xl text-base font-bold border-2 transition-colors ${focusRing} ${
                                                Number(data.tenor_baru) === bulan
                                                    ? 'border-brand-green bg-brand-green-light text-brand-green-dark'
                                                    : 'border-slate-200 text-slate-600 hover:border-slate-300'
                                            }`}
                                        >
                                            {bulan}
                                        </button>
                                    ))}
                                </div>
                                {errors.tenor_baru && <p className="text-sm text-red-600 mt-1.5">{errors.tenor_baru}</p>}
                            </div>
                        )}

                        {loadingPreview && (
                            <div role="status" className="mb-5 flex items-center gap-2 text-sm text-slate-400">
                                <div aria-hidden="true" className="w-4 h-4 border-2 border-brand-green border-t-transparent rounded-full animate-spin" />
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
                                    <div className="max-h-48 overflow-y-auto overscroll-contain divide-y divide-slate-200 border border-slate-200 rounded-lg bg-white">
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

                        <div className="mb-5">
                            <div className="flex items-baseline justify-between gap-3 mb-2">
                                <label htmlFor="alasan-perubahan" className="text-base font-semibold text-slate-700">
                                    Alasan Pengajuan
                                </label>
                                <span className={`text-xs tabular-nums ${alasanCukup ? 'text-brand-green-dark' : 'text-slate-400'}`}>
                                    {data.keterangan.trim().length}/{MAKS_KETERANGAN}
                                </span>
                            </div>
                            <p className="text-xs text-slate-400 -mt-1 mb-2">
                                {alasanCukup
                                    ? 'Alasan cukup panjang.'
                                    : `Minimal ${MIN_KETERANGAN} karakter, tersisa ${sisaAlasan} lagi.`}
                            </p>
                            <textarea
                                id="alasan-perubahan"
                                value={data.keterangan}
                                onChange={(e) => setData('keterangan', e.target.value)}
                                rows={3}
                                maxLength={MAKS_KETERANGAN}
                                placeholder="Jelaskan alasan Anda mengajukan perubahan ini"
                                aria-describedby="petunjuk-alasan"
                                aria-invalid={!alasanCukup && data.keterangan.length > 0}
                                className={`w-full px-4 py-3 text-base rounded-xl border outline-none transition-colors ${focusRing} ${
                                    !alasanCukup && data.keterangan.length > 0
                                        ? 'border-red-300 focus:border-red-500 focus:ring-2 focus:ring-red-500/20'
                                        : 'border-slate-300 focus:border-brand-green focus:ring-2 focus:ring-brand-green/20'
                                }`}
                            />
                            <span id="petunjuk-alasan" className="sr-only">
                                Alasan perubahan tenor, minimal {MIN_KETERANGAN} karakter.
                            </span>
                            {errors.keterangan && <p className="text-sm text-red-600 mt-1.5">{errors.keterangan}</p>}
                        </div>

                        {errors.tipe && (
                            <div role="alert" className="mb-4 flex items-start gap-2.5 bg-red-50 border border-red-100 rounded-xl p-4">
                                <AlertCircle size={18} aria-hidden="true" className="text-red-500 shrink-0 mt-0.5" />
                                <p className="text-sm text-red-700">{errors.tipe}</p>
                            </div>
                        )}

                        {!bisaSubmit && !errors.tipe && (
                            <div className="mb-4 flex items-start gap-2.5 bg-slate-50 border border-slate-100 rounded-xl p-3.5">
                                <Info size={16} aria-hidden="true" className="text-slate-400 shrink-0 mt-0.5" />
                                <p className="text-sm text-slate-500">
                                    {!tenorSiap
                                        ? 'Pilih tenor baru untuk melanjutkan.'
                                        : !simulasiSiap
                                            ? 'Tunggu simulasi selesai.'
                                            : `Tulis alasan minimal ${MIN_KETERANGAN} karakter untuk melanjutkan.`}
                                </p>
                            </div>
                        )}

                        <div className="sticky bottom-24 sm:static -mx-1 px-1 pb-1 sm:mx-0 sm:px-0 sm:pb-0 bg-white/95 sm:bg-transparent backdrop-blur pt-2 sm:pt-0">
                            <button
                                type="submit"
                                disabled={processing || !bisaSubmit}
                                className={`w-full min-h-[52px] text-base font-bold rounded-2xl bg-brand-green text-white hover:bg-brand-green-dark active:scale-[0.99] transition-all disabled:opacity-50 disabled:cursor-not-allowed ${focusRing}`}
                            >
                                {processing ? 'Mengirim...' : 'Tinjau & Kirim Pengajuan'}
                            </button>
                        </div>
                    </form>
                )}
            </div>

            <aside className="space-y-4 lg:sticky lg:top-24">
                <div className="bg-white rounded-2xl border border-slate-100 p-5">
                    <p className="text-sm font-bold text-slate-700 mb-3">Pinjaman Anda Saat Ini</p>
                    <dl className="space-y-2.5">
                        <div className="flex items-baseline justify-between gap-3">
                            <dt className="text-sm text-slate-500">Nominal</dt>
                            <dd className="text-base font-bold text-slate-800 tabular-nums">{formatRupiah(pinjaman.nominal)}</dd>
                        </div>
                        <div className="flex items-baseline justify-between gap-3">
                            <dt className="text-sm text-slate-500">Tenor</dt>
                            <dd className="text-base font-bold text-slate-800 tabular-nums">{pinjaman.tenor_bulan} bulan</dd>
                        </div>
                        <div className="flex items-baseline justify-between gap-3">
                            <dt className="text-sm text-slate-500">Sisa cicilan</dt>
                            <dd className="text-base font-bold text-slate-800 tabular-nums">{pinjaman.sisa_angsuran}</dd>
                        </div>
                    </dl>
                </div>

                <div className="bg-white rounded-2xl border border-slate-100 p-5">
                    <div className="flex items-center gap-2 mb-3">
                        <FileText size={18} aria-hidden="true" className="text-brand-navy" />
                        <p className="text-sm font-bold text-slate-700">Proses Pengajuan</p>
                    </div>
                    <ol className="space-y-3">
                        {[
                            'Anda mengajukan pengajuan',
                            'Ditinjau Bendahara Koperasi',
                            'Disetujui final oleh Ketua',
                            'Jadwal angsuran diperbarui',
                        ].map((t, i) => (
                            <li key={t} className="flex gap-3">
                                <span className="w-6 h-6 rounded-full bg-brand-navy/5 text-brand-navy flex items-center justify-center text-xs font-bold shrink-0">
                                    {i + 1}
                                </span>
                                <p className="text-sm text-slate-500 leading-snug">{t}</p>
                            </li>
                        ))}
                    </ol>
                </div>

                <div className="bg-amber-50 border border-amber-200 rounded-2xl p-5">
                    <div className="flex items-center gap-2 mb-2">
                        <Info size={18} aria-hidden="true" className="text-amber-600" />
                        <p className="text-sm font-bold text-amber-800">Perhatian</p>
                    </div>
                    <p className="text-sm text-amber-700 leading-relaxed">
                        Hak perubahan tenor hanya dapat dipakai <span className="font-bold">satu kali</span> per pinjaman.
                    </p>
                </div>
            </aside>
            </div>

            <KonfirmasiSheet
                show={showKonfirmasi}
                onClose={() => setShowKonfirmasi(false)}
                jenis={tipeDipilih}
                tenorLama={pinjaman.tenor_bulan}
                tenorBaru={tenorBaru}
                cicilanBaru={cicilanBaru}
                totalBaru={totalBaru}
                processing={processing}
                errors={errors}
                onKirim={() => {
                    setShowKonfirmasi(false);
                    post(route('portal.percepatan.store'), withIdempotencyKey());
                }}
            />
        </AnggotaLayout>
    );
}

function Peringatan({ judul, pesan }) {
    return (
        <AnggotaLayout>
            <Head title="Perubahan Tenor" />
            <div className="max-w-2xl mx-auto flex items-start gap-3 bg-amber-50 border border-amber-100 rounded-2xl p-5">
                <AlertCircle size={22} aria-hidden="true" className="text-amber-600 shrink-0 mt-0.5" />
                <div>
                    <p className="text-base font-semibold text-amber-800 mb-1">{judul}</p>
                    <p className="text-sm text-amber-700">{pesan}</p>
                </div>
            </div>
            <Link href={route('portal.dashboard')} className="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 hover:text-brand-navy mt-5 max-w-2xl mx-auto">
                <ArrowLeft size={16} aria-hidden="true" />
                Kembali ke Beranda
            </Link>
        </AnggotaLayout>
    );
}

function KonfirmasiSheet({
    show, onClose, jenis, tenorLama, tenorBaru, cicilanBaru, totalBaru, processing, errors, onKirim,
}) {
    const label = opsi.find((o) => o.tipe === jenis)?.label ?? '-';

    const baris = [
        { k: 'Jenis', sebelum: `${tenorLama} bln`, sesudah: tenorBaru ? `${tenorBaru} bln` : 'Lunas', kecil: true },
        { k: 'Tenor', sebelum: `${tenorLama} bulan`, sesudah: tenorBaru ? `${tenorBaru} bulan` : 'Lunas Sekarang', kecil: false },
        { k: 'Cicilan per bulan', sebelum: '-', sesudah: cicilanBaru ? formatRupiah(cicilanBaru) : formatRupiah(totalBaru), kecil: false },
        { k: 'Total sisa', sebelum: '-', sesudah: formatRupiah(totalBaru), kecil: false },
    ];

    return (
        <Transition show={show} leave="duration-200">
            <Dialog as="div" className="fixed inset-0 z-50 overflow-y-auto overscroll-contain" onClose={onClose}>
                <TransitionChild
                    enter="ease-out duration-200"
                    enterFrom="opacity-0"
                    enterTo="opacity-100"
                    leave="ease-in duration-150"
                    leaveFrom="opacity-100"
                    leaveTo="opacity-0"
                >
                    <div className="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" />
                </TransitionChild>

                <div className="fixed inset-0 flex items-end sm:items-center sm:justify-center sm:p-4">
                    <TransitionChild
                        enter="ease-out duration-200"
                        enterFrom="translate-y-full sm:translate-y-0 sm:scale-95 sm:opacity-0"
                        enterTo="translate-y-0 sm:scale-100 sm:opacity-100"
                        leave="ease-in duration-150"
                        leaveFrom="translate-y-0 sm:scale-100 sm:opacity-100"
                        leaveTo="translate-y-full sm:scale-95 sm:opacity-0"
                    >
                        <DialogPanel className="w-full max-w-lg bg-white rounded-t-3xl sm:rounded-2xl shadow-2xl max-h-[92dvh] flex flex-col">
                            <div className="sm:hidden pt-2 pb-1 flex justify-center shrink-0" aria-hidden="true">
                                <span className="w-10 h-1 rounded-full bg-slate-300" />
                            </div>

                            <div className="shrink-0 flex items-start justify-between gap-4 px-4 sm:px-6 py-4 border-b border-slate-100 rounded-t-3xl sm:rounded-t-2xl">
                                <div className="flex items-start gap-3 min-w-0">
                                    <div className="w-10 h-10 rounded-xl bg-brand-green-light flex items-center justify-center shrink-0">
                                        <FileText size={20} aria-hidden="true" className="text-brand-green" />
                                    </div>
                                    <div className="min-w-0">
                                        <h2 className="text-lg font-bold text-slate-800">Tinjau Pengajuan</h2>
                                        <p className="text-sm text-slate-500 mt-0.5">{label}</p>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    onClick={onClose}
                                    disabled={processing}
                                    aria-label="Tutup tanpa mengirim"
                                    className={`p-2 -m-2 text-slate-400 hover:text-slate-600 transition-colors shrink-0 rounded-lg disabled:opacity-40 ${focusRing}`}
                                >
                                    <X size={20} />
                                </button>
                            </div>

                            <div className="px-4 sm:px-6 py-5 overflow-y-auto overscroll-contain">
                                <div className="flex items-start gap-2.5 bg-amber-50 border border-amber-200 rounded-xl p-3.5 mb-4">
                                    <Info size={18} aria-hidden="true" className="text-amber-600 shrink-0 mt-0.5" />
                                    <p className="text-xs text-amber-800 leading-relaxed">
                                        Pengajuan akan ditinjau Bendahara lalu disetujui Ketua Koperasi.
                                        Jadwal angsuran Anda berubah setelah disetujui.
                                    </p>
                                </div>

                                <dl className="rounded-xl border border-slate-100 divide-y divide-slate-100">
                                    {baris.map((b) => (
                                        <div key={b.k} className="flex items-baseline justify-between gap-3 px-4 py-3">
                                            <dt className="text-sm text-slate-500 shrink-0">{b.k}</dt>
                                            <dd className={`text-right tabular-nums ${b.kecil ? 'text-sm text-slate-600' : 'text-base font-bold text-slate-800'}`}>
                                                {b.sesudah}
                                            </dd>
                                        </div>
                                    ))}
                                </dl>

                                {errors.tipe && (
                                    <div role="alert" className="mt-4 flex items-start gap-2.5 bg-red-50 border border-red-100 rounded-xl p-4">
                                        <AlertCircle size={18} aria-hidden="true" className="text-red-500 shrink-0 mt-0.5" />
                                        <p className="text-sm text-red-700">{errors.tipe}</p>
                                    </div>
                                )}
                            </div>

                            <div className="shrink-0 border-t border-slate-100 px-4 sm:px-6 py-4 pb-[calc(1rem+env(safe-area-inset-bottom))] sm:pb-5 rounded-b-none sm:rounded-b-2xl">
                                <div className="flex flex-col-reverse sm:flex-row gap-3">
                                    <button
                                        type="button"
                                        onClick={onClose}
                                        disabled={processing}
                                        className={`sm:flex-1 min-h-[48px] text-base font-bold rounded-xl border-2 border-slate-200 text-slate-700 hover:bg-slate-50 active:bg-slate-100 transition-colors disabled:opacity-50 ${focusRing}`}
                                    >
                                        Kembali
                                    </button>
                                    <button
                                        type="button"
                                        onClick={onKirim}
                                        disabled={processing}
                                        className={`sm:flex-[2] min-h-[48px] text-base font-bold rounded-xl bg-brand-green text-white hover:bg-brand-green-dark active:bg-brand-green-dark transition-colors disabled:opacity-50 disabled:cursor-not-allowed ${focusRing}`}
                                    >
                                        {processing ? 'Mengirim...' : 'Konfirmasi & Kirim'}
                                    </button>
                                </div>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </Dialog>
        </Transition>
    );
}
