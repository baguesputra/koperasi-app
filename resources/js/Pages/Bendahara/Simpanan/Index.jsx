import AppLayout from '@/Layouts/AppLayout';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { HeartHandshake, Check } from 'lucide-react';
import Card from '@/Components/ui/Card';
import StatWidget from '@/Components/ui/StatWidget';
import Button from '@/Components/ui/Button';

function formatRupiah(angka) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka);
}

export default function Index({ bulan, belumSimpanan, totalDanaSosialTerkumpul }) {
    const [terpilih, setTerpilih] = useState([]);
    const [processing, setProcessing] = useState(false);

    function ubahBulan(nilaiBaru) {
        router.get(route('bendahara.simpanan.index'), { bulan: nilaiBaru }, { preserveState: true });
    }

    function toggleSatu(id) {
        setTerpilih((prev) => prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id]);
    }

    function toggleSemua() {
        setTerpilih((prev) => prev.length === belumSimpanan.length ? [] : belumSimpanan.map((a) => a.id));
    }

    function konfirmasi() {
        setProcessing(true);
        router.post(route('bendahara.simpanan.konfirmasi'), { anggota_ids: terpilih, bulan_periode: bulan }, {
            onSuccess: () => setTerpilih([]),
            onFinish: () => setProcessing(false),
        });
    }

    return (
        <AppLayout>
            <Head title="Konfirmasi Simpanan" />

            <div className="flex items-center justify-between flex-wrap gap-4 mb-6">
                <div>
                    <h1 className="text-2xl font-bold text-slate-800">Konfirmasi Simpanan Wajib</h1>
                    <p className="text-base text-slate-400 mt-1">
                        Tandai anggota yang simpanan wajibnya sudah dipotong bulan ini
                    </p>
                </div>
                <input
                    type="month"
                    value={bulan}
                    onChange={(e) => ubahBulan(e.target.value)}
                    className="px-4 py-2.5 text-base rounded-xl border border-slate-300 bg-white focus:border-brand-green outline-none"
                />
            </div>

            <div className="mb-6 max-w-sm">
                <StatWidget
                    label="Total Dana Sosial Terkumpul"
                    value={formatRupiah(totalDanaSosialTerkumpul)}
                    icon={HeartHandshake}
                    tone="bg-brand-green-light text-brand-green-dark"
                />
            </div>

            <Card padding="none">
                <div className="p-5 border-b border-slate-100 flex items-center justify-between">
                    <h2 className="text-lg font-bold text-slate-800">
                        Belum Konfirmasi ({belumSimpanan.length})
                    </h2>
                    {belumSimpanan.length > 0 && (
                        <button onClick={toggleSemua} className="text-sm font-semibold text-brand-green hover:text-brand-green-dark">
                            {terpilih.length === belumSimpanan.length ? 'Batalkan semua' : 'Pilih semua'}
                        </button>
                    )}
                </div>

                {belumSimpanan.length === 0 ? (
                    <p className="text-base text-slate-400 text-center py-10">
                        Semua anggota aktif sudah dikonfirmasi untuk bulan ini.
                    </p>
                ) : (
                    <div className="divide-y divide-slate-50">
                        {belumSimpanan.map((a) => (
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
                                    <p className="text-sm text-slate-400">{a.no_anggota} &bull; {a.cabang}</p>
                                </div>
                            </label>
                        ))}
                    </div>
                )}
            </Card>

            {terpilih.length > 0 && (
                <div className="fixed bottom-0 inset-x-0 bg-white border-t border-slate-200 px-6 py-4 shadow-lg">
                    <div className="max-w-[1400px] mx-auto flex items-center justify-between">
                        <p className="text-base font-semibold text-slate-700">
                            {terpilih.length} anggota dipilih
                        </p>
                        <Button variant="primary" onClick={konfirmasi} disabled={processing}>
                            <Check size={18} />
                            {processing ? 'Memproses...' : 'Konfirmasi Terpilih'}
                        </Button>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}