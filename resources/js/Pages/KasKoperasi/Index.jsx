import AppLayout from '@/Layouts/AppLayout';
import { Head, usePage, useForm, router, Link } from '@inertiajs/react';
import {
    Wallet, ArrowDownCircle, ArrowUpCircle, ChevronLeft, ChevronRight,
    Landmark, Building2, CalendarDays, Search, X, ArrowRightLeft, Check, BookOpenText
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
            bulan: bulanFilter,
            kanal: kanalAktif,
            cari,
            page: 1,
            ...overrides,
        };

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
        const [tahun, bulanAngka] = bulanFilter.split('-').map(Number);
        const d = new Date(tahun, bulanAngka - 1 + delta, 1);
        const blnBaru = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
        terapkan({ bulan: blnBaru });
    }

    function resetFilter() {
        setCari('');
        router.get(
            route('kas-koperasi.index'),
            { bulan: bulanFilter },
            { preserveState: true, replace: true }
        );
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

    const labelAkunSaldo = kanalAktif === 'bank'
        ? ' Bank'
        : kanalAktif === 'kas_kecil'
        ? ' Kas Kecil'
        : '';

    const daftarRiwayat = Array.isArray(riwayat) ? riwayat : (riwayat?.data ?? []);
    const filterAktif = Boolean(kanalAktif || filters.cari || filters.kategori || filters.kantong);
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

            {/* Filter Card & Periode Stepper */}
            <Card padding="sm" className="shadow-xs border-slate-200/80 mb-4">
                <div className="flex items-center justify-between gap-3 flex-wrap">
                    <div className="flex items-center gap-2.5 flex-wrap flex-1 min-w-[280px]">
                        {/* Stepper Bulan */}
                        <div className="flex items-center rounded-xl border border-slate-300 bg-white overflow-hidden shadow-2xs">
                            <button
                                type="button"
                                onClick={() => geserBulan(-1)}
                                aria-label="Bulan sebelumnya"
                                className={`px-2 py-2 text-slate-500 hover:text-slate-700 hover:bg-slate-50 transition-colors ${fokusRing}`}
                            >
                                <ChevronLeft size={16} />
                            </button>
                            <input
                                type="month"
                                value={bulanFilter}
                                onChange={(e) => e.target.value && terapkan({ bulan: e.target.value })}
                                aria-label="Pilih bulan"
                                className="px-2 py-1.5 text-sm font-semibold text-slate-700 border-x border-slate-200 bg-transparent focus:outline-none"
                            />
                            <button
                                type="button"
                                onClick={() => geserBulan(1)}
                                aria-label="Bulan berikutnya"
                                className={`px-2 py-2 text-slate-500 hover:text-slate-700 hover:bg-slate-50 transition-colors ${fokusRing}`}
                            >
                                <ChevronRight size={16} />
                            </button>
                        </div>

                        {/* Search Field */}
                        <div className="relative flex-1 min-w-[200px] max-w-md">
                            <Search size={15} className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
                            <TextField
                                size="sm"
                                value={cari}
                                onChange={(e) => setCari(e.target.value)}
                                placeholder="Cari uraian atau no. bukti..."
                                aria-label="Cari transaksi"
                                className="pl-9 pr-8 text-sm"
                            />
                            {cari && (
                                <button
                                    type="button"
                                    onClick={() => setCari('')}
                                    aria-label="Hapus pencarian"
                                    className="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                                >
                                    <X size={15} />
                                </button>
                            )}
                        </div>
                    </div>

                    <div className="flex items-center gap-2 shrink-0">
                        {bulanFilter !== kunciBulanIni && (
                            <button
                                type="button"
                                onClick={() => terapkan({ bulan: kunciBulanIni })}
                                className="text-xs font-bold text-brand-green-dark bg-brand-green-light rounded-full px-2.5 py-1.5 hover:bg-brand-green/20 transition-colors whitespace-nowrap"
                            >
                                Bulan ini
                            </button>
                        )}
                        {filterAktif && (
                            <button
                                type="button"
                                onClick={resetFilter}
                                className="text-xs font-bold text-slate-500 hover:text-slate-700 rounded-full px-2.5 py-1.5 hover:bg-slate-100 transition-colors whitespace-nowrap"
                            >
                                Reset
                            </button>
                        )}
                        <span className="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold whitespace-nowrap bg-emerald-50 text-emerald-700 border border-emerald-200/60 tabular-nums">
                            +{formatRupiahSingkat(ringkasanPeriode?.total_masuk ?? 0)}
                        </span>
                        <span className="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold whitespace-nowrap bg-rose-50 text-rose-700 border border-rose-200/60 tabular-nums">
                            -{formatRupiahSingkat(ringkasanPeriode?.total_keluar ?? 0)}
                        </span>
                    </div>
                </div>

                <div className="flex items-center gap-2 min-w-0 px-1 pt-2.5 mt-2 border-t border-slate-100 flex-wrap">
                    <CalendarDays size={14} className="text-slate-400 shrink-0" />
                    <p className="text-xs text-slate-500 truncate">
                        Buku kas <span className="font-bold text-slate-700">{labelBulan}</span>
                        {kanalAktif ? (
                            <span className="ml-1 text-slate-400">
                                (Akun: <span className="font-semibold text-slate-600">{kanalAktif === 'bank' ? 'Bank' : 'Kas Kecil'}</span>)
                            </span>
                        ) : null}
                    </p>
                    {!saldoValid && (
                        <p className="text-[11px] text-amber-700 bg-amber-50 border border-amber-200 rounded-full px-2 py-0.5">
                            Kolom saldo disembunyikan — filter aktif membatasi baris transaksi
                        </p>
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
