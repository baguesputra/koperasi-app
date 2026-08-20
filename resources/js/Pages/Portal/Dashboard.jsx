import AnggotaLayout from '@/Layouts/AnggotaLayout';
import { Head, Link } from '@inertiajs/react';
import {
    PiggyBank, Wallet, TrendingUp, ArrowRight, CheckCircle2,
    ArrowDownCircle, Clock, XCircle, Info, Calendar,
    ShieldCheck, Percent, Repeat,
} from 'lucide-react';
import { formatRupiah } from '@/Utils/formatCurrency';

const statusPengajuanLabel = {
    diajukan: { text: 'Menunggu ditinjau Bendahara', step: 1 },
    approved_bendahara: { text: 'Menunggu persetujuan Ketua Koperasi', step: 2 },
};

const tipeLabel = {
    perpanjang: 'Perubahan Tenor (Perpanjang)',
    percepat: 'Perubahan Tenor (Percepat)',
    lunas_total: 'Pelunasan Dipercepat (Total)',
};

export default function Dashboard({
    anggota, totalSimpanan, simpananPokok, simpananWajib, limitMaksimal,
    limitTersedia, sisaAngsuranAktif, cicilanPokokAktif,
    pinjamanAktif, pinjamanAktifList, pinjamanAktifCount, pengajuanPercepatanMenunggu,
    pengajuanBerjalan, pengajuanDitolak, angsuranBerikutnya,
    bisaAjukan, alasanTidakBisa, riwayatGabungan, tabelTenor, settingSimpanan,
}) {
    const aggregateTotalNominal = (pinjamanAktifList || []).reduce((sum, p) => sum + (p.nominal || 0), 0);
    const aggregateTotalSisaAngsuran = (pinjamanAktifList || []).reduce((sum, p) => sum + (p.sisa_angsuran || 0), 0);
    const aggregateTotalAngsuran = (pinjamanAktifList || []).reduce((sum, p) => sum + (p.total_angsuran || 0), 0);
    const aggregateSisaTotalBayar = (pinjamanAktifList || []).reduce((sum, p) => sum + (p.sisa_total_bayar || 0), 0);
    const progress = aggregateTotalAngsuran > 0
        ? Math.round(((aggregateTotalAngsuran - aggregateTotalSisaAngsuran) / aggregateTotalAngsuran) * 100)
        : 0;

    return (
        <AnggotaLayout>
            <Head title="Beranda" />

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-5">
                {/* Kolom kiri - lebar, konten personal */}
                <div className="lg:col-span-2 space-y-5">
                    {/* Hero */}
                    <div className="bg-brand-navy rounded-3xl p-6 text-white space-y-3">
                        <p className="text-slate-300 text-sm mb-1">Selamat datang,</p>
                        <h1 className="text-2xl font-bold mb-1">{anggota.nama.split(' ')[0]}</h1>
                        <p className="text-slate-400 text-sm mb-5">
                            {anggota.no_anggota} &bull; Anggota sejak {anggota.lama_keanggotaan_label}
                        </p>

                        {pinjamanAktifCount > 0 ? (
                            <>
                                <Link
                                    href={route('portal.riwayat')}
                                    className="block bg-white/10 rounded-2xl p-5 hover:bg-white/15 transition-colors group"
                                >
                                    <div className="flex items-center justify-between mb-3">
                                        <p className="text-sm text-slate-300">
                                            {pinjamanAktifCount > 1 ? `${pinjamanAktifCount} Pinjaman Aktif` : 'Pinjaman Aktif'}
                                        </p>
                                        <span className="text-xs font-semibold bg-brand-green text-white px-2.5 py-1 rounded-full">
                                            {progress}% lunas
                                        </span>
                                    </div>
                                    <p className="text-2xl font-bold mb-3">{formatRupiah(aggregateTotalNominal)}</p>
                                    <div className="w-full h-2 bg-white/15 rounded-full overflow-hidden mb-4">
                                        <div className="h-full bg-brand-green rounded-full transition-all" style={{ width: `${progress}%` }} />
                                    </div>
                                    {angsuranBerikutnya && (
                                        <div className="flex items-center justify-between text-sm text-slate-200 group-hover:text-white transition-colors">
                                            <span className="flex items-center gap-2">
                                                <Calendar size={16} />
                                                Cicilan ke-{angsuranBerikutnya.cicilan_ke} &bull; {angsuranBerikutnya.tanggal_jatuh_tempo} &bull; {formatRupiah(angsuranBerikutnya.total_bayar)}
                                            </span>
                                            <ArrowRight size={16} className="group-hover:translate-x-0.5 transition-transform shrink-0" />
                                        </div>
                                    )}
                                    {pinjamanAktifCount > 1 && (
                                        <p className="text-xs text-slate-400 mt-3">
                                            Sisa total bayar (gabungan): {formatRupiah(aggregateSisaTotalBayar)}
                                        </p>
                                    )}
                                </Link>

                                {pengajuanPercepatanMenunggu.length > 0 ? (
                                    <div className="bg-amber-500/15 border border-amber-400/30 rounded-2xl p-5">
                                        <div className="flex items-start gap-3">
                                            <Repeat className="text-amber-300 shrink-0 mt-0.5" size={18} />
                                            <div className="flex-1">
                                                <p className="text-sm font-semibold text-amber-100 mb-1">
                                                    Pengajuan perubahan tenor/pelunasan sedang diproses
                                                </p>
                                                {pengajuanPercepatanMenunggu.map((pp) => (
                                                    <p key={pp.id} className="text-xs text-amber-200/90">
                                                        {'• '}{tipeLabel[pp.tipe]} - Pinjaman {formatRupiah(pp.pinjaman_nominal)}
                                                        {' '}({pp.tenor_lama} → {pp.tenor_baru ?? 'lunas'} bulan)
                                                        {' '}• Status: {statusPengajuanLabel[pp.status]?.text ?? pp.status}
                                                    </p>
                                                ))}
                                            </div>
                                        </div>
                                    </div>
                                ) : pinjamanAktifList.some(p => p.sudah_pakai_percepatan) ? (
                                    <div className="bg-slate-500/15 border border-slate-400/30 rounded-2xl p-5">
                                        <p className="text-sm text-slate-200">
                                            Pinjaman ini sudah pernah menggunakan hak perubahan tenor/pelunasan dipercepat.
                                            {' '}Selesaikan pelunasan terlebih dahulu untuk menggunakan hak ini lagi.
                                        </p>
                                    </div>
                                ) : (
                                    <Link
                                        href={route('portal.percepatan.create')}
                                        className="flex items-center justify-center gap-2 w-full px-5 py-3 text-sm font-bold rounded-xl bg-white/10 border border-white/20 text-white hover:bg-white/20 transition-colors"
                                    >
                                        <Repeat size={16} />
                                        Ajukan Perubahan Tenor / Pelunasan Dipercepat
                                    </Link>
                                )}

                                {bisaAjukan ? (
                                    <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white/10 rounded-2xl p-5">
                                        <div>
                                            <p className="text-base font-semibold mb-1">Siap mengajukan pinjaman berikutnya</p>
                                            <p className="text-sm text-slate-300">
                                                Tersisa {aggregateTotalSisaAngsuran} dari {aggregateTotalAngsuran} cicilan
                                                {pinjamanAktifCount > 1 && ` (gabungan ${pinjamanAktifCount} pinjaman)`}.
                                            </p>
                                            {sisaAngsuranAktif > 0 && cicilanPokokAktif > 0 && (
                                                <div className="mt-2 pt-2 border-t border-white/10">
                                                    <p className="text-xs text-slate-300 mb-0.5">
                                                        Sisa cicilan pokok: <span className="font-semibold text-white">{formatRupiah(sisaAngsuranAktif * cicilanPokokAktif)}</span>
                                                        {' '}({sisaAngsuranAktif} × {formatRupiah(cicilanPokokAktif)})
                                                    </p>
                                                    <p className="text-xs text-slate-300">
                                                        Limit tersedia: <span className="font-semibold text-white">{formatRupiah(limitTersedia)}</span>
                                                        <span className="text-slate-400"> (dari limit {formatRupiah(limitMaksimal)})</span>
                                                    </p>
                                                </div>
                                            )}
                                        </div>
                                        <Link
                                            href={route('portal.pinjaman.create')}
                                            className="shrink-0 inline-flex items-center justify-center gap-2 px-6 py-3 text-sm font-bold rounded-xl bg-brand-green text-white hover:bg-brand-green-dark transition-colors"
                                        >
                                            Ajukan Pinjaman Baru
                                            <ArrowRight size={16} />
                                        </Link>
                                    </div>
                                ) : (
                                    <div className="bg-amber-500/15 border border-amber-400/30 rounded-2xl p-5">
                                        <p className="text-sm text-amber-200">{alasanTidakBisa}</p>
                                    </div>
                                )}

                                {bisaAjukan && limitTersedia < limitMaksimal && (
                                    <Link
                                        href={route('portal.pengajuan-limit.create')}
                                        className="flex items-center justify-between gap-3 bg-white/5 border border-white/15 rounded-2xl p-4 hover:bg-white/10 transition-colors group"
                                    >
                                        <div className="flex items-center gap-3 min-w-0">
                                            <div className="w-9 h-9 rounded-xl bg-brand-green/20 flex items-center justify-center shrink-0">
                                                <TrendingUp size={18} className="text-brand-green" />
                                            </div>
                                            <div className="min-w-0">
                                                <p className="text-sm font-semibold text-white">Limit Anda kurang?</p>
                                                <p className="text-xs text-slate-300 truncate">
                                                    Saat ini {formatRupiah(limitTersedia)} dari {formatRupiah(limitMaksimal)}
                                                </p>
                                            </div>
                                        </div>
                                        <ArrowRight size={16} className="text-slate-300 group-hover:translate-x-0.5 transition-transform shrink-0" />
                                    </Link>
                                )}
                            </>
                        ) : pengajuanBerjalan ? (
                            <Link href={route('portal.riwayat')} className="block bg-white/10 rounded-2xl p-5 hover:bg-white/[0.15] transition-colors">
                                <div className="flex items-center justify-between mb-3">
                                    <p className="text-sm text-slate-300">Pengajuan Sedang Diproses</p>
                                    <Clock size={18} className="text-amber-400" />
                                </div>
                                <p className="text-2xl font-bold mb-3">{formatRupiah(pengajuanBerjalan.nominal)}</p>
                                <div className="flex items-center gap-2 mb-2">
                                    {[1, 2].map((s) => (
                                        <div key={s} className={`h-1.5 flex-1 rounded-full ${s <= statusPengajuanLabel[pengajuanBerjalan.status].step ? 'bg-amber-400' : 'bg-white/15'}`} />
                                    ))}
                                </div>
                                <p className="text-sm text-slate-300">{statusPengajuanLabel[pengajuanBerjalan.status].text}</p>
                            </Link>
                        ) : (
                            <div className="space-y-3">
                                {pengajuanDitolak && (
                                    <div className="flex items-start gap-3 bg-red-500/15 border border-red-400/30 rounded-2xl p-4">
                                        <XCircle className="text-red-300 shrink-0 mt-0.5" size={18} />
                                        <div>
                                            <p className="text-sm font-semibold text-red-200 mb-0.5">
                                                Pengajuan {formatRupiah(pengajuanDitolak.nominal)} ditolak
                                            </p>
                                            {pengajuanDitolak.catatan && (
                                                <p className="text-sm text-red-200/80">{pengajuanDitolak.catatan}</p>
                                            )}
                                        </div>
                                    </div>
                                )}

                                {bisaAjukan ? (
                                    <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white/10 rounded-2xl p-5">
                                        <div>
                                            <p className="text-base font-semibold mb-1">Belum ada pinjaman aktif</p>
                                            <p className="text-sm text-slate-300">
                                                Limit pengajuan Anda saat ini: {formatRupiah(limitMaksimal)}
                                            </p>
                                        </div>
                                        <Link
                                            href={route('portal.pinjaman.create')}
                                            className="shrink-0 inline-flex items-center justify-center gap-2 px-6 py-3 text-sm font-bold rounded-xl bg-brand-green text-white hover:bg-brand-green-dark transition-colors"
                                        >
                                            Ajukan Pinjaman
                                            <ArrowRight size={16} />
                                        </Link>
                                    </div>
                                ) : (
                                    <div className="bg-amber-500/15 border border-amber-400/30 rounded-2xl p-5">
                                        <p className="text-sm text-amber-200">{alasanTidakBisa}</p>
                                    </div>
                                )}

                                {bisaAjukan && limitTersedia < limitMaksimal && (
                                    <Link
                                        href={route('portal.pengajuan-limit.create')}
                                        className="flex items-center justify-between gap-3 bg-white/5 border border-white/15 rounded-2xl p-4 hover:bg-white/10 transition-colors group"
                                    >
                                        <div className="flex items-center gap-3 min-w-0">
                                            <div className="w-9 h-9 rounded-xl bg-brand-green/20 flex items-center justify-center shrink-0">
                                                <TrendingUp size={18} className="text-brand-green" />
                                            </div>
                                            <div className="min-w-0">
                                                <p className="text-sm font-semibold text-white">Limit Anda kurang?</p>
                                                <p className="text-xs text-slate-300 truncate">
                                                    Saat ini {formatRupiah(limitTersedia)} dari {formatRupiah(limitMaksimal)}
                                                </p>
                                            </div>
                                        </div>
                                        <ArrowRight size={16} className="text-slate-300 group-hover:translate-x-0.5 transition-transform shrink-0" />
                                    </Link>
                                )}
                            </div>
                        )}
                    </div>

                    {/* Widget Keuangan */}
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div className="bg-white rounded-2xl border border-slate-100 p-5">
                            <div className="w-10 h-10 rounded-xl bg-brand-green-light text-brand-green-dark flex items-center justify-center mb-3">
                                <PiggyBank size={20} />
                            </div>
                            <p className="text-sm text-slate-400">Total Simpanan</p>
                            <p className="text-xl font-bold text-slate-800 mt-0.5">{formatRupiah(totalSimpanan)}</p>
                        </div>
                        <div className="bg-white rounded-2xl border border-slate-100 p-5">
                            <div className="w-10 h-10 rounded-xl bg-brand-navy/5 text-brand-navy flex items-center justify-center mb-3">
                                <Wallet size={20} />
                            </div>
                            <p className="text-sm text-slate-400">Simpanan Pokok</p>
                            <p className="text-xl font-bold text-slate-800 mt-0.5">{formatRupiah(simpananPokok)}</p>
                        </div>
                        <div className="bg-white rounded-2xl border border-slate-100 p-5">
                            <div className="w-10 h-10 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center mb-3">
                                <TrendingUp size={20} />
                            </div>
                            <p className="text-sm text-slate-400">Simpanan Wajib</p>
                            <p className="text-xl font-bold text-slate-800 mt-0.5">{formatRupiah(simpananWajib)}</p>
                        </div>
                    </div>

                    {pinjamanAktifCount > 0 && (
                        <div className="bg-white rounded-2xl border border-slate-100 p-5 flex items-center justify-between flex-wrap gap-3">
                            <div>
                                <p className="text-sm text-slate-400">Sisa Total yang Harus Dibayar</p>
                                <p className="text-xl font-bold text-slate-800 mt-0.5">{formatRupiah(aggregateSisaTotalBayar)}</p>
                            </div>
                            <p className="text-sm text-slate-500">
                                {pinjamanAktifCount > 1 ? `${aggregateTotalSisaAngsuran} dari ${aggregateTotalAngsuran} cicilan (gabungan ${pinjamanAktifCount} pinjaman)` : `${pinjamanAktif.sisa_angsuran} dari {pinjamanAktif.total_angsuran} cicilan tersisa`}
                            </p>
                        </div>
                    )}

                    {/* Aktivitas Terbaru */}
                    <div className="bg-white rounded-2xl border border-slate-100 p-5">
                        <div className="flex items-center justify-between mb-4">
                            <p className="text-base font-bold text-slate-700">Aktivitas Terbaru</p>
                            <Link href={route('portal.riwayat')} className="text-sm font-semibold text-brand-green flex items-center gap-1 hover:text-brand-green-dark">
                                Lihat semua
                                <ArrowRight size={14} />
                            </Link>
                        </div>

                        {riwayatGabungan.length === 0 ? (
                            <p className="text-base text-slate-400 text-center py-6">Belum ada aktivitas transaksi.</p>
                        ) : (
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                {riwayatGabungan.map((item, i) => (
                                    <div key={i} className="flex items-center gap-3 p-3 bg-slate-50 rounded-xl">
                                        {item.tipe === 'simpanan' ? (
                                            <div className="w-8 h-8 rounded-full bg-brand-green-light text-brand-green-dark flex items-center justify-center shrink-0">
                                                <ArrowDownCircle size={14} />
                                            </div>
                                        ) : (
                                            <div className="w-8 h-8 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                                                <CheckCircle2 size={14} />
                                            </div>
                                        )}
                                        <div className="flex-1 min-w-0">
                                            <p className="text-sm font-semibold text-slate-700 truncate">{item.label}</p>
                                            <p className="text-xs text-slate-400">{item.tanggal_format}</p>
                                        </div>
                                        <p className="text-sm font-bold text-slate-800 shrink-0">{formatRupiah(item.nominal)}</p>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                </div>

                {/* Kolom kanan - sempit, panel info */}
                <div className="space-y-5">
                    <div className="bg-white rounded-2xl border border-slate-100 p-5">
                        <div className="flex items-center gap-2 mb-2.5">
                            <Info size={16} className="text-brand-navy" />
                            <p className="text-sm font-bold text-slate-700">Tentang Koperasi App</p>
                        </div>
                        <p className="text-sm text-slate-500 leading-relaxed mb-4">
                            Kelola simpanan dan pinjaman Anda dengan mudah. Cicilan dan simpanan wajib dipotong otomatis dari gaji tiap bulan.
                        </p>

                        <div className="flex items-center gap-2 mb-2.5 pt-4 border-t border-slate-100">
                            <ShieldCheck size={16} className="text-brand-green" />
                            <p className="text-sm font-bold text-slate-700">Proses Persetujuan</p>
                        </div>
                        <p className="text-sm text-slate-500 leading-relaxed">
                            Pengajuan ditinjau Bendahara, lalu disetujui final Ketua Koperasi sebelum dana dicairkan.
                        </p>
                    </div>

                    <div className="bg-white rounded-2xl border border-slate-100 p-5">
                        <div className="flex items-center gap-2 mb-2.5">
                            <Percent size={16} className="text-brand-green" />
                            <p className="text-sm font-bold text-slate-700">Tenor Pinjaman</p>
                        </div>
                        <div className="space-y-1.5 mb-4">
                            {tabelTenor.map((t, i) => (
                                <div key={i} className="flex items-center justify-between text-xs">
                                    <span className="text-slate-500">
                                        {formatRupiah(t.nominal_min)}&ndash;{formatRupiah(t.nominal_max)}
                                    </span>
                                    <span className="font-semibold text-slate-700 shrink-0 ml-2">maks {t.tenor_maksimal_bulan} bln</span>
                                </div>
                            ))}
                        </div>

                        <div className="flex items-center gap-2 mb-2.5 pt-4 border-t border-slate-100">
                            <PiggyBank size={16} className="text-amber-600" />
                            <p className="text-sm font-bold text-slate-700">Rincian Simpanan</p>
                        </div>
                        <div className="space-y-1.5">
                            {settingSimpanan.map((s, i) => (
                                <div key={i} className="flex items-center justify-between text-xs">
                                    <span className="text-slate-500">{s.label}</span>
                                    <span className="font-semibold text-slate-700">{formatRupiah(s.nominal)}</span>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        </AnggotaLayout>
    );
}