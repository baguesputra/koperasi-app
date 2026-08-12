import AppLayout from '@/Layouts/AppLayout';
import { Head, router } from '@inertiajs/react';
import { Search, Plus, Upload, Pencil, Users, UserCheck, UserX } from 'lucide-react';
import { useState } from 'react';
import ButtonLink from '@/Components/ui/ButtonLink';
import Card from '@/Components/ui/Card';
import StatWidget from '@/Components/ui/StatWidget';
import StatusBadge from '@/Components/ui/StatusBadge';
import PageHeader from '@/Components/ui/PageHeader';
import TextField from '@/Components/ui/TextField';
import Select from '@/Components/ui/Select';
import Drawer from '@/Components/ui/Drawer';
import EditDrawer from './Partials/EditDrawer';

const jabatanLabel = { staff: 'Staff', hod: 'HOD' };

function formatLamaAnggota(tahun) {
    if (tahun < 1) {
        const bulan = Math.max(1, Math.round(tahun * 12));
        return `${bulan} bulan`;
    }
    return `${tahun} tahun`;
}

export default function Index({ anggota, statistik, filters, daftarCabang }) {
    const [cari, setCari] = useState(filters.cari ?? '');
    const [editAnggota, setEditAnggota] = useState(null);
    const [drawerOpen, setDrawerOpen] = useState(false);

    function bukaEdit(a) {
        setEditAnggota(a);
        setDrawerOpen(true);
    }

    function tutupEdit() {
        setDrawerOpen(false);
    }

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

    return (
        <AppLayout>
            <Head title="Anggota" />

            <PageHeader title="Anggota" subtitle={`${anggota.total} anggota terdaftar`}>
                <div className="flex items-center gap-3">
                    <ButtonLink href={route('anggota.import.index')} variant="outline">
                        <Upload size={18} />
                        Import Excel
                    </ButtonLink>
                    <ButtonLink href={route('anggota.create')}>
                        <Plus size={18} />
                        Tambah Anggota
                    </ButtonLink>
                </div>
            </PageHeader>

            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                <StatWidget compact label="Total Anggota" value={statistik.total} icon={Users} tone="navy" />
                <StatWidget compact label="Anggota Aktif" value={statistik.aktif} icon={UserCheck} tone="green" />
                <StatWidget compact label="Anggota Nonaktif" value={statistik.nonaktif} icon={UserX} tone="amber" />
            </div>

            <Card className="mb-5">
                <div className="flex flex-col sm:flex-row gap-3">
                    <form onSubmit={handleCariSubmit} className="flex-1 relative">
                        <Search className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" size={20} />
                        <TextField
                            type="text"
                            size="sm"
                            value={cari}
                            onChange={(e) => setCari(e.target.value)}
                            placeholder="Cari nama atau nomor anggota..."
                            className="pl-11"
                        />
                    </form>

                    <Select
                        size="sm"
                        value={filters.cabang ?? ''}
                        onChange={(e) => terapkanFilter({ cabang: e.target.value })}
                        className="sm:w-48"
                    >
                        <option value="">Semua Cabang</option>
                        {daftarCabang.map((c) => (
                            <option key={c} value={c}>{c}</option>
                        ))}
                    </Select>

                    <Select
                        size="sm"
                        value={filters.status ?? ''}
                        onChange={(e) => terapkanFilter({ status: e.target.value })}
                        className="sm:w-40"
                    >
                        <option value="">Semua Status</option>
                        <option value="aktif">Aktif</option>
                        <option value="nonaktif">Nonaktif</option>
                    </Select>
                </div>
            </Card>

            <Card padding="none">
                <div className="overflow-x-auto">
                    <table className="w-full">
                        <thead>
                            <tr className="border-b border-slate-100 text-left">
                                <th className="px-5 py-3.5 text-sm font-semibold text-slate-500">Anggota</th>
                                <th className="px-5 py-3.5 text-sm font-semibold text-slate-500">Cabang</th>
                                <th className="px-5 py-3.5 text-sm font-semibold text-slate-500">Jabatan</th>
                                <th className="px-5 py-3.5 text-sm font-semibold text-slate-500">Lama Anggota</th>
                                <th className="px-5 py-3.5 text-sm font-semibold text-slate-500">Status</th>
                                <th className="px-5 py-3.5 text-right text-sm font-semibold text-slate-500">Aksi</th>
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
                                        <td className="px-5 py-3.5">
                                            <div className="flex items-center gap-3">
                                                <div className="w-10 h-10 rounded-full bg-brand-green text-white flex items-center justify-center text-sm font-bold shrink-0">
                                                    {a.nama.charAt(0).toUpperCase()}
                                                </div>
                                                <div className="min-w-0">
                                                    <button onClick={() => bukaEdit(a)} className="block text-left text-base font-semibold text-slate-800 hover:text-brand-green truncate">
                                                        {a.nama}
                                                    </button>
                                                    <p className="text-sm text-slate-400">{a.no_anggota}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-5 py-3.5 text-base text-slate-600">{a.cabang}</td>
                                        <td className="px-5 py-3.5 text-base text-slate-600">{jabatanLabel[a.jabatan]}</td>
                                        <td className="px-5 py-3.5 text-base text-slate-600">{formatLamaAnggota(a.lama_keanggotaan_tahun)}</td>
                                        <td className="px-5 py-3.5">
                                            <StatusBadge status={a.status} />
                                        </td>
                                        <td className="px-5 py-3.5 text-right">
                                            <button
                                                onClick={() => bukaEdit(a)}
                                                className="inline-flex items-center justify-center w-9 h-9 rounded-lg text-slate-400 hover:text-brand-green hover:bg-slate-100 transition-colors"
                                                title="Edit anggota"
                                            >
                                                <Pencil size={16} />
                                            </button>
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

            <Drawer show={drawerOpen} title={`Edit ${editAnggota?.nama ?? 'Anggota'}`} onClose={tutupEdit}>
                {editAnggota && (
                    <EditDrawer
                        key={editAnggota.id}
                        anggota={editAnggota}
                        daftarCabang={daftarCabang}
                        onClose={tutupEdit}
                    />
                )}
            </Drawer>
        </AppLayout>
    );
}