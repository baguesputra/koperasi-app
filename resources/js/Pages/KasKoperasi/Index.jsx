import AppLayout from '@/Layouts/AppLayout';
import { Head, usePage, useForm, router, Link } from '@inertiajs/react';
import {
    Wallet, ArrowDownCircle, ArrowUpCircle, ChevronLeft, ChevronRight,
    Landmark, Building2, CalendarDays, Search, X, ArrowRightLeft, Check, BookOpenText,
    SlidersHorizontal, ChevronDown, Calendar
} from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import Card from '@/Components/ui/Card';
import Button from '@/Components/ui/Button';
import Pagination from '@/Components/ui/Pagination';
import PageHeader from '@/Components/ui/PageHeader';
import TextField from '@/Components/ui/TextField';
import FormField from '@/Components/ui/FormField';
import Select from '@/Components/ui/Select';
import Drawer from '@/Components/ui/Drawer';
import useDebouncedValue from '@/Utils/useDebouncedValue';
import ChipNominal from '@/Components/ui/ChipNominal';
import { formatRupiah, formatRupiahSingkat } from '@/Utils/formatCurrency';
import { withIdempotencyKey } from '@/Utils/idempotency';

const fokusRing = 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-green/40';

const kategoriLabel = {
    saldo_awal: 'Saldo Awal',
    topup_bulanan: 'Topup Saldo',
    pencairan_pinjaman: 'Pencairan Pinjaman',
    pembayaran_angsuran: 'Pembayaran Angsuran',
    dana_sosial_bulanan: 'Dana Sosial Bulanan',
    pengeluaran_koperasi: 'Pengeluaran Koperasi',
    pengeluaran_dana_sosial: 'Pengeluaran Dana Sosial',
    pelunasan_resign_pinjaman: 'Pelunasan Resign Pinjaman',
    pelunasan_resign_simpanan: 'Pelunasan Pinjaman dari Simpanan',
    simpanan_resign_masuk: 'Simpanan Anggota (Resign)',
    return_simpanan_pokok: 'Return Simpanan Pokok',
    return_simpanan_wajib: 'Return Simpanan Wajib',
    simpanan_pokok_masuk: 'Simpanan Pokok Masuk',
    simpanan_wajib_masuk: 'Simpanan Wajib Masuk',
    transfer_ke_dana_pinjaman: 'Transfer ke Dana Pinjaman',
    terima_dari_pengembalian_simpanan: 'Terima dari Pengembalian Simpanan',
    talangan_sosial_ke_pinjaman: 'Talangan Sosial ke Pinjaman',
    talangan_simpanan_ke_pinjaman: 'Talangan Simpanan ke Pinjaman',
    terima_talangan_dari_sosial: 'Terima Talangan dari Sosial',
    terima_talangan_dari_simpanan: 'Terima Talangan dari Simpanan',
    kembali_talangan_dari_pinjaman: 'Pengembalian Talangan dari Pinjaman',
    kembali_talangan_ke_simpanan: 'Pengembalian Talangan ke Simpanan',
    kembali_talangan_ke_sosial: 'Pengembalian Talangan ke Sosial',
    sisih_kas_kecil: 'Sisih Kas Kecil',
    terima_sisih_kas_kecil: 'Terima Sisih Kas Kecil',
    pembayaran_shu: 'Pembayaran SHU',
};

const kantongLabel = {
    pinjaman: 'Pinjaman',
    dana_sosial: 'Dana Sosial',
    pengembalian_simpanan: 'Pengembalian Simpanan',
    simpanan: 'Simpanan Anggota',
    bank: 'Bank',
    kas_kecil: 'Kas Kecil',
};

const kanalMeta = {
    bank: { label: 'Bank', ikon: Landmark, warna: 'bg-brand-navy/10 text-brand-navy border-brand-navy/20' },
    kas_kecil: { label: 'Kas Kecil', ikon: Wallet, warna: 'bg-amber-50 text-amber-700 border-amber-200' },
};

const sumberTopupLabel = {
    modal: 'Modal',
    bunga_bank: 'Bunga Bank',
};

export default function Index({
    saldoBank,
    saldoKasKecil,
    bulanFilter,
    filters = {},
    tipePengeluaranList = {},
    akunPengeluaranList = {},
    kantongOptions = {},
    kategoriOptions = {},
    ringkasanPeriode,
    riwayat,
}) {
    const { auth } = usePage().props;
    const bisaTopup = auth.user?.permissions?.includes('kas.topup');

    const [showDrawer, setShowDrawer] = useState(false);
    const [tabDrawer, setTabDrawer] = useState('pemasukan');
    const [cari, setCari] = useState(filters.cari ?? '');
    const cariDebounced = useDebouncedValue(cari);
    const pertama = useRef(true);
    const hariIni = new Date().toISOString().slice(0, 10);

    const kanalAktif = filters.kanal ?? filters.akun ?? '';
    const tipeFilter = filters.tipe ?? '';
    const kategoriFilter = filters.kategori ?? '';
    const isRentangAktif = Boolean(filters.dari_tanggal || filters.sampai_tanggal);

    const [showPeriodePopover, setShowPeriodePopover] = useState(false);
    const [showKategoriPopover, setShowKategoriPopover] = useState(false);
    const [kategoriCari, setKategoriCari] = useState('');
    const [tabPeriode, setTabPeriode] = useState(isRentangAktif ? 'rentang' : 'bulan');
    const [tempBulan, setTempBulan] = useState(bulanFilter);
    const [tempDari, setTempDari] = useState(filters.dari_tanggal ?? '');
    const [tempSampai, setTempSampai] = useState(filters.sampai_tanggal ?? '');

    const periodeRef = useRef(null);
    const kategoriRef = useRef(null);

    useEffect(() => {
        function handleClickOutside(e) {
            if (periodeRef.current && !periodeRef.current.contains(e.target)) {
                setShowPeriodePopover(false);
            }
            if (kategoriRef.current && !kategoriRef.current.contains(e.target)) {
                setShowKategoriPopover(false);
            }
        }
        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    useEffect(() => {
        setTempBulan(bulanFilter);
        setTempDari(filters.dari_tanggal ?? '');
        setTempSampai(filters.sampai_tanggal ?? '');
        if (filters.dari_tanggal || filters.sampai_tanggal) {
            setTabPeriode('rentang');
        }
    }, [bulanFilter, filters.dari_tanggal, filters.sampai_tanggal]);

    // Forms
    const pemasukanForm = useForm({
        jumlah: '',
        sumber_topup: 'modal',
        keterangan: '',
        lain_lain: false,
        keterangan_lain: '',
        tanggal: hariIni,
    });

    const pengeluaranForm = useForm({
        tipe: 'lain_lain',
        akun: 'kas_kecil',
        jumlah: '',
        keterangan: '',
        tanggal: hariIni,
    });

    const sisihForm = useForm({
        jumlah: '',
        keterangan: '',
    });

    function terapkan(overrides = {}) {
        const params = {
            bulan: (filters.dari_tanggal || filters.sampai_tanggal) && !overrides.bulan ? undefined : bulanFilter,
            kanal: kanalAktif,
            tipe: tipeFilter,
            kategori: kategoriFilter,
            dari_tanggal: filters.dari_tanggal,
            sampai_tanggal: filters.sampai_tanggal,
            cari,
            page: 1,
            ...overrides,
        };

        if (overrides.bulan) {
            params.dari_tanggal = undefined;
            params.sampai_tanggal = undefined;
            params.tanggal = undefined;
        }

        if (overrides.dari_tanggal || overrides.sampai_tanggal) {
            params.bulan = undefined;
            params.tanggal = undefined;
        }

        Object.keys(params).forEach((k) => {
            if (params[k] === null || params[k] === undefined || params[k] === '') {
                delete params[k];
            }
        });

        router.get(
            route('kas-koperasi.index'),
            params,
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            }
        );
    }

    function geserBulan(delta) {
        const [tahun, bulanAngka] = (bulanFilter || kunciBulanIni).split('-').map(Number);
        const d = new Date(tahun, bulanAngka - 1 + delta, 1);
        const blnBaru = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
        terapkan({ bulan: blnBaru, dari_tanggal: '', sampai_tanggal: '', tanggal: '' });
    }

    function resetFilter() {
        setCari('');
        router.get(
            route('kas-koperasi.index'),
            { bulan: kunciBulanIni },
            { preserveState: true, replace: true }
        );
    }

    function terapkanRentang() {
        if (!tempDari && !tempSampai) return;
        let d = tempDari || tempSampai;
        let s = tempSampai || tempDari;
        if (d > s) [d, s] = [s, d];
        terapkan({ dari_tanggal: d, sampai_tanggal: s, bulan: '', tanggal: '' });
        setShowPeriodePopover(false);
    }

    function terapkanBulanPilihan() {
        if (!tempBulan) return;
        terapkan({ bulan: tempBulan, dari_tanggal: '', sampai_tanggal: '', tanggal: '' });
        setShowPeriodePopover(false);
    }

    function setPresetRentang(hari) {
        const d = new Date();
        const sampai = d.toISOString().slice(0, 10);
        d.setDate(d.getDate() - (hari - 1));
        const dari = d.toISOString().slice(0, 10);
        setTempDari(dari);
        setTempSampai(sampai);
    }

    useEffect(() => {
        if (pertama.current) {
            pertama.current = false;
            return;
        }
        if ((cariDebounced ?? '') !== (filters.cari ?? '')) {
            terapkan({ cari: cariDebounced ?? '' });
        }
    }, [cariDebounced]);

    useEffect(() => {
        setCari(filters.cari ?? '');
    }, [filters.cari]);

    const labelBulan = useMemo(() => {
        if (!bulanFilter) return '';
        const [tahun, bulanAngka] = bulanFilter.split('-').map(Number);
        if (!bulanAngka) return '';
        return new Date(tahun, bulanAngka - 1, 1).toLocaleDateString('id-ID', { month: 'long', year: 'numeric' });
    }, [bulanFilter]);

    const bulanIni = new Date();
    const kunciBulanIni = `${bulanIni.getFullYear()}-${String(bulanIni.getMonth() + 1).padStart(2, '0')}`;

    const labelPeriode = useMemo(() => {
        if (filters.dari_tanggal || filters.sampai_tanggal) {
            const dari = filters.dari_tanggal || filters.sampai_tanggal;
            const sampai = filters.sampai_tanggal || filters.dari_tanggal;
            const [y1, m1, d1] = dari.split('-');
            const [y2, m2, d2] = sampai.split('-');
            const dt1 = new Date(Number(y1), Number(m1) - 1, Number(d1));
            const dt2 = new Date(Number(y2), Number(m2) - 1, Number(d2));
            if (dari === sampai) {
                return dt1.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
            }
            if (y1 === y2 && m1 === m2) {
                return `${d1} – ${dt2.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })}`;
            }
            return `${dt1.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })} – ${dt2.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })}`;
        }
        return labelBulan;
    }, [filters.dari_tanggal, filters.sampai_tanggal, labelBulan]);

    const opsiKategoriTersaring = useMemo(() => {
        const semua = Object.entries(kategoriOptions && Object.keys(kategoriOptions).length > 0 ? kategoriOptions : kategoriLabel);
        if (!kategoriCari.trim()) return semua;
        const q = kategoriCari.toLowerCase();
        return semua.filter(([k, label]) =>
            label.toLowerCase().includes(q) || k.toLowerCase().includes(q)
        );
    }, [kategoriOptions, kategoriCari]);

    const labelAkunSaldo = kanalAktif === 'bank'
        ? ' Bank'
        : kanalAktif === 'kas_kecil'
        ? ' Kas Kecil'
        : '';

    const daftarRiwayat = Array.isArray(riwayat) ? riwayat : (riwayat?.data ?? []);
    const filterAktif = Boolean(
        kanalAktif ||
        filters.cari ||
        tipeFilter ||
        kategoriFilter ||
        isRentangAktif ||
        (bulanFilter && bulanFilter !== kunciBulanIni)
    );
    const saldoValid = ringkasanPeriode?.saldo_valid !== false;

    // Saldo awal & akhir per akun / gabungan
    const saldoAwalKanal = ringkasanPeriode?.saldo_awal_kanal ?? {};
    const saldoAkhirKanal = ringkasanPeriode?.saldo_akhir_kanal ?? {};

    const saldoAwal = kanalAktif === 'bank'
        ? (saldoAwalKanal.bank ?? ringkasanPeriode?.saldo_awal ?? 0)
        : kanalAktif === 'kas_kecil'
        ? (saldoAwalKanal.kas_kecil ?? ringkasanPeriode?.saldo_awal ?? 0)
        : (ringkasanPeriode?.saldo_awal ?? ((saldoAwalKanal.bank ?? 0) + (saldoAwalKanal.kas_kecil ?? 0)));

    const saldoAkhir = kanalAktif === 'bank'
        ? (saldoAkhirKanal.bank ?? ringkasanPeriode?.saldo_akhir ?? 0)
        : kanalAktif === 'kas_kecil'
        ? (saldoAkhirKanal.kas_kecil ?? ringkasanPeriode?.saldo_akhir ?? 0)
        : (ringkasanPeriode?.saldo_akhir ?? ((saldoAkhirKanal.bank ?? 0) + (saldoAkhirKanal.kas_kecil ?? 0)));

    // Form submits
    function submitPemasukan(e) {
        e.preventDefault();
        pemasukanForm.post(
            route('kas-koperasi.topup'),
            withIdempotencyKey({
                preserveScroll: true,
                onSuccess: () => {
                    pemasukanForm.reset('jumlah', 'keterangan', 'keterangan_lain');
                    pemasukanForm.setData('lain_lain', false);
                    pemasukanForm.setData('sumber_topup', 'modal');
                    setShowDrawer(false);
                },
            })
        );
    }

    function submitPengeluaran(e) {
        e.preventDefault();
        pengeluaranForm.post(
            route('pengeluaran.store'),
            withIdempotencyKey({
                preserveScroll: true,
                onSuccess: () => {
                    pengeluaranForm.reset('jumlah', 'keterangan');
                    setShowDrawer(false);
                },
            })
        );
    }

    function submitSisih(e) {
        e.preventDefault();
        sisihForm.post(
            route('kas-koperasi.sisih-kas-kecil'),
            withIdempotencyKey({
                preserveScroll: true,
                onSuccess: () => {
                    sisihForm.reset('jumlah', 'keterangan');
                    setShowDrawer(false);
                },
            })
        );
    }

    function badgeKanal(kanal) {
        const meta = kanalMeta[kanal] ?? kanalMeta.bank;
        const Ikon = meta.ikon;
        return (
            <span className={`inline-flex items-center gap-1 text-[11px] font-semibold px-2 py-0.5 rounded-md border whitespace-nowrap ${meta.warna}`}>
                <Ikon size={11} aria-hidden="true" className="shrink-0" />
                {meta.label}
            </span>
        );
    }

    function barisKeterangan(r) {
        const kategori = kategoriLabel[r.kategori] ?? r.kategori;
        return (
            <div>
                <p className="text-slate-800 font-medium leading-snug">
                    {r.keterangan}
                </p>
                <div className="flex items-center gap-2 mt-0.5 flex-wrap">
                    {r.sub_judul && (
                        <span className="text-[11px] text-slate-500 font-normal">
                            {r.sub_judul}
                        </span>
                    )}
                    {r.kategori && r.kategori !== 'saldo_awal' && (
                        <span className="text-[10px] uppercase font-bold tracking-wider text-slate-600 bg-slate-100 px-1.5 py-0.2 rounded border border-slate-200">
                            {kategori}
                        </span>
                    )}
                    {r.tipe_pengeluaran_label && (
                        <span className="text-[10px] font-semibold text-rose-700 bg-rose-50 px-1.5 py-0.2 rounded border border-rose-200">
                            {r.tipe_pengeluaran_label}
                        </span>
                    )}
                    {r.rute_asal && auth.user?.permissions?.includes(r.rute_asal.izin) && (
                        <Link
                            href={route(r.rute_asal.nama, r.rute_asal.id)}
                            className="inline-flex items-center gap-0.5 text-xs font-semibold text-brand-green-dark hover:text-brand-green hover:underline"
                        >
                            Lihat berkas
                            <ChevronRight size={11} aria-hidden="true" />
                        </Link>
                    )}
                </div>
            </div>
        );
    }

    return (
        <AppLayout>
            <Head title="Kas Koperasi" />

            <PageHeader title="Kas Koperasi" subtitle="Buku kas operasional, pencatatan transaksi & jejak bukti kas">
                {bisaTopup && (
                    <div className="flex items-center gap-2 flex-wrap">
                        <div className="hidden sm:inline-flex items-center rounded-xl bg-white border border-slate-200/90 p-1 shadow-2xs">
                            <button
                                type="button"
                                onClick={() => { setTabDrawer('pemasukan'); setShowDrawer(true); }}
                                className={`inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-emerald-700 hover:bg-emerald-50 transition-colors ${fokusRing}`}
                            >
                                <ArrowDownCircle size={14} className="text-emerald-600" />
                                Pemasukan
                            </button>
                            <span className="w-px h-3.5 bg-slate-200" aria-hidden="true" />
                            <button
                                type="button"
                                onClick={() => { setTabDrawer('pengeluaran'); setShowDrawer(true); }}
                                className={`inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-rose-700 hover:bg-rose-50 transition-colors ${fokusRing}`}
                            >
                                <ArrowUpCircle size={14} className="text-rose-600" />
                                Pengeluaran
                            </button>
                            <span className="w-px h-3.5 bg-slate-200" aria-hidden="true" />
                            <button
                                type="button"
                                onClick={() => { setTabDrawer('sisih'); setShowDrawer(true); }}
                                className={`inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-amber-800 hover:bg-amber-50 transition-colors ${fokusRing}`}
                            >
                                <ArrowRightLeft size={13} className="text-amber-600" />
                                Sisih Kas Kecil
                            </button>
                        </div>

                        <Button
                            size="sm"
                            className="sm:hidden"
                            onClick={() => { setTabDrawer('pemasukan'); setShowDrawer(true); }}
                        >
                            <ArrowDownCircle size={15} aria-hidden="true" />
                            Pemasukan
                        </Button>
                        <Button
                            size="sm"
                            variant="secondary"
                            className="sm:hidden"
                            onClick={() => { setTabDrawer('pengeluaran'); setShowDrawer(true); }}
                        >
                            <ArrowUpCircle size={15} aria-hidden="true" />
                            Pengeluaran
                        </Button>
                    </div>
                )}
            </PageHeader>

            {/* Interactive Multi-Account Switcher (Minimalist Compact Style) */}
            <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-5" role="tablist" aria-label="Pilih akun kas">
                {/* 1. Akun Semua Kas */}
                {(() => {
                    const isAktif = !kanalAktif;
                    const totalSaldo = (saldoBank ?? 0) + (saldoKasKecil ?? 0);
                    return (
                        <button
                            type="button"
                            role="tab"
                            aria-selected={isAktif}
                            onClick={() => terapkan({ kanal: '' })}
                            className={`group text-left p-3.5 sm:p-4 rounded-2xl border transition-all duration-150 flex flex-col justify-between ${fokusRing} ${
                                isAktif
                                    ? 'bg-gradient-to-br from-[#0B1527] via-[#0F1E36] to-[#0A2E1A] text-white border-slate-800 shadow-sm'
                                    : 'bg-white text-slate-800 border-slate-200/90 hover:border-slate-300 shadow-2xs'
                            }`}
                        >
                            <div className="flex items-center justify-between gap-2">
                                <div className="flex items-center gap-2.5">
                                    <span className={`w-8 h-8 rounded-xl flex items-center justify-center shrink-0 ${
                                        isAktif ? 'bg-white/10 text-emerald-400' : 'bg-slate-100 text-slate-600'
                                    }`}>
                                        <Landmark size={16} />
                                    </span>
                                    <div>
                                        <h3 className={`text-xs font-bold uppercase tracking-wider ${isAktif ? 'text-white' : 'text-slate-800'}`}>
                                            Semua Kas
                                        </h3>
                                        <p className={`text-[11px] ${isAktif ? 'text-slate-300/80' : 'text-slate-500'}`}>
                                            Bank &amp; Kas Kecil
                                        </p>
                                    </div>
                                </div>
                                {isAktif ? (
                                    <span className="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-400/30">
                                        <Check size={10} strokeWidth={3} />
                                        Aktif
                                    </span>
                                ) : (
                                    <span className="text-[11px] font-medium text-slate-500 group-hover:text-slate-700">
                                        Pilih
                                    </span>
                                )}
                            </div>

                            <div className="mt-3">
                                <p className={`text-[11px] font-medium ${isAktif ? 'text-slate-300/80' : 'text-slate-500'}`}>
                                    Saldo Saat Ini
                                </p>
                                <p className={`text-xl sm:text-2xl font-extrabold tracking-tight tabular-nums privacy-blur mt-0.5 truncate ${isAktif ? 'text-white' : 'text-slate-900'}`}>
                                    {formatRupiah(totalSaldo)}
                                </p>
                            </div>
                        </button>
                    );
                })()}

                {/* 2. Akun Bank */}
                {(() => {
                    const isAktif = kanalAktif === 'bank';
                    return (
                        <button
                            type="button"
                            role="tab"
                            aria-selected={isAktif}
                            onClick={() => terapkan({ kanal: 'bank' })}
                            className={`group text-left p-3.5 sm:p-4 rounded-2xl border transition-all duration-150 flex flex-col justify-between ${fokusRing} ${
                                isAktif
                                    ? 'bg-gradient-to-br from-[#0B1527] via-[#0F1E36] to-[#0A2E1A] text-white border-slate-800 shadow-sm'
                                    : 'bg-white text-slate-800 border-slate-200/90 hover:border-slate-300 shadow-2xs'
                            }`}
                        >
                            <div className="flex items-center justify-between gap-2">
                                <div className="flex items-center gap-2.5">
                                    <span className={`w-8 h-8 rounded-xl flex items-center justify-center shrink-0 ${
                                        isAktif ? 'bg-white/10 text-emerald-400' : 'bg-slate-100 text-slate-600'
                                    }`}>
                                        <Building2 size={16} />
                                    </span>
                                    <div>
                                        <h3 className={`text-xs font-bold uppercase tracking-wider ${isAktif ? 'text-white' : 'text-slate-800'}`}>
                                            Kas Bank
                                        </h3>
                                        <p className={`text-[11px] ${isAktif ? 'text-slate-300/80' : 'text-slate-500'}`}>
                                            Rekening Utama
                                        </p>
                                    </div>
                                </div>
                                {isAktif ? (
                                    <span className="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-400/30">
                                        <Check size={10} strokeWidth={3} />
                                        Aktif
                                    </span>
                                ) : (
                                    <span className="text-[11px] font-medium text-slate-500 group-hover:text-slate-700">
                                        Pilih
                                    </span>
                                )}
                            </div>

                            <div className="mt-3">
                                <p className={`text-[11px] font-medium ${isAktif ? 'text-slate-300/80' : 'text-slate-500'}`}>
                                    Saldo Saat Ini
                                </p>
                                <p className={`text-xl sm:text-2xl font-extrabold tracking-tight tabular-nums privacy-blur mt-0.5 truncate ${isAktif ? 'text-white' : 'text-slate-900'}`}>
                                    {formatRupiah(saldoBank ?? 0)}
                                </p>
                            </div>
                        </button>
                    );
                })()}

                {/* 3. Akun Kas Kecil */}
                {(() => {
                    const isAktif = kanalAktif === 'kas_kecil';
                    return (
                        <button
                            type="button"
                            role="tab"
                            aria-selected={isAktif}
                            onClick={() => terapkan({ kanal: 'kas_kecil' })}
                            className={`group text-left p-3.5 sm:p-4 rounded-2xl border transition-all duration-150 flex flex-col justify-between ${fokusRing} ${
                                isAktif
                                    ? 'bg-gradient-to-br from-[#0B1527] via-[#0F1E36] to-[#0A2E1A] text-white border-slate-800 shadow-sm'
                                    : 'bg-white text-slate-800 border-slate-200/90 hover:border-slate-300 shadow-2xs'
                            }`}
                        >
                            <div className="flex items-center justify-between gap-2">
                                <div className="flex items-center gap-2.5">
                                    <span className={`w-8 h-8 rounded-xl flex items-center justify-center shrink-0 ${
                                        isAktif ? 'bg-white/10 text-emerald-400' : 'bg-slate-100 text-slate-600'
                                    }`}>
                                        <Wallet size={16} />
                                    </span>
                                    <div>
                                        <h3 className={`text-xs font-bold uppercase tracking-wider ${isAktif ? 'text-white' : 'text-slate-800'}`}>
                                            Kas Kecil
                                        </h3>
                                        <p className={`text-[11px] ${isAktif ? 'text-slate-300/80' : 'text-slate-500'}`}>
                                            Operasional Tunai
                                        </p>
                                    </div>
                                </div>
                                {isAktif ? (
                                    <span className="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-400/30">
                                        <Check size={10} strokeWidth={3} />
                                        Aktif
                                    </span>
                                ) : (
                                    <span className="text-[11px] font-medium text-slate-500 group-hover:text-slate-700">
                                        Pilih
                                    </span>
                                )}
                            </div>

                            <div className="mt-3">
                                <p className={`text-[11px] font-medium ${isAktif ? 'text-slate-300/80' : 'text-slate-500'}`}>
                                    Saldo Saat Ini
                                </p>
                                <p className={`text-xl sm:text-2xl font-extrabold tracking-tight tabular-nums privacy-blur mt-0.5 truncate ${isAktif ? 'text-white' : 'text-slate-900'}`}>
                                    {formatRupiah(saldoKasKecil ?? 0)}
                                </p>
                            </div>
                        </button>
                    );
                })()}
            </div>

            {/* Filter Card: Ultra-Minimalist Single-Bar Layout */}
            <Card padding="sm" className="shadow-xs border-slate-200/80 mb-4">
                <div className="flex items-center justify-between gap-2.5 flex-wrap">
                    <div className="flex items-center gap-2 flex-wrap flex-1 min-w-[280px]">
                        {/* 1. Periode Capsule with Popover */}
                        <div className="relative" ref={periodeRef}>
                            <div className="flex items-center rounded-xl border border-slate-300 bg-white overflow-hidden shadow-2xs">
                                {!isRentangAktif && (
                                    <button
                                        type="button"
                                        onClick={() => geserBulan(-1)}
                                        aria-label="Bulan sebelumnya"
                                        className={`px-2 py-1.5 text-slate-500 hover:text-slate-700 hover:bg-slate-50 transition-colors ${fokusRing}`}
                                    >
                                        <ChevronLeft size={15} />
                                    </button>
                                )}
                                <button
                                    type="button"
                                    onClick={() => setShowPeriodePopover(!showPeriodePopover)}
                                    className={`px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors flex items-center gap-1.5 ${fokusRing}`}
                                >
                                    <CalendarDays size={13} className="text-slate-400" />
                                    <span>{labelPeriode}</span>
                                    <ChevronDown size={11} className="text-slate-400" />
                                </button>
                                {isRentangAktif ? (
                                    <button
                                        type="button"
                                        onClick={() => terapkan({ dari_tanggal: '', sampai_tanggal: '', bulan: kunciBulanIni })}
                                        aria-label="Reset rentang tanggal"
                                        title="Kembali ke bulan ini"
                                        className={`px-2 py-1.5 text-rose-500 hover:text-rose-700 hover:bg-rose-50 border-l border-slate-200 transition-colors ${fokusRing}`}
                                    >
                                        <X size={13} />
                                    </button>
                                ) : (
                                    <button
                                        type="button"
                                        onClick={() => geserBulan(1)}
                                        aria-label="Bulan berikutnya"
                                        className={`px-2 py-1.5 text-slate-500 hover:text-slate-700 hover:bg-slate-50 transition-colors ${fokusRing}`}
                                    >
                                        <ChevronRight size={15} />
                                    </button>
                                )}
                            </div>

                            {/* Periode Popover Flyout */}
                            {showPeriodePopover && (
                                <div className="absolute left-0 top-full mt-2 z-50 w-72 sm:w-80 p-3 bg-white rounded-2xl shadow-xl border border-slate-200 animate-in fade-in zoom-in-95 duration-150">
                                    <div className="flex items-center p-0.5 mb-3 rounded-lg bg-slate-100 border border-slate-200 text-xs">
                                        <button
                                            type="button"
                                            onClick={() => setTabPeriode('bulan')}
                                            className={`flex-1 py-1 rounded-md font-semibold transition-all ${
                                                tabPeriode === 'bulan' ? 'bg-white text-slate-800 shadow-2xs' : 'text-slate-500 hover:text-slate-800'
                                            }`}
                                        >
                                            Per Bulan
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => setTabPeriode('rentang')}
                                            className={`flex-1 py-1 rounded-md font-semibold transition-all ${
                                                tabPeriode === 'rentang' ? 'bg-white text-slate-800 shadow-2xs' : 'text-slate-500 hover:text-slate-800'
                                            }`}
                                        >
                                            Rentang Tanggal
                                        </button>
                                    </div>

                                    {tabPeriode === 'bulan' ? (
                                        <div className="space-y-3">
                                            <div>
                                                <label className="block text-[11px] font-semibold text-slate-500 mb-1">
                                                    Pilih Bulan
                                                </label>
                                                <input
                                                    type="month"
                                                    value={tempBulan}
                                                    onChange={(e) => setTempBulan(e.target.value)}
                                                    className="w-full px-2.5 py-1.5 text-xs font-semibold rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-green/30"
                                                />
                                            </div>
                                            <div className="flex items-center gap-1.5">
                                                <button
                                                    type="button"
                                                    onClick={() => setTempBulan(kunciBulanIni)}
                                                    className="px-2.5 py-1 text-xs font-semibold rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition-colors"
                                                >
                                                    Bulan Ini
                                                </button>
                                                <button
                                                    type="button"
                                                    onClick={() => {
                                                        const d = new Date();
                                                        d.setMonth(d.getMonth() - 1);
                                                        setTempBulan(`${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`);
                                                    }}
                                                    className="px-2.5 py-1 text-xs font-semibold rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition-colors"
                                                >
                                                    Bulan Lalu
                                                </button>
                                            </div>
                                            <div className="pt-2 border-t border-slate-100 flex justify-end gap-2">
                                                <button
                                                    type="button"
                                                    onClick={() => setShowPeriodePopover(false)}
                                                    className="px-3 py-1 text-xs font-semibold text-slate-500 hover:text-slate-700"
                                                >
                                                    Batal
                                                </button>
                                                <Button size="xs" onClick={terapkanBulanPilihan}>
                                                    Terapkan
                                                </Button>
                                            </div>
                                        </div>
                                    ) : (
                                        <div className="space-y-3">
                                            <div className="grid grid-cols-2 gap-2">
                                                <div>
                                                    <label className="block text-[11px] font-semibold text-slate-500 mb-1">
                                                        Dari Tanggal
                                                    </label>
                                                    <input
                                                        type="date"
                                                        value={tempDari}
                                                        onChange={(e) => setTempDari(e.target.value)}
                                                        className="w-full px-2 py-1.5 text-xs font-medium rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-green/30"
                                                    />
                                                </div>
                                                <div>
                                                    <label className="block text-[11px] font-semibold text-slate-500 mb-1">
                                                        Sampai Tanggal
                                                    </label>
                                                    <input
                                                        type="date"
                                                        value={tempSampai}
                                                        onChange={(e) => setTempSampai(e.target.value)}
                                                        className="w-full px-2 py-1.5 text-xs font-medium rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-green/30"
                                                    />
                                                </div>
                                            </div>
                                            <div>
                                                <p className="text-[10px] uppercase font-bold text-slate-400 tracking-wider mb-1.5">
                                                    Pintasan Cepat
                                                </p>
                                                <div className="flex items-center gap-1.5 flex-wrap">
                                                    <button
                                                        type="button"
                                                        onClick={() => setPresetRentang(1)}
                                                        className="px-2 py-0.5 text-xs font-medium rounded-md bg-slate-100 hover:bg-slate-200 text-slate-700 transition-colors"
                                                    >
                                                        Hari Ini
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() => setPresetRentang(7)}
                                                        className="px-2 py-0.5 text-xs font-medium rounded-md bg-slate-100 hover:bg-slate-200 text-slate-700 transition-colors"
                                                    >
                                                        7 Hari
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() => setPresetRentang(30)}
                                                        className="px-2 py-0.5 text-xs font-medium rounded-md bg-slate-100 hover:bg-slate-200 text-slate-700 transition-colors"
                                                    >
                                                        30 Hari
                                                    </button>
                                                </div>
                                            </div>
                                            <div className="pt-2 border-t border-slate-100 flex justify-end gap-2">
                                                <button
                                                    type="button"
                                                    onClick={() => setShowPeriodePopover(false)}
                                                    className="px-3 py-1 text-xs font-semibold text-slate-500 hover:text-slate-700"
                                                >
                                                    Batal
                                                </button>
                                                <Button size="xs" onClick={terapkanRentang}>
                                                    Terapkan Rentang
                                                </Button>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            )}
                        </div>

                        {/* 2. Segmented Pill: Tipe Transaksi */}
                        <div className="inline-flex items-center p-0.5 rounded-xl bg-slate-100 border border-slate-200/80 text-xs shrink-0">
                            <button
                                type="button"
                                onClick={() => terapkan({ tipe: '' })}
                                className={`px-2.5 py-1 rounded-lg font-semibold transition-all ${
                                    !tipeFilter ? 'bg-white text-slate-800 shadow-2xs' : 'text-slate-500 hover:text-slate-800'
                                }`}
                            >
                                Semua
                            </button>
                            <button
                                type="button"
                                onClick={() => terapkan({ tipe: 'masuk' })}
                                className={`px-2.5 py-1 rounded-lg font-semibold transition-all ${
                                    tipeFilter === 'masuk' ? 'bg-white text-emerald-700 shadow-2xs' : 'text-slate-500 hover:text-emerald-700'
                                }`}
                            >
                                + Masuk
                            </button>
                            <button
                                type="button"
                                onClick={() => terapkan({ tipe: 'keluar' })}
                                className={`px-2.5 py-1 rounded-lg font-semibold transition-all ${
                                    tipeFilter === 'keluar' ? 'bg-white text-rose-700 shadow-2xs' : 'text-slate-500 hover:text-rose-700'
                                }`}
                            >
                                − Keluar
                            </button>
                        </div>

                        {/* 3. Dropdown Popover: Kategori Transaksi */}
                        <div className="relative shrink-0" ref={kategoriRef}>
                            <button
                                type="button"
                                onClick={() => setShowKategoriPopover(!showKategoriPopover)}
                                className={`inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border text-xs font-semibold transition-all ${fokusRing} ${
                                    kategoriFilter
                                        ? 'bg-brand-green-light/70 text-brand-green-dark border-brand-green/30'
                                        : 'bg-white text-slate-600 border-slate-200/90 hover:bg-slate-50'
                                }`}
                            >
                                <SlidersHorizontal size={13} className={kategoriFilter ? 'text-brand-green' : 'text-slate-400'} />
                                <span className="max-w-[120px] truncate">
                                    {kategoriFilter ? (kategoriOptions[kategoriFilter] ?? kategoriLabel[kategoriFilter] ?? kategoriFilter) : 'Kategori'}
                                </span>
                                <ChevronDown size={11} className={kategoriFilter ? 'text-brand-green' : 'text-slate-400'} />
                            </button>

                            {/* Kategori Popover Flyout */}
                            {showKategoriPopover && (
                                <div className="absolute left-0 top-full mt-2 z-50 w-64 p-2 bg-white rounded-2xl shadow-xl border border-slate-200 animate-in fade-in zoom-in-95 duration-150">
                                    <div className="relative mb-2">
                                        <Search size={13} className="absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400" />
                                        <input
                                            type="text"
                                            value={kategoriCari}
                                            onChange={(e) => setKategoriCari(e.target.value)}
                                            placeholder="Cari kategori..."
                                            className="w-full pl-7 pr-2 py-1 text-xs rounded-lg border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-green/30"
                                        />
                                    </div>
                                    <div className="max-h-56 overflow-y-auto space-y-0.5 pr-1">
                                        <button
                                            type="button"
                                            onClick={() => {
                                                terapkan({ kategori: '' });
                                                setShowKategoriPopover(false);
                                            }}
                                            className={`w-full text-left px-2.5 py-1.5 rounded-lg text-xs font-medium flex items-center justify-between transition-colors ${
                                                !kategoriFilter ? 'bg-slate-100 font-semibold text-slate-900' : 'text-slate-600 hover:bg-slate-50'
                                            }`}
                                        >
                                            <span>Semua Kategori</span>
                                            {!kategoriFilter && <Check size={12} className="text-slate-700" />}
                                        </button>
                                        {opsiKategoriTersaring.map(([k, label]) => {
                                            const aktif = kategoriFilter === k;
                                            return (
                                                <button
                                                    key={k}
                                                    type="button"
                                                    onClick={() => {
                                                        terapkan({ kategori: k });
                                                        setShowKategoriPopover(false);
                                                    }}
                                                    className={`w-full text-left px-2.5 py-1.5 rounded-lg text-xs font-medium flex items-center justify-between transition-colors ${
                                                        aktif ? 'bg-emerald-50 font-semibold text-emerald-800' : 'text-slate-600 hover:bg-slate-50'
                                                    }`}
                                                >
                                                    <span className="truncate">{label}</span>
                                                    {aktif && <Check size={12} className="text-emerald-600 shrink-0" />}
                                                </button>
                                            );
                                        })}
                                    </div>
                                </div>
                            )}
                        </div>

                        {/* 4. Search Field */}
                        <div className="relative flex-1 min-w-[170px] max-w-xs">
                            <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
                            <input
                                type="text"
                                value={cari}
                                onChange={(e) => setCari(e.target.value)}
                                placeholder="Cari uraian / no. bukti..."
                                aria-label="Cari transaksi"
                                className="w-full h-8 pl-8 pr-7 text-xs rounded-xl border border-slate-200/90 bg-white placeholder:text-slate-400 text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-green/30 focus:border-brand-green transition-all"
                            />
                            {cari && (
                                <button
                                    type="button"
                                    onClick={() => setCari('')}
                                    aria-label="Hapus pencarian"
                                    className="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                                >
                                    <X size={13} />
                                </button>
                            )}
                        </div>
                    </div>

                    {/* Right side: Mini summary & dynamic Reset */}
                    <div className="flex items-center gap-2 shrink-0">
                        {filterAktif && (
                            <button
                                type="button"
                                onClick={resetFilter}
                                className="text-xs font-bold text-rose-600 hover:text-rose-700 bg-rose-50 hover:bg-rose-100/80 rounded-full px-2.5 py-1 transition-colors whitespace-nowrap flex items-center gap-1"
                            >
                                <X size={12} />
                                Reset
                            </button>
                        )}
                        <span className="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold whitespace-nowrap bg-emerald-50 text-emerald-700 border border-emerald-200/60 tabular-nums">
                            +{formatRupiahSingkat(ringkasanPeriode?.total_masuk ?? 0)}
                        </span>
                        <span className="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold whitespace-nowrap bg-rose-50 text-rose-700 border border-rose-200/60 tabular-nums">
                            -{formatRupiahSingkat(ringkasanPeriode?.total_keluar ?? 0)}
                        </span>
                    </div>
                </div>

                {/* Subline: Status Filter Ringkas */}
                <div className="flex items-center gap-2 min-w-0 px-1 pt-2 mt-2 border-t border-slate-100 flex-wrap text-xs text-slate-500">
                    <CalendarDays size={13} className="text-slate-400 shrink-0" />
                    <span>
                        Buku kas <strong className="text-slate-700 font-semibold">{labelPeriode}</strong>
                    </span>
                    {kanalAktif && (
                        <span>
                            • Akun: <strong className="text-slate-700 font-semibold">{kanalAktif === 'bank' ? 'Bank' : 'Kas Kecil'}</strong>
                        </span>
                    )}
                    {tipeFilter && (
                        <span>
                            • Tipe: <strong className="text-slate-700 font-semibold">{tipeFilter === 'masuk' ? 'Uang Masuk (+)' : 'Uang Keluar (−)'}</strong>
                        </span>
                    )}
                    {kategoriFilter && (
                        <span>
                            • Kategori: <strong className="text-slate-700 font-semibold">{kategoriOptions[kategoriFilter] ?? kategoriLabel[kategoriFilter] ?? kategoriFilter}</strong>
                        </span>
                    )}
                    {!saldoValid && (
                        <span className="text-[11px] text-amber-700 bg-amber-50 border border-amber-200 rounded-full px-2 py-0.2">
                            Kolom saldo disembunyikan — filter aktif membatasi baris transaksi
                        </span>
                    )}
                </div>
            </Card>

            {/* Tabel Buku Kas (Jurnal Kas Style) */}
            <Card padding="sm" className="shadow-xs border-slate-200/80 mb-4 overflow-hidden">
                {daftarRiwayat.length === 0 ? (
                    <div className="text-center py-12 px-4">
                        <BookOpenText size={32} aria-hidden="true" className="mx-auto text-slate-300 mb-3" />
                        <p className="text-sm font-semibold text-slate-700">
                            Belum ada mutasi kas pada filter ini untuk {labelBulan}.
                        </p>
                        <p className="text-xs text-slate-400 mt-1">
                            Coba ubah filter atau bulan untuk melihat transaksi lainnya.
                        </p>
                    </div>
                ) : (
                    <>
                        {/* Desktop Table View */}
                        <div className="overflow-x-auto">
                            <table className="hidden md:table w-full text-sm border-collapse">
                                <thead>
                                    <tr className="text-left text-[11px] uppercase tracking-wide text-slate-400 border-b border-slate-200">
                                        <th scope="col" className="py-2.5 pr-2 font-semibold w-24">Tgl</th>
                                        <th scope="col" className="py-2.5 pr-2 font-semibold w-32">No. Bukti</th>
                                        <th scope="col" className="py-2.5 pr-2 font-semibold">Keterangan</th>
                                        <th scope="col" className="py-2.5 pr-2 font-semibold w-24">Akun</th>
                                        <th scope="col" className="py-2.5 pr-2 font-semibold text-right w-36">Masuk</th>
                                        <th scope="col" className="py-2.5 pr-2 font-semibold text-right w-36">Keluar</th>
                                        <th scope="col" className="py-2.5 font-semibold text-right w-40">Saldo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {/* Baris Saldo Awal */}
                                    <tr className="border-b border-slate-100 bg-slate-50/70">
                                        <td colSpan={4} className="py-2 pr-2 text-xs font-bold text-slate-600">
                                            Saldo awal{labelAkunSaldo} {labelBulan}
                                        </td>
                                        <td colSpan={2} className="py-2 pr-2 text-right text-xs text-slate-400">
                                            {saldoValid ? '' : 'saldo terbatas filter'}
                                        </td>
                                        <td className="py-2 text-right font-bold tabular-nums text-slate-700 privacy-blur">
                                            {saldoValid ? formatRupiah(saldoAwal) : '—'}
                                        </td>
                                    </tr>

                                    {/* Baris Transaksi Riwayat */}
                                    {daftarRiwayat.map((r) => (
                                        <tr key={r.id} className="border-b border-slate-50 hover:bg-slate-50/80 transition-colors align-top">
                                            <td className="py-2.5 pr-2 text-xs text-slate-500 whitespace-nowrap tabular-nums">
                                                {r.tanggal}
                                            </td>
                                            <td className="py-2.5 pr-2 text-xs text-slate-500 whitespace-nowrap tabular-nums font-mono">
                                                {r.no_bukti ?? '—'}
                                            </td>
                                            <td className="py-2.5 pr-2">
                                                {barisKeterangan(r)}
                                            </td>
                                            <td className="py-2.5 pr-2">
                                                {badgeKanal(r.kanal ?? r.akun)}
                                            </td>
                                            <td className="py-2.5 pr-2 text-right whitespace-nowrap tabular-nums privacy-blur">
                                                {r.tipe === 'masuk' ? (
                                                    <span className="font-bold text-emerald-700">
                                                        + {formatRupiah(r.jumlah)}
                                                    </span>
                                                ) : (
                                                    <span className="text-slate-300">—</span>
                                                )}
                                            </td>
                                            <td className="py-2.5 pr-2 text-right whitespace-nowrap tabular-nums privacy-blur">
                                                {r.tipe === 'keluar' ? (
                                                    <span className="font-bold text-rose-600">
                                                        - {formatRupiah(r.jumlah)}
                                                    </span>
                                                ) : (
                                                    <span className="text-slate-300">—</span>
                                                )}
                                            </td>
                                            <td className="py-2.5 text-right whitespace-nowrap tabular-nums font-medium text-slate-700 privacy-blur">
                                                {saldoValid ? formatRupiah(r.saldo_gabungan_setelah ?? r.saldo_setelah) : '—'}
                                            </td>
                                        </tr>
                                    ))}

                                    {/* Baris Total Mutasi */}
                                    <tr className="border-t-2 border-slate-200 bg-slate-50/70">
                                        <td colSpan={4} className="py-2.5 pr-2 text-xs font-bold text-slate-700">
                                            Total {labelBulan}
                                        </td>
                                        <td className="py-2.5 pr-2 text-right font-bold tabular-nums text-emerald-700 privacy-blur">
                                            + {formatRupiah(ringkasanPeriode?.total_masuk ?? 0)}
                                        </td>
                                        <td className="py-2.5 pr-2 text-right font-bold tabular-nums text-rose-600 privacy-blur">
                                            - {formatRupiah(ringkasanPeriode?.total_keluar ?? 0)}
                                        </td>
                                        <td className="py-2.5 text-right font-bold tabular-nums text-slate-800 privacy-blur">
                                            {saldoValid ? formatRupiah(saldoAkhir) : '—'}
                                        </td>
                                    </tr>

                                    {/* Baris Saldo Akhir */}
                                    {saldoValid && (
                                        <tr className="bg-slate-50/70 -mt-px">
                                            <td colSpan={6} className="py-1 pr-2 text-xs text-slate-500 text-right">
                                                Saldo akhir{labelAkunSaldo} {labelBulan}
                                            </td>
                                            <td className="py-1 text-right text-xs font-bold tabular-nums text-slate-900 privacy-blur">
                                                {formatRupiah(saldoAkhir)}
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>

                        {/* Mobile List View */}
                        <ul className="md:hidden divide-y divide-slate-100">
                            {/* Saldo Awal Mobile */}
                            <li className="flex items-center justify-between gap-3 py-2.5 bg-slate-50 rounded-lg px-2.5 -mx-1 mb-1">
                                <div>
                                    <p className="text-xs font-bold text-slate-700">Saldo Awal {labelBulan}</p>
                                    <p className="text-[10px] text-slate-400">Pembuka periode</p>
                                </div>
                                <p className="text-sm font-bold tabular-nums text-slate-700 privacy-blur">
                                    {saldoValid ? formatRupiah(saldoAwal) : '—'}
                                </p>
                            </li>

                            {daftarRiwayat.map((r) => (
                                <li key={r.id} className="py-3">
                                    <div className="flex items-start justify-between gap-2 mb-1">
                                        <div className="flex items-center gap-1.5 text-xs text-slate-500">
                                            <span className="tabular-nums">{r.tanggal}</span>
                                            <span>•</span>
                                            <span className="font-mono text-[11px]">{r.no_bukti ?? '—'}</span>
                                        </div>
                                        {badgeKanal(r.kanal ?? r.akun)}
                                    </div>

                                    <div className="mb-2">
                                        {barisKeterangan(r)}
                                    </div>

                                    <div className="flex items-center justify-between pt-1.5 border-t border-slate-50 text-xs">
                                        <span className="text-slate-400">
                                            {saldoValid ? `Saldo: ${formatRupiah(r.saldo_gabungan_setelah ?? r.saldo_setelah)}` : ''}
                                        </span>
                                        <span className={`font-bold tabular-nums privacy-blur ${r.tipe === 'masuk' ? 'text-emerald-700' : 'text-rose-600'}`}>
                                            {r.tipe === 'masuk' ? `+ ${formatRupiah(r.jumlah)}` : `- ${formatRupiah(r.jumlah)}`}
                                        </span>
                                    </div>
                                </li>
                            ))}

                            {/* Saldo Akhir Mobile */}
                            {saldoValid && (
                                <li className="flex items-center justify-between gap-3 py-2.5 bg-slate-50 rounded-lg px-2.5 -mx-1 mt-1">
                                    <div>
                                        <p className="text-xs font-bold text-slate-700">Saldo Akhir {labelBulan}</p>
                                        <p className="text-[10px] text-slate-400">Penutup periode</p>
                                    </div>
                                    <p className="text-sm font-bold tabular-nums text-slate-900 privacy-blur">
                                        {formatRupiah(saldoAkhir)}
                                    </p>
                                </li>
                            )}
                        </ul>

                        {/* Pagination jika ada */}
                        {riwayat?.links && riwayat?.last_page > 1 && (
                            <div className="pt-4 border-t border-slate-100">
                                <Pagination links={riwayat.links} />
                            </div>
                        )}
                    </>
                )}
            </Card>

            {/* Drawer Form Transaksi Bendahara */}
            {bisaTopup && (
                <Drawer
                    open={showDrawer}
                    onClose={() => setShowDrawer(false)}
                    title={
                        tabDrawer === 'pemasukan'
                            ? 'Catat Pemasukan Kas'
                            : tabDrawer === 'pengeluaran'
                            ? 'Catat Pengeluaran Kas'
                            : 'Sisih Kas Kecil'
                    }
                    description={
                        tabDrawer === 'pemasukan'
                            ? 'Tambah modal atau catat pendapatan bunga bank ke kas koperasi'
                            : tabDrawer === 'pengeluaran'
                            ? 'Catat biaya operasional atau pengeluaran resmi koperasi'
                            : 'Pindahkan dana dari Bank ke Kas Kecil untuk operasional tunai'
                    }
                >
                    {/* Tab Switcher dalam Drawer */}
                    <div className="flex rounded-xl bg-slate-100 p-1 mb-5">
                        <button
                            type="button"
                            onClick={() => setTabDrawer('pemasukan')}
                            className={`flex-1 py-1.5 text-xs font-semibold rounded-lg transition-colors ${
                                tabDrawer === 'pemasukan'
                                    ? 'bg-white text-emerald-800 shadow-xs'
                                    : 'text-slate-600 hover:text-slate-900'
                            }`}
                        >
                            Pemasukan
                        </button>
                        <button
                            type="button"
                            onClick={() => setTabDrawer('pengeluaran')}
                            className={`flex-1 py-1.5 text-xs font-semibold rounded-lg transition-colors ${
                                tabDrawer === 'pengeluaran'
                                    ? 'bg-white text-rose-800 shadow-xs'
                                    : 'text-slate-600 hover:text-slate-900'
                            }`}
                        >
                            Pengeluaran
                        </button>
                        <button
                            type="button"
                            onClick={() => setTabDrawer('sisih')}
                            className={`flex-1 py-1.5 text-xs font-semibold rounded-lg transition-colors ${
                                tabDrawer === 'sisih'
                                    ? 'bg-white text-amber-800 shadow-xs'
                                    : 'text-slate-600 hover:text-slate-900'
                            }`}
                        >
                            Sisih Kas Kecil
                        </button>
                    </div>

                    {/* Form Pemasukan */}
                    {tabDrawer === 'pemasukan' && (
                        <form onSubmit={submitPemasukan} className="space-y-4">
                            <FormField label="Sumber Pemasukan" required>
                                <Select
                                    size="sm"
                                    value={pemasukanForm.data.sumber_topup}
                                    onChange={(e) => pemasukanForm.setData('sumber_topup', e.target.value)}
                                >
                                    <option value="modal">Penambahan Modal</option>
                                    <option value="bunga_bank">Pendapatan Bunga Bank</option>
                                </Select>
                            </FormField>

                            <FormField label="Nominal Pemasukan" error={pemasukanForm.errors.jumlah} required>
                                <div className="relative">
                                    <span className="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-semibold text-slate-400">Rp</span>
                                    <TextField
                                        size="sm"
                                        type="number"
                                        value={pemasukanForm.data.jumlah}
                                        onChange={(e) => pemasukanForm.setData('jumlah', e.target.value)}
                                        placeholder="0"
                                        className="pl-9"
                                    />
                                </div>
                                <ChipNominal grup="topup" nilai={pemasukanForm.data.jumlah} onPilih={(n) => pemasukanForm.setData('jumlah', n)} />
                            </FormField>

                            <FormField label="Keterangan" error={pemasukanForm.errors.keterangan} hint="Contoh: Setoran modal operasional dari pengurus">
                                <TextField
                                    size="sm"
                                    value={pemasukanForm.data.keterangan}
                                    onChange={(e) => pemasukanForm.setData('keterangan', e.target.value)}
                                    placeholder="Keterangan pemasukan"
                                />
                            </FormField>

                            <div className="pt-2 flex justify-end gap-2 border-t border-slate-100">
                                <Button type="button" variant="secondary" size="sm" onClick={() => setShowDrawer(false)}>
                                    Batal
                                </Button>
                                <Button type="submit" size="sm" disabled={pemasukanForm.processing}>
                                    {pemasukanForm.processing ? 'Menyimpan...' : 'Simpan Pemasukan'}
                                </Button>
                            </div>
                        </form>
                    )}

                    {/* Form Pengeluaran */}
                    {tabDrawer === 'pengeluaran' && (
                        <form onSubmit={submitPengeluaran} className="space-y-4">
                            <FormField label="Sumber Dana" required>
                                <Select
                                    size="sm"
                                    value={pengeluaranForm.data.akun}
                                    onChange={(e) => pengeluaranForm.setData('akun', e.target.value)}
                                >
                                    <option value="kas_kecil">Kas Kecil (Saldo: {formatRupiah(saldoKasKecil ?? 0)})</option>
                                    <option value="bank">Kas Bank (Saldo: {formatRupiah(saldoBank ?? 0)})</option>
                                </Select>
                            </FormField>

                            <FormField label="Jenis Pengeluaran" error={pengeluaranForm.errors.tipe} required>
                                <Select
                                    size="sm"
                                    value={pengeluaranForm.data.tipe}
                                    onChange={(e) => pengeluaranForm.setData('tipe', e.target.value)}
                                >
                                    {Object.entries(tipePengeluaranList).map(([k, v]) => (
                                        <option key={k} value={k}>{v}</option>
                                    ))}
                                </Select>
                            </FormField>

                            <FormField label="Nominal Pengeluaran" error={pengeluaranForm.errors.jumlah} required>
                                <div className="relative">
                                    <span className="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-semibold text-slate-400">Rp</span>
                                    <TextField
                                        size="sm"
                                        type="number"
                                        value={pengeluaranForm.data.jumlah}
                                        onChange={(e) => pengeluaranForm.setData('jumlah', e.target.value)}
                                        placeholder="0"
                                        className="pl-9"
                                    />
                                </div>
                                <ChipNominal grup="pengeluaran" nilai={pengeluaranForm.data.jumlah} onPilih={(n) => pengeluaranForm.setData('jumlah', n)} />
                            </FormField>

                            <FormField label="Keterangan / Keperluan" error={pengeluaranForm.errors.keterangan} required hint="Jelaskan kebutuhan pengeluaran secara lengkap">
                                <TextField
                                    size="sm"
                                    value={pengeluaranForm.data.keterangan}
                                    onChange={(e) => pengeluaranForm.setData('keterangan', e.target.value)}
                                    placeholder="Contoh: Pembelian ATK dan kertas printer"
                                />
                            </FormField>

                            <div className="pt-2 flex justify-end gap-2 border-t border-slate-100">
                                <Button type="button" variant="secondary" size="sm" onClick={() => setShowDrawer(false)}>
                                    Batal
                                </Button>
                                <Button type="submit" size="sm" disabled={pengeluaranForm.processing}>
                                    {pengeluaranForm.processing ? 'Menyimpan...' : 'Simpan Pengeluaran'}
                                </Button>
                            </div>
                        </form>
                    )}

                    {/* Form Sisih Kas Kecil */}
                    {tabDrawer === 'sisih' && (
                        <form onSubmit={submitSisih} className="space-y-4">
                            <div className="p-3 bg-amber-50 rounded-xl border border-amber-200/70 text-xs text-amber-800 leading-relaxed">
                                <p className="font-semibold text-amber-900 mb-1">Pemindahan Dana Bank &rarr; Kas Kecil</p>
                                Saldo Kas Bank saat ini: <strong>{formatRupiah(saldoBank ?? 0)}</strong>.
                                Dana yang disisihkan akan ditarik dari Bank dan masuk ke saldo Kas Kecil untuk kebutuhan operasional tunai.
                            </div>

                            <FormField label="Nominal Sisih" error={sisihForm.errors.jumlah} required>
                                <div className="relative">
                                    <span className="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-semibold text-slate-400">Rp</span>
                                    <TextField
                                        size="sm"
                                        type="number"
                                        value={sisihForm.data.jumlah}
                                        onChange={(e) => sisihForm.setData('jumlah', e.target.value)}
                                        placeholder="0"
                                        className="pl-9"
                                    />
                                </div>
                                <ChipNominal grup="sisih" nilai={sisihForm.data.jumlah} onPilih={(n) => sisihForm.setData('jumlah', n)} />
                            </FormField>

                            <FormField label="Keterangan" error={sisihForm.errors.keterangan} hint="Contoh: Kas operasional sekretariat minggu ke-2">
                                <TextField
                                    size="sm"
                                    value={sisihForm.data.keterangan}
                                    onChange={(e) => sisihForm.setData('keterangan', e.target.value)}
                                    placeholder="Keterangan penyisihan dana"
                                />
                            </FormField>

                            <div className="pt-2 flex justify-end gap-2 border-t border-slate-100">
                                <Button type="button" variant="secondary" size="sm" onClick={() => setShowDrawer(false)}>
                                    Batal
                                </Button>
                                <Button type="submit" size="sm" disabled={sisihForm.processing}>
                                    {sisihForm.processing ? 'Menyimpan...' : 'Sisih Sekarang'}
                                </Button>
                            </div>
                        </form>
                    )}
                </Drawer>
            )}
        </AppLayout>
    );
}
