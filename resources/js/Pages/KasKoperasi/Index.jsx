import AppLayout from '@/Layouts/AppLayout';
import { Head, usePage, useForm, router, Link } from '@inertiajs/react';
import {
    Wallet, ArrowDownCircle, ArrowUpCircle, Plus, ChevronLeft, ChevronRight,
    Landmark, ReceiptText, CalendarDays, Search, X, ArrowRightLeft
} from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import Card from '@/Components/ui/Card';
import Button from '@/Components/ui/Button';
import Pagination from '@/Components/ui/Pagination';
import PageHeader from '@/Components/ui/PageHeader';
import StatWidget from '@/Components/ui/StatWidget';
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
            { bulan: bulanFilter },
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
    const filterAktif = Boolean(filters.kanal || filters.cari || filters.tipe || filters.tanggal || filters.tipe_pengeluaran);

    // Kelompokkan baris per tanggal (riwayat sudah urut tanggal desc).
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
                    <div className="flex items-center gap-2 flex-wrap">
                        <Button
                            size="sm"
                            onClick={() => { setTabDrawer('pemasukan'); setShowDrawer(true); }}
                            className="rounded-full shadow-md shadow-brand-green/25 hover:-translate-y-px active:translate-y-0"
                        >
                            <Plus size={16} aria-hidden="true" />
                            Pemasukan / Pengeluaran
                        </Button>
                        <Button
                            size="sm"
                            variant="secondary"
                            onClick={() => { setTabDrawer('sisih'); setShowDrawer(true); }}
                            className="rounded-full shadow-md hover:-translate-y-px active:translate-y-0"
                        >
                            <ArrowRightLeft size={14} aria-hidden="true" />
                            Sisih Kas Kecil
                        </Button>
                    </div>
                )}
            </PageHeader>

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

            <Card padding="sm" className="shadow-md border-slate-200/70 mb-4">
                <div className="flex flex-col md:flex-row md:items-center gap-2 pb-3 mb-1 border-b border-slate-100">
                    <div className="grid grid-cols-2 md:grid-cols-12 gap-2 flex-1">
                        <div className="col-span-1 md:col-span-2">
                            <Select size="sm" value={filters.kanal ?? ''} onChange={(e) => terapkan({ kanal: e.target.value })} aria-label="Filter akun kas" className={fokusRing}>
                                <option value="">Semua akun kas</option>
                                {Object.entries(kanalMeta).map(([key, meta]) => (
                                    <option key={key} value={key}>{meta.label}</option>
                                ))}
                            </Select>
                        </div>
                        <div className="col-span-1 md:col-span-2 flex items-center gap-1">
                            <button
                                type="button"
                                onClick={() => handlePilihTipe('')}
                                aria-label="Filter semua tipe"
                                className={`px-2 py-2 rounded-lg text-xs font-medium transition-colors shrink-0 ${tipe === '' ? 'bg-brand-green-light text-brand-green-dark' : 'bg-white text-slate-600 border border-slate-300 hover:bg-slate-50'}`}
                            >
                                Semua
                            </button>
                            <button
                                type="button"
                                onClick={() => handlePilihTipe('masuk')}
                                aria-label="Filter pemasukan"
                                className={`px-2 py-2 rounded-lg text-xs font-medium transition-colors shrink-0 ${tipe === 'masuk' ? 'bg-emerald-100 text-emerald-700' : 'bg-white text-slate-600 border border-slate-300 hover:bg-slate-50'}`}
                            >
                                Masuk
                            </button>
                            <button
                                type="button"
                                onClick={() => handlePilihTipe('keluar')}
                                aria-label="Filter pengeluaran"
                                className={`px-2 py-2 rounded-lg text-xs font-medium transition-colors shrink-0 ${tipe === 'keluar' ? 'bg-red-50 text-red-700' : 'bg-white text-slate-600 border border-slate-300 hover:bg-slate-50'}`}
                            >
                                Keluar
                            </button>
                        </div>
                        {tipe === 'keluar' && Object.keys(tipePengeluaranList).length > 0 && (
                            <div className="col-span-2 md:col-span-2">
                                <Select
                                    size="sm"
                                    value={tipePengeluaran}
                                    onChange={(e) => handlePilihTipePengeluaran(e.target.value)}
                                    aria-label="Filter tipe pengeluaran"
                                    className={fokusRing}
                                >
                                    <option value="">Semua Tipe</option>
                                    {Object.entries(tipePengeluaranList).map(([key, label]) => (
                                        <option key={key} value={key}>{label}</option>
                                    ))}
                                </Select>
                            </div>
                        )}
                        <div className={`col-span-2 ${tipe === 'keluar' ? 'md:col-span-2' : 'md:col-span-3'} flex items-center rounded-xl border border-slate-300 bg-white overflow-hidden`}>
                            <button type="button" onClick={() => geserBulan(-1)} aria-label="Bulan sebelumnya" className={`px-2 py-2.5 text-slate-500 hover:text-slate-700 ${fokusRing}`}>
                                <ChevronLeft size={16} />
                            </button>
                            <input
                                type="month"
                                value={bulanFilter}
                                onChange={(e) => e.target.value && terapkan({ bulan: e.target.value, tanggal: '' })}
                                aria-label="Pilih bulan"
                                className="w-full px-1 py-2.5 text-base text-slate-700 border-x border-slate-200 bg-transparent focus:outline-none"
                            />
                            <button type="button" onClick={() => geserBulan(1)} aria-label="Bulan berikutnya" className={`px-2 py-2.5 text-slate-500 hover:text-slate-700 ${fokusRing}`}>
                                <ChevronRight size={16} />
                            </button>
                        </div>
                        <div className="relative col-span-2 md:col-span-2 flex items-center rounded-xl border border-slate-300 bg-white overflow-hidden">
                            <CalendarDays size={16} className="text-slate-400 shrink-0 ml-2 pointer-events-none" />
                            <input
                                type="date"
                                value={tanggal}
                                onChange={(e) => handlePilihTanggal(e.target.value)}
                                aria-label="Pilih tanggal"
                                className="w-full px-1 py-2.5 text-base text-slate-700 bg-transparent focus:outline-none pl-2 pr-7"
                            />
                            {tanggal && (
                                <button
                                    type="button"
                                    onClick={handleHapusTanggal}
                                    aria-label="Hapus filter tanggal"
                                    className="absolute right-2 text-slate-400 hover:text-slate-600 p-0.5"
                                >
                                    <X size={14} />
                                </button>
                            )}
                        </div>
                        <div className={`relative col-span-full ${tipe === 'keluar' ? 'md:col-span-2' : 'md:col-span-3'}`}>
                            <Search size={16} className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
                            <TextField
                                size="sm"
                                value={cari}
                                onChange={(e) => setCari(e.target.value)}
                                placeholder="Cari keterangan..."
                                aria-label="Cari keterangan"
                                className="pl-10 pr-9"
                            />
                            {cari && (
                                <button onClick={() => setCari('')} aria-label="Hapus pencarian" className="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                    <X size={16} />
                                </button>
                            )}
                        </div>
                    </div>
                    <div className="flex items-center gap-2 shrink-0">
                        {bulanFilter !== kunciBulanIni && (
                            <button onClick={() => terapkan({ bulan: kunciBulanIni })} className="text-xs font-bold text-brand-green-dark bg-brand-green-light rounded-full px-2.5 py-1.5 hover:bg-brand-green/20 transition-colors whitespace-nowrap">
                                Bulan ini
                            </button>
                        )}
                        {filterAktif && (
                            <button onClick={resetFilter} className="text-xs font-bold text-slate-500 hover:text-slate-700 rounded-full px-2.5 py-1.5 whitespace-nowrap">
                                Reset
                            </button>
                        )}
                        <span className="inline-flex items-center px-2.5 py-1.5 rounded-full text-xs font-bold whitespace-nowrap bg-brand-green-light text-brand-green-dark tabular-nums">
                            +{formatRupiahSingkat(ringkasanPeriode.total_masuk)}
                        </span>
                        <span className="inline-flex items-center px-2.5 py-1.5 rounded-full text-xs font-bold whitespace-nowrap bg-red-50 text-red-700 tabular-nums">
                            -{formatRupiahSingkat(ringkasanPeriode.total_keluar)}
                        </span>
                    </div>
                </div>
                <div className="flex items-center gap-1.5 min-w-0 px-1 pt-1">
                    <CalendarDays size={15} className="text-slate-400 shrink-0" />
                    <p className="text-sm text-slate-500 truncate">
                        Mutasi kas <span className="font-bold text-slate-800">{labelBulan}</span>
                    </p>
                </div>
                <div className={`transition-opacity duration-150 ${memuatFilter ? 'opacity-50 pointer-events-none' : 'opacity-100'}`}>
                    {riwayat.data.length === 0 ? (
                        <div className="text-center py-12 px-4">
                            <Wallet size={28} aria-hidden="true" className="mx-auto text-slate-300 mb-3" />
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
                                    <Button type="button" size="sm" className="rounded-full" onClick={() => { setTabDrawer('pemasukan'); setShowDrawer(true); }}>
                                        <Plus size={16} aria-hidden="true" />
                                        Pemasukan / Pengeluaran
                                    </Button>
                                </div>
                            )}
                        </div>
                    ) : (
                        <ul className="divide-y divide-slate-50">
                            {grupHari.map(([tanggalIso, grup]) => (
                                <li key={tanggalIso} className="pt-3 first:pt-1">
                                    <div className="flex items-baseline gap-2 px-1 pb-1.5">
                                        <p className="text-sm font-bold text-slate-700">{grup.tanggal}</p>
                                        <p className="text-xs text-slate-400">{grup.hari}</p>
                                        <p className="text-xs text-slate-400 ml-auto tabular-nums">{grup.baris.length} mutasi</p>
                                    </div>
                                    <div className="flex items-center gap-3 px-1 pb-1 text-xs">
                                        <span className="text-emerald-600 font-semibold">+{formatRupiah(ringkasanPerTanggal[tanggalIso]?.masuk ?? 0)}</span>
                                        <span className="text-red-600 font-semibold">-{formatRupiah(ringkasanPerTanggal[tanggalIso]?.keluar ?? 0)}</span>
                                        <span className="ml-auto text-slate-500">Saldo {formatRupiah((ringkasanPerTanggal[tanggalIso]?.masuk ?? 0) - (ringkasanPerTanggal[tanggalIso]?.keluar ?? 0))}</span>
                                    </div>
                                    <ul className="border-t border-slate-100/70">
                                        {grup.baris.map((r) => (
                                            <li key={r.id} className="flex items-center gap-3 px-1 py-3 hover:bg-gradient-to-r hover:from-slate-50 hover:to-transparent rounded-xl transition-all">
                                                {r.tipe === 'masuk' ? (
                                                    <ArrowDownCircle size={22} aria-hidden="true" className="text-brand-green shrink-0" />
                                                ) : (
                                                    <ArrowUpCircle size={22} aria-hidden="true" className="text-red-500 shrink-0" />
                                                )}
                                                <div className="flex-1 min-w-0">
                                                    <div className="flex items-center gap-2 flex-wrap">
                                                        <p className="text-sm font-semibold text-slate-700">
                                                            {labelKategori(r)}
                                                        </p>
                                                        {tampilBadge(r)}
                                                    </div>
                                                    {r.sub_judul && (
                                                        <p className="text-xs italic text-slate-500 mt-0.5 truncate" title={r.sub_judul}>{r.sub_judul}</p>
                                                    )}
                                                    <div className="flex items-center gap-2 mt-0.5">
                                                        {r.keterangan && (
                                                            <p className="text-xs text-slate-400 truncate" title={r.keterangan}>{r.keterangan}</p>
                                                        )}
                                                        {barisRuteAsal(r)}
                                                    </div>
                                                </div>
                                                <div className="text-right shrink-0 pl-2">
                                                    <p className={`text-sm font-bold whitespace-nowrap tabular-nums ${r.tipe === 'masuk' ? 'text-brand-green-dark' : 'text-red-600'}`}>
                                                        <span className="sr-only">{r.tipe === 'masuk' ? 'Masuk' : 'Keluar'} </span>
                                                        <span aria-hidden="true">{r.tipe === 'masuk' ? '+' : '-'} {formatRupiah(r.jumlah)}</span>
                                                    </p>
                                                    <p className="text-[11px] text-slate-400 whitespace-nowrap tabular-nums">
                                                        {`${r.kanal === 'bank' ? 'Saldo Bank' : 'Saldo Kas'}: ${formatRupiah(r.saldo_setelah)}`}
                                                    </p>
                                                </div>
                                            </li>
                                        ))}
                                    </ul>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            </Card>

            <Pagination links={riwayat.links} />
        </AppLayout>
    );
}
