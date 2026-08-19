import AppLayout from '@/Layouts/AppLayout';
import { Head, router } from '@inertiajs/react';
import { Search, HeartHandshake, PiggyBank, Users } from 'lucide-react';
import { useState } from 'react';
import Card from '@/Components/ui/Card';
import Drawer from '@/Components/ui/Drawer';
import StatWidget from '@/Components/ui/StatWidget';
import { formatRupiah } from '@/Utils/formatCurrency';

const jenisLabel = { pokok: 'Simpanan Pokok', wajib: 'Simpanan Wajib', dana_sosial: 'Dana Sosial' };

export default function Index({
    anggota,
    filters,
    cabangAktif,
    daftarCabang,
    totalDanaSosialTerkumpul,
    totalSimpananSeluruhAnggota,
    totalSimpananTampil,
}) {
    const [cari, setCari] = useState(filters.cari ?? '');
    const [detailAnggota, setDetailAnggota] = useState(null);
    const [drawerOpen, setDrawerOpen] = useState(false);

    const tab = [
        { key: '', label: 'Semua Cabang' },
        ...daftarCabang.map((c) => ({ key: c, label: c })),
    ];

    function pindahTab(cabang) {
        router.get(
            route('simpanan.index'),
            { cari: filters.cari ?? '', cabang },
            { preserveState: true, replace: true }
        );
    }

    function cariSubmit(e) {
        e.preventDefault();
        router.get(
            route('simpanan.index'),
            { cari, cabang: cabangAktif },
            { preserveState: true, replace: true }
        );
    }

    function bukaDetail(a) {
        setDetailAnggota(a);
        setDrawerOpen(true);
    }

    function tutupDetail() {
        setDrawerOpen(false);
    }

    const inisial = (nama) => nama.charAt(0).toUpperCase();

    return (
        <AppLayout>
            <Head title="Simpanan" />

            <div className="mb-6">
                <h1 className="text-2xl font-bold text-slate-800">Simpanan Anggota</h1>
                <p className="text-base text-slate-400 mt-1">Rekap simpanan seluruh anggota</p>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                <StatWidget
                    label={cabangAktif ? `Total Simpanan ${cabangAktif}` : 'Total Simpanan Seluruh Anggota'}
                    value={formatRupiah(totalSimpananTampil)}
                    icon={PiggyBank}
                    tone="navy"
                />
                <StatWidget
                    label="Total Dana Sosial Terkumpul"
                    value={formatRupiah(totalDanaSosialTerkumpul)}
                    icon={HeartHandshake}
                    tone="green"
                />
                <StatWidget
                    label="Jumlah Anggota"
                    value={anggota.total}
                    icon={Users}
                    tone="amber"
                />
            </div>

            <div className="flex items-center justify-between flex-wrap gap-4 mb-5">
                <div className="flex items-center gap-2 bg-slate-100 p-1 rounded-xl w-fit overflow-x-auto">
                    {tab.map((t) => (
                        <button
                            key={t.key}
                            onClick={() => pindahTab(t.key)}
                            className={`px-4 py-2 text-sm font-semibold rounded-lg whitespace-nowrap transition-colors ${
                                cabangAktif === t.key ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500'
                            }`}
                        >
                            {t.label}
                        </button>
                    ))}
                </div>
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
                                <th className="px-5 py-3.5 text-sm font-semibold text-slate-500">Anggota</th>
                                <th className="px-5 py-3.5 text-sm font-semibold text-slate-500">Cabang</th>
                                <th className="px-5 py-3.5 text-right text-sm font-semibold text-slate-500">Total Simpanan</th>
                            </tr>
                        </thead>
                        <tbody>
                            {anggota.data.length === 0 ? (
                                <tr>
                                    <td colSpan={3} className="px-5 py-10 text-center text-base text-slate-400">
                                        {cabangAktif
                                            ? `Belum ada anggota di cabang ${cabangAktif}.`
                                            : 'Belum ada data anggota ditemukan.'}
                                    </td>
                                </tr>
                            ) : (
                                anggota.data.map((a) => (
                                    <tr key={a.id} className="border-b border-slate-50 hover:bg-slate-50 transition-colors">
                                        <td className="px-5 py-3.5">
                                            <div className="flex items-center gap-3">
                                                <div className="w-10 h-10 rounded-full bg-brand-green text-white flex items-center justify-center text-sm font-bold shrink-0">
                                                    {inisial(a.nama)}
                                                </div>
                                                <div className="min-w-0">
                                                    <button
                                                        onClick={() => bukaDetail(a)}
                                                        className="block w-full text-left text-base font-semibold text-slate-800 hover:text-brand-green truncate"
                                                    >
                                                        {a.nama}
                                                    </button>
                                                    <p className="text-sm text-slate-400">{a.no_anggota}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-5 py-3.5">
                                            <span className="inline-block px-2.5 py-1 text-xs font-semibold rounded-full bg-slate-100 text-slate-600">
                                                {a.cabang}
                                            </span>
                                        </td>
                                        <td className="px-5 py-3.5 text-right text-base font-bold text-slate-800">
                                            {formatRupiah(a.total_simpanan)}
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

            <Drawer show={drawerOpen} title={`Simpanan - ${detailAnggota?.nama ?? 'Anggota'}`} onClose={tutupDetail}>
                {detailAnggota && (
                    <div>
                        <div className="mb-5">
                            <p className="text-base font-semibold text-slate-800">{detailAnggota.nama}</p>
                            <p className="text-sm text-slate-400">{detailAnggota.no_anggota}</p>
                        </div>

                        <Card className="mb-5">
                            <p className="text-sm text-slate-400">Total Simpanan</p>
                            <p className="text-3xl font-bold text-slate-800">{formatRupiah(detailAnggota.total_simpanan)}</p>
                        </Card>

                        <Card padding="none">
                            <div className="p-5 border-b border-slate-100">
                                <h2 className="text-lg font-bold text-slate-800">Riwayat</h2>
                            </div>
                            <div className="divide-y divide-slate-50">
                                {detailAnggota.riwayat.length === 0 ? (
                                    <p className="px-5 py-10 text-center text-base text-slate-400">Belum ada riwayat simpanan.</p>
                                ) : (
                                    detailAnggota.riwayat.map((r, i) => (
                                        <div key={i} className="flex items-center justify-between gap-4 px-5 py-3.5">
                                            <div>
                                                <p className="text-base font-semibold text-slate-700">{jenisLabel[r.jenis]}</p>
                                                <p className="text-sm text-slate-400">Periode {r.bulan_periode} &bull; {r.tanggal_input}</p>
                                            </div>
                                            <p className="text-base font-bold text-slate-800 whitespace-nowrap">{formatRupiah(r.jumlah)}</p>
                                        </div>
                                    ))
                                )}
                            </div>
                        </Card>
                    </div>
                )}
            </Drawer>
        </AppLayout>
    );
}
