import { useForm } from '@inertiajs/react';
import { useEffect, useId, useRef, useState } from 'react';
import {
    AlertCircle,
    ArrowLeft,
    ArrowUpRight,
    Calendar,
    Check,
    CheckCircle2,
    ChevronDown,
    ChevronUp,
    Clock,
    CreditCard,
    FileText,
    GitCommitHorizontal,
    MessageSquareText,
    User,
    X,
    XCircle,
} from 'lucide-react';
import Button from '@/Components/ui/Button';
import ChipNominal from '@/Components/ui/ChipNominal';
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
import StatusBadge from '@/Components/ui/StatusBadge';
import { formatRupiah } from '@/Utils/formatCurrency';
import { withIdempotencyKey } from '@/Utils/idempotency';

export default function KeputusanDrawer({ pengajuan, onClose }) {
    const [aksi, setAksi] = useState(null);
    const [showPinjamanTable, setShowPinjamanTable] = useState(false);
    const formId = useId();
    const formRef = useRef(null);
    const { data, setData, post, processing, errors } = useForm({
        catatan: '',
        limit_disetujui: String(pengajuan.limit_disetujui_bendahara ?? pengajuan.limit_diminta ?? ''),
    });

    const bisaDiproses = pengajuan.status === 'approved_bendahara';
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
            ? route('ketua.pengajuan-limit.approve', pengajuan.id)
            : route('ketua.pengajuan-limit.reject', pengajuan.id);
        post(url, withIdempotencyKey({
            preserveScroll: true,
            onSuccess: () => onClose(),
        }));
    }

    const jejakRows = [
        { label: 'Diminta anggota', value: formatRupiah(pengajuan.limit_diminta), privacy: true },
        ...(pengajuan.limit_disetujui_bendahara
            ? [{ label: 'Disetujui Bendahara', value: formatRupiah(pengajuan.limit_disetujui_bendahara), tone: 'navy', privacy: true }]
            : []),
        ...(pengajuan.limit_disetujui
            ? [{ label: 'Final Ketua', value: formatRupiah(pengajuan.limit_disetujui), tone: 'emerald', emphasize: true, privacy: true }]
            : []),
    ];

    const totalSisaPinjaman = (pengajuan.pinjaman_aktif ?? []).reduce((sum, p) => sum + (p.sisa_total_bayar ?? 0), 0);

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

                {pengajuan.catatan_ketua && (
                    <DrawerNoteCallout
                        label="Catatan Ketua Koperasi"
                        note={pengajuan.catatan_ketua}
                        tone="emerald"
                        icon={MessageSquareText}
                    />
                )}

                {(pengajuan.limit_disetujui_bendahara || pengajuan.limit_disetujui) && (
                    <DrawerSection title="Jejak Persetujuan Limit" icon={GitCommitHorizontal}>
                        <DrawerKeyValue rows={jejakRows} />
                    </DrawerSection>
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

                {pengajuan.pinjaman_aktif && pengajuan.pinjaman_aktif.length > 0 && (
                    <DrawerSection
                        title={`Detail Jadwal Angsuran (${pengajuan.pinjaman_aktif.length} pinjaman)`}
                        description={
                            <>
                                Total sisa: <strong className="text-slate-700 tabular-nums privacy-blur">{formatRupiah(totalSisaPinjaman)}</strong>
                            </>
                        }
                        icon={Calendar}
                        padding="none"
                        action={
                            <button
                                type="button"
                                onClick={() => setShowPinjamanTable(!showPinjamanTable)}
                                className="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 transition-colors"
                            >
                                <span>{showPinjamanTable ? 'Tutup Jadwal' : 'Lihat Jadwal'}</span>
                                {showPinjamanTable ? <ChevronUp size={14} aria-hidden="true" /> : <ChevronDown size={14} aria-hidden="true" />}
                            </button>
                        }
                    >
                        {showPinjamanTable && (
                            <div className="divide-y divide-slate-200/80">
                                {pengajuan.pinjaman_aktif.map((p, idx) => (
                                    <div key={p.id} className="p-3.5 sm:p-4 space-y-2.5">
                                        <div className="flex flex-wrap items-center justify-between gap-2">
                                            <div className="flex items-center gap-2">
                                                <span className="text-sm font-bold text-slate-800">Pinjaman #{idx + 1}</span>
                                                <span className="text-xs text-slate-500 tabular-nums privacy-blur">
                                                    {formatRupiah(p.nominal)} &bull; {p.tenor_bulan} bln
                                                </span>
                                            </div>
                                            <div className="flex items-center gap-2 text-xs">
                                                <span className="inline-flex items-center px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 font-semibold">
                                                    Sisa {p.sisa_cicilan} / {p.total_cicilan} cicilan
                                                </span>
                                                <span className="font-bold text-slate-800 tabular-nums privacy-blur">
                                                    {formatRupiah(p.sisa_total_bayar)}
                                                </span>
                                            </div>
                                        </div>

                                        {p.jadwal_angsuran && p.jadwal_angsuran.length > 0 && (
                                            <div className="overflow-x-auto rounded-lg border border-slate-200/80">
                                                <table className="w-full text-xs">
                                                    <thead className="bg-[#F8FAFC] text-[11px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200/80">
                                                        <tr>
                                                            <th className="py-2 px-2.5 w-10 text-center whitespace-nowrap">Ke</th>
                                                            <th className="py-2 px-2.5 text-right whitespace-nowrap">Pokok</th>
                                                            <th className="py-2 px-2.5 text-right whitespace-nowrap">Bunga</th>
                                                            <th className="py-2 px-2.5 text-right whitespace-nowrap">Total</th>
                                                            <th className="py-2 px-2.5 text-center whitespace-nowrap">Jatuh Tempo</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody className="divide-y divide-slate-100 bg-white">
                                                        {p.jadwal_angsuran.slice(0, 6).map((a) => (
                                                            <tr key={a.cicilan_ke} className="hover:bg-[#F2FBF5] transition-colors">
                                                                <td className="py-1.5 px-2.5 text-center font-medium text-slate-700 tabular-nums">{a.cicilan_ke}</td>
                                                                <td className="py-1.5 px-2.5 text-right text-slate-700 tabular-nums privacy-blur">{formatRupiah(a.nominal_pokok)}</td>
                                                                <td className="py-1.5 px-2.5 text-right text-amber-700 tabular-nums privacy-blur">{formatRupiah(a.nominal_bunga)}</td>
                                                                <td className="py-1.5 px-2.5 text-right font-semibold text-slate-800 tabular-nums privacy-blur">{formatRupiah(a.total_bayar)}</td>
                                                                <td className="py-1.5 px-2.5 text-center text-slate-600 whitespace-nowrap">{a.tanggal_jatuh_tempo}</td>
                                                            </tr>
                                                        ))}
                                                        {p.jadwal_angsuran.length > 6 && (
                                                            <tr className="text-center text-slate-500 bg-[#F8FAFC]">
                                                                <td colSpan={5} className="py-2 text-xs">+{p.jadwal_angsuran.length - 6} angsuran lagi...</td>
                                                            </tr>
                                                        )}
                                                    </tbody>
                                                </table>
                                            </div>
                                        )}
                                    </div>
                                ))}
                            </div>
                        )}
                    </DrawerSection>
                )}

                {pengajuan.pinjaman_pending && (
                    <DrawerSection title="Pinjaman Sedang Diproses" icon={Clock} tone="amber">
                        <p className="text-sm text-slate-700">
                            Nominal: <strong className="privacy-blur tabular-nums">{formatRupiah(pengajuan.pinjaman_pending.nominal)}</strong> &bull; Status:{' '}
                            <strong>{pengajuan.pinjaman_pending.status === 'diajukan' ? 'Menunggu Bendahara' : 'Menunggu Ketua'}</strong>
                        </p>
                        <p className="text-xs text-amber-700 mt-1">
                            Pengajuan ini akan mempengaruhi limit tersedia jika disetujui.
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
                        title={aksi === 'approve' ? 'Otorisasi Limit Baru Final' : 'Konfirmasi Penolakan'}
                        icon={aksi === 'approve' ? CheckCircle2 : XCircle}
                        tone={aksi === 'reject' ? 'rose' : 'emerald'}
                    >
                        <form id={formId} onSubmit={submit} className="space-y-3.5">
                            {aksi === 'approve' && (
                                <div>
                                    <label className="block text-xs sm:text-sm font-semibold text-slate-700 mb-1">
                                        Total Limit Baru Final yang Disetujui
                                    </label>
                                    <p className="text-xs text-slate-500 mb-2">
                                        Isi total limit baru secara keseluruhan (bukan nilai selisih). Wajib &gt;{' '}
                                        <span className="privacy-blur font-semibold text-slate-700">{formatRupiah(pengajuan.limit_saat_ini)}</span>.
                                    </p>
                                    <div className="relative">
                                        <span className="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm font-semibold text-slate-500">Rp</span>
                                        <input
                                            type="number"
                                            min={pengajuan.limit_saat_ini + 1}
                                            value={data.limit_disetujui}
                                            onChange={(e) => setData('limit_disetujui', e.target.value)}
                                            className="w-full pl-10 pr-4 py-2 text-base font-bold rounded-xl border border-slate-300 focus:border-brand-green focus:ring-2 focus:ring-brand-green/20 outline-none transition-colors privacy-blur tabular-nums"
                                            autoFocus
                                        />
                                    </div>
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
                                    Catatan {aksi === 'approve' ? 'Persetujuan' : 'Penolakan'}
                                </label>
                                <textarea
                                    value={data.catatan}
                                    onChange={(e) => setData('catatan', e.target.value)}
                                    rows={3}
                                    placeholder={aksi === 'approve' ? 'Contoh: Limit dinaikkan sesuai permintaan, memenuhi ketentuan.' : 'Contoh: Belum memenuhi syarat keanggotaan minimal.'}
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