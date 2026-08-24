import AppLayout from '@/Layouts/AppLayout';
import { Head } from '@inertiajs/react';
import { useState } from 'react';
import Card from '@/Components/ui/Card';
import StatusBadge from '@/Components/ui/StatusBadge';
import PageHeader from '@/Components/ui/PageHeader';
import Drawer from '@/Components/ui/Drawer';
import KeputusanDrawer from './Partials/KeputusanDrawer';

export default function Index({ menunggu, riwayat }) {
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

            <div className="mb-6">
                <p className="text-base font-bold text-slate-700 mb-3">Menunggu Approval</p>
                {menunggu.length === 0 ? (
                    <Card><p className="text-base text-slate-400 text-center py-4">Tidak ada pengajuan yang menunggu.</p></Card>
                ) : (
                    <div className="space-y-3">
                        {menunggu.map((p) => (
                            <div key={p.id} onClick={() => bukaDetail(p)} role="button" tabIndex={0} onKeyDown={(e) => e.key === 'Enter' && bukaDetail(p)}>
                                <Card className="hover:border-brand-green transition-colors cursor-pointer">
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <p className="text-base font-bold text-slate-800">{p.anggota.nama}</p>
                                            <p className="text-sm text-slate-400 mt-0.5">{p.tipe_label ?? p.tipe} &bull; {p.tanggal_pengajuan}</p>
                                        </div>
                                        <StatusBadge status="pending" />
                                    </div>
                                </Card>
                            </div>
                        ))}
                    </div>
                )}
            </div>

            <div>
                <p className="text-base font-bold text-slate-700 mb-3">Riwayat Keputusan</p>
                <Card padding="none">
                    {riwayat.length === 0 ? (
                        <p className="text-base text-slate-400 text-center py-8">Belum ada riwayat.</p>
                    ) : (
                        <div className="divide-y divide-slate-50">
                            {riwayat.map((p) => (
                                <button key={p.id} onClick={() => bukaDetail(p)} className="w-full flex items-center justify-between px-5 py-3.5 hover:bg-slate-50 transition-colors text-left">
                                    <div>
                                        <p className="text-base font-semibold text-slate-700">{p.anggota.nama}</p>
                                        <p className="text-sm text-slate-400">{p.tipe_label ?? p.tipe}</p>
                                    </div>
                                    <StatusBadge status={p.status === 'ditolak' ? 'ditolak' : 'lunas'} />
                                </button>
                            ))}
                        </div>
                    )}
                </Card>
            </div>

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
