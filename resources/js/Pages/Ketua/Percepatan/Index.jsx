import AppLayout from '@/Layouts/AppLayout';
import { Head } from '@inertiajs/react';
import { ClipboardCheck } from 'lucide-react';
import { useState } from 'react';
import Card from '@/Components/ui/Card';
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
    }

    return (
        <AppLayout>
            <Head title="Perubahan Tenor" />

            <PageHeader title="Approval Perubahan Tenor" subtitle={`${menunggu.length} pengajuan menunggu approval final Anda`} />

            <Card padding="none">
                {/* Tab lembaran dokumen */}
                <div className="flex items-end gap-1 px-3 pt-2 border-b border-slate-200">
                    <button onClick={() => setTab('baru')} className={tab === 'baru' ? tabAktif : tabNonAktif}>
                        Menunggu Approval
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
                                    <th className="px-5 py-3 text-sm font-semibold text-slate-500">Tipe</th>
                                    <th className="hidden md:table-cell px-5 py-3 text-sm font-semibold text-slate-500">Tenor Lama → Baru</th>
                                    <th className="px-5 py-3 text-sm font-semibold text-slate-500">Tanggal</th>
                                    <th className="px-5 py-3 text-sm font-semibold text-slate-500">Status</th>
                                    <th className="px-5 py-3 text-right text-sm font-semibold text-slate-500">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                {menunggu.length === 0 ? (
                                    <tr>
                                        <td colSpan={6} className="px-5 py-8 text-center text-base text-slate-400">
                                            Tidak ada pengajuan yang menunggu approval.
                                        </td>
                                    </tr>
                                ) : (
                                    menunggu.map((p) => (
                                        <tr key={p.id} onClick={() => bukaDetail(p)} className="border-t border-slate-50 hover:bg-slate-50 transition-colors cursor-pointer">
                                            <td className="px-5 py-3">
                                                <div className="flex items-center gap-3">
                                                    <div className="w-9 h-9 rounded-full bg-brand-green text-white flex items-center justify-center text-sm font-bold shrink-0">
                                                        {p.anggota.nama.charAt(0).toUpperCase()}
                                                    </div>
                                                    <div className="min-w-0">
                                                        <p className="text-sm font-semibold text-slate-800 truncate">{p.anggota.nama}</p>
                                                        <p className="text-xs text-slate-400">{p.anggota.no_anggota}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-5 py-3 text-sm text-slate-700">{p.tipe_label ?? p.tipe}</td>
                                            <td className="hidden md:table-cell px-5 py-3 text-sm text-slate-600">{p.tenor_lama} → {p.tenor_baru} bln</td>
                                            <td className="px-5 py-3 text-sm text-slate-600">{p.tanggal_pengajuan}</td>
                                            <td className="px-5 py-3"><StatusBadge status="pending" /></td>
                                            <td className="px-5 py-3 text-right">
                                                <button
                                                    onClick={(e) => { e.stopPropagation(); bukaDetail(p); }}
                                                    className="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-bold rounded-lg bg-brand-green text-white hover:bg-brand-green-dark transition-colors"
                                                >
                                                    <ClipboardCheck size={15} />
                                                    Review
                                                </button>
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
                                    <th className="px-5 py-3 text-sm font-semibold text-slate-500">Tipe</th>
                                    <th className="hidden md:table-cell px-5 py-3 text-sm font-semibold text-slate-500">Tenor</th>
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
                                                    <div className="w-9 h-9 rounded-full bg-brand-green text-white flex items-center justify-center text-sm font-bold shrink-0">
                                                        {p.anggota.nama.charAt(0).toUpperCase()}
                                                    </div>
                                                    <div className="min-w-0">
                                                        <p className="text-sm font-semibold text-slate-800 truncate">{p.anggota.nama}</p>
                                                        <p className="text-xs text-slate-400">{p.anggota.no_anggota}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-5 py-3 text-sm text-slate-700">{p.tipe_label ?? p.tipe}</td>
                                            <td className="hidden md:table-cell px-5 py-3 text-sm text-slate-600">{p.tenor_baru} bln</td>
                                            <td className="px-5 py-3 text-sm text-slate-600">{p.tanggal_pengajuan}</td>
                                            <td className="px-5 py-3"><StatusBadge status={p.status === 'ditolak' ? 'ditolak' : 'lunas'} /></td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    )}
                </div>
            </Card>

            <Drawer show={drawerOpen} title={`Approval Final - ${detailPengajuan?.anggota?.nama ?? 'Anggota'}`} onClose={tutupDetail} maxWidth="3xl">
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