import AppLayout from '@/Layouts/AppLayout';
import { Head, Link } from '@inertiajs/react';
import Card from '@/Components/ui/Card';
import StatusBadge from '@/Components/ui/StatusBadge';

function formatRupiah(angka) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka);
}

export default function Index({ menunggu, riwayat }) {
    return (
        <AppLayout>
            <Head title="Pengajuan Limit" />

            <div className="mb-6">
                <h1 className="text-2xl font-bold text-slate-800">Pengajuan Tambah Limit</h1>
                <p className="text-base text-slate-400 mt-1">{menunggu.length} pengajuan menunggu keputusan Anda</p>
            </div>

            <div className="mb-6">
                <p className="text-base font-bold text-slate-700 mb-3">Menunggu Keputusan</p>
                {menunggu.length === 0 ? (
                    <Card><p className="text-base text-slate-400 text-center py-4">Tidak ada pengajuan yang menunggu.</p></Card>
                ) : (
                    <div className="space-y-3">
                        {menunggu.map((p) => (
                            <Link key={p.id} href={route('ketua.pengajuan-limit.show', p.id)}>
                                <Card className="hover:border-brand-green transition-colors cursor-pointer">
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <p className="text-base font-bold text-slate-800">{p.nama}</p>
                                            <p className="text-sm text-slate-400 mt-0.5">Minta: {formatRupiah(p.limit_diminta)} &bull; {p.tanggal_pengajuan}</p>
                                        </div>
                                        <StatusBadge status="pending" />
                                    </div>
                                </Card>
                            </Link>
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
                                <Link key={p.id} href={route('ketua.pengajuan-limit.show', p.id)} className="flex items-center justify-between px-5 py-3.5 hover:bg-slate-50 transition-colors">
                                    <div>
                                        <p className="text-base font-semibold text-slate-700">{p.nama}</p>
                                        <p className="text-sm text-slate-400">{formatRupiah(p.limit_diminta)}</p>
                                    </div>
                                    <StatusBadge status={p.status === 'ditolak' ? 'ditolak' : 'lunas'} />
                                </Link>
                            ))}
                        </div>
                    )}
                </Card>
            </div>
        </AppLayout>
    );
}