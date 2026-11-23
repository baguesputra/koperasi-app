import AnggotaLayout from '@/Layouts/AnggotaLayout';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import {
    ArrowLeft, ArrowDownCircle, ArrowUpCircle, CheckCircle2, Clock, Copy, Check,
    Eye, EyeOff, FileText, Info, Percent, ShieldCheck, Upload, Wallet, XCircle,
} from 'lucide-react';
import Button from '@/Components/ui/Button';
import Card from '@/Components/ui/Card';
import ChipNominal from '@/Components/ui/ChipNominal';
import Drawer from '@/Components/ui/Drawer';
import { DrawerBody, DrawerFooter, DrawerFooterActions, DrawerSection } from '@/Components/ui/DrawerSection';
import FormField from '@/Components/ui/FormField';
import Nominal, { usePrivasiNominal } from '@/Components/ui/Nominal';
import Pagination from '@/Components/ui/Pagination';
import Select from '@/Components/ui/Select';
import StatusBadge from '@/Components/ui/StatusBadge';
import TextField from '@/Components/ui/TextField';
import { formatRupiah } from '@/Utils/formatCurrency';
import { withIdempotencyKey } from '@/Utils/idempotency';

const jenisLabel = {
    setor: 'Setoran',
    tarik: 'Penarikan',
    bunga: 'Bunga Tabungan',
    koreksi_masuk: 'Koreksi Masuk',
    koreksi_keluar: 'Koreksi Keluar',
    tutup_rekening: 'Penutupan Rekening',
};

export default function Index({
    rekening,
    setting,
    syarat,
    estimasiBunga,
    ringkasanBunga,
    pengajuanList = [],
    riwayatTransaksi,
    riwayatBunga = [],
    rekeningAnggota = [],
    filters = {},
    hasPin = false,
}) {
    const { flash } = usePage().props;
    const [nominalTampil, alihNominal] = usePrivasiNominal();
    const [tab, setTab] = useState('mutasi');
    const [showSetorDrawer, setShowSetorDrawer] = useState(false);
    const [showTarikDrawer, setShowTarikDrawer] = useState(false);
    const [salinBerhasil, setSalinBerhasil] = useState('');

    const bukaForm = useForm({ setuju_syarat: false });
    const hariIni = new Date().toISOString().slice(0, 10);

    const setorForm = useForm({
        nominal: '',
        tanggal_transaksi: hariIni,
        bukti: null,
        catatan: '',
    });

    const tarikForm = useForm({
        tutup_rekening: false,
        nominal: '',
        kanal: rekeningAnggota.length > 0 ? 'transfer' : 'tunai',
        rekening_anggota_id: rekeningAnggota.find((r) => r.is_default)?.id ?? rekeningAnggota[0]?.id ?? '',
        catatan: '',
        pin: '',
    });

    function salinTeks(label, teks) {
        if (!teks || !navigator.clipboard) return;
        navigator.clipboard.writeText(teks);
        setSalinBerhasil(label);
        setTimeout(() => setSalinBerhasil(''), 1800);
    }

    function submitBuka(e) {
        e.preventDefault();
        bukaForm.post(route('portal.tabungan.buka'), withIdempotencyKey({ preserveScroll: true }));
    }

    function submitSetor(e) {
        e.preventDefault();
        setorForm.post(route('portal.tabungan.setor'), {
            ...withIdempotencyKey({
                forceFormData: true,
                preserveScroll: true,
                onSuccess: () => {
                    setorForm.reset('nominal', 'bukti', 'catatan');
                    setShowSetorDrawer(false);
                },
            }),
        });
    }

    function submitTarik(e) {
        e.preventDefault();
        tarikForm.post(
            route('portal.tabungan.tarik'),
            withIdempotencyKey({
                preserveScroll: true,
                onSuccess: () => {
                    tarikForm.reset('nominal', 'catatan', 'pin', 'tutup_rekening');
                    setShowTarikDrawer(false);
                },
            })
        );
    }

    function filterJenis(jenis) {
        router.get(route('portal.tabungan.index'), jenis ? { jenis } : {}, { preserveState: true, preserveScroll: true });
    }

    const eyeAction = (
        <button
            type="button"
            onClick={alihNominal}
            aria-label={nominalTampil ? 'Sembunyikan nominal' : 'Tampilkan nominal'}
            className="w-9 h-9 rounded-full flex items-center justify-center text-slate-500 hover:text-brand-navy active:scale-95 transition-all"
        >
            {nominalTampil ? <EyeOff size={18} /> : <Eye size={18} />}
        </button>
    );

    const statusRekening = rekening?.status ?? null;

    return (
        <AnggotaLayout mobileTitle="Tabungan Anggota" backHref={route('portal.dashboard')} mobileAction={eyeAction}>
            <Head title="Tabungan Anggota" />

            <Link href={route('portal.dashboard')} className="hidden sm:inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 hover:text-brand-navy mb-4">
                <ArrowLeft size={16} />
                Kembali ke Beranda
            </Link>

            <div className="mb-5 flex items-start justify-between gap-3">
                <div>
                    <h1 className="text-xl sm:text-2xl font-bold text-slate-800">Tabungan Anggota</h1>
                    <p className="text-sm text-slate-500 mt-1">
                        Simpanan sukarela berbunga {setting.suku_bunga_aktif}% / tahun ({setting.metode_bunga_label})
                    </p>
                </div>
                <button
                    type="button"
                    onClick={alihNominal}
                    aria-label={nominalTampil ? 'Sembunyikan nominal' : 'Tampilkan nominal'}
                    className="hidden sm:flex shrink-0 w-10 h-10 rounded-full border border-slate-200 text-slate-500 hover:text-brand-navy hover:bg-slate-50 items-center justify-center"
                >
                    {nominalTampil ? <EyeOff size={18} /> : <Eye size={18} />}
                </button>
            </div>

            {flash?.status && (
                <div className="mb-4 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm font-medium text-emerald-800 flex items-start gap-2.5">
                    <CheckCircle2 size={18} className="shrink-0 mt-0.5 text-brand-green-dark" />
                    <span>{flash.status}</span>
                </div>
            )}

            {/* STATE 1: BELUM PUNYA REKENING / DITOLAK / DITUTUP */}
            {(!statusRekening || statusRekening === 'ditolak' || statusRekening === 'ditutup') && (
                <div className="space-y-4">
                    {statusRekening === 'ditolak' && (
                        <div className="rounded-2xl bg-red-50 border border-red-200 p-4 flex items-start gap-3">
                            <XCircle size={20} className="text-red-600 shrink-0 mt-0.5" />
                            <div className="text-sm text-red-800">
                                <p className="font-bold">Pengajuan Pembukaan Sebelumnya Ditolak</p>
                                {rekening.catatan_pengurus && <p className="mt-1">Catatan Bendahara: {rekening.catatan_pengurus}</p>}
                                <p className="mt-1 text-xs text-red-700">Anda dapat mengajukan ulang pembukaan rekening di bawah ini.</p>
                            </div>
                        </div>
                    )}

                    {statusRekening === 'ditutup' && (
                        <div className="rounded-2xl bg-slate-50 border border-slate-200 p-4 flex items-start gap-3">
                            <Info size={20} className="text-slate-600 shrink-0 mt-0.5" />
                            <div className="text-sm text-slate-700">
                                <p className="font-bold">Rekening Sebelumnya ({rekening.no_rekening}) Telah Ditutup</p>
                                <p className="mt-0.5 text-xs text-slate-500">Ditutup pada {rekening.tanggal_tutup}. Anda dapat mengajukan pembukaan rekening baru kapan saja.</p>
                            </div>
                        </div>
                    )}

                    <div className="rounded-2xl bg-gradient-to-br from-[#0F1E36] via-[#13294B] to-[#0E3A24] text-white p-5 sm:p-6 shadow-lg">
                        <div className="flex items-center gap-2 text-emerald-300 text-xs font-bold uppercase tracking-wider mb-2">
                            <Percent size={14} />
                            <span>Program Tabungan Koperasi</span>
                        </div>
                        <h2 className="text-xl sm:text-2xl font-bold">Bunga {setting.suku_bunga_aktif}% per Tahun</h2>
                        <p className="text-sm text-slate-200 mt-1.5 max-w-2xl">
                            Dihitung dari saldo harian Anda dan otomatis dikreditkan ke saldo tabungan setiap akhir bulan.
                        </p>
                        <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-5">
                            <div className="rounded-xl bg-white/10 border border-white/10 px-3.5 py-2.5">
                                <p className="text-[11px] text-slate-300">Minimal Setor</p>
                                <p className="text-sm font-bold tabular-nums mt-0.5">{formatRupiah(setting.min_setor)}</p>
                            </div>
                            <div className="rounded-xl bg-white/10 border border-white/10 px-3.5 py-2.5">
                                <p className="text-[11px] text-slate-300">Saldo Minimal Mengendap</p>
                                <p className="text-sm font-bold tabular-nums mt-0.5">{formatRupiah(setting.saldo_minimal)}</p>
                            </div>
                            <div className="rounded-xl bg-white/10 border border-white/10 px-3.5 py-2.5">
                                <p className="text-[11px] text-slate-300">Minimal Penarikan</p>
                                <p className="text-sm font-bold tabular-nums mt-0.5">{formatRupiah(setting.min_tarik)}</p>
                            </div>
                        </div>
                    </div>

                    <Card>
                        <h3 className="text-base font-bold text-slate-800 mb-3 flex items-center gap-2">
                            <ShieldCheck size={18} className="text-brand-green-dark" />
                            Ketentuan Pembukaan Rekening ({syarat?.versi})
                        </h3>
                        <ul className="space-y-2.5 mb-5">
                            {(syarat?.poin ?? []).map((p, idx) => (
                                <li key={idx} className="rounded-xl bg-slate-50 border border-slate-100 p-3">
                                    <p className="text-sm font-semibold text-slate-800">{p.judul}</p>
                                    <p className="text-xs sm:text-sm text-slate-600 mt-0.5 leading-relaxed">{p.deskripsi}</p>
                                </li>
                            ))}
                        </ul>

                        {!setting.buka_rekening_aktif ? (
                            <div className="rounded-xl bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800">
                                Pembukaan rekening tabungan baru sedang ditutup sementara oleh pengurus koperasi.
                            </div>
                        ) : (
                            <form onSubmit={submitBuka} className="space-y-4">
                                <label className="flex items-start gap-3 cursor-pointer rounded-xl border border-slate-200 p-3.5 hover:bg-slate-50 transition-colors">
                                    <input
                                        type="checkbox"
                                        checked={bukaForm.data.setuju_syarat}
                                        onChange={(e) => bukaForm.setData('setuju_syarat', e.target.checked)}
                                        className="mt-0.5 w-4 h-4 rounded border-slate-300 text-brand-green focus:ring-brand-green"
                                    />
                                    <span className="text-sm text-slate-700 leading-relaxed">
                                        Saya telah membaca dan menyetujui seluruh ketentuan Tabungan Anggota Koperasi Karya Mandiri.
                                    </span>
                                </label>
                                {(bukaForm.errors.setuju_syarat || bukaForm.errors.buka) && (
                                    <p className="text-xs font-medium text-red-600">{bukaForm.errors.setuju_syarat || bukaForm.errors.buka}</p>
                                )}
                                <Button type="submit" disabled={bukaForm.processing || !bukaForm.data.setuju_syarat} className="w-full sm:w-auto">
                                    {bukaForm.processing ? 'Mengirim Pengajuan...' : 'Ajukan Pembukaan Tabungan'}
                                </Button>
                            </form>
                        )}
                    </Card>
                </div>
            )}

            {/* STATE 2: PENGAJUAN SEDANG DIPROSES */}
            {statusRekening === 'diajukan' && (
                <Card className="border-amber-200/80">
                    <div className="flex items-start gap-3.5">
                        <span className="w-11 h-11 rounded-2xl bg-amber-50 text-amber-700 flex items-center justify-center shrink-0">
                            <Clock size={22} />
                        </span>
                        <div>
                            <div className="flex items-center gap-2 flex-wrap">
                                <h2 className="text-base font-bold text-slate-800">Pengajuan Pembukaan Rekening Diproses</h2>
                                <StatusBadge status="diajukan" />
                            </div>
                            <p className="text-sm text-slate-600 mt-1">
                                Pengajuan Anda tanggal <strong>{rekening.tanggal_pengajuan}</strong> sedang menunggu persetujuan Bendahara Koperasi.
                                Setelah disetujui, nomor rekening akan terbit otomatis dan Anda dapat langsung melakukan setoran.
                            </p>
                        </div>
                    </div>
                </Card>
            )}

            {/* STATE 3: REKENING AKTIF */}
            {statusRekening === 'aktif' && (
                <div className="space-y-5">
                    {/* Hero Card Rekening */}
                    <div className="rounded-2xl bg-gradient-to-br from-[#0F1E36] via-[#13294B] to-[#0E3A24] text-white p-5 sm:p-6 shadow-lg">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <div className="flex items-center gap-2 text-xs text-emerald-300 font-semibold">
                                    <Wallet size={14} />
                                    <span>Rekening Tabungan Aktif</span>
                                </div>
                                <div className="flex items-center gap-2 mt-1">
                                    <span className="font-mono text-base sm:text-lg font-bold tracking-wide">{rekening.no_rekening}</span>
                                    <button
                                        type="button"
                                        onClick={() => salinTeks('rek', rekening.no_rekening)}
                                        className="p-1.5 rounded-lg bg-white/10 hover:bg-white/20 transition-colors"
                                        title="Salin nomor rekening"
                                    >
                                        {salinBerhasil === 'rek' ? <Check size={14} className="text-emerald-300" /> : <Copy size={14} />}
                                    </button>
                                </div>
                            </div>
                            <span className="px-3 py-1 rounded-full bg-white/10 border border-white/15 text-xs font-semibold tabular-nums">
                                Bunga Aktif: {setting.suku_bunga_aktif}% / tahun
                            </span>
                        </div>

                        <div className="mt-5">
                            <p className="text-xs text-slate-300">Saldo Tabungan Saat Ini</p>
                            <p className="text-3xl sm:text-4xl font-bold tabular-nums mt-0.5">
                                <Nominal nilai={rekening.saldo} tampil={nominalTampil} />
                            </p>
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-5 pt-4 border-t border-white/10">
                            <div>
                                <p className="text-[11px] text-slate-300">Estimasi Bunga Bulan Ini</p>
                                <p className="text-sm font-bold text-emerald-300 tabular-nums mt-0.5">
                                    +<Nominal nilai={estimasiBunga?.bunga_bersih ?? 0} tampil={nominalTampil} />
                                    {estimasiBunga?.sudah_diposting ? ' (Sudah masuk)' : ''}
                                </p>
                            </div>
                            <div>
                                <p className="text-[11px] text-slate-300">Bunga Didapat Tahun Ini</p>
                                <p className="text-sm font-bold tabular-nums mt-0.5">
                                    <Nominal nilai={ringkasanBunga?.tahun_ini ?? 0} tampil={nominalTampil} />
                                </p>
                            </div>
                            <div>
                                <p className="text-[11px] text-slate-300">Total Bunga Kumulatif</p>
                                <p className="text-sm font-bold tabular-nums mt-0.5">
                                    <Nominal nilai={ringkasanBunga?.kumulatif ?? 0} tampil={nominalTampil} />
                                </p>
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-3 mt-5">
                            <button
                                type="button"
                                onClick={() => setShowSetorDrawer(true)}
                                className="min-h-[44px] rounded-xl bg-brand-green text-white font-semibold text-sm flex items-center justify-center gap-2 hover:bg-brand-green-dark transition-colors shadow-sm"
                            >
                                <ArrowDownCircle size={17} />
                                Setor Tabungan
                            </button>
                            <button
                                type="button"
                                onClick={() => setShowTarikDrawer(true)}
                                className="min-h-[44px] rounded-xl bg-white/15 border border-white/20 text-white font-semibold text-sm flex items-center justify-center gap-2 hover:bg-white/25 transition-colors"
                            >
                                <ArrowUpCircle size={17} />
                                Tarik Tabungan
                            </button>
                        </div>
                    </div>

                    {/* Info Rekening Setoran Koperasi */}
                    <Card padding="sm" className="bg-slate-50/70">
                        <div className="flex flex-wrap items-center justify-between gap-2 text-xs sm:text-sm">
                            <div>
                                <span className="text-slate-500">Rekening Tujuan Setor Transfer: </span>
                                <strong className="text-slate-800">{setting.bank_tujuan_nama}</strong>
                                <span className="mx-1.5 text-slate-300">&bull;</span>
                                <span className="font-mono font-bold text-brand-navy">{setting.bank_tujuan_rekening}</span>
                                <span className="mx-1.5 text-slate-300">&bull;</span>
                                <span className="text-slate-700">a.n. {setting.bank_tujuan_atas_nama}</span>
                            </div>
                            <button
                                type="button"
                                onClick={() => salinTeks('tujuan', setting.bank_tujuan_rekening)}
                                className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white border border-slate-200 text-xs font-semibold text-brand-navy hover:bg-slate-100"
                            >
                                {salinBerhasil === 'tujuan' ? <Check size={13} className="text-brand-green-dark" /> : <Copy size={13} />}
                                {salinBerhasil === 'tujuan' ? 'Tersalin' : 'Salin No. Rek'}
                            </button>
                        </div>
                    </Card>

                    {/* Antrean Pengajuan Menunggu / Ditolak Terbaru */}
                    {pengajuanList.length > 0 && (
                        <Card padding="none">
                            <div className="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
                                <h2 className="text-sm font-bold text-slate-800">Status Pengajuan Setor &amp; Tarik</h2>
                            </div>
                            <ul className="divide-y divide-slate-100">
                                {pengajuanList.map((p) => (
                                    <li key={p.id} className="px-4 py-3 flex items-center justify-between gap-3">
                                        <div className="min-w-0">
                                            <div className="flex items-center gap-2 flex-wrap">
                                                <span className="text-sm font-semibold text-slate-800">
                                                    {p.jenis === 'setor' ? 'Setor Transfer' : p.jenis === 'tutup_rekening' ? 'Tutup Rekening' : 'Penarikan'}
                                                </span>
                                                <StatusBadge status={p.status} />
                                            </div>
                                            <p className="text-xs text-slate-500 mt-0.5">
                                                Tgl {p.tanggal_transaksi} &bull; Diajukan {p.created_at}
                                            </p>
                                            {p.status === 'ditolak' && p.catatan_pengurus && (
                                                <p className="text-xs text-red-600 mt-1 font-medium">Alasan: {p.catatan_pengurus}</p>
                                            )}
                                        </div>
                                        <div className="text-right shrink-0">
                                            <p className="text-sm font-bold text-slate-800 tabular-nums">
                                                <Nominal nilai={p.nominal} tampil={nominalTampil} />
                                            </p>
                                            {p.bukti_url && (
                                                <a href={p.bukti_url} target="_blank" rel="noreferrer" className="text-xs font-semibold text-brand-navy hover:underline">
                                                    Lihat bukti
                                                </a>
                                            )}
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        </Card>
                    )}

                    {/* Tab Riwayat */}
                    <div className="flex items-center gap-1 bg-slate-100 p-1 rounded-xl w-full sm:w-fit">
                        <button
                            type="button"
                            onClick={() => setTab('mutasi')}
                            className={`flex-1 sm:flex-none px-4 min-h-[40px] text-sm font-semibold rounded-lg transition-colors ${
                                tab === 'mutasi' ? 'bg-white text-slate-800 shadow-xs' : 'text-slate-500'
                            }`}
                        >
                            Riwayat Transaksi
                        </button>
                        <button
                            type="button"
                            onClick={() => setTab('bunga')}
                            className={`flex-1 sm:flex-none px-4 min-h-[40px] text-sm font-semibold rounded-lg transition-colors ${
                                tab === 'bunga' ? 'bg-white text-slate-800 shadow-xs' : 'text-slate-500'
                            }`}
                        >
                            Riwayat Bunga ({riwayatBunga.length})
                        </button>
                    </div>

                    {tab === 'mutasi' && (
                        <Card padding="none">
                            <div className="px-4 py-3 border-b border-slate-100 flex flex-wrap items-center justify-between gap-2">
                                <h2 className="text-sm font-bold text-slate-800">Buku Tabungan</h2>
                                <select
                                    value={filters.jenis ?? ''}
                                    onChange={(e) => filterJenis(e.target.value)}
                                    className="px-3 py-1.5 text-xs font-semibold rounded-lg border border-slate-200 bg-white text-slate-700"
                                >
                                    <option value="">Semua Jenis</option>
                                    <option value="setor">Setoran</option>
                                    <option value="tarik">Penarikan</option>
                                    <option value="bunga">Bunga Tabungan</option>
                                </select>
                            </div>

                            {(!riwayatTransaksi || riwayatTransaksi.data.length === 0) ? (
                                <p className="p-8 text-center text-sm text-slate-400">Belum ada transaksi tabungan.</p>
                            ) : (
                                <>
                                    <ul className="divide-y divide-slate-100">
                                        {riwayatTransaksi.data.map((t) => (
                                            <li key={t.id} className="px-4 py-3 flex items-center justify-between gap-3">
                                                <div className="flex items-start gap-3 min-w-0">
                                                    <span className={`w-9 h-9 rounded-xl flex items-center justify-center shrink-0 mt-0.5 ${t.is_kredit ? 'bg-brand-green-light text-brand-green-dark' : 'bg-rose-50 text-rose-600'}`}>
                                                        {t.is_kredit ? <ArrowDownCircle size={17} /> : <ArrowUpCircle size={17} />}
                                                    </span>
                                                    <div className="min-w-0">
                                                        <div className="flex items-center gap-2 flex-wrap">
                                                            <p className="text-sm font-semibold text-slate-800">{jenisLabel[t.jenis] ?? t.jenis}</p>
                                                            <span className="text-[11px] font-medium px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 capitalize">{t.kanal}</span>
                                                            {t.dibatalkan_pada && <span className="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-red-50 text-red-600">Dibatalkan</span>}
                                                        </div>
                                                        <p className="text-xs text-slate-500 mt-0.5 truncate">{t.keterangan}</p>
                                                        <p className="text-[11px] text-slate-400 mt-0.5">
                                                            {t.no_bukti} &bull; {t.tanggal}
                                                            {t.suku_bunga_snapshot != null && ` • Tarif ${t.suku_bunga_snapshot}%`}
                                                        </p>
                                                    </div>
                                                </div>
                                                <div className="text-right shrink-0">
                                                    <p className={`text-sm font-bold tabular-nums ${t.is_kredit ? 'text-brand-green-dark' : 'text-rose-600'}`}>
                                                        {t.is_kredit ? '+' : '−'}<Nominal nilai={t.nominal} tampil={nominalTampil} />
                                                    </p>
                                                    <p className="text-[11px] text-slate-500 tabular-nums mt-0.5">
                                                        Saldo <Nominal nilai={t.saldo_sesudah} tampil={nominalTampil} />
                                                    </p>
                                                    {t.bukti_url && (
                                                        <a href={t.bukti_url} target="_blank" rel="noreferrer" className="text-[11px] font-semibold text-brand-navy hover:underline inline-flex items-center gap-1 mt-0.5">
                                                            <FileText size={11} /> Bukti
                                                        </a>
                                                    )}
                                                </div>
                                            </li>
                                        ))}
                                    </ul>
                                    <div className="px-4 py-3 border-t border-slate-100">
                                        <Pagination links={riwayatTransaksi.links} />
                                    </div>
                                </>
                            )}
                        </Card>
                    )}

                    {tab === 'bunga' && (
                        <Card padding="none">
                            <div className="px-4 py-3 border-b border-slate-100">
                                <h2 className="text-sm font-bold text-slate-800">Rincian Bunga Bulanan (Snapshot Historis)</h2>
                            </div>
                            {riwayatBunga.length === 0 ? (
                                <p className="p-8 text-center text-sm text-slate-400">Belum ada bunga yang dibukukan. Bunga dikreditkan setiap akhir bulan.</p>
                            ) : (
                                <ul className="divide-y divide-slate-100">
                                    {riwayatBunga.map((b) => (
                                        <li key={b.id} className="px-4 py-3.5">
                                            <div className="flex items-center justify-between gap-3">
                                                <div>
                                                    <p className="text-sm font-bold text-slate-800">Periode {b.periode}</p>
                                                    <p className="text-xs text-slate-500 mt-0.5">
                                                        Tarif snapshot {b.suku_bunga_snapshot}% / thn &bull; {b.jumlah_hari} hari &bull; Saldo dasar <Nominal nilai={b.saldo_dasar} tampil={nominalTampil} />
                                                    </p>
                                                </div>
                                                <p className="text-sm font-bold text-brand-green-dark tabular-nums">
                                                    +<Nominal nilai={b.bunga_bersih} tampil={nominalTampil} />
                                                </p>
                                            </div>
                                            {b.rincian_tarif?.length > 1 && (
                                                <div className="mt-2 rounded-lg bg-slate-50 border border-slate-100 p-2 text-[11px] text-slate-600 space-y-0.5">
                                                    {b.rincian_tarif.map((seg, idx) => (
                                                        <p key={idx}>
                                                            {seg.dari} s/d {seg.sampai} ({seg.hari} hari): tarif {seg.persentase}% → {formatRupiah(seg.bunga)}
                                                        </p>
                                                    ))}
                                                </div>
                                            )}
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </Card>
                    )}
                </div>
            )}

            {/* DRAWER SETOR TABUNGAN */}
            <Drawer
                show={showSetorDrawer}
                title="Setor Tabungan via Transfer"
                description="Transfer ke rekening koperasi lalu unggah bukti transfer."
                onClose={() => setShowSetorDrawer(false)}
                layout="split"
            >
                <form onSubmit={submitSetor} className="flex-1 min-h-0 flex flex-col">
                    <DrawerBody>
                        <DrawerSection title="Rekening Tujuan Koperasi" icon={Wallet} tone="emerald">
                            <div className="flex items-center justify-between gap-2 text-sm">
                                <div>
                                    <p className="font-bold text-slate-800">{setting.bank_tujuan_nama}</p>
                                    <p className="font-mono text-base font-bold text-brand-navy mt-0.5">{setting.bank_tujuan_rekening}</p>
                                    <p className="text-xs text-slate-500">a.n. {setting.bank_tujuan_atas_nama}</p>
                                </div>
                                <Button type="button" size="sm" variant="neutral" onClick={() => salinTeks('drawer', setting.bank_tujuan_rekening)}>
                                    {salinBerhasil === 'drawer' ? <Check size={14} /> : <Copy size={14} />}
                                    {salinBerhasil === 'drawer' ? 'Disalin' : 'Salin'}
                                </Button>
                            </div>
                        </DrawerSection>

                        <DrawerSection title="Detail Setoran" icon={Upload}>
                            <FormField label="Nominal Setor (Rp)" error={setorForm.errors.nominal || setorForm.errors.setor} hint={`Minimal ${formatRupiah(setting.min_setor)}`} required>
                                <TextField
                                    type="number"
                                    min={setting.min_setor}
                                    value={setorForm.data.nominal}
                                    onChange={(e) => setorForm.setData('nominal', e.target.value)}
                                    placeholder="Contoh: 250000"
                                    className="tabular-nums"
                                    required
                                />
                                <ChipNominal grup="tabungan" nilai={setorForm.data.nominal} onPilih={(v) => setorForm.setData('nominal', v)} />
                            </FormField>

                            <FormField label="Tanggal Transfer" error={setorForm.errors.tanggal_transaksi} required>
                                <TextField
                                    type="date"
                                    max={hariIni}
                                    value={setorForm.data.tanggal_transaksi}
                                    onChange={(e) => setorForm.setData('tanggal_transaksi', e.target.value)}
                                    required
                                />
                            </FormField>

                            <FormField label="Bukti Transfer (JPG/PNG/WEBP/PDF, maks 5 MB)" error={setorForm.errors.bukti} required>
                                <input
                                    type="file"
                                    accept=".jpg,.jpeg,.png,.webp,.pdf"
                                    onChange={(e) => setorForm.setData('bukti', e.target.files?.[0] ?? null)}
                                    className="block w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-brand-navy/10 file:text-brand-navy hover:file:bg-brand-navy/15"
                                    required
                                />
                            </FormField>

                            <FormField label="Catatan (Opsional)" error={setorForm.errors.catatan}>
                                <TextField
                                    as="textarea"
                                    rows={2}
                                    value={setorForm.data.catatan}
                                    onChange={(e) => setorForm.setData('catatan', e.target.value)}
                                    placeholder="Mis. Transfer dari BCA a.n. Budi"
                                />
                            </FormField>
                        </DrawerSection>
                    </DrawerBody>

                    <DrawerFooter>
                        <DrawerFooterActions cols={2} stackOnMobile>
                            <Button type="button" variant="neutral" size="drawer" onClick={() => setShowSetorDrawer(false)}>
                                Batal
                            </Button>
                            <Button type="submit" size="drawer" disabled={setorForm.processing}>
                                {setorForm.processing ? 'Mengirim...' : 'Kirim Bukti Setor'}
                            </Button>
                        </DrawerFooterActions>
                    </DrawerFooter>
                </form>
            </Drawer>

            {/* DRAWER TARIK TABUNGAN */}
            <Drawer
                show={showTarikDrawer}
                title="Ajukan Penarikan Tabungan"
                description={`Saldo saat ini: ${formatRupiah(rekening?.saldo ?? 0)}`}
                onClose={() => setShowTarikDrawer(false)}
                layout="split"
            >
                <form onSubmit={submitTarik} className="flex-1 min-h-0 flex flex-col">
                    <DrawerBody>
                        <DrawerSection title="Pilihan Penarikan" icon={ArrowUpCircle}>
                            <label className="flex items-start gap-2.5 cursor-pointer rounded-xl border border-slate-200 p-3 mb-3 hover:bg-slate-50">
                                <input
                                    type="checkbox"
                                    checked={tarikForm.data.tutup_rekening}
                                    onChange={(e) => tarikForm.setData('tutup_rekening', e.target.checked)}
                                    className="mt-0.5 w-4 h-4 rounded border-slate-300 text-rose-600 focus:ring-rose-500"
                                />
                                <span className="text-xs sm:text-sm text-slate-700">
                                    <strong>Tutup Rekening</strong> — tarik seluruh saldo beserta bunga berjalan pro-rata hingga hari pencairan.
                                </span>
                            </label>

                            {!tarikForm.data.tutup_rekening && (
                                <FormField
                                    label="Nominal Penarikan (Rp)"
                                    error={tarikForm.errors.nominal || tarikForm.errors.tarik}
                                    hint={`Min. tarik ${formatRupiah(setting.min_tarik)} • Sisa saldo minimal ${formatRupiah(setting.saldo_minimal)}`}
                                    required
                                >
                                    <TextField
                                        type="number"
                                        min={setting.min_tarik}
                                        value={tarikForm.data.nominal}
                                        onChange={(e) => tarikForm.setData('nominal', e.target.value)}
                                        placeholder="Contoh: 100000"
                                        className="tabular-nums"
                                        required
                                    />
                                    <ChipNominal grup="tabungan" nilai={tarikForm.data.nominal} onPilih={(v) => tarikForm.setData('nominal', v)} />
                                </FormField>
                            )}

                            <FormField label="Kanal Pencairan" error={tarikForm.errors.kanal} required>
                                <Select value={tarikForm.data.kanal} onChange={(e) => tarikForm.setData('kanal', e.target.value)}>
                                    <option value="transfer">Transfer ke Rekening Bank Saya</option>
                                    <option value="tunai">Ambil Tunai di Koperasi</option>
                                </Select>
                            </FormField>

                            {tarikForm.data.kanal === 'transfer' && (
                                <FormField
                                    label="Rekening Bank Tujuan"
                                    error={tarikForm.errors.rekening_anggota_id}
                                    hint={rekeningAnggota.length === 0 ? 'Belum ada rekening tersimpan. Tambahkan dulu di menu Profil.' : undefined}
                                    required
                                >
                                    {rekeningAnggota.length > 0 ? (
                                        <Select
                                            value={tarikForm.data.rekening_anggota_id}
                                            onChange={(e) => tarikForm.setData('rekening_anggota_id', e.target.value)}
                                            required
                                        >
                                            {rekeningAnggota.map((r) => (
                                                <option key={r.id} value={r.id}>
                                                    {r.nama_bank} • {r.no_rekening} (a.n. {r.atas_nama})
                                                </option>
                                            ))}
                                        </Select>
                                    ) : (
                                        <Link href={route('portal.profil')} className="inline-flex text-xs font-semibold text-brand-navy underline">
                                            Tambah Rekening di Profil Saya →
                                        </Link>
                                    )}
                                </FormField>
                            )}

                            <FormField label="Catatan (Opsional)" error={tarikForm.errors.catatan}>
                                <TextField
                                    as="textarea"
                                    rows={2}
                                    value={tarikForm.data.catatan}
                                    onChange={(e) => tarikForm.setData('catatan', e.target.value)}
                                    placeholder="Keperluan atau catatan tambahan"
                                />
                            </FormField>

                            {hasPin && (
                                <FormField label="PIN Keamanan (6 Digit)" error={tarikForm.errors.pin} required>
                                    <TextField
                                        type="password"
                                        inputMode="numeric"
                                        maxLength={6}
                                        value={tarikForm.data.pin}
                                        onChange={(e) => tarikForm.setData('pin', e.target.value.replace(/\D/g, '').slice(0, 6))}
                                        placeholder="••••••"
                                        className="font-mono tracking-widest"
                                        required
                                    />
                                </FormField>
                            )}
                        </DrawerSection>
                    </DrawerBody>

                    <DrawerFooter>
                        <DrawerFooterActions cols={2} stackOnMobile>
                            <Button type="button" variant="neutral" size="drawer" onClick={() => setShowTarikDrawer(false)}>
                                Batal
                            </Button>
                            <Button
                                type="submit"
                                variant={tarikForm.data.tutup_rekening ? 'danger' : 'primary'}
                                size="drawer"
                                disabled={tarikForm.processing || (tarikForm.data.kanal === 'transfer' && rekeningAnggota.length === 0)}
                            >
                                {tarikForm.processing ? 'Mengirim...' : tarikForm.data.tutup_rekening ? 'Ajukan Tutup Rekening' : 'Ajukan Penarikan'}
                            </Button>
                        </DrawerFooterActions>
                    </DrawerFooter>
                </form>
            </Drawer>
        </AnggotaLayout>
    );
}
