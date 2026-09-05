import AppLayout from '@/Layouts/AppLayout';
import { Head, router } from '@inertiajs/react';
import { CheckCircle2, Clock, FileText, Search, ShieldCheck, XCircle, Wallet } from 'lucide-react';
import { useState } from 'react';
import Card from '@/Components/ui/Card';
import StatWidget from '@/Components/ui/StatWidget';
import StatusBadge from '@/Components/ui/StatusBadge';
import Drawer from '@/Components/ui/Drawer';
import DetailDrawer from './Partials/DetailDrawer';
import { formatRupiah } from '@/Utils/formatCurrency';
import Pagination from '@/Components/ui/Pagination';

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

export default function Index({ pinjaman, filters, statistik, cabangAktif, daftarCabang }) {
    const [cari, setCari] = useState(filters.cari ?? '');
    const [detailPinjaman, setDetailPinjaman] = useState(null);
    const [drawerOpen, setDrawerOpen] = useState(false);

    const tab = [
        { key: '', label: 'Semua Cabang' },
        ...daftarCabang.map((c) => ({ key: c, label: c })),
    ];

    function pindahTab(cabang) {
        router.get(
            route('pinjaman.index'),
            { cari: filters.cari ?? '', status: filters.status ?? '', cabang },
            { preserveState: true, replace: true }
        );
    }

    function terapkanFilter(overrides = {}) {
        router.get(
            route('pinjaman.index'),
            { cari, status: filters.status ?? '', cabang: cabangAktif ?? '', ...overrides },
            { preserveState: true, replace: true }
        );
    }

    function bukaDetail(p) {
        setDetailPinjaman(p);
        setDrawerOpen(true);
    }

    function tutupDetail() {
        setDrawerOpen(false);
    }

    function bukaCetak(e, p) {
        e.stopPropagation();
        window.open(route('pinjaman.cetak-bukti', p.id), '_blank');
    }

    return (
        <AppLayout>
            <Head title="Pinjaman" />

            <div className="mb-6">
                <h1 className="text-2xl font-bold text-slate-800">Pinjaman</h1>
                <p className="text-base text-slate-400 mt-1">{pinjaman.total} pengajuan pinjaman tercatat</p>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-6 gap-4 mb-6">
                <StatWidget compact label="Total Pinjaman" value={statistik.total} icon={FileText} tone="navy" />
                <StatWidget compact label="Diajukan" value={statistik.diajukan} icon={Clock} tone="amber" />
                <StatWidget compact label="Disetujui Bendahara" value={statistik.approved_bendahara} icon={ShieldCheck} tone="navy" />
                <StatWidget compact label="Aktif" value={statistik.aktif} icon={CheckCircle2} tone="green" />
                <StatWidget compact label="Lunas" value={statistik.lunas} icon={CheckCircle2} tone="green" />
                <StatWidget compact label="Ditolak" value={statistik.ditolak} icon={XCircle} tone="red" />
            </div>

            <div className="flex items-center justify-between flex-wrap gap-4 mb-5">
                <div className="flex items-center gap-2 bg-slate-100 p-1 rounded-xl w-fit overflow-x-auto">
                    {tab.map((t) => (
                        <button
                            key={t.key}
                            onClick={() => pindahTab(t.key)}
                            className={`px-4 py-2 text-sm font-semibold rounded-lg whitespace-nowrap transition-colors ${
                                (cabangAktif ?? '') === t.key ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500'
                            }`}
                        >
                            {t.label}
                        </button>
                    ))}
                </div>
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
                    <table className="w-full table-sticky-first">
                        <thead>
                            <tr className="border-b border-slate-100 text-left">
                                <th className="px-5 py-3.5 text-sm font-semibold text-slate-500">Anggota</th>
                                <th className="px-5 py-3.5 text-sm font-semibold text-slate-500">Nominal</th>
                                <th className="px-5 py-3.5 text-sm font-semibold text-slate-500">Tenor</th>
                                <th className="px-5 py-3.5 text-sm font-semibold text-slate-500">Tanggal</th>
                                <th className="px-5 py-3.5 text-sm font-semibold text-slate-500">Status</th>
                                <th className="px-5 py-3.5 text-sm font-semibold text-slate-500 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            {pinjaman.data.length === 0 ? (
                                <tr><td colSpan={6} className="px-5 py-10 text-center text-base text-slate-400">Tidak ada data ditemukan.</td></tr>
                            ) : (
                                pinjaman.data.map((p) => (
                                    <tr key={p.id} onClick={() => bukaDetail(p)} className="border-b border-slate-50 hover:bg-slate-50 transition-colors cursor-pointer">
                                        <td className="px-5 py-3.5">
                                            <div className="flex items-center gap-2">
                                                <div className="min-w-0">
                                                    <button
                                                        onClick={(e) => { e.stopPropagation(); bukaDetail(p); }}
                                                        className="block text-left text-base font-semibold text-slate-800 hover:text-brand-green transition-colors"
                                                    >
                                                        {p.nama}
                                                    </button>
                                                    <div className="flex items-center gap-2 mt-0.5">
                                                        <p className="text-sm text-slate-400">{p.no_anggota}</p>
                                                        {p.cabang && (
                                                            <span className="inline-block px-2 py-0.5 text-[10px] font-semibold rounded-full bg-slate-100 text-slate-600">
                                                                {p.cabang}
                                                            </span>
                                                        )}
                                                    </div>
                                                </div>
                                            </div>
                                            {p.pelunasan_resign_total > 0 && (
                                                <div className="mt-1 flex items-center gap-1.5 text-xs text-rose-600">
                                                    <Wallet size={12} />
                                                    <span className="italic">Dilunasi dari simpanan: -{formatRupiah(p.pelunasan_resign_total)}</span>
                                                </div>
                                            )}
                                        </td>
                                        <td className="px-5 py-3.5 text-base text-slate-700">{formatRupiah(p.nominal)}</td>
                                        <td className="px-5 py-3.5 text-base text-slate-600">{p.tenor_bulan} bulan</td>
                                        <td className="px-5 py-3.5 text-base text-slate-600">{p.tanggal_pengajuan}</td>
                                        <td className="px-5 py-3.5"><StatusBadge status={statusMap[p.status] ?? 'pending'} /></td>
                                        <td className="px-5 py-3.5 text-right">
                                            {p.status === 'aktif' && (
                                                <button
                                                    onClick={(e) => { e.stopPropagation(); bukaCetak(e, p); }}
                                                    className="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-semibold rounded-lg bg-brand-green text-white hover:bg-brand-green/90 transition-colors"
                                                    title="Cetak bukti peminjaman"
                                                >
                                                    <FileText size={14} />
                                                    Cetak
                                                </button>
                                            )}
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            </Card>

            <Pagination links={pinjaman.links} />

            <Drawer show={drawerOpen} title={detailPinjaman ? `Pinjaman #${detailPinjaman.id} - ${detailPinjaman.nama}` : 'Detail Pinjaman'} onClose={tutupDetail} maxWidth="3xl">
                {detailPinjaman && (
                    <DetailDrawer
                        key={detailPinjaman.id}
                        pinjaman={detailPinjaman}
                        angsuran={detailPinjaman.angsuran ?? []}
                        pelunasan_resign={detailPinjaman.pelunasan_resign ?? { total: 0, tanggal: null }}
                        jurnal_pelunasan={detailPinjaman.jurnal_pelunasan ?? []}
                    />
                )}
            </Drawer>
        </AppLayout>
    );
}
