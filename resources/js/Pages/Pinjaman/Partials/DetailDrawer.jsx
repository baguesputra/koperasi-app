import { Printer, Wallet } from 'lucide-react';
import Card from '@/Components/ui/Card';
import StatusBadge from '@/Components/ui/StatusBadge';
import { formatRupiah } from '@/Utils/formatCurrency';

const statusBadgeMap = {
    diajukan: 'pending',
    approved_bendahara: 'pending',
    aktif: 'aktif',
    lunas: 'lunas',
    ditolak: 'ditolak',
};

const angsuranStatusBadgeMap = {
    belum_bayar: 'pending',
    lunas: 'lunas',
    digantikan: 'pending',
};

export default function DetailDrawer({ pinjaman, angsuran, pelunasan_resign, jurnal_pelunasan }) {
    const isResign = pinjaman.anggota_status === 'resign';
    const dilunasiViaResign = pelunasan_resign.total > 0;

    function bukaCetak() {
        window.open(route('pinjaman.cetak-bukti', pinjaman.id), '_blank');
    }

    return (
        <div className="space-y-5">
            <div className="flex items-center gap-3 flex-wrap">
                <h2 className="text-xl font-bold text-slate-800">Pinjaman #{pinjaman.id}</h2>
                <StatusBadge status={statusBadgeMap[pinjaman.status] ?? 'pending'} />
                {isResign && (
                    <span className="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-full bg-rose-50 text-rose-700">
                        Anggota Resign
                    </span>
                )}
                {pinjaman.status === 'aktif' && (
                    <button
                        onClick={bukaCetak}
                        className="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-semibold rounded-lg bg-brand-green text-white hover:bg-brand-green/90 transition-colors ml-auto"
                    >
                        <Printer size={15} />
                        Cetak Bukti
                    </button>
                )}
            </div>

            <p className="text-sm text-slate-500 -mt-3">
                {pinjaman.nama} &bull; {pinjaman.no_anggota}
            </p>

            <div className="grid grid-cols-3 gap-3">
                <Card>
                    <p className="text-xs text-slate-400">Nominal</p>
                    <p className="text-lg font-bold text-slate-800 mt-1">{formatRupiah(pinjaman.nominal)}</p>
                </Card>
                <Card>
                    <p className="text-xs text-slate-400">Tenor</p>
                    <p className="text-lg font-bold text-slate-800 mt-1">{pinjaman.tenor_bulan} bulan</p>
                </Card>
                <Card>
                    <p className="text-xs text-slate-400">Tgl Pengajuan</p>
                    <p className="text-sm font-bold text-slate-800 mt-1">{pinjaman.tanggal_pengajuan}</p>
                </Card>
            </div>

            {dilunasiViaResign && (
                <Card className="border-l-4 border-l-rose-500">
                    <div className="flex items-start gap-3">
                        <div className="w-9 h-9 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0">
                            <Wallet size={18} />
                        </div>
                        <div className="flex-1">
                            <p className="text-sm font-semibold text-rose-700">Pelunasan via Resign</p>
                            <p className="text-xs italic text-slate-500 mt-0.5">Dilunasi otomatis dari simpanan anggota saat proses resign</p>
                            <div className="grid grid-cols-2 gap-3 mt-2">
                                <div>
                                    <p className="text-xs text-slate-400">Total Dilunasi</p>
                                    <p className="text-base font-bold text-slate-800">{formatRupiah(pelunasan_resign.total)}</p>
                                </div>
                                {pelunasan_resign.tanggal && (
                                    <div>
                                        <p className="text-xs text-slate-400">Tanggal Pelunasan</p>
                                        <p className="text-base font-bold text-slate-800">{pelunasan_resign.tanggal}</p>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </Card>
            )}

            <Card padding="none">
                <div className="px-5 py-3 border-b border-slate-100">
                    <p className="text-sm font-bold text-slate-700">Jadwal Angsuran</p>
                </div>
                {angsuran.length === 0 ? (
                    <p className="px-5 py-10 text-center text-sm text-slate-400">Belum ada jadwal angsuran.</p>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead>
                                <tr className="border-b border-slate-100 text-left">
                                    <th className="px-4 py-2.5 text-xs font-semibold text-slate-500">#</th>
                                    <th className="px-4 py-2.5 text-xs font-semibold text-slate-500">Jatuh Tempo</th>
                                    <th className="px-4 py-2.5 text-xs font-semibold text-slate-500">Status</th>
                                    <th className="px-4 py-2.5 text-xs font-semibold text-slate-500">Tgl Bayar</th>
                                    <th className="px-4 py-2.5 text-xs font-semibold text-slate-500 text-right">Nominal</th>
                                </tr>
                            </thead>
                            <tbody>
                                {angsuran.map((a) => (
                                    <tr key={a.id} className="border-b border-slate-50">
                                        <td className="px-4 py-2.5 text-sm text-slate-700">#{a.cicilan_ke}</td>
                                        <td className="px-4 py-2.5 text-sm text-slate-600">{a.tanggal_jatuh_tempo ?? '-'}</td>
                                        <td className="px-4 py-2.5">
                                            <StatusBadge status={angsuranStatusBadgeMap[a.status] ?? 'pending'} />
                                        </td>
                                        <td className="px-4 py-2.5 text-sm text-slate-600">{a.tanggal_konfirmasi_bayar ?? '-'}</td>
                                        <td className="px-4 py-2.5 text-sm font-bold text-slate-800 text-right">{formatRupiah(a.total_bayar)}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </Card>

            {jurnal_pelunasan.length > 0 && (
                <Card padding="none">
                    <div className="px-5 py-3 border-b border-slate-100">
                        <p className="text-sm font-bold text-slate-700">Riwayat Pelunasan Resign</p>
                    </div>
                    <div className="divide-y divide-slate-50">
                        {jurnal_pelunasan.map((j) => (
                            <div key={j.id} className="flex items-center justify-between px-5 py-3">
                                <div>
                                    <p className="text-sm font-semibold text-slate-700">{j.keterangan}</p>
                                    {j.sub_judul && (
                                        <p className="text-xs italic text-slate-500 mt-0.5">{j.sub_judul}</p>
                                    )}
                                    <p className="text-xs text-slate-400 mt-0.5">{j.tanggal}</p>
                                </div>
                                <p className="text-sm font-bold text-rose-600">-{formatRupiah(j.jumlah)}</p>
                            </div>
                        ))}
                    </div>
                </Card>
            )}
        </div>
    );
}
