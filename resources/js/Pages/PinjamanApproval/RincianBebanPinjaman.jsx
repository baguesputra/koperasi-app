import { useState } from 'react';
import { ChevronDown, ChevronUp, Layers } from 'lucide-react';
import { DrawerSection } from '@/Components/ui/DrawerSection';
import PrivacyWrapper from '@/Components/ui/PrivacyWrapper';
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

    const toggleButton = jadwal.length > 0 && (
        <button
            type="button"
            onClick={() => setBukaTabel(!bukaTabel)}
            className="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 transition-colors"
        >
            {bukaTabel ? (
                <>
                    <span>Tutup Jadwal</span>
                    <ChevronUp size={14} aria-hidden="true" />
                </>
            ) : (
                <>
                    <span>Detail Jadwal ({jadwal.length} bln)</span>
                    <ChevronDown size={14} aria-hidden="true" />
                </>
            )}
        </button>
    );

    return (
        <DrawerSection
            title="Simulasi Beban Angsuran"
            description={`Bunga menurun (declining balance) ${pinjaman.persentase_bunga}%/bln`}
            icon={Layers}
            action={toggleButton}
            padding="none"
        >
            {/* 3 Metrik Beban Bulanan dalam Segmented Strip */}
            <div className="grid grid-cols-1 sm:grid-cols-3 divide-y sm:divide-y-0 sm:divide-x divide-slate-100">
                <div className="p-3.5 sm:px-4">
                    <p className="text-[11px] font-medium text-slate-500">Beban Bulan ke-1 (Puncak)</p>
                    <p className="text-base font-bold text-slate-900 tabular-nums mt-0.5">
                        <PrivacyWrapper>{formatRupiah(cicilanPertama)}</PrivacyWrapper>
                    </p>
                    <p className="text-[11px] text-slate-500 mt-1 tabular-nums">
                        Pokok <PrivacyWrapper>{formatRupiah(jadwal[0]?.nominal_pokok ?? 0)}</PrivacyWrapper> + Bunga <PrivacyWrapper>{formatRupiah(jadwal[0]?.nominal_bunga ?? 0)}</PrivacyWrapper>
                    </p>
                </div>

                <div className="p-3.5 sm:px-4">
                    <p className="text-[11px] font-medium text-slate-500">Beban Bulan Terakhir</p>
                    <p className="text-base font-bold text-slate-900 tabular-nums mt-0.5">
                        <PrivacyWrapper>{formatRupiah(cicilanTerakhir)}</PrivacyWrapper>
                    </p>
                    <p className="text-[11px] text-brand-green-dark font-medium mt-1">
                        Beban teringan di akhir tenor
                    </p>
                </div>

                <div className="p-3.5 sm:px-4">
                    <p className="text-[11px] font-medium text-slate-500">Estimasi Rata-rata / Bulan</p>
                    <p className="text-base font-bold text-brand-navy tabular-nums mt-0.5">
                        <PrivacyWrapper>{formatRupiah(cicilanRataRata)}</PrivacyWrapper>
                    </p>
                    <p className="text-[11px] text-slate-500 mt-1">
                        Tenor total {jadwal.length} bulan
                    </p>
                </div>
            </div>

            {/* Akumulasi Pembayaran */}
            <div className="bg-[#F8FAFC] border-t border-slate-100 px-4 py-2.5 flex flex-wrap items-center justify-between gap-3 text-xs">
                <div>
                    <span className="text-slate-500">Total Pokok: </span>
                    <strong className="text-slate-800 font-semibold tabular-nums">
                        <PrivacyWrapper>{formatRupiah(totalPokok)}</PrivacyWrapper>
                    </strong>
                </div>
                <div>
                    <span className="text-slate-500">Total Bunga ({pinjaman.persentase_bunga}%): </span>
                    <strong className="text-amber-700 font-semibold tabular-nums">
                        <PrivacyWrapper>{formatRupiah(totalBunga)}</PrivacyWrapper>
                    </strong>
                </div>
                <div className="pt-1 sm:pt-0 border-t sm:border-t-0 border-slate-200/70 w-full sm:w-auto">
                    <span className="text-slate-600 font-medium">Total Pengembalian: </span>
                    <strong className="text-brand-navy text-sm font-bold tabular-nums">
                        <PrivacyWrapper>{formatRupiah(totalBayar)}</PrivacyWrapper>
                    </strong>
                </div>
            </div>

            {/* Tabel Detail Jadwal (Collapsible) */}
            {bukaTabel && jadwal.length > 0 && (
                <div className="border-t border-slate-200/80 bg-white">
                    <div className="overflow-x-auto max-h-64 overflow-y-auto">
                        <table className="w-full text-xs">
                            <thead className="bg-gradient-to-r from-[#f4f7fb] via-[#f8fafc] to-[#f1faf4] text-slate-600 sticky top-0 border-b border-slate-200/80">
                                <tr>
                                    <th className="py-2 px-3 text-center font-semibold">Bln</th>
                                    <th className="py-2 px-3 text-right font-semibold">Pokok</th>
                                    <th className="py-2 px-3 text-right font-semibold">Bunga</th>
                                    <th className="py-2 px-3 text-right font-semibold">Total Bayar</th>
                                    <th className="py-2 px-3 text-center font-semibold">Jatuh Tempo</th>
                                </tr>
                            </thead>
                            <tbody>
                                {jadwal.map((a) => (
                                    <tr key={a.cicilan_ke} className="border-b border-slate-100 hover:bg-brand-green-light/25 transition-colors">
                                        <td className="py-1.5 px-3 text-center font-medium text-slate-600 tabular-nums">{a.cicilan_ke}</td>
                                        <td className="py-1.5 px-3 text-right text-slate-700 tabular-nums">
                                            <PrivacyWrapper>{formatRupiah(a.nominal_pokok)}</PrivacyWrapper>
                                        </td>
                                        <td className="py-1.5 px-3 text-right text-amber-700 font-medium tabular-nums">
                                            <PrivacyWrapper>{formatRupiah(a.nominal_bunga)}</PrivacyWrapper>
                                        </td>
                                        <td className="py-1.5 px-3 text-right font-bold text-slate-800 tabular-nums">
                                            <PrivacyWrapper>{formatRupiah(a.total_bayar)}</PrivacyWrapper>
                                        </td>
                                        <td className="py-1.5 px-3 text-center text-slate-500 whitespace-nowrap">
                                            {pinjaman.tanggal_pengajuan && calculateDueDate(pinjaman.tanggal_pengajuan, a.cicilan_ke)}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                            <tfoot className="bg-[#F4F7FB] border-t-2 border-slate-200 font-bold text-slate-800 sticky bottom-0">
                                <tr>
                                    <td className="py-2 px-3 text-center">Total</td>
                                    <td className="py-2 px-3 text-right tabular-nums">
                                        <PrivacyWrapper>{formatRupiah(totalPokok)}</PrivacyWrapper>
                                    </td>
                                    <td className="py-2 px-3 text-right text-amber-700 tabular-nums">
                                        <PrivacyWrapper>{formatRupiah(totalBunga)}</PrivacyWrapper>
                                    </td>
                                    <td className="py-2 px-3 text-right text-brand-navy tabular-nums">
                                        <PrivacyWrapper>{formatRupiah(totalBayar)}</PrivacyWrapper>
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            )}
        </DrawerSection>
    );
}

