import AppLayout from '@/Layouts/AppLayout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import {
    ArrowDownCircle, BookOpenText, CheckCircle2, ChevronRight, Percent, Plus, Search, Users, Wallet, X,
} from 'lucide-react';
import Button from '@/Components/ui/Button';
import Card from '@/Components/ui/Card';
import ChipNominal from '@/Components/ui/ChipNominal';
import CurrencyField from '@/Components/ui/CurrencyField';
import Drawer from '@/Components/ui/Drawer';
import {
    DrawerBody,
    DrawerFooter,
    DrawerFooterActions,
    DrawerSection,
} from '@/Components/ui/DrawerSection';
import FormField from '@/Components/ui/FormField';
import FotoAnggota from '@/Components/ui/FotoAnggota';
import PageHeader from '@/Components/ui/PageHeader';
import Pagination from '@/Components/ui/Pagination';
import Select from '@/Components/ui/Select';
import StatusBadge from '@/Components/ui/StatusBadge';
import TextField from '@/Components/ui/TextField';
import { formatRupiah } from '@/Utils/formatCurrency';
import { withIdempotencyKey } from '@/Utils/idempotency';
import useDebouncedValue from '@/Utils/useDebouncedValue';

export default function Index({
    rekening,
    filters = {},
    daftarCabang = [],
    statistik = {
        total_saldo_aktif: 0,
        jumlah_aktif: 0,
        jumlah_diajukan: 0,
        total_bunga_tahun_ini: 0,
        suku_bunga_aktif: 2,
    },
    bisaKelola = false,
    tabAktif = 'rekening',
    periodeBunga = '',
    previewBunga = null,
    rekeningAktifOptions = [],
    setting = { min_setor: 50000, saldo_minimal: 50000, suku_bunga_aktif: 2 },
}) {
    const daftarCabangAman = Array.isArray(daftarCabang) ? daftarCabang : Object.values(daftarCabang ?? {});
    const [cari, setCari] = useState(filters.cari ?? '');
    const [showSetorTunai, setShowSetorTunai] = useState(false);
    const cariDebounced = useDebouncedValue(cari);
    const pertama = useRef(true);
    const hariIni = new Date().toISOString().slice(0, 10);

    const setorTunaiForm = useForm({
        rekening_tabungan_id: rekeningAktifOptions[0]?.id ?? '',
        nominal: '',
        akun_kas: 'kas_kecil',
        tanggal: hariIni,
        keterangan: '',
        bukti: null,
    });

    const postingBungaForm = useForm({ periode: periodeBunga });

    useEffect(() => {
        postingBungaForm.setData('periode', periodeBunga);
    }, [periodeBunga]);

    useEffect(() => {
        if (!setorTunaiForm.data.rekening_tabungan_id && rekeningAktifOptions.length > 0) {
            setorTunaiForm.setData('rekening_tabungan_id', rekeningAktifOptions[0].id);
        }
    }, [rekeningAktifOptions]);

    const filterAktif = Boolean((filters.cari ?? '') || (filters.cabang ?? '') || (filters.status ?? ''));

    function terapkan(overrides = {}) {
        router.get(
            route('tabungan.index'),
            {
                tab: 'rekening',
                cari,
                cabang: filters.cabang ?? '',
                status: filters.status ?? '',
                ...overrides,
            },
            { preserveState: true, replace: true }
        );
    }

    function resetFilter() {
        setCari('');
        router.get(route('tabungan.index'), { tab: 'rekening' }, { preserveState: true, replace: true });
    }

    function pindahTabUtama(nextTab) {
        if (nextTab === 'bunga') {
            router.get(
                route('tabungan.index'),
                { tab: 'bunga', periode: periodeBunga || undefined },
                { preserveState: false, replace: true }
            );
            return;
        }
        router.get(
            route('tabungan.index'),
            {
                tab: 'rekening',
                cari: filters.cari ?? '',
                cabang: filters.cabang ?? '',
                status: filters.status ?? '',
            },
            { preserveState: false, replace: true }
        );
    }

    function ubahPeriodeBunga(periode) {
        router.get(route('tabungan.index'), { tab: 'bunga', periode }, { preserveState: false, replace: true });
    }

    function submitSetorTunai(e) {
        e.preventDefault();
        setorTunaiForm.post(route('bendahara.tabungan.setor-tunai'), {
            ...withIdempotencyKey({
                forceFormData: true,
                preserveScroll: true,
                onSuccess: () => {
                    setorTunaiForm.reset('nominal', 'keterangan', 'bukti');
                    setShowSetorTunai(false);
                },
            }),
        });
    }

    function submitPostingBunga() {
        postingBungaForm.post(
            route('bendahara.tabungan.posting-bunga'),
            withIdempotencyKey({ preserveScroll: true })
        );
    }

    useEffect(() => {
        if (pertama.current) {
            pertama.current = false;
            return;
        }
        if (tabAktif !== 'rekening') return;
        if ((cariDebounced ?? '') !== (filters.cari ?? '')) {
            terapkan({ cari: cariDebounced ?? '' });
        }
    }, [cariDebounced]);

    useEffect(() => {
        setCari(filters.cari ?? '');
    }, [filters.cari]);

    return (
        <AppLayout>
            <Head title="Tabungan Anggota" />

            <PageHeader
                title="Tabungan Anggota"
                subtitle="Daftar rekening tabungan sukarela berbunga, saldo, dan riwayat bunga anggota"
                actions={
                    bisaKelola ? (
                        <Button size="sm" onClick={() => setShowSetorTunai(true)} disabled={rekeningAktifOptions.length === 0}>
                            <Plus size={15} /> Setor Tunai
                        </Button>
                    ) : null
                }
            />

            <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
                <div className="rounded-2xl p-4 border bg-white border-slate-200/95 ring-1 ring-slate-900/[0.04] shadow-[0_1px_3px_rgba(15,30,54,0.07),0_10px_26px_-10px_rgba(15,30,54,0.12)] flex flex-col justify-between">
                    <div className="flex items-center justify-between gap-2">
                        <span className="text-xs font-semibold text-slate-500">Total Saldo Tabungan</span>
                        <span className="w-8 h-8 rounded-xl bg-brand-navy/5 text-brand-navy flex items-center justify-center shrink-0">
                            <Wallet size={16} />
                        </span>
                    </div>
                    <div className="mt-2">
                        <p className="text-xl sm:text-2xl font-bold text-[#0F1E36] tabular-nums privacy-blur">
                            {formatRupiah(statistik.total_saldo_aktif)}
                        </p>
                        <p className="text-xs text-slate-400 mt-1">Rekening aktif</p>
                    </div>
                </div>

                <div className="rounded-2xl p-4 border bg-white border-slate-200/95 ring-1 ring-slate-900/[0.04] shadow-[0_1px_3px_rgba(15,30,54,0.07),0_10px_26px_-10px_rgba(15,30,54,0.12)] flex flex-col justify-between">
                    <div className="flex items-center justify-between gap-2">
                        <span className="text-xs font-semibold text-slate-500">Rekening Aktif</span>
                        <span className="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center shrink-0">
                            <Users size={16} />
                        </span>
                    </div>
                    <div className="mt-2">
                        <p className="text-xl sm:text-2xl font-bold text-[#0F1E36] tabular-nums">
                            {statistik.jumlah_aktif}
                        </p>
                        <p className="text-xs text-slate-400 mt-1">
                            {statistik.jumlah_diajukan} pengajuan menunggu
                        </p>
                    </div>
                </div>

                <div className="rounded-2xl p-4 border bg-white border-slate-200/95 ring-1 ring-slate-900/[0.04] shadow-[0_1px_3px_rgba(15,30,54,0.07),0_10px_26px_-10px_rgba(15,30,54,0.12)] flex flex-col justify-between">
                    <div className="flex items-center justify-between gap-2">
                        <span className="text-xs font-semibold text-slate-500">Bunga Terbukukan</span>
                        <span className="w-8 h-8 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center shrink-0">
                            <BookOpenText size={16} />
                        </span>
                    </div>
                    <div className="mt-2">
                        <p className="text-xl sm:text-2xl font-bold text-[#0F1E36] tabular-nums privacy-blur">
                            {formatRupiah(statistik.total_bunga_tahun_ini)}
                        </p>
                        <p className="text-xs text-slate-400 mt-1">Akumulasi tahun berjalan</p>
                    </div>
                </div>

                <div className="rounded-2xl p-4 border bg-white border-slate-200/95 ring-1 ring-slate-900/[0.04] shadow-[0_1px_3px_rgba(15,30,54,0.07),0_10px_26px_-10px_rgba(15,30,54,0.12)] flex flex-col justify-between">
                    <div className="flex items-center justify-between gap-2">
                        <span className="text-xs font-semibold text-slate-500">Suku Bunga Aktif</span>
                        <span className="w-8 h-8 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center shrink-0">
                            <Percent size={16} />
                        </span>
                    </div>
                    <div className="mt-2">
                        <p className="text-xl sm:text-2xl font-bold text-[#0F1E36] tabular-nums">
                            {statistik.suku_bunga_aktif}% / thn
                        </p>
                        <p className="text-xs text-slate-400 mt-1">Saldo harian, kredit bulanan</p>
                    </div>
                </div>
            </div>

            {bisaKelola && (
                <div className="flex flex-wrap gap-1.5 mb-4 bg-slate-100/90 border border-slate-200/70 p-1 rounded-xl w-full sm:w-fit">
                    <button
                        type="button"
                        onClick={() => pindahTabUtama('rekening')}
                        className={`flex items-center gap-2 px-3.5 py-2 text-xs sm:text-sm font-semibold rounded-lg transition-all ${
                            tabAktif === 'rekening'
                                ? 'bg-gradient-to-r from-brand-navy to-brand-navy-light text-white shadow-xs'
                                : 'text-slate-600 hover:bg-white/80 hover:text-brand-navy'
                        }`}
                    >
                        <span>Daftar Rekening</span>
                    </button>
                    <button
                        type="button"
                        onClick={() => pindahTabUtama('bunga')}
                        className={`flex items-center gap-2 px-3.5 py-2 text-xs sm:text-sm font-semibold rounded-lg transition-all ${
                            tabAktif === 'bunga'
                                ? 'bg-gradient-to-r from-brand-navy to-brand-navy-light text-white shadow-xs'
                                : 'text-slate-600 hover:bg-white/80 hover:text-brand-navy'
                        }`}
                    >
                        <Percent size={14} />
                        <span>Posting Bunga Bulanan</span>
                    </button>
                </div>
            )}

            {bisaKelola && tabAktif === 'bunga' && previewBunga ? (
                <div className="space-y-4">
                    <Card padding="sm">
                        <div className="flex flex-col sm:flex-row sm:items-end justify-between gap-3">
                            <div className="flex flex-wrap items-end gap-3">
                                <div className="w-44">
                                    <FormField label="Periode Bunga (YYYY-MM)">
                                        <TextField
                                            size="sm"
                                            type="month"
                                            value={periodeBunga}
                                            onChange={(e) => ubahPeriodeBunga(e.target.value)}
                                        />
                                    </FormField>
                                </div>
                                <div className="pb-3.5 flex items-center gap-4 text-xs sm:text-sm">
                                    <div>
                                        <span className="text-slate-500">Belum Diposting: </span>
                                        <strong className="text-brand-navy tabular-nums">{previewBunga.belum_diposting} / {previewBunga.total_rekening} rek</strong>
                                    </div>
                                    <div>
                                        <span className="text-slate-500">Total Bunga Periode: </span>
                                        <strong className="text-brand-green-dark tabular-nums privacy-blur">{formatRupiah(previewBunga.total_bunga)}</strong>
                                    </div>
                                </div>
                            </div>
                            <div className="pb-3.5">
                                <Button
                                    type="button"
                                    disabled={postingBungaForm.processing || previewBunga.belum_diposting === 0}
                                    onClick={submitPostingBunga}
                                >
                                    <Percent size={15} />
                                    {postingBungaForm.processing
                                        ? 'Memposting...'
                                        : previewBunga.belum_diposting === 0
                                        ? 'Semua Sudah Diposting'
                                        : `Posting Bunga (${previewBunga.belum_diposting} Rek)`}
                                </Button>
                            </div>
                        </div>
                    </Card>

                    <Card padding="none" className="overflow-hidden">
                        <div className="overflow-x-auto">
                            <table className="w-full text-left border-collapse text-sm">
                                <thead>
                                    <tr className="bg-[#F8FAFC] border-b border-slate-200/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                        <th className="px-4 py-2.5">Anggota</th>
                                        <th className="px-4 py-2.5">No. Rek</th>
                                        <th className="px-4 py-2.5 text-right">Saldo Dasar</th>
                                        <th className="px-4 py-2.5 text-right">Hari</th>
                                        <th className="px-4 py-2.5 text-right">Tarif</th>
                                        <th className="px-4 py-2.5 text-right">Bunga</th>
                                        <th className="px-4 py-2.5">Status</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {previewBunga.baris.length === 0 ? (
                                        <tr>
                                            <td colSpan={7} className="px-4 py-10 text-center text-slate-400">
                                                Tidak ada rekening aktif pada periode ini.
                                            </td>
                                        </tr>
                                    ) : (
                                        previewBunga.baris.map((row) => (
                                            <tr key={row.rekening_id} className="hover:bg-[#F2FBF5] transition-colors">
                                                <td className="px-4 py-2.5">
                                                    <p className="font-semibold text-slate-800">{row.nama}</p>
                                                    <p className="text-xs text-slate-500">{row.no_karyawan}</p>
                                                </td>
                                                <td className="px-4 py-2.5 font-mono text-xs text-brand-navy">{row.no_rekening}</td>
                                                <td className="px-4 py-2.5 text-right tabular-nums privacy-blur text-slate-700">{formatRupiah(row.saldo_dasar)}</td>
                                                <td className="px-4 py-2.5 text-right tabular-nums text-slate-600">{row.jumlah_hari}</td>
                                                <td className="px-4 py-2.5 text-right tabular-nums text-slate-700">{row.suku_bunga_snapshot}%</td>
                                                <td className="px-4 py-2.5 text-right font-bold text-brand-green-dark tabular-nums privacy-blur">
                                                    +{formatRupiah(row.bunga_bersih)}
                                                </td>
                                                <td className="px-4 py-2.5">
                                                    {row.sudah_diposting ? (
                                                        <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-brand-green-light text-brand-green-dark">
                                                            <CheckCircle2 size={12} /> Diposting
                                                        </span>
                                                    ) : (
                                                        <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700">
                                                            Siap Posting
                                                        </span>
                                                    )}
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </Card>
                </div>
            ) : (
                <>
                    <Card padding="sm" className="mb-4">
                        <div className="flex flex-col sm:flex-row gap-2.5 items-stretch sm:items-center">
                            <div className="relative flex-1 group">
                                <Search size={15} aria-hidden="true" className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-brand-green transition-colors pointer-events-none select-none" />
                                <TextField
                                    size="sm"
                                    value={cari}
                                    onChange={(e) => setCari(e.target.value)}
                                    placeholder="Cari nama anggota, NIK, atau nomor rekening..."
                                    className="pl-10 pr-8"
                                />
                            </div>
                            <div className="w-full sm:w-44">
                                <Select size="sm" value={filters.cabang ?? ''} onChange={(e) => terapkan({ cabang: e.target.value })}>
                                    <option value="">Semua Cabang</option>
                                    {daftarCabangAman.map((c) => (
                                        <option key={c} value={c}>{c}</option>
                                    ))}
                                </Select>
                            </div>
                            <div className="w-full sm:w-40">
                                <Select size="sm" value={filters.status ?? ''} onChange={(e) => terapkan({ status: e.target.value })}>
                                    <option value="">Semua Status</option>
                                    <option value="aktif">Aktif</option>
                                    <option value="diajukan">Diajukan</option>
                                    <option value="ditutup">Ditutup</option>
                                    <option value="ditolak">Ditolak</option>
                                </Select>
                            </div>
                            {filterAktif && (
                                <button
                                    type="button"
                                    onClick={resetFilter}
                                    className="inline-flex items-center justify-center gap-1 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:text-slate-900 rounded-lg border border-slate-200 hover:bg-slate-50"
                                >
                                    <X size={13} /> Reset
                                </button>
                            )}
                        </div>
                    </Card>

                    <Card padding="none" className="overflow-hidden">
                        <div className="overflow-x-auto">
                            <table className="w-full text-left border-collapse text-sm">
                                <thead>
                                    <tr className="bg-[#F8FAFC] border-b border-slate-200/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                        <th className="px-4 py-3">Anggota</th>
                                        <th className="px-4 py-3">No. Rekening</th>
                                        <th className="px-4 py-3">Cabang</th>
                                        <th className="px-4 py-3">Tgl Buka</th>
                                        <th className="px-4 py-3 text-right">Saldo</th>
                                        <th className="px-4 py-3">Status</th>
                                        <th className="px-4 py-3 text-right"></th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {rekening.data.length === 0 ? (
                                        <tr>
                                            <td colSpan={7} className="px-4 py-12 text-center text-sm text-slate-400">
                                                Belum ada data rekening tabungan anggota.
                                            </td>
                                        </tr>
                                    ) : (
                                        rekening.data.map((r) => (
                                            <tr
                                                key={r.id}
                                                onClick={() => router.visit(route('tabungan.show', r.id))}
                                                className="hover:bg-[#F2FBF5] transition-colors cursor-pointer group"
                                            >
                                                <td className="px-4 py-2.5">
                                                    <div className="flex items-center gap-2.5">
                                                        <FotoAnggota fotoUrl={r.anggota?.foto_url} nama={r.anggota?.nama} ukuran="sm" />
                                                        <div className="min-w-0">
                                                            <p className="font-semibold text-slate-800 truncate">{r.anggota?.nama ?? '-'}</p>
                                                            <p className="text-xs text-slate-500 truncate">{r.anggota?.no_karyawan ?? '-'}</p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td className="px-4 py-2.5 font-mono text-xs font-semibold text-brand-navy whitespace-nowrap">
                                                    {r.no_rekening || '-'}
                                                </td>
                                                <td className="px-4 py-2.5 text-slate-600 whitespace-nowrap">{r.anggota?.cabang ?? '-'}</td>
                                                <td className="px-4 py-2.5 text-slate-600 whitespace-nowrap">{r.tanggal_buka || r.tanggal_pengajuan || '-'}</td>
                                                <td className="px-4 py-2.5 text-right font-bold text-slate-800 tabular-nums privacy-blur whitespace-nowrap">
                                                    {formatRupiah(r.saldo)}
                                                </td>
                                                <td className="px-4 py-2.5 whitespace-nowrap">
                                                    <StatusBadge status={r.status} />
                                                </td>
                                                <td className="px-4 py-2.5 text-right">
                                                    <Link
                                                        href={route('tabungan.show', r.id)}
                                                        onClick={(e) => e.stopPropagation()}
                                                        className="inline-flex items-center justify-center w-7 h-7 rounded-lg text-slate-400 group-hover:text-brand-navy"
                                                    >
                                                        <ChevronRight size={16} />
                                                    </Link>
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>

                        <div className="px-4 py-3 border-t border-slate-100">
                            <Pagination links={rekening.links} />
                        </div>
                    </Card>
                </>
            )}

            {bisaKelola && (
                <Drawer
                    show={showSetorTunai}
                    title="Input Setoran Tunai Tabungan"
                    description="Mencatat setoran tunai anggota langsung ke rekening tabungan dan buku kas."
                    onClose={() => setShowSetorTunai(false)}
                    layout="split"
                >
                    <form onSubmit={submitSetorTunai} className="flex-1 min-h-0 flex flex-col">
                        <DrawerBody>
                            <DrawerSection title="Pilih Rekening & Nominal" icon={ArrowDownCircle}>
                                <FormField label="Rekening Tabungan Anggota" error={setorTunaiForm.errors.rekening_tabungan_id} required>
                                    <Select
                                        data-autofocus={rekeningAktifOptions.length > 1 ? true : undefined}
                                        value={setorTunaiForm.data.rekening_tabungan_id}
                                        onChange={(e) => setorTunaiForm.setData('rekening_tabungan_id', e.target.value)}
                                        required
                                    >
                                        {rekeningAktifOptions.map((r) => (
                                            <option key={r.id} value={r.id}>
                                                {r.no_rekening} — {r.nama} ({r.no_karyawan})
                                            </option>
                                        ))}
                                    </Select>
                                </FormField>

                                <FormField label="Nominal Setor (Rp)" error={setorTunaiForm.errors.nominal || setorTunaiForm.errors.setor_tunai} hint={`Minimal ${formatRupiah(setting?.min_setor ?? 50000)}`} required>
                                    <CurrencyField
                                        data-autofocus={rekeningAktifOptions.length <= 1 ? true : undefined}
                                        value={setorTunaiForm.data.nominal}
                                        onChange={(e) => setorTunaiForm.setData('nominal', e.target.value)}
                                        placeholder="0"
                                        className="font-semibold"
                                        required
                                    />
                                    <ChipNominal grup="tabungan" nilai={setorTunaiForm.data.nominal} onPilih={(v) => setorTunaiForm.setData('nominal', v)} />
                                </FormField>

                                <FormField label="Masuk ke Akun Kas" error={setorTunaiForm.errors.akun_kas} required>
                                    <Select value={setorTunaiForm.data.akun_kas} onChange={(e) => setorTunaiForm.setData('akun_kas', e.target.value)}>
                                        <option value="kas_kecil">Kas Kecil (Tunai di Kantor)</option>
                                        <option value="bank">Bank Koperasi</option>
                                    </Select>
                                </FormField>

                                <FormField label="Tanggal Transaksi" error={setorTunaiForm.errors.tanggal} required>
                                    <TextField
                                        type="date"
                                        max={hariIni}
                                        value={setorTunaiForm.data.tanggal}
                                        onChange={(e) => setorTunaiForm.setData('tanggal', e.target.value)}
                                        required
                                    />
                                </FormField>

                                <FormField label="Keterangan (Opsional)" error={setorTunaiForm.errors.keterangan}>
                                    <TextField
                                        as="textarea"
                                        rows={2}
                                        value={setorTunaiForm.data.keterangan}
                                        onChange={(e) => setorTunaiForm.setData('keterangan', e.target.value)}
                                        placeholder="Catatan setoran tunai"
                                    />
                                </FormField>

                                <FormField label="Lampiran Kwitansi (Opsional)" error={setorTunaiForm.errors.bukti}>
                                    <input
                                        type="file"
                                        accept=".jpg,.jpeg,.png,.webp,.pdf"
                                        onChange={(e) => setorTunaiForm.setData('bukti', e.target.files?.[0] ?? null)}
                                        className="block w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-brand-navy/10 file:text-brand-navy"
                                    />
                                </FormField>
                            </DrawerSection>
                        </DrawerBody>

                        <DrawerFooter>
                            <DrawerFooterActions cols={2} stackOnMobile>
                                <Button type="button" variant="neutral" size="drawer" onClick={() => setShowSetorTunai(false)}>Batal</Button>
                                <Button type="submit" size="drawer" disabled={setorTunaiForm.processing}>
                                    {setorTunaiForm.processing ? 'Menyimpan...' : 'Catat Setoran Tunai'}
                                </Button>
                            </DrawerFooterActions>
                        </DrawerFooter>
                    </form>
                </Drawer>
            )}
        </AppLayout>
    );
}
