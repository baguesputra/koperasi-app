import { useForm } from '@inertiajs/react';
import { useEffect, useId, useRef, useState } from 'react';
import {
    AlertCircle,
    ArrowLeft,
    Check,
    CheckCircle2,
    ChevronDown,
    ChevronUp,
    FileText,
    Layers,
    MessageSquareText,
    User,
    Wallet,
    X,
    XCircle,
} from 'lucide-react';
import Button from '@/Components/ui/Button';
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
import StatusBadge from '@/Components/ui/StatusBadge';
import { formatRupiah } from '@/Utils/formatCurrency';
import { withIdempotencyKey } from '@/Utils/idempotency';

const tipeLabel = {
    percepat: 'Percepat Pelunasan',
    perpanjang: 'Perpanjang Tenor',
    lunas_total: 'Lunas Sekarang',
};

export default function KeputusanDrawer({ pengajuan, onClose }) {
    const [aksi, setAksi] = useState(null);
    const [showTable, setShowTable] = useState(true);
    const formId = useId();
    const formRef = useRef(null);
    const { data, setData, post, processing, errors } = useForm({ catatan: '' });

    useEffect(() => {
        if (aksi && formRef.current) {
            formRef.current.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }, [aksi]);

    function submit(e) {
        e.preventDefault();
        const url = aksi === 'approve'
            ? route('bendahara.percepatan.approve', pengajuan.id)
            : route('bendahara.percepatan.reject', pengajuan.id);
        post(url, withIdempotencyKey({
            preserveScroll: true,
            onSuccess: () => onClose(),
        }));
    }

    const bisaDiproses = pengajuan.status === 'diajukan';
    const badgeStatus = pengajuan.status === 'aktif' ? 'disetujui' : pengajuan.status;

    const tipe = tipeLabel[pengajuan.tipe] ?? pengajuan.tipe;
    const preview = pengajuan.preview;
    const angsuranBaru = pengajuan.angsuran_baru;

    return (
        <>
            <DrawerBody>
                <DrawerHero>
                    <div className="flex items-start justify-between gap-3">
                        <div>
                            <p className="text-[11px] font-medium text-slate-300">Jenis Pengajuan</p>
                            <p className="text-xl sm:text-2xl font-bold text-white mt-0.5">{tipe}</p>
                        </div>
                        <span className="inline-flex items-center px-2.5 py-1 rounded-full bg-white/10 border border-white/15 text-xs font-semibold text-slate-200 shrink-0 tabular-nums">
                            Sisa {pengajuan.pinjaman.sisa_angsuran}x angsuran
                        </span>
                    </div>

                    <div className="grid grid-cols-3 gap-3 pt-3.5 mt-3.5 border-t border-white/10">
                        <div>
                            <p className="text-[11px] text-slate-300">Tenor Saat Ini</p>
                            <p className="text-sm sm:text-base font-bold mt-0.5 tabular-nums">{pengajuan.tenor_lama} bulan</p>
                        </div>
                        <div>
                            <p className="text-[11px] text-slate-300">Tenor Diminta</p>
                            <p className="text-sm sm:text-base font-bold text-emerald-300 mt-0.5 tabular-nums">
                                {pengajuan.tenor_baru ? `${pengajuan.tenor_baru} bulan` : 'Lunas Sekarang'}
                            </p>
                        </div>
                        <div>
                            <p className="text-[11px] text-slate-300">Tgl Pengajuan</p>
                            <p className="text-sm sm:text-base font-bold mt-0.5">{pengajuan.tanggal_pengajuan}</p>
                        </div>
                    </div>
                </DrawerHero>

                {pengajuan.catatan_bendahara && (
                    <DrawerNoteCallout
                        label="Catatan Bendahara"
                        note={pengajuan.catatan_bendahara}
                        tone="indigo"
                        icon={MessageSquareText}
                    />
                )}

                <DrawerSection title="Data Anggota" icon={User}>
                    <DrawerFieldGrid cols={3}>
                        <DrawerField label="Nama" value={pengajuan.anggota.nama} />
                        <DrawerField label="No. Karyawan" value={pengajuan.anggota.no_karyawan} mono />
                        {pengajuan.anggota.cabang && (
                            <DrawerField label="Cabang" value={pengajuan.anggota.cabang} />
                        )}
                        {pengajuan.anggota.jabatan && (
                            <DrawerField label="Jabatan" value={pengajuan.anggota.jabatan === 'hod' ? 'HOD' : 'Staff'} />
                        )}
                        {pengajuan.anggota.lama_keanggotaan_tahun && (
                            <DrawerField label="Lama Keanggotaan" value={`${pengajuan.anggota.lama_keanggotaan_tahun} tahun`} />
                        )}
                        <DrawerField label="Sisa Angsuran" value={`${pengajuan.pinjaman.sisa_angsuran}x`} />
                    </DrawerFieldGrid>
                </DrawerSection>

                <DrawerSection title="Alasan Pengajuan" icon={FileText}>
                    <p className="text-sm text-slate-700 leading-relaxed">{pengajuan.keterangan || '-'}</p>
                </DrawerSection>

                {preview && (
                    <DrawerSection
                        title="Simulasi Perubahan"
                        description="Perhitungan bunga menurun (declining balance)"
                        icon={Layers}
                        padding="none"
                    >
                        <div className="grid grid-cols-1 sm:grid-cols-3 divide-y sm:divide-y-0 sm:divide-x divide-slate-100">
                            <div className="p-3.5 sm:px-4">
                                <p className="text-[11px] font-medium text-slate-500">Sisa Pokok</p>
                                <p className="text-base font-bold text-slate-900 tabular-nums mt-0.5 privacy-blur">
                                    {formatRupiah(preview.sisa_pokok)}
                                </p>
                            </div>
                            {preview.bunga !== undefined && (
                                <div className="p-3.5 sm:px-4 bg-amber-50/35">
                                    <p className="text-[11px] font-medium text-amber-800">Bunga 1 Bulan</p>
                                    <p className="text-base font-bold text-amber-700 tabular-nums mt-0.5 privacy-blur">
                                        {formatRupiah(preview.bunga)}
                                    </p>
                                </div>
                            )}
                            {preview.total_bayar && (
                                <div className="p-3.5 sm:px-4 bg-emerald-50/35">
                                    <p className="text-[11px] font-medium text-emerald-800">Total Bayar (Lunas Sekarang)</p>
                                    <p className="text-base font-extrabold text-brand-green-dark tabular-nums mt-0.5 privacy-blur">
                                        {formatRupiah(preview.total_bayar)}
                                    </p>
                                </div>
                            )}
                        </div>

                        {preview.jadwal && preview.jadwal.length > 0 && (
                            <div className="border-t border-slate-200/80">
                                <div className="px-4 py-2 bg-[#F8FAFC] border-b border-slate-100">
                                    <p className="text-xs font-semibold text-slate-600">Detail Angsuran Baru</p>
                                </div>
                                <div className="overflow-x-auto">
                                    <table className="w-full text-xs sm:text-sm">
                                        <thead>
                                            <tr className="text-left text-[11px] font-bold uppercase tracking-wider text-slate-500 bg-[#F8FAFC] border-b border-slate-200/80">
                                                <th className="py-2 px-3 w-12 text-center whitespace-nowrap">Ke</th>
                                                <th className="py-2 px-3 text-right whitespace-nowrap">Pokok</th>
                                                <th className="py-2 px-3 text-right whitespace-nowrap">Bunga</th>
                                                <th className="py-2 px-3 text-right whitespace-nowrap">Total Bayar</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-100 bg-white">
                                            {preview.jadwal.map((a) => (
                                                <tr key={a.cicilan_ke} className="hover:bg-[#F2FBF5] transition-colors">
                                                    <td className="py-2 px-3 text-center font-medium text-slate-700 tabular-nums">{a.cicilan_ke}</td>
                                                    <td className="py-2 px-3 text-right text-slate-700 tabular-nums privacy-blur">{formatRupiah(a.nominal_pokok)}</td>
                                                    <td className="py-2 px-3 text-right text-amber-700 tabular-nums privacy-blur">{formatRupiah(a.nominal_bunga)}</td>
                                                    <td className="py-2 px-3 text-right font-semibold text-slate-800 tabular-nums privacy-blur">{formatRupiah(a.total_bayar)}</td>
                                                </tr>
                                            ))}
                                            <tr className="font-bold bg-[#F7F9FC] border-t-2 border-slate-200/90 text-slate-800">
                                                <td className="py-2 px-3">Total</td>
                                                <td className="py-2 px-3 text-right tabular-nums privacy-blur">{formatRupiah(preview.jadwal.reduce((s, a) => s + a.nominal_pokok, 0))}</td>
                                                <td className="py-2 px-3 text-right text-amber-700 tabular-nums privacy-blur">{formatRupiah(preview.jadwal.reduce((s, a) => s + a.nominal_bunga, 0))}</td>
                                                <td className="py-2 px-3 text-right text-brand-navy tabular-nums privacy-blur">{formatRupiah(preview.jadwal.reduce((s, a) => s + a.total_bayar, 0))}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        )}
                    </DrawerSection>
                )}

                {angsuranBaru && angsuranBaru.length > 0 && (
                    <DrawerSection
                        title="Detail Jadwal Angsuran Baru"
                        description={`${angsuranBaru.length} cicilan`}
                        icon={Layers}
                        padding="none"
                        action={
                            <button
                                type="button"
                                onClick={() => setShowTable(!showTable)}
                                className="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 transition-colors"
                            >
                                <span>{showTable ? 'Tutup Jadwal' : 'Lihat Jadwal'}</span>
                                {showTable ? <ChevronUp size={14} aria-hidden="true" /> : <ChevronDown size={14} aria-hidden="true" />}
                            </button>
                        }
                    >
                        {showTable && (
                            <div className="overflow-x-auto">
                                <table className="w-full text-xs sm:text-sm">
                                    <thead>
                                        <tr className="text-left text-[11px] font-bold uppercase tracking-wider text-slate-500 bg-[#F8FAFC] border-b border-slate-200/80">
                                            <th className="py-2 px-3 w-12 text-center whitespace-nowrap">Ke</th>
                                            <th className="py-2 px-3 text-right whitespace-nowrap">Pokok</th>
                                            <th className="py-2 px-3 text-right whitespace-nowrap">Bunga</th>
                                            <th className="py-2 px-3 text-right whitespace-nowrap">Total Bayar</th>
                                            <th className="py-2 px-3 text-center whitespace-nowrap">Jatuh Tempo</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100 bg-white">
                                        {angsuranBaru.map((a) => (
                                            <tr key={a.id} className="hover:bg-[#F2FBF5] transition-colors">
                                                <td className="py-2 px-3 text-center font-medium text-slate-700 tabular-nums">{a.cicilan_ke}</td>
                                                <td className="py-2 px-3 text-right text-slate-700 tabular-nums privacy-blur">{formatRupiah(a.nominal_pokok)}</td>
                                                <td className="py-2 px-3 text-right text-amber-700 tabular-nums privacy-blur">{formatRupiah(a.nominal_bunga)}</td>
                                                <td className="py-2 px-3 text-right font-semibold text-slate-800 tabular-nums privacy-blur">{formatRupiah(a.total_bayar)}</td>
                                                <td className="py-2 px-3 text-center text-slate-600 whitespace-nowrap">
                                                    {a.tanggal_jatuh_tempo ? new Date(a.tanggal_jatuh_tempo).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) : '-'}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                    <tfoot className="bg-[#F7F9FC] border-t-2 border-slate-200/90">
                                        <tr className="font-bold text-slate-800">
                                            <td className="py-2 px-3">Total</td>
                                            <td className="py-2 px-3 text-right tabular-nums privacy-blur">{formatRupiah(angsuranBaru.reduce((sum, a) => sum + a.nominal_pokok, 0))}</td>
                                            <td className="py-2 px-3 text-right text-amber-700 tabular-nums privacy-blur">{formatRupiah(angsuranBaru.reduce((sum, a) => sum + a.nominal_bunga, 0))}</td>
                                            <td className="py-2 px-3 text-right text-brand-navy tabular-nums privacy-blur">{formatRupiah(angsuranBaru.reduce((sum, a) => sum + a.total_bayar, 0))}</td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        )}
                    </DrawerSection>
                )}

                {pengajuan.nominal_final && (
                    <DrawerSection title="Nominal Final Pelunasan" icon={Wallet} tone="emerald">
                        <p className="text-xl font-extrabold text-brand-green-dark tabular-nums privacy-blur">
                            {formatRupiah(pengajuan.nominal_final)}
                        </p>
                    </DrawerSection>
                )}

                {errors.keputusan && (
                    <div className="flex items-start gap-2.5 bg-rose-50/50 border border-rose-200/80 rounded-xl p-3.5">
                        <AlertCircle size={17} className="text-rose-500 shrink-0 mt-0.5" />
                        <p className="text-sm font-medium text-rose-700">{errors.keputusan}</p>
                    </div>
                )}

                {bisaDiproses && aksi && (
                    <DrawerSection
                        ref={formRef}
                        title={aksi === 'approve' ? 'Konfirmasi Persetujuan Perubahan Tenor' : 'Konfirmasi Penolakan'}
                        icon={aksi === 'approve' ? CheckCircle2 : XCircle}
                        tone={aksi === 'reject' ? 'rose' : 'emerald'}
                    >
                        <form id={formId} onSubmit={submit}>
                            <label className="block text-xs sm:text-sm font-semibold text-slate-700 mb-1.5">
                                Catatan {aksi === 'approve' ? 'Persetujuan' : 'Penolakan'}
                            </label>
                            <textarea
                                value={data.catatan}
                                onChange={(e) => setData('catatan', e.target.value)}
                                rows={3}
                                placeholder={aksi === 'approve' ? 'Contoh: Data lengkap, memenuhi syarat.' : 'Contoh: Dokumen belum lengkap.'}
                                className="w-full px-3.5 py-2.5 text-base sm:text-sm rounded-xl border border-slate-300 focus:border-brand-green focus:ring-2 focus:ring-brand-green/20 outline-none transition-colors"
                                autoFocus
                            />
                            {errors.catatan && <p className="text-xs sm:text-sm font-medium text-red-600 mt-1.5">{errors.catatan}</p>}
                        </form>
                    </DrawerSection>
                )}
            </DrawerBody>

            <DrawerFooter>
                {bisaDiproses ? (
                    !aksi ? (
                        <DrawerFooterActions>
                            <Button
                                type="button"
                                variant="dangerSoft"
                                size="drawer"
                                onClick={() => setAksi('reject')}
                            >
                                <X size={16} />
                                Tolak
                            </Button>
                            <Button
                                type="button"
                                variant="primary"
                                size="drawer"
                                onClick={() => setAksi('approve')}
                            >
                                <Check size={16} />
                                Setujui
                            </Button>
                        </DrawerFooterActions>
                    ) : (
                        <DrawerFooterActions>
                            <Button
                                type="button"
                                variant="neutral"
                                size="drawer"
                                onClick={() => setAksi(null)}
                                disabled={processing}
                            >
                                <ArrowLeft size={16} />
                                Batal
                            </Button>
                            <Button
                                type="submit"
                                form={formId}
                                variant={aksi === 'approve' ? 'primary' : 'danger'}
                                size="drawer"
                                disabled={processing}
                            >
                                {aksi === 'approve' ? <Check size={16} /> : <X size={16} />}
                                {processing ? 'Memproses...' : `Konfirmasi ${aksi === 'approve' ? 'Setujui' : 'Tolak'}`}
                            </Button>
                        </DrawerFooterActions>
                    )
                ) : (
                    <div className="flex items-center justify-between gap-3">
                        <StatusBadge status={badgeStatus} />
                        <Button type="button" variant="neutral" size="drawer" onClick={onClose}>
                            Tutup
                        </Button>
                    </div>
                )}
            </DrawerFooter>
        </>
    );
}