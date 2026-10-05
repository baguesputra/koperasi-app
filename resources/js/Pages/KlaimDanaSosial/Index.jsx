import AppLayout from '@/Layouts/AppLayout';
import { Head } from '@inertiajs/react';
import { ChevronRight, HeartHandshake } from 'lucide-react';
import { useState } from 'react';
import Card from '@/Components/ui/Card';
import FotoAnggota from '@/Components/ui/FotoAnggota';
import StatusBadge from '@/Components/ui/StatusBadge';
import PageHeader from '@/Components/ui/PageHeader';
import Drawer from '@/Components/ui/Drawer';
import KeputusanDrawer from '@/Pages/KlaimDanaSosial/Partials/KeputusanDrawer';
import { formatRupiah } from '@/Utils/formatCurrency';

const tabDasar = 'inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-t-lg border -mb-px';
const tabAktif = `${tabDasar} bg-white border-slate-200 border-b-0 text-brand-navy`;
const tabNonAktif = `${tabDasar} bg-slate-50 border-slate-200 text-slate-500 hover:bg-white hover:text-slate-700`;

export default function Index({ menunggu, riwayat, tahap }) {
    const bendahara = tahap === 'bendahara';
    const [tab, setTab] = useState('baru');
    const [detailKlaim, setDetailKlaim] = useState(null);
    const [drawerOpen, setDrawerOpen] = useState(false);

    function bukaDetail(k) {
        setDetailKlaim(k);
        setDrawerOpen(true);
    }

    function tutupDetail() {
        setDrawerOpen(false);
        setDetailKlaim(null);
    }

    return (
        <AppLayout>
            <Head title={bendahara ? 'Verifikasi Santunan' : 'Persetujuan Santunan'} />

            <PageHeader
                title={bendahara ? 'Verifikasi Santunan Dana Sosial' : 'Persetujuan Santunan Dana Sosial'}
                subtitle={`${menunggu.length} pengajuan menunggu ${bendahara ? 'verifikasi' : 'keputusan'} Bapak/Ibu`}
            />

            <Card padding="none">
                <div className="flex items-end gap-1 px-3 pt-2 border-b border-slate-200">
                    <button onClick={() => setTab('baru')} className={tab === 'baru' ? tabAktif : tabNonAktif}>
                        Menunggu {bendahara ? 'Verifikasi' : 'Keputusan'}
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
                    {tab === 'baru' ? (
                        <table className="w-full table-sticky-first">
                            <thead>
                                <tr className="text-left sticky top-0 bg-white z-10">
                                    <th className="px-5 py-3 text-sm font-semibold text-slate-500">Pemohon</th>
                                    <th className="px-5 py-3 text-sm font-semibold text-slate-500">Jenis Santunan</th>
                                    <th className="hidden md:table-cell px-5 py-3 text-sm font-semibold text-slate-500">Kejadian</th>
                                    <th className="px-5 py-3 text-sm font-semibold text-slate-500">Tanggal</th>
                                    <th className="px-5 py-3 text-sm font-semibold text-slate-500">Status</th>
                                    <th className="w-10" aria-label="Buka detail" />
                                </tr>
                            </thead>
                            <tbody>
                                {menunggu.length === 0 ? (
                                    <tr>
                                        <td colSpan={6} className="px-5 py-8 text-center text-base text-slate-400">
                                            Tidak ada pengajuan yang menunggu {bendahara ? 'verifikasi' : 'keputusan'}.
                                        </td>
                                    </tr>
                                ) : (
                                    menunggu.map((k) => (
                                        <tr
                                            key={k.id}
                                            onClick={() => bukaDetail(k)}
                                            onKeyDown={(e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); bukaDetail(k); } }}
                                            tabIndex={0}
                                            title="Klik untuk mereview"
                                            className="border-t border-slate-50 hover:bg-slate-50 transition-colors cursor-pointer focus-visible:outline-none focus-visible:bg-slate-100"
                                        >
                                            <td className="px-5 py-3">
                                                <div className="flex items-center gap-3">
                                                    <FotoAnggota nama={k.anggota.nama} fotoUrl={k.anggota.foto_url} ukuran="sm" />
                                                    <div className="min-w-0">
                                                        <p className="text-sm font-semibold text-slate-800 truncate">{k.anggota.nama}</p>
                                                        <p className="text-xs text-slate-400">{k.anggota.no_karyawan}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-5 py-3 text-sm text-slate-700">{k.jenis_label}</td>
                                            <td className="hidden md:table-cell px-5 py-3 text-sm text-slate-600">{k.tanggal_kejadian}</td>
                                            <td className="px-5 py-3 text-sm text-slate-600">{k.tanggal_pengajuan}</td>
                                            <td className="px-5 py-3"><StatusBadge status={k.status} /></td>
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
                                    <th className="px-5 py-3 text-sm font-semibold text-slate-500">Pemohon</th>
                                    <th className="px-5 py-3 text-sm font-semibold text-slate-500">Jenis → Nominal</th>
                                    <th className="hidden md:table-cell px-5 py-3 text-sm font-semibold text-slate-500">Tanggal</th>
                                    <th className="px-5 py-3 text-sm font-semibold text-slate-500">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                {riwayat.length === 0 ? (
                                    <tr>
                                        <td colSpan={4} className="px-5 py-8 text-center text-base text-slate-400">
                                            Belum ada riwayat keputusan.
                                        </td>
                                    </tr>
                                ) : (
                                    riwayat.map((k) => (
                                        <tr key={k.id} onClick={() => bukaDetail(k)} className="border-t border-slate-50 hover:bg-slate-50 transition-colors cursor-pointer">
                                            <td className="px-5 py-3">
                                                <div className="flex items-center gap-3">
                                                    <FotoAnggota nama={k.anggota.nama} fotoUrl={k.anggota.foto_url} ukuran="sm" />
                                                    <div className="min-w-0">
                                                        <p className="text-sm font-semibold text-slate-800 truncate">{k.anggota.nama}</p>
                                                        <p className="text-xs text-slate-400">{k.anggota.no_karyawan}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-5 py-3 text-sm text-slate-700">
                                                {k.jenis_label} → {k.nominal_final ? formatRupiah(k.nominal_final) : (k.nominal_bendahara ? formatRupiah(k.nominal_bendahara) : '-')}
                                            </td>
                                            <td className="hidden md:table-cell px-5 py-3 text-sm text-slate-600">{k.tanggal_pengajuan}</td>
                                            <td className="px-5 py-3"><StatusBadge status={k.status} /></td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    )}
                </div>
            </Card>

            <Drawer show={drawerOpen} title={`Detail Santunan - ${detailKlaim?.anggota?.nama ?? 'Anggota'}`} onClose={tutupDetail} maxWidth="3xl">
                {detailKlaim && (
                    <KeputusanDrawer
                        key={detailKlaim.id}
                        klaim={detailKlaim}
                        tahap={tahap}
                        onClose={tutupDetail}
                    />
                )}
            </Drawer>
        </AppLayout>
    );
}

export function EmptyIcon() {
    return <HeartHandshake size={15} />;
}
