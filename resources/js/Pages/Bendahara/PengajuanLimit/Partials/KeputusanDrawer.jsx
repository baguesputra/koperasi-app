import { useForm } from '@inertiajs/react';
import { useEffect, useId, useRef, useState } from 'react';
import { AlertCircle, ArrowLeft, ArrowUpRight, Calendar, Check, CheckCircle2, Clock, CreditCard, FileText, MessageSquareText, User, X, XCircle } from 'lucide-react';
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

export default function KeputusanDrawer({ pengajuan, onClose }) {
    const [aksi, setAksi] = useState(null);
    const formId = useId();
    const formRef = useRef(null);
    const { data, setData, post, processing, errors } = useForm({
        catatan: '',
        limit_disetujui: String(pengajuan.limit_diminta ?? ''),
    });

    const bisaDiproses = pengajuan.status === 'diajukan';
    const selisihLimit = pengajuan.limit_diminta - pengajuan.limit_saat_ini;
    const persenKenaikan = pengajuan.limit_saat_ini > 0
        ? ((selisihLimit / pengajuan.limit_saat_ini) * 100).toFixed(1)
        : '0.0';
    const diBawahSaatIni = Number(data.limit_disetujui) > 0 && Number(data.limit_disetujui) < pengajuan.limit_saat_ini;

    useEffect(() => {
        if (aksi && formRef.current) {
            formRef.current.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }, [aksi]);

    function submit(e) {
        e.preventDefault();
        const url = aksi === 'approve'
            ? route('bendahara.pengajuan-limit.approve', pengajuan.id)
            : route('bendahara.pengajuan-limit.reject', pengajuan.id);
        post(url, withIdempotencyKey({
            preserveScroll: true,
            onSuccess: () => onClose(),
        }));
    }

    return (
        <>
            <DrawerBody>
                <DrawerHero>
                    <div className="flex items-start justify-between gap-4 mb-4">
                        <div className="min-w-0">
                            <p className="text-[11px] font-medium text-slate-300">Pemohon Kenaikan Limit</p>
                            <p className="text-lg sm:text-xl font-bold text-white truncate mt-0.5">{pengajuan.anggota.nama}</p>
                            <p className="text-xs text-slate-300 font-mono mt-0.5">{pengajuan.anggota.no_karyawan}</p>
                        </div>
                        <span className="text-xs text-slate-300 shrink-0">{pengajuan.tanggal_pengajuan}</span>
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-3.5 border-t border-white/10">
                        <div className="rounded-xl bg-white/[0.06] border border-white/10 p-3.5">
                            <p className="text-[11px] text-slate-300">Limit Saat Ini</p>
                            <p className="text-xl sm:text-2xl font-bold text-white tabular-nums mt-1 privacy-blur">
                                {formatRupiah(pengajuan.limit_saat_ini)}
                            </p>
                        </div>

                        <div className="rounded-xl bg-brand-green/20 border border-brand-green/35 p-3.5">
                            <div className="flex items-center justify-between gap-2">
                                <p className="text-[11px] font-semibold text-emerald-200">Limit Diminta</p>
                                <span className="inline-flex items-center gap-1 rounded-full bg-emerald-500/25 border border-emerald-400/30 px-2 py-0.5 text-[11px] font-bold text-emerald-200 tabular-nums privacy-blur">
                                    <ArrowUpRight size={11} aria-hidden="true" />
                                    +{persenKenaikan}%
                                </span>
                            </div>
                            <p className="text-xl sm:text-2xl font-extrabold text-white tabular-nums mt-1 privacy-blur">
                                {formatRupiah(pengajuan.limit_diminta)}
                            </p>
                            <p className="text-[11px] text-emerald-200 mt-0.5 tabular-nums privacy-blur">
                                Selisih: +{formatRupiah(selisihLimit)}
                            </p>
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

                <DrawerSection title="Alasan Pengajuan" icon={FileText}>
                    <p className="text-sm text-slate-700 leading-relaxed">{pengajuan.keterangan || '-'}</p>
                </DrawerSection>

                <DrawerSection title="Data Anggota" icon={User}>
                    <DrawerFieldGrid cols={4}>
                        <DrawerField label="No. Karyawan" value={pengajuan.anggota.no_karyawan} mono />
                        <DrawerField label="Cabang" value={pengajuan.anggota.cabang} />
                        <DrawerField label="Lama Keanggotaan" value={`${pengajuan.anggota.lama_keanggotaan_tahun} tahun`} />
                        <DrawerField label="Tgl Pengajuan" value={pengajuan.tanggal_pengajuan} />
                    </DrawerFieldGrid>
                </DrawerSection>

                <DrawerSection title="Status Pinjaman" icon={CreditCard} padding="none">
                    {pengajuan.pinjaman_aktif && pengajuan.pinjaman_aktif.length > 0 ? (
                        <div className="divide-y divide-slate-100">
                            {pengajuan.pinjaman_aktif.map((p, idx) => {
                                const terbayar = Math.max(0, (p.total_cicilan ?? 0) - (p.sisa_cicilan ?? 0));
                                const persen = p.total_cicilan > 0 ? Math.min(100, Math.round((terbayar / p.total_cicilan) * 100)) : 0;
                                return (
                                    <div key={p.id} className="p-3.5 sm:px-4 space-y-2">
                                        <div className="flex items-center justify-between gap-3">
                                            <div className="flex items-center gap-2.5 min-w-0">
                                                <span className="w-8 h-8 rounded-lg bg-gradient-to-br from-sky-50 to-indigo-100/80 text-sky-700 inline-flex items-center justify-center shrink-0">
                                                    <CreditCard size={15} aria-hidden="true" />
                                                </span>
                                                <div className="min-w-0">
                                                    <p className="text-sm font-semibold text-slate-800">Pinjaman #{idx + 1}</p>
                                                    <p className="text-xs text-slate-500 tabular-nums privacy-blur">
                                                        {formatRupiah(p.nominal)} &bull; {p.tenor_bulan} bln
                                                    </p>
                                                </div>
                                            </div>
                                            <div className="text-right shrink-0">
                                                <p className="text-sm font-bold text-slate-800 tabular-nums privacy-blur">
                                                    {formatRupiah(p.sisa_total_bayar)}
                                                </p>
                                                <p className="text-[11px] text-slate-500 flex items-center justify-end gap-1">
                                                    <Calendar size={11} aria-hidden="true" />
                                                    Cicilan ke-{terbayar + 1} / {p.total_cicilan}
                                                </p>
                                            </div>
                                        </div>
                                        <div className="h-1.5 w-full rounded-full bg-slate-100 overflow-hidden">
                                            <div
                                                className="h-full rounded-full bg-gradient-to-r from-brand-navy to-brand-green"
                                                style={{ width: `${persen}%` }}
                                            />
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    ) : (
                        <div className="py-6 text-center text-sm text-slate-500">
                            Tidak ada pinjaman aktif
                        </div>
                    )}
                </DrawerSection>

                {pengajuan.pinjaman_pending && (
                    <DrawerSection title="Pinjaman Sedang Diproses" icon={Clock} tone="amber">
                        <p className="text-sm text-slate-700">
                            Nominal: <strong className="privacy-blur tabular-nums">{formatRupiah(pengajuan.pinjaman_pending.nominal)}</strong> &bull; Status:{' '}
                            <strong>{pengajuan.pinjaman_pending.status === 'diajukan' ? 'Menunggu Bendahara' : 'Menunggu Ketua'}</strong>
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
                        title={aksi === 'approve' ? 'Evaluasi Usulan Limit Baru' : 'Konfirmasi Penolakan'}
                        icon={aksi === 'approve' ? CheckCircle2 : XCircle}
                        tone={aksi === 'reject' ? 'rose' : 'emerald'}
                    >
                        <form id={formId} onSubmit={submit} className="space-y-3.5">
                            {aksi === 'approve' && (
                                <div>
                                    <label className="block text-xs sm:text-sm font-semibold text-slate-700 mb-1">
                                        Total Limit Baru yang Disetujui
                                    </label>
                                    <p className="text-xs text-slate-500 mb-2">
                                        Isi total limit baru secara keseluruhan (bukan nilai selisih). Wajib &gt;{' '}
                                        <span className="privacy-blur font-semibold text-slate-700">{formatRupiah(pengajuan.limit_saat_ini)}</span>.
                                    </p>
                                    <CurrencyField
                                        value={data.limit_disetujui}
                                        onChange={(e) => setData('limit_disetujui', e.target.value)}
                                        className="font-bold privacy-blur"
                                        autoFocus
                                    />
                                    <ChipNominal grup="limit_baru" nilai={data.limit_disetujui} onPilih={(n) => setData('limit_disetujui', n)} />
                                    {Number(data.limit_disetujui) > pengajuan.limit_saat_ini && (
                                        <p className="text-xs font-semibold text-brand-green-dark mt-1.5 privacy-blur tabular-nums">
                                            Penambahan limit: +{formatRupiah(Number(data.limit_disetujui) - pengajuan.limit_saat_ini)}
                                        </p>
                                    )}
                                    {errors.limit_disetujui && <p className="text-xs sm:text-sm font-medium text-red-600 mt-1.5">{errors.limit_disetujui}</p>}
                                    {diBawahSaatIni && (
                                        <p className="text-xs sm:text-sm text-red-600 font-semibold mt-1.5">
                                            Nominal di bawah limit saat ini (<span className="privacy-blur">{formatRupiah(pengajuan.limit_saat_ini)}</span>). Masukkan total limit baru yang lebih besar.
                                        </p>
                                    )}
                                </div>
                            )}

                            <div>
                                <label className="block text-xs sm:text-sm font-semibold text-slate-700 mb-1.5">
                                    Catatan {aksi === 'approve' ? 'Verifikasi' : 'Penolakan'}
                                </label>
                                <textarea
                                    value={data.catatan}
                                    onChange={(e) => setData('catatan', e.target.value)}
                                    rows={3}
                                    placeholder={aksi === 'approve' ? 'Contoh: Layak diteruskan ke Ketua sebesar nominal di atas.' : 'Contoh: Belum memenuhi syarat.'}
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
                        <StatusBadge status={pengajuan.status} />
                        <Button type="button" variant="neutral" size="drawer" onClick={onClose}>
                            Tutup
                        </Button>
                    </div>
                )}
            </DrawerFooter>
        </>
    );
}
