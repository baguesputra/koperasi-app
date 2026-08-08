import AnggotaLayout from '@/Layouts/AnggotaLayout';
import { Head, Link } from '@inertiajs/react';
import { PiggyBank, AlertCircle, CheckCircle2, ArrowRight } from 'lucide-react';

function formatRupiah(angka) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka);
}

export default function Dashboard({ anggota, totalSimpanan, pinjamanAktif, bisaAjukan, alasanTidakBisa, riwayatAngsuran }) {
    return (
        <AnggotaLayout>
            <Head title="Beranda" />

            <div className="mb-6">
                <h1 className="text-xl sm:text-2xl font-bold text-slate-800">
                    Halo, {anggota.nama.split(' ')[0]}
                </h1>
                <p className="text-base text-slate-400 mt-1">No. Anggota: {anggota.no_anggota}</p>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                <div className="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
                    <div className="flex items-center gap-2 text-slate-400 mb-1.5">
                        <PiggyBank size={18} />
                        <p className="text-sm font-medium">Total Simpanan</p>
                    </div>
                    <p className="text-2xl sm:text-3xl font-bold text-slate-800">
                        {formatRupiah(totalSimpanan)}
                    </p>
                </div>

                {pinjamanAktif ? (
                    <div className="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
                        <p className="text-sm font-medium text-slate-400 mb-1.5">Pinjaman Aktif</p>
                        <p className="text-2xl sm:text-3xl font-bold text-slate-800 mb-3">
                            {formatRupiah(pinjamanAktif.nominal)}
                        </p>

                        <div className="mb-1.5 flex items-center justify-between text-sm">
                            <span className="text-slate-500">Progress angsuran</span>
                            <span className="font-semibold text-slate-700">
                                {pinjamanAktif.total_angsuran - pinjamanAktif.sisa_angsuran} / {pinjamanAktif.total_angsuran}
                            </span>
                        </div>
                        <div className="w-full h-2.5 bg-slate-100 rounded-full overflow-hidden">
                            <div
                                className="h-full bg-brand-green rounded-full transition-all"
                                style={{
                                    width: `${((pinjamanAktif.total_angsuran - pinjamanAktif.sisa_angsuran) / pinjamanAktif.total_angsuran) * 100}%`,
                                }}
                            />
                        </div>
                    </div>
                ) : (
                    <div className="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 flex items-center justify-center text-center">
                        <p className="text-base text-slate-400">Belum ada pinjaman aktif.</p>
                    </div>
                )}
            </div>

            <div className="mb-6">
                {bisaAjukan ? (
                    <Link
                        href="#"
                        className="block w-full sm:w-auto sm:inline-block text-center px-8 py-4 text-base font-bold rounded-2xl bg-brand-green text-white shadow-sm hover:bg-brand-green-dark transition-colors"
                    >
                        Ajukan Pinjaman
                    </Link>
                ) : (
                    <div className="flex items-start gap-3 bg-amber-50 border border-amber-100 rounded-2xl p-4 max-w-xl">
                        <AlertCircle className="text-amber-600 shrink-0 mt-0.5" size={20} />
                        <p className="text-sm text-amber-800">{alasanTidakBisa}</p>
                    </div>
                )}
            </div>

            {/* Riwayat Terbaru */}
            <div className="bg-white rounded-2xl border border-slate-100 p-5">
                <div className="flex items-center justify-between mb-4">
                    <p className="text-base font-bold text-slate-700">Pembayaran Terakhir</p>
                    <Link
                        href={route('portal.riwayat')}
                        className="text-sm font-semibold text-brand-green flex items-center gap-1 hover:text-brand-green-dark"
                    >
                        Lihat semua
                        <ArrowRight size={14} />
                    </Link>
                </div>

                {riwayatAngsuran.length === 0 ? (
                    <p className="text-base text-slate-400 text-center py-4">
                        Belum ada riwayat pembayaran.
                    </p>
                ) : (
                    <div className="divide-y divide-slate-50">
                        {riwayatAngsuran.map((item, i) => (
                            <div key={i} className="flex items-center gap-3 py-3">
                                <CheckCircle2 size={20} className="text-brand-green shrink-0" />
                                <div className="flex-1">
                                    <p className="text-base font-semibold text-slate-700">{item.label}</p>
                                    <p className="text-sm text-slate-400">{item.tanggal}</p>
                                </div>
                                <p className="text-base font-bold text-slate-800">
                                    {formatRupiah(item.nominal)}
                                </p>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </AnggotaLayout>
    );
}