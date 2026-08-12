import AppLayout from '@/Layouts/AppLayout';
import { Head, Link, router } from '@inertiajs/react';
import { Search, HeartHandshake, PiggyBank } from 'lucide-react';
import { useState } from 'react';
import Card from '@/Components/ui/Card';
import StatWidget from '@/Components/ui/StatWidget';
import { formatRupiah } from '@/Utils/formatCurrency';

export default function Index({ anggota, filters, totalDanaSosialTerkumpul, totalSimpananSeluruhAnggota }) {
    const [cari, setCari] = useState(filters.cari ?? '');

    function cariSubmit(e) {
        e.preventDefault();
        router.get(route('simpanan.index'), { cari }, { preserveState: true, replace: true });
    }

    return (
        <AppLayout>
            <Head title="Simpanan" />

            <div className="mb-6">
                <h1 className="text-2xl font-bold text-slate-800">Simpanan Anggota</h1>
                <p className="text-base text-slate-400 mt-1">Rekap simpanan seluruh anggota</p>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                <StatWidget
                    label="Total Simpanan Seluruh Anggota"
                    value={formatRupiah(totalSimpananSeluruhAnggota)}
                    icon={PiggyBank}
                    tone="navy"
                />
                <StatWidget
                    label="Total Dana Sosial Terkumpul"
                    value={formatRupiah(totalDanaSosialTerkumpul)}
                    icon={HeartHandshake}
                    tone="green"
                />
            </div>

            <Card className="mb-5">
                <form onSubmit={cariSubmit} className="relative">
                    <Search className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" size={20} />
                    <input
                        type="text"
                        value={cari}
                        onChange={(e) => setCari(e.target.value)}
                        placeholder="Cari nama anggota..."
                        className="w-full pl-11 pr-4 py-2.5 text-base rounded-xl border border-slate-300 bg-white focus:border-brand-green outline-none"
                    />
                </form>
            </Card>

            <Card padding="none">
                <div className="overflow-x-auto">
                    <table className="w-full">
                        <thead>
                            <tr className="border-b border-slate-100 text-left">
                                <th className="px-5 py-3.5 text-sm font-semibold text-slate-500">Nama</th>
                                <th className="px-5 py-3.5 text-sm font-semibold text-slate-500">No. Anggota</th>
                                <th className="px-5 py-3.5 text-sm font-semibold text-slate-500">Cabang</th>
                                <th className="px-5 py-3.5 text-sm font-semibold text-slate-500">Total Simpanan</th>
                            </tr>
                        </thead>
                        <tbody>
                            {anggota.data.map((a) => (
                                <tr key={a.id} className="border-b border-slate-50 hover:bg-slate-50 transition-colors">
                                    <td className="px-5 py-3.5">
                                        <Link href={route('simpanan.show', a.id)} className="text-base font-semibold text-slate-800 hover:text-brand-green">
                                            {a.nama}
                                        </Link>
                                    </td>
                                    <td className="px-5 py-3.5 text-base text-slate-600">{a.no_anggota}</td>
                                    <td className="px-5 py-3.5 text-base text-slate-600">{a.cabang}</td>
                                    <td className="px-5 py-3.5 text-base font-bold text-slate-800">{formatRupiah(a.total_simpanan)}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </Card>
        </AppLayout>
    );
}