import { Link } from '@inertiajs/react';
import { ChevronRight, Wallet } from 'lucide-react';
import { formatRupiah } from '@/Utils/formatCurrency';

const labelBulan = new Intl.DateTimeFormat('id-ID', { month: 'long', year: 'numeric' }).format(new Date());

export default function RingkasanKas({ ringkasan, judul = 'Beban Persetujuan Bulan Ini', tinjauHref = null }) {
    if (!ringkasan) return null;

    const { total_menunggu = 0, jumlah_menunggu = 0 } = ringkasan;
    const pagu = ringkasan.pagu ?? null;
    const limit = pagu ? pagu.layak : (ringkasan.sisa_proyeksi ?? 0);
    const proyeksi = limit - total_menunggu;
    const defisit = proyeksi < 0;
    const persen = limit > 0 ? Math.min(100, Math.round((total_menunggu / limit) * 100)) : (total_menunggu > 0 ? 100 : 0);

    return (
        <div className="bg-brand-navy rounded-2xl p-5 text-white mb-5">
            <div className="flex items-center justify-between gap-3 flex-wrap mb-4">
                <div className="flex items-center gap-2">
                    <Wallet size={18} className="text-brand-green-light" />
                    <p className="text-base font-bold">{judul}</p>
                </div>
                <p className="text-xs text-slate-300">Saldo bank {labelBulan}</p>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div className="bg-white/5 rounded-xl p-4 border border-white/10">
                    <p className="text-xs text-slate-300 mb-1">Total Pengajuan Menunggu ({jumlah_menunggu})</p>
                    <p className="text-xl font-bold">{formatRupiah(total_menunggu)}</p>
                </div>
                <div className="bg-white/5 rounded-xl p-4 border border-white/10">
                    <p className="text-xs text-slate-300 mb-1">Layak Cair</p>
                    <p className="text-xl font-bold">{formatRupiah(limit)}</p>
                    {pagu && (
                        <p className="text-xs text-slate-300 mt-1">
                            Pagu {formatRupiah(pagu.pagu)} &bull; Sudah cair {formatRupiah(pagu.sudah_cair)}
                        </p>
                    )}
                </div>
                <div className={`rounded-xl p-4 border ${defisit ? 'bg-red-500/15 border-red-400/30' : 'bg-brand-green/20 border-brand-green/30'}`}>
                    <p className={`text-xs mb-1 ${defisit ? 'text-red-200' : 'text-brand-green/80'}`}>Proyeksi</p>
                    <p className={`text-xl font-bold ${defisit ? 'text-red-200' : 'text-brand-green-light'}`}>{formatRupiah(proyeksi)}</p>
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
                    {persen}% limit terpakai bila semua disetujui
                    {defisit && ' — pertimbangkan penyesuaian nominal.'}
                </p>
            </div>

            {tinjauHref && jumlah_menunggu > 0 && (
                <Link
                    href={tinjauHref}
                    className="mt-4 inline-flex items-center gap-1 text-sm font-bold text-brand-green-light hover:text-white transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-green focus-visible:ring-offset-2 focus-visible:ring-offset-brand-navy rounded"
                >
                    Tinjau {jumlah_menunggu} antrean
                    <ChevronRight size={14} aria-hidden="true" />
                </Link>
            )}

        </div>
    );
}
