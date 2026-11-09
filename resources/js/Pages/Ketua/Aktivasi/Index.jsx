import AppLayout from '@/Layouts/AppLayout';
import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import {
    ChevronRight, CheckCircle2, AlertCircle, Briefcase, UserCheck,
    CreditCard, ShieldCheck, MapPin
} from 'lucide-react';
import Card from '@/Components/ui/Card';
import FotoAnggota from '@/Components/ui/FotoAnggota';
import StatusBadge from '@/Components/ui/StatusBadge';
import PageHeader from '@/Components/ui/PageHeader';
import Drawer from '@/Components/ui/Drawer';
import Button from '@/Components/ui/Button';
import { formatRupiah } from '@/Utils/formatCurrency';
import { withIdempotencyKey } from '@/Utils/idempotency';

const tabDasar = 'inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-t-lg border -mb-px';
const tabAktif = `${tabDasar} bg-white border-slate-200 border-b-0 text-brand-navy`;
const tabNonAktif = `${tabDasar} bg-slate-50 border-slate-200 text-slate-500 hover:bg-white hover:text-slate-700`;

function KeputusanDrawer({ pengajuan, ketentuanFinansial, onClose }) {
    const [aksi, setAksi] = useState(null);
    const { data, setData, post, processing, errors } = useForm({ catatan: '' });
    const bisaDiproses = pengajuan.status === 'diajukan';
    const isReaktivasi = pengajuan.anggota.is_reaktivasi;

    function submit(e) {
        e.preventDefault();
        const url = aksi === 'approve'
            ? route('ketua.aktivasi.approve', pengajuan.id)
            : route('ketua.aktivasi.reject', pengajuan.id);
        post(url, withIdempotencyKey({ preserveScroll: true, onSuccess: () => onClose() }));
    }

    return (
        <div className="space-y-4">
            {/* Header Profil Pemohon */}
            <div className="bg-gradient-to-br from-[#0B1527] via-[#0F1E36] to-[#0A2E1A] rounded-2xl p-5 text-white shadow-sm border border-slate-800">
                <div className="flex items-start justify-between gap-4">
                    <div className="flex items-start gap-3.5">
                        <FotoAnggota
                            nama={pengajuan.anggota.nama}
                            fotoUrl={pengajuan.anggota.foto_url}
                            ukuran="lg"
                            className="ring-2 ring-white/20 shrink-0"
                        />
                        <div>
                            <div className="flex items-center gap-2 flex-wrap">
                                <h3 className="text-xl font-bold text-white tracking-tight">
                                    {pengajuan.anggota.nama}
                                </h3>
                                {isReaktivasi ? (
                                    <span className="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-300 border border-amber-400/30">
                                        Re-Aktivasi
                                    </span>
                                ) : (
                                    <span className="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-400/30">
                                        Anggota Baru
                                    </span>
                                )}
                            </div>
                            <p className="text-xs text-slate-300 mt-1 flex items-center gap-2 flex-wrap">
                                <span>No. Karyawan: <strong className="text-white">{pengajuan.anggota.no_karyawan}</strong></span>
                                <span>&bull;</span>
                                <span>No. Anggota: <strong className="text-white">{pengajuan.anggota.no_anggota}</strong></span>
                            </p>
                            <p className="text-[11px] text-slate-400 mt-0.5">
                                Diajukan pada: {pengajuan.tanggal_pengajuan}
                            </p>
                        </div>
                    </div>
                    <StatusBadge status={pengajuan.status === 'diajukan' ? 'pending' : pengajuan.status} />
                </div>
            </div>

            {/* Kepegawaian & Masa Kerja (GATE HRIS) */}
            <div className="bg-slate-50/80 rounded-2xl p-4 border border-slate-200/90 shadow-2xs">
                <div className="flex items-center justify-between mb-3">
                    <div className="flex items-center gap-2 text-slate-800 font-bold text-sm">
                        <Briefcase size={16} className="text-brand-green" />
                        <h4>Kepegawaian &amp; Masa Kerja</h4>
                    </div>
                    {pengajuan.anggota.gate_synced_at && (
                        <span className="text-[10px] text-slate-400 font-medium">
                            Sinkron GATE: {pengajuan.anggota.gate_synced_at}
                        </span>
                    )}
                </div>

                <div className="grid grid-cols-2 gap-x-4 gap-y-2.5 text-sm">
                    <div className="bg-white p-2.5 rounded-xl border border-slate-200/70">
                        <p className="text-[11px] text-slate-500 font-medium">Masa Kerja Perusahaan</p>
                        <p className="font-bold text-slate-900 mt-0.5">
                            {pengajuan.anggota.masa_kerja ?? '-'}
                        </p>
                        {pengajuan.anggota.tanggal_mulai_kerja && (
                            <p className="text-[10px] text-slate-400">
                                Mulai: {pengajuan.anggota.tanggal_mulai_kerja}
                            </p>
                        )}
                    </div>
                    <div className="bg-white p-2.5 rounded-xl border border-slate-200/70">
                        <p className="text-[11px] text-slate-500 font-medium">Cabang &amp; Unit Bisnis</p>
                        <p className="font-bold text-slate-900 mt-0.5 truncate">
                            {pengajuan.anggota.cabang ?? '-'}
                        </p>
                        <p className="text-[10px] text-slate-400 truncate">
                            {pengajuan.anggota.unit_bisnis ?? '-'}
                        </p>
                    </div>
                    <div className="bg-white p-2.5 rounded-xl border border-slate-200/70">
                        <p className="text-[11px] text-slate-500 font-medium">Jabatan</p>
                        <p className="font-semibold text-slate-800 mt-0.5 truncate">
                            {pengajuan.anggota.jabatan ?? '-'}
                        </p>
                    </div>
                    <div className="bg-white p-2.5 rounded-xl border border-slate-200/70">
                        <p className="text-[11px] text-slate-500 font-medium">Departemen / Divisi</p>
                        <p className="font-semibold text-slate-800 mt-0.5 truncate">
                            {pengajuan.anggota.department ?? '-'}
                        </p>
                        {pengajuan.anggota.divisi && (
                            <p className="text-[10px] text-slate-400 truncate">
                                Divisi: {pengajuan.anggota.divisi}
                            </p>
                        )}
                    </div>
                </div>
            </div>

            {/* Identitas & Kontak */}
            <div className="bg-slate-50/80 rounded-2xl p-4 border border-slate-200/90 shadow-2xs">
                <div className="flex items-center gap-2 text-slate-800 font-bold text-sm mb-3">
                    <UserCheck size={16} className="text-brand-green" />
                    <h4>Identitas &amp; Kontak</h4>
                </div>
                <div className="grid grid-cols-2 gap-x-4 gap-y-2.5 text-sm">
                    <div>
                        <p className="text-[11px] text-slate-500 font-medium">No. KTP / NIK</p>
                        <p className="font-semibold text-slate-800 font-mono">
                            {pengajuan.anggota.no_ktp ?? '-'}
                        </p>
                    </div>
                    <div>
                        <p className="text-[11px] text-slate-500 font-medium">No. WhatsApp / HP</p>
                        <p className="font-semibold text-slate-800">
                            {pengajuan.anggota.no_hp ?? '-'}
                        </p>
                    </div>
                    <div>
                        <p className="text-[11px] text-slate-500 font-medium">Email Akun SSO</p>
                        <p className="font-semibold text-slate-800 truncate" title={pengajuan.anggota.email}>
                            {pengajuan.anggota.email ?? '-'}
                        </p>
                    </div>
                    <div>
                        <p className="text-[11px] text-slate-500 font-medium">Status Keanggotaan</p>
                        <p className="font-semibold text-slate-800 capitalize">
                            {pengajuan.anggota.status}
                        </p>
                    </div>
                </div>
                {pengajuan.anggota.alamat && (
                    <div className="mt-2.5 pt-2.5 border-t border-slate-200/60 text-xs text-slate-600 flex items-start gap-1.5">
                        <MapPin size={13} className="text-slate-400 shrink-0 mt-0.5" />
                        <span>Alamat: {pengajuan.anggota.alamat}</span>
                    </div>
                )}
            </div>

            {/* Ketentuan Finansial Pasca-Aktivasi */}
            {ketentuanFinansial && (
                <div className="bg-emerald-50/60 rounded-2xl p-4 border border-emerald-200/80 shadow-2xs">
                    <div className="flex items-center gap-2 text-emerald-900 font-bold text-sm mb-3">
                        <CreditCard size={16} className="text-emerald-700" />
                        <h4>Ketentuan Finansial Pasca-Aktivasi</h4>
                    </div>
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-2.5 text-xs">
                        <div className="bg-white/90 p-3 rounded-xl border border-emerald-200/70">
                            <p className="text-[11px] text-slate-500 font-medium">Simpanan Pokok</p>
                            <p className="text-base font-extrabold text-emerald-800 mt-0.5">
                                {formatRupiah(ketentuanFinansial.simpanan_pokok ?? 50000)}
                            </p>
                            <p className="text-[10px] text-slate-400 mt-0.5">
                                Dicatat otomatis ke buku kas
                            </p>
                        </div>
                        <div className="bg-white/90 p-3 rounded-xl border border-emerald-200/70">
                            <p className="text-[11px] text-slate-500 font-medium">Iuran Bulanan</p>
                            <p className="text-base font-extrabold text-slate-800 mt-0.5">
                                {formatRupiah((ketentuanFinansial.simpanan_wajib ?? 45000) + (ketentuanFinansial.dana_sosial ?? 5000))}<span className="text-xs font-normal text-slate-400">/bln</span>
                            </p>
                            <p className="text-[10px] text-slate-400 mt-0.5">
                                Wajib + Dana Sosial
                            </p>
                        </div>
                        <div className="bg-white/90 p-3 rounded-xl border border-emerald-200/70">
                            <p className="text-[11px] text-slate-500 font-medium">Plafon Pinjaman Awal</p>
                            <p className="text-base font-extrabold text-brand-navy mt-0.5">
                                {formatRupiah(ketentuanFinansial.limit_awal ?? 1000000)}
                            </p>
                            <p className="text-[10px] text-slate-400 mt-0.5">
                                Hak limit pinjaman pertama
                            </p>
                        </div>
                    </div>
                </div>
            )}

            {/* Riwayat Resign Sebelumnya (Jika Re-Aktivasi) */}
            {isReaktivasi && (
                <div className="bg-amber-50/80 rounded-2xl p-4 border border-amber-200/90 text-xs text-amber-900 shadow-2xs">
                    <div className="flex items-center gap-1.5 font-bold mb-1.5 text-amber-950">
                        <AlertCircle size={15} className="text-amber-600" />
                        <span>Riwayat Keanggotaan Sebelumnya</span>
                    </div>
                    <p className="text-slate-700">
                        Anggota ini sebelumnya pernah mengundurkan diri / resign pada tanggal: <strong>{pengajuan.anggota.tanggal_resign ?? '-'}</strong>
                    </p>
                    {pengajuan.anggota.alasan_resign && (
                        <p className="text-slate-600 mt-1 italic bg-white/70 p-2 rounded-lg border border-amber-200/60">
                            Alasan resign sebelumnya: &ldquo;{pengajuan.anggota.alasan_resign}&rdquo;
                        </p>
                    )}
                </div>
            )}

            {/* Pernyataan & Kepatuhan Pemohon */}
            <div className="bg-slate-50/80 rounded-2xl p-4 border border-slate-200/90 shadow-2xs text-xs">
                <div className="flex items-center justify-between mb-2.5">
                    <div className="flex items-center gap-2 text-slate-800 font-bold text-sm">
                        <ShieldCheck size={16} className="text-brand-green" />
                        <h4>Pernyataan Digital Pemohon</h4>
                    </div>
                    <span className="text-[10px] font-semibold text-slate-500 bg-slate-200/80 px-2 py-0.5 rounded-full">
                        {pengajuan.versi_syarat}
                    </span>
                </div>
                <div className="space-y-1.5 text-slate-700">
                    <div className="flex items-center gap-2">
                        <CheckCircle2 size={14} className="text-emerald-600 shrink-0" />
                        <span>Pemohon menyatakan data diri &amp; kepegawaian telah diisi dengan sebenarnya.</span>
                    </div>
                    <div className="flex items-center gap-2">
                        <CheckCircle2 size={14} className="text-emerald-600 shrink-0" />
                        <span>Pemohon menyetujui kepatuhan AD/ART dan seluruh kebijakan Koperasi.</span>
                    </div>
                </div>
            </div>

            {/* Catatan Ketua */}
            {pengajuan.catatan_ketua && (
                <div className="bg-slate-50 rounded-xl p-4 border border-slate-200">
                    <div className="flex items-center justify-between mb-1">
                        <p className="text-xs font-bold text-slate-600">Catatan Ketua</p>
                        {pengajuan.tanggal_keputusan && (
                            <span className="text-[10px] text-slate-400">{pengajuan.tanggal_keputusan}</span>
                        )}
                    </div>
                    <p className="text-sm text-slate-800 bg-white p-2.5 rounded-lg border border-slate-200/60">{pengajuan.catatan_ketua}</p>
                </div>
            )}

            <div className="pt-4 border-t border-slate-100">
                {errors.keputusan && <p className="text-sm text-red-600 mb-3">{errors.keputusan}</p>}
                {bisaDiproses ? (
                    !aksi ? (
                        <div className="flex items-center gap-3">
                            <Button variant="primary" onClick={() => setAksi('approve')} className="flex-1">Setujui Aktivasi</Button>
                            <Button variant="danger" onClick={() => setAksi('reject')} className="flex-1">Tolak Pengajuan</Button>
                        </div>
                    ) : (
                        <form onSubmit={submit}>
                            <label className="block text-sm font-semibold text-slate-700 mb-2">
                                Catatan {aksi === 'approve' ? 'Persetujuan' : 'Penolakan'}
                            </label>
                            <textarea
                                value={data.catatan}
                                onChange={(e) => setData('catatan', e.target.value)}
                                rows={3}
                                placeholder={aksi === 'approve' ? 'Contoh: Data telah diverifikasi sesuai HRIS GATE, aktivasi disetujui.' : 'Contoh: Masa kerja belum memenuhi ketentuan, mohon periksa kembali.'}
                                className="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-300 focus:border-brand-green focus:ring-2 focus:ring-brand-green/20 outline-none transition-colors"
                                autoFocus
                            />
                            {errors.catatan && <p className="text-sm text-red-600 mt-1.5">{errors.catatan}</p>}
                            <div className="flex items-center gap-3 mt-4">
                                <Button type="submit" variant={aksi === 'approve' ? 'primary' : 'danger'} disabled={processing}>
                                    {processing ? 'Memproses...' : `Konfirmasi ${aksi === 'approve' ? 'Setujui' : 'Tolak'}`}
                                </Button>
                                <Button type="button" variant="ghost" onClick={() => setAksi(null)}>Batal</Button>
                            </div>
                        </form>
                    )
                ) : (
                    <div className="flex items-center justify-between bg-slate-50 p-3 rounded-xl border border-slate-200">
                        <span className="text-xs text-slate-500 font-medium">Status Keputusan:</span>
                        <StatusBadge status={pengajuan.status} />
                    </div>
                )}
            </div>
        </div>
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
                <div className="flex items-end gap-1 px-3 pt-2 border-b border-slate-200">
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
                            <tr className="text-left sticky top-0 bg-white z-10">
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
                                        className="border-t border-slate-50 hover:bg-slate-50 transition-colors cursor-pointer focus-visible:outline-none focus-visible:bg-slate-100"
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
                                            <ChevronRight size={18} className="ml-auto text-slate-300" />
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            </Card>

            <Drawer show={open} title={`Aktivasi - ${detail?.anggota?.nama ?? 'Anggota'}`} onClose={tutup} maxWidth="2xl">
                {detail && <KeputusanDrawer key={detail.id} pengajuan={detail} ketentuanFinansial={ketentuanFinansial} onClose={tutup} />}
            </Drawer>
        </AppLayout>
    );
}
