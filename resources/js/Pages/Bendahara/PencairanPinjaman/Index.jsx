import AppLayout from '@/Layouts/AppLayout';
import { Head, useForm } from '@inertiajs/react';
import {
    Banknote, CalendarDays, Check, CheckCircle2, ChevronRight, Copy,
    ExternalLink, FileText, Landmark, MessageSquareText, Upload, User, XCircle,
} from 'lucide-react';
import { useId, useMemo, useState } from 'react';
import Button from '@/Components/ui/Button';
import Card from '@/Components/ui/Card';
import CountBadge from '@/Components/ui/CountBadge';
import Drawer from '@/Components/ui/Drawer';
import {
    DrawerBody,
    DrawerField,
    DrawerFieldGrid,
    DrawerFooter,
    DrawerFooterActions,
    DrawerHero,
    DrawerNoteCallout,
    DrawerSection,
} from '@/Components/ui/DrawerSection';
import FormField from '@/Components/ui/FormField';
import FotoAnggota from '@/Components/ui/FotoAnggota';
import PageHeader from '@/Components/ui/PageHeader';
import StatusBadge from '@/Components/ui/StatusBadge';
import TextField from '@/Components/ui/TextField';
import RingkasanKas from '@/Pages/PinjamanApproval/RingkasanKas';
import { formatRupiah } from '@/Utils/formatCurrency';
import { withIdempotencyKey } from '@/Utils/idempotency';

const tabDasar = 'inline-flex items-center gap-2 px-3.5 py-2 text-xs sm:text-sm font-semibold rounded-xl transition-all duration-150';
const tabAktif = `${tabDasar} bg-brand-green-light/45 border border-brand-green/35 text-brand-navy`;
const tabNonAktif = `${tabDasar} text-slate-600 hover:bg-slate-100/70 hover:text-brand-navy`;

function hitungJatuhTempoPertama(tanggalIso) {
    if (!tanggalIso || !/^\d{4}-\d{2}-\d{2}$/.test(tanggalIso)) return '-';
    const [y, m] = tanggalIso.split('-').map(Number);
    const akhirBulanBerikut = new Date(y, m + 1, 0);
    return new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }).format(akhirBulanBerikut);
}

export default function Index({ menungguPencairan = [], riwayatPencairan = [], ringkasanKas }) {
    const [tab, setTab] = useState('menunggu');
    const [pinjamanDipilih, setPinjamanDipilih] = useState(null);
    const [riwayatDipilih, setRiwayatDipilih] = useState(null);
    const [salinBerhasil, setSalinBerhasil] = useState(false);
    const [showBatalPencairan, setShowBatalPencairan] = useState(false);
    const formId = useId();
    const hariIni = new Date().toISOString().slice(0, 10);

    const cairForm = useForm({
        tanggal_pencairan: hariIni,
        bukti_pencairan: null,
        catatan: '',
    });

    const jatuhTempoPertamaLabel = useMemo(
        () => hitungJatuhTempoPertama(cairForm.data.tanggal_pencairan),
        [cairForm.data.tanggal_pencairan]
    );

    function bukaDrawerPencairan(p) {
        cairForm.reset();
        cairForm.setData({
            tanggal_pencairan: hariIni,
            bukti_pencairan: null,
            catatan: '',
        });
        cairForm.clearErrors();
        setSalinBerhasil(false);
        setShowBatalPencairan(false);
        setPinjamanDipilih(p);
    }

    function tutupDrawerPencairan() {
        setPinjamanDipilih(null);
        setShowBatalPencairan(false);
        cairForm.reset();
        cairForm.clearErrors();
    }

    function salinRekening(noRekening) {
        if (!noRekening || !navigator.clipboard) return;
        navigator.clipboard.writeText(noRekening);
        setSalinBerhasil(true);
        setTimeout(() => setSalinBerhasil(false), 1800);
    }

    function submitCair(e) {
        e.preventDefault();
        if (!pinjamanDipilih) return;
        const url = showBatalPencairan
            ? route('bendahara.pencairan-pinjaman.batal', pinjamanDipilih.id)
            : route('bendahara.pencairan-pinjaman.cair', pinjamanDipilih.id);
        cairForm.post(url, {
            ...withIdempotencyKey({
                forceFormData: !showBatalPencairan,
                preserveScroll: true,
                onSuccess: tutupDrawerPencairan,
            }),
        });
    }

    return (
        <AppLayout>
            <Head title="Pencairan Pinjaman" />

            <PageHeader
                title="Pencairan Pinjaman"
                subtitle={`${menungguPencairan.length} pinjaman telah disetujui Ketua dan menunggu bukti transfer pencairan`}
            />

            <RingkasanKas ringkasan={ringkasanKas} />

            <Card padding="none" className="overflow-hidden">
                <div className="flex flex-wrap items-center gap-1.5 px-3.5 py-2.5 bg-white border-b border-slate-200/80">
                    <button type="button" onClick={() => setTab('menunggu')} className={tab === 'menunggu' ? tabAktif : tabNonAktif}>
                        Menunggu Transfer
                        <CountBadge count={menungguPencairan.length} context="tab" />
                    </button>
                    <button type="button" onClick={() => setTab('riwayat')} className={tab === 'riwayat' ? tabAktif : tabNonAktif}>
                        Riwayat Pencairan
                    </button>
                </div>

                <div className="overflow-x-auto">
                    {tab === 'menunggu' ? (
                        <table className="w-full table-sticky-first text-sm">
                            <thead>
                                <tr className="text-left border-b border-slate-200/80 bg-[#F8FAFC]">
                                    <th className="px-4 py-2.5 text-[11px] font-bold uppercase tracking-wider text-slate-500 whitespace-nowrap">Anggota</th>
                                    <th className="px-4 py-2.5 text-[11px] font-bold uppercase tracking-wider text-slate-500 whitespace-nowrap">Nominal Disetujui</th>
                                    <th className="px-4 py-2.5 text-[11px] font-bold uppercase tracking-wider text-slate-500 whitespace-nowrap">Rekening Tujuan</th>
                                    <th className="px-4 py-2.5 text-[11px] font-bold uppercase tracking-wider text-slate-500 whitespace-nowrap">Tenor</th>
                                    <th className="px-4 py-2.5 text-[11px] font-bold uppercase tracking-wider text-slate-500 whitespace-nowrap">Status</th>
                                    <th className="w-10 px-3 py-2.5" aria-label="Buka pencairan" />
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100 bg-white">
                                {menungguPencairan.length === 0 ? (
                                    <tr>
                                        <td colSpan={6} className="px-4 py-10 text-center text-sm font-medium text-slate-500">
                                            Semua pinjaman yang disetujui Ketua sudah dicairkan.
                                        </td>
                                    </tr>
                                ) : (
                                    menungguPencairan.map((p) => (
                                        <tr
                                            key={p.id}
                                            onClick={() => bukaDrawerPencairan(p)}
                                            onKeyDown={(e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); bukaDrawerPencairan(p); } }}
                                            tabIndex={0}
                                            title="Klik untuk memproses transfer & unggah bukti"
                                            className="group hover:bg-[#F2FBF5] transition-colors cursor-pointer focus-visible:outline-none focus-visible:bg-[#F2FBF5]"
                                        >
                                            <td className="px-4 py-2.5">
                                                <div className="flex items-center gap-3">
                                                    <FotoAnggota nama={p.anggota.nama} fotoUrl={p.anggota.foto_url} ukuran="sm" />
                                                    <div className="min-w-0">
                                                        <p className="text-sm font-semibold text-slate-800 truncate">{p.anggota.nama}</p>
                                                        <p className="text-xs text-slate-500">{p.anggota.no_karyawan}{p.anggota.cabang ? ` • ${p.anggota.cabang}` : ''}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-4 py-2.5 text-sm font-bold text-slate-800 tabular-nums privacy-blur whitespace-nowrap">
                                                {formatRupiah(p.nominal_disetujui ?? p.nominal)}
                                            </td>
                                            <td className="px-4 py-2.5 text-xs text-slate-600">
                                                <p className="font-semibold text-slate-800">{p.rekening?.bank}</p>
                                                <p className="font-mono">{p.rekening?.no_rekening}</p>
                                            </td>
                                            <td className="px-4 py-2.5 text-sm text-slate-600 whitespace-nowrap">{p.tenor_disetujui ?? p.tenor_bulan} bln</td>
                                            <td className="px-4 py-2.5"><StatusBadge status={p.status} /></td>
                                            <td className="px-3 py-2.5 text-right" aria-hidden="true">
                                                <ChevronRight size={18} className="ml-auto text-slate-300 group-hover:text-brand-green group-hover:translate-x-0.5 transition-all" />
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    ) : (
                        <table className="w-full table-sticky-first text-sm">
                            <thead>
                                <tr className="text-left border-b border-slate-200/80 bg-[#F8FAFC]">
                                    <th className="px-4 py-2.5 text-[11px] font-bold uppercase tracking-wider text-slate-500 whitespace-nowrap">Anggota</th>
                                    <th className="px-4 py-2.5 text-[11px] font-bold uppercase tracking-wider text-slate-500 whitespace-nowrap">Nominal Cair</th>
                                    <th className="px-4 py-2.5 text-[11px] font-bold uppercase tracking-wider text-slate-500 whitespace-nowrap">Tgl Transfer</th>
                                    <th className="px-4 py-2.5 text-[11px] font-bold uppercase tracking-wider text-slate-500 whitespace-nowrap">Bukti Transfer</th>
                                    <th className="px-4 py-2.5 text-[11px] font-bold uppercase tracking-wider text-slate-500 whitespace-nowrap">Status</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100 bg-white">
                                {riwayatPencairan.length === 0 ? (
                                    <tr>
                                        <td colSpan={5} className="px-4 py-10 text-center text-sm font-medium text-slate-500">
                                            Belum ada riwayat pencairan.
                                        </td>
                                    </tr>
                                ) : (
                                    riwayatPencairan.map((p) => (
                                        <tr
                                            key={p.id}
                                            onClick={() => setRiwayatDipilih(p)}
                                            className="group hover:bg-[#F2FBF5] transition-colors cursor-pointer"
                                        >
                                            <td className="px-4 py-2.5">
                                                <p className="text-sm font-semibold text-slate-800 truncate">{p.anggota.nama}</p>
                                                <p className="text-xs text-slate-500">{p.anggota.no_karyawan}{p.nomor_dokumen ? ` • ${p.nomor_dokumen}` : ''}</p>
                                            </td>
                                            <td className="px-4 py-2.5 text-sm font-bold text-slate-800 tabular-nums privacy-blur whitespace-nowrap">{formatRupiah(p.nominal)}</td>
                                            <td className="px-4 py-2.5 text-sm text-slate-600 whitespace-nowrap">{p.tanggal_pencairan}</td>
                                            <td className="px-4 py-2.5 whitespace-nowrap" onClick={(e) => e.stopPropagation()}>
                                                {p.bukti_pencairan_url ? (
                                                    <a
                                                        href={p.bukti_pencairan_url}
                                                        target="_blank"
                                                        rel="noreferrer"
                                                        className="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border border-slate-200 text-xs font-semibold text-brand-navy hover:bg-slate-50"
                                                    >
                                                        <FileText size={12} /> Lihat Bukti <ExternalLink size={11} />
                                                    </a>
                                                ) : (
                                                    <span className="text-xs text-slate-400">-</span>
                                                )}
                                            </td>
                                            <td className="px-4 py-2.5"><StatusBadge status={p.status} /></td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    )}
                </div>
            </Card>

            {/* DRAWER KONFIRMASI PENCAIRAN */}
            <Drawer
                show={Boolean(pinjamanDipilih)}
                layout="split"
                title={`Pencairan Pinjaman — ${pinjamanDipilih?.anggota?.nama ?? ''}`}
                description={pinjamanDipilih ? `${pinjamanDipilih.anggota.no_karyawan}${pinjamanDipilih.anggota.cabang ? ` • ${pinjamanDipilih.anggota.cabang}` : ''}` : ''}
                badge={pinjamanDipilih ? <StatusBadge status={pinjamanDipilih.status} /> : null}
                onClose={tutupDrawerPencairan}
                maxWidth="2xl"
            >
                {pinjamanDipilih && (
                    <>
                        <DrawerBody>
                            <DrawerHero>
                                <div className="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                                    <div>
                                        <p className="text-xs font-medium text-slate-300">Nominal Disetujui untuk Ditransfer</p>
                                        <p className="text-2xl sm:text-[28px] font-extrabold tracking-tight tabular-nums mt-0.5 privacy-blur">
                                            {formatRupiah(pinjamanDipilih.nominal_disetujui ?? pinjamanDipilih.nominal)}
                                        </p>
                                        <p className="text-xs text-slate-300 italic mt-0.5 privacy-blur">{pinjamanDipilih.terbilang}</p>
                                    </div>
                                    <div className="rounded-xl bg-white/10 border border-white/15 px-3 py-2 text-xs sm:text-right shrink-0">
                                        <p className="text-slate-300">Tenor Final &bull; Bunga</p>
                                        <p className="font-bold text-white mt-0.5 tabular-nums">
                                            {pinjamanDipilih.tenor_disetujui ?? pinjamanDipilih.tenor_bulan} bln &bull; {pinjamanDipilih.persentase_bunga}% / bln
                                        </p>
                                    </div>
                                </div>

                                <div className="flex flex-wrap items-center justify-between gap-3 pt-3.5 mt-3.5 border-t border-white/10">
                                    <div className="min-w-0">
                                        <p className="text-[11px] font-medium text-emerald-300 flex items-center gap-1.5">
                                            <Landmark size={12} /> Rekening Tujuan Transfer Anggota
                                        </p>
                                        <p className="text-sm font-bold text-white mt-0.5">
                                            {pinjamanDipilih.rekening?.bank}{' '}
                                            <span className="font-mono tracking-wide">{pinjamanDipilih.rekening?.no_rekening}</span>
                                        </p>
                                        <p className="text-xs text-slate-300">a.n. {pinjamanDipilih.rekening?.atas_nama}</p>
                                    </div>
                                    <button
                                        type="button"
                                        onClick={() => salinRekening(pinjamanDipilih.rekening?.no_rekening)}
                                        className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white/15 hover:bg-white/25 border border-white/20 text-xs font-semibold text-white transition-colors shrink-0"
                                    >
                                        {salinBerhasil ? <Check size={13} className="text-emerald-300" /> : <Copy size={13} />}
                                        {salinBerhasil ? 'Tersalin' : 'Salin No. Rek'}
                                    </button>
                                </div>
                            </DrawerHero>

                            <DrawerSection
                                title={showBatalPencairan ? 'Batalkan Pencairan Pinjaman' : 'Upload Bukti & Tanggal Transfer'}
                                icon={showBatalPencairan ? XCircle : Banknote}
                                tone={showBatalPencairan ? 'rose' : 'emerald'}
                            >
                                <form id={formId} onSubmit={submitCair} className="space-y-3.5">
                                    {cairForm.errors.keputusan && (
                                        <div className="rounded-xl bg-rose-50 border border-rose-200 px-3.5 py-2.5 text-sm font-medium text-rose-700">
                                            {cairForm.errors.keputusan}
                                        </div>
                                    )}

                                    {showBatalPencairan ? (
                                        <FormField
                                            label="Alasan Pembatalan Pencairan (Wajib, min. 5 karakter)"
                                            error={cairForm.errors.catatan}
                                            required
                                        >
                                            <TextField
                                                data-autofocus
                                                as="textarea"
                                                rows={3}
                                                value={cairForm.data.catatan}
                                                onChange={(e) => cairForm.setData('catatan', e.target.value)}
                                                placeholder="Contoh: Anggota membatalkan pinjaman sebelum dana ditransfer."
                                                required
                                            />
                                        </FormField>
                                    ) : (
                                        <>
                                            <FormField
                                                label="Bukti Transfer (JPG, PNG, WEBP, atau PDF, maks 5 MB)"
                                                error={cairForm.errors.bukti_pencairan}
                                                hint={cairForm.data.bukti_pencairan ? `File siap diunggah: ${cairForm.data.bukti_pencairan.name}` : 'Wajib unggah bukti transfer sebelum konfirmasi pencairan.'}
                                                required
                                            >
                                                <div className="flex items-center gap-2 rounded-xl border border-dashed border-emerald-300/90 bg-emerald-50/35 px-3 py-2.5 focus-within:border-brand-green focus-within:ring-[3px] focus-within:ring-brand-green/15 transition-all">
                                                    <Upload size={16} className="text-brand-green-dark shrink-0" />
                                                    <input
                                                        data-autofocus
                                                        type="file"
                                                        accept=".jpg,.jpeg,.png,.webp,.pdf"
                                                        onChange={(e) => cairForm.setData('bukti_pencairan', e.target.files?.[0] ?? null)}
                                                        className="block w-full text-xs text-slate-600 focus:outline-none file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-brand-navy text-slate-700 file:text-white hover:file:opacity-90 cursor-pointer"
                                                        required
                                                    />
                                                </div>
                                            </FormField>

                                            <FormField label="Tanggal Transfer / Pencairan" error={cairForm.errors.tanggal_pencairan} required>
                                                <TextField
                                                    type="date"
                                                    max={hariIni}
                                                    value={cairForm.data.tanggal_pencairan}
                                                    onChange={(e) => cairForm.setData('tanggal_pencairan', e.target.value)}
                                                    required
                                                />
                                                <p className="text-xs text-slate-500 mt-1.5 inline-flex items-center gap-1.5">
                                                    <CalendarDays size={13} className="text-brand-green-dark shrink-0" />
                                                    Cicilan ke-1 jatuh tempo akhir bulan berikutnya: <strong className="text-slate-700">{jatuhTempoPertamaLabel}</strong>
                                                </p>
                                            </FormField>

                                            <FormField label="Catatan Pencairan (Opsional)" error={cairForm.errors.catatan}>
                                                <TextField
                                                    as="textarea"
                                                    rows={2}
                                                    value={cairForm.data.catatan}
                                                    onChange={(e) => cairForm.setData('catatan', e.target.value)}
                                                    placeholder="Contoh: Transfer via Bank Mandiri akhir bulan November."
                                                />
                                            </FormField>
                                        </>
                                    )}
                                </form>
                            </DrawerSection>

                            {pinjamanDipilih.catatan_ketua && (
                                <DrawerNoteCallout
                                    label="Catatan Persetujuan Ketua"
                                    note={pinjamanDipilih.catatan_ketua}
                                    timestamp={pinjamanDipilih.disetujui_ketua_pada ?? pinjamanDipilih.tanggal_pengajuan}
                                    tone="emerald"
                                    icon={MessageSquareText}
                                />
                            )}

                            <DrawerSection title="Informasi Pengajuan" icon={User}>
                                <DrawerFieldGrid cols={2}>
                                    <DrawerField label="Nama Anggota" value={pinjamanDipilih.anggota.nama} />
                                    <DrawerField label="Disetujui Ketua" value={pinjamanDipilih.disetujui_ketua_pada ?? pinjamanDipilih.tanggal_pengajuan} />
                                </DrawerFieldGrid>
                            </DrawerSection>
                        </DrawerBody>

                        <DrawerFooter>
                            {showBatalPencairan ? (
                                <DrawerFooterActions cols={2} stackOnMobile>
                                    <Button type="button" variant="neutral" size="drawer" onClick={() => setShowBatalPencairan(false)} disabled={cairForm.processing}>
                                        Kembali
                                    </Button>
                                    <Button
                                        type="submit"
                                        form={formId}
                                        variant="danger"
                                        size="drawer"
                                        disabled={cairForm.processing || cairForm.data.catatan.trim().length < 5}
                                    >
                                        <XCircle size={16} />
                                        {cairForm.processing ? 'Membatalkan...' : 'Konfirmasi Batalkan Pencairan'}
                                    </Button>
                                </DrawerFooterActions>
                            ) : (
                                <DrawerFooterActions cols={2} stackOnMobile>
                                    <Button type="button" variant="dangerSoft" size="drawer" onClick={() => setShowBatalPencairan(true)} disabled={cairForm.processing}>
                                        <XCircle size={16} />
                                        Batalkan Pencairan
                                    </Button>
                                    <Button
                                        type="submit"
                                        form={formId}
                                        size="drawer"
                                        disabled={cairForm.processing || !cairForm.data.bukti_pencairan}
                                    >
                                        <CheckCircle2 size={16} />
                                        {cairForm.processing ? 'Memproses...' : 'Konfirmasi Cair & Kirim WA'}
                                    </Button>
                                </DrawerFooterActions>
                            )}
                        </DrawerFooter>
                    </>
                )}
            </Drawer>

            {/* DRAWER RINCIAN RIWAYAT */}
            <Drawer
                show={Boolean(riwayatDipilih)}
                layout="split"
                title={`Rincian Pencairan #${riwayatDipilih?.id ?? ''}`}
                badge={riwayatDipilih ? <StatusBadge status={riwayatDipilih.status} /> : null}
                onClose={() => setRiwayatDipilih(null)}
                maxWidth="xl"
            >
                {riwayatDipilih && (
                    <>
                        <DrawerBody>
                            <DrawerSection title="Informasi Pencairan" icon={Banknote}>
                                <DrawerFieldGrid cols={2}>
                                    <DrawerField label="Anggota" value={`${riwayatDipilih.anggota.nama} (${riwayatDipilih.anggota.no_karyawan})`} />
                                    <DrawerField label="No. Dokumen" value={riwayatDipilih.nomor_dokumen || '-'} mono />
                                    <DrawerField label="Nominal Cair" value={formatRupiah(riwayatDipilih.nominal)} />
                                    <DrawerField label="Tanggal Transfer" value={riwayatDipilih.tanggal_pencairan || '-'} />
                                    <DrawerField label="Dicairkan Oleh" value={riwayatDipilih.dicairkan_oleh || '-'} />
                                    <DrawerField label="Rekening Tujuan" value={`${riwayatDipilih.rekening?.bank ?? '-'} • ${riwayatDipilih.rekening?.no_rekening ?? '-'}`} />
                                </DrawerFieldGrid>
                                {riwayatDipilih.catatan_pencairan && (
                                    <p className="text-xs text-slate-600 mt-3 pt-3 border-t border-slate-100">
                                        <strong>Catatan:</strong> {riwayatDipilih.catatan_pencairan}
                                    </p>
                                )}
                                {riwayatDipilih.bukti_pencairan_url && (
                                    <div className="mt-4 pt-3 border-t border-slate-100">
                                        <a
                                            href={riwayatDipilih.bukti_pencairan_url}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-brand-navy text-white text-xs font-semibold hover:opacity-95"
                                        >
                                            <FileText size={14} /> Buka Bukti Transfer <ExternalLink size={12} />
                                        </a>
                                    </div>
                                )}
                            </DrawerSection>
                        </DrawerBody>
                        <DrawerFooter>
                            <DrawerFooterActions cols={1}>
                                <Button type="button" variant="neutral" size="drawer" onClick={() => setRiwayatDipilih(null)}>
                                    Tutup
                                </Button>
                            </DrawerFooterActions>
                        </DrawerFooter>
                    </>
                )}
            </Drawer>
        </AppLayout>
    );
}
