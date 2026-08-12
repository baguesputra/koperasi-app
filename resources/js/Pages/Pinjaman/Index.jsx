import AppLayout from '@/Layouts/AppLayout';
import { Head, Link, router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useState } from 'react';
import Card from '@/Components/ui/Card';
import StatusBadge from '@/Components/ui/StatusBadge';
import { formatRupiah } from '@/Utils/formatCurrency';

const statusOptions = [
    { value: '', label: 'Semua Status' },
    { value: 'diajukan', label: 'Diajukan' },
    { value: 'approved_bendahara', label: 'Disetujui Bendahara' },
    { value: 'aktif', label: 'Aktif' },
    { value: 'lunas', label: 'Lunas' },
    { value: 'ditolak', label: 'Ditolak' },
];

const statusMap = {
    diajukan: 'pending',
    approved_bendahara: 'pending',
    aktif: 'aktif',
    lunas: 'lunas',
    ditolak: 'ditolak',
};

export default function Index({ pinjaman, filters }) {
    const [cari, setCari] = useState(filters.cari ?? '');

    function terapkanFilter(overrides = {}) {
        router.get(route('pinjaman.index'), { cari, status: filters.status, ...overrides }, { preserveState: true, replace: true });
    }

    return (
        <AppLayout>
            <Head title="Pinjaman" />

            <div className="mb-6">
                <h1 className="text-2xl font-bold text-slate-800">Pinjaman</h1>
                <p className="text-base text-slate-400 mt-1">{pinjaman.total} pengajuan pinjaman tercatat</p>
            </div>

            <Card className="mb-5">
                <div className="flex flex-col sm:flex-row gap-3">
                    <form onSubmit={(e) => { e.preventDefault(); terapkanFilter({ cari }); }} className="flex-1 relative">
                        <Search className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" size={20} />
                        <input
                            type="text"
                            value={cari}
                            onChange={(e) => setCari(e.target.value)}
                            placeholder="Cari nama anggota..."
                            className="w-full pl-11 pr-4 py-2.5 text-base rounded-xl border border-slate-300 bg-white focus:border-brand-green outline-none"
                        />
                    </form>

                    <select
                        value={filters.status ?? ''}
                        onChange={(e) => terapkanFilter({ status: e.target.value })}
                        className="px-4 py-2.5 text-base rounded-xl border border-slate-300 bg-white focus:border-brand-green outline-none"
                    >
                        {statusOptions.map((s) => (
                            <option key={s.value} value={s.value}>{s.label}</option>
                        ))}
                    </select>
                </div>
            </Card>

            <Card padding="none">
                <div className="overflow-x-auto">
                    <table className="w-full">
                        <thead>
                            <tr className="border-b border-slate-100 text-left">
                                <th className="px-5 py-3.5 text-sm font-semibold text-slate-500">Anggota</th>
                                <th className="px-5 py-3.5 text-sm font-semibold text-slate-500">Nominal</th>
                                <th className="px-5 py-3.5 text-sm font-semibold text-slate-500">Tenor</th>
                                <th className="px-5 py-3.5 text-sm font-semibold text-slate-500">Tanggal</th>
                                <th className="px-5 py-3.5 text-sm font-semibold text-slate-500">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            {pinjaman.data.length === 0 ? (
                                <tr><td colSpan={5} className="px-5 py-10 text-center text-base text-slate-400">Tidak ada data ditemukan.</td></tr>
                            ) : (
                                pinjaman.data.map((p) => (
                                    <tr key={p.id} className="border-b border-slate-50 hover:bg-slate-50 transition-colors">
                                        <td className="px-5 py-3.5">
                                            <p className="text-base font-semibold text-slate-800">{p.nama}</p>
                                            <p className="text-sm text-slate-400">{p.no_anggota}</p>
                                        </td>
                                        <td className="px-5 py-3.5 text-base text-slate-700">{formatRupiah(p.nominal)}</td>
                                        <td className="px-5 py-3.5 text-base text-slate-600">{p.tenor_bulan} bulan</td>
                                        <td className="px-5 py-3.5 text-base text-slate-600">{p.tanggal_pengajuan}</td>
                                        <td className="px-5 py-3.5"><StatusBadge status={statusMap[p.status] ?? 'pending'} /></td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            </Card>

            {pinjaman.links.length > 3 && (
                <div className="flex items-center justify-center gap-1.5 mt-5">
                    {pinjaman.links.map((link, i) => (
                        <button
                            key={i}
                            disabled={!link.url}
                            onClick={() => link.url && router.get(link.url, {}, { preserveState: true })}
                            className={`px-3.5 py-2 text-sm font-semibold rounded-lg transition-colors ${
                                link.active ? 'bg-brand-green text-white' : link.url ? 'text-slate-600 hover:bg-slate-100' : 'text-slate-300 cursor-not-allowed'
                            }`}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ))}
                </div>
            )}
        </AppLayout>
    );
}