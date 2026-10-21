import { Landmark } from 'lucide-react';
import { formatRupiah } from '@/Utils/formatCurrency';

export default function RingkasanKas({ ringkasan, judul = 'Beban Persetujuan Pinjaman' }) {
    if (!ringkasan) return null;

    const {
        jumlah_antrean = 0,
        total_antrean = 0,
        saldo_bank = 0,
        sisa_proyeksi = 0,
        beban_peran,
        pipeline,
        peran,
    } = ringkasan;

    const defisit = sisa_proyeksi < 0;

    return (
        <div className="bg-gradient-to-br from-brand-navy to-slate-900 border border-slate-700/60 rounded-2xl p-5 text-white mb-6 shadow-sm">
            {/* Header: Judul */}
            <div className="flex flex-wrap items-center justify-between gap-3 mb-4 pb-3 border-b border-white/10">
                <div className="flex items-center gap-2.5">
                    <div className="w-8 h-8 rounded-lg bg-brand-green/20 border border-brand-green/30 flex items-center justify-center text-brand-green-light">
                        <Landmark size={18} />
                    </div>
                    <div>
                        <div className="flex items-center gap-2">
                            <p className="text-base font-bold tracking-tight">{judul}</p>
                            {beban_peran?.tahap && (
                                <span className="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-white/10 text-slate-300">
                                    {beban_peran.tahap}
                                </span>
                            )}
                        </div>
                        <p className="text-xs text-slate-400">Komitmen kas keluar & kapasitas kas bank koperasi</p>
                    </div>
                </div>
            </div>

            {/* 4 Kartu Metrik Utama */}
            <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">
                {/* 1. Beban Tugas Peran Saat Ini */}
                <div className="bg-white/10 rounded-xl p-4 border border-white/20 backdrop-blur-sm">
                    <div className="flex items-center justify-between gap-2 mb-1">
                        <p className="text-xs font-semibold text-brand-green-light">
                            {beban_peran?.label ?? 'Antrean Anda'}
                        </p>
                        <span className="text-[10px] uppercase tracking-wider px-1.5 py-0.5 rounded bg-brand-green/20 text-brand-green-light font-bold">
                            Tindakan
                        </span>
                    </div>
                    <p className="text-2xl font-black text-white">{beban_peran?.jumlah ?? 0} <span className="text-sm font-medium text-slate-300">pengajuan</span></p>
                    <p className="text-xs text-slate-300 mt-1 font-semibold">
                        Beban: {formatRupiah(beban_peran?.total ?? 0)}
                    </p>
                    {Boolean(beban_peran?.cair_mandiri_jumlah) && (
                        <p className="text-[11px] text-amber-300 mt-1">
                            + {beban_peran.cair_mandiri_jumlah} mandiri ({formatRupiah(beban_peran.cair_mandiri_total)})
                        </p>
                    )}
                </div>

                {/* 2. Total Antrean Berjalan (Seluruh Koperasi) */}
                <div className="bg-white/5 rounded-xl p-4 border border-white/10">
                    <p className="text-xs text-slate-300 mb-1">Total Antrean Koperasi</p>
                    <p className="text-xl font-bold">{formatRupiah(total_antrean)}</p>
                    <p className="text-[11px] text-slate-400 mt-1">{jumlah_antrean} pengajuan di seluruh tahap</p>
                </div>

                {/* 3. Saldo Bank Aktif */}
                <div className="bg-white/5 rounded-xl p-4 border border-white/10">
                    <p className="text-xs text-slate-300 mb-1">Saldo Bank Koperasi</p>
                    <p className="text-xl font-bold">{formatRupiah(saldo_bank)}</p>
                    <p className="text-[11px] text-slate-400 mt-1">Tersedia untuk transfer pencairan</p>
                </div>

                {/* 4. Proyeksi Sisa Saldo Bank */}
                <div className={`rounded-xl p-4 border ${defisit ? 'bg-red-500/15 border-red-400/30' : 'bg-emerald-500/15 border-emerald-400/30'}`}>
                    <p className={`text-xs mb-1 ${defisit ? 'text-red-200' : 'text-emerald-200'}`}>Proyeksi Sisa Bank</p>
                    <p className={`text-xl font-bold ${defisit ? 'text-red-300' : 'text-emerald-300'}`}>{formatRupiah(sisa_proyeksi)}</p>
                    <p className={`text-[11px] mt-1 ${defisit ? 'text-red-300/80' : 'text-emerald-300/80'}`}>
                        {defisit ? 'Saldo kurang jika semua cair' : 'Sisa aman setelah pencairan'}
                    </p>
                </div>
            </div>

            {/* Pipeline Jalur Antrean (Funnel Approval) */}
            {pipeline && (
                <div className="mt-4 pt-3 border-t border-white/10">
                    <p className="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">
                        Distribusi Antrean Berdasarkan Tahap Persetujuan
                    </p>
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-2">
                        {/* Tahap 1: Admin */}
                        <div className={`rounded-lg px-3 py-2 text-xs border transition-colors ${
                            peran === 'admin'
                                ? 'bg-brand-green/15 border-brand-green/40 text-white font-medium'
                                : 'bg-white/5 border-white/10 text-slate-300'
                        }`}>
                            <div className="flex items-center justify-between">
                                <span className={peran === 'admin' ? 'text-brand-green-light font-bold' : 'text-slate-400'}>
                                    1. Verifikasi Admin
                                </span>
                                <span className="font-bold">{pipeline.admin?.jumlah ?? 0}</span>
                            </div>
                            <p className="text-[11px] text-slate-400 mt-0.5">{formatRupiah(pipeline.admin?.total ?? 0)}</p>
                        </div>

                        {/* Tahap 2: Bendahara */}
                        <div className={`rounded-lg px-3 py-2 text-xs border transition-colors ${
                            peran === 'bendahara'
                                ? 'bg-brand-green/15 border-brand-green/40 text-white font-medium'
                                : 'bg-white/5 border-white/10 text-slate-300'
                        }`}>
                            <div className="flex items-center justify-between">
                                <span className={peran === 'bendahara' ? 'text-brand-green-light font-bold' : 'text-slate-400'}>
                                    2. Usulan Bendahara
                                </span>
                                <span className="font-bold">{pipeline.bendahara?.jumlah ?? 0}</span>
                            </div>
                            <p className="text-[11px] text-slate-400 mt-0.5">{formatRupiah(pipeline.bendahara?.total ?? 0)}</p>
                        </div>

                        {/* Tahap 3: Ketua */}
                        <div className={`rounded-lg px-3 py-2 text-xs border transition-colors ${
                            peran === 'ketua'
                                ? 'bg-brand-green/15 border-brand-green/40 text-white font-medium'
                                : 'bg-white/5 border-white/10 text-slate-300'
                        }`}>
                            <div className="flex items-center justify-between">
                                <span className={peran === 'ketua' ? 'text-brand-green-light font-bold' : 'text-slate-400'}>
                                    3. Keputusan Ketua
                                </span>
                                <span className="font-bold">{pipeline.ketua?.jumlah ?? 0}</span>
                            </div>
                            <p className="text-[11px] text-slate-400 mt-0.5">{formatRupiah(pipeline.ketua?.total ?? 0)}</p>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
