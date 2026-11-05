import AppLayout from '@/Layouts/AppLayout';
import { Head, usePage, useForm, router, Link } from '@inertiajs/react';
import {
    Wallet, ArrowDownCircle, ArrowUpCircle, Plus, ChevronLeft, ChevronRight,
    Landmark, CalendarDays, Search, X, ArrowRightLeft, Check, ChevronDown
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
    const [dariTanggal, setDariTanggal] = useState(filters.dari_tanggal ?? '');
    const [sampaiTanggal, setSampaiTanggal] = useState(filters.sampai_tanggal ?? '');
    const [bukaPeriodePopover, setBukaPeriodePopover] = useState(false);
    const [tabPeriode, setTabPeriode] = useState(filters.dari_tanggal || filters.sampai_tanggal ? 'rentang' : 'bulan');
    const [tempBulan, setTempBulan] = useState(bulanFilter);
    const [tempDari, setTempDari] = useState(filters.dari_tanggal ?? '');
    const [tempSampai, setTempSampai] = useState(filters.sampai_tanggal ?? '');
    const popoverRef = useRef(null);
    const [memuatFilter, setMemuatFilter] = useState(false);
    const pertama = useRef(true);
    const hariIni = new Date().toISOString().slice(0, 10);

    useEffect(() => {
        function handleClickOutside(event) {
            if (popoverRef.current && !popoverRef.current.contains(event.target)) {
                setBukaPeriodePopover(false);
            }
        }
        if (bukaPeriodePopover) {
            document.addEventListener('mousedown', handleClickOutside);
        }
        return () => {
            document.removeEventListener('mousedown', handleClickOutside);
        };
    }, [bukaPeriodePopover]);

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
            dari_tanggal: dariTanggal,
            sampai_tanggal: sampaiTanggal,
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

    function formatTglIso(d) {
        const thn = d.getFullYear();
        const bln = String(d.getMonth() + 1).padStart(2, '0');
        const tgl = String(d.getDate()).padStart(2, '0');
        return `${thn}-${bln}-${tgl}`;
    }

    function terapkanPreset(tipePreset) {
        const now = new Date();
        let dari = '';
        let sampai = '';

        if (tipePreset === 'hari_ini') {
            dari = formatTglIso(now);
            sampai = formatTglIso(now);
        } else if (tipePreset === '7_hari') {
            const lalu = new Date();
            lalu.setDate(now.getDate() - 6);
            dari = formatTglIso(lalu);
            sampai = formatTglIso(now);
        } else if (tipePreset === '30_hari') {
            const lalu = new Date();
            lalu.setDate(now.getDate() - 29);
            dari = formatTglIso(lalu);
            sampai = formatTglIso(now);
        } else if (tipePreset === 'bulan_ini') {
            const awal = new Date(now.getFullYear(), now.getMonth(), 1);
            const akhir = new Date(now.getFullYear(), now.getMonth() + 1, 0);
            dari = formatTglIso(awal);
            sampai = formatTglIso(akhir);
        } else if (tipePreset === 'bulan_lalu') {
            const awal = new Date(now.getFullYear(), now.getMonth() - 1, 1);
            const akhir = new Date(now.getFullYear(), now.getMonth(), 0);
            dari = formatTglIso(awal);
            sampai = formatTglIso(akhir);
        }

        setTempDari(dari);
        setTempSampai(sampai);
    }

    function handleTerapkanRentang() {
        if (!tempDari && !tempSampai) return;
        setBukaPeriodePopover(false);
        setDariTanggal(tempDari);
        setSampaiTanggal(tempSampai);
        setTanggal('');
        terapkan({
            dari_tanggal: tempDari,
            sampai_tanggal: tempSampai,
            tanggal: '',
        });
    }

    function handleTerapkanBulan() {
        if (!tempBulan) return;
        setBukaPeriodePopover(false);
        setDariTanggal('');
        setSampaiTanggal('');
        setTanggal('');
        terapkan({
            bulan: tempBulan,
            tanggal: '',
            dari_tanggal: '',
            sampai_tanggal: '',
        });
    }

    function handleHapusRentang() {
        setTempDari('');
        setTempSampai('');
        setDariTanggal('');
        setSampaiTanggal('');
        terapkan({
            dari_tanggal: '',
            sampai_tanggal: '',
            tanggal: '',
        });
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
        const overrides = { tanggal: tanggalBaru, dari_tanggal: '', sampai_tanggal: '' };
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
        const blnBaru = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
        setTempBulan(blnBaru);
        setDariTanggal('');
        setSampaiTanggal('');
        setTanggal('');
        terapkan({
            bulan: blnBaru,
            tanggal: '',
            dari_tanggal: '',
            sampai_tanggal: '',
        });
    }

    function resetFilter() {
        setCari('');
        setTipe('');
        setTipePengeluaran('');
        setTanggal('');
        setDariTanggal('');
        setSampaiTanggal('');
        setTempDari('');
        setTempSampai('');
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

    useEffect(() => {
        setDariTanggal(filters.dari_tanggal ?? '');
        setTempDari(filters.dari_tanggal ?? '');
    }, [filters.dari_tanggal]);

    useEffect(() => {
        setSampaiTanggal(filters.sampai_tanggal ?? '');
        setTempSampai(filters.sampai_tanggal ?? '');
    }, [filters.sampai_tanggal]);

    useEffect(() => {
        setTempBulan(bulanFilter);
    }, [bulanFilter]);

    function formatTglIndo(tglStr) {
        if (!tglStr) return '';
        try {
            const [y, m, d] = tglStr.split('-').map(Number);
            const dt = new Date(y, m - 1, d);
            return dt.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
        } catch {
            return tglStr;
        }
    }

    const labelBulan = useMemo(() => {
        const [tahun, bulanAngka] = bulanFilter.split('-').map(Number);
        return new Date(tahun, bulanAngka - 1, 1).toLocaleDateString('id-ID', { month: 'long', year: 'numeric' });
    }, [bulanFilter]);

    const isRentangAktif = Boolean(filters.dari_tanggal || filters.sampai_tanggal);

    const labelPeriode = useMemo(() => {
        if (filters.dari_tanggal && filters.sampai_tanggal) {
            if (filters.dari_tanggal === filters.sampai_tanggal) {
                return formatTglIndo(filters.dari_tanggal);
            }
            return `${formatTglIndo(filters.dari_tanggal)} – ${formatTglIndo(filters.sampai_tanggal)}`;
        }
        if (filters.dari_tanggal) return `Mulai ${formatTglIndo(filters.dari_tanggal)}`;
        if (filters.sampai_tanggal) return `Hingga ${formatTglIndo(filters.sampai_tanggal)}`;
        if (filters.tanggal) return `per ${formatTglIndo(filters.tanggal)}`;
        return labelBulan;
    }, [filters.dari_tanggal, filters.sampai_tanggal, filters.tanggal, labelBulan]);

    const bulanIni = new Date();
    const kunciBulanIni = `${bulanIni.getFullYear()}-${String(bulanIni.getMonth() + 1).padStart(2, '0')}`;
    const filterAktif = Boolean(
        filters.kanal || filters.cari || filters.tanggal || filters.dari_tanggal || filters.sampai_tanggal || filters.tipe_pengeluaran
    );
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
            <span className={`inline-flex items-center gap-1 text-[11px] font-semibold px-2 py-0.5 rounded-md border whitespace-nowrap ${
                kanal === 'kas_kecil'
                    ? 'bg-amber-50/90 text-amber-800 border-amber-200/70'
                    : 'bg-slate-100/90 text-slate-700 border-slate-200/80'
            }`}>
                <Ikon size={11} aria-hidden="true" className="shrink-0" />
                {meta.label}
            </span>
        );
    }

    function badgeKantong(kantong) {
        return (
            <span className="inline-flex items-center text-[10px] font-semibold uppercase tracking-wider px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 border border-slate-200/60 whitespace-nowrap">
                Pos: {kantongLabel[kantong] ?? kantong}
            </span>
        );
    }

    function tampilBadge(r, sertakanKanal = false) {
        const sama = r.kantong === r.kanal;
        return (
            <>
                {sertakanKanal && badgeKanal(r.kanal)}
                {!sama && badgeKantong(r.kantong)}
                {r.tipe_pengeluaran_label && (
                    <span className="inline-flex items-center text-[10px] font-semibold uppercase tracking-wider px-2 py-0.5 rounded-md bg-rose-50 text-rose-700 border border-rose-200/70 whitespace-nowrap">
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
                className="inline-flex items-center gap-0.5 text-xs font-semibold text-brand-green-dark hover:text-brand-green hover:underline shrink-0"
            >
                Lihat asal
                <ChevronRight size={12} aria-hidden="true" />
            </Link>
        );
    }

    const netPeriode = (ringkasanPeriode?.total_masuk ?? 0) - (ringkasanPeriode?.total_keluar ?? 0);

    return (
        <AppLayout>
            <Head title="Kas Koperasi" />

            <PageHeader title="Kas Koperasi" subtitle="Buku besar arus kas, rekonsiliasi saldo & mutasi operasional">
                {bisaTopup && (
                    <div className="flex items-center gap-2 flex-wrap">
                        <div className="hidden sm:inline-flex items-center rounded-xl bg-white border border-slate-200/90 p-1 shadow-2xs">
                            <button
                                type="button"
                                onClick={() => { setTabDrawer('pemasukan'); setShowDrawer(true); }}
                                className={`inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold text-emerald-700 hover:bg-emerald-50 transition-colors ${fokusRing}`}
                            >
                                <ArrowDownCircle size={14} className="text-emerald-600" />
                                Pemasukan
                            </button>
                            <span className="w-px h-3.5 bg-slate-200" aria-hidden="true" />
                            <button
                                type="button"
                                onClick={() => { setTabDrawer('pengeluaran'); setShowDrawer(true); }}
                                className={`inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold text-rose-700 hover:bg-rose-50 transition-colors ${fokusRing}`}
                            >
                                <ArrowUpCircle size={14} className="text-rose-600" />
                                Pengeluaran
                            </button>
                            <span className="w-px h-3.5 bg-slate-200" aria-hidden="true" />
                            <button
                                type="button"
                                onClick={() => { setTabDrawer('sisih'); setShowDrawer(true); }}
                                className={`inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold text-amber-800 hover:bg-amber-50 transition-colors ${fokusRing}`}
                            >
                                <ArrowRightLeft size={13} className="text-amber-600" />
                                Sisih Kas Kecil
                            </button>
                        </div>

                        <Button
                            size="sm"
                            onClick={() => { setTabDrawer('pemasukan'); setShowDrawer(true); }}
                            className="sm:hidden rounded-full shadow-xs"
                        >
                            <Plus size={16} aria-hidden="true" />
                            Catat Mutasi
                        </Button>
                    </div>
                )}
            </PageHeader>

            {/* Interactive Multi-Account Switcher (Minimalist Executive Style) */}
            <div className="grid grid-cols-1 sm:grid-cols-3 gap-3.5 mb-5" role="tablist" aria-label="Pilih akun kas">
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
                            className={`group text-left p-4 sm:p-5 rounded-2xl border transition-all duration-200 relative overflow-hidden flex flex-col justify-between ${fokusRing} ${
                                isAktif
                                    ? 'bg-gradient-to-br from-[#0B1527] via-[#0F1E36] to-[#0A2E1A] text-white border-slate-800 shadow-xs'
                                    : 'bg-white text-slate-800 border-slate-200/80 hover:border-slate-300 shadow-2xs'
                            }`}
                        >
                            <div className="flex items-start justify-between gap-2">
                                <div className="flex items-center gap-2.5">
                                    <span className={`w-9 h-9 rounded-xl border flex items-center justify-center shrink-0 transition-colors ${
                                        isAktif ? 'bg-white/10 border-white/10 text-emerald-400' : 'bg-slate-100 border-slate-200/60 text-slate-700'
                                    }`}>
                                        <Landmark size={17} />
                                    </span>
                                    <div>
                                        <h3 className={`text-xs font-bold uppercase tracking-wider ${isAktif ? 'text-white' : 'text-slate-800'}`}>
                                            Semua Kas
                                        </h3>
                                        <p className={`text-[11px] ${isAktif ? 'text-slate-300/80' : 'text-slate-400'}`}>
                                            Gabungan Bank &amp; Kas Kecil
                                        </p>
                                    </div>
                                </div>
                                {isAktif ? (
                                    <span className="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-500/15 text-emerald-300 border border-emerald-400/25">
                                        <Check size={11} strokeWidth={3} />
                                        Aktif
                                    </span>
                                ) : (
                                    <span className="text-[11px] font-medium text-slate-400 group-hover:text-slate-600 transition-colors">
                                        Pilih
                                    </span>
                                )}
                            </div>

                            <div className="mt-3.5">
                                <p className={`text-[11px] font-medium ${isAktif ? 'text-slate-300/80' : 'text-slate-400'}`}>
                                    Saldo Saat Ini
                                </p>
                                <p className={`text-xl sm:text-2xl font-extrabold tracking-tight tabular-nums privacy-blur mt-0.5 truncate ${isAktif ? 'text-white' : 'text-slate-900'}`}>
                                    {formatRupiah(totalSaldo)}
                                </p>
                            </div>

                            <div className={`mt-3 pt-2.5 border-t flex items-center justify-between text-[11px] font-semibold tabular-nums privacy-blur ${
                                isAktif ? 'border-white/10' : 'border-slate-100'
                            }`}>
                                <span className={isAktif ? 'text-emerald-300' : 'text-emerald-600'}>
                                    Masuk: +{formatRupiahSingkat(ringkasanPeriode.total_masuk)}
                                </span>
                                <span className={isAktif ? 'text-rose-300' : 'text-rose-600'}>
                                    Keluar: -{formatRupiahSingkat(ringkasanPeriode.total_keluar)}
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
                            className={`group text-left p-4 sm:p-5 rounded-2xl border transition-all duration-200 relative overflow-hidden flex flex-col justify-between ${fokusRing} ${
                                isAktif
                                    ? 'bg-gradient-to-br from-[#0B1527] via-[#0F1E36] to-[#0A2E1A] text-white border-slate-800 shadow-xs'
                                    : 'bg-white text-slate-800 border-slate-200/80 hover:border-slate-300 shadow-2xs'
                            }`}
                        >
                            <div className="flex items-start justify-between gap-2">
                                <div className="flex items-center gap-2.5">
                                    <span className={`w-9 h-9 rounded-xl border flex items-center justify-center shrink-0 transition-colors ${
                                        isAktif ? 'bg-white/10 border-white/10 text-emerald-400' : 'bg-slate-100 border-slate-200/60 text-brand-navy'
                                    }`}>
                                        <Landmark size={17} />
                                    </span>
                                    <div>
                                        <h3 className={`text-xs font-bold uppercase tracking-wider ${isAktif ? 'text-white' : 'text-slate-800'}`}>
                                            Kas Bank
                                        </h3>
                                        <p className={`text-[11px] ${isAktif ? 'text-slate-300/80' : 'text-slate-400'}`}>
                                            Rekening Operasional
                                        </p>
                                    </div>
                                </div>
                                {isAktif ? (
                                    <span className="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-500/15 text-emerald-300 border border-emerald-400/25">
                                        <Check size={11} strokeWidth={3} />
                                        Aktif
                                    </span>
                                ) : (
                                    <span className="text-[11px] font-medium text-slate-400 group-hover:text-slate-600 transition-colors">
                                        Pilih
                                    </span>
                                )}
                            </div>

                            <div className="mt-3.5">
                                <p className={`text-[11px] font-medium ${isAktif ? 'text-slate-300/80' : 'text-slate-400'}`}>
                                    Saldo Saat Ini
                                </p>
                                <p className={`text-xl sm:text-2xl font-extrabold tracking-tight tabular-nums privacy-blur mt-0.5 truncate ${isAktif ? 'text-white' : 'text-slate-900'}`}>
                                    {formatRupiah(saldoBank ?? 0)}
                                </p>
                            </div>

                            <div className={`mt-3 pt-2.5 border-t flex items-center justify-between text-[11px] font-semibold tabular-nums privacy-blur ${
                                isAktif ? 'border-white/10' : 'border-slate-100'
                            }`}>
                                <span className={isAktif ? 'text-emerald-300' : 'text-emerald-600'}>
                                    Masuk: +{formatRupiahSingkat(bankMasuk)}
                                </span>
                                <span className={isAktif ? 'text-rose-300' : 'text-rose-600'}>
                                    Keluar: -{formatRupiahSingkat(bankKeluar)}
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
                            className={`group text-left p-4 sm:p-5 rounded-2xl border transition-all duration-200 relative overflow-hidden flex flex-col justify-between ${fokusRing} ${
                                isAktif
                                    ? 'bg-gradient-to-br from-[#0B1527] via-[#0F1E36] to-[#0A2E1A] text-white border-slate-800 shadow-xs'
                                    : 'bg-white text-slate-800 border-slate-200/80 hover:border-slate-300 shadow-2xs'
                            }`}
                        >
                            <div className="flex items-start justify-between gap-2">
                                <div className="flex items-center gap-2.5">
                                    <span className={`w-9 h-9 rounded-xl border flex items-center justify-center shrink-0 transition-colors ${
                                        isAktif ? 'bg-white/10 border-white/10 text-amber-300' : 'bg-amber-50 border-amber-200/60 text-amber-700'
                                    }`}>
                                        <Wallet size={17} />
                                    </span>
                                    <div>
                                        <h3 className={`text-xs font-bold uppercase tracking-wider ${isAktif ? 'text-white' : 'text-slate-800'}`}>
                                            Kas Kecil Fisik
                                        </h3>
                                        <p className={`text-[11px] ${isAktif ? 'text-slate-300/80' : 'text-slate-400'}`}>
                                            Petty Cash Tunai
                                        </p>
                                    </div>
                                </div>
                                {isAktif ? (
                                    <span className="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-500/15 text-emerald-300 border border-emerald-400/25">
                                        <Check size={11} strokeWidth={3} />
                                        Aktif
                                    </span>
                                ) : (
                                    <span className="text-[11px] font-medium text-slate-400 group-hover:text-slate-600 transition-colors">
                                        Pilih
                                    </span>
                                )}
                            </div>

                            <div className="mt-3.5">
                                <p className={`text-[11px] font-medium ${isAktif ? 'text-slate-300/80' : 'text-slate-400'}`}>
                                    Saldo Saat Ini
                                </p>
                                <p className={`text-xl sm:text-2xl font-extrabold tracking-tight tabular-nums privacy-blur mt-0.5 truncate ${isAktif ? 'text-white' : 'text-slate-900'}`}>
                                    {formatRupiah(saldoKasKecil ?? 0)}
                                </p>
                            </div>

                            <div className={`mt-3 pt-2.5 border-t flex items-center justify-between text-[11px] font-semibold tabular-nums privacy-blur ${
                                isAktif ? 'border-white/10' : 'border-slate-100'
                            }`}>
                                <span className={isAktif ? 'text-emerald-300' : 'text-emerald-600'}>
                                    Masuk: +{formatRupiahSingkat(kkMasuk)}
                                </span>
                                <span className={isAktif ? 'text-rose-300' : 'text-rose-600'}>
                                    Keluar: -{formatRupiahSingkat(kkKeluar)}
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
                                    className="pl-10 tabular-nums privacy-blur"
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
                                {pemasukanForm.processing ? 'Menyimpan...' : <>Simpan Pemasukan • <span className="privacy-blur">{pemasukanForm.data.jumlah ? formatRupiah(Number(pemasukanForm.data.jumlah)) : 'Rp 0'}</span></>}
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
                                    className="pl-10 tabular-nums privacy-blur"
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
                                {pengeluaranForm.processing ? 'Menyimpan...' : <>Simpan Pengeluaran • <span className="privacy-blur">{pengeluaranForm.data.jumlah ? formatRupiah(Number(pengeluaranForm.data.jumlah)) : 'Rp 0'}</span></>}
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
                                    className="pl-10 tabular-nums privacy-blur"
                                />
                            </div>
                            <ChipNominal grup="sisih" nilai={sisihForm.data.jumlah} onPilih={(n) => sisihForm.setData('jumlah', n)} />
                            <p className="text-xs text-slate-400 mt-1">Saldo Bank saat ini: <span className="privacy-blur">{formatRupiah(saldoBank ?? 0)}</span></p>
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
                                {sisihForm.processing ? 'Menyimpan...' : <>Sisihkan Kas Kecil • <span className="privacy-blur">{sisihForm.data.jumlah ? formatRupiah(Number(sisihForm.data.jumlah)) : 'Rp 0'}</span></>}
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
                        {/* Kiri: Unified Periode Capsule (Bulanan + Rentang Tanggal Popover) */}
                        <div className="relative" ref={popoverRef}>
                            <div className="flex items-center gap-1.5 sm:gap-2">
                                {isRentangAktif ? (
                                    /* Kapsul Mode Rentang Tanggal Aktif */
                                    <div className="inline-flex items-center rounded-xl border border-brand-green/30 bg-brand-green/10 text-brand-green-dark shadow-2xs overflow-hidden">
                                        <button
                                            type="button"
                                            onClick={() => {
                                                setTabPeriode('rentang');
                                                setBukaPeriodePopover((v) => !v);
                                            }}
                                            aria-expanded={bukaPeriodePopover}
                                            aria-label="Ubah rentang tanggal"
                                            className={`flex items-center gap-1.5 px-3 py-1.5 text-xs sm:text-sm font-bold text-brand-green-dark hover:bg-brand-green/15 transition-colors ${fokusRing}`}
                                        >
                                            <CalendarDays size={14} className="text-brand-green shrink-0" />
                                            <span className="truncate max-w-[200px] sm:max-w-none">{labelPeriode}</span>
                                            <ChevronDown size={14} className={`text-brand-green/70 shrink-0 transition-transform duration-200 ${bukaPeriodePopover ? 'rotate-180' : ''}`} />
                                        </button>
                                        <button
                                            type="button"
                                            onClick={handleHapusRentang}
                                            title="Kembali ke filter bulanan"
                                            aria-label="Hapus filter rentang tanggal"
                                            className="px-2 py-1.5 text-brand-green-dark/70 hover:text-red-600 hover:bg-red-50/50 transition-colors border-l border-brand-green/20"
                                        >
                                            <X size={14} />
                                        </button>
                                    </div>
                                ) : (
                                    /* Kapsul Mode Bulanan Default dengan Stepper */
                                    <div className="inline-flex items-center rounded-xl border border-slate-200 bg-white shadow-2xs overflow-hidden">
                                        <button
                                            type="button"
                                            onClick={() => geserBulan(-1)}
                                            aria-label="Bulan sebelumnya"
                                            className={`px-2 py-1.5 text-slate-500 hover:text-slate-800 hover:bg-slate-50 transition-colors ${fokusRing}`}
                                        >
                                            <ChevronLeft size={16} />
                                        </button>

                                        <button
                                            type="button"
                                            onClick={() => {
                                                setTabPeriode('bulan');
                                                setBukaPeriodePopover((v) => !v);
                                            }}
                                            aria-expanded={bukaPeriodePopover}
                                            aria-label="Pilih periode kas"
                                            className={`flex items-center gap-1.5 px-2.5 py-1.5 text-xs sm:text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors border-x border-slate-100 ${fokusRing}`}
                                        >
                                            <CalendarDays size={14} className="text-slate-400 shrink-0" />
                                            <span className="capitalize">{labelBulan}</span>
                                            <ChevronDown size={14} className={`text-slate-400 shrink-0 transition-transform duration-200 ${bukaPeriodePopover ? 'rotate-180' : ''}`} />
                                        </button>

                                        <button
                                            type="button"
                                            onClick={() => geserBulan(1)}
                                            aria-label="Bulan berikutnya"
                                            className={`px-2 py-1.5 text-slate-500 hover:text-slate-800 hover:bg-slate-50 transition-colors ${fokusRing}`}
                                        >
                                            <ChevronRight size={16} />
                                        </button>
                                    </div>
                                )}

                                {!isRentangAktif && bulanFilter !== kunciBulanIni && (
                                    <button
                                        type="button"
                                        onClick={() => terapkan({ bulan: kunciBulanIni, tanggal: '', dari_tanggal: '', sampai_tanggal: '' })}
                                        className="text-xs font-bold text-brand-green-dark bg-brand-green-light rounded-full px-2.5 py-1 hover:bg-brand-green/20 transition-colors whitespace-nowrap"
                                    >
                                        Bulan ini
                                    </button>
                                )}
                            </div>

                            {/* Popover Flyout Periode */}
                            {bukaPeriodePopover && (
                                <div className="absolute left-0 top-full mt-2 z-40 w-[310px] sm:w-[350px] rounded-2xl bg-white p-3.5 shadow-xl border border-slate-200/90 animate-in fade-in zoom-in-95 duration-150">
                                    {/* Segmented Switcher Popover: Bulan vs Rentang */}
                                    <div className="flex rounded-xl bg-slate-100 p-1 mb-3">
                                        <button
                                            type="button"
                                            onClick={() => setTabPeriode('bulan')}
                                            className={`flex-1 py-1 text-xs font-bold rounded-lg transition-all ${
                                                tabPeriode === 'bulan'
                                                    ? 'bg-white text-slate-800 shadow-2xs'
                                                    : 'text-slate-500 hover:text-slate-800'
                                            }`}
                                        >
                                            Per Bulan
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => setTabPeriode('rentang')}
                                            className={`flex-1 py-1 text-xs font-bold rounded-lg transition-all ${
                                                tabPeriode === 'rentang'
                                                    ? 'bg-white text-brand-green-dark shadow-2xs'
                                                    : 'text-slate-500 hover:text-slate-800'
                                            }`}
                                        >
                                            Rentang Tanggal
                                        </button>
                                    </div>

                                    {tabPeriode === 'bulan' ? (
                                        /* Mode Tab Bulan */
                                        <div className="space-y-3">
                                            <div>
                                                <label className="block text-[11px] font-semibold text-slate-500 mb-1">
                                                    Pilih Bulan & Tahun
                                                </label>
                                                <input
                                                    type="month"
                                                    value={tempBulan}
                                                    onChange={(e) => setTempBulan(e.target.value)}
                                                    className="w-full px-3 py-1.5 text-xs sm:text-sm font-semibold rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-green/30"
                                                />
                                            </div>

                                            <div>
                                                <span className="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">
                                                    Pintasan Bulan
                                                </span>
                                                <div className="flex flex-wrap gap-1.5">
                                                    <button
                                                        type="button"
                                                        onClick={() => setTempBulan(kunciBulanIni)}
                                                        className="px-2.5 py-1 text-xs rounded-lg font-medium bg-slate-100 hover:bg-slate-200/70 text-slate-700 transition-colors"
                                                    >
                                                        Bulan Ini
                                                    </button>
                                                    {(() => {
                                                        const dLalu = new Date();
                                                        dLalu.setMonth(dLalu.getMonth() - 1);
                                                        const kunciLalu = `${dLalu.getFullYear()}-${String(dLalu.getMonth() + 1).padStart(2, '0')}`;
                                                        return (
                                                            <button
                                                                type="button"
                                                                onClick={() => setTempBulan(kunciLalu)}
                                                                className="px-2.5 py-1 text-xs rounded-lg font-medium bg-slate-100 hover:bg-slate-200/70 text-slate-700 transition-colors"
                                                            >
                                                                Bulan Lalu
                                                            </button>
                                                        );
                                                    })()}
                                                </div>
                                            </div>

                                            <div className="pt-2 border-t border-slate-100 flex items-center justify-between">
                                                <button
                                                    type="button"
                                                    onClick={() => setBukaPeriodePopover(false)}
                                                    className="px-2.5 py-1 text-xs font-semibold text-slate-500 hover:text-slate-800"
                                                >
                                                    Batal
                                                </button>
                                                <Button
                                                    size="sm"
                                                    type="button"
                                                    onClick={handleTerapkanBulan}
                                                    className="rounded-xl text-xs py-1"
                                                >
                                                    Terapkan Bulan
                                                </Button>
                                            </div>
                                        </div>
                                    ) : (
                                        /* Mode Tab Rentang Tanggal */
                                        <div className="space-y-3">
                                            <div>
                                                <span className="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">
                                                    Pintasan Rentang
                                                </span>
                                                <div className="flex flex-wrap gap-1">
                                                    {[
                                                        { id: 'hari_ini', label: 'Hari Ini' },
                                                        { id: '7_hari', label: '7 Hari' },
                                                        { id: '30_hari', label: '30 Hari' },
                                                        { id: 'bulan_ini', label: 'Bulan Ini' },
                                                        { id: 'bulan_lalu', label: 'Bulan Lalu' },
                                                    ].map((p) => (
                                                        <button
                                                            key={p.id}
                                                            type="button"
                                                            onClick={() => terapkanPreset(p.id)}
                                                            className="px-2 py-0.5 text-[11px] rounded-lg font-semibold bg-slate-100 hover:bg-brand-green/10 hover:text-brand-green-dark text-slate-700 transition-colors"
                                                        >
                                                            {p.label}
                                                        </button>
                                                    ))}
                                                </div>
                                            </div>

                                            <div className="grid grid-cols-2 gap-2">
                                                <div>
                                                    <label className="block text-[11px] font-semibold text-slate-600 mb-1">
                                                        Dari Tanggal
                                                    </label>
                                                    <input
                                                        type="date"
                                                        value={tempDari}
                                                        onChange={(e) => setTempDari(e.target.value)}
                                                        className="w-full px-2 py-1 text-xs font-medium rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-green/30"
                                                    />
                                                </div>
                                                <div>
                                                    <label className="block text-[11px] font-semibold text-slate-600 mb-1">
                                                        Sampai Tanggal
                                                    </label>
                                                    <input
                                                        type="date"
                                                        value={tempSampai}
                                                        onChange={(e) => setTempSampai(e.target.value)}
                                                        className="w-full px-2 py-1 text-xs font-medium rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-green/30"
                                                    />
                                                </div>
                                            </div>

                                            <div className="pt-2 border-t border-slate-100 flex items-center justify-between">
                                                <button
                                                    type="button"
                                                    onClick={() => {
                                                        setTempDari('');
                                                        setTempSampai('');
                                                    }}
                                                    className="px-2 py-1 text-xs font-semibold text-slate-400 hover:text-slate-600"
                                                >
                                                    Bersihkan
                                                </button>
                                                <div className="flex items-center gap-1.5">
                                                    <button
                                                        type="button"
                                                        onClick={() => setBukaPeriodePopover(false)}
                                                        className="px-2.5 py-1 text-xs font-semibold text-slate-500 hover:text-slate-800"
                                                    >
                                                        Batal
                                                    </button>
                                                    <Button
                                                        size="sm"
                                                        type="button"
                                                        disabled={!tempDari && !tempSampai}
                                                        onClick={handleTerapkanRentang}
                                                        className="rounded-xl text-xs py-1"
                                                    >
                                                        Terapkan Rentang
                                                    </Button>
                                                </div>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            )}
                        </div>

                        {/* Kanan / Tengah: Segmented Arus Kas + Kategori Keluar + Search */}
                        <div className="flex flex-wrap items-center gap-2 flex-1 lg:justify-end">
                            {/* Segmented Pill Arus Kas (Bersih tanpa pengulangan nominal singkat) */}
                            <div className="inline-flex items-center rounded-xl bg-slate-200/70 p-1 border border-slate-200/60">
                                <button
                                    type="button"
                                    onClick={() => handlePilihTipe('')}
                                    className={`px-3 py-1 rounded-lg text-xs font-semibold transition-all flex items-center gap-1.5 ${
                                        tipe === ''
                                            ? 'bg-white text-slate-900 shadow-2xs font-bold'
                                            : 'text-slate-600 hover:text-slate-900'
                                    }`}
                                >
                                    <span>Semua</span>
                                    <span className={`text-[10px] px-1.5 py-0.2 rounded-full font-bold tabular-nums ${
                                        tipe === '' ? 'bg-slate-100 text-slate-700' : 'bg-slate-200/80 text-slate-500'
                                    }`}>
                                        {riwayat.total ?? 0}
                                    </span>
                                </button>
                                <button
                                    type="button"
                                    onClick={() => handlePilihTipe('masuk')}
                                    className={`px-3 py-1 rounded-lg text-xs font-semibold transition-all flex items-center gap-1.5 ${
                                        tipe === 'masuk'
                                            ? 'bg-emerald-600 text-white shadow-2xs font-bold'
                                            : 'text-slate-600 hover:text-emerald-700 hover:bg-white/50'
                                    }`}
                                >
                                    <ArrowDownCircle size={13} className={tipe === 'masuk' ? 'text-white' : 'text-emerald-600'} />
                                    <span>Masuk (Debit)</span>
                                </button>
                                <button
                                    type="button"
                                    onClick={() => handlePilihTipe('keluar')}
                                    className={`px-3 py-1 rounded-lg text-xs font-semibold transition-all flex items-center gap-1.5 ${
                                        tipe === 'keluar'
                                            ? 'bg-rose-600 text-white shadow-2xs font-bold'
                                            : 'text-slate-600 hover:text-rose-700 hover:bg-white/50'
                                    }`}
                                >
                                    <ArrowUpCircle size={13} className={tipe === 'keluar' ? 'text-white' : 'text-rose-600'} />
                                    <span>Keluar (Kredit)</span>
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
                                        className="text-xs py-1 px-2.5 rounded-xl border-rose-200 bg-rose-50/90 text-rose-800 font-semibold focus:ring-rose-400"
                                    >
                                        <option value="">Semua Kategori Keluar</option>
                                        {Object.entries(tipePengeluaranList).map(([k, label]) => (
                                            <option key={k} value={k}>{label}</option>
                                        ))}
                                    </Select>
                                </div>
                            )}

                            {/* Search Box */}
                            <div className="relative min-w-[170px] sm:min-w-[220px] flex-1 sm:flex-initial">
                                <Search size={14} className="absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
                                <input
                                    type="text"
                                    value={cari}
                                    onChange={(e) => setCari(e.target.value)}
                                    placeholder="Cari keterangan / anggota..."
                                    aria-label="Cari transaksi"
                                    className="w-full pl-8 pr-7 py-1.5 text-xs sm:text-sm rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-brand-green/30 transition-all placeholder:text-slate-400"
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

                                {isRentangAktif && (
                                    <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-brand-green/10 border border-brand-green/30 text-brand-green-dark font-medium shadow-2xs text-[11px]">
                                        <CalendarDays size={11} className="text-brand-green" />
                                        <span>Rentang: {labelPeriode}</span>
                                        <button
                                            type="button"
                                            onClick={handleHapusRentang}
                                            className="hover:text-red-500 rounded-full p-0.5 transition-colors"
                                            aria-label="Hapus filter rentang tanggal"
                                        >
                                            <X size={11} />
                                        </button>
                                    </span>
                                )}

                                {filters.tanggal && !isRentangAktif && (
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
                                    <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-rose-50 border border-rose-200 text-rose-700 font-medium shadow-2xs text-[11px]">
                                        <span>Kategori: {tipePengeluaranList[filters.tipe_pengeluaran] ?? filters.tipe_pengeluaran}</span>
                                        <button
                                            type="button"
                                            onClick={() => handlePilihTipePengeluaran('')}
                                            className="hover:text-rose-900 rounded-full p-0.5 transition-colors"
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
                                className="text-[11px] font-bold text-rose-600 hover:text-rose-800 hover:underline px-1.5 py-0.5 ml-auto sm:ml-0"
                            >
                                Reset Filter
                            </button>
                        </div>
                    )}
                </div>

                {/* 4. Panel Rekonsiliasi Akuntansi (5 Kolom Terpadu: Saldo Awal + Masuk - Keluar = Saldo Akhir) */}
                <div className="border-b border-slate-200/80 bg-white px-4 sm:px-5 py-3.5">
                    <div className="flex flex-wrap items-center justify-between gap-2 mb-3 pb-2.5 border-b border-slate-100">
                        <div className="flex items-center gap-2 min-w-0">
                            <CalendarDays size={15} className="text-slate-400 shrink-0" />
                            <p className="text-xs sm:text-sm text-slate-600 truncate">
                                Buku Kas & Rekonsiliasi Periode <span className="font-bold text-slate-900">{labelPeriode}</span>
                                <span className="text-slate-400 mx-1.5">•</span>
                                <span className="font-semibold text-slate-700">
                                    {filters.kanal ? kanalMeta[filters.kanal]?.label : 'Gabungan Semua Kas'}
                                </span>
                            </p>
                        </div>
                        <div className="flex items-center gap-2">
                            {!saldoValid && (
                                <span className="text-[11px] text-amber-700 bg-amber-50 border border-amber-200/70 px-2 py-0.5 rounded-md font-medium">
                                    Mode Filter Aktif — Saldo awal/akhir disembunyikan
                                </span>
                            )}
                            <span className="text-xs text-slate-600 font-semibold tabular-nums px-2.5 py-0.5 rounded-full bg-slate-100 border border-slate-200/70">
                                {riwayat.total ?? 0} jurnal
                            </span>
                        </div>
                    </div>

                    <dl className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2.5 sm:gap-3">
                        {/* Kolom 1: Saldo Awal */}
                        <div className="rounded-xl bg-slate-50/90 border border-slate-200/60 px-3.5 py-2.5">
                            <dt className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                                Saldo Awal{labelAkunSaldo}
                            </dt>
                            <dd className="mt-0.5 text-sm sm:text-base font-bold tabular-nums text-slate-800 privacy-blur">
                                {saldoValid ? formatRupiah(ringkasanPeriode?.saldo_awal ?? 0) : '—'}
                            </dd>
                        </div>

                        {/* Kolom 2: Total Masuk (Debit) */}
                        <div className="rounded-xl bg-emerald-50/50 border border-emerald-200/60 px-3.5 py-2.5">
                            <dt className="text-[11px] font-semibold uppercase tracking-wider text-emerald-700/80">
                                + Masuk (Debit)
                            </dt>
                            <dd className="mt-0.5 text-sm sm:text-base font-bold tabular-nums text-emerald-700 privacy-blur">
                                +{formatRupiah(ringkasanPeriode?.total_masuk ?? 0)}
                            </dd>
                        </div>

                        {/* Kolom 3: Total Keluar (Kredit) */}
                        <div className="rounded-xl bg-rose-50/50 border border-rose-200/60 px-3.5 py-2.5">
                            <dt className="text-[11px] font-semibold uppercase tracking-wider text-rose-700/80">
                                − Keluar (Kredit)
                            </dt>
                            <dd className="mt-0.5 text-sm sm:text-base font-bold tabular-nums text-rose-600 privacy-blur">
                                -{formatRupiah(ringkasanPeriode?.total_keluar ?? 0)}
                            </dd>
                        </div>

                        {/* Kolom 4: Mutasi Bersih (Net Cashflow) */}
                        <div className="rounded-xl bg-slate-50/90 border border-slate-200/60 px-3.5 py-2.5">
                            <dt className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                                Mutasi Bersih (Net)
                            </dt>
                            <dd className={`mt-0.5 text-sm sm:text-base font-bold tabular-nums privacy-blur ${
                                netPeriode > 0
                                    ? 'text-emerald-700'
                                    : netPeriode < 0
                                      ? 'text-rose-600'
                                      : 'text-slate-700'
                            }`}>
                                {netPeriode >= 0 ? `+${formatRupiah(netPeriode)}` : `-${formatRupiah(Math.abs(netPeriode))}`}
                            </dd>
                        </div>

                        {/* Kolom 5: Saldo Akhir */}
                        <div className="col-span-2 sm:col-span-1 rounded-xl bg-slate-900 text-white border border-slate-800 px-3.5 py-2.5 shadow-2xs">
                            <dt className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                                = Saldo Akhir{labelAkunSaldo}
                            </dt>
                            <dd className="mt-0.5 text-sm sm:text-base font-extrabold tabular-nums text-white privacy-blur">
                                {saldoValid ? formatRupiah(ringkasanPeriode?.saldo_akhir ?? 0) : '—'}
                            </dd>
                        </div>
                    </dl>
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
                                        <tr className="border-b border-slate-200 bg-slate-50/90 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                            <th className="px-5 py-3 min-w-[300px]">Uraian & Keterangan Transaksi</th>
                                            <th className="px-4 py-3 w-36 whitespace-nowrap">Akun Kas</th>
                                            <th className="px-4 py-3 w-40 text-right whitespace-nowrap text-emerald-700">Debit / Masuk (+)</th>
                                            <th className="px-4 py-3 w-40 text-right whitespace-nowrap text-rose-700">Kredit / Keluar (−)</th>
                                            <th className="px-5 py-3 w-48 text-right whitespace-nowrap text-slate-700">
                                                {!filters.kanal && saldoValid ? 'Saldo Gabungan' : 'Saldo Kas'}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100">
                                        {grupHari.map(([tanggalIso, grup]) => {
                                            const ringkasanHari = ringkasanPerTanggal[tanggalIso] ?? { masuk: 0, keluar: 0 };
                                            const netHari = (ringkasanHari.masuk ?? 0) - (ringkasanHari.keluar ?? 0);
                                            return (
                                                <Fragment key={tanggalIso}>
                                                    {/* Header Grup Tanggal */}
                                                    <tr className="bg-slate-100/80 border-y border-slate-200/80 text-xs text-slate-600">
                                                        <td colSpan={2} className="px-5 py-2">
                                                            <div className="flex items-center gap-2">
                                                                <span className="font-bold text-slate-900">{grup.tanggal}</span>
                                                                <span className="text-slate-500 font-medium">({grup.hari})</span>
                                                                <span className="text-[11px] font-medium text-slate-400">• {grup.baris.length} transaksi</span>
                                                            </div>
                                                        </td>
                                                        <td className="px-4 py-2 text-right font-bold text-emerald-700 tabular-nums text-xs whitespace-nowrap privacy-blur">
                                                            {ringkasanHari.masuk > 0 ? `+${formatRupiah(ringkasanHari.masuk)}` : '—'}
                                                        </td>
                                                        <td className="px-4 py-2 text-right font-bold text-rose-600 tabular-nums text-xs whitespace-nowrap privacy-blur">
                                                            {ringkasanHari.keluar > 0 ? `-${formatRupiah(ringkasanHari.keluar)}` : '—'}
                                                        </td>
                                                        <td className="px-5 py-2 text-right text-xs text-slate-600 tabular-nums font-semibold whitespace-nowrap privacy-blur">
                                                            Net: {netHari >= 0 ? `+${formatRupiah(netHari)}` : `-${formatRupiah(Math.abs(netHari))}`}
                                                        </td>
                                                    </tr>

                                                    {/* Baris Transaksi */}
                                                    {grup.baris.map((r) => (
                                                        <tr key={r.id} className="hover:bg-slate-50/90 transition-colors">
                                                            <td className="px-5 py-3.5 align-top">
                                                                <div className="flex items-start gap-3 min-w-0">
                                                                    <span className={`w-2 h-2 rounded-full shrink-0 mt-1.5 ${r.tipe === 'masuk' ? 'bg-emerald-500' : 'bg-rose-500'}`} />
                                                                    <div className="min-w-0 flex-1">
                                                                        <div className="flex items-center gap-2 flex-wrap">
                                                                            <p className="font-bold text-slate-900 text-sm">
                                                                                {labelKategori(r)}
                                                                            </p>
                                                                            {tampilBadge(r, false)}
                                                                        </div>
                                                                        {r.sub_judul && (
                                                                            <p className="text-xs font-medium text-slate-700 mt-0.5 truncate max-w-xl" title={r.sub_judul}>
                                                                                {r.sub_judul}
                                                                            </p>
                                                                        )}
                                                                        {(r.keterangan || r.rute_asal) && (
                                                                            <div className="flex items-center gap-2 text-xs text-slate-500 mt-0.5 flex-wrap">
                                                                                {r.keterangan && (
                                                                                    <span className="truncate max-w-lg" title={r.keterangan}>{r.keterangan}</span>
                                                                                )}
                                                                                {barisRuteAsal(r)}
                                                                            </div>
                                                                        )}
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td className="px-4 py-3.5 align-top whitespace-nowrap">
                                                                {badgeKanal(r.kanal)}
                                                            </td>
                                                            <td className="px-4 py-3.5 text-right align-top tabular-nums whitespace-nowrap privacy-blur">
                                                                {r.tipe === 'masuk' ? (
                                                                    <span className="font-bold text-emerald-600 text-sm">
                                                                        +{formatRupiah(r.jumlah)}
                                                                    </span>
                                                                ) : (
                                                                    <span className="text-slate-300 font-normal text-sm">—</span>
                                                                )}
                                                            </td>
                                                            <td className="px-4 py-3.5 text-right align-top tabular-nums whitespace-nowrap privacy-blur">
                                                                {r.tipe === 'keluar' ? (
                                                                    <span className="font-bold text-rose-600 text-sm">
                                                                        -{formatRupiah(r.jumlah)}
                                                                    </span>
                                                                ) : (
                                                                    <span className="text-slate-300 font-normal text-sm">—</span>
                                                                )}
                                                            </td>
                                                            <td className="px-5 py-3.5 text-right align-top tabular-nums whitespace-nowrap privacy-blur">
                                                                {!filters.kanal && saldoValid && r.saldo_gabungan_setelah !== null && r.saldo_gabungan_setelah !== undefined ? (
                                                                    <>
                                                                        <p className="font-bold text-slate-900 text-sm">
                                                                            {formatRupiah(r.saldo_gabungan_setelah)}
                                                                        </p>
                                                                        <p className="text-[11px] text-slate-500 font-medium mt-0.5">
                                                                            {r.kanal === 'bank' ? 'Bank' : 'Kas Kecil'}: {formatRupiah(r.saldo_setelah)}
                                                                        </p>
                                                                    </>
                                                                ) : (
                                                                    <>
                                                                        <p className="font-bold text-slate-900 text-sm">
                                                                            {r.saldo_setelah !== null && r.saldo_setelah !== undefined ? formatRupiah(r.saldo_setelah) : '—'}
                                                                        </p>
                                                                        <p className="text-[10px] text-slate-400 uppercase tracking-wider mt-0.5">
                                                                            {r.kanal === 'bank' ? 'Saldo Bank' : 'Saldo Kas Kecil'}
                                                                        </p>
                                                                    </>
                                                                )}
                                                            </td>
                                                        </tr>
                                                    ))}
                                                </Fragment>
                                            );
                                        })}
                                    </tbody>
                                    <tfoot>
                                        <tr className="border-t-2 border-slate-200 bg-slate-50/90 text-xs font-bold text-slate-800">
                                            <td colSpan={2} className="px-5 py-3 uppercase tracking-wider text-slate-600">
                                                Total Rekapitulasi Periode ({labelPeriode})
                                            </td>
                                            <td className="px-4 py-3 text-right tabular-nums text-emerald-700 whitespace-nowrap privacy-blur">
                                                +{formatRupiah(ringkasanPeriode?.total_masuk ?? 0)}
                                            </td>
                                            <td className="px-4 py-3 text-right tabular-nums text-rose-600 whitespace-nowrap privacy-blur">
                                                -{formatRupiah(ringkasanPeriode?.total_keluar ?? 0)}
                                            </td>
                                            <td className="px-5 py-3 text-right tabular-nums text-slate-900 whitespace-nowrap privacy-blur">
                                                {saldoValid
                                                    ? formatRupiah(ringkasanPeriode?.saldo_akhir ?? 0)
                                                    : `Net: ${netPeriode >= 0 ? `+${formatRupiah(netPeriode)}` : `-${formatRupiah(Math.abs(netPeriode))}`}`}
                                            </td>
                                        </tr>
                                    </tfoot>
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
                                                <div className="flex items-center gap-2 font-semibold tabular-nums text-[11px] privacy-blur">
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
                                                                    <p className="text-sm font-bold text-slate-900 truncate">
                                                                        {labelKategori(r)}
                                                                    </p>
                                                                    {r.sub_judul && (
                                                                        <p className="text-xs font-medium text-slate-700 truncate mt-0.5" title={r.sub_judul}>
                                                                            {r.sub_judul}
                                                                        </p>
                                                                    )}
                                                                    <div className="flex items-center gap-1.5 mt-1 flex-wrap">
                                                                        {tampilBadge(r, true)}
                                                                    </div>
                                                                    {r.keterangan && (
                                                                        <p className="text-[11px] text-slate-500 truncate mt-1" title={r.keterangan}>
                                                                            {r.keterangan}
                                                                        </p>
                                                                    )}
                                                                    {barisRuteAsal(r)}
                                                                </div>
                                                            </div>

                                                            {/* Kanan: Nominal + Saldo */}
                                                            <div className="text-right shrink-0 privacy-blur">
                                                                <p className={`text-sm font-bold tabular-nums whitespace-nowrap ${
                                                                    r.tipe === 'masuk' ? 'text-emerald-600' : 'text-rose-600'
                                                                }`}>
                                                                    {r.tipe === 'masuk' ? '+' : '-'} {formatRupiah(r.jumlah)}
                                                                </p>
                                                                <p className="text-[10px] text-slate-500 font-medium tabular-nums whitespace-nowrap mt-0.5">
                                                                    Saldo: {formatRupiah(!filters.kanal && saldoValid && r.saldo_gabungan_setelah !== null && r.saldo_gabungan_setelah !== undefined ? r.saldo_gabungan_setelah : r.saldo_setelah)}
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
