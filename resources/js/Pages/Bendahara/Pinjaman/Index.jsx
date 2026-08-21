import AppLayout from '@/Layouts/AppLayout';
import { Head } from '@inertiajs/react';
import { useForm } from '@inertiajs/react';
import { ClipboardCheck, Wallet } from 'lucide-react';
import { useState } from 'react';
import Card from '@/Components/ui/Card';
import StatusBadge from '@/Components/ui/StatusBadge';
import PageHeader from '@/Components/ui/PageHeader';
import Button from '@/Components/ui/Button';
import Drawer from '@/Components/ui/Drawer';
import KeputusanDrawer from './Partials/KeputusanDrawer';
import { formatRupiah } from '@/Utils/formatCurrency';

const tabDasar = 'inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-t-lg border -mb-px';
const tabAktif = `${tabDasar} bg-white border-slate-200 border-b-0 text-brand-navy`;
const tabNonAktif = `${tabDasar} bg-slate-50 border-slate-200 text-slate-500 hover:bg-white hover:text-slate-700`;

export default function Index({ menungguTinjauan, menungguPencairan, riwayat }) {
    const [tab, setTab] = useState('baru');
    const [detailPinjaman, setDetailPinjaman] = useState(null);
    const [drawerOpen, setDrawerOpen] = useState(false);
    const [cairPinjaman, setCairPinjaman] = useState(null);
    const [cairOpen, setCairOpen] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({ catatan: '' });

    function bukaDetail(p) {
        setDetailPinjaman(p);
        setDrawerOpen(true);
    }

    function tutupDetail() {
        setDrawerOpen(false);
    }

    function bukaCair(p) {
        setCairPinjaman(p);
        setCairOpen(true);
    }

    function tutupCair() {
        setCairOpen(false);
        setCairPinjaman(null);
        reset('catatan');
    }

    function cairkan() {
        post(route('bendahara.pinjaman.cair', cairPinjaman.id), {
            preserveScroll: true,
            onSuccess: () => tutupCair(),
        });
    }

    return (
        <AppLayout>
            <Head title="Approval Pinjaman" />

            <PageHeader
                title="Approval Pinjaman"
                subtitle={`${menungguTinjauan.length} pengajuan menunggu tinjauan Anda`}
            />

            <Card padding="none">
                {/* Tab lembaran dokumen */}
                <div className="flex items-end gap-1 px-3 pt-2 border-b border-slate-200">
                    <button onClick={() => setTab('baru')} className={tab === 'baru' ? tabAktif : tabNonAktif}>
                        Approval Baru
                        {menungguTinjauan.length > 0 && (
                            <span className="inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[11px] font-bold">
                                {menungguTinjauan.length}
                            </span>
                        )}
                    </button>
                    <button onClick={() => setTab('pencairan')} className={tab === 'pencairan' ? tabAktif : tabNonAktif}>
                        Pencairan
                        {menungguPencairan.length > 0 && (
                            <span className="inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[11px] font-bold">
                                {menungguPencairan.length}
                            </span>
                        )}
                    </button>
                    <button onClick={() => setTab('riwayat')} className={tab === 'riwayat' ? tabAktif : tabNonAktif}>
                        Riwayat
                    </button>
                </div>

                {/* Tabel dalam kartu yang sama */}
                <div className="overflow-x-auto">
                    {tab === 'baru' ? (
                        <table className="w-full">
                            <thead>
                                <tr className="text-left">
                                    <th className="px-5 py-3 text-sm font-semibold text-slate-500">Anggota</th>
                                    <th className="px-5 py-3 text-sm font-semibold text-slate-500">Nominal</th>
                                    <th className="px-5 py-3 text-sm font-semibold text-slate-500">Tenor</th>
                                    <th className="px-5 py-3 text-sm font-semibold text-slate-500">Tanggal</th>
                                    <th className="px-5 py-3 text-sm font-semibold text-slate-500">Status</th>
                                    <th className="px-5 py-3 text-right text-sm font-semibold text-slate-500">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                {menungguTinjauan.length === 0 ? (
                                    <tr>
                                        <td colSpan={6} className="px-5 py-8 text-center text-base text-slate-400">
                                            Tidak ada pengajuan yang menunggu tinjauan.
                                        </td>
                                    </tr>
                                ) : (
                                    menungguTinjauan.map((p) => (
                                        <tr key={p.id} onClick={() => bukaDetail(p)} className="border-t border-slate-50 hover:bg-slate-50 transition-colors cursor-pointer">
                                            <td className="px-5 py-3">
                                                <div className="flex items-center gap-3">
                                                    <div className="w-9 h-9 rounded-full bg-brand-green text-white flex items-center justify-center text-sm font-bold shrink-0">
                                                        {p.anggota.nama.charAt(0).toUpperCase()}
                                                    </div>
                                                    <div className="min-w-0">
                                                        <p className="text-sm font-semibold text-slate-800 truncate">{p.anggota.nama}</p>
                                                        <p className="text-xs text-slate-400">{p.anggota.no_anggota}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-5 py-3 text-sm text-slate-700">{formatRupiah(p.nominal)}</td>
                                            <td className="px-5 py-3 text-sm text-slate-600">{p.tenor_bulan} bulan</td>
                                            <td className="px-5 py-3 text-sm text-slate-600">{p.tanggal_pengajuan}</td>
                                            <td className="px-5 py-3"><StatusBadge status="pending" /></td>
                                            <td className="px-5 py-3 text-right">
                                                <button
                                                    onClick={(e) => { e.stopPropagation(); bukaDetail(p); }}
                                                    className="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-bold rounded-lg bg-brand-green text-white hover:bg-brand-green-dark transition-colors"
                                                >
                                                    <ClipboardCheck size={15} />
                                                    Tinjau
                                                </button>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    ) : tab === 'pencairan' ? (
                        <table className="w-full">
                            <thead>
                                <tr className="text-left">
                                    <th className="px-5 py-3 text-sm font-semibold text-slate-500">Anggota</th>
                                    <th className="px-5 py-3 text-sm font-semibold text-slate-500">Nominal</th>
                                    <th className="px-5 py-3 text-sm font-semibold text-slate-500">Tenor</th>
                                    <th className="px-5 py-3 text-sm font-semibold text-slate-500">Tanggal</th>
                                    <th className="px-5 py-3 text-sm font-semibold text-slate-500">Status</th>
                                    <th className="px-5 py-3 text-right text-sm font-semibold text-slate-500">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                {menungguPencairan.length === 0 ? (
                                    <tr>
                                        <td colSpan={6} className="px-5 py-8 text-center text-base text-slate-400">
                                            Tidak ada pinjaman yang menunggu pencairan.
                                        </td>
                                    </tr>
                                ) : (
                                    menungguPencairan.map((p) => (
                                        <tr key={p.id} className="border-t border-slate-50">
                                            <td className="px-5 py-3">
                                                <div className="flex items-center gap-3">
                                                    <div className="w-9 h-9 rounded-full bg-brand-green text-white flex items-center justify-center text-sm font-bold shrink-0">
                                                        {p.anggota.nama.charAt(0).toUpperCase()}
                                                    </div>
                                                    <div className="min-w-0">
                                                        <p className="text-sm font-semibold text-slate-800 truncate">{p.anggota.nama}</p>
                                                        <p className="text-xs text-slate-400">{p.anggota.no_anggota}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-5 py-3 text-sm text-slate-700">{formatRupiah(p.nominal)}</td>
                                            <td className="px-5 py-3 text-sm text-slate-600">{p.tenor_bulan} bulan</td>
                                            <td className="px-5 py-3 text-sm text-slate-600">{p.tanggal_pengajuan}</td>
                                            <td className="px-5 py-3"><StatusBadge status="approved_bendahara" /></td>
                                            <td className="px-5 py-3 text-right">
                                                <button
                                                    onClick={() => bukaCair(p)}
                                                    className="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-bold rounded-lg bg-brand-navy text-white hover:opacity-90 transition-colors"
                                                >
                                                    <Wallet size={15} />
                                                    Cairkan
                                                </button>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    ) : (
                        <table className="w-full">
                            <thead>
                                <tr className="text-left">
                                    <th className="px-5 py-3 text-sm font-semibold text-slate-500">Anggota</th>
                                    <th className="px-5 py-3 text-sm font-semibold text-slate-500">Nominal</th>
                                    <th className="px-5 py-3 text-sm font-semibold text-slate-500">Tenor</th>
                                    <th className="px-5 py-3 text-sm font-semibold text-slate-500">Tanggal</th>
                                    <th className="px-5 py-3 text-sm font-semibold text-slate-500">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                {riwayat.length === 0 ? (
                                    <tr>
                                        <td colSpan={5} className="px-5 py-8 text-center text-base text-slate-400">
                                            Belum ada riwayat keputusan.
                                        </td>
                                    </tr>
                                ) : (
                                    riwayat.map((p) => (
                                        <tr key={p.id} className="border-t border-slate-50">
                                            <td className="px-5 py-3">
                                                <p className="text-sm font-semibold text-slate-800 truncate">{p.nama}</p>
                                                <p className="text-xs text-slate-400">{p.no_anggota}</p>
                                            </td>
                                            <td className="px-5 py-3 text-sm text-slate-700">{formatRupiah(p.nominal)}</td>
                                            <td className="px-5 py-3 text-sm text-slate-600">{p.tenor_bulan} bulan</td>
                                            <td className="px-5 py-3 text-sm text-slate-600">{p.tanggal_pengajuan}</td>
                                            <td className="px-5 py-3"><StatusBadge status={p.status} /></td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    )}
                </div>
            </Card>

            <Drawer show={drawerOpen} title={`Tinjau Pinjaman - ${detailPinjaman?.anggota?.nama ?? 'Anggota'}`} onClose={tutupDetail} className="max-w-3xl">
                {detailPinjaman && (
                    <KeputusanDrawer
                        key={detailPinjaman.id}
                        pinjaman={detailPinjaman}
                        onClose={tutupDetail}
                    />
                )}
            </Drawer>

            <Drawer show={cairOpen} title={`Cairkan Pinjaman - ${cairPinjaman?.anggota?.nama ?? 'Anggota'}`} onClose={tutupCair}>
                {cairPinjaman && (
                    <div>
                        <div className="bg-brand-navy rounded-2xl p-4 text-white mb-4">
                            <p className="text-xs text-slate-300 mb-1">Nominal Pencairan</p>
                            <p className="text-2xl font-bold">{formatRupiah(cairPinjaman.nominal)}</p>
                        </div>

                        <div className="bg-slate-50 rounded-xl p-3 mb-4">
                            <p className="text-xs text-slate-400 mb-1">Rekening Tujuan Pencairan</p>
                            <p className="text-sm font-bold text-slate-800">{cairPinjaman.rekening.bank}</p>
                            <p className="text-sm text-slate-600">{cairPinjaman.rekening.no_rekening}</p>
                            <p className="text-sm text-slate-400">a.n. {cairPinjaman.rekening.atas_nama}</p>
                        </div>

                        {cairPinjaman.catatan_bendahara && (
                            <div className="mb-4">
                                <p className="text-xs text-slate-400 mb-1">Catatan Bendahara</p>
                                <p className="text-sm text-slate-700">{cairPinjaman.catatan_bendahara}</p>
                            </div>
                        )}

                        <label className="block text-sm font-semibold text-slate-600 mb-2">
                            Catatan Pencairan
                        </label>
                        <textarea
                            value={data.catatan}
                            onChange={(e) => setData('catatan', e.target.value)}
                            rows={3}
                            placeholder="Contoh: Dana telah ditransfer ke rekening anggota."
                            className="w-full px-4 py-2.5 text-base rounded-xl border border-slate-300 focus:border-brand-green focus:ring-2 focus:ring-brand-green/20 outline-none transition-colors"
                            autoFocus
                        />
                        {errors.catatan && <p className="text-sm text-red-600 mt-1.5">{errors.catatan}</p>}

                        <div className="flex items-center gap-3 mt-4">
                            <Button
                                type="button"
                                variant="primary"
                                disabled={processing}
                                onClick={cairkan}
                            >
                                {processing ? 'Memproses...' : 'Cairkan Sekarang'}
                            </Button>
                            <Button type="button" variant="ghost" onClick={tutupCair}>
                                Batal
                            </Button>
                        </div>
                    </div>
                )}
            </Drawer>
        </AppLayout>
    );
}
