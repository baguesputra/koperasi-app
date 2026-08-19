import AppLayout from '@/Layouts/AppLayout';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { TrendingUp, Wallet, Calendar, Check, AlertTriangle } from 'lucide-react';
import Card from '@/Components/ui/Card';
import StatWidget from '@/Components/ui/StatWidget';
import Button from '@/Components/ui/Button';
import { formatRupiah } from '@/Utils/formatCurrency';

export default function Index({ bulan, daftarAngsuran, daftarAngsuranPercepatan = [], peringatanPercepatan = [], totalTagihanBulanIni, totalKeuntunganBulanIni, totalKeuntunganKeseluruhan }) {
    const [terpilih, setTerpilih] = useState([]);
    const [terpilihPercepatan, setTerpilihPercepatan] = useState([]);
    const [processing, setProcessing] = useState(false);

    function ubahBulan(nilaiBaru) {
        router.get(route('bendahara.angsuran.index'), { bulan: nilaiBaru }, { preserveState: true });
    }

    function toggleSatu(id) {
        setTerpilih((prev) => prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id]);
    }

    function toggleSemua() {
        setTerpilih((prev) => prev.length === daftarAngsuran.length ? [] : daftarAngsuran.map((a) => a.id));
    }

    function konfirmasi() {
        setProcessing(true);
        router.post(route('bendahara.angsuran.konfirmasi'), { angsuran_ids: terpilih }, {
            onSuccess: () => setTerpilih([]),
            onFinish: () => setProcessing(false),
        });
    }

    function toggleSatuPercepatan(id) {
        setTerpilihPercepatan((prev) => prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id]);
    }

    function toggleSemuaPercepatan() {
        setTerpilihPercepatan((prev) => prev.length === daftarAngsuranPercepatan.length ? [] : daftarAngsuranPercepatan.map((a) => a.id));
    }

    function konfirmasiPercepatan() {
        setProcessing(true);
        router.post(route('bendahara.angsuran.percepatan.konfirmasi'), { angsuran_percepatan_ids: terpilihPercepatan }, {
            onSuccess: () => setTerpilihPercepatan([]),
            onFinish: () => setProcessing(false),
        });
    }

    const widgets = [
        {
            label: 'Total Tagihan Bulan Ini',
            value: formatRupiah(totalTagihanBulanIni),
            icon: Calendar,
            tone: 'amber',
        },
        {
            label: 'Keuntungan Bulan Ini',
            value: formatRupiah(totalKeuntunganBulanIni),
            icon: TrendingUp,
            tone: 'green',
        },
        {
            label: 'Total Keuntungan Keseluruhan',
            value: formatRupiah(totalKeuntunganKeseluruhan),
            icon: Wallet,
            tone: 'navy',
        },
    ];

    return (
        <AppLayout>
            <Head title="Konfirmasi Angsuran" />

            <div className="flex items-center justify-between flex-wrap gap-4 mb-6">
                <div>
                    <h1 className="text-2xl font-bold text-slate-800">Konfirmasi Angsuran</h1>
                    <p className="text-base text-slate-400 mt-1">
                        Tandai angsuran yang sudah dipotong dari gaji anggota
                    </p>
                </div>
                <input
                    type="month"
                    value={bulan}
                    onChange={(e) => ubahBulan(e.target.value)}
                    className="px-4 py-2.5 text-base rounded-xl border border-slate-300 bg-white focus:border-brand-green outline-none"
                />
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                {widgets.map((w) => (
                    <StatWidget key={w.label} label={w.label} value={w.value} icon={w.icon} tone={w.tone} />
                ))}
            </div>

            <Card padding="none">
                <div className="p-5 border-b border-slate-100 flex items-center justify-between">
                    <h2 className="text-lg font-bold text-slate-800">
                        Jatuh Tempo Bulan Ini ({daftarAngsuran.length})
                    </h2>
                    {daftarAngsuran.length > 0 && (
                        <button onClick={toggleSemua} className="text-sm font-semibold text-brand-green hover:text-brand-green-dark">
                            {terpilih.length === daftarAngsuran.length ? 'Batalkan semua' : 'Pilih semua'}
                        </button>
                    )}
                </div>

                {daftarAngsuran.length === 0 ? (
                    <p className="text-base text-slate-400 text-center py-10">
                        Tidak ada angsuran jatuh tempo di bulan ini.
                    </p>
                ) : (
                    <div className="divide-y divide-slate-50">
                        {daftarAngsuran.map((a) => (
                            <label
                                key={a.id}
                                className="flex items-center gap-4 px-5 py-4 hover:bg-slate-50 cursor-pointer transition-colors"
                            >
                                <input
                                    type="checkbox"
                                    checked={terpilih.includes(a.id)}
                                    onChange={() => toggleSatu(a.id)}
                                    className="w-5 h-5 rounded border-slate-300 text-brand-green focus:ring-brand-green/30"
                                />
                                <div className="flex-1">
                                    <p className="text-base font-semibold text-slate-800">{a.nama}</p>
                                    <p className="text-sm text-slate-400">
                                        {a.no_anggota} &bull; Cicilan ke-{a.cicilan_ke} &bull; Jatuh tempo {a.tanggal_jatuh_tempo}
                                        {a.terlambat && <span className="text-red-600 font-semibold"> &bull; Terlambat</span>}
                                    </p>
                                    {peringatanPercepatan.includes(a.pinjaman_id) && (
                                        <p className="mt-1 inline-flex items-center gap-1 text-xs font-semibold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-full">
                                            <AlertTriangle size={12} /> Ada pengajuan perubahan tenor
                                        </p>
                                    )}
                                </div>
                                <div className="text-right">
                                    <p className="text-base font-bold text-slate-800">{formatRupiah(a.total_bayar)}</p>
                                    <p className="text-sm text-brand-green">
                                        +{formatRupiah(a.nominal_bunga)} keuntungan
                                    </p>
                                </div>
                            </label>
                        ))}
                    </div>
                )}
            </Card>

            {daftarAngsuranPercepatan.length > 0 && (
                <Card padding="none" className="mt-6">
                    <div className="p-5 border-b border-slate-100 flex items-center justify-between">
                        <h2 className="text-lg font-bold text-slate-800">
                            Tagihan Percepatan / Final ({daftarAngsuranPercepatan.length})
                        </h2>
                        <button onClick={toggleSemuaPercepatan} className="text-sm font-semibold text-brand-green hover:text-brand-green-dark">
                            {terpilihPercepatan.length === daftarAngsuranPercepatan.length ? 'Batalkan semua' : 'Pilih semua'}
                        </button>
                    </div>
                    <div className="divide-y divide-slate-50">
                        {daftarAngsuranPercepatan.map((a) => (
                            <label
                                key={a.id}
                                className="flex items-center gap-4 px-5 py-4 hover:bg-slate-50 cursor-pointer transition-colors"
                            >
                                <input
                                    type="checkbox"
                                    checked={terpilihPercepatan.includes(a.id)}
                                    onChange={() => toggleSatuPercepatan(a.id)}
                                    className="w-5 h-5 rounded border-slate-300 text-brand-green focus:ring-brand-green/30"
                                />
                                <div className="flex-1">
                                    <p className="text-base font-semibold text-slate-800">{a.nama}</p>
                                    <p className="text-sm text-slate-400">
                                        {a.no_anggota} &bull; {a.tipe === 'lunas_total' ? 'Lunas Sekarang' : 'Ubah Tenor'} &bull; Cicilan ke-{a.cicilan_ke} &bull; Jatuh tempo {a.tanggal_jatuh_tempo}
                                        {a.terlambat && <span className="text-red-600 font-semibold"> &bull; Terlambat</span>}
                                    </p>
                                </div>
                                <div className="text-right">
                                    <p className="text-base font-bold text-slate-800">{formatRupiah(a.total_bayar)}</p>
                                    <p className="text-sm text-brand-green">
                                        +{formatRupiah(a.nominal_bunga)} keuntungan
                                    </p>
                                </div>
                            </label>
                        ))}
                    </div>
                </Card>
            )}

            {terpilih.length > 0 && (
                <div className="fixed bottom-0 inset-x-0 bg-white border-t border-slate-200 px-6 py-4 shadow-lg">
                    <div className="max-w-[1400px] mx-auto flex items-center justify-between">
                        <p className="text-base font-semibold text-slate-700">
                            {terpilih.length} angsuran dipilih
                        </p>
                        <Button variant="primary" onClick={konfirmasi} disabled={processing}>
                            <Check size={18} />
                            {processing ? 'Memproses...' : 'Konfirmasi Terpilih'}
                        </Button>
                    </div>
                </div>
            )}

            {terpilihPercepatan.length > 0 && (
                <div className="fixed bottom-0 inset-x-0 bg-white border-t border-slate-200 px-6 py-4 shadow-lg">
                    <div className="max-w-[1400px] mx-auto flex items-center justify-between">
                        <p className="text-base font-semibold text-slate-700">
                            {terpilihPercepatan.length} tagihan percepatan dipilih
                        </p>
                        <Button variant="primary" onClick={konfirmasiPercepatan} disabled={processing}>
                            <Check size={18} />
                            {processing ? 'Memproses...' : 'Konfirmasi Terpilih'}
                        </Button>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}