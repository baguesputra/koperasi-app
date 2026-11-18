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
import RingkasanKas from '@/Pages/PinjamanApproval/RingkasanKas';
import { formatRupiah } from '@/Utils/formatCurrency';

const tabDasar = 'inline-flex items-center gap-2 px-3.5 py-2 text-xs sm:text-sm font-semibold rounded-xl transition-all duration-150';
const tabAktif = `${tabDasar} bg-gradient-to-b from-white to-brand-green-light/30 border border-brand-green/40 text-brand-navy shadow-sm`;
const tabNonAktif = `${tabDasar} text-slate-600 hover:bg-white/80 hover:text-brand-navy`;

export default function Index({ menungguApproval, riwayat, ringkasanKas }) {
    const [tab, setTab] = useState('baru');
    const [detailPinjaman, setDetailPinjaman] = useState(null);
    const [drawerOpen, setDrawerOpen] = useState(false);

    function bukaDetail(p) {
        setDetailPinjaman(p);
        setDrawerOpen(true);
    }

    function tutupDetail() {
        setDrawerOpen(false);
    }

    return (
        <AppLayout>
            <Head title="Approval Pinjaman" />

            <PageHeader title="Approval Pinjaman" subtitle={`${menungguApproval.length} pengajuan menunggu approval final Anda`} />

            <RingkasanKas ringkasan={ringkasanKas} />

            <Card padding="none">
                {/* Tab lembaran dokumen */}
                <div className="flex flex-wrap items-center gap-1.5 px-3.5 py-2.5 bg-gradient-to-r from-[#f4f7fb] via-[#f8fafc] to-[#f0faf4] border-b border-slate-200/80">
                    <button onClick={() => setTab('baru')} className={tab === 'baru' ? tabAktif : tabNonAktif}>
                        Approval Baru
                        {menungguApproval.length > 0 && (
                            <span className="inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[11px] font-bold">
                                {menungguApproval.length}
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
                                <tr className="text-left border-b border-slate-200/80 bg-gradient-to-r from-[#f4f7fb] via-[#f8fafc] to-[#f1faf4]">
                                    <th className="px-5 py-3 text-sm font-semibold text-slate-500">Anggota</th>
                                    <th className="px-5 py-3 text-sm font-semibold text-slate-500">Nominal</th>
                                    <th className="px-5 py-3 text-sm font-semibold text-slate-500">Tenor</th>
                                    <th className="px-5 py-3 text-sm font-semibold text-slate-500">Tanggal</th>
                                    <th className="px-5 py-3 text-sm font-semibold text-slate-500">Status</th>
                                    <th className="w-10" aria-label="Buka detail" />
                                </tr>
                            </thead>
                            <tbody>
                                {menungguApproval.length === 0 ? (
                                    <tr>
                                        <td colSpan={6} className="px-5 py-8 text-center text-base text-slate-400">
                                            Tidak ada pengajuan yang menunggu approval.
                                        </td>
                                    </tr>
                                ) : (
                                    menungguApproval.map((p) => (
                                        <tr
                                            key={p.id}
                                            onClick={() => bukaDetail(p)}
                                            onKeyDown={(e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); bukaDetail(p); } }}
                                            tabIndex={0}
                                            title="Klik untuk meninjau"
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
                                            <td className="px-5 py-3 text-sm text-slate-700 privacy-blur">{formatRupiah(p.nominal)}</td>
                                            <td className="px-5 py-3 text-sm text-slate-600">{p.tenor_bulan} bulan</td>
                                            <td className="px-5 py-3 text-sm text-slate-600">{p.tanggal_pengajuan}</td>
                                            <td className="px-5 py-3"><StatusBadge status={p.status} /></td>
                                            <td className="px-3 py-3 text-right" aria-hidden="true">
                                                <ChevronRight size={18} className="ml-auto text-slate-300 group-hover:text-brand-green group-hover:translate-x-0.5 transition-all" />
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    ) : (
                        <table className="w-full table-sticky-first">
                            <thead>
                                <tr className="text-left border-b border-slate-200/80 bg-gradient-to-r from-[#f4f7fb] via-[#f8fafc] to-[#f1faf4]">
                                    <th className="px-5 py-3 text-sm font-semibold text-slate-500">Anggota</th>
                                    <th className="px-5 py-3 text-sm font-semibold text-slate-500">Nominal</th>
                                    <th className="px-5 py-3 text-sm font-semibold text-slate-500">Tenor</th>
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
                                        <tr key={p.id} onClick={() => bukaDetail(p)} className="group border-t border-slate-100/80 hover:bg-brand-green-light/35 transition-colors cursor-pointer">
                                            <td className="px-5 py-3">
                                                <p className="text-sm font-semibold text-slate-800 truncate">{p.anggota.nama}</p>
                                                <p className="text-xs text-slate-400">{p.anggota.no_karyawan}</p>
                                            </td>
                                            <td className="px-5 py-3 text-sm text-slate-700 privacy-blur">{formatRupiah(p.nominal)}</td>
                                            <td className="px-5 py-3 text-sm text-slate-600">{p.tenor_bulan} bulan</td>
                                            <td className="px-5 py-3 text-sm text-slate-600">{p.tanggal_pengajuan}</td>
                                            <td className="px-5 py-3"><StatusBadge status={p.status} /></td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    )}
                </div>
            </Card>

            <Drawer show={drawerOpen} title={`Approval Final - ${detailPinjaman?.anggota?.nama ?? 'Anggota'}`} onClose={tutupDetail} maxWidth="3xl">
                {detailPinjaman && (
                    <KeputusanDrawer
                        key={detailPinjaman.id}
                        pinjaman={detailPinjaman}
                        onClose={tutupDetail}
                    />
                )}
            </Drawer>
        </AppLayout>
    );
}