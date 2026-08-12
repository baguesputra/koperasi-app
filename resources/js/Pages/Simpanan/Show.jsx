import AppLayout from '@/Layouts/AppLayout';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import Card from '@/Components/ui/Card';
import { formatRupiah } from '@/Utils/formatCurrency';

const jenisLabel = { pokok: 'Simpanan Pokok', wajib: 'Simpanan Wajib', dana_sosial: 'Dana Sosial' };

export default function Show({ anggota, riwayat, totalSimpanan }) {
    return (
        <AppLayout>
            <Head title={`Simpanan - ${anggota.nama}`} />

            <Link href={route('simpanan.index')} className="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 hover:text-brand-navy mb-5">
                <ArrowLeft size={16} />
                Kembali
            </Link>

            <div className="mb-6">
                <h1 className="text-2xl font-bold text-slate-800">{anggota.nama}</h1>
                <p className="text-base text-slate-400 mt-1">{anggota.no_anggota}</p>
            </div>

            <Card className="mb-5">
                <p className="text-sm text-slate-400">Total Simpanan</p>
                <p className="text-3xl font-bold text-slate-800">{formatRupiah(totalSimpanan)}</p>
            </Card>

            <Card padding="none">
                <div className="p-5 border-b border-slate-100">
                    <h2 className="text-lg font-bold text-slate-800">Riwayat</h2>
                </div>
                <div className="divide-y divide-slate-50">
                    {riwayat.map((r, i) => (
                        <div key={i} className="flex items-center justify-between px-5 py-3.5">
                            <div>
                                <p className="text-base font-semibold text-slate-700">{jenisLabel[r.jenis]}</p>
                                <p className="text-sm text-slate-400">Periode {r.bulan_periode} &bull; {r.tanggal_input}</p>
                            </div>
                            <p className="text-base font-bold text-slate-800">{formatRupiah(r.jumlah)}</p>
                        </div>
                    ))}
                </div>
            </Card>
        </AppLayout>
    );
}