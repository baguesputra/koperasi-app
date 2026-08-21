import AppLayout from '@/Layouts/AppLayout';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Wallet } from 'lucide-react';
import Card from '@/Components/ui/Card';
import { formatRupiah } from '@/Utils/formatCurrency';

const jenisLabel = { pokok: 'Simpanan Pokok', wajib: 'Simpanan Wajib', dana_sosial: 'Dana Sosial' };

export default function Show({ anggota, riwayat, totalSimpanan, alokasiPelunasanResign, alokasiDariPokok, alokasiDariWajib, tanggalResign }) {
    const adaPelunasanResign = alokasiPelunasanResign > 0;

    return (
        <AppLayout>
            <Head title={`Simpanan - ${anggota.nama}`} />

            <Link href={route('simpanan.index')} className="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 hover:text-brand-navy mb-5">
                <ArrowLeft size={16} />
                Kembali
            </Link>

            <div className="mb-6">
                <h1 className="text-2xl font-bold text-slate-800">{anggota.nama}</h1>
                <p className="text-base text-slate-400 mt-1">{anggota.no_anggota}</p>
            </div>

            <Card className="mb-5">
                <p className="text-sm text-slate-400">Total Simpanan</p>
                <p className="text-3xl font-bold text-slate-800">{formatRupiah(totalSimpanan)}</p>
            </Card>

            {adaPelunasanResign && (
                <Card className="mb-5 border-l-4 border-l-rose-500">
                    <div className="flex items-start gap-3">
                        <div className="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0">
                            <Wallet size={20} />
                        </div>
                        <div className="flex-1">
                            <p className="text-sm font-semibold text-rose-700">Dipakai untuk Pelunasan Pinjaman saat Resign</p>
                            <p className="text-xs italic text-slate-500 mt-0.5">
                                Saat proses resign, sebagian simpanan dialokasikan untuk melunasi angsuran tersisa.
                            </p>
                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-3">
                                <div>
                                    <p className="text-xs text-slate-400">Dari Simpanan Pokok</p>
                                    <p className="text-base font-bold text-slate-800">-{formatRupiah(alokasiDariPokok)}</p>
                                </div>
                                <div>
                                    <p className="text-xs text-slate-400">Dari Simpanan Wajib</p>
                                    <p className="text-base font-bold text-slate-800">-{formatRupiah(alokasiDariWajib)}</p>
                                </div>
                                <div>
                                    <p className="text-xs text-slate-400">Total Dipakai</p>
                                    <p className="text-base font-bold text-rose-600">-{formatRupiah(alokasiPelunasanResign)}</p>
                                </div>
                            </div>
                            {tanggalResign && (
                                <p className="text-xs text-slate-400 mt-2">Tanggal proses: {tanggalResign}</p>
                            )}
                        </div>
                    </div>
                </Card>
            )}

            <Card padding="none">
                <div className="p-5 border-b border-slate-100">
                    <h2 className="text-lg font-bold text-slate-800">Riwayat Simpanan</h2>
                </div>
                {riwayat.length === 0 ? (
                    <p className="px-5 py-10 text-center text-base text-slate-400">Belum ada riwayat simpanan.</p>
                ) : (
                    <div className="divide-y divide-slate-50">
                        {riwayat.map((r, i) => (
                            <div key={i} className="flex items-center justify-between px-5 py-3.5">
                                <div>
                                    <p className="text-base font-semibold text-slate-700">{jenisLabel[r.jenis]}</p>
                                    <p className="text-sm text-slate-400">Periode {r.bulan_periode} &bull; {r.tanggal_input}</p>
                                </div>
                                <p className="text-base font-bold text-slate-800">{formatRupiah(r.jumlah)}</p>
                            </div>
                        ))}
                    </div>
                )}
            </Card>
        </AppLayout>
    );
}
