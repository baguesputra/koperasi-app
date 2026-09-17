import AppLayout from '@/Layouts/AppLayout';
import { Head, router, usePage } from '@inertiajs/react';
import { Search, Upload, Users, UserCheck, UserX, UserMinus, RotateCcw, FileText, ChevronRight, X, RefreshCw } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import useDebouncedValue from '@/Utils/useDebouncedValue';
import Button from '@/Components/ui/Button';
import ButtonLink from '@/Components/ui/ButtonLink';
import Card from '@/Components/ui/Card';
import FotoAnggota from '@/Components/ui/FotoAnggota';
import StatWidget from '@/Components/ui/StatWidget';
import StatusBadge from '@/Components/ui/StatusBadge';
import PageHeader from '@/Components/ui/PageHeader';
import TextField from '@/Components/ui/TextField';
import Select from '@/Components/ui/Select';
import Drawer from '@/Components/ui/Drawer';
import EditDrawer from './Partials/EditDrawer';
import ResignDrawer from './Partials/ResignDrawer';
import AktifkanKembaliDialog from './Partials/AktifkanKembaliDialog';
import Pagination from '@/Components/ui/Pagination';

function formatLamaAnggota(tahun) {
    if (tahun < 1) {
        const bulan = Math.max(1, Math.round(tahun * 12));
        return `${bulan} bln`;
    }
    return `${tahun} thn`;
}

function TombolAksi({ label, title, onClick, tone }) {
    const tones = {
        rose: 'hover:text-rose-600 hover:bg-rose-50 hover:border-rose-200 hover:shadow-sm',
        green: 'hover:text-brand-green-dark hover:bg-brand-green-light hover:border-brand-green/30 hover:shadow-sm',
        blue: 'hover:text-blue-600 hover:bg-blue-50 hover:border-blue-200 hover:shadow-sm',
    };
    return (
        <button
            onClick={onClick}
            aria-label={label}
            title={title}
            className={`inline-flex items-center justify-center w-9 h-9 rounded-xl border border-transparent text-slate-400 transition-all duration-200 ${tones[tone]}`}
        >
            {title === 'Resign' ? <UserMinus size={16} /> : null}
            {title === 'Aktifkan kembali' ? <RotateCcw size={16} /> : null}
            {title === 'Slip resign' ? <FileText size={16} /> : null}
        </button>
    );
}

export default function Index({ anggota, statistik, filters, daftarCabang }) {
    const { props } = usePage();
    const permissions = props.auth?.user?.permissions ?? [];
    const bisaKelola = permissions.includes('anggota.kelola');

    const [cari, setCari] = useState(filters.cari ?? '');
    const cariDebounced = useDebouncedValue(cari);
    const pertama = useRef(true);
    const [editAnggota, setEditAnggota] = useState(null);
    const [drawerOpen, setDrawerOpen] = useState(false);
    const [resignAnggota, setResignAnggota] = useState(null);
    const [resignDrawerOpen, setResignDrawerOpen] = useState(false);
    const [reaktivasiAnggota, setReaktivasiAnggota] = useState(null);

    const canResign = permissions.includes('anggota.resign');
    const filterAktif = Boolean((filters.cari ?? '') || (filters.cabang ?? '') || (filters.status ?? ''));

    function bukaEdit(a) {
        setEditAnggota(a);
        setDrawerOpen(true);
    }

    function bukaSlip(a) {
        fetch(route('anggota.slip-resign', a.id))
            .then((res) => {
                if (!res.ok) throw new Error('Gagal mengunduh slip');
                return res.blob();
            })
            .then((blob) => {
                const url = window.URL.createObjectURL(blob);
                const el = document.createElement('a');
                el.href = url;
                el.download = `slip-resign-${a.no_karyawan}.pdf`;
                document.body.appendChild(el);
                el.click();
                el.remove();
                window.URL.revokeObjectURL(url);
            })
            .catch(() => {
                alert('Gagal mengunduh slip resign. Silakan coba lagi.');
            });
    }

    function terapkanFilter(overrides = {}) {
        router.get(
            route('anggota.index'),
            { cari, cabang: filters.cabang, status: filters.status, ...overrides },
            { preserveState: true, replace: true }
        );
    }

    function resetFilter() {
        setCari('');
        router.get(route('anggota.index'), {}, { preserveState: true, replace: true });
    }

    useEffect(() => {
        if (pertama.current) {
            pertama.current = false;
            return;
        }
        if ((cariDebounced ?? '') !== (filters.cari ?? '')) {
            terapkanFilter({ cari: cariDebounced ?? '' });
        }
    }, [cariDebounced]);

    useEffect(() => {
        setCari(filters.cari ?? '');
    }, [filters.cari]);

    return (
        <AppLayout>
            <Head title="Anggota" />

            <PageHeader title="Anggota" subtitle={`${anggota.total} anggota terdaftar • Sinkron GATE`}>
                {bisaKelola && (
                    <ButtonLink
                        href={route('anggota.import.index')}
                        size="sm"
                        className="bg-gradient-to-r from-brand-navy to-brand-navy-light text-white border-0 shadow-md shadow-brand-navy/20 hover:shadow-lg hover:shadow-brand-navy/25 hover:-translate-y-px active:translate-y-0"
                    >
                        <Upload size={16} />
                        Import Excel
                    </ButtonLink>
                )}
            </PageHeader>

            <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
                <div className="transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md rounded-xl">
                    <StatWidget compact label="Total" value={statistik.total} icon={Users} tone="navy" />
                </div>
                <div className="transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md rounded-xl">
                    <StatWidget compact label="Aktif" value={statistik.aktif} icon={UserCheck} tone="green" />
                </div>
                <div className="transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md rounded-xl">
                    <StatWidget compact label="Nonaktif" value={statistik.nonaktif} icon={UserX} tone="amber" />
                </div>
                <div className="transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md rounded-xl">
                    <StatWidget compact label="Resign" value={statistik.resign} icon={UserMinus} tone="rose" />
                </div>
            </div>

            <Card padding="sm" className="shadow-md border-slate-200/70 mb-4">
                <div className="pb-3 mb-1 border-b border-slate-100">
                    <div className="flex flex-col md:flex-row md:items-center gap-2">
                        <div className="flex-1 relative group">
                            <Search className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-brand-green transition-colors pointer-events-none" size={16} />
                            <TextField
                                type="search"
                                size="sm"
                                value={cari}
                                onChange={(e) => setCari(e.target.value)}
                                placeholder="Cari nama atau no. karyawan..."
                                aria-label="Cari anggota"
                                className="pl-10 pr-8 text-sm rounded-full border-slate-200 bg-slate-50/60 focus:bg-white shadow-inner focus:shadow-md transition-all"
                            />
                            {cari && (
                                <button
                                    type="button"
                                    onClick={() => setCari('')}
                                    aria-label="Hapus pencarian"
                                    className="absolute right-2 top-1/2 -translate-y-1/2 w-6 h-6 inline-flex items-center justify-center rounded-full text-slate-400 hover:text-slate-600 hover:bg-slate-200/70"
                                >
                                    <X size={14} />
                                </button>
                            )}
                        </div>
                        <div className="flex gap-2">
                            <Select
                                size="sm"
                                value={filters.cabang ?? ''}
                                onChange={(e) => terapkanFilter({ cabang: e.target.value })}
                                aria-label="Filter cabang"
                                className="flex-1 min-w-0 md:flex-none md:w-40 text-sm rounded-full border-slate-200 bg-slate-50/60"
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
                                aria-label="Filter status"
                                className="flex-1 min-w-0 md:flex-none md:w-36 text-sm rounded-full border-slate-200 bg-slate-50/60"
                            >
                                <option value="">Semua Status</option>
                                <option value="aktif">Aktif</option>
                                <option value="nonaktif">Nonaktif</option>
                                <option value="resign">Resign</option>
                            </Select>
                            {filterAktif && (
                                <Button type="button" variant="ghost" size="sm" className="shrink-0 rounded-full" onClick={resetFilter}>
                                    <RefreshCw size={14} />
                                    Reset
                                </Button>
                            )}
                        </div>
                    </div>
                </div>
                <div className="overflow-x-auto">
                    <table className="w-full table-sticky-first table-head-static text-sm">
                        <thead>
                            <tr className="text-left bg-gradient-to-r from-slate-50 to-white border-b border-slate-200/80">
                                <th className="px-4 py-2.5 text-xs font-bold uppercase tracking-wide text-slate-500 whitespace-nowrap">Anggota</th>
                                <th className="px-4 py-2.5 text-xs font-bold uppercase tracking-wide text-slate-500 whitespace-nowrap">Cabang</th>
                                <th className="hidden lg:table-cell px-4 py-2.5 text-xs font-bold uppercase tracking-wide text-slate-500">Jabatan</th>
                                <th className="hidden lg:table-cell px-4 py-2.5 text-xs font-bold uppercase tracking-wide text-slate-500 whitespace-nowrap">Lama</th>
                                <th className="px-4 py-2.5 text-xs font-bold uppercase tracking-wide text-slate-500">Status</th>
                                {canResign && (
                                    <th className="px-4 py-2.5 text-right text-xs font-bold uppercase tracking-wide text-slate-500"><span className="sr-only">Aksi resign</span></th>
                                )}
                            </tr>
                        </thead>
                        <tbody>
                            {anggota.data.length === 0 ? (
                                <tr>
                                    <td colSpan={canResign ? 6 : 5} className="px-4 py-10 text-center">
                                        <p className="text-sm font-semibold text-slate-600">Tidak ada data ditemukan</p>
                                        <p className="text-sm text-slate-400 mt-1">Coba ubah kata kunci atau filter.</p>
                                        {filterAktif && (
                                            <Button type="button" variant="outline" size="sm" className="mt-3 rounded-full" onClick={resetFilter}>
                                                Tampilkan semua anggota
                                            </Button>
                                        )}
                                    </td>
                                </tr>
                            ) : (
                                anggota.data.map((a) => (
                                    <tr key={a.id} onClick={() => bukaEdit(a)} onKeyDown={(e) => { if (e.key === 'Enter') bukaEdit(a); }} tabIndex={0} title="Klik untuk ubah" className="group border-b border-slate-50 last:border-0 hover:bg-gradient-to-r hover:from-brand-green-light/40 hover:to-transparent transition-all cursor-pointer focus-visible:outline-none focus-visible:bg-brand-green-light/50">
                                        <td className="px-4 py-2.5">
                                            <div className="flex items-center gap-2.5 min-w-0">
                                                <FotoAnggota nama={a.nama} fotoUrl={a.foto_url} ukuran="sm" />
                                                <div className="min-w-0">
                                                    <p className="text-sm font-semibold text-slate-800 truncate max-w-[180px] sm:max-w-[240px] group-hover:text-brand-navy transition-colors">{a.nama}</p>
                                                    <p className="text-xs text-slate-400 truncate">{a.no_karyawan}</p>
                                                </div>
                                                <ChevronRight size={15} className="ml-1 shrink-0 text-slate-300 opacity-0 -translate-x-1 group-hover:opacity-100 group-hover:translate-x-0 transition-all" aria-hidden="true" />
                                            </div>
                                        </td>
                                        <td className="px-4 py-2.5 text-slate-600 whitespace-nowrap">{a.cabang}</td>
                                        <td className="hidden lg:table-cell px-4 py-2.5 text-slate-600 max-w-[200px] truncate">{a.jabatanMaster?.nama ?? a.jabatan}</td>
                                        <td className="hidden lg:table-cell px-4 py-2.5 text-slate-600 whitespace-nowrap">{formatLamaAnggota(a.lama_keanggotaan_tahun)}</td>
                                        <td className="px-4 py-2.5">
                                            <StatusBadge status={a.status} />
                                        </td>
                                        {canResign && (
                                            <td className="px-4 py-2.5 text-right" onClick={(e) => e.stopPropagation()}>
                                                <div className="inline-flex items-center gap-1 justify-end">
                                                    {a.status === 'aktif' && (
                                                        <TombolAksi label={`Resign ${a.nama}`} title="Resign" tone="rose" onClick={() => { setResignAnggota(a); setResignDrawerOpen(true); }} />
                                                    )}
                                                    {a.status === 'resign' && (
                                                        <>
                                                            <TombolAksi label={`Aktifkan ${a.nama}`} title="Aktifkan kembali" tone="green" onClick={() => setReaktivasiAnggota(a)} />
                                                            <TombolAksi label={`Slip resign ${a.nama}`} title="Slip resign" tone="blue" onClick={() => bukaSlip(a)} />
                                                        </>
                                                    )}
                                                </div>
                                            </td>
                                        )}
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            </Card>

            <Pagination links={anggota.links} />

            <Drawer show={drawerOpen} title={editAnggota?.nama ?? 'Ubah Anggota'} onClose={() => setDrawerOpen(false)}>
                {editAnggota && (
                    <EditDrawer
                        key={editAnggota.id}
                        anggota={editAnggota}
                        onClose={() => setDrawerOpen(false)}
                    />
                )}
            </Drawer>

            <Drawer show={resignDrawerOpen} title={`Resign ${resignAnggota?.nama ?? ''}`} onClose={() => { setResignDrawerOpen(false); setResignAnggota(null); }} maxWidth="2xl">
                {resignAnggota && (
                    <ResignDrawer
                        key={resignAnggota.id}
                        anggota={resignAnggota}
                        onClose={() => { setResignDrawerOpen(false); setResignAnggota(null); }}
                    />
                )}
            </Drawer>

            {reaktivasiAnggota && (
                <AktifkanKembaliDialog
                    key={reaktivasiAnggota.id}
                    anggota={reaktivasiAnggota}
                    onClose={() => setReaktivasiAnggota(null)}
                />
            )}
        </AppLayout>
    );
}
