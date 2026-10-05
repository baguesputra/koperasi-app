import AppLayout from '@/Layouts/AppLayout';
import { Head } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import { useState } from 'react';
import Card from '@/Components/ui/Card';
import FotoAnggota from '@/Components/ui/FotoAnggota';
import StatusBadge from '@/Components/ui/StatusBadge';
import PageHeader from '@/Components/ui/PageHeader';
import Drawer from '@/Components/ui/Drawer';
import KeputusanDrawer from './Partials/KeputusanDrawer';
import { formatRupiah } from '@/Utils/formatCurrency';

const tabDasar = 'inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-t-lg border -mb-px';
const tabAktif = `${tabDasar} bg-white border-slate-200 border-b-0 text-brand-navy`;
const tabNonAktif = `${tabDasar} bg-slate-50 border-slate-200 text-slate-500 hover:bg-white hover:text-slate-700`;

export default function Index({ menunggu, riwayat }) {
    const [tab, setTab] = useState('baru');
    const [detailPengajuan, setDetailPengajuan] = useState(null);
    const [drawerOpen, setDrawerOpen] = useState(false);

    function bukaDetail(p) {
        setDetailPengajuan(p);
        setDrawerOpen(true);
    }

    function tutupDetail() {
        setDrawerOpen(false);
        setDetailPengajuan(null);
    }

    return (
        <AppLayout>
            <Head title="Pengajuan Limit" />

            <PageHeader title="Pengajuan Tambah Limit" subtitle={`${menunggu.length} pengajuan menunggu keputusan final Anda`} />

            <Card padding="none">
                {/* Tab lembaran dokumen */}
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

                {/* Tabel dalam kartu yang sama */}
                <div className="overflow-x-auto">
                    {tab === 'baru' ? (
                        <table className="w-full table-sticky-first">
                            <thead>
                                <tr className="text-left sticky top-0 bg-white z-10">
                                    <th className="px-5 py-3 text-sm font-semibold text-slate-500">Anggota</th>
                                    <th className="px-5 py-3 text-sm font-semibold text-slate-500">Diminta → Bendahara</th>
                                    <th className="hidden md:table-cell px-5 py-3 text-sm font-semibold text-slate-500">Cabang</th>
                                    <th className="px-5 py-3 text-sm font-semibold text-slate-500">Tanggal</th>
                                    <th className="px-5 py-3 text-sm font-semibold text-slate-500">Status</th>
                                    <th className="w-10" aria-label="Buka detail" />
                                </tr>
                            </thead>
                            <tbody>
                                {menunggu.length === 0 ? (
                                    <tr>
                                        <td colSpan={6} className="px-5 py-8 text-center text-base text-slate-400">
                                            Tidak ada pengajuan yang menunggu keputusan.
                                        </td>
                                    </tr>
                                ) : (
                                    menunggu.map((p) => (
                                        <tr
                                            key={p.id}
                                            onClick={() => bukaDetail(p)}
                                            onKeyDown={(e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); bukaDetail(p); } }}
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
                                            <td className="px-5 py-3 text-sm text-slate-700">
                                                {formatRupiah(p.limit_diminta)} → {p.limit_disetujui_bendahara ? formatRupiah(p.limit_disetujui_bendahara) : '-'}
                                            </td>
                                            <td className="hidden md:table-cell px-5 py-3 text-sm text-slate-600">{p.anggota.cabang}</td>
                                            <td className="px-5 py-3 text-sm text-slate-600">{p.tanggal_pengajuan}</td>
                                            <td className="px-5 py-3"><StatusBadge status={p.status} /></td>
                                            <td className="px-3 py-3 text-right" aria-hidden="true">
                                                <ChevronRight size={18} className="ml-auto text-slate-300" />
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    ) : (
                        <table className="w-full table-sticky-first">
                            <thead>
                                <tr className="text-left sticky top-0 bg-white z-10">
                                    <th className="px-5 py-3 text-sm font-semibold text-slate-500">Anggota</th>
                                    <th className="px-5 py-3 text-sm font-semibold text-slate-500">Diminta → Final</th>
                                    <th className="hidden md:table-cell px-5 py-3 text-sm font-semibold text-slate-500">Cabang</th>
                                    <th className="px-5 py-3 text-sm font-semibold text-slate-500">Tanggal</th>
                                    <th className="px-5 py-3 text-sm font-semibold text-slate-500">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                {riwayat.length === 0 ? (
                                    <tr>
                                        <td colSpan={5} className="px-5 py-8 text-center text-base text-slate-400">
                                            Belum ada riwayat keputusan.
                                        </td>
                                    </tr>
                                ) : (
                                    riwayat.map((p) => (
                                        <tr key={p.id} onClick={() => bukaDetail(p)} className="border-t border-slate-50 hover:bg-slate-50 transition-colors cursor-pointer">
                                            <td className="px-5 py-3">
                                                <div className="flex items-center gap-3">
                                                    <FotoAnggota nama={p.anggota.nama} fotoUrl={p.anggota.foto_url} ukuran="sm" />
                                                    <div className="min-w-0">
                                                        <p className="text-sm font-semibold text-slate-800 truncate">{p.anggota.nama}</p>
                                                        <p className="text-xs text-slate-400">{p.anggota.no_karyawan}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-5 py-3 text-sm text-slate-700">
                                                {formatRupiah(p.limit_diminta)} → {p.limit_disetujui ? formatRupiah(p.limit_disetujui) : '-'}
                                            </td>
                                            <td className="hidden md:table-cell px-5 py-3 text-sm text-slate-600">{p.anggota.cabang}</td>
                                            <td className="px-5 py-3 text-sm text-slate-600">{p.tanggal_pengajuan}</td>
                                            <td className="px-5 py-3"><StatusBadge status={p.status === 'ditolak' ? 'ditolak' : 'disetujui'} /></td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    )}
                </div>
            </Card>

            <Drawer show={drawerOpen} title={`Detail Pengajuan Limit - ${detailPengajuan?.anggota?.nama ?? 'Anggota'}`} onClose={tutupDetail} maxWidth="3xl">
                {detailPengajuan && (
                    <KeputusanDrawer
                        key={detailPengajuan.id}
                        pengajuan={detailPengajuan}
                        onClose={tutupDetail}
                    />
                )}
            </Drawer>
        </AppLayout>
    );
}