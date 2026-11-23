import { useForm } from '@inertiajs/react';
import { useEffect, useId, useRef, useState } from 'react';
import { AlertCircle, ArrowLeft, Check, CheckCircle2, FileText, Landmark, MessageSquareText, User, X, XCircle } from 'lucide-react';
import Button from '@/Components/ui/Button';
import ChipNominal from '@/Components/ui/ChipNominal';
import CurrencyField from '@/Components/ui/CurrencyField';
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
import JejakNominal from '@/Pages/PinjamanApproval/JejakNominal';
import KartuKas from '@/Pages/PinjamanApproval/KartuKas';
import PreviewNominal from '@/Pages/PinjamanApproval/PreviewNominal';
import RincianBebanPinjaman from '@/Pages/PinjamanApproval/RincianBebanPinjaman';

const jabatanLabel = { staff: 'Staff', hod: 'HOD' };

export default function KeputusanDrawer({ pinjaman, onClose }) {
    const [aksi, setAksi] = useState(null);
    const formId = useId();
    const formRef = useRef(null);
    const { data, setData, post, processing, errors } = useForm({
        catatan: '',
        nominal: String(pinjaman.nominal_disetujui_bendahara ?? pinjaman.nominal_diminta ?? pinjaman.nominal),
        tenor_bulan: '',
    });

    const bisaDiproses = pinjaman.status === 'approved_bendahara';
    const nominalFinal = Number(data.nominal) || 0;

    useEffect(() => {
        if (aksi && formRef.current) {
            formRef.current.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }, [aksi]);

    function submit(e) {
        e.preventDefault();
        const url = aksi === 'approve'
            ? route('ketua.pinjaman.approve', pinjaman.id)
            : route('ketua.pinjaman.reject', pinjaman.id);
        post(url, withIdempotencyKey({
            preserveScroll: true,
            onSuccess: () => onClose(),
        }));
    }

    return (
        <>
            <DrawerBody>
                <DrawerHero>
                    <p className="text-xs font-medium text-slate-300">Nominal Pengajuan</p>
                    <p className="text-2xl sm:text-[28px] font-extrabold tracking-tight tabular-nums mt-1 privacy-blur">
                        {formatRupiah(pinjaman.nominal)}
                    </p>
                    <p className="text-xs sm:text-sm text-slate-300 italic mt-1 privacy-blur">{pinjaman.terbilang}</p>

                    <div className="grid grid-cols-3 gap-3 pt-3.5 mt-3.5 border-t border-white/10">
                        <div>
                            <p className="text-[11px] text-slate-300">Tenor</p>
                            <p className="text-sm sm:text-base font-bold mt-0.5 tabular-nums">{pinjaman.tenor_bulan} bulan</p>
                        </div>
                        <div>
                            <p className="text-[11px] text-slate-300">Bunga</p>
                            <p className="text-sm sm:text-base font-bold mt-0.5 tabular-nums">{pinjaman.persentase_bunga}% / bln</p>
                        </div>
                        <div>
                            <p className="text-[11px] text-slate-300">Tgl Pengajuan</p>
                            <p className="text-sm sm:text-base font-bold mt-0.5">{pinjaman.tanggal_pengajuan}</p>
                        </div>
                    </div>
                </DrawerHero>

                {pinjaman.catatan_admin && (
                    <DrawerNoteCallout
                        label="Catatan dari Verifikasi Sekretaris"
                        note={pinjaman.catatan_admin}
                        tone="sky"
                        icon={MessageSquareText}
                    />
                )}

                {pinjaman.catatan_bendahara && (
                    <DrawerNoteCallout
                        label="Disetujui Bendahara — Catatan Bendahara"
                        note={pinjaman.catatan_bendahara}
                        tone="amber"
                        icon={CheckCircle2}
                    />
                )}

                <KartuKas
                    kas={pinjaman.kas}
                    nominalTahap={pinjaman.nominal_disetujui_bendahara ?? pinjaman.nominal_diminta}
                    label="Nominal usulan Bendahara"
                />

                <JejakNominal pinjaman={pinjaman} finalLabel="Final (cair)" />

                <DrawerSection title="Data Anggota" icon={User}>
                    <DrawerFieldGrid cols={3}>
                        <DrawerField label="Nama" value={pinjaman.anggota.nama} />
                        <DrawerField label="No. Karyawan" value={pinjaman.anggota.no_karyawan} mono />
                        <DrawerField label="Cabang" value={pinjaman.anggota.cabang} />
                        <DrawerField label="Jabatan" value={jabatanLabel[pinjaman.anggota.jabatan] ?? pinjaman.anggota.jabatan} />
                        <DrawerField label="Lama Keanggotaan" value={`${pinjaman.anggota.lama_keanggotaan_tahun} tahun`} />
                        <DrawerField label="Reloan" value={pinjaman.sudah_pakai_privilege_reloan ? 'Ya' : 'Tidak'} />
                    </DrawerFieldGrid>
                </DrawerSection>

                <RincianBebanPinjaman pinjaman={pinjaman} />

                <div className="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                    <DrawerSection title="Keperluan Peminjaman" icon={FileText}>
                        <p className="text-sm text-slate-700 leading-relaxed">{pinjaman.keperluan || '-'}</p>
                    </DrawerSection>

                    <DrawerSection title="Rekening Tujuan Pencairan" icon={Landmark}>
                        <p className="text-sm font-bold text-slate-800">{pinjaman.rekening.bank}</p>
                        <p className="text-sm font-semibold font-mono text-slate-700 mt-0.5">{pinjaman.rekening.no_rekening}</p>
                        <p className="text-xs text-slate-500 mt-0.5">a.n. {pinjaman.rekening.atas_nama}</p>
                    </DrawerSection>
                </div>

                {errors.keputusan && (
                    <div className="flex items-start gap-2.5 bg-rose-50/50 border border-rose-200/80 rounded-xl p-3.5">
                        <AlertCircle size={17} className="text-rose-500 shrink-0 mt-0.5" />
                        <p className="text-sm font-medium text-rose-700">{errors.keputusan}</p>
                    </div>
                )}

                {bisaDiproses && aksi && (
                    <DrawerSection
                        ref={formRef}
                        title={aksi === 'approve' ? 'Otorisasi & Pencairan Final' : 'Konfirmasi Penolakan'}
                        icon={aksi === 'approve' ? CheckCircle2 : XCircle}
                        tone={aksi === 'reject' ? 'rose' : 'emerald'}
                    >
                        <form id={formId} onSubmit={submit} className="space-y-3.5">
                            {aksi === 'approve' && (
                                <div className="space-y-3">
                                    <div>
                                        <label className="block text-xs sm:text-sm font-semibold text-slate-700 mb-1.5">
                                            Nominal Final Cair (maks <span className="privacy-blur">{formatRupiah(pinjaman.limit_tersedia)}</span>)
                                        </label>
                                        <CurrencyField
                                            value={data.nominal}
                                            onChange={(e) => setData('nominal', e.target.value)}
                                            className="font-bold privacy-blur"
                                            autoFocus
                                        />
                                        <ChipNominal grup="pinjaman_usulan" nilai={data.nominal} onPilih={(n) => setData('nominal', n)} />
                                        {errors.nominal && <p className="text-xs sm:text-sm font-medium text-red-600 mt-1.5">{errors.nominal}</p>}
                                        {nominalFinal > (pinjaman.limit_tersedia ?? 0) && (
                                            <p className="text-xs sm:text-sm text-amber-700 font-medium mt-1.5">Melebihi limit tersedia anggota — server akan menolak.</p>
                                        )}
                                    </div>
                                    <div>
                                        <label className="block text-xs sm:text-sm font-semibold text-slate-700 mb-1.5">
                                            Tenor Final (kosongkan = ikut usulan / auto-clamp)
                                        </label>
                                        <div className="relative">
                                            <input
                                                type="number"
                                                min={1}
                                                max={120}
                                                value={data.tenor_bulan}
                                                onChange={(e) => setData('tenor_bulan', e.target.value)}
                                                placeholder={`Default: ${pinjaman.tenor_disetujui_bendahara ?? pinjaman.tenor_bulan} bln`}
                                                className="w-full pl-3.5 pr-12 py-2 text-base font-bold rounded-xl border border-slate-200/95 hover:border-slate-300 shadow-[0_1px_2px_rgba(15,23,42,0.03)] focus:border-brand-green focus:ring-[3px] focus:ring-brand-green/15 outline-none transition-all tabular-nums"
                                            />
                                            <span className="absolute right-3.5 top-1/2 -translate-y-1/2 text-sm font-semibold text-slate-400 pointer-events-none select-none">bln</span>
                                        </div>
                                        {errors.tenor_bulan && <p className="text-xs sm:text-sm font-medium text-red-600 mt-1.5">{errors.tenor_bulan}</p>}
                                    </div>
                                    <PreviewNominal
                                        routeName="ketua.pinjaman.preview"
                                        pinjamanId={pinjaman.id}
                                        nominal={data.nominal}
                                        tenorBulan={data.tenor_bulan}
                                    />
                                </div>
                            )}

                            <div>
                                <label className="block text-xs sm:text-sm font-semibold text-slate-700 mb-1.5">
                                    Catatan {aksi === 'approve' ? 'Persetujuan' : 'Penolakan'}
                                </label>
                                <textarea
                                    value={data.catatan}
                                    onChange={(e) => setData('catatan', e.target.value)}
                                    rows={3}
                                    placeholder={aksi === 'approve' ? 'Contoh: Layak dicairkan, saldo mencukupi.' : 'Contoh: Belum memenuhi ketentuan.'}
                                    className="w-full px-3.5 py-2.5 text-base sm:text-sm rounded-xl border border-slate-300 focus:border-brand-green focus:ring-2 focus:ring-brand-green/20 outline-none transition-colors"
                                    autoFocus={aksi !== 'approve'}
                                />
                                {errors.catatan && <p className="text-xs sm:text-sm font-medium text-red-600 mt-1.5">{errors.catatan}</p>}
                            </div>
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
                        <StatusBadge status={pinjaman.status} />
                        <Button type="button" variant="neutral" size="drawer" onClick={onClose}>
                            Tutup
                        </Button>
                    </div>
                )}
            </DrawerFooter>
        </>
    );
}
