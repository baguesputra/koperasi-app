import { useForm } from '@inertiajs/react';
import {
    ArrowDownCircle, ArrowUpCircle, CheckCircle2, ClipboardCheck, ExternalLink, FileText, Landmark, UserCheck, XCircle,
} from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import Button from '@/Components/ui/Button';
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
import StatusBadge from '@/Components/ui/StatusBadge';
import TextField from '@/Components/ui/TextField';
import { formatRupiah } from '@/Utils/formatCurrency';
import { withIdempotencyKey } from '@/Utils/idempotency';

export default function TabunganDrawers({ item, onClose }) {
    const { jenis_kode: jenisKode, tahap, detail: data } = item;
    const [showTolakMode, setShowTolakMode] = useState(false);
    const catatanRef = useRef(null);
    const form = useForm({ catatan: '' });

    useEffect(() => {
        if (!showTolakMode) return undefined;
        const id = window.requestAnimationFrame(() => {
            catatanRef.current?.focus({ preventScroll: false });
        });
        return () => window.cancelAnimationFrame(id);
    }, [showTolakMode]);

    const isBuka = jenisKode === 'tabungan_buka';
    const isSetor = jenisKode === 'tabungan_setor';

    const bisaDiproses = isBuka
        ? (
            (tahap === 'admin' && data.status === 'diajukan')
            || (tahap === 'bendahara' && data.status === 'approved_admin')
            || (tahap === 'ketua' && data.status === 'approved_bendahara')
        )
        : isSetor
            ? data.status === 'menunggu'
            : (
                (tahap === 'admin' && data.status === 'menunggu')
                || (tahap === 'bendahara' && data.status === 'approved_admin')
                || (tahap === 'ketua' && data.status === 'approved_bendahara')
            );

    function targetRoute(isApprove) {
        if (isBuka) {
            if (tahap === 'admin') return route(isApprove ? 'admin.tabungan.buka.approve' : 'admin.tabungan.buka.reject', data.id);
            if (tahap === 'bendahara') return route(isApprove ? 'bendahara.tabungan.buka.setujui' : 'bendahara.tabungan.buka.tolak', data.id);
            return route(isApprove ? 'ketua.tabungan.buka.approve' : 'ketua.tabungan.buka.reject', data.id);
        }
        if (tahap === 'admin') return route(isApprove ? 'admin.tabungan.pengajuan.approve' : 'admin.tabungan.pengajuan.reject', data.id);
        if (tahap === 'bendahara') return route(isApprove ? 'bendahara.tabungan.pengajuan.setujui' : 'bendahara.tabungan.pengajuan.tolak', data.id);
        return route(isApprove ? 'ketua.tabungan.pengajuan.approve' : 'ketua.tabungan.pengajuan.reject', data.id);
    }

    function submit(e) {
        e.preventDefault();
        if (!bisaDiproses) return;
        form.post(
            targetRoute(!showTolakMode),
            withIdempotencyKey({ preserveScroll: true, onSuccess: onClose })
        );
    }

    const labelTahap = tahap === 'admin' ? 'Sekretaris' : tahap === 'bendahara' ? 'Bendahara' : 'Ketua';
    const placeholderCatatan = showTolakMode
        ? (isSetor ? 'Mis. Nominal tidak sesuai mutasi bank / bukti buram' : 'Tuliskan alasan penolakan (min. 3 karakter)...')
        : tahap === 'admin'
            ? 'Catatan verifikasi data anggota...'
            : tahap === 'bendahara'
                ? 'Catatan tinjauan untuk Ketua Koperasi...'
                : 'Catatan persetujuan Ketua...';

    const labelSetuju = isBuka
        ? (tahap === 'admin' ? 'Lolos & Teruskan ke Bendahara' : tahap === 'bendahara' ? 'Setujui & Teruskan ke Ketua' : 'Setujui & Aktifkan Rekening')
        : isSetor
            ? 'Setujui & Tambah Saldo'
            : (tahap === 'admin' ? 'Lolos & Teruskan ke Bendahara' : tahap === 'bendahara' ? 'Setujui & Teruskan ke Ketua' : 'Setujui & Teruskan Pencairan');

    return (
        <form onSubmit={submit} className="flex-1 min-h-0 flex flex-col">
            <DrawerBody>
                {isBuka ? (
                    <DrawerSection title="Profil Pemohon" icon={UserCheck}>
                        <DrawerFieldGrid cols={2}>
                            <DrawerField label="Nama Lengkap" value={data.anggota?.nama} />
                            <DrawerField label="No. Karyawan" value={data.anggota?.no_karyawan} mono />
                            <DrawerField label="Cabang" value={data.anggota?.cabang} />
                            <DrawerField label="Tgl Pengajuan" value={data.tanggal_pengajuan} />
                            <DrawerField label="Tarif Berlaku" value={`${data.suku_bunga_saat_buka}% / tahun`} />
                            <DrawerField label="Versi S&K Disetujui" value={data.versi_syarat} mono />
                        </DrawerFieldGrid>
                    </DrawerSection>
                ) : (
                    <>
                        <DrawerSection
                            title="Ringkasan Transaksi"
                            icon={isSetor ? ArrowDownCircle : ArrowUpCircle}
                            tone={isSetor ? 'emerald' : 'amber'}
                        >
                            <DrawerKeyValue
                                rows={[
                                    { label: 'Anggota', value: `${data.anggota?.nama} (${data.anggota?.no_karyawan})` },
                                    { label: 'No. Rekening', value: data.no_rekening, mono: true },
                                    ...(data.tanggal_buka ? [{ label: 'Tanggal Buka Rekening', value: data.tanggal_buka }] : []),
                                    { label: 'Saldo Rekening Saat Ini', value: formatRupiah(data.saldo_rekening), privacy: true },
                                    {
                                        label: isSetor ? 'Nominal Setor' : 'Nominal Diajukan',
                                        value: formatRupiah(data.nominal),
                                        tone: isSetor ? 'emerald' : 'rose',
                                        emphasize: true,
                                        privacy: true,
                                    },
                                    { label: 'Kanal', value: data.kanal },
                                    ...(data.tanggal_transaksi ? [{ label: 'Tanggal', value: data.tanggal_transaksi }] : []),
                                    ...(data.catatan_anggota ? [{ label: 'Catatan Anggota', value: data.catatan_anggota }] : []),
                                ]}
                            />
                        </DrawerSection>

                        {isSetor && data.bukti_url && (
                            <DrawerSection
                                title="Bukti Transfer Anggota"
                                icon={FileText}
                                action={
                                    <a
                                        href={data.bukti_url}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="inline-flex items-center gap-1 text-xs font-semibold text-brand-navy hover:underline"
                                    >
                                        Buka Tab Baru <ExternalLink size={12} />
                                    </a>
                                }
                            >
                                {data.bukti_mime?.includes('pdf') ? (
                                    <iframe
                                        src={data.bukti_url}
                                        title="Bukti Transfer PDF"
                                        className="w-full h-72 rounded-lg border border-slate-200 bg-slate-50"
                                    />
                                ) : (
                                    <img
                                        src={data.bukti_url}
                                        alt="Bukti Transfer Setoran"
                                        className="w-full max-h-80 object-contain rounded-lg border border-slate-200 bg-slate-50"
                                    />
                                )}
                            </DrawerSection>
                        )}
                    </>
                )}

                {bisaDiproses && (
                    <DrawerSection
                        title={showTolakMode ? 'Alasan Penolakan' : `Catatan ${labelTahap} (Opsional)`}
                        tone={showTolakMode ? 'rose' : 'default'}
                    >
                        <FormField label={showTolakMode ? 'Alasan Penolakan (wajib)' : 'Catatan'} error={form.errors.catatan || form.errors.aksi} required={showTolakMode}>
                            <TextField
                                ref={catatanRef}
                                data-autofocus
                                as="textarea"
                                rows={2}
                                value={form.data.catatan}
                                onChange={(e) => form.setData('catatan', e.target.value)}
                                placeholder={placeholderCatatan}
                                required={showTolakMode}
                            />
                        </FormField>
                    </DrawerSection>
                )}

                {!isBuka && !isSetor && data.rekening_tujuan && (
                    <DrawerSection title="Rekening Tujuan Transfer Anggota" icon={Landmark}>
                        <DrawerKeyValue
                            rows={[
                                { label: 'Bank', value: data.rekening_tujuan.nama_bank },
                                { label: 'No. Rekening', value: data.rekening_tujuan.no_rekening, mono: true },
                                { label: 'Atas Nama', value: data.rekening_tujuan.atas_nama },
                            ]}
                        />
                    </DrawerSection>
                )}

                {(data.admin_oleh || data.bendahara_oleh) && (
                    <DrawerSection title="Jejak Verifikasi Sebelumnya" icon={ClipboardCheck}>
                        <DrawerKeyValue
                            rows={[
                                ...(data.admin_oleh ? [
                                    { label: 'Sekretaris', value: `${data.admin_oleh} (${data.disetujui_admin_pada ?? '-'})` },
                                    { label: 'Catatan Sekretaris', value: data.catatan_admin || '-' },
                                ] : []),
                                ...(data.bendahara_oleh ? [
                                    { label: 'Bendahara', value: `${data.bendahara_oleh} (${data.disetujui_bendahara_pada ?? '-'})` },
                                    { label: 'Catatan Bendahara', value: data.catatan_bendahara || '-' },
                                ] : []),
                            ]}
                        />
                    </DrawerSection>
                )}
            </DrawerBody>

            <DrawerFooter>
                {bisaDiproses ? (
                    showTolakMode ? (
                        <DrawerFooterActions cols={2} stackOnMobile>
                            <Button type="button" variant="neutral" size="drawer" onClick={() => setShowTolakMode(false)}>Kembali</Button>
                            <Button type="submit" variant="danger" size="drawer" disabled={form.processing}>
                                {form.processing ? 'Menolak...' : 'Konfirmasi Tolak'}
                            </Button>
                        </DrawerFooterActions>
                    ) : (
                        <DrawerFooterActions cols={2} stackOnMobile>
                            <Button type="button" variant="dangerSoft" size="drawer" onClick={() => setShowTolakMode(true)}>
                                <XCircle size={15} /> Tolak
                            </Button>
                            <Button type="submit" size="drawer" disabled={form.processing}>
                                <CheckCircle2 size={15} /> {form.processing ? 'Memproses...' : labelSetuju}
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
