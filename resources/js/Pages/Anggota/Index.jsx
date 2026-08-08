import AppLayout from '@/Layouts/AppLayout';
import { Head, Link, router } from '@inertiajs/react';
import { Search, Plus, Upload } from 'lucide-react';
import { useState } from 'react';
import Button from '@/Components/ui/Button';
import Card from '@/Components/ui/Card';

export default function Index({ anggota, filters, daftarCabang }) {
    const [cari, setCari] = useState(filters.cari ?? '');

    function terapkanFilter(overrides = {}) {
        router.get(
            route('anggota.index'),
            { cari, cabang: filters.cabang, status: filters.status, ...overrides },
            { preserveState: true, replace: true }
        );
    }

    function handleCariSubmit(e) {
        e.preventDefault();
        terapkanFilter({ cari });
    }

    const statusStyle = {
        aktif: 'bg-brand-green-light text-brand-green-dark',
        nonaktif: 'bg-slate-100 text-slate-500',
    };

    const jabatanLabel = { staff: 'Staff', hod: 'HOD' };

    return (
        <AppLayout>
            <Head title="Anggota" />

            <div className="flex items-center justify-between mb-6">
                <div>
                    <h1 className="text-2xl font-bold text-slate-800">Anggota</h1>
                    <p className="text-base text-slate-400 mt-1">
                        {anggota.total} anggota terdaftar
                    </p>
                </div>
                <div className="flex items-center gap-3">
                    <Button variant="outline" size="md">
                        <Upload size={18} />
                        Import Excel
                    </Button>
                    <Button variant="primary" size="md">
                        <Plus size={18} />
                        Tambah Anggota
                    </Button>
                </div>
            </div>

            <Card padding="normal" className="mb-5">
                <div className="flex flex-col sm:flex-row gap-3">
                    <form onSubmit={handleCariSubmit} className="flex-1 relative">
                        <Search className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" size={20} />
                        <input
                            type="text"
                            value={cari}
                            onChange={(e) => setCari(e.target.value)}
                            placeholder="Cari nama atau nomor anggota..."
                            className="w-full pl-11 pr-4 py-2.5 text-base rounded-xl border border-slate-300 focus:border-brand-green focus:ring-2 focus:ring-brand-green/20 outline-none transition-colors"
                        />
                    </form>

                    <select
                        value={filters.cabang ?? ''}
                        onChange={(e) => terapkanFilter({ cabang: e.target.value })}
                        className="px-4 py-2.5 text-base rounded-xl border border-slate-300 focus:border-brand-green outline-none"
                    >
                        <option value="">Semua Cabang</option>
                        {daftarCabang.map((c) => (
                            <option key={c} value={c}>{c}</option>
                        ))}
                    </select>

                    <select
                        value={filters.status ?? ''}
                        onChange={(e) => terapkanFilter({ status: e.target.value })}
                        className="px-4 py-2.5 text-base rounded-xl border border-slate-300 focus:border-brand-green outline-none"
                    >
                        <option value="">Semua Status</option>
                        <option value="aktif">Aktif</option>
                        <option value="nonaktif">Nonaktif</option>
                    </select>
                </div>
            </Card>

            <Card padding="none">
                <div className="overflow-x-auto">
                    <table className="w-full">
                        <thead>
                            <tr className="border-b border-slate-100 text-left">
                                <th className="px-5 py-3.5 text-sm font-semibold text-slate-500">No. Anggota</th>
                                <th className="px-5 py-3.5 text-sm font-semibold text-slate-500">Nama</th>
                                <th className="px-5 py-3.5 text-sm font-semibold text-slate-500">Cabang</th>
                                <th className="px-5 py-3.5 text-sm font-semibold text-slate-500">Jabatan</th>
                                <th className="px-5 py-3.5 text-sm font-semibold text-slate-500">Lama Anggota</th>
                                <th className="px-5 py-3.5 text-sm font-semibold text-slate-500">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            {anggota.data.length === 0 ? (
                                <tr>
                                    <td colSpan={6} className="px-5 py-10 text-center text-base text-slate-400">
                                        Tidak ada data anggota ditemukan.
                                    </td>
                                </tr>
                            ) : (
                                anggota.data.map((a) => (
                                    <tr key={a.id} className="border-b border-slate-50 hover:bg-slate-50 transition-colors">
                                        <td className="px-5 py-3.5 text-base text-slate-600">{a.no_anggota}</td>
                                        <td className="px-5 py-3.5 text-base font-semibold text-slate-800">
                                            <Link href="#" className="hover:text-brand-green">
                                                {a.nama}
                                            </Link>
                                        </td>
                                        <td className="px-5 py-3.5 text-base text-slate-600">{a.cabang}</td>
                                        <td className="px-5 py-3.5 text-base text-slate-600">{jabatanLabel[a.jabatan]}</td>
                                        <td className="px-5 py-3.5 text-base text-slate-600">{a.lama_keanggotaan_tahun} tahun</td>
                                        <td className="px-5 py-3.5">
                                            <span className={`inline-flex px-3 py-1 rounded-full text-sm font-semibold ${statusStyle[a.status]}`}>
                                                {a.status === 'aktif' ? 'Aktif' : 'Nonaktif'}
                                            </span>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            </Card>

            {anggota.links.length > 3 && (
                <div className="flex items-center justify-center gap-1.5 mt-5">
                    {anggota.links.map((link, i) => (
                        <button
                            key={i}
                            disabled={!link.url}
                            onClick={() => link.url && router.get(link.url, {}, { preserveState: true })}
                            className={`px-3.5 py-2 text-sm font-semibold rounded-lg transition-colors ${
                                link.active
                                    ? 'bg-brand-green text-white'
                                    : link.url
                                    ? 'text-slate-600 hover:bg-slate-100'
                                    : 'text-slate-300 cursor-not-allowed'
                            }`}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ))}
                </div>
            )}
        </AppLayout>
    );
}