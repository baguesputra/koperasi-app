import { Wallet } from 'lucide-react';
import { formatRupiah } from '@/Utils/formatCurrency';

export default function RingkasanKas({ ringkasan, judul = 'Beban Persetujuan Bulan Ini' }) {
    if (!ringkasan) return null;

    const { total_menunggu = 0, jumlah_menunggu = 0, saldo = 0, sisa_proyeksi = 0 } = ringkasan;
    const defisit = sisa_proyeksi < 0;
    const persen = saldo > 0 ? Math.min(100, Math.round((total_menunggu / saldo) * 100)) : (total_menunggu > 0 ? 100 : 0);

    return (
        <div className="bg-brand-navy rounded-2xl p-5 text-white mb-5">
            <div className="flex items-center gap-2 mb-4">
                <Wallet size={18} className="text-brand-green-light" />
                <p className="text-base font-bold">{judul}</p>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div className="bg-white/5 rounded-xl p-4 border border-white/10">
                    <p className="text-xs text-slate-300 mb-1">Total Pengajuan Menunggu ({jumlah_menunggu})</p>
                    <p className="text-xl font-bold">{formatRupiah(total_menunggu)}</p>
                </div>
                <div className="bg-white/5 rounded-xl p-4 border border-white/10">
                    <p className="text-xs text-slate-300 mb-1">Saldo Kas Pinjaman</p>
                    <p className="text-xl font-bold">{formatRupiah(saldo)}</p>
                </div>
                <div className={`rounded-xl p-4 border ${defisit ? 'bg-red-500/15 border-red-400/30' : 'bg-brand-green/20 border-brand-green/30'}`}>
                    <p className={`text-xs mb-1 ${defisit ? 'text-red-200' : 'text-brand-green/80'}`}>Proyeksi Sisa Kas</p>
                    <p className={`text-xl font-bold ${defisit ? 'text-red-200' : 'text-brand-green-light'}`}>{formatRupiah(sisa_proyeksi)}</p>
                </div>
            </div>

            <div className="mt-4">
                <div className="h-2 rounded-full bg-white/10 overflow-hidden">
                    <div
                        className={`h-full rounded-full transition-all ${defisit ? 'bg-red-400' : 'bg-brand-green'}`}
                        style={{ width: `${persen}%` }}
                    />
                </div>
                <p className="text-xs text-slate-300 mt-1.5">
                    {persen}% saldo terpakai bila semua disetujui
                    {defisit && ' — pertimbangkan penyesuaian nominal.'}
                </p>
            </div>
        </div>
    );
}
