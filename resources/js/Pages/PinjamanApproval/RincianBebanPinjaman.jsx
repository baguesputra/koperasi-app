import { useState } from 'react';
import { ChevronDown, ChevronUp, Layers } from 'lucide-react';
import { formatRupiah } from '@/Utils/formatCurrency';

function calculateDueDate(tanggalPengajuan, cicilanKe) {
    if (!tanggalPengajuan) return '-';
    const date = new Date(tanggalPengajuan);
    date.setMonth(date.getMonth() + cicilanKe);
    date.setDate(0);
    return date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
}

export default function RincianBebanPinjaman({ pinjaman }) {
    const [bukaTabel, setBukaTabel] = useState(false);

    if (!pinjaman) return null;

    const jadwal = pinjaman.jadwal_angsuran ?? [];
    const beban = pinjaman.beban_angsuran ?? {};

    const cicilanPertama = beban.cicilan_pertama ?? jadwal[0]?.total_bayar ?? 0;
    const cicilanTerakhir = beban.cicilan_terakhir ?? (jadwal.length > 0 ? jadwal[jadwal.length - 1]?.total_bayar : 0);
    const cicilanRataRata = beban.cicilan_rata_rata ?? (jadwal.length > 0 ? Math.round((pinjaman.total_angsuran ?? 0) / jadwal.length) : 0);

    const totalPokok = pinjaman.total_pokok_angsuran ?? pinjaman.nominal ?? 0;
    const totalBunga = pinjaman.total_bunga_angsuran ?? 0;
    const totalBayar = pinjaman.total_angsuran ?? (totalPokok + totalBunga);

    return (
        <div className="bg-slate-50 border border-slate-200 rounded-xl overflow-hidden text-slate-800">
            {/* Header Box */}
            <div className="p-4 bg-white border-b border-slate-200 flex flex-wrap items-center justify-between gap-2">
                <div className="flex items-center gap-2">
                    <div className="w-7 h-7 rounded-lg bg-brand-navy/5 flex items-center justify-center text-brand-navy">
                        <Layers size={16} />
                    </div>
                    <div>
                        <p className="text-sm font-bold text-slate-800">Simulasi Beban Angsuran</p>
                        <p className="text-xs text-slate-500">Perhitungan bunga menurun (declining balance) {pinjaman.persentase_bunga}%/bln</p>
                    </div>
                </div>
                <button
                    type="button"
                    onClick={() => setBukaTabel(!bukaTabel)}
                    className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors"
                >
                    {bukaTabel ? (
                        <>
                            <span>Tutup Detail Jadwal</span>
                            <ChevronUp size={14} />
                        </>
                    ) : (
                        <>
                            <span>Lihat Detail Jadwal ({jadwal.length} bln)</span>
                            <ChevronDown size={14} />
                        </>
                    )}
                </button>
            </div>

            {/* 3 Kartu Beban Bulanan */}
            <div className="p-4 grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div className="bg-white rounded-lg p-3 border border-slate-200 shadow-2xs">
                    <p className="text-[11px] font-semibold text-slate-400 mb-0.5">Beban Bulan ke-1 (Puncak)</p>
                    <p className="text-base font-bold text-slate-900">{formatRupiah(cicilanPertama)}</p>
                    <p className="text-[11px] text-slate-500 mt-1">
                        Pokok {formatRupiah(jadwal[0]?.nominal_pokok ?? 0)} + Bunga {formatRupiah(jadwal[0]?.nominal_bunga ?? 0)}
                    </p>
                </div>

                <div className="bg-white rounded-lg p-3 border border-slate-200 shadow-2xs">
                    <p className="text-[11px] font-semibold text-slate-400 mb-0.5">Beban Bulan Terakhir</p>
                    <p className="text-base font-bold text-slate-900">{formatRupiah(cicilanTerakhir)}</p>
                    <p className="text-[11px] text-emerald-600 mt-1">
                        Beban teringan di akhir tenor
                    </p>
                </div>

                <div className="bg-white rounded-lg p-3 border border-slate-200 shadow-2xs">
                    <p className="text-[11px] font-semibold text-slate-400 mb-0.5">Estimasi Rata-rata / Bulan</p>
                    <p className="text-base font-bold text-brand-navy">{formatRupiah(cicilanRataRata)}</p>
                    <p className="text-[11px] text-slate-500 mt-1">
                        Tenor total {jadwal.length} bulan
                    </p>
                </div>
            </div>

            {/* Akumulasi Pembayaran */}
            <div className="px-4 pb-4">
                <div className="bg-white rounded-lg p-3 border border-slate-200 flex flex-wrap items-center justify-between gap-3 text-xs">
                    <div>
                        <span className="text-slate-400">Total Pokok: </span>
                        <strong className="text-slate-800 font-semibold">{formatRupiah(totalPokok)}</strong>
                    </div>
                    <div>
                        <span className="text-slate-400">Total Bunga ({pinjaman.persentase_bunga}%): </span>
                        <strong className="text-amber-600 font-semibold">{formatRupiah(totalBunga)}</strong>
                    </div>
                    <div className="pt-1 sm:pt-0 border-t sm:border-t-0 border-slate-100 w-full sm:w-auto">
                        <span className="text-slate-500 font-medium">Total Pengembalian: </span>
                        <strong className="text-brand-navy text-sm font-bold">{formatRupiah(totalBayar)}</strong>
                    </div>
                </div>
            </div>

            {/* Tabel Detail Jadwal (Collapsible) */}
            {bukaTabel && jadwal.length > 0 && (
                <div className="border-t border-slate-200 bg-white">
                    <div className="overflow-x-auto max-h-64 overflow-y-auto">
                        <table className="w-full text-xs">
                            <thead className="bg-slate-50 text-slate-500 sticky top-0 border-b border-slate-200">
                                <tr>
                                    <th className="py-2 px-3 text-center">Bln</th>
                                    <th className="py-2 px-3 text-right">Pokok</th>
                                    <th className="py-2 px-3 text-right">Bunga</th>
                                    <th className="py-2 px-3 text-right">Total Bayar</th>
                                    <th className="py-2 px-3 text-center">Jatuh Tempo</th>
                                </tr>
                            </thead>
                            <tbody>
                                {jadwal.map((a) => (
                                    <tr key={a.cicilan_ke} className="border-b border-slate-100 hover:bg-slate-50">
                                        <td className="py-1.5 px-3 text-center font-medium text-slate-600">{a.cicilan_ke}</td>
                                        <td className="py-1.5 px-3 text-right text-slate-700">{formatRupiah(a.nominal_pokok)}</td>
                                        <td className="py-1.5 px-3 text-right text-amber-600 font-medium">{formatRupiah(a.nominal_bunga)}</td>
                                        <td className="py-1.5 px-3 text-right font-bold text-slate-800">{formatRupiah(a.total_bayar)}</td>
                                        <td className="py-1.5 px-3 text-center text-slate-500 whitespace-nowrap">
                                            {pinjaman.tanggal_pengajuan && calculateDueDate(pinjaman.tanggal_pengajuan, a.cicilan_ke)}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                            <tfoot className="bg-slate-50 border-t-2 border-slate-200 font-bold text-slate-800 sticky bottom-0">
                                <tr>
                                    <td className="py-2 px-3 text-center">Total</td>
                                    <td className="py-2 px-3 text-right">{formatRupiah(totalPokok)}</td>
                                    <td className="py-2 px-3 text-right text-amber-600">{formatRupiah(totalBunga)}</td>
                                    <td className="py-2 px-3 text-right text-brand-navy">{formatRupiah(totalBayar)}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            )}
        </div>
    );
}

