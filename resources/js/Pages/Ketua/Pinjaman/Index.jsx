import AppLayout from '@/Layouts/AppLayout';
import { Head, Link } from '@inertiajs/react';
import Card from '@/Components/ui/Card';
import StatusBadge from '@/Components/ui/StatusBadge';
import { formatRupiah } from '@/Utils/formatCurrency';

export default function Index({ menungguApproval, riwayat }) {
    return (
        <AppLayout>
            <Head title="Approval Pinjaman" />

            <div className="mb-6">
                <h1 className="text-2xl font-bold text-slate-800">Approval Pinjaman</h1>
                <p className="text-base text-slate-400 mt-1">
                    {menungguApproval.length} pengajuan menunggu approval final Anda
                </p>
            </div>

            <div className="mb-6">
                <p className="text-base font-bold text-slate-700 mb-3">Menunggu Approval Final</p>
                {menungguApproval.length === 0 ? (
                    <Card>
                        <p className="text-base text-slate-400 text-center py-4">
                            Tidak ada pengajuan yang menunggu approval.
                        </p>
                    </Card>
                ) : (
                    <div className="space-y-3">
                        {menungguApproval.map((p) => (
                            <Link key={p.id} href={route('ketua.pinjaman.show', p.id)}>
                                <Card className="hover:border-brand-green transition-colors cursor-pointer">
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <p className="text-base font-bold text-slate-800">{p.nama}</p>
                                            <p className="text-sm text-slate-400 mt-0.5">
                                                {formatRupiah(p.nominal)} &bull; {p.tenor_bulan} bulan &bull; {p.tanggal_pengajuan}
                                            </p>
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
                                <Link
                                    key={p.id}
                                    href={route('ketua.pinjaman.show', p.id)}
                                    className="flex items-center justify-between px-5 py-3.5 hover:bg-slate-50 transition-colors"
                                >
                                    <div>
                                        <p className="text-base font-semibold text-slate-700">{p.nama}</p>
                                        <p className="text-sm text-slate-400">{formatRupiah(p.nominal)}</p>
                                    </div>
                                    <StatusBadge status={p.status === 'ditolak' ? 'ditolak' : p.status === 'aktif' || p.status === 'lunas' ? p.status : 'pending'} />
                                </Link>
                            ))}
                        </div>
                    )}
                </Card>
            </div>
        </AppLayout>
    );
}