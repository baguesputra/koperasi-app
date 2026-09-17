import AppLayout from '@/Layouts/AppLayout';
import { Head } from '@inertiajs/react';
import { Wallet } from 'lucide-react';
import BackLink from '@/Components/ui/BackLink';
import PageHeader from '@/Components/ui/PageHeader';
import Card from '@/Components/ui/Card';
import StatusBadge from '@/Components/ui/StatusBadge';
import { formatRupiah } from '@/Utils/formatCurrency';

const jenisLabel = { pokok: 'Pokok', wajib: 'Wajib', dana_sosial: 'Dana Sosial' };
const jenisTone = {
    pokok: 'bg-brand-navy/5 text-brand-navy',
    wajib: 'bg-brand-green-light text-brand-green-dark',
    dana_sosial: 'bg-rose-50 text-rose-700',
};

export default function Show({ anggota, riwayat, totalSimpanan, alokasiPelunasanResign, alokasiDariPokok, alokasiDariWajib, tanggalResign }) {
    const adaPelunasanResign = alokasiPelunasanResign > 0;

    return (
        <AppLayout>
            <Head title={`Simpanan - ${anggota.nama}`} />

            <BackLink href={route('simpanan.index')}>Kembali ke daftar simpanan</BackLink>
            <PageHeader title={anggota.nama} subtitle={`${anggota.no_karyawan} • ${anggota.no_anggota ?? '-'}`}>
                <StatusBadge status={anggota.status} />
            </PageHeader>

            <div className="rounded-2xl bg-gradient-to-r from-brand-navy to-brand-navy-light text-white px-5 py-4 mb-4 shadow-md shadow-brand-navy/20">
                <p className="text-xs text-white/70">Total Simpanan (pokok + wajib)</p>
                <p className="text-3xl font-bold tabular-nums mt-0.5">{formatRupiah(totalSimpanan)}</p>
            </div>

            {adaPelunasanResign && (
                <Card className="mb-4 border-l-4 border-l-rose-500">
                    <div className="flex items-start gap-3">
                        <span className="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 inline-flex items-center justify-center shrink-0">
                            <Wallet size={19} />
                        </span>
                        <div className="flex-1 min-w-0">
                            <p className="text-sm font-bold text-rose-700">Dipakai untuk Pelunasan Pinjaman saat Resign</p>
                            <p className="text-xs text-slate-500 mt-0.5">Sebagian simpanan dialokasikan untuk melunasi angsuran tersisa.</p>
                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-2 mt-3 text-sm">
                                <div className="rounded-xl bg-slate-50 px-3 py-2">
                                    <p className="text-[11px] text-slate-400">Dari Pokok</p>
                                    <p className="font-bold text-slate-800 tabular-nums">-{formatRupiah(alokasiDariPokok)}</p>
                                </div>
                                <div className="rounded-xl bg-slate-50 px-3 py-2">
                                    <p className="text-[11px] text-slate-400">Dari Wajib</p>
                                    <p className="font-bold text-slate-800 tabular-nums">-{formatRupiah(alokasiDariWajib)}</p>
                                </div>
                                <div className="rounded-xl bg-rose-50 px-3 py-2">
                                    <p className="text-[11px] text-rose-400">Total Dipakai</p>
                                    <p className="font-bold text-rose-600 tabular-nums">-{formatRupiah(alokasiPelunasanResign)}</p>
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
                <div className="px-4 py-3 border-b border-slate-100">
                    <h2 className="text-sm font-bold text-slate-800">Riwayat ({riwayat.length})</h2>
                </div>
                {riwayat.length === 0 ? (
                    <p className="px-4 py-10 text-center text-sm text-slate-400">Belum ada riwayat simpanan.</p>
                ) : (
                    <ul className="divide-y divide-slate-50">
                        {riwayat.map((r, i) => (
                            <li key={i} className="flex items-center justify-between gap-3 px-4 py-2.5">
                                <div className="flex items-center gap-2.5 min-w-0">
                                    <span className={`px-2.5 py-1 text-xs font-bold rounded-full shrink-0 ${jenisTone[r.jenis] ?? 'bg-slate-100 text-slate-600'}`}>
                                        {jenisLabel[r.jenis] ?? r.jenis}
                                    </span>
                                    <p className="text-xs text-slate-400 truncate">Periode {r.bulan_periode} • {r.tanggal_input}</p>
                                </div>
                                <p className="text-sm font-bold text-slate-800 tabular-nums whitespace-nowrap">{formatRupiah(r.jumlah)}</p>
                            </li>
                        ))}
                    </ul>
                )}
            </Card>
        </AppLayout>
    );
}
