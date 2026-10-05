import AppLayout from '@/Layouts/AppLayout';
import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { ChevronRight } from 'lucide-react';
import Card from '@/Components/ui/Card';
import FotoAnggota from '@/Components/ui/FotoAnggota';
import StatusBadge from '@/Components/ui/StatusBadge';
import PageHeader from '@/Components/ui/PageHeader';
import Drawer from '@/Components/ui/Drawer';
import Button from '@/Components/ui/Button';
import { withIdempotencyKey } from '@/Utils/idempotency';

const tabDasar = 'inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-t-lg border -mb-px';
const tabAktif = `${tabDasar} bg-white border-slate-200 border-b-0 text-brand-navy`;
const tabNonAktif = `${tabDasar} bg-slate-50 border-slate-200 text-slate-500 hover:bg-white hover:text-slate-700`;

function KeputusanDrawer({ pengajuan, onClose }) {
    const [aksi, setAksi] = useState(null);
    const { data, setData, post, processing, errors } = useForm({ catatan: '' });
    const bisaDiproses = pengajuan.status === 'diajukan';

    function submit(e) {
        e.preventDefault();
        const url = aksi === 'approve'
            ? route('ketua.aktivasi.approve', pengajuan.id)
            : route('ketua.aktivasi.reject', pengajuan.id);
        post(url, withIdempotencyKey({ preserveScroll: true, onSuccess: () => onClose() }));
    }

    return (
        <div className="space-y-4">
            <div className="bg-brand-navy rounded-2xl p-5 text-white">
                <div className="flex items-start justify-between gap-4">
                    <div>
                        <p className="text-xs text-slate-300 mb-1">Pemohon</p>
                        <p className="text-xl font-bold">{pengajuan.anggota.nama}</p>
                        <p className="text-sm text-slate-300 mt-0.5">{pengajuan.anggota.no_karyawan} &bull; {pengajuan.tanggal_pengajuan}</p>
                    </div>
                    <StatusBadge status={pengajuan.status === 'diajukan' ? 'pending' : pengajuan.status} />
                </div>
            </div>

            <div className="bg-slate-50 rounded-xl p-4 border border-slate-200">
                <p className="text-sm font-bold text-slate-700 mb-2">Data Keanggotaan (GATE)</p>
                <div className="grid grid-cols-2 gap-x-6 gap-y-2 text-sm">
                    <div><p className="text-xs text-slate-400">No. Anggota</p><p className="font-semibold text-slate-800">{pengajuan.anggota.no_anggota}</p></div>
                    <div><p className="text-xs text-slate-400">Cabang</p><p className="font-semibold text-slate-800">{pengajuan.anggota.cabang}</p></div>
                    <div><p className="text-xs text-slate-400">Unit Bisnis</p><p className="font-semibold text-slate-800">{pengajuan.anggota.unit_bisnis}</p></div>
                    <div><p className="text-xs text-slate-400">Jabatan</p><p className="font-semibold text-slate-800">{pengajuan.anggota.jabatan}</p></div>
                    <div><p className="text-xs text-slate-400">Departemen</p><p className="font-semibold text-slate-800">{pengajuan.anggota.department}</p></div>
                    <div><p className="text-xs text-slate-400">No. HP</p><p className="font-semibold text-slate-800">{pengajuan.anggota.no_hp ?? '-'}</p></div>
                </div>
                {pengajuan.anggota.alamat && (
                    <p className="text-sm text-slate-600 mt-2">{pengajuan.anggota.alamat}</p>
                )}
                <p className="text-xs text-slate-400 mt-3">Versi syarat disetujui pemohon: {pengajuan.versi_syarat}</p>
            </div>

            {pengajuan.catatan_ketua && (
                <div className="bg-slate-50 rounded-xl p-4 border border-slate-200">
                    <p className="text-xs text-slate-400 mb-1">Catatan Ketua</p>
                    <p className="text-sm text-slate-700">{pengajuan.catatan_ketua}</p>
                </div>
            )}

            <div className="pt-4 border-t border-slate-100">
                {errors.keputusan && <p className="text-sm text-red-600 mb-3">{errors.keputusan}</p>}
                {bisaDiproses ? (
                    !aksi ? (
                        <div className="flex items-center gap-3">
                            <Button variant="primary" onClick={() => setAksi('approve')}>Setujui</Button>
                            <Button variant="danger" onClick={() => setAksi('reject')}>Tolak</Button>
                        </div>
                    ) : (
                        <form onSubmit={submit}>
                            <label className="block text-sm font-semibold text-slate-600 mb-2">
                                Catatan {aksi === 'approve' ? 'Persetujuan' : 'Penolakan'}
                            </label>
                            <textarea
                                value={data.catatan}
                                onChange={(e) => setData('catatan', e.target.value)}
                                rows={3}
                                placeholder={aksi === 'approve' ? 'Contoh: Data telah sesuai, pengajuan disetujui.' : 'Contoh: Data belum lengkap, mohon dilengkapi.'}
                                className="w-full px-4 py-2.5 text-base rounded-xl border border-slate-300 focus:border-brand-green focus:ring-2 focus:ring-brand-green/20 outline-none transition-colors"
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
                    <StatusBadge status={pengajuan.status} />
                )}
            </div>
        </div>
    );
}

export default function Index({ menunggu, riwayat }) {
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
                {detail && <KeputusanDrawer key={detail.id} pengajuan={detail} onClose={tutup} />}
            </Drawer>
        </AppLayout>
    );
}
