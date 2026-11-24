import { useForm } from '@inertiajs/react';
import {
    Banknote, CalendarDays, Check, CheckCircle2, Copy, ExternalLink, FileText,
    Landmark, MessageSquareText, Upload, User, Wallet, XCircle,
} from 'lucide-react';
import { useEffect, useId, useMemo, useRef, useState } from 'react';
import Button from '@/Components/ui/Button';
import {
    DrawerBody,
    DrawerField,
    DrawerFieldGrid,
    DrawerFooter,
    DrawerFooterActions,
    DrawerHero,
    DrawerKeyValue,
    DrawerNoteCallout,
    DrawerSection,
} from '@/Components/ui/DrawerSection';
import FormField from '@/Components/ui/FormField';
import Select from '@/Components/ui/Select';
import StatusBadge from '@/Components/ui/StatusBadge';
import TextField from '@/Components/ui/TextField';
import { formatRupiah } from '@/Utils/formatCurrency';
import { withIdempotencyKey } from '@/Utils/idempotency';

function hitungJatuhTempoPertama(tanggalIso) {
    if (!tanggalIso || !/^\d{4}-\d{2}-\d{2}$/.test(tanggalIso)) return '-';
    const [y, m] = tanggalIso.split('-').map(Number);
    const akhirBulanBerikut = new Date(y, m + 1, 0);
    return new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }).format(akhirBulanBerikut);
}

export default function PencairanDrawers({ item, kasRingkas, onClose }) {
    const isTabungan = item.jenis_kode === 'cair_tabungan';
    const isResign = item.jenis_kode === 'cair_resign';
    const data = item.detail;
    const bisaCair = data.status === 'approved_ketua';
    const bisaCairResign = data.status === 'menunggu_pencairan';

    const [salinBerhasil, setSalinBerhasil] = useState(false);
    const [showTolakTabungan, setShowTolakTabungan] = useState(false);
    const formId = useId();
    const tabunganCatatanRef = useRef(null);
    const hariIni = new Date().toISOString().slice(0, 10);

    const pinjamanForm = useForm({
        tanggal_pencairan: hariIni,
        bukti_pencairan: null,
        catatan: '',
    });

    const tabunganForm = useForm({
        catatan: '',
        akun_kas: data.kanal === 'tunai' ? 'kas_kecil' : 'bank',
        tanggal_pencairan: hariIni,
        bukti_pencairan: null,
    });

    const resignForm = useForm({
        akun_kas: data.rekening?.no_rekening && data.rekening.no_rekening !== '-' ? 'bank' : 'kas_kecil',
        tanggal_pencairan: hariIni,
        bukti_pencairan: null,
        catatan: '',
    });

    useEffect(() => {
        if (!showTolakTabungan) return undefined;
        const id = window.requestAnimationFrame(() => {
            tabunganCatatanRef.current?.focus({ preventScroll: false });
        });
        return () => window.cancelAnimationFrame(id);
    }, [showTolakTabungan]);

    const jatuhTempoPertamaLabel = useMemo(
        () => hitungJatuhTempoPertama(pinjamanForm.data.tanggal_pencairan),
        [pinjamanForm.data.tanggal_pencairan]
    );

    function salinRekening(noRek) {
        if (!noRek || !navigator.clipboard) return;
        navigator.clipboard.writeText(noRek);
        setSalinBerhasil(true);
        setTimeout(() => setSalinBerhasil(false), 1800);
    }

    function submitPinjaman(e) {
        e.preventDefault();
        pinjamanForm.post(route('bendahara.pencairan-pinjaman.cair', data.id), {
            ...withIdempotencyKey({
                forceFormData: true,
                preserveScroll: true,
                onSuccess: onClose,
            }),
        });
    }

    function submitTabungan(e) {
        e.preventDefault();
        const url = showTolakTabungan
            ? route('bendahara.tabungan.pengajuan.tolak', data.id)
            : route('bendahara.tabungan.pengajuan.cair', data.id);

        tabunganForm.post(url, {
            ...withIdempotencyKey({
                forceFormData: !showTolakTabungan,
                preserveScroll: true,
                onSuccess: onClose,
            }),
        });
    }

    function submitResign(e) {
        e.preventDefault();
        resignForm.post(route('bendahara.pencairan-resign.cair', data.id), {
            ...withIdempotencyKey({
                forceFormData: true,
                preserveScroll: true,
                onSuccess: onClose,
            }),
        });
    }

    const saldoBank = kasRingkas?.saldo_bank ?? 0;
    const saldoKasKecil = kasRingkas?.saldo_kas_kecil ?? 0;

    if (isResign) {
        if (!bisaCairResign) {
            return (
                <>
                    <DrawerBody>
                        <DrawerSection title="Informasi Pencairan Resign" icon={Banknote}>
                            <DrawerFieldGrid cols={2}>
                                <DrawerField label="Anggota" value={`${data.anggota?.nama ?? '-'} (${data.anggota?.no_karyawan ?? '-'})`} />
                                <DrawerField label="No. Dokumen" value={data.nomor_dokumen || '-'} mono />
                                <DrawerField label="Nominal Cair" value={formatRupiah(data.nominal)} />
                                <DrawerField label="Tanggal Transfer" value={data.tanggal_pencairan || '-'} />
                                <DrawerField label="Sumber Kas" value={data.akun_kas === 'kas_kecil' ? 'Kas Kecil' : 'Bank Koperasi'} />
                                <DrawerField label="Dicairkan Oleh" value={data.dicairkan_oleh || '-'} />
                                <DrawerField
                                    label="Rekening Tujuan"
                                    value={data.rekening ? `${data.rekening.bank} • ${data.rekening.no_rekening} a.n. ${data.rekening.atas_nama}` : 'Tunai / Non-transfer'}
                                />
                                <DrawerField label="Tanggal Resign" value={data.tanggal_resign || '-'} />
                            </DrawerFieldGrid>
                            {data.catatan_pencairan && (
                                <p className="text-xs text-slate-600 mt-3 pt-3 border-t border-slate-100">
                                    <strong>Catatan:</strong> {data.catatan_pencairan}
                                </p>
                            )}
                            <div className="mt-4 pt-3 border-t border-slate-100 flex flex-wrap gap-2">
                                {data.bukti_pencairan_url && (
                                    <a
                                        href={data.bukti_pencairan_url}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-brand-navy text-white text-xs font-semibold hover:opacity-95"
                                    >
                                        <FileText size={14} /> Buka Bukti Transfer <ExternalLink size={12} />
                                    </a>
                                )}
                                {data.slip_resign_url && (
                                    <a
                                        href={data.slip_resign_url}
                                        className="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-slate-100 text-slate-700 border border-slate-200 text-xs font-semibold hover:bg-slate-200/70"
                                    >
                                        <FileText size={14} /> Unduh Slip Resign (PDF)
                                    </a>
                                )}
                            </div>
                        </DrawerSection>
                    </DrawerBody>
                    <DrawerFooter>
                        <DrawerFooterActions cols={1}>
                            <Button type="button" variant="neutral" size="drawer" onClick={onClose}>
                                Tutup
                            </Button>
                        </DrawerFooterActions>
                    </DrawerFooter>
                </>
            );
        }

        const rincianResignRows = [
            { label: 'Alasan Resign', value: data.alasan_resign || '-' },
            ...(data.tagihan_pelunasan > 0
                ? [{ label: 'Offset Pelunasan Pinjaman', value: formatRupiah(data.tagihan_pelunasan), privacy: true }]
                : []),
            ...(data.kembali_pokok > 0
                ? [{ label: 'Kembali Simpanan Pokok', value: formatRupiah(data.kembali_pokok), privacy: true }]
                : []),
            ...(data.kembali_wajib > 0
                ? [{ label: 'Kembali Simpanan Wajib', value: formatRupiah(data.kembali_wajib), privacy: true }]
                : []),
            ...(data.kembali_tabungan > 0
                ? [{ label: `Kembali Tabungan (${data.tabungan_no_rekening ?? '-'})`, value: formatRupiah(data.kembali_tabungan), privacy: true }]
                : []),
            { label: 'Dana Sosial (Hangus)', value: formatRupiah(data.dana_sosial_hangus), tone: 'rose', privacy: true },
        ];

        return (
            <>
                <DrawerBody>
                    <DrawerHero>
                        <div className="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                            <div>
                                <p className="text-xs font-medium text-slate-300">Total Pengembalian Resign</p>
                                <p className="text-2xl sm:text-[28px] font-extrabold tracking-tight tabular-nums mt-0.5 privacy-blur">
                                    {formatRupiah(data.nominal)}
                                </p>
                                <p className="text-xs text-slate-300 italic mt-0.5 privacy-blur">{data.terbilang}</p>
                            </div>
                            <div className="rounded-xl bg-white/10 border border-white/15 px-3 py-2 text-xs sm:text-right shrink-0">
                                <p className="text-slate-300">No. Dokumen &bull; Tgl Resign</p>
                                <p className="font-bold text-white mt-0.5 tabular-nums">
                                    {data.nomor_dokumen || '-'} &bull; {data.tanggal_resign}
                                </p>
                            </div>
                        </div>

                        <div className="flex flex-wrap items-center justify-between gap-3 pt-3.5 mt-3.5 border-t border-white/10">
                            {data.rekening ? (
                                <>
                                    <div className="min-w-0">
                                        <p className="text-[11px] font-medium text-emerald-300 flex items-center gap-1.5">
                                            <Landmark size={12} /> Rekening Tujuan Pengembalian
                                        </p>
                                        <p className="text-sm font-bold text-white mt-0.5">
                                            {data.rekening.bank}{' '}
                                            <span className="font-mono tracking-wide">{data.rekening.no_rekening}</span>
                                        </p>
                                        <p className="text-xs text-slate-300">a.n. {data.rekening.atas_nama}</p>
                                    </div>
                                    {data.rekening.no_rekening && data.rekening.no_rekening !== '-' && (
                                        <button
                                            type="button"
                                            onClick={() => salinRekening(data.rekening.no_rekening)}
                                            className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white/15 hover:bg-white/25 border border-white/20 text-xs font-semibold text-white transition-colors shrink-0"
                                        >
                                            {salinBerhasil ? <Check size={13} className="text-emerald-300" /> : <Copy size={13} />}
                                            {salinBerhasil ? 'Tersalin' : 'Salin No. Rek'}
                                        </button>
                                    )}
                                </>
                            ) : (
                                <p className="text-xs text-slate-300">
                                    Belum ada rekening bank tercatat. Gunakan <strong>Kas Kecil</strong> jika dibayar tunai atau transfer sesuai konfirmasi anggota.
                                </p>
                            )}
                        </div>
                    </DrawerHero>

                    <DrawerSection title="Upload Bukti & Tanggal Transfer" icon={Banknote} tone="emerald">
                        <form id={formId} onSubmit={submitResign} className="space-y-3.5">
                            {resignForm.errors.keputusan && (
                                <div className="rounded-xl bg-rose-50 border border-rose-200 px-3.5 py-2.5 text-sm font-medium text-rose-700">
                                    {resignForm.errors.keputusan}
                                </div>
                            )}

                            <FormField
                                label="Bukti Transfer (JPG, PNG, WEBP, atau PDF, maks 5 MB)"
                                error={resignForm.errors.bukti_pencairan}
                                hint={resignForm.data.bukti_pencairan ? `File siap diunggah: ${resignForm.data.bukti_pencairan.name}` : 'Wajib unggah bukti transfer sebelum konfirmasi pencairan resign.'}
                                required
                            >
                                <div className="flex items-center gap-2 rounded-xl border border-dashed border-emerald-300/90 bg-emerald-50/35 px-3 py-2.5 focus-within:border-brand-green focus-within:ring-[3px] focus-within:ring-brand-green/15 transition-all">
                                    <Upload size={16} className="text-brand-green-dark shrink-0" />
                                    <input
                                        data-autofocus
                                        type="file"
                                        accept=".jpg,.jpeg,.png,.webp,.pdf"
                                        onChange={(e) => resignForm.setData('bukti_pencairan', e.target.files?.[0] ?? null)}
                                        className="block w-full text-xs text-slate-600 focus:outline-none file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-brand-navy file:text-white hover:file:opacity-90 cursor-pointer"
                                        required
                                    />
                                </div>
                            </FormField>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <FormField label="Sumber Kas Fisik" error={resignForm.errors.akun_kas} required>
                                    <Select value={resignForm.data.akun_kas} onChange={(e) => resignForm.setData('akun_kas', e.target.value)}>
                                        <option value="bank">Bank ({formatRupiah(saldoBank)})</option>
                                        <option value="kas_kecil">Kas Kecil ({formatRupiah(saldoKasKecil)})</option>
                                    </Select>
                                </FormField>

                                <FormField label="Tanggal Transfer / Pencairan" error={resignForm.errors.tanggal_pencairan} required>
                                    <TextField
                                        type="date"
                                        max={hariIni}
                                        value={resignForm.data.tanggal_pencairan}
                                        onChange={(e) => resignForm.setData('tanggal_pencairan', e.target.value)}
                                        required
                                    />
                                </FormField>
                            </div>

                            <FormField label="Catatan Pencairan (Opsional)" error={resignForm.errors.catatan}>
                                <TextField
                                    as="textarea"
                                    rows={2}
                                    value={resignForm.data.catatan}
                                    onChange={(e) => resignForm.setData('catatan', e.target.value)}
                                    placeholder="Contoh: Transfer pengembalian simpanan & tabungan resign."
                                />
                            </FormField>
                        </form>
                    </DrawerSection>

                    <DrawerSection title="Rincian Penyelesaian Resign" icon={User}>
                        <DrawerKeyValue rows={rincianResignRows} />
                        {data.slip_resign_url && (
                            <div className="mt-3 pt-3 border-t border-slate-100">
                                <a
                                    href={data.slip_resign_url}
                                    className="inline-flex items-center gap-1.5 text-xs font-semibold text-brand-navy hover:underline"
                                >
                                    <FileText size={14} /> Unduh Slip Resign (PDF)
                                </a>
                            </div>
                        )}
                    </DrawerSection>
                </DrawerBody>

                <DrawerFooter>
                    <DrawerFooterActions cols={2} stackOnMobile>
                        <Button type="button" variant="neutral" size="drawer" onClick={onClose} disabled={resignForm.processing}>
                            Batal
                        </Button>
                        <Button
                            type="submit"
                            form={formId}
                            size="drawer"
                            disabled={resignForm.processing || !resignForm.data.bukti_pencairan}
                        >
                            <CheckCircle2 size={16} />
                            {resignForm.processing ? 'Memproses...' : 'Konfirmasi Cair & Kirim WA'}
                        </Button>
                    </DrawerFooterActions>
                </DrawerFooter>
            </>
        );
    }

    if (!isTabungan) {
        if (!bisaCair) {
            return (
                <>
                    <DrawerBody>
                        <DrawerSection title="Informasi Pencairan" icon={Banknote}>
                            <DrawerFieldGrid cols={2}>
                                <DrawerField label="Anggota" value={`${data.anggota?.nama ?? '-'} (${data.anggota?.no_karyawan ?? '-'})`} />
                                <DrawerField label="No. Dokumen" value={data.nomor_dokumen || '-'} mono />
                                <DrawerField label="Nominal Cair" value={formatRupiah(data.nominal)} />
                                <DrawerField label="Tanggal Transfer" value={data.tanggal_pencairan || '-'} />
                                <DrawerField label="Dicairkan Oleh" value={data.dicairkan_oleh || '-'} />
                                <DrawerField label="Rekening Tujuan" value={`${data.rekening?.bank ?? '-'} • ${data.rekening?.no_rekening ?? '-'}`} />
                            </DrawerFieldGrid>
                            {data.catatan_pencairan && (
                                <p className="text-xs text-slate-600 mt-3 pt-3 border-t border-slate-100">
                                    <strong>Catatan:</strong> {data.catatan_pencairan}
                                </p>
                            )}
                            {data.bukti_pencairan_url && (
                                <div className="mt-4 pt-3 border-t border-slate-100">
                                    <a
                                        href={data.bukti_pencairan_url}
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
                            <Button type="button" variant="neutral" size="drawer" onClick={onClose}>
                                Tutup
                            </Button>
                        </DrawerFooterActions>
                    </DrawerFooter>
                </>
            );
        }

        return (
            <>
                <DrawerBody>
                    <DrawerHero>
                        <div className="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                            <div>
                                <p className="text-xs font-medium text-slate-300">Nominal Disetujui untuk Ditransfer</p>
                                <p className="text-2xl sm:text-[28px] font-extrabold tracking-tight tabular-nums mt-0.5 privacy-blur">
                                    {formatRupiah(data.nominal_disetujui ?? data.nominal)}
                                </p>
                                <p className="text-xs text-slate-300 italic mt-0.5 privacy-blur">{data.terbilang}</p>
                            </div>
                            <div className="rounded-xl bg-white/10 border border-white/15 px-3 py-2 text-xs sm:text-right shrink-0">
                                <p className="text-slate-300">Tenor Final &bull; Bunga</p>
                                <p className="font-bold text-white mt-0.5 tabular-nums">
                                    {data.tenor_disetujui ?? data.tenor_bulan} bln &bull; {data.persentase_bunga}% / bln
                                </p>
                            </div>
                        </div>

                        <div className="flex flex-wrap items-center justify-between gap-3 pt-3.5 mt-3.5 border-t border-white/10">
                            <div className="min-w-0">
                                <p className="text-[11px] font-medium text-emerald-300 flex items-center gap-1.5">
                                    <Landmark size={12} /> Rekening Tujuan Transfer Anggota
                                </p>
                                <p className="text-sm font-bold text-white mt-0.5">
                                    {data.rekening?.bank}{' '}
                                    <span className="font-mono tracking-wide">{data.rekening?.no_rekening}</span>
                                </p>
                                <p className="text-xs text-slate-300">a.n. {data.rekening?.atas_nama}</p>
                            </div>
                            <button
                                type="button"
                                onClick={() => salinRekening(data.rekening?.no_rekening)}
                                className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white/15 hover:bg-white/25 border border-white/20 text-xs font-semibold text-white transition-colors shrink-0"
                            >
                                {salinBerhasil ? <Check size={13} className="text-emerald-300" /> : <Copy size={13} />}
                                {salinBerhasil ? 'Tersalin' : 'Salin No. Rek'}
                            </button>
                        </div>
                    </DrawerHero>

                    <DrawerSection title="Upload Bukti & Tanggal Transfer" icon={Banknote} tone="emerald">
                        <form id={formId} onSubmit={submitPinjaman} className="space-y-3.5">
                            {pinjamanForm.errors.keputusan && (
                                <div className="rounded-xl bg-rose-50 border border-rose-200 px-3.5 py-2.5 text-sm font-medium text-rose-700">
                                    {pinjamanForm.errors.keputusan}
                                </div>
                            )}

                            <FormField
                                label="Bukti Transfer (JPG, PNG, WEBP, atau PDF, maks 5 MB)"
                                error={pinjamanForm.errors.bukti_pencairan}
                                hint={pinjamanForm.data.bukti_pencairan ? `File siap diunggah: ${pinjamanForm.data.bukti_pencairan.name}` : 'Wajib unggah bukti transfer sebelum konfirmasi pencairan.'}
                                required
                            >
                                <div className="flex items-center gap-2 rounded-xl border border-dashed border-emerald-300/90 bg-emerald-50/35 px-3 py-2.5 focus-within:border-brand-green focus-within:ring-[3px] focus-within:ring-brand-green/15 transition-all">
                                    <Upload size={16} className="text-brand-green-dark shrink-0" />
                                    <input
                                        data-autofocus
                                        type="file"
                                        accept=".jpg,.jpeg,.png,.webp,.pdf"
                                        onChange={(e) => pinjamanForm.setData('bukti_pencairan', e.target.files?.[0] ?? null)}
                                        className="block w-full text-xs text-slate-600 focus:outline-none file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-brand-navy file:text-white hover:file:opacity-90 cursor-pointer"
                                        required
                                    />
                                </div>
                            </FormField>

                            <FormField label="Tanggal Transfer / Pencairan" error={pinjamanForm.errors.tanggal_pencairan} required>
                                <TextField
                                    type="date"
                                    max={hariIni}
                                    value={pinjamanForm.data.tanggal_pencairan}
                                    onChange={(e) => pinjamanForm.setData('tanggal_pencairan', e.target.value)}
                                    required
                                />
                                <p className="text-xs text-slate-500 mt-1.5 inline-flex items-center gap-1.5">
                                    <CalendarDays size={13} className="text-brand-green-dark shrink-0" />
                                    Cicilan ke-1 jatuh tempo akhir bulan berikutnya: <strong className="text-slate-700">{jatuhTempoPertamaLabel}</strong>
                                </p>
                            </FormField>

                            <FormField label="Catatan Pencairan (Opsional)" error={pinjamanForm.errors.catatan}>
                                <TextField
                                    as="textarea"
                                    rows={2}
                                    value={pinjamanForm.data.catatan}
                                    onChange={(e) => pinjamanForm.setData('catatan', e.target.value)}
                                    placeholder="Contoh: Transfer via Bank Mandiri akhir bulan November."
                                />
                            </FormField>
                        </form>
                    </DrawerSection>

                    {data.catatan_ketua && (
                        <DrawerNoteCallout
                            label="Catatan Persetujuan Ketua"
                            note={data.catatan_ketua}
                            timestamp={data.disetujui_ketua_pada ?? data.tanggal_pengajuan}
                            tone="emerald"
                            icon={MessageSquareText}
                        />
                    )}

                    <DrawerSection title="Informasi Pengajuan" icon={User}>
                        <DrawerFieldGrid cols={2}>
                            <DrawerField label="Nama Anggota" value={data.anggota?.nama} />
                            <DrawerField label="Disetujui Ketua" value={data.disetujui_ketua_pada ?? data.tanggal_pengajuan} />
                        </DrawerFieldGrid>
                    </DrawerSection>
                </DrawerBody>

                <DrawerFooter>
                    <DrawerFooterActions cols={2} stackOnMobile>
                        <Button type="button" variant="neutral" size="drawer" onClick={onClose} disabled={pinjamanForm.processing}>
                            Batal
                        </Button>
                        <Button
                            type="submit"
                            form={formId}
                            size="drawer"
                            disabled={pinjamanForm.processing || !pinjamanForm.data.bukti_pencairan}
                        >
                            <CheckCircle2 size={16} />
                            {pinjamanForm.processing ? 'Memproses...' : 'Konfirmasi Cair & Kirim WA'}
                        </Button>
                    </DrawerFooterActions>
                </DrawerFooter>
            </>
        );
    }

    return (
        <form onSubmit={submitTabungan} className="flex-1 min-h-0 flex flex-col">
            <DrawerBody>
                <DrawerSection title="Ringkasan Transfer" icon={Banknote} tone="emerald">
                    <div className="flex items-start justify-between gap-3">
                        <div>
                            <p className="text-[11px] font-medium text-slate-500">Nominal Cair</p>
                            <p className="text-xl sm:text-2xl font-extrabold text-rose-600 tabular-nums privacy-blur mt-0.5">
                                {formatRupiah(data.nominal)}
                            </p>
                            <p className="text-xs text-slate-500 mt-0.5">
                                Saldo rekening saat ini: <span className="font-semibold text-slate-700 privacy-blur">{formatRupiah(data.saldo_rekening)}</span>
                            </p>
                        </div>
                        <div className="text-right text-xs text-slate-500">
                            <p className="font-semibold text-slate-700">{data.anggota?.no_karyawan}</p>
                            <p className="mt-0.5 capitalize">Kanal: {data.kanal}</p>
                        </div>
                    </div>

                    {data.rekening_tujuan && (
                        <div className="mt-3 pt-3 border-t border-emerald-100/80 flex items-center justify-between gap-3">
                            <div className="min-w-0">
                                <p className="text-[11px] font-medium text-slate-500 flex items-center gap-1">
                                    <Landmark size={12} /> Rekening Tujuan Transfer
                                </p>
                                <p className="text-sm font-bold text-slate-800 mt-0.5">
                                    {data.rekening_tujuan.nama_bank}{' '}
                                    <span className="font-mono text-brand-navy">{data.rekening_tujuan.no_rekening}</span>
                                </p>
                                <p className="text-xs text-slate-500">a.n. {data.rekening_tujuan.atas_nama}</p>
                            </div>
                            <Button
                                type="button"
                                size="sm"
                                variant="neutral"
                                onClick={() => salinRekening(data.rekening_tujuan.no_rekening)}
                            >
                                {salinBerhasil ? <Check size={14} /> : <Copy size={14} />}
                                {salinBerhasil ? 'Tersalin' : 'Salin'}
                            </Button>
                        </div>
                    )}
                </DrawerSection>

                {bisaCair && (
                    showTolakTabungan ? (
                        <DrawerSection title="Alasan Penolakan" tone="rose">
                            <FormField label="Alasan Penolakan (wajib)" error={tabunganForm.errors.catatan || tabunganForm.errors.aksi} required>
                                <TextField
                                    ref={tabunganCatatanRef}
                                    data-autofocus
                                    as="textarea"
                                    rows={3}
                                    value={tabunganForm.data.catatan}
                                    onChange={(e) => tabunganForm.setData('catatan', e.target.value)}
                                    placeholder="Mis. Rekening tujuan bermasalah / ditolak bank"
                                    required
                                />
                            </FormField>
                        </DrawerSection>
                    ) : (
                        <DrawerSection title="Bukti Transfer & Pencairan" icon={Wallet}>
                            <FormField
                                label="Bukti Transfer / Pencairan (Wajib)"
                                error={tabunganForm.errors.bukti_pencairan || tabunganForm.errors.aksi}
                                hint={tabunganForm.data.bukti_pencairan ? `File dipilih: ${tabunganForm.data.bukti_pencairan.name}` : 'Format JPG, PNG, WEBP, atau PDF (maks. 5 MB)'}
                                required
                            >
                                <input
                                    data-autofocus
                                    type="file"
                                    accept=".jpg,.jpeg,.png,.webp,.pdf"
                                    onChange={(e) => tabunganForm.setData('bukti_pencairan', e.target.files?.[0] ?? null)}
                                    className="block w-full text-xs text-slate-600 rounded-xl border border-dashed border-slate-300 bg-slate-50/70 px-3 py-2 focus:outline-none focus:border-brand-green focus:ring-[3px] focus:ring-brand-green/15 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-brand-navy file:text-white cursor-pointer"
                                    required
                                />
                            </FormField>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <FormField label="Sumber Kas Fisik" error={tabunganForm.errors.akun_kas} required>
                                    <Select value={tabunganForm.data.akun_kas} onChange={(e) => tabunganForm.setData('akun_kas', e.target.value)}>
                                        <option value="bank">Bank ({formatRupiah(saldoBank)})</option>
                                        <option value="kas_kecil">Kas Kecil ({formatRupiah(saldoKasKecil)})</option>
                                    </Select>
                                </FormField>

                                <FormField label="Tanggal Pencairan" error={tabunganForm.errors.tanggal_pencairan} required>
                                    <TextField
                                        type="date"
                                        max={hariIni}
                                        value={tabunganForm.data.tanggal_pencairan}
                                        onChange={(e) => tabunganForm.setData('tanggal_pencairan', e.target.value)}
                                        required
                                    />
                                </FormField>
                            </div>

                            <FormField label="Catatan Pencairan (Opsional)" error={tabunganForm.errors.catatan}>
                                <TextField
                                    as="textarea"
                                    rows={2}
                                    value={tabunganForm.data.catatan}
                                    onChange={(e) => tabunganForm.setData('catatan', e.target.value)}
                                    placeholder="Nomor referensi transfer atau catatan tambahan"
                                />
                            </FormField>
                        </DrawerSection>
                    )
                )}

                <DrawerSection title="Jejak Persetujuan 3 Tahap">
                    <DrawerKeyValue
                        rows={[
                            { label: '1. Sekretaris', value: `${data.admin_oleh ?? '-'}${data.catatan_admin ? ` — ${data.catatan_admin}` : ''}` },
                            { label: '2. Bendahara', value: `${data.bendahara_oleh ?? '-'}${data.catatan_bendahara ? ` — ${data.catatan_bendahara}` : ''}` },
                            { label: '3. Ketua Koperasi', value: `${data.ketua_oleh ?? '-'} (${data.disetujui_ketua_pada ?? '-'})${data.catatan_ketua ? ` — ${data.catatan_ketua}` : ''}` },
                        ]}
                    />
                </DrawerSection>
            </DrawerBody>

            <DrawerFooter>
                {bisaCair ? (
                    showTolakTabungan ? (
                        <DrawerFooterActions cols={2} stackOnMobile>
                            <Button type="button" variant="neutral" size="drawer" onClick={() => setShowTolakTabungan(false)}>Kembali</Button>
                            <Button type="submit" variant="danger" size="drawer" disabled={tabunganForm.processing}>
                                {tabunganForm.processing ? 'Menolak...' : 'Konfirmasi Tolak'}
                            </Button>
                        </DrawerFooterActions>
                    ) : (
                        <DrawerFooterActions cols={2} stackOnMobile>
                            <Button type="button" variant="dangerSoft" size="drawer" onClick={() => setShowTolakTabungan(true)}>
                                <XCircle size={15} /> Batalkan / Tolak
                            </Button>
                            <Button type="submit" size="drawer" disabled={tabunganForm.processing || !tabunganForm.data.bukti_pencairan}>
                                <CheckCircle2 size={15} />{' '}
                                {tabunganForm.processing
                                    ? 'Mencairkan...'
                                    : data.jenis === 'tutup_rekening'
                                        ? 'Cairkan & Tutup Rekening'
                                        : 'Simpan Bukti & Cairkan'}
                            </Button>
                        </DrawerFooterActions>
                    )
                ) : (
                    <div className="flex items-center justify-between gap-3">
                        <StatusBadge status={data.status} />
                        <Button type="button" variant="neutral" size="drawer" onClick={onClose}>
                            Tutup
                        </Button>
                    </div>
                )}
            </DrawerFooter>
        </form>
    );
}
