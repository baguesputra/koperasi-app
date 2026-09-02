import AppLayout from '@/Layouts/AppLayout';
import { Head, router, usePage } from '@inertiajs/react';
import { Search, Plus, Upload, Users, UserCheck, UserX, UserMinus, RotateCcw, FileText, MoreVertical } from 'lucide-react';
import { useState } from 'react';
import ButtonLink from '@/Components/ui/ButtonLink';
import Card from '@/Components/ui/Card';
import StatWidget from '@/Components/ui/StatWidget';
import StatusBadge from '@/Components/ui/StatusBadge';
import PageHeader from '@/Components/ui/PageHeader';
import TextField from '@/Components/ui/TextField';
import Select from '@/Components/ui/Select';
import Drawer from '@/Components/ui/Drawer';
import CreateDrawer from './Partials/CreateDrawer';
import EditDrawer from './Partials/EditDrawer';
import ResignDrawer from './Partials/ResignDrawer';
import AktifkanKembaliDialog from './Partials/AktifkanKembaliDialog';
import Pagination from '@/Components/ui/Pagination';

const jabatanLabel = { staff: 'Staff', hod: 'HOD' };

function formatLamaAnggota(tahun) {
    if (tahun < 1) {
        const bulan = Math.max(1, Math.round(tahun * 12));
        return `${bulan} bulan`;
    }
    return `${tahun} tahun`;
}

export default function Index({ anggota, statistik, filters, noAnggotaBerikutnya, daftarCabang }) {
    const { props } = usePage();
    const permissions = props.auth?.user?.permissions ?? [];

    const [cari, setCari] = useState(filters.cari ?? '');
    const [createOpen, setCreateOpen] = useState(false);
    const [editAnggota, setEditAnggota] = useState(null);
    const [drawerOpen, setDrawerOpen] = useState(false);
    const [resignAnggota, setResignAnggota] = useState(null);
    const [resignDrawerOpen, setResignDrawerOpen] = useState(false);
    const [reaktivasiAnggota, setReaktivasiAnggota] = useState(null);

    const canResign = permissions.includes('anggota.resign');

    function bukaCreate() {
        setCreateOpen(true);
    }

    function tutupCreate() {
        setCreateOpen(false);
    }

    function bukaEdit(a) {
        setEditAnggota(a);
        setDrawerOpen(true);
    }

    function tutupEdit() {
        setDrawerOpen(false);
    }

    function bukaResign(a) {
        setResignAnggota(a);
        setResignDrawerOpen(true);
    }

    function tutupResign() {
        setResignDrawerOpen(false);
        setResignAnggota(null);
    }

    function bukaReaktivasi(a) {
        setReaktivasiAnggota(a);
    }

    function tutupReaktivasi() {
        setReaktivasiAnggota(null);
    }

    function bukaSlip(a) {
        window.open(route('anggota.slip-resign', a.id), '_blank');
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
                    <button
                        onClick={bukaCreate}
                        className="inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold rounded-xl bg-brand-green text-white hover:bg-brand-green/90 transition-colors"
                    >
                        <Plus size={18} />
                        Tambah Anggota
                    </button>
                </div>
            </PageHeader>

            <div className="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                <StatWidget compact label="Total Anggota" value={statistik.total} icon={Users} tone="navy" />
                <StatWidget compact label="Anggota Aktif" value={statistik.aktif} icon={UserCheck} tone="green" />
                <StatWidget compact label="Anggota Nonaktif" value={statistik.nonaktif} icon={UserX} tone="amber" />
                <StatWidget compact label="Anggota Resign" value={statistik.resign} icon={UserMinus} tone="rose" />
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
                        <option value="resign">Resign</option>
                    </Select>
                </div>
            </Card>

            <Card padding="none">
                <div className="overflow-x-auto">
                    <table className="w-full table-sticky-first">
                        <thead>
                            <tr className="border-b border-slate-100 text-left sticky top-0 bg-white z-10">
                                <th className="px-5 py-3.5 text-sm font-semibold text-slate-500">Anggota</th>
                                <th className="px-5 py-3.5 text-sm font-semibold text-slate-500">Cabang</th>
                                <th className="hidden md:table-cell px-5 py-3.5 text-sm font-semibold text-slate-500">Jabatan</th>
                                <th className="hidden md:table-cell px-5 py-3.5 text-sm font-semibold text-slate-500">Lama Anggota</th>
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
                                        <tr key={a.id} onClick={() => bukaEdit(a)} className="border-b border-slate-50 hover:bg-slate-50 transition-colors cursor-pointer">
                                            <td className="px-5 py-3.5">
                                                <div className="flex items-center gap-3">
                                                    <div className="w-10 h-10 rounded-full bg-brand-green text-white flex items-center justify-center text-sm font-bold shrink-0">
                                                        {a.nama.charAt(0).toUpperCase()}
                                                    </div>
                                                    <div className="min-w-0">
                                                        <p className="text-base font-semibold text-slate-800 truncate">{a.nama}</p>
                                                        <p className="text-sm text-slate-400">{a.no_anggota}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-5 py-3.5 text-base text-slate-600">{a.cabang}</td>
                                            <td className="hidden md:table-cell px-5 py-3.5 text-base text-slate-600">{jabatanLabel[a.jabatan]}</td>
                                            <td className="hidden md:table-cell px-5 py-3.5 text-base text-slate-600">{formatLamaAnggota(a.lama_keanggotaan_tahun)}</td>
                                            <td className="px-5 py-3.5">
                                                <StatusBadge status={a.status} />
                                            </td>
                                            <td className="px-5 py-3.5 text-right">
                                                <div className="inline-flex items-center gap-1 justify-end">
                                                    {canResign && a.status === 'aktif' && (
                                                        <button
                                                            onClick={(e) => { e.stopPropagation(); bukaResign(a); }}
                                                            className="inline-flex items-center justify-center w-10 h-10 min-w-[44px] min-h-[44px] rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors"
                                                            title="Resign anggota"
                                                        >
                                                            <UserMinus size={18} />
                                                        </button>
                                                    )}
                                                    {canResign && a.status === 'resign' && (
                                                        <>
                                                            <button
                                                                onClick={(e) => { e.stopPropagation(); bukaReaktivasi(a); }}
                                                                className="inline-flex items-center justify-center w-10 h-10 min-w-[44px] min-h-[44px] rounded-lg text-slate-400 hover:text-brand-green hover:bg-slate-100 transition-colors"
                                                                title="Aktifkan kembali"
                                                            >
                                                                <RotateCcw size={18} />
                                                            </button>
                                                            <button
                                                                onClick={(e) => { e.stopPropagation(); bukaSlip(a); }}
                                                                className="inline-flex items-center justify-center w-10 h-10 min-w-[44px] min-h-[44px] rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                                                                title="Lihat slip resign"
                                                            >
                                                                <FileText size={18} />
                                                            </button>
                                                        </>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                            )}
                        </tbody>
                    </table>
                </div>
            </Card>

            <Pagination links={anggota.links} />

            <Drawer show={createOpen} title="Tambah Anggota" onClose={tutupCreate}>
                <CreateDrawer
                    key={noAnggotaBerikutnya}
                    noAnggotaBerikutnya={noAnggotaBerikutnya}
                    daftarCabang={daftarCabang}
                    onClose={tutupCreate}
                />
            </Drawer>

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

            <Drawer show={resignDrawerOpen} title={`Resign ${resignAnggota?.nama ?? ''}`} onClose={tutupResign} maxWidth="2xl">
                {resignAnggota && (
                    <ResignDrawer
                        key={resignAnggota.id}
                        anggota={resignAnggota}
                        onClose={tutupResign}
                    />
                )}
            </Drawer>

            {reaktivasiAnggota && (
                <AktifkanKembaliDialog
                    key={reaktivasiAnggota.id}
                    anggota={reaktivasiAnggota}
                    onClose={tutupReaktivasi}
                />
            )}
        </AppLayout>
    );
}