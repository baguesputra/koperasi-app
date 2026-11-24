import { useForm } from '@inertiajs/react';
import {
    AlertTriangle, ArrowLeft, CheckCircle2, Clock, FileX2, Search, ShieldCheck, X, XCircle,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import Button from '@/Components/ui/Button';
import {
    DrawerBody,
    DrawerField,
    DrawerFieldGrid,
    DrawerFooter,
    DrawerFooterActions,
    DrawerSection,
} from '@/Components/ui/DrawerSection';
import FormField from '@/Components/ui/FormField';
import FotoAnggota from '@/Components/ui/FotoAnggota';
import StatusBadge from '@/Components/ui/StatusBadge';
import TextField from '@/Components/ui/TextField';
import { formatRupiah } from '@/Utils/formatCurrency';
import { withIdempotencyKey } from '@/Utils/idempotency';

export function PenghapusanDetailDrawer({ detail, peranSaya, onClose }) {
    const form = useForm({ catatan: '' });

    function submitApprove(e) {
        e.preventDefault();
        form.post(route('pengurus.penghapusan-pinjaman.approve', detail.id), {
            ...withIdempotencyKey({
                preserveScroll: true,
                onSuccess: onClose,
            }),
        });
    }

    function submitReject(e) {
        e.preventDefault();
        form.post(route('pengurus.penghapusan-pinjaman.reject', detail.id), {
            ...withIdempotencyKey({
                preserveScroll: true,
                onSuccess: onClose,
            }),
        });
    }

    return (
        <>
            <DrawerBody>
                <DrawerSection title="Ringkasan Penghapusan" icon={FileX2}>
                    <DrawerFieldGrid cols={2}>
                        <DrawerField label="Nomor Dokumen" value={detail.nomor_dokumen ?? 'Terbit saat disetujui final'} mono />
                        <DrawerField label="Anggota" value={`${detail.anggota.nama} (${detail.anggota.no_karyawan})`} />
                        <DrawerField label="Nominal Pinjaman Awal" value={formatRupiah(detail.nominal_pinjaman)} />
                        <DrawerField label="Pokok Sudah Terbayar" value={formatRupiah(detail.total_pokok_terbayar)} />
                        <DrawerField label="Sisa Cicilan" value={`${detail.sisa_cicilan}x angsuran`} />
                        <DrawerField label="Sisa Bunga Dibatalkan" value={formatRupiah(detail.sisa_bunga)} />
                    </DrawerFieldGrid>
                    <div className="pt-3 mt-3 border-t border-slate-100 flex items-center justify-between text-sm">
                        <span className="font-bold text-rose-700">Sisa Pokok Dihapusbukukan</span>
                        <span className="font-bold text-rose-700 tabular-nums privacy-blur">{formatRupiah(detail.sisa_pokok)}</span>
                    </div>
                </DrawerSection>

                <DrawerSection title="Alur Persetujuan Berjenjang (Pengurus → Pengawas 3 → 2 → 1)" icon={ShieldCheck}>
                    <ol className="space-y-2.5">
                        {(detail.timeline ?? []).map((step) => {
                            const isDone = step.status === 'done';
                            const isCurrent = step.status === 'current';
                            const isRejected = step.status === 'rejected';
                            return (
                                <li
                                    key={step.tahap}
                                    className={`flex items-start gap-3 rounded-xl p-2.5 border ${
                                        isRejected
                                            ? 'bg-red-50/60 border-red-200'
                                            : isCurrent
                                                ? 'bg-amber-50/60 border-amber-300'
                                                : isDone
                                                    ? 'bg-emerald-50/40 border-emerald-200/70'
                                                    : 'bg-slate-50/60 border-slate-200/60'
                                    }`}
                                >
                                    <div className="mt-0.5 shrink-0">
                                        {isRejected ? (
                                            <XCircle size={17} className="text-red-600" />
                                        ) : isDone ? (
                                            <CheckCircle2 size={17} className="text-emerald-600" />
                                        ) : isCurrent ? (
                                            <Clock size={17} className="text-amber-600" />
                                        ) : (
                                            <Clock size={17} className="text-slate-300" />
                                        )}
                                    </div>
                                    <div className="flex-1 min-w-0">
                                        <div className="flex items-center justify-between gap-2">
                                            <p className="text-xs font-bold text-slate-800">{step.label}</p>
                                            <span className="text-[11px] text-slate-400">{step.tanggal}</span>
                                        </div>
                                        <p className="text-xs text-slate-600 mt-0.5">Pejabat: {step.user}</p>
                                        {step.catatan && (
                                            <p className="text-xs text-slate-700 mt-1 italic bg-white/80 rounded-lg px-2.5 py-1.5 border border-slate-200/60">
                                                &ldquo;{step.catatan}&rdquo;
                                            </p>
                                        )}
                                    </div>
                                </li>
                            );
                        })}
                    </ol>
                </DrawerSection>

                {detail.bisa_diproses && (
                    <DrawerSection title={`Keputusan Anda (${peranSaya})`} tone="emerald">
                        <FormField label="Catatan Keputusan" error={form.errors.catatan} required>
                            <TextField
                                data-autofocus
                                as="textarea"
                                rows={3}
                                value={form.data.catatan}
                                onChange={(e) => form.setData('catatan', e.target.value)}
                                placeholder="Tulis catatan verifikasi / alasan persetujuan atau penolakan..."
                                required
                            />
                        </FormField>
                    </DrawerSection>
                )}
            </DrawerBody>

            <DrawerFooter>
                {detail.bisa_diproses ? (
                    <DrawerFooterActions cols={2} stackOnMobile>
                        <Button type="button" variant="danger" size="drawer" disabled={form.processing} onClick={submitReject}>
                            Tolak Pengajuan
                        </Button>
                        <Button type="button" size="drawer" disabled={form.processing} onClick={submitApprove}>
                            {detail.status === 'approved_pengawas_2' ? 'Setujui Final & Hapusbukukan' : 'Setujui & Teruskan'}
                        </Button>
                    </DrawerFooterActions>
                ) : (
                    <div className="flex items-center justify-between gap-3">
                        <StatusBadge status={detail.status} />
                        <Button type="button" variant="neutral" size="drawer" onClick={onClose}>
                            Tutup
                        </Button>
                    </div>
                )}
            </DrawerFooter>
        </>
    );
}

export function AjukanPenghapusanDrawer({ pinjamanMenunggak = [], onClose }) {
    const [cari, setCari] = useState('');
    const [dipilih, setDipilih] = useState(null);
    const form = useForm({ pinjaman_id: '', alasan: '' });

    const tersaring = useMemo(() => {
        const q = cari.trim().toLowerCase();
        if (!q) return pinjamanMenunggak;
        return pinjamanMenunggak.filter(
            (p) =>
                p.anggota?.nama?.toLowerCase().includes(q)
                || p.anggota?.no_karyawan?.toLowerCase().includes(q)
                || p.nomor_dokumen?.toLowerCase().includes(q)
        );
    }, [cari, pinjamanMenunggak]);

    function pilih(pinjaman) {
        setDipilih(pinjaman);
        form.setData({ pinjaman_id: pinjaman.pinjaman_id, alasan: '' });
        form.clearErrors();
    }

    function submit(e) {
        e.preventDefault();
        form.post(route('pengurus.penghapusan-pinjaman.store'), {
            ...withIdempotencyKey({
                preserveScroll: true,
                onSuccess: () => {
                    form.reset();
                    onClose();
                },
            }),
        });
    }

    if (!dipilih) {
        return (
            <>
                <DrawerBody>
                    <div className="relative mb-3">
                        <Search size={15} className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
                        <TextField
                            data-autofocus
                            size="sm"
                            value={cari}
                            onChange={(e) => setCari(e.target.value)}
                            placeholder="Cari anggota atau nomor pinjaman menunggak..."
                            className="pl-10 pr-8"
                        />
                        {cari && (
                            <button
                                type="button"
                                onClick={() => setCari('')}
                                className="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                            >
                                <X size={14} />
                            </button>
                        )}
                    </div>

                    <div className="space-y-2">
                        {tersaring.length === 0 ? (
                            <p className="py-10 text-center text-sm text-slate-400">
                                Tidak ada pinjaman aktif yang dapat diajukan penghapusan.
                            </p>
                        ) : (
                            tersaring.map((p) => (
                                <button
                                    key={p.pinjaman_id}
                                    type="button"
                                    onClick={() => pilih(p)}
                                    className="w-full text-left flex items-center justify-between gap-3 p-3 rounded-xl border border-slate-200/80 bg-white hover:bg-[#F2FBF5] hover:border-brand-green/50 transition-colors cursor-pointer"
                                >
                                    <div className="flex items-center gap-2.5 min-w-0">
                                        <FotoAnggota nama={p.anggota?.nama} fotoUrl={p.anggota?.foto_url} ukuran="sm" />
                                        <div className="min-w-0">
                                            <p className="text-sm font-semibold text-slate-800 truncate">{p.anggota?.nama}</p>
                                            <p className="text-xs text-slate-500 truncate">
                                                {p.anggota?.no_karyawan} • {p.nomor_dokumen} • {p.sisa_cicilan}x cicilan
                                            </p>
                                        </div>
                                    </div>
                                    <div className="text-right shrink-0">
                                        <p className="text-sm font-bold text-rose-700 tabular-nums privacy-blur">{formatRupiah(p.sisa_pokok)}</p>
                                        {p.jumlah_terlambat > 0 && (
                                            <span className="text-[11px] font-semibold text-rose-600">{p.jumlah_terlambat}x telat</span>
                                        )}
                                    </div>
                                </button>
                            ))
                        )}
                    </div>
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
        <form onSubmit={submit} className="flex-1 min-h-0 flex flex-col">
            <DrawerBody>
                <DrawerSection title="Pinjaman Dipilih" icon={FileX2}>
                    <DrawerFieldGrid cols={2}>
                        <DrawerField label="Anggota" value={`${dipilih.anggota.nama} (${dipilih.anggota.no_karyawan})`} />
                        <DrawerField label="No. Dokumen" value={dipilih.nomor_dokumen} mono />
                        <DrawerField label="Pinjaman Awal" value={formatRupiah(dipilih.nominal_pinjaman)} />
                        <DrawerField label="Pokok Terbayar" value={formatRupiah(dipilih.total_pokok_terbayar)} />
                        <DrawerField label="Sisa Cicilan" value={`${dipilih.sisa_cicilan}x cicilan`} />
                        <DrawerField label="Sisa Bunga Dibatalkan" value={formatRupiah(dipilih.sisa_bunga)} />
                    </DrawerFieldGrid>
                    <div className="pt-3 mt-3 border-t border-slate-100 flex items-center justify-between text-sm">
                        <span className="font-bold text-rose-700">Sisa Pokok Dihapusbukukan</span>
                        <span className="font-bold text-rose-700 tabular-nums privacy-blur">{formatRupiah(dipilih.sisa_pokok)}</span>
                    </div>
                </DrawerSection>

                <DrawerSection title="Alasan Penghapusan" icon={AlertTriangle} tone="amber">
                    <FormField label="Kronologi / Alasan Penghapusan" error={form.errors.alasan} required>
                        <TextField
                            data-autofocus
                            as="textarea"
                            rows={3}
                            value={form.data.alasan}
                            onChange={(e) => form.setData('alasan', e.target.value)}
                            placeholder="Jelaskan kronologi tunggakan / alasan anggota tidak dapat ditagih lagi..."
                            required
                        />
                    </FormField>
                    <p className="text-xs text-amber-900 mt-2">
                        Saat disetujui final oleh Pengawas 1, sisa pokok ({formatRupiah(dipilih.sisa_pokok)}) dihapusbukukan tanpa memotong saldo bank, dan anggota dinonaktifkan.
                    </p>
                </DrawerSection>
            </DrawerBody>

            <DrawerFooter>
                <DrawerFooterActions cols={2} stackOnMobile>
                    <Button type="button" variant="neutral" size="drawer" onClick={() => setDipilih(null)}>
                        <ArrowLeft size={15} /> Pilih Lain
                    </Button>
                    <Button type="submit" size="drawer" disabled={form.processing}>
                        {form.processing ? 'Mengirim...' : 'Kirim Pengajuan'}
                    </Button>
                </DrawerFooterActions>
            </DrawerFooter>
        </form>
    );
}
