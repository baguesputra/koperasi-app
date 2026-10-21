import AppLayout from '@/Layouts/AppLayout';
import { Head, usePage, useForm, router, Link } from '@inertiajs/react';
import {
    Wallet, ArrowDownCircle, ArrowUpCircle, Plus, ChevronLeft, ChevronRight,
    Landmark, CalendarDays, Search, X, ArrowRightLeft, Check
} from 'lucide-react';
import { useEffect, useMemo, useRef, useState, Fragment } from 'react';
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

// Kanal fisik tempat uang bergerak: bank atau kas kecil.
const kanalMeta = {
    bank: { label: 'Bank', ikon: Landmark, warna: 'bg-brand-navy/10 text-brand-navy' },
    kas_kecil: { label: 'Kas Kecil', ikon: Wallet, warna: 'bg-amber-50 text-amber-700' },
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
    ringkasanPeriode,
    ringkasanKanal,
    ringkasanPerTanggal,
    riwayat,
}) {
    const { auth } = usePage().props;
    const bisaTopup = auth.user?.permissions?.includes('kas.topup');
    const [showDrawer, setShowDrawer] = useState(false);
    const [tabDrawer, setTabDrawer] = useState('pemasukan');
    const [cari, setCari] = useState(filters.cari ?? '');
    const cariDebounced = useDebouncedValue(cari);
    const [tipe, setTipe] = useState(filters.tipe ?? '');
    const [tipePengeluaran, setTipePengeluaran] = useState(filters.tipe_pengeluaran ?? '');
    const [tanggal, setTanggal] = useState(filters.tanggal ?? '');
    const [memuatFilter, setMemuatFilter] = useState(false);
    const pertama = useRef(true);
    const hariIni = new Date().toISOString().slice(0, 10);

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
            kanal: filters.kanal ?? '',
            cari,
            tipe,
            tanggal,
            tipe_pengeluaran: tipePengeluaran,
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
                only: ['riwayat', 'ringkasanPeriode', 'ringkasanKanal', 'ringkasanPerTanggal', 'filters', 'bulanFilter'],
                onStart: () => setMemuatFilter(true),
                onFinish: () => setMemuatFilter(false),
            }
        );
    }

    function handlePilihTipe(tipeBaru) {
        setTipe(tipeBaru);
        const overrides = { tipe: tipeBaru };
        if (tipeBaru !== 'keluar') {
            setTipePengeluaran('');
            overrides.tipe_pengeluaran = '';
        }
        terapkan(overrides);
    }

    function handlePilihTipePengeluaran(tpBaru) {
        setTipePengeluaran(tpBaru);
        const overrides = { tipe_pengeluaran: tpBaru };
        if (tpBaru) {
            setTipe('keluar');
            overrides.tipe = 'keluar';
        }
        terapkan(overrides);
    }

    function handlePilihTanggal(tanggalBaru) {
        setTanggal(tanggalBaru);
        const overrides = { tanggal: tanggalBaru };
        if (tanggalBaru && tanggalBaru.length >= 7) {
            overrides.bulan = tanggalBaru.slice(0, 7);
        }
        terapkan(overrides);
    }

    function handleHapusTanggal() {
        setTanggal('');
        terapkan({ tanggal: '' });
    }

    function geserBulan(delta) {
        const [tahun, bulanAngka] = bulanFilter.split('-').map(Number);
        const d = new Date(tahun, bulanAngka - 1 + delta, 1);
        terapkan({
            bulan: `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`,
            tanggal: '',
        });
    }

    function resetFilter() {
        setCari('');
        setTipe('');
        setTipePengeluaran('');
        setTanggal('');
        router.get(
            route('kas-koperasi.index'),
            { bulan: bulanFilter, page: 1 },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only: ['riwayat', 'ringkasanPeriode', 'ringkasanKanal', 'ringkasanPerTanggal', 'filters', 'bulanFilter'],
                onStart: () => setMemuatFilter(true),
                onFinish: () => setMemuatFilter(false),
            }
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

    useEffect(() => {
        setTipe(filters.tipe ?? '');
    }, [filters.tipe]);

    useEffect(() => {
        setTipePengeluaran(filters.tipe_pengeluaran ?? '');
    }, [filters.tipe_pengeluaran]);

    useEffect(() => {
        setTanggal(filters.tanggal ?? '');
    }, [filters.tanggal]);

    const labelBulan = useMemo(() => {
        const [tahun, bulanAngka] = bulanFilter.split('-').map(Number);
        return new Date(tahun, bulanAngka - 1, 1).toLocaleDateString('id-ID', { month: 'long', year: 'numeric' });
    }, [bulanFilter]);

    const bulanIni = new Date();
    const kunciBulanIni = `${bulanIni.getFullYear()}-${String(bulanIni.getMonth() + 1).padStart(2, '0')}`;
    const filterAktif = Boolean(filters.kanal || filters.cari || filters.tanggal || filters.tipe_pengeluaran);
    const labelPeriode = filters.tanggal ? `per ${filters.tanggal}` : labelBulan;
    const labelAkunSaldo = filters.kanal === 'bank' ? ' Bank' : filters.kanal === 'kas_kecil' ? ' Kas Kecil' : '';
    const saldoValid = ringkasanPeriode?.saldo_valid ?? true;

    // Kelompokkan baris per tanggal (riwayat sudah urut tanggal asc).
    const grupHari = useMemo(() => {
        const peta = new Map();
        for (const r of riwayat.data) {
            const kunci = r.tanggalIso ?? r.tanggal;
            if (!peta.has(kunci)) peta.set(kunci, { tanggal: r.tanggal, hari: r.hari, baris: [] });
            peta.get(kunci).baris.push(r);
        }
        return [...peta.entries()];
    }, [riwayat.data]);

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
            <span className={`inline-flex items-center gap-1 text-[10px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded-full whitespace-nowrap ${meta.warna}`}>
                <Ikon size={11} aria-hidden="true" />
                {meta.label}
            </span>
        );
    }

    function badgeKantong(kantong) {
        return (
            <span className="text-[10px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded-full bg-slate-100 text-slate-500 whitespace-nowrap">
                {kantongLabel[kantong] ?? kantong}
            </span>
        );
    }

    function tampilBadge(r) {
        // Badge kantong ganda kalau labelnya beda dengan kanal (mis. Bank + Pinjaman).
        const sama = r.kantong === r.kanal;
        return (
            <>
                {badgeKanal(r.kanal)}
                {!sama && badgeKantong(r.kantong)}
                {r.tipe_pengeluaran_label && (
                    <span className="text-[10px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded-full bg-red-50 text-red-700 border border-red-200/60 whitespace-nowrap">
                        {r.tipe_pengeluaran_label}
                    </span>
                )}
            </>
        );
    }

    function labelKategori(r) {
        const dasar = kategoriLabel[r.kategori] ?? r.kategori;
        if (r.kategori === 'topup_bulanan' && r.sumber_topup) {
            return `${dasar} — ${sumberTopupLabel[r.sumber_topup] ?? r.sumber_topup}`;
        }
        return dasar;
    }

    function barisRuteAsal(r) {
        if (!r.rute_asal) return null;
        if (!auth.user?.permissions?.includes(r.rute_asal.izin)) return null;
        return (
            <Link
                href={route(r.rute_asal.nama, r.rute_asal.id)}
                className="text-xs font-semibold text-brand-green hover:text-brand-green-dark hover:underline shrink-0"
            >
                Lihat asal
            </Link>
        );
    }

    return (
        <AppLayout>
            <Head title="Kas Koperasi" />

            <PageHeader title="Kas Koperasi" subtitle="Saldo dan mutasi kas koperasi">
                {bisaTopup && (
                    <Button
                        size="sm"
                        onClick={() => { setTabDrawer('pemasukan'); setShowDrawer(true); }}
                        className="rounded-full shadow-md shadow-brand-green/25 hover:-translate-y-px active:translate-y-0"
                    >
                        <Plus size={16} aria-hidden="true" />
                        Mutasi
                    </Button>
                )}
            </PageHeader>

            {/* Interactive Multi-Account Switcher (Fintech Style) */}
            <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4">
                {/* 1. Akun Semua Kas */}
                {(() => {
                    const isAktif = !filters.kanal;
                    const totalSaldo = (saldoBank ?? 0) + (saldoKasKecil ?? 0);
                    return (
                        <button
                            type="button"
                            role="tab"
                            aria-selected={isAktif}
                            onClick={() => terapkan({ kanal: '' })}
                            className={`group text-left p-4 rounded-2xl border transition-all duration-200 relative overflow-hidden flex flex-col justify-between ${fokusRing} ${
                                isAktif
                                    ? 'bg-slate-900 text-white border-slate-900 shadow-md shadow-slate-900/15 ring-2 ring-brand-navy'
                                    : 'bg-white text-slate-800 border-slate-200/90 hover:border-slate-300 hover:shadow-xs'
                            }`}
                        >
                            <div className="flex items-center justify-between gap-2 mb-2">
                                <div className="flex items-center gap-2.5">
                                    <span className={`w-9 h-9 rounded-xl flex items-center justify-center shrink-0 transition-colors ${
                                        isAktif ? 'bg-white/15 text-white' : 'bg-slate-100 text-slate-700 group-hover:bg-slate-200/80'
                                    }`}>
                                        <Landmark size={18} />
                                    </span>
                                    <div>
                                        <h3 className={`text-xs font-bold uppercase tracking-wider ${isAktif ? 'text-white' : 'text-slate-800'}`}>
                                            Semua Kas
                                        </h3>
                                        <p className={`text-[11px] ${isAktif ? 'text-white/70' : 'text-slate-400'}`}>
                                            Bank + Kas Kecil
                                        </p>
                                    </div>
                                </div>
                                {isAktif ? (
                                    <span className="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-brand-green/20 text-brand-green border border-brand-green/30">
                                        <Check size={11} strokeWidth={3} />
                                        Aktif
                                    </span>
                                ) : (
                                    <span className="text-[10px] font-medium text-slate-400 group-hover:text-slate-600 transition-colors">
                                        Pilih Akun
                                    </span>
                                )}
                            </div>

                            <div className="my-1.5">
                                <p className={`text-xl sm:text-2xl font-bold tracking-tight tabular-nums ${isAktif ? 'text-white' : 'text-slate-900'}`}>
                                    {formatRupiah(totalSaldo)}
                                </p>
                            </div>

                            <div className={`mt-2 pt-2 border-t flex items-center justify-between text-[11px] font-medium tabular-nums ${
                                isAktif ? 'border-white/10' : 'border-slate-100'
                            }`}>
                                <span className={isAktif ? 'text-emerald-300' : 'text-emerald-600'}>
                                    +{formatRupiahSingkat(ringkasanPeriode.total_masuk)}
                                </span>
                                <span className={isAktif ? 'text-white/40' : 'text-slate-300'}>•</span>
                                <span className={isAktif ? 'text-rose-300' : 'text-rose-600'}>
                                    -{formatRupiahSingkat(ringkasanPeriode.total_keluar)}
                                </span>
                            </div>
                        </button>
                    );
                })()}

                {/* 2. Akun Bank */}
                {(() => {
                    const isAktif = filters.kanal === 'bank';
                    const bankMasuk = ringkasanKanal?.bank?.masuk ?? 0;
                    const bankKeluar = ringkasanKanal?.bank?.keluar ?? 0;
                    return (
                        <button
                            type="button"
                            role="tab"
                            aria-selected={isAktif}
                            onClick={() => terapkan({ kanal: 'bank' })}
                            className={`group text-left p-4 rounded-2xl border transition-all duration-200 relative overflow-hidden flex flex-col justify-between ${fokusRing} ${
                                isAktif
                                    ? 'bg-slate-900 text-white border-slate-900 shadow-md shadow-blue-950/20 ring-2 ring-blue-600'
                                    : 'bg-white text-slate-800 border-slate-200/90 hover:border-slate-300 hover:shadow-xs'
                            }`}
                        >
                            <div className="flex items-center justify-between gap-2 mb-2">
                                <div className="flex items-center gap-2.5">
                                    <span className={`w-9 h-9 rounded-xl flex items-center justify-center shrink-0 transition-colors ${
                                        isAktif ? 'bg-blue-500/20 text-blue-300' : 'bg-blue-50 text-blue-700 group-hover:bg-blue-100/80'
                                    }`}>
                                        <Landmark size={18} />
                                    </span>
                                    <div>
                                        <h3 className={`text-xs font-bold uppercase tracking-wider ${isAktif ? 'text-white' : 'text-slate-800'}`}>
                                            Kas Bank
                                        </h3>
                                        <p className={`text-[11px] ${isAktif ? 'text-blue-200/70' : 'text-slate-400'}`}>
                                            Giro / Operasional
                                        </p>
                                    </div>
                                </div>
                                {isAktif ? (
                                    <span className="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-blue-500/20 text-blue-300 border border-blue-400/30">
                                        <Check size={11} strokeWidth={3} />
                                        Aktif
                                    </span>
                                ) : (
                                    <span className="text-[10px] font-medium text-slate-400 group-hover:text-slate-600 transition-colors">
                                        Pilih Akun
                                    </span>
                                )}
                            </div>

                            <div className="my-1.5">
                                <p className={`text-xl sm:text-2xl font-bold tracking-tight tabular-nums ${isAktif ? 'text-white' : 'text-slate-900'}`}>
                                    {formatRupiah(saldoBank ?? 0)}
                                </p>
                            </div>

                            <div className={`mt-2 pt-2 border-t flex items-center justify-between text-[11px] font-medium tabular-nums ${
                                isAktif ? 'border-white/10' : 'border-slate-100'
                            }`}>
                                <span className={isAktif ? 'text-emerald-300' : 'text-emerald-600'}>
                                    +{formatRupiahSingkat(bankMasuk)}
                                </span>
                                <span className={isAktif ? 'text-white/40' : 'text-slate-300'}>•</span>
                                <span className={isAktif ? 'text-rose-300' : 'text-rose-600'}>
                                    -{formatRupiahSingkat(bankKeluar)}
                                </span>
                            </div>
                        </button>
                    );
                })()}

                {/* 3. Akun Kas Kecil */}
                {(() => {
                    const isAktif = filters.kanal === 'kas_kecil';
                    const kkMasuk = ringkasanKanal?.kas_kecil?.masuk ?? 0;
                    const kkKeluar = ringkasanKanal?.kas_kecil?.keluar ?? 0;
                    return (
                        <button
                            type="button"
                            role="tab"
                            aria-selected={isAktif}
                            onClick={() => terapkan({ kanal: 'kas_kecil' })}
                            className={`group text-left p-4 rounded-2xl border transition-all duration-200 relative overflow-hidden flex flex-col justify-between ${fokusRing} ${
                                isAktif
                                    ? 'bg-slate-900 text-white border-slate-900 shadow-md shadow-amber-950/20 ring-2 ring-amber-500'
                                    : 'bg-white text-slate-800 border-slate-200/90 hover:border-slate-300 hover:shadow-xs'
                            }`}
                        >
                            <div className="flex items-center justify-between gap-2 mb-2">
                                <div className="flex items-center gap-2.5">
                                    <span className={`w-9 h-9 rounded-xl flex items-center justify-center shrink-0 transition-colors ${
                                        isAktif ? 'bg-amber-500/20 text-amber-300' : 'bg-amber-50 text-amber-700 group-hover:bg-amber-100/80'
                                    }`}>
                                        <Wallet size={18} />
                                    </span>
                                    <div>
                                        <h3 className={`text-xs font-bold uppercase tracking-wider ${isAktif ? 'text-white' : 'text-slate-800'}`}>
                                            Kas Kecil Fisik
                                        </h3>
                                        <p className={`text-[11px] ${isAktif ? 'text-amber-200/70' : 'text-slate-400'}`}>
                                            Petty Cash Tunai
                                        </p>
                                    </div>
                                </div>
                                {isAktif ? (
                                    <span className="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-300 border border-amber-400/30">
                                        <Check size={11} strokeWidth={3} />
                                        Aktif
                                    </span>
                                ) : (
                                    <span className="text-[10px] font-medium text-slate-400 group-hover:text-slate-600 transition-colors">
                                        Pilih Akun
                                    </span>
                                )}
                            </div>

                            <div className="my-1.5">
                                <p className={`text-xl sm:text-2xl font-bold tracking-tight tabular-nums ${isAktif ? 'text-white' : 'text-slate-900'}`}>
                                    {formatRupiah(saldoKasKecil ?? 0)}
                                </p>
                            </div>

                            <div className={`mt-2 pt-2 border-t flex items-center justify-between text-[11px] font-medium tabular-nums ${
                                isAktif ? 'border-white/10' : 'border-slate-100'
                            }`}>
                                <span className={isAktif ? 'text-emerald-300' : 'text-emerald-600'}>
                                    +{formatRupiahSingkat(kkMasuk)}
                                </span>
                                <span className={isAktif ? 'text-white/40' : 'text-slate-300'}>•</span>
                                <span className={isAktif ? 'text-rose-300' : 'text-rose-600'}>
                                    -{formatRupiahSingkat(kkKeluar)}
                                </span>
                            </div>
                        </button>
                    );
                })()}
            </div>

            <Drawer
                show={showDrawer}
                onClose={() => setShowDrawer(false)}
                title="Pemasukan & Pengeluaran Kas"
                maxWidth="xl"
            >
                <div className="flex rounded-xl bg-slate-100 p-1 mb-5">
                    <button
                        type="button"
                        onClick={() => setTabDrawer('pemasukan')}
                        className={`flex-1 py-2 text-xs font-bold rounded-lg flex items-center justify-center gap-1.5 transition-all ${
                            tabDrawer === 'pemasukan'
                                ? 'bg-white text-emerald-700 shadow-sm'
                                : 'text-slate-600 hover:text-slate-900'
                        }`}
                    >
                        <ArrowDownCircle size={15} className={tabDrawer === 'pemasukan' ? 'text-emerald-600' : 'text-slate-400'} />
                        Pemasukan
                    </button>
                    <button
                        type="button"
                        onClick={() => setTabDrawer('pengeluaran')}
                        className={`flex-1 py-2 text-xs font-bold rounded-lg flex items-center justify-center gap-1.5 transition-all ${
                            tabDrawer === 'pengeluaran'
                                ? 'bg-white text-red-700 shadow-sm'
                                : 'text-slate-600 hover:text-slate-900'
                        }`}
                    >
                        <ArrowUpCircle size={15} className={tabDrawer === 'pengeluaran' ? 'text-red-600' : 'text-slate-400'} />
                        Pengeluaran
                    </button>
                    <button
                        type="button"
                        onClick={() => setTabDrawer('sisih')}
                        className={`flex-1 py-2 text-xs font-bold rounded-lg flex items-center justify-center gap-1.5 transition-all ${
                            tabDrawer === 'sisih'
                                ? 'bg-white text-amber-700 shadow-sm'
                                : 'text-slate-600 hover:text-slate-900'
                        }`}
                    >
                        <ArrowRightLeft size={14} className={tabDrawer === 'sisih' ? 'text-amber-600' : 'text-slate-400'} />
                        Sisih Kas Kecil
                    </button>
                </div>

                {tabDrawer === 'pemasukan' && (
                    <form onSubmit={submitPemasukan} className="space-y-4">
                        <div className="p-3 rounded-xl bg-emerald-50/70 border border-emerald-100 text-xs text-emerald-800 flex items-center gap-2">
                            <ArrowDownCircle size={18} className="text-emerald-600 shrink-0" />
                            <span>Pemasukan topup akan menambah saldo <strong>Bank</strong> dan tercatat langsung di mutasi kas.</span>
                        </div>

                        <FormField label="Jumlah (Rp)" error={pemasukanForm.errors.jumlah} required>
                            <div className="relative">
                                <span className="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm font-bold text-slate-400 pointer-events-none">Rp</span>
                                <TextField
                                    size="sm"
                                    type="number"
                                    min="1"
                                    value={pemasukanForm.data.jumlah}
                                    onChange={(e) => pemasukanForm.setData('jumlah', e.target.value)}
                                    placeholder="5000000"
                                    autoFocus
                                    required
                                    className="pl-10 tabular-nums"
                                />
                            </div>
                            <ChipNominal grup="topup" nilai={pemasukanForm.data.jumlah} onPilih={(n) => pemasukanForm.setData('jumlah', n)} />
                        </FormField>

                        <FormField label="Tanggal" error={pemasukanForm.errors.tanggal} required>
                            <div className="relative">
                                <CalendarDays size={15} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
                                <TextField
                                    size="sm"
                                    type="date"
                                    value={pemasukanForm.data.tanggal}
                                    onChange={(e) => pemasukanForm.setData('tanggal', e.target.value)}
                                    max={hariIni}
                                    required
                                    className="pl-9"
                                />
                            </div>
                        </FormField>

                        <FormField label="Sumber Topup" error={pemasukanForm.errors.sumber_topup} required hint="Bunga Bank masuk pendapatan SHU; Modal tidak.">
                            <Select
                                size="sm"
                                value={pemasukanForm.data.sumber_topup}
                                onChange={(e) => pemasukanForm.setData('sumber_topup', e.target.value)}
                                aria-label="Sumber topup"
                                required
                            >
                                {Object.entries(sumberTopupLabel).map(([key, label]) => (
                                    <option key={key} value={key}>{label}</option>
                                ))}
                            </Select>
                        </FormField>

                        <FormField label="Keterangan" error={pemasukanForm.errors.keterangan} hint={`Contoh: Topup ${labelBulan} dari pendapatan bunga`}>
                            <TextField
                                size="sm"
                                value={pemasukanForm.data.keterangan}
                                onChange={(e) => pemasukanForm.setData('keterangan', e.target.value)}
                                placeholder={`Topup ${labelBulan} dari pendapatan bunga`}
                            />
                        </FormField>

                        <label className="flex items-center gap-2.5 cursor-pointer select-none">
                            <input
                                type="checkbox"
                                checked={Boolean(pemasukanForm.data.lain_lain)}
                                onChange={(e) => pemasukanForm.setData('lain_lain', e.target.checked)}
                                className="w-4 h-4 rounded accent-brand-green"
                            />
                            <span className="text-sm font-semibold text-slate-600">Lain-lain</span>
                        </label>

                        {Boolean(pemasukanForm.data.lain_lain) && (
                            <FormField label="Keterangan lain-lain" error={pemasukanForm.errors.keterangan_lain} required>
                                <TextField
                                    size="sm"
                                    value={pemasukanForm.data.keterangan_lain}
                                    onChange={(e) => pemasukanForm.setData('keterangan_lain', e.target.value)}
                                    placeholder="Contoh: Hibah dari donatur"
                                    autoFocus
                                />
                            </FormField>
                        )}

                        <div className="pt-2 flex items-center gap-2">
                            <Button type="submit" size="sm" disabled={pemasukanForm.processing} className="rounded-full shadow-md shadow-brand-green/25">
                                {pemasukanForm.processing ? 'Menyimpan...' : `Simpan Pemasukan • ${pemasukanForm.data.jumlah ? formatRupiah(Number(pemasukanForm.data.jumlah)) : 'Rp 0'}`}
                            </Button>
                            <Button type="button" variant="ghost" size="sm" className="rounded-full" onClick={() => setShowDrawer(false)}>
                                Batal
                            </Button>
                        </div>
                    </form>
                )}

                {tabDrawer === 'pengeluaran' && (
                    <form onSubmit={submitPengeluaran} className="space-y-4">
                        <div className="p-3 rounded-xl bg-red-50/70 border border-red-100 text-xs text-red-800 flex items-center gap-2">
                            <ArrowUpCircle size={18} className="text-red-600 shrink-0" />
                            <span>Pengeluaran akan memotong saldo kas fisik (Bank atau Kas Kecil) dan tercatat ke mutasi kas.</span>
                        </div>

                        <FormField label="Jumlah (Rp)" error={pengeluaranForm.errors.jumlah} required>
                            <div className="relative">
                                <span className="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm font-bold text-slate-400 pointer-events-none">Rp</span>
                                <TextField
                                    size="sm"
                                    type="number"
                                    min="1"
                                    value={pengeluaranForm.data.jumlah}
                                    onChange={(e) => pengeluaranForm.setData('jumlah', e.target.value)}
                                    placeholder="250000"
                                    autoFocus
                                    required
                                    className="pl-10 tabular-nums"
                                />
                            </div>
                            <ChipNominal grup="pengeluaran" nilai={pengeluaranForm.data.jumlah} onPilih={(n) => pengeluaranForm.setData('jumlah', n)} />
                        </FormField>

                        <FormField label="Tanggal" error={pengeluaranForm.errors.tanggal} required>
                            <div className="relative">
                                <CalendarDays size={15} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
                                <TextField
                                    size="sm"
                                    type="date"
                                    value={pengeluaranForm.data.tanggal}
                                    onChange={(e) => pengeluaranForm.setData('tanggal', e.target.value)}
                                    max={hariIni}
                                    required
                                    className="pl-9"
                                />
                            </div>
                        </FormField>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <FormField label="Tipe Pengeluaran" error={pengeluaranForm.errors.tipe} required>
                                <Select
                                    size="sm"
                                    value={pengeluaranForm.data.tipe}
                                    onChange={(e) => pengeluaranForm.setData('tipe', e.target.value)}
                                    aria-label="Tipe pengeluaran"
                                    required
                                >
                                    {Object.entries(tipePengeluaranList).map(([key, label]) => (
                                        <option key={key} value={key}>{label}</option>
                                    ))}
                                </Select>
                            </FormField>

                            <FormField label="Sumber Dana" error={pengeluaranForm.errors.akun} required hint="Bank atau Kas Kecil">
                                <Select
                                    size="sm"
                                    value={pengeluaranForm.data.akun}
                                    onChange={(e) => pengeluaranForm.setData('akun', e.target.value)}
                                    aria-label="Sumber dana"
                                    required
                                >
                                    {Object.entries(akunPengeluaranList).map(([key, label]) => (
                                        <option key={key} value={key}>{label}</option>
                                    ))}
                                </Select>
                            </FormField>
                        </div>

                        <FormField label="Keterangan" error={pengeluaranForm.errors.keterangan} required>
                            <TextField
                                size="sm"
                                as="textarea"
                                rows={2}
                                value={pengeluaranForm.data.keterangan}
                                onChange={(e) => pengeluaranForm.setData('keterangan', e.target.value)}
                                placeholder="Contoh: Biaya ATK dan operasional kantor"
                                required
                            />
                        </FormField>

                        <div className="pt-2 flex items-center gap-2">
                            <Button type="submit" size="sm" disabled={pengeluaranForm.processing} className="rounded-full shadow-md shadow-red-500/25 bg-red-600 hover:bg-red-700">
                                {pengeluaranForm.processing ? 'Menyimpan...' : `Simpan Pengeluaran • ${pengeluaranForm.data.jumlah ? formatRupiah(Number(pengeluaranForm.data.jumlah)) : 'Rp 0'}`}
                            </Button>
                            <Button type="button" variant="ghost" size="sm" className="rounded-full" onClick={() => setShowDrawer(false)}>
                                Batal
                            </Button>
                        </div>
                    </form>
                )}

                {tabDrawer === 'sisih' && (
                    <form onSubmit={submitSisih} className="space-y-4">
                        <div className="p-3 rounded-xl bg-amber-50/70 border border-amber-100 text-xs text-amber-800 flex items-center gap-2">
                            <ArrowRightLeft size={18} className="text-amber-600 shrink-0" />
                            <span>Memindahkan uang fisik dari <strong>Bank</strong> ke <strong>Kas Kecil</strong> dalam satu transaksi atomik.</span>
                        </div>

                        <FormField label="Jumlah (Rp)" error={sisihForm.errors.jumlah} required>
                            <div className="relative">
                                <span className="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm font-bold text-slate-400 pointer-events-none">Rp</span>
                                <TextField
                                    size="sm"
                                    type="number"
                                    min="1"
                                    value={sisihForm.data.jumlah}
                                    onChange={(e) => sisihForm.setData('jumlah', e.target.value)}
                                    placeholder="1000000"
                                    autoFocus
                                    required
                                    className="pl-10 tabular-nums"
                                />
                            </div>
                            <ChipNominal grup="sisih" nilai={sisihForm.data.jumlah} onPilih={(n) => sisihForm.setData('jumlah', n)} />
                            <p className="text-xs text-slate-400 mt-1">Saldo Bank saat ini: {formatRupiah(saldoBank ?? 0)}</p>
                        </FormField>

                        <FormField label="Keterangan" error={sisihForm.errors.keterangan} hint="Contoh: Kas operasional sekretariat minggu ini">
                            <TextField
                                size="sm"
                                value={sisihForm.data.keterangan}
                                onChange={(e) => sisihForm.setData('keterangan', e.target.value)}
                                placeholder="Sisih kas kecil untuk operasional"
                            />
                        </FormField>

                        <div className="pt-2 flex items-center gap-2">
                            <Button type="submit" size="sm" disabled={sisihForm.processing} className="rounded-full shadow-md shadow-amber-500/25 bg-amber-600 hover:bg-amber-700">
                                {sisihForm.processing ? 'Menyimpan...' : `Sisihkan Kas Kecil • ${sisihForm.data.jumlah ? formatRupiah(Number(sisihForm.data.jumlah)) : 'Rp 0'}`}
                            </Button>
                            <Button type="button" variant="ghost" size="sm" className="rounded-full" onClick={() => setShowDrawer(false)}>
                                Batal
                            </Button>
                        </div>
                    </form>
                )}
            </Drawer>



            <Card padding="none" className="shadow-md border-slate-200/70 mb-4 overflow-hidden">
                {/* Stream Control Bar (Revolut / Wise Fintech Pattern) */}
                <div className="p-3 sm:p-3.5 bg-slate-50/70 border-b border-slate-100 flex flex-col gap-3">
                    {/* Baris Kontrol Utama: Stepper Periode, Segmented Pill Arus Kas, Search */}
                    <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-2.5">
                        {/* Kiri: Stepper Bulan & Filter Harian */}
                        <div className="flex flex-wrap items-center gap-2">
                            {/* Stepper Bulan */}
                            <div className="flex items-center rounded-xl border border-slate-200 bg-white shadow-2xs overflow-hidden">
                                <button
                                    type="button"
                                    onClick={() => geserBulan(-1)}
                                    aria-label="Bulan sebelumnya"
                                    className={`px-2.5 py-1.5 text-slate-500 hover:text-slate-800 hover:bg-slate-50 transition-colors ${fokusRing}`}
                                >
                                    <ChevronLeft size={16} />
                                </button>
                                <input
                                    type="month"
                                    value={bulanFilter}
                                    onChange={(e) => e.target.value && terapkan({ bulan: e.target.value, tanggal: '' })}
                                    aria-label="Pilih bulan"
                                    className="px-2 py-1 text-xs sm:text-sm font-semibold text-slate-700 bg-transparent focus:outline-none border-x border-slate-100 tabular-nums text-center cursor-pointer"
                                />
                                <button
                                    type="button"
                                    onClick={() => geserBulan(1)}
                                    aria-label="Bulan berikutnya"
                                    className={`px-2.5 py-1.5 text-slate-500 hover:text-slate-800 hover:bg-slate-50 transition-colors ${fokusRing}`}
                                >
                                    <ChevronRight size={16} />
                                </button>
                            </div>

                            {bulanFilter !== kunciBulanIni && (
                                <button
                                    type="button"
                                    onClick={() => terapkan({ bulan: kunciBulanIni, tanggal: '' })}
                                    className="text-xs font-bold text-brand-green-dark bg-brand-green-light rounded-full px-2.5 py-1 hover:bg-brand-green/20 transition-colors whitespace-nowrap"
                                >
                                    Bulan ini
                                </button>
                            )}

                            {/* Tanggal Spesifik (Harian) Inline */}
                            <div className="relative inline-flex items-center">
                                <CalendarDays size={14} className="absolute left-2.5 text-slate-400 pointer-events-none" />
                                <input
                                    type="date"
                                    value={tanggal}
                                    onChange={(e) => handlePilihTanggal(e.target.value)}
                                    aria-label="Filter tanggal spesifik"
                                    title="Pilih tanggal spesifik (harian)"
                                    className="pl-8 pr-7 py-1 text-xs font-medium text-slate-700 bg-white rounded-xl border border-slate-200 hover:border-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-green/30 transition-all cursor-pointer"
                                />
                                {tanggal && (
                                    <button
                                        type="button"
                                        onClick={handleHapusTanggal}
                                        aria-label="Hapus filter tanggal"
                                        className="absolute right-2 text-slate-400 hover:text-slate-600 p-0.5"
                                    >
                                        <X size={13} />
                                    </button>
                                )}
                            </div>
                        </div>

                        {/* Kanan / Tengah: Segmented Arus Kas + Kategori Keluar + Search */}
                        <div className="flex flex-wrap items-center gap-2 flex-1 lg:justify-end">
                            {/* Segmented Pill Arus Kas */}
                            <div className="inline-flex items-center rounded-xl bg-slate-200/70 p-1 border border-slate-200/50 shadow-2xs">
                                <button
                                    type="button"
                                    onClick={() => handlePilihTipe('')}
                                    className={`px-2.5 py-1 rounded-lg text-xs font-semibold transition-all flex items-center gap-1.5 ${
                                        tipe === ''
                                            ? 'bg-white text-brand-navy shadow-xs font-bold'
                                            : 'text-slate-600 hover:text-slate-900'
                                    }`}
                                >
                                    <span>Semua</span>
                                    <span className="text-[10px] px-1.5 py-0.2 rounded-full bg-slate-100 text-slate-500 font-bold tabular-nums">
                                        {riwayat.total ?? 0}
                                    </span>
                                </button>
                                <button
                                    type="button"
                                    onClick={() => handlePilihTipe('masuk')}
                                    className={`px-2.5 py-1 rounded-lg text-xs font-semibold transition-all flex items-center gap-1.5 ${
                                        tipe === 'masuk'
                                            ? 'bg-emerald-600 text-white shadow-xs font-bold'
                                            : 'text-emerald-700 hover:bg-emerald-50/70'
                                    }`}
                                >
                                    <ArrowDownCircle size={13} className={tipe === 'masuk' ? 'text-white' : 'text-emerald-600'} />
                                    <span>Masuk</span>
                                    <span className={`text-[11px] font-bold tabular-nums ${tipe === 'masuk' ? 'text-emerald-100' : 'text-emerald-600'}`}>
                                        +{formatRupiahSingkat(ringkasanPeriode.total_masuk)}
                                    </span>
                                </button>
                                <button
                                    type="button"
                                    onClick={() => handlePilihTipe('keluar')}
                                    className={`px-2.5 py-1 rounded-lg text-xs font-semibold transition-all flex items-center gap-1.5 ${
                                        tipe === 'keluar'
                                            ? 'bg-red-600 text-white shadow-xs font-bold'
                                            : 'text-red-700 hover:bg-red-50/70'
                                    }`}
                                >
                                    <ArrowUpCircle size={13} className={tipe === 'keluar' ? 'text-white' : 'text-red-600'} />
                                    <span>Keluar</span>
                                    <span className={`text-[11px] font-bold tabular-nums ${tipe === 'keluar' ? 'text-red-100' : 'text-red-600'}`}>
                                        -{formatRupiahSingkat(ringkasanPeriode.total_keluar)}
                                    </span>
                                </button>
                            </div>

                            {/* Dropdown Kategori Pengeluaran (Tampil Dinamis saat Keluar Aktif atau Kategori Terpilih) */}
                            {(tipe === 'keluar' || tipePengeluaran) && Object.keys(tipePengeluaranList).length > 0 && (
                                <div className="animate-in fade-in zoom-in-95 duration-150">
                                    <Select
                                        size="sm"
                                        value={tipePengeluaran}
                                        onChange={(e) => handlePilihTipePengeluaran(e.target.value)}
                                        aria-label="Filter kategori pengeluaran"
                                        className="text-xs py-1 px-2.5 rounded-xl border-red-200 bg-red-50/90 text-red-800 font-semibold focus:ring-red-400"
                                    >
                                        <option value="">Semua Kategori Keluar</option>
                                        {Object.entries(tipePengeluaranList).map(([k, label]) => (
                                            <option key={k} value={k}>{label}</option>
                                        ))}
                                    </Select>
                                </div>
                            )}

                            {/* Search Box */}
                            <div className="relative min-w-[160px] sm:min-w-[200px] flex-1 sm:flex-initial">
                                <Search size={14} className="absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
                                <input
                                    type="text"
                                    value={cari}
                                    onChange={(e) => setCari(e.target.value)}
                                    placeholder="Cari transaksi..."
                                    aria-label="Cari transaksi"
                                    className="w-full pl-8 pr-7 py-1 text-xs sm:text-sm rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-brand-green/30 transition-all placeholder:text-slate-400"
                                />
                                {cari && (
                                    <button
                                        type="button"
                                        onClick={() => setCari('')}
                                        aria-label="Hapus pencarian"
                                        className="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-0.5"
                                    >
                                        <X size={13} />
                                    </button>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Active Filter Chips & Reset All */}
                    {filterAktif && (
                        <div className="flex flex-wrap items-center justify-between gap-1.5 pt-2 border-t border-slate-200/60 text-xs">
                            <div className="flex flex-wrap items-center gap-1.5">
                                <span className="text-slate-400 text-[11px] font-medium mr-0.5">Filter aktif:</span>

                                {filters.kanal && (
                                    <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-white border border-slate-200 text-slate-700 font-medium shadow-2xs text-[11px]">
                                        <span>Akun: {kanalMeta[filters.kanal]?.label ?? filters.kanal}</span>
                                        <button
                                            type="button"
                                            onClick={() => terapkan({ kanal: '' })}
                                            className="hover:text-red-500 rounded-full p-0.5 transition-colors"
                                            aria-label="Hapus filter akun"
                                        >
                                            <X size={11} />
                                        </button>
                                    </span>
                                )}

                                {filters.tanggal && (
                                    <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-white border border-slate-200 text-slate-700 font-medium shadow-2xs text-[11px]">
                                        <CalendarDays size={11} className="text-slate-400" />
                                        <span>{filters.tanggal}</span>
                                        <button
                                            type="button"
                                            onClick={handleHapusTanggal}
                                            className="hover:text-red-500 rounded-full p-0.5 transition-colors"
                                            aria-label="Hapus filter tanggal"
                                        >
                                            <X size={11} />
                                        </button>
                                    </span>
                                )}

                                {filters.tipe_pengeluaran && (
                                    <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-red-50 border border-red-200 text-red-700 font-medium shadow-2xs text-[11px]">
                                        <span>Kategori: {tipePengeluaranList[filters.tipe_pengeluaran] ?? filters.tipe_pengeluaran}</span>
                                        <button
                                            type="button"
                                            onClick={() => handlePilihTipePengeluaran('')}
                                            className="hover:text-red-900 rounded-full p-0.5 transition-colors"
                                            aria-label="Hapus filter kategori pengeluaran"
                                        >
                                            <X size={11} />
                                        </button>
                                    </span>
                                )}

                                {filters.cari && (
                                    <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-white border border-slate-200 text-slate-700 font-medium shadow-2xs text-[11px]">
                                        <span>"{filters.cari}"</span>
                                        <button
                                            type="button"
                                            onClick={() => setCari('')}
                                            className="hover:text-red-500 rounded-full p-0.5 transition-colors"
                                            aria-label="Hapus filter pencarian"
                                        >
                                            <X size={11} />
                                        </button>
                                    </span>
                                )}
                            </div>

                            <button
                                type="button"
                                onClick={resetFilter}
                                className="text-[11px] font-bold text-red-600 hover:text-red-800 hover:underline px-1.5 py-0.5 ml-auto sm:ml-0"
                            >
                                Reset Filter
                            </button>
                        </div>
                    )}
                </div>

                {/* 4. Subheader Info */}
                <div className="flex items-center justify-between gap-1.5 px-4 sm:px-5 py-3 bg-white border-b border-slate-100">
                    <div className="flex items-center gap-2 min-w-0">
                        <CalendarDays size={16} className="text-slate-400 shrink-0" />
                        <p className="text-xs sm:text-sm text-slate-600 truncate">
                            Mutasi kas <span className="font-bold text-slate-800">{labelBulan}</span>
                            {filters.tanggal && <span className="text-brand-navy font-semibold"> • {filters.tanggal}</span>}
                            {filters.kanal && <span className="text-slate-600"> • {kanalMeta[filters.kanal]?.label}</span>}
                        </p>
                    </div>
                    <span className="text-xs text-slate-500 font-semibold tabular-nums px-2.5 py-0.5 rounded-full bg-slate-100 border border-slate-200/60">
                        {riwayat.total ?? 0} data
                    </span>
                </div>

                {/* Ringkasan saldo periode */}
                <div className="border-b border-slate-100 bg-slate-50/40 px-4 sm:px-5 py-3">
                    <dl className="grid grid-cols-2 gap-x-4 gap-y-2 md:grid-cols-4 text-sm">
                        <div>
                            <dt className="text-[11px] text-slate-400">Saldo awal{labelAkunSaldo} {labelPeriode}</dt>
                            <dd className="font-bold tabular-nums text-slate-800">
                                {saldoValid ? formatRupiah(ringkasanPeriode?.saldo_awal ?? 0) : '—'}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-[11px] text-slate-400">Masuk {labelPeriode}</dt>
                            <dd className="font-bold tabular-nums text-brand-green-dark">
                                + {formatRupiah(ringkasanPeriode?.total_masuk ?? 0)}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-[11px] text-slate-400">Keluar {labelPeriode}</dt>
                            <dd className="font-bold tabular-nums text-red-600">
                                - {formatRupiah(ringkasanPeriode?.total_keluar ?? 0)}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-[11px] text-slate-400">Saldo akhir{labelAkunSaldo} {labelPeriode}</dt>
                            <dd className="font-bold tabular-nums text-slate-800">
                                {saldoValid ? formatRupiah(ringkasanPeriode?.saldo_akhir ?? 0) : '—'}
                            </dd>
                        </div>
                    </dl>
                    {!saldoValid && (
                        <p className="mt-2 text-[11px] text-slate-400">
                            Saldo awal/akhir tidak dihitung saat filter transaksi aktif.
                        </p>
                    )}
                </div>

                <div className={`transition-opacity duration-150 ${memuatFilter ? 'opacity-50 pointer-events-none' : 'opacity-100'}`}>
                    {riwayat.data.length === 0 ? (
                        <div className="text-center py-14 px-4">
                            <Wallet size={32} aria-hidden="true" className="mx-auto text-slate-300 mb-3" />
                            <p className="text-sm font-semibold text-slate-600">
                                Belum ada mutasi kas untuk {labelBulan}.
                            </p>
                            {filterAktif && (
                                <Button type="button" variant="outline" size="sm" className="mt-3 rounded-full" onClick={resetFilter}>
                                    Tampilkan semua
                                </Button>
                            )}
                            {bisaTopup && !filterAktif && bulanFilter === kunciBulanIni && (
                                <div className="mt-3">
                                    <Button type="button" size="sm" className="rounded-full shadow-xs" onClick={() => { setTabDrawer('pemasukan'); setShowDrawer(true); }}>
                                        <Plus size={16} aria-hidden="true" />
                                        Mutasi
                                    </Button>
                                </div>
                            )}
                        </div>
                    ) : (
                        <>
                            {/* Desktop & Tablet View: Accounting Ledger Table */}
                            <div className="hidden md:block overflow-x-auto">
                                <table className="w-full text-left border-collapse text-sm">
                                    <thead>
                                        <tr className="border-b border-slate-200/80 bg-slate-50/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                            <th className="px-5 py-3 w-36 whitespace-nowrap">Tanggal</th>
                                            <th className="px-4 py-3 min-w-[260px]">Transaksi & Keterangan</th>
                                            <th className="px-4 py-3 w-32 whitespace-nowrap">Akun Kas</th>
                                            <th className="px-4 py-3 w-36 text-right whitespace-nowrap text-emerald-700">Masuk (+)</th>
                                            <th className="px-4 py-3 w-36 text-right whitespace-nowrap text-rose-700">Keluar (-)</th>
                                            <th className="px-5 py-3 w-40 text-right whitespace-nowrap text-slate-700">Saldo Kas</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100">
                                        {grupHari.map(([tanggalIso, grup]) => {
                                            const ringkasanHari = ringkasanPerTanggal[tanggalIso] ?? { masuk: 0, keluar: 0 };
                                            const netHari = (ringkasanHari.masuk ?? 0) - (ringkasanHari.keluar ?? 0);
                                            return (
                                                <Fragment key={tanggalIso}>
                                                    {/* Header Grup Tanggal */}
                                                    <tr className="bg-slate-100/70 border-t border-slate-200/70 text-xs text-slate-600">
                                                        <td colSpan={3} className="px-5 py-2">
                                                            <div className="flex items-center gap-2">
                                                                <span className="font-bold text-slate-800">{grup.tanggal}</span>
                                                                <span className="text-slate-500">({grup.hari})</span>
                                                                <span className="text-[11px] font-normal text-slate-400">• {grup.baris.length} mutasi</span>
                                                            </div>
                                                        </td>
                                                        <td className="px-4 py-2 text-right font-bold text-emerald-600 tabular-nums text-xs whitespace-nowrap">
                                                            {ringkasanHari.masuk > 0 ? `+${formatRupiah(ringkasanHari.masuk)}` : '—'}
                                                        </td>
                                                        <td className="px-4 py-2 text-right font-bold text-rose-600 tabular-nums text-xs whitespace-nowrap">
                                                            {ringkasanHari.keluar > 0 ? `-${formatRupiah(ringkasanHari.keluar)}` : '—'}
                                                        </td>
                                                        <td className="px-5 py-2 text-right text-xs text-slate-500 tabular-nums font-semibold whitespace-nowrap">
                                                            Net: {netHari >= 0 ? `+${formatRupiah(netHari)}` : `-${formatRupiah(Math.abs(netHari))}`}
                                                        </td>
                                                    </tr>

                                                    {/* Baris Transaksi */}
                                                    {grup.baris.map((r) => (
                                                        <tr key={r.id} className="hover:bg-slate-50/80 transition-colors">
                                                            <td className="px-5 py-3 text-xs text-slate-500 whitespace-nowrap align-top">
                                                                <div className="flex items-center gap-2">
                                                                    <span className={`w-2 h-2 rounded-full shrink-0 ${r.tipe === 'masuk' ? 'bg-emerald-500' : 'bg-rose-500'}`} />
                                                                    <span>{r.tanggal}</span>
                                                                </div>
                                                            </td>
                                                            <td className="px-4 py-3 align-top">
                                                                <div className="min-w-0">
                                                                    <div className="flex items-center gap-2 flex-wrap mb-1">
                                                                        <p className="font-semibold text-slate-800 text-sm">
                                                                            {labelKategori(r)}
                                                                        </p>
                                                                        {tampilBadge(r)}
                                                                    </div>
                                                                    {r.sub_judul && (
                                                                        <p className="text-xs text-slate-600 italic mb-0.5 truncate max-w-lg" title={r.sub_judul}>
                                                                            {r.sub_judul}
                                                                        </p>
                                                                    )}
                                                                    <div className="flex items-center gap-2 text-xs text-slate-400">
                                                                        {r.keterangan && (
                                                                            <span className="truncate max-w-md" title={r.keterangan}>{r.keterangan}</span>
                                                                        )}
                                                                        {barisRuteAsal(r)}
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td className="px-4 py-3 align-top whitespace-nowrap">
                                                                {badgeKanal(r.kanal)}
                                                            </td>
                                                            <td className="px-4 py-3 text-right align-top tabular-nums whitespace-nowrap">
                                                                {r.tipe === 'masuk' ? (
                                                                    <span className="font-bold text-emerald-600 text-sm">
                                                                        +{formatRupiah(r.jumlah)}
                                                                    </span>
                                                                ) : (
                                                                    <span className="text-slate-300 font-normal text-sm">—</span>
                                                                )}
                                                            </td>
                                                            <td className="px-4 py-3 text-right align-top tabular-nums whitespace-nowrap">
                                                                {r.tipe === 'keluar' ? (
                                                                    <span className="font-bold text-rose-600 text-sm">
                                                                        -{formatRupiah(r.jumlah)}
                                                                    </span>
                                                                ) : (
                                                                    <span className="text-slate-300 font-normal text-sm">—</span>
                                                                )}
                                                            </td>
                                                            <td className="px-5 py-3 text-right align-top tabular-nums whitespace-nowrap">
                                                                <p className="font-semibold text-slate-800 text-sm">
                                                                    {formatRupiah(r.saldo_setelah)}
                                                                </p>
                                                                <p className="text-[10px] text-slate-400 uppercase tracking-wider">
                                                                    {r.kanal === 'bank' ? 'Bank' : 'Kas Kecil'}
                                                                </p>
                                                            </td>
                                                        </tr>
                                                    ))}
                                                </Fragment>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>

                            {/* Mobile View: Modern Financial Transaction Rows with Generous Breathing Room */}
                            <div className="md:hidden divide-y divide-slate-100">
                                {grupHari.map(([tanggalIso, grup]) => {
                                    const ringkasanHari = ringkasanPerTanggal[tanggalIso] ?? { masuk: 0, keluar: 0 };
                                    return (
                                        <div key={tanggalIso} className="pt-2">
                                            {/* Header Tanggal Mobile */}
                                            <div className="px-4 py-2 bg-slate-50/90 border-y border-slate-100 flex items-center justify-between text-xs">
                                                <div className="flex items-center gap-1.5">
                                                    <span className="font-bold text-slate-800">{grup.tanggal}</span>
                                                    <span className="text-slate-400">• {grup.hari}</span>
                                                </div>
                                                <div className="flex items-center gap-2 font-semibold tabular-nums text-[11px]">
                                                    {ringkasanHari.masuk > 0 && (
                                                        <span className="text-emerald-600">+{formatRupiahSingkat(ringkasanHari.masuk)}</span>
                                                    )}
                                                    {ringkasanHari.keluar > 0 && (
                                                        <span className="text-rose-600">-{formatRupiahSingkat(ringkasanHari.keluar)}</span>
                                                    )}
                                                </div>
                                            </div>

                                            {/* List Transaksi Mobile */}
                                            <div className="divide-y divide-slate-100">
                                                {grup.baris.map((r) => (
                                                    <div key={r.id} className="px-4 py-3.5 hover:bg-slate-50/60 transition-colors">
                                                        <div className="flex items-start justify-between gap-3">
                                                            {/* Kiri: Icon + Info */}
                                                            <div className="flex items-start gap-3 min-w-0 flex-1">
                                                                <span className={`w-8 h-8 rounded-xl flex items-center justify-center shrink-0 mt-0.5 ${
                                                                    r.tipe === 'masuk'
                                                                        ? 'bg-emerald-50 text-emerald-600 border border-emerald-200/50'
                                                                        : 'bg-rose-50 text-rose-600 border border-rose-200/50'
                                                                }`}>
                                                                    {r.tipe === 'masuk' ? (
                                                                        <ArrowDownCircle size={18} />
                                                                    ) : (
                                                                        <ArrowUpCircle size={18} />
                                                                    )}
                                                                </span>
                                                                <div className="min-w-0 flex-1">
                                                                    <p className="text-sm font-semibold text-slate-800 truncate">
                                                                        {labelKategori(r)}
                                                                    </p>
                                                                    {r.sub_judul && (
                                                                        <p className="text-[11px] text-slate-500 italic truncate mt-0.5" title={r.sub_judul}>
                                                                            {r.sub_judul}
                                                                        </p>
                                                                    )}
                                                                    <div className="flex items-center gap-1.5 mt-1 flex-wrap">
                                                                        {tampilBadge(r)}
                                                                    </div>
                                                                    {r.keterangan && (
                                                                        <p className="text-[11px] text-slate-400 truncate mt-1" title={r.keterangan}>
                                                                            {r.keterangan}
                                                                        </p>
                                                                    )}
                                                                    {barisRuteAsal(r)}
                                                                </div>
                                                            </div>

                                                            {/* Kanan: Nominal + Saldo */}
                                                            <div className="text-right shrink-0">
                                                                <p className={`text-sm font-bold tabular-nums whitespace-nowrap ${
                                                                    r.tipe === 'masuk' ? 'text-emerald-600' : 'text-rose-600'
                                                                }`}>
                                                                    {r.tipe === 'masuk' ? '+' : '-'} {formatRupiah(r.jumlah)}
                                                                </p>
                                                                <p className="text-[10px] text-slate-400 tabular-nums whitespace-nowrap mt-0.5">
                                                                    Saldo: {formatRupiah(r.saldo_setelah)}
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        </>
                    )}
                </div>
            </Card>

            <Pagination links={riwayat.links} />
        </AppLayout>
    );
}
