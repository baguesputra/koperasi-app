import AppLayout from '@/Layouts/AppLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import {
    ArrowDownCircle, ArrowUpCircle, CheckCircle2, ExternalLink, FileText,
    Landmark, Percent, Plus, UserCheck, Wallet, XCircle,
} from 'lucide-react';
import Button from '@/Components/ui/Button';
import Card from '@/Components/ui/Card';
import ChipNominal from '@/Components/ui/ChipNominal';
import CountBadge from '@/Components/ui/CountBadge';
import Drawer from '@/Components/ui/Drawer';
import {
    DrawerBody,
    DrawerField,
    DrawerFieldGrid,
    DrawerFooter,
    DrawerFooterActions,
    DrawerKeyValue,
    DrawerSection,
} from '@/Components/ui/DrawerSection';
import FormField from '@/Components/ui/FormField';
import FotoAnggota from '@/Components/ui/FotoAnggota';
import PageHeader from '@/Components/ui/PageHeader';
import Select from '@/Components/ui/Select';
import StatusBadge from '@/Components/ui/StatusBadge';
import TextField from '@/Components/ui/TextField';
import { formatRupiah } from '@/Utils/formatCurrency';
import { withIdempotencyKey } from '@/Utils/idempotency';

export default function Index({
    tabAktif = 'setor',
    periodeBunga,
    antreanBuka = [],
    antreanSetor = [],
    antreanTarik = [],
    previewBunga = null,
    rekeningAktifOptions = [],
    kasFisik = { saldo_bank: 0, saldo_kas_kecil: 0 },
    setting = { min_setor: 50000, saldo_minimal: 50000, suku_bunga_aktif: 2 },
    counts = { buka: 0, setor: 0, tarik: 0 },
}) {
    const [bukaDipilih, setBukaDipilih] = useState(null);
    const [pengajuanDipilih, setPengajuanDipilih] = useState(null);
    const [showTolakMode, setShowTolakMode] = useState(false);
    const [showSetorTunai, setShowSetorTunai] = useState(false);

    const hariIni = new Date().toISOString().slice(0, 10);

    const keputusanForm = useForm({
        catatan: '',
        akun_kas: 'bank',
        bukti_pencairan: null,
    });

    const setorTunaiForm = useForm({
        rekening_tabungan_id: rekeningAktifOptions[0]?.id ?? '',
        nominal: '',
        akun_kas: 'kas_kecil',
        tanggal: hariIni,
        keterangan: '',
        bukti: null,
    });

    const postingBungaForm = useForm({ periode: periodeBunga });

    function pindahTab(tab) {
        router.get(route('bendahara.tabungan.index'), { tab, periode: periodeBunga }, { preserveState: false, replace: true });
    }

    function ubahPeriodeBunga(periode) {
        router.get(route('bendahara.tabungan.index'), { tab: 'bunga', periode }, { preserveState: false, replace: true });
    }

    function tutupDrawers() {
        setBukaDipilih(null);
        setPengajuanDipilih(null);
        setShowTolakMode(false);
        keputusanForm.reset('catatan', 'bukti_pencairan');
        keputusanForm.clearErrors();
    }

    function setujuiBuka() {
        if (!bukaDipilih) return;
        keputusanForm.post(
            route('bendahara.tabungan.buka.setujui', bukaDipilih.id),
            withIdempotencyKey({ preserveScroll: true, onSuccess: tutupDrawers })
        );
    }

    function tolakBuka(e) {
        e.preventDefault();
        if (!bukaDipilih) return;
        keputusanForm.post(
            route('bendahara.tabungan.buka.tolak', bukaDipilih.id),
            withIdempotencyKey({ preserveScroll: true, onSuccess: tutupDrawers })
        );
    }

    function setujuiPengajuan(e) {
        e.preventDefault();
        if (!pengajuanDipilih) return;
        keputusanForm.post(route('bendahara.tabungan.pengajuan.setujui', pengajuanDipilih.id), {
            ...withIdempotencyKey({
                forceFormData: true,
                preserveScroll: true,
                onSuccess: tutupDrawers,
            }),
        });
    }

    function tolakPengajuan(e) {
        e.preventDefault();
        if (!pengajuanDipilih) return;
        keputusanForm.post(
            route('bendahara.tabungan.pengajuan.tolak', pengajuanDipilih.id),
            withIdempotencyKey({ preserveScroll: true, onSuccess: tutupDrawers })
        );
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

    const tabs = [
        { key: 'setor', label: 'Verifikasi Setoran', count: counts.setor },
        { key: 'tarik', label: 'Pengajuan Penarikan', count: counts.tarik },
        { key: 'buka', label: 'Buka Rekening', count: counts.buka },
        { key: 'bunga', label: 'Posting Bunga Bulanan', count: 0 },
    ];

    return (
        <AppLayout>
            <Head title="Proses Tabungan Anggota" />

            <PageHeader
                title="Proses Tabungan Anggota"
                subtitle="Verifikasi pembukaan rekening, setoran bukti transfer, penarikan, setor tunai, dan posting bunga bulanan"
                actions={
                    <Button size="sm" onClick={() => setShowSetorTunai(true)} disabled={rekeningAktifOptions.length === 0}>
                        <Plus size={15} /> Setor Tunai
                    </Button>
                }
            />

            {/* Ringkasan Kas Fisik */}
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
                <Card padding="sm">
                    <div className="flex items-center justify-between">
                        <div>
                            <p className="text-xs font-semibold text-slate-500">Saldo Bank Koperasi</p>
                            <p className="text-lg font-bold text-slate-800 tabular-nums privacy-blur mt-0.5">{formatRupiah(kasFisik.saldo_bank)}</p>
                        </div>
                        <span className="w-9 h-9 rounded-xl bg-brand-navy/5 text-brand-navy flex items-center justify-center">
                            <Landmark size={18} />
                        </span>
                    </div>
                </Card>
                <Card padding="sm">
                    <div className="flex items-center justify-between">
                        <div>
                            <p className="text-xs font-semibold text-slate-500">Saldo Kas Kecil</p>
                            <p className="text-lg font-bold text-slate-800 tabular-nums privacy-blur mt-0.5">{formatRupiah(kasFisik.saldo_kas_kecil)}</p>
                        </div>
                        <span className="w-9 h-9 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center">
                            <Wallet size={18} />
                        </span>
                    </div>
                </Card>
            </div>

            {/* Tab Navigation */}
            <div className="flex flex-wrap gap-1.5 mb-4 bg-slate-100/90 border border-slate-200/70 p-1 rounded-xl w-full sm:w-fit">
                {tabs.map((t) => {
                    const aktif = tabAktif === t.key;
                    return (
                        <button
                            key={t.key}
                            type="button"
                            onClick={() => pindahTab(t.key)}
                            className={`flex items-center gap-2 px-3.5 py-2 text-xs sm:text-sm font-semibold rounded-lg transition-all ${
                                aktif
                                    ? 'bg-gradient-to-r from-brand-navy to-brand-navy-light text-white shadow-xs'
                                    : 'text-slate-600 hover:bg-white/80 hover:text-brand-navy'
                            }`}
                        >
                            <span>{t.label}</span>
                            {t.count > 0 && <CountBadge count={t.count} active={aktif} />}
                        </button>
                    );
                })}
            </div>

            {/* TAB 1: VERIFIKASI SETORAN */}
            {tabAktif === 'setor' && (
                <Card padding="none" className="overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left border-collapse text-sm">
                            <thead>
                                <tr className="bg-[#F8FAFC] border-b border-slate-200/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                    <th className="px-4 py-3">Anggota</th>
                                    <th className="px-4 py-3">No. Rekening</th>
                                    <th className="px-4 py-3">Tgl Transfer</th>
                                    <th className="px-4 py-3 text-right">Nominal</th>
                                    <th className="px-4 py-3">Status</th>
                                    <th className="px-4 py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {antreanSetor.length === 0 ? (
                                    <tr>
                                        <td colSpan={6} className="px-4 py-12 text-center text-slate-400">
                                            Tidak ada setoran tabungan yang menunggu verifikasi.
                                        </td>
                                    </tr>
                                ) : (
                                    antreanSetor.map((p) => (
                                        <tr
                                            key={p.id}
                                            onClick={() => { setPengajuanDipilih(p); setShowTolakMode(false); }}
                                            className="hover:bg-[#F2FBF5] transition-colors cursor-pointer"
                                        >
                                            <td className="px-4 py-2.5">
                                                <div className="flex items-center gap-2.5">
                                                    <FotoAnggota src={p.anggota?.foto_url} nama={p.anggota?.nama} size="sm" />
                                                    <div>
                                                        <p className="font-semibold text-slate-800">{p.anggota?.nama}</p>
                                                        <p className="text-xs text-slate-500">{p.anggota?.no_karyawan}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-4 py-2.5 font-mono text-xs font-semibold text-brand-navy">{p.no_rekening}</td>
                                            <td className="px-4 py-2.5 text-slate-600">{p.tanggal_transaksi}</td>
                                            <td className="px-4 py-2.5 text-right font-bold text-brand-green-dark tabular-nums privacy-blur">
                                                +{formatRupiah(p.nominal)}
                                            </td>
                                            <td className="px-4 py-2.5"><StatusBadge status={p.status} /></td>
                                            <td className="px-4 py-2.5 text-right">
                                                <Button size="sm" variant="neutral" onClick={(e) => { e.stopPropagation(); setPengajuanDipilih(p); setShowTolakMode(false); }}>
                                                    Periksa Bukti
                                                </Button>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </Card>
            )}

            {/* TAB 2: PENGAJUAN PENARIKAN */}
            {tabAktif === 'tarik' && (
                <Card padding="none" className="overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left border-collapse text-sm">
                            <thead>
                                <tr className="bg-[#F8FAFC] border-b border-slate-200/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                    <th className="px-4 py-3">Anggota</th>
                                    <th className="px-4 py-3">Jenis &amp; Kanal</th>
                                    <th className="px-4 py-3 text-right">Saldo Rek</th>
                                    <th className="px-4 py-3 text-right">Nominal Tarik</th>
                                    <th className="px-4 py-3">Status</th>
                                    <th className="px-4 py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {antreanTarik.length === 0 ? (
                                    <tr>
                                        <td colSpan={6} className="px-4 py-12 text-center text-slate-400">
                                            Tidak ada pengajuan penarikan yang menunggu pencairan.
                                        </td>
                                    </tr>
                                ) : (
                                    antreanTarik.map((p) => (
                                        <tr
                                            key={p.id}
                                            onClick={() => {
                                                setPengajuanDipilih(p);
                                                setShowTolakMode(false);
                                                keputusanForm.setData('akun_kas', p.kanal === 'tunai' ? 'kas_kecil' : 'bank');
                                            }}
                                            className="hover:bg-[#F2FBF5] transition-colors cursor-pointer"
                                        >
                                            <td className="px-4 py-2.5">
                                                <div className="flex items-center gap-2.5">
                                                    <FotoAnggota src={p.anggota?.foto_url} nama={p.anggota?.nama} size="sm" />
                                                    <div>
                                                        <p className="font-semibold text-slate-800">{p.anggota?.nama}</p>
                                                        <p className="text-xs text-slate-500">{p.no_rekening}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-4 py-2.5">
                                                <p className="font-semibold text-slate-800">
                                                    {p.jenis === 'tutup_rekening' ? 'Tutup Rekening' : 'Penarikan Saldo'}
                                                </p>
                                                <p className="text-xs text-slate-500 capitalize">Via {p.kanal}</p>
                                            </td>
                                            <td className="px-4 py-2.5 text-right tabular-nums privacy-blur text-slate-600">{formatRupiah(p.saldo_rekening)}</td>
                                            <td className="px-4 py-2.5 text-right font-bold text-rose-600 tabular-nums privacy-blur">{formatRupiah(p.nominal)}</td>
                                            <td className="px-4 py-2.5"><StatusBadge status={p.status} /></td>
                                            <td className="px-4 py-2.5 text-right">
                                                <Button
                                                    size="sm"
                                                    variant="neutral"
                                                    onClick={(e) => {
                                                        e.stopPropagation();
                                                        setPengajuanDipilih(p);
                                                        setShowTolakMode(false);
                                                        keputusanForm.setData('akun_kas', p.kanal === 'tunai' ? 'kas_kecil' : 'bank');
                                                    }}
                                                >
                                                    Proses
                                                </Button>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </Card>
            )}

            {/* TAB 3: BUKA REKENING */}
            {tabAktif === 'buka' && (
                <Card padding="none" className="overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left border-collapse text-sm">
                            <thead>
                                <tr className="bg-[#F8FAFC] border-b border-slate-200/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                    <th className="px-4 py-3">Anggota</th>
                                    <th className="px-4 py-3">Cabang</th>
                                    <th className="px-4 py-3">Tgl Pengajuan</th>
                                    <th className="px-4 py-3">Tarif Saat Daftar</th>
                                    <th className="px-4 py-3">Status</th>
                                    <th className="px-4 py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {antreanBuka.length === 0 ? (
                                    <tr>
                                        <td colSpan={6} className="px-4 py-12 text-center text-slate-400">
                                            Tidak ada pengajuan pembukaan rekening yang menunggu persetujuan.
                                        </td>
                                    </tr>
                                ) : (
                                    antreanBuka.map((r) => (
                                        <tr
                                            key={r.id}
                                            onClick={() => { setBukaDipilih(r); setShowTolakMode(false); }}
                                            className="hover:bg-[#F2FBF5] transition-colors cursor-pointer"
                                        >
                                            <td className="px-4 py-2.5">
                                                <div className="flex items-center gap-2.5">
                                                    <FotoAnggota src={r.anggota?.foto_url} nama={r.anggota?.nama} size="sm" />
                                                    <div>
                                                        <p className="font-semibold text-slate-800">{r.anggota?.nama}</p>
                                                        <p className="text-xs text-slate-500">{r.anggota?.no_karyawan}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-4 py-2.5 text-slate-600">{r.anggota?.cabang ?? '-'}</td>
                                            <td className="px-4 py-2.5 text-slate-600">{r.tanggal_pengajuan}</td>
                                            <td className="px-4 py-2.5 font-semibold text-brand-navy tabular-nums">{r.suku_bunga_saat_buka}% / thn</td>
                                            <td className="px-4 py-2.5"><StatusBadge status={r.status} /></td>
                                            <td className="px-4 py-2.5 text-right">
                                                <Button size="sm" variant="neutral" onClick={(e) => { e.stopPropagation(); setBukaDipilih(r); setShowTolakMode(false); }}>
                                                    Tinjau
                                                </Button>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </Card>
            )}

            {/* TAB 4: POSTING BUNGA BULANAN */}
            {tabAktif === 'bunga' && previewBunga && (
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
            )}

            {/* DRAWER BUKA REKENING */}
            <Drawer
                show={Boolean(bukaDipilih)}
                title="Persetujuan Buka Rekening"
                description={bukaDipilih?.anggota?.nama}
                badge={bukaDipilih ? <StatusBadge status={bukaDipilih.status} /> : null}
                onClose={tutupDrawers}
                layout="split"
            >
                {bukaDipilih && (
                    <form onSubmit={showTolakMode ? tolakBuka : (e) => { e.preventDefault(); setujuiBuka(); }} className="flex-1 min-h-0 flex flex-col">
                        <DrawerBody>
                            <DrawerSection title="Profil Pemohon" icon={UserCheck}>
                                <DrawerFieldGrid cols={2}>
                                    <DrawerField label="Nama Lengkap" value={bukaDipilih.anggota?.nama} />
                                    <DrawerField label="No. Karyawan" value={bukaDipilih.anggota?.no_karyawan} mono />
                                    <DrawerField label="Cabang" value={bukaDipilih.anggota?.cabang} />
                                    <DrawerField label="Tgl Pengajuan" value={bukaDipilih.tanggal_pengajuan} />
                                    <DrawerField label="Tarif Berlaku" value={`${bukaDipilih.suku_bunga_saat_buka}% / tahun`} />
                                    <DrawerField label="Versi S&K Disetujui" value={bukaDipilih.versi_syarat} mono />
                                </DrawerFieldGrid>
                            </DrawerSection>

                            <DrawerSection title={showTolakMode ? 'Alasan Penolakan' : 'Catatan Bendahara (Opsional)'} tone={showTolakMode ? 'rose' : 'default'}>
                                <FormField label={showTolakMode ? 'Alasan Penolakan (wajib)' : 'Catatan'} error={keputusanForm.errors.catatan || keputusanForm.errors.aksi} required={showTolakMode}>
                                    <TextField
                                        as="textarea"
                                        rows={3}
                                        value={keputusanForm.data.catatan}
                                        onChange={(e) => keputusanForm.setData('catatan', e.target.value)}
                                        placeholder={showTolakMode ? 'Tuliskan alasan penolakan (min. 3 karakter)...' : 'Catatan tambahan untuk anggota...'}
                                        required={showTolakMode}
                                    />
                                </FormField>
                            </DrawerSection>
                        </DrawerBody>

                        <DrawerFooter>
                            {showTolakMode ? (
                                <DrawerFooterActions cols={2} stackOnMobile>
                                    <Button type="button" variant="neutral" size="drawer" onClick={() => setShowTolakMode(false)}>Kembali</Button>
                                    <Button type="submit" variant="danger" size="drawer" disabled={keputusanForm.processing}>
                                        {keputusanForm.processing ? 'Menolak...' : 'Konfirmasi Tolak'}
                                    </Button>
                                </DrawerFooterActions>
                            ) : (
                                <DrawerFooterActions cols={2} stackOnMobile>
                                    <Button type="button" variant="dangerSoft" size="drawer" onClick={() => setShowTolakMode(true)}>
                                        <XCircle size={15} /> Tolak
                                    </Button>
                                    <Button type="submit" size="drawer" disabled={keputusanForm.processing}>
                                        <CheckCircle2 size={15} /> {keputusanForm.processing ? 'Menyetujui...' : 'Setujui & Terbitkan Rekening'}
                                    </Button>
                                </DrawerFooterActions>
                            )}
                        </DrawerFooter>
                    </form>
                )}
            </Drawer>

            {/* DRAWER VERIFIKASI SETOR / TARIK */}
            <Drawer
                show={Boolean(pengajuanDipilih)}
                title={
                    pengajuanDipilih?.jenis === 'setor'
                        ? 'Verifikasi Setoran Tabungan'
                        : pengajuanDipilih?.jenis === 'tutup_rekening'
                        ? 'Verifikasi Tutup Rekening & Pencairan'
                        : 'Verifikasi Penarikan Tabungan'
                }
                description={pengajuanDipilih ? `${pengajuanDipilih.anggota?.nama} • ${pengajuanDipilih.no_rekening}` : ''}
                badge={pengajuanDipilih ? <StatusBadge status={pengajuanDipilih.status} /> : null}
                onClose={tutupDrawers}
                layout="split"
            >
                {pengajuanDipilih && (
                    <form onSubmit={showTolakMode ? tolakPengajuan : setujuiPengajuan} className="flex-1 min-h-0 flex flex-col">
                        <DrawerBody>
                            <DrawerSection
                                title="Ringkasan Transaksi"
                                icon={pengajuanDipilih.jenis === 'setor' ? ArrowDownCircle : ArrowUpCircle}
                                tone={pengajuanDipilih.jenis === 'setor' ? 'emerald' : 'amber'}
                            >
                                <DrawerKeyValue
                                    rows={[
                                        { label: 'Anggota', value: `${pengajuanDipilih.anggota?.nama} (${pengajuanDipilih.anggota?.no_karyawan})` },
                                        { label: 'No. Rekening', value: pengajuanDipilih.no_rekening, mono: true },
                                        { label: 'Saldo Rekening Saat Ini', value: formatRupiah(pengajuanDipilih.saldo_rekening), privacy: true },
                                        {
                                            label: pengajuanDipilih.jenis === 'setor' ? 'Nominal Setor' : 'Nominal Diajukan',
                                            value: formatRupiah(pengajuanDipilih.nominal),
                                            tone: pengajuanDipilih.jenis === 'setor' ? 'emerald' : 'rose',
                                            emphasize: true,
                                            privacy: true,
                                        },
                                        { label: 'Tanggal', value: pengajuanDipilih.tanggal_transaksi },
                                        ...(pengajuanDipilih.catatan_anggota ? [{ label: 'Catatan Anggota', value: pengajuanDipilih.catatan_anggota }] : []),
                                    ]}
                                />
                            </DrawerSection>

                            {pengajuanDipilih.jenis === 'setor' && pengajuanDipilih.bukti_url && (
                                <DrawerSection
                                    title="Bukti Transfer Anggota"
                                    icon={FileText}
                                    action={
                                        <a
                                            href={pengajuanDipilih.bukti_url}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="inline-flex items-center gap-1 text-xs font-semibold text-brand-navy hover:underline"
                                        >
                                            Buka Tab Baru <ExternalLink size={12} />
                                        </a>
                                    }
                                >
                                    {pengajuanDipilih.bukti_mime?.includes('pdf') ? (
                                        <iframe
                                            src={pengajuanDipilih.bukti_url}
                                            title="Bukti Transfer PDF"
                                            className="w-full h-72 rounded-lg border border-slate-200 bg-slate-50"
                                        />
                                    ) : (
                                        <img
                                            src={pengajuanDipilih.bukti_url}
                                            alt="Bukti Transfer Setoran"
                                            className="w-full max-h-80 object-contain rounded-lg border border-slate-200 bg-slate-50"
                                        />
                                    )}
                                </DrawerSection>
                            )}

                            {pengajuanDipilih.jenis !== 'setor' && pengajuanDipilih.rekening_tujuan && (
                                <DrawerSection title="Rekening Tujuan Transfer Anggota" icon={Landmark}>
                                    <DrawerKeyValue
                                        rows={[
                                            { label: 'Bank', value: pengajuanDipilih.rekening_tujuan.nama_bank },
                                            { label: 'No. Rekening', value: pengajuanDipilih.rekening_tujuan.no_rekening, mono: true },
                                            { label: 'Atas Nama', value: pengajuanDipilih.rekening_tujuan.atas_nama },
                                        ]}
                                    />
                                </DrawerSection>
                            )}

                            {!showTolakMode && pengajuanDipilih.jenis !== 'setor' && (
                                <DrawerSection title="Sumber Dana Pencairan" icon={Wallet}>
                                    <FormField label="Sumber Kas Fisik" error={keputusanForm.errors.akun_kas || keputusanForm.errors.aksi} required>
                                        <Select value={keputusanForm.data.akun_kas} onChange={(e) => keputusanForm.setData('akun_kas', e.target.value)}>
                                            <option value="bank">Bank Koperasi (Saldo {formatRupiah(kasFisik.saldo_bank)})</option>
                                            <option value="kas_kecil">Kas Kecil (Saldo {formatRupiah(kasFisik.saldo_kas_kecil)})</option>
                                        </Select>
                                    </FormField>
                                    <FormField label="Bukti Pencairan / Transfer (Opsional)" error={keputusanForm.errors.bukti_pencairan}>
                                        <input
                                            type="file"
                                            accept=".jpg,.jpeg,.png,.webp,.pdf"
                                            onChange={(e) => keputusanForm.setData('bukti_pencairan', e.target.files?.[0] ?? null)}
                                            className="block w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-brand-navy/10 file:text-brand-navy"
                                        />
                                    </FormField>
                                </DrawerSection>
                            )}

                            <DrawerSection title={showTolakMode ? 'Alasan Penolakan' : 'Catatan Bendahara (Opsional)'} tone={showTolakMode ? 'rose' : 'default'}>
                                <FormField label={showTolakMode ? 'Alasan Penolakan (wajib)' : 'Catatan'} error={keputusanForm.errors.catatan || keputusanForm.errors.aksi} required={showTolakMode}>
                                    <TextField
                                        as="textarea"
                                        rows={2}
                                        value={keputusanForm.data.catatan}
                                        onChange={(e) => keputusanForm.setData('catatan', e.target.value)}
                                        placeholder={showTolakMode ? 'Mis. Nominal tidak sesuai mutasi bank / bukti buram' : 'Catatan verifikasi'}
                                        required={showTolakMode}
                                    />
                                </FormField>
                            </DrawerSection>
                        </DrawerBody>

                        <DrawerFooter>
                            {showTolakMode ? (
                                <DrawerFooterActions cols={2} stackOnMobile>
                                    <Button type="button" variant="neutral" size="drawer" onClick={() => setShowTolakMode(false)}>Kembali</Button>
                                    <Button type="submit" variant="danger" size="drawer" disabled={keputusanForm.processing}>
                                        {keputusanForm.processing ? 'Menolak...' : 'Konfirmasi Tolak'}
                                    </Button>
                                </DrawerFooterActions>
                            ) : (
                                <DrawerFooterActions cols={2} stackOnMobile>
                                    <Button type="button" variant="dangerSoft" size="drawer" onClick={() => setShowTolakMode(true)}>
                                        <XCircle size={15} /> Tolak
                                    </Button>
                                    <Button type="submit" size="drawer" disabled={keputusanForm.processing}>
                                        <CheckCircle2 size={15} />{' '}
                                        {keputusanForm.processing
                                            ? 'Memproses...'
                                            : pengajuanDipilih.jenis === 'setor'
                                            ? 'Setujui & Tambah Saldo'
                                            : pengajuanDipilih.jenis === 'tutup_rekening'
                                            ? 'Cairkan & Tutup Rekening'
                                            : 'Setujui & Cairkan'}
                                    </Button>
                                </DrawerFooterActions>
                            )}
                        </DrawerFooter>
                    </form>
                )}
            </Drawer>

            {/* DRAWER SETOR TUNAI LANGSUNG */}
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

                            <FormField label="Nominal Setor (Rp)" error={setorTunaiForm.errors.nominal || setorTunaiForm.errors.setor_tunai} hint={`Minimal ${formatRupiah(setting.min_setor)}`} required>
                                <TextField
                                    type="number"
                                    min={setting.min_setor}
                                    value={setorTunaiForm.data.nominal}
                                    onChange={(e) => setorTunaiForm.setData('nominal', e.target.value)}
                                    className="tabular-nums"
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
        </AppLayout>
    );
}
