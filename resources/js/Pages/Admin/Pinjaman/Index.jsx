import AppLayout from '@/Layouts/AppLayout';
import { Head } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import { useState } from 'react';
import Card from '@/Components/ui/Card';
import CountBadge from '@/Components/ui/CountBadge';
import FotoAnggota from '@/Components/ui/FotoAnggota';
import StatusBadge from '@/Components/ui/StatusBadge';
import PageHeader from '@/Components/ui/PageHeader';
import Drawer from '@/Components/ui/Drawer';
import KeputusanDrawer from './Partials/KeputusanDrawer';
import RingkasanKas from '@/Pages/PinjamanApproval/RingkasanKas';
import { formatRupiah } from '@/Utils/formatCurrency';

const tabDasar = 'inline-flex items-center gap-2 px-3.5 py-2 text-xs sm:text-sm font-semibold rounded-xl transition-all duration-150';
const tabAktif = `${tabDasar} bg-brand-green-light/45 border border-brand-green/35 text-brand-navy`;
const tabNonAktif = `${tabDasar} text-slate-600 hover:bg-slate-100/70 hover:text-brand-navy`;

export default function Index({ menungguTinjauan, riwayat, ringkasanKas }) {
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
            <Head title="Verifikasi Awal Pinjaman" />

            <PageHeader
                title="Verifikasi Awal Pinjaman"
                subtitle={`${menungguTinjauan.length} pengajuan menunggu verifikasi Anda`}
            />

            <RingkasanKas ringkasan={ringkasanKas} judul="Beban Verifikasi Pinjaman" />

            <Card padding="none" className="overflow-hidden">
                {/* Tab lembaran dokumen */}
                <div className="flex flex-wrap items-center gap-1.5 px-3.5 py-2.5 bg-white border-b border-slate-200/80">
                    <button onClick={() => setTab('baru')} className={tab === 'baru' ? tabAktif : tabNonAktif}>
                        Verifikasi Baru
                        <CountBadge count={menungguTinjauan.length} context="tab" />
                    </button>
                    <button onClick={() => setTab('riwayat')} className={tab === 'riwayat' ? tabAktif : tabNonAktif}>
                        Riwayat
                    </button>
                </div>

                {/* Tabel dalam kartu yang sama */}
                <div className="overflow-x-auto">
                    {tab === 'baru' ? (
                        <table className="w-full table-sticky-first text-sm">
                            <thead>
                                <tr className="text-left border-b border-slate-200/80 bg-[#F8FAFC]">
                                    <th className="px-4 py-2.5 text-[11px] font-bold uppercase tracking-wider text-slate-500 whitespace-nowrap">Anggota</th>
                                    <th className="px-4 py-2.5 text-[11px] font-bold uppercase tracking-wider text-slate-500 whitespace-nowrap">Nominal</th>
                                    <th className="px-4 py-2.5 text-[11px] font-bold uppercase tracking-wider text-slate-500 whitespace-nowrap">Tenor</th>
                                    <th className="px-4 py-2.5 text-[11px] font-bold uppercase tracking-wider text-slate-500 whitespace-nowrap">Tanggal</th>
                                    <th className="px-4 py-2.5 text-[11px] font-bold uppercase tracking-wider text-slate-500 whitespace-nowrap">Status</th>
                                    <th className="w-10 px-3 py-2.5" aria-label="Buka detail" />
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100 bg-white">
                                {menungguTinjauan.length === 0 ? (
                                    <tr>
                                        <td colSpan={6} className="px-4 py-8 text-center text-sm font-medium text-slate-500">
                                            Tidak ada pengajuan yang menunggu verifikasi.
                                        </td>
                                    </tr>
                                ) : (
                                    menungguTinjauan.map((p) => (
                                        <tr
                                            key={p.id}
                                            onClick={() => bukaDetail(p)}
                                            onKeyDown={(e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); bukaDetail(p); } }}
                                            tabIndex={0}
                                            title="Klik untuk meninjau"
                                            className="group hover:bg-[#F2FBF5] transition-colors cursor-pointer focus-visible:outline-none focus-visible:bg-[#F2FBF5]"
                                        >
                                            <td className="px-4 py-2.5">
                                                <div className="flex items-center gap-3">
                                                    <FotoAnggota nama={p.anggota.nama} fotoUrl={p.anggota.foto_url} ukuran="sm" />
                                                    <div className="min-w-0">
                                                        <p className="text-sm font-semibold text-slate-800 truncate">{p.anggota.nama}</p>
                                                        <p className="text-xs text-slate-500">{p.anggota.no_karyawan}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-4 py-2.5 text-sm text-slate-700 tabular-nums privacy-blur">{formatRupiah(p.nominal)}</td>
                                            <td className="px-4 py-2.5 text-sm text-slate-600">{p.tenor_bulan} bulan</td>
                                            <td className="px-4 py-2.5 text-sm text-slate-600">{p.tanggal_pengajuan}</td>
                                            <td className="px-4 py-2.5"><StatusBadge status="pending" /></td>
                                            <td className="px-3 py-2.5 text-right" aria-hidden="true">
                                                <ChevronRight size={18} className="ml-auto text-slate-300 group-hover:text-brand-green group-hover:translate-x-0.5 transition-all" />
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    ) : (
                        <table className="w-full table-sticky-first text-sm">
                            <thead>
                                <tr className="text-left border-b border-slate-200/80 bg-[#F8FAFC]">
                                    <th className="px-4 py-2.5 text-[11px] font-bold uppercase tracking-wider text-slate-500 whitespace-nowrap">Anggota</th>
                                    <th className="px-4 py-2.5 text-[11px] font-bold uppercase tracking-wider text-slate-500 whitespace-nowrap">Nominal</th>
                                    <th className="px-4 py-2.5 text-[11px] font-bold uppercase tracking-wider text-slate-500 whitespace-nowrap">Tenor</th>
                                    <th className="px-4 py-2.5 text-[11px] font-bold uppercase tracking-wider text-slate-500 whitespace-nowrap">Tanggal</th>
                                    <th className="px-4 py-2.5 text-[11px] font-bold uppercase tracking-wider text-slate-500 whitespace-nowrap">Status</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100 bg-white">
                                {riwayat.length === 0 ? (
                                    <tr>
                                        <td colSpan={5} className="px-4 py-8 text-center text-sm font-medium text-slate-500">
                                            Belum ada riwayat keputusan.
                                        </td>
                                    </tr>
                                ) : (
                                    riwayat.map((p) => (
                                        <tr key={p.id} onClick={() => bukaDetail(p)} className="group hover:bg-[#F2FBF5] transition-colors cursor-pointer">
                                            <td className="px-4 py-2.5">
                                                <p className="text-sm font-semibold text-slate-800 truncate">{p.anggota.nama}</p>
                                                <p className="text-xs text-slate-500">{p.anggota.no_karyawan}</p>
                                            </td>
                                            <td className="px-4 py-2.5 text-sm text-slate-700 tabular-nums privacy-blur">{formatRupiah(p.nominal)}</td>
                                            <td className="px-4 py-2.5 text-sm text-slate-600">{p.tenor_bulan} bulan</td>
                                            <td className="px-4 py-2.5 text-sm text-slate-600">{p.tanggal_pengajuan}</td>
                                            <td className="px-4 py-2.5"><StatusBadge status={p.status} /></td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    )}
                </div>
            </Card>

            <Drawer
                show={drawerOpen}
                layout="split"
                title={`Verifikasi Pinjaman — ${detailPinjaman?.anggota?.nama ?? 'Anggota'}`}
                badge={detailPinjaman ? <StatusBadge status={detailPinjaman.status} /> : null}
                onClose={tutupDetail}
                maxWidth="3xl"
            >
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
