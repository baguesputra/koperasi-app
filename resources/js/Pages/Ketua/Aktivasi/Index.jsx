import AppLayout from '@/Layouts/AppLayout';
import { Head, useForm } from '@inertiajs/react';
import { useEffect, useId, useRef, useState } from 'react';
import {
    ArrowLeft, Check, ChevronRight, CheckCircle2, AlertCircle, Briefcase, UserCheck,
    CreditCard, ShieldCheck, MapPin, MessageSquareText, X, XCircle
} from 'lucide-react';
import Card from '@/Components/ui/Card';
import FotoAnggota from '@/Components/ui/FotoAnggota';
import StatusBadge from '@/Components/ui/StatusBadge';
import PageHeader from '@/Components/ui/PageHeader';
import Drawer from '@/Components/ui/Drawer';
import {
    DrawerBody,
    DrawerField,
    DrawerFieldGrid,
    DrawerFooter,
    DrawerHero,
    DrawerNoteCallout,
    DrawerSection,
} from '@/Components/ui/DrawerSection';
import Button from '@/Components/ui/Button';
import { formatRupiah } from '@/Utils/formatCurrency';
import { withIdempotencyKey } from '@/Utils/idempotency';

const tabDasar = 'inline-flex items-center gap-2 px-3.5 py-2 text-xs sm:text-sm font-semibold rounded-xl transition-all duration-150';
const tabAktif = `${tabDasar} bg-gradient-to-b from-white to-brand-green-light/30 border border-brand-green/40 text-brand-navy shadow-sm`;
const tabNonAktif = `${tabDasar} text-slate-600 hover:bg-white/80 hover:text-brand-navy`;

function KeputusanDrawer({ pengajuan, ketentuanFinansial, onClose }) {
    const [aksi, setAksi] = useState(null);
    const formId = useId();
    const formRef = useRef(null);
    const { data, setData, post, processing, errors } = useForm({ catatan: '' });
    const bisaDiproses = pengajuan.status === 'diajukan';
    const isReaktivasi = pengajuan.anggota.is_reaktivasi;

    useEffect(() => {
        if (aksi && formRef.current) {
            formRef.current.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }, [aksi]);

    function submit(e) {
        e.preventDefault();
        const url = aksi === 'approve'
            ? route('ketua.aktivasi.approve', pengajuan.id)
            : route('ketua.aktivasi.reject', pengajuan.id);
        post(url, withIdempotencyKey({ preserveScroll: true, onSuccess: () => onClose() }));
    }

    return (
        <>
            <DrawerBody>
                <DrawerHero>
                    <div className="flex items-start gap-3.5">
                        <FotoAnggota
                            nama={pengajuan.anggota.nama}
                            fotoUrl={pengajuan.anggota.foto_url}
                            ukuran="lg"
                            className="ring-2 ring-white/20 shrink-0"
                        />
                        <div className="min-w-0 flex-1">
                            <div className="flex items-center gap-2 flex-wrap">
                                <h3 className="text-lg sm:text-xl font-bold text-white tracking-tight">
                                    {pengajuan.anggota.nama}
                                </h3>
                                {isReaktivasi ? (
                                    <span className="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-200 border border-amber-400/30">
                                        Re-Aktivasi
                                    </span>
                                ) : (
                                    <span className="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-200 border border-emerald-400/30">
                                        Anggota Baru
                                    </span>
                                )}
                            </div>
                            <p className="text-xs text-slate-300 mt-1 flex items-center gap-2 flex-wrap">
                                <span>No. Karyawan: <strong className="text-white font-mono">{pengajuan.anggota.no_karyawan}</strong></span>
                                <span>&bull;</span>
                                <span>No. Anggota: <strong className="text-white font-mono">{pengajuan.anggota.no_anggota}</strong></span>
                            </p>
                            <p className="text-[11px] text-slate-300 mt-0.5">
                                Diajukan pada: {pengajuan.tanggal_pengajuan}
                            </p>
                        </div>
                    </div>
                </DrawerHero>

                <DrawerSection
                    title="Kepegawaian & Masa Kerja"
                    icon={Briefcase}
                    action={
                        pengajuan.anggota.gate_synced_at && (
                            <span className="text-[11px] text-slate-500 font-medium">
                                Sinkron GATE: {pengajuan.anggota.gate_synced_at}
                            </span>
                        )
                    }
                >
                    <DrawerFieldGrid cols={2}>
                        <DrawerField
                            label="Masa Kerja Perusahaan"
                            value={pengajuan.anggota.masa_kerja ?? '-'}
                            sub={pengajuan.anggota.tanggal_mulai_kerja ? `Mulai: ${pengajuan.anggota.tanggal_mulai_kerja}` : null}
                        />
                        <DrawerField
                            label="Cabang & Unit Bisnis"
                            value={pengajuan.anggota.cabang ?? '-'}
                            sub={pengajuan.anggota.unit_bisnis ?? '-'}
                        />
                        <DrawerField label="Jabatan" value={pengajuan.anggota.jabatan ?? '-'} />
                        <DrawerField
                            label="Departemen / Divisi"
                            value={pengajuan.anggota.department ?? '-'}
                            sub={pengajuan.anggota.divisi ? `Divisi: ${pengajuan.anggota.divisi}` : null}
                        />
                    </DrawerFieldGrid>
                </DrawerSection>

                <DrawerSection title="Identitas & Kontak" icon={UserCheck}>
                    <DrawerFieldGrid cols={2}>
                        <DrawerField label="No. KTP / NIK" value={pengajuan.anggota.no_ktp ?? '-'} mono />
                        <DrawerField label="No. WhatsApp / HP" value={pengajuan.anggota.no_hp ?? '-'} />
                        <DrawerField label="Email Akun SSO" value={pengajuan.anggota.email ?? '-'} />
                        <DrawerField
                            label="Status Keanggotaan"
                            value={<span className="capitalize">{pengajuan.anggota.status}</span>}
                        />
                    </DrawerFieldGrid>
                    {pengajuan.anggota.alamat && (
                        <div className="mt-3 pt-2.5 border-t border-slate-100 text-xs text-slate-600 flex items-start gap-1.5">
                            <MapPin size={13} className="text-slate-500 shrink-0 mt-0.5" aria-hidden="true" />
                            <span>Alamat: {pengajuan.anggota.alamat}</span>
                        </div>
                    )}
                </DrawerSection>

                {ketentuanFinansial && (
                    <DrawerSection
                        title="Ketentuan Finansial Pasca-Aktivasi"
                        icon={CreditCard}
                        tone="emerald"
                        padding="none"
                    >
                        <div className="grid grid-cols-1 sm:grid-cols-3 divide-y sm:divide-y-0 sm:divide-x divide-slate-100">
                            <div className="p-3.5 sm:px-4">
                                <p className="text-[11px] font-medium text-slate-500">Simpanan Pokok</p>
                                <p className="text-sm sm:text-base font-extrabold text-brand-green-dark tabular-nums mt-0.5">
                                    {formatRupiah(ketentuanFinansial.simpanan_pokok ?? 50000)}
                                </p>
                                <p className="text-[11px] text-slate-500 mt-0.5">Dicatat otomatis ke buku kas</p>
                            </div>
                            <div className="p-3.5 sm:px-4">
                                <p className="text-[11px] font-medium text-slate-500">Iuran Bulanan</p>
                                <p className="text-sm sm:text-base font-extrabold text-slate-900 tabular-nums mt-0.5">
                                    {formatRupiah((ketentuanFinansial.simpanan_wajib ?? 45000) + (ketentuanFinansial.dana_sosial ?? 5000))}
                                    <span className="text-xs font-normal text-slate-500">/bln</span>
                                </p>
                                <p className="text-[11px] text-slate-500 mt-0.5">Wajib + Dana Sosial</p>
                            </div>
                            <div className="p-3.5 sm:px-4">
                                <p className="text-[11px] font-medium text-slate-500">Plafon Pinjaman Awal</p>
                                <p className="text-sm sm:text-base font-extrabold text-brand-navy tabular-nums mt-0.5">
                                    {formatRupiah(ketentuanFinansial.limit_awal ?? 1000000)}
                                </p>
                                <p className="text-[11px] text-slate-500 mt-0.5">Hak limit pinjaman pertama</p>
                            </div>
                        </div>
                    </DrawerSection>
                )}

                {isReaktivasi && (
                    <DrawerSection title="Riwayat Keanggotaan Sebelumnya" icon={AlertCircle} tone="amber">
                        <p className="text-xs sm:text-sm text-slate-700">
                            Anggota ini sebelumnya pernah mengundurkan diri / resign pada tanggal:{' '}
                            <strong>{pengajuan.anggota.tanggal_resign ?? '-'}</strong>
                        </p>
                        {pengajuan.anggota.alasan_resign && (
                            <p className="text-xs text-slate-600 mt-2 italic px-3 py-2 rounded-lg bg-amber-50/50 border border-amber-200/70">
                                Alasan resign sebelumnya: &ldquo;{pengajuan.anggota.alasan_resign}&rdquo;
                            </p>
                        )}
                    </DrawerSection>
                )}

                <DrawerSection
                    title="Pernyataan Digital Pemohon"
                    icon={ShieldCheck}
                    action={
                        <span className="text-[11px] font-semibold text-slate-600 bg-slate-100 px-2 py-0.5 rounded-full">
                            {pengajuan.versi_syarat}
                        </span>
                    }
                >
                    <div className="space-y-2 text-xs sm:text-sm text-slate-700">
                        <div className="flex items-start gap-2">
                            <CheckCircle2 size={15} className="text-brand-green shrink-0 mt-0.5" aria-hidden="true" />
                            <span>Pemohon menyatakan data diri &amp; kepegawaian telah diisi dengan sebenarnya.</span>
                        </div>
                        <div className="flex items-start gap-2">
                            <CheckCircle2 size={15} className="text-brand-green shrink-0 mt-0.5" aria-hidden="true" />
                            <span>Pemohon menyetujui kepatuhan AD/ART dan seluruh kebijakan Koperasi.</span>
                        </div>
                    </div>
                </DrawerSection>

                {pengajuan.catatan_ketua && (
                    <DrawerNoteCallout
                        label="Catatan Ketua"
                        note={pengajuan.catatan_ketua}
                        timestamp={pengajuan.tanggal_keputusan}
                        tone="emerald"
                        icon={MessageSquareText}
                    />
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
                        title={aksi === 'approve' ? 'Konfirmasi Persetujuan Aktivasi' : 'Konfirmasi Penolakan Aktivasi'}
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
                                placeholder={aksi === 'approve' ? 'Contoh: Data telah diverifikasi sesuai HRIS GATE, aktivasi disetujui.' : 'Contoh: Masa kerja belum memenuhi ketentuan, mohon periksa kembali.'}
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
                        <div className="grid grid-cols-2 gap-2.5 sm:flex sm:items-center sm:justify-between">
                            <Button
                                type="button"
                                variant="dangerSoft"
                                size="drawer"
                                onClick={() => setAksi('reject')}
                            >
                                <X size={16} />
                                Tolak Pengajuan
                            </Button>
                            <Button
                                type="button"
                                variant="primary"
                                size="drawer"
                                onClick={() => setAksi('approve')}
                            >
                                <Check size={16} />
                                Setujui Aktivasi
                            </Button>
                        </div>
                    ) : (
                        <div className="grid grid-cols-2 gap-2.5 sm:flex sm:items-center sm:justify-between">
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
                        </div>
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

export default function Index({ menunggu, riwayat, ketentuanFinansial }) {
    const [tab, setTab] = useState('baru');
    const [detail, setDetail] = useState(null);
    const [open, setOpen] = useState(false);

    function buka(p) { setDetail(p); setOpen(true); }
    function tutup() { setOpen(false); setDetail(null); }

    return (
        <AppLayout>
            <Head title="Aktivasi Anggota" />
            <PageHeader title="Pengajuan Aktivasi Anggota" subtitle={`${menunggu.length} pengajuan menunggu keputusan`} />

            <Card padding="none">
                <div className="flex flex-wrap items-center gap-1.5 px-3.5 py-2.5 bg-gradient-to-r from-[#f4f7fb] via-[#f8fafc] to-[#f0faf4] border-b border-slate-200/80">
                    <button onClick={() => setTab('baru')} className={tab === 'baru' ? tabAktif : tabNonAktif}>
                        Menunggu Keputusan
                        {menunggu.length > 0 && (
                            <span className="inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[11px] font-bold">
                                {menunggu.length}
                            </span>
                        )}
                    </button>
                    <button onClick={() => setTab('riwayat')} className={tab === 'riwayat' ? tabAktif : tabNonAktif}>
                        Riwayat
                    </button>
                </div>

                <div className="overflow-x-auto">
                    <table className="w-full table-sticky-first">
                        <thead>
                            <tr className="text-left sticky top-0 bg-gradient-to-r from-[#f4f7fb] via-[#f8fafc] to-[#f1faf4] border-b border-slate-200/80 z-10">
                                <th className="px-5 py-3 text-sm font-semibold text-slate-500">Pemohon</th>
                                <th className="hidden md:table-cell px-5 py-3 text-sm font-semibold text-slate-500">Cabang</th>
                                <th className="px-5 py-3 text-sm font-semibold text-slate-500">Tanggal</th>
                                <th className="px-5 py-3 text-sm font-semibold text-slate-500">Status</th>
                                <th className="w-10" aria-label="Buka detail" />
                            </tr>
                        </thead>
                        <tbody>
                            {(tab === 'baru' ? menunggu : riwayat).length === 0 ? (
                                <tr>
                                    <td colSpan={5} className="px-5 py-8 text-center text-base text-slate-400">
                                        {tab === 'baru' ? 'Tidak ada pengajuan yang menunggu keputusan.' : 'Belum ada riwayat keputusan.'}
                                    </td>
                                </tr>
                            ) : (
                                (tab === 'baru' ? menunggu : riwayat).map((p) => (
                                    <tr
                                        key={p.id}
                                        onClick={() => buka(p)}
                                        onKeyDown={(e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); buka(p); } }}
                                        tabIndex={0}
                                        title="Klik untuk mereview"
                                        className="group border-t border-slate-100/80 hover:bg-brand-green-light/35 transition-colors cursor-pointer focus-visible:outline-none focus-visible:bg-brand-green-light/45"
                                    >
                                        <td className="px-5 py-3">
                                            <div className="flex items-center gap-3">
                                                <FotoAnggota nama={p.anggota.nama} fotoUrl={p.anggota.foto_url} ukuran="sm" />
                                                <div className="min-w-0">
                                                    <p className="text-sm font-semibold text-slate-800 truncate">{p.anggota.nama}</p>
                                                    <p className="text-xs text-slate-400">{p.anggota.no_karyawan}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td className="hidden md:table-cell px-5 py-3 text-sm text-slate-600">{p.anggota.cabang}</td>
                                        <td className="px-5 py-3 text-sm text-slate-600">{p.tanggal_pengajuan}</td>
                                        <td className="px-5 py-3"><StatusBadge status={p.status === 'diajukan' ? 'pending' : p.status} /></td>
                                        <td className="px-3 py-3 text-right" aria-hidden="true">
                                            <ChevronRight size={18} className="ml-auto text-slate-300 group-hover:text-brand-green group-hover:translate-x-0.5 transition-all" />
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            </Card>

            <Drawer
                show={open}
                layout="split"
                title={`Aktivasi — ${detail?.anggota?.nama ?? 'Anggota'}`}
                badge={detail ? <StatusBadge status={detail.status === 'diajukan' ? 'pending' : detail.status} /> : null}
                onClose={tutup}
                maxWidth="2xl"
            >
                {detail && <KeputusanDrawer key={detail.id} pengajuan={detail} ketentuanFinansial={ketentuanFinansial} onClose={tutup} />}
            </Drawer>
        </AppLayout>
    );
}
