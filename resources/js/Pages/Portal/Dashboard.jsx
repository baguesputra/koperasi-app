import AnggotaLayout from '@/Layouts/AnggotaLayout';
import { Head, Link } from '@inertiajs/react';
import {
    PiggyBank,
    Wallet,
    TrendingUp,
    ArrowRight,
    CheckCircle2,
    ArrowDownCircle,
    Clock,
    XCircle,
    Info,
    Calendar,
    ShieldCheck,
    Percent,
    Repeat,
} from 'lucide-react';
import { formatRupiah } from '@/Utils/formatCurrency';

const statusPengajuanLabel = {
    diajukan: {
        text: 'Menunggu pemeriksaan Bendahara',
        step: 1,
    },
    approved_bendahara: {
        text: 'Menunggu persetujuan Ketua Koperasi',
        step: 2,
    },
};

const tipeLabel = {
    perpanjang: 'Perubahan Tenor (Perpanjang)',
    percepat: 'Perubahan Tenor (Percepat)',
    lunas_total: 'Pelunasan Dipercepat',
};

export default function Dashboard({
    anggota,
    totalSimpanan,
    simpananPokok,
    simpananWajib,
    limitMaksimal,
    limitTersedia,
    sisaAngsuranAktif,
    cicilanPokokAktif,
    pinjamanAktif,
    pinjamanAktifList,
    pinjamanAktifCount,
    pengajuanPercepatanMenunggu,
    pengajuanBerjalan,
    pengajuanLimitBerjalan,
    pengajuanDitolak,
    angsuranBerikutnya,
    bisaAjukan,
    alasanTidakBisa,
    riwayatGabungan,
    tabelTenor,
    settingSimpanan,
}) {
    const aggregateTotalNominal = (pinjamanAktifList || []).reduce(
        (sum, p) => sum + (p.nominal || 0),
        0
    );

    const aggregateTotalSisaAngsuran = (pinjamanAktifList || []).reduce(
        (sum, p) => sum + (p.sisa_angsuran || 0),
        0
    );

    const aggregateTotalAngsuran = (pinjamanAktifList || []).reduce(
        (sum, p) => sum + (p.total_angsuran || 0),
        0
    );

    const aggregateSisaTotalBayar = (pinjamanAktifList || []).reduce(
        (sum, p) => sum + (p.sisa_total_bayar || 0),
        0
    );

    const progress =
        aggregateTotalAngsuran > 0
            ? Math.round(
                  ((aggregateTotalAngsuran -
                      aggregateTotalSisaAngsuran) /
                      aggregateTotalAngsuran) *
                      100
              )
            : 0;

    return (
        <AnggotaLayout>
            <Head title="Beranda" />

            <div className="space-y-5">

                {/* =====================================================
                    HEADER
                ====================================================== */}
                <div className="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-2">
                    <div>
                        <p className="text-sm text-slate-400">
                            Selamat datang,
                        </p>

                        <h1 className="text-2xl font-bold text-slate-800">
                            {anggota.nama.split(' ')[0]}
                        </h1>
                    </div>

                    <div className="text-left sm:text-right">
                        <p className="text-sm font-semibold text-slate-700">
                            {anggota.no_anggota}
                        </p>

                        <p className="text-xs text-slate-400">
                            Anggota sejak {anggota.lama_keanggotaan_label}
                        </p>
                    </div>
                </div>

                {/* =====================================================
                    RINGKASAN KEUANGAN
                ====================================================== */}
                <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">

                    {/* Total Simpanan */}
                    <div className="bg-white rounded-xl border border-slate-100 p-4">
                        <div className="flex items-center gap-3">
                            <div className="w-9 h-9 rounded-lg bg-brand-green-light text-brand-green-dark flex items-center justify-center shrink-0">
                                <PiggyBank size={18} />
                            </div>

                            <div className="min-w-0">
                                <p className="text-xs text-slate-400">
                                    Total Simpanan
                                </p>

                                <p className="text-base sm:text-lg font-bold text-slate-800 truncate">
                                    {formatRupiah(totalSimpanan)}
                                </p>
                            </div>
                        </div>

                        <div className="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between text-[11px]">
                            <span className="text-slate-400">
                                Simpanan Pokok
                            </span>

                            <span className="font-semibold text-slate-600">
                                {formatRupiah(simpananPokok)}
                            </span>
                        </div>
                    </div>

                    {/* Pinjaman */}
                    <div className="bg-white rounded-xl border border-slate-100 p-4">
                        <div className="flex items-center gap-3">
                            <div className="w-9 h-9 rounded-lg bg-brand-navy/5 text-brand-navy flex items-center justify-center shrink-0">
                                <Wallet size={18} />
                            </div>

                            <div className="min-w-0">
                                <p className="text-xs text-slate-400">
                                    Pinjaman Aktif
                                </p>

                                <p className="text-base sm:text-lg font-bold text-slate-800 truncate">
                                    {pinjamanAktifCount > 0
                                        ? formatRupiah(
                                              aggregateTotalNominal
                                          )
                                        : 'Rp0'}
                                </p>
                            </div>
                        </div>

                        <div className="mt-3 pt-3 border-t border-slate-100">
                            <p className="text-[11px] text-slate-400">
                                {pinjamanAktifCount > 0
                                    ? `${pinjamanAktifCount} pinjaman aktif`
                                    : 'Tidak terdapat pinjaman aktif'}
                            </p>
                        </div>
                    </div>

                    {/* Sisa Pembayaran */}
                    <div className="bg-white rounded-xl border border-slate-100 p-4">
                        <div className="flex items-center gap-3">
                            <div className="w-9 h-9 rounded-lg bg-amber-50 text-amber-700 flex items-center justify-center shrink-0">
                                <TrendingUp size={18} />
                            </div>

                            <div className="min-w-0">
                                <p className="text-xs text-slate-400">
                                    Sisa Pembayaran
                                </p>

                                <p className="text-base sm:text-lg font-bold text-slate-800 truncate">
                                    {pinjamanAktifCount > 0
                                        ? formatRupiah(
                                              aggregateSisaTotalBayar
                                          )
                                        : 'Rp0'}
                                </p>
                            </div>
                        </div>

                        <div className="mt-3 pt-3 border-t border-slate-100">
                            <p className="text-[11px] text-slate-400">
                                {pinjamanAktifCount > 0
                                    ? `${aggregateTotalSisaAngsuran} dari ${aggregateTotalAngsuran} angsuran`
                                    : 'Tidak terdapat angsuran aktif'}
                            </p>
                        </div>
                    </div>

                    {/* Limit */}
                    <div className="bg-white rounded-xl border border-slate-100 p-4">
                        <div className="flex items-center gap-3">
                            <div className="w-9 h-9 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                                <TrendingUp size={18} />
                            </div>

                            <div className="min-w-0">
                                <p className="text-xs text-slate-400">
                                    Limit Tersedia
                                </p>

                                <p className="text-base sm:text-lg font-bold text-slate-800 truncate">
                                    {formatRupiah(limitTersedia)}
                                </p>
                            </div>
                        </div>

                        <div className="mt-3 pt-3 border-t border-slate-100">
                            <p className="text-[11px] text-slate-400">
                                dari limit{' '}
                                {formatRupiah(limitMaksimal)}
                            </p>

                            {pengajuanLimitBerjalan ? (
                                <div className="mt-3 flex items-center gap-2 w-full px-3 py-2 rounded-lg bg-amber-50 border border-amber-200">
                                    <Clock size={13} className="text-amber-600" />
                                    <span className="text-[11px] font-semibold text-amber-800">
                                        Proses Pengajuan: Menunggu persetujuan Ketua Koperasi
                                    </span>
                                </div>
                            ) : bisaAjukan && limitTersedia > 0 && (
                                <Link
                                    href={route(
                                        'portal.pengajuan-limit.create'
                                    )}
                                    className="mt-3 flex items-center justify-center gap-1.5 w-full px-3 py-2 rounded-lg bg-brand-green text-white text-[11px] font-bold hover:bg-brand-green-dark transition-colors"
                                >
                                    Ajukan Penambahan Limit
                                    <ArrowRight size={13} />
                                </Link>
                            )}
                        </div>
                    </div>
                </div>

                {/* =====================================================
                    KONTEN UTAMA
                ====================================================== */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-5">

                    {/* =================================================
                        KOLOM UTAMA
                    ================================================== */}
                    <div className="lg:col-span-2 space-y-5">

                        {/* =================================================
                            PINJAMAN / PENGAJUAN
                        ================================================== */}
                        {pinjamanAktifCount > 0 ? (
                            <div className="bg-brand-navy rounded-2xl p-5 text-white">

                                {/* Header */}
                                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                    <div>
                                        <p className="text-xs text-slate-400 mb-1">
                                            {pinjamanAktifCount > 1
                                                ? `${pinjamanAktifCount} Pinjaman Aktif`
                                                : 'Pinjaman Aktif'}
                                        </p>

                                        <p className="text-2xl font-bold">
                                            {formatRupiah(
                                                aggregateTotalNominal
                                            )}
                                        </p>
                                    </div>

                                    <span className="inline-flex self-start sm:self-auto items-center px-2.5 py-1 rounded-full bg-brand-green text-white text-xs font-semibold">
                                        {progress}% lunas
                                    </span>
                                </div>

                                {/* Progress */}
                                <div className="mt-4">
                                    <div className="w-full h-2 bg-white/10 rounded-full overflow-hidden">
                                        <div
                                            className="h-full bg-brand-green rounded-full transition-all"
                                            style={{
                                                width: `${progress}%`,
                                            }}
                                        />
                                    </div>

                                    <div className="flex items-center justify-between mt-2 text-[11px] text-slate-400">
                                        <span>
                                            {aggregateTotalAngsuran -
                                                aggregateTotalSisaAngsuran}{' '}
                                            angsuran telah dibayar
                                        </span>

                                        <span>
                                            {aggregateTotalSisaAngsuran}{' '}
                                            tersisa
                                        </span>
                                    </div>
                                </div>

                                {/* Angsuran Berikutnya */}
                                {angsuranBerikutnya && (
                                    <Link
                                        href={route('portal.riwayat')}
                                        className="mt-4 flex items-center justify-between gap-4 bg-white/10 hover:bg-white/15 rounded-xl p-3.5 transition-colors group"
                                    >
                                        <div className="flex items-center gap-3 min-w-0">
                                            <div className="w-9 h-9 rounded-lg bg-white/10 flex items-center justify-center shrink-0">
                                                <Calendar size={17} />
                                            </div>

                                            <div className="min-w-0">
                                                <p className="text-[11px] text-slate-400">
                                                    Angsuran berikutnya
                                                </p>

                                                <p className="text-sm font-semibold text-white truncate">
                                                    Angsuran ke-
                                                    {
                                                        angsuranBerikutnya.cicilan_ke
                                                    }{' '}
                                                    •{' '}
                                                    {
                                                        angsuranBerikutnya.tanggal_jatuh_tempo
                                                    }
                                                </p>
                                            </div>
                                        </div>

                                        <div className="text-right shrink-0">
                                            <p className="text-sm font-bold">
                                                {formatRupiah(
                                                    angsuranBerikutnya.total_bayar
                                                )}
                                            </p>

                                            <ArrowRight
                                                size={14}
                                                className="ml-auto mt-1 text-slate-400 group-hover:translate-x-0.5 transition-transform"
                                            />
                                        </div>
                                    </Link>
                                )}

                                {/* Ringkasan Pembayaran */}
                                <div className="mt-4 grid grid-cols-2 gap-3">
                                    <div className="bg-white/5 rounded-xl p-3">
                                        <p className="text-[10px] text-slate-400">
                                            Sisa Angsuran
                                        </p>

                                        <p className="text-sm font-semibold mt-0.5">
                                            {aggregateTotalSisaAngsuran}{' '}
                                            angsuran
                                        </p>
                                    </div>

                                    <div className="bg-white/5 rounded-xl p-3">
                                        <p className="text-[10px] text-slate-400">
                                            Sisa Total Pembayaran
                                        </p>

                                        <p className="text-sm font-semibold mt-0.5">
                                            {formatRupiah(
                                                aggregateSisaTotalBayar
                                            )}
                                        </p>
                                    </div>
                                </div>

                                {/* =================================================
                                    SATU-SATUNYA TOMBOL AJUKAN PINJAMAN
                                ================================================== */}
                                {pengajuanBerjalan ? (
                                    <div className="mt-4 pt-4 border-t border-white/10">
                                        <div className="flex items-center gap-2 w-full px-4 py-2.5 rounded-lg bg-amber-50 border border-amber-200">
                                            <Clock size={14} className="text-amber-600" />
                                            <span className="text-xs font-semibold text-amber-800">
                                                Proses Pengajuan: {statusPengajuanLabel[pengajuanBerjalan.status]?.text ?? 'Menunggu pemeriksaan'}
                                            </span>
                                        </div>
                                    </div>
                                ) : bisaAjukan && (
                                    <div className="mt-4 pt-4 border-t border-white/10">
                                        <Link
                                            href={route(
                                                'portal.pinjaman.create'
                                            )}
                                            className="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-brand-green text-white text-xs font-bold hover:bg-brand-green-dark transition-colors"
                                        >
                                            Ajukan Pinjaman
                                            <ArrowRight size={14} />
                                        </Link>
                                    </div>
                                )}
                            </div>
                        ) : pengajuanBerjalan ? (
                            <Link
                                href={route('portal.riwayat')}
                                className="block bg-brand-navy rounded-2xl p-5 text-white hover:bg-brand-navy/95 transition-colors"
                            >
                                <div className="flex items-center justify-between gap-3">
                                    <div>
                                        <p className="text-xs text-slate-400 mb-1">
                                            Pengajuan Pinjaman
                                        </p>

                                        <p className="text-2xl font-bold">
                                            {formatRupiah(
                                                pengajuanBerjalan.nominal
                                            )}
                                        </p>
                                    </div>

                                    <Clock
                                        size={20}
                                        className="text-amber-400"
                                    />
                                </div>

                                <div className="flex items-center gap-1.5 mt-4 mb-2">
                                    {[1, 2].map((s) => (
                                        <div
                                            key={s}
                                            className={`h-1.5 flex-1 rounded-full ${
                                                s <=
                                                statusPengajuanLabel[
                                                    pengajuanBerjalan.status
                                                ].step
                                                    ? 'bg-amber-400'
                                                    : 'bg-white/10'
                                            }`}
                                        />
                                    ))}
                                </div>

                                <p className="text-xs text-slate-300">
                                    {
                                        statusPengajuanLabel[
                                            pengajuanBerjalan.status
                                        ].text
                                    }
                                </p>
                            </Link>
                        ) : (
                            <div className="bg-brand-navy rounded-2xl p-5 text-white">

                                {pengajuanDitolak && (
                                    <div className="flex items-start gap-3 bg-red-500/15 border border-red-400/20 rounded-xl p-3.5 mb-4">
                                        <XCircle
                                            className="text-red-300 shrink-0 mt-0.5"
                                            size={17}
                                        />

                                        <div>
                                            <p className="text-xs font-semibold text-red-200">
                                                Pengajuan{' '}
                                                {formatRupiah(
                                                    pengajuanDitolak.nominal
                                                )}{' '}
                                                ditolak
                                            </p>

                                            {pengajuanDitolak.catatan && (
                                                <p className="text-xs text-red-200/70 mt-1">
                                                    {pengajuanDitolak.catatan}
                                                </p>
                                            )}
                                        </div>
                                    </div>
                                )}

                                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                    <div>
                                        <p className="text-lg font-bold">
                                            Tidak terdapat pinjaman aktif
                                        </p>

                                        <p className="text-xs text-slate-400 mt-1">
                                            Limit pengajuan saat ini{' '}
                                            {formatRupiah(limitMaksimal)}
                                        </p>
                                    </div>

                                    {pengajuanBerjalan ? (
                                        <div className="flex items-center gap-2 px-4 py-2.5 rounded-lg bg-amber-50 border border-amber-200 shrink-0">
                                            <Clock size={14} className="text-amber-600" />
                                            <span className="text-xs font-semibold text-amber-800">
                                                Proses Pengajuan: {statusPengajuanLabel[pengajuanBerjalan.status]?.text ?? 'Menunggu pemeriksaan'}
                                            </span>
                                        </div>
                                    ) : bisaAjukan ? (
                                        <Link
                                            href={route(
                                                'portal.pinjaman.create'
                                            )}
                                            className="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg bg-brand-green text-white text-xs font-bold hover:bg-brand-green-dark transition-colors shrink-0"
                                        >
                                            Ajukan Pinjaman
                                            <ArrowRight size={14} />
                                        </Link>
                                    ) : (
                                        <div className="bg-amber-500/10 border border-amber-400/20 rounded-lg px-3 py-2">
                                            <p className="text-xs text-amber-200">
                                                {alasanTidakBisa}
                                            </p>
                                        </div>
                                    )}
                                </div>
                            </div>
                        )}

                        {/* =================================================
                            PENGAJUAN PERUBAHAN TENOR
                        ================================================== */}
                        {pinjamanAktifCount > 0 &&
                            pengajuanPercepatanMenunggu.length > 0 && (
                                <div className="flex items-start gap-3 bg-amber-50 border border-amber-200 rounded-xl px-4 py-3">
                                    <Repeat
                                        className="text-amber-600 shrink-0 mt-0.5"
                                        size={17}
                                    />

                                    <div className="min-w-0">
                                        <p className="text-xs font-semibold text-amber-800">
                                            Pengajuan perubahan tenor /
                                            pelunasan dalam proses
                                        </p>

                                        <div className="mt-1 space-y-0.5">
                                            {pengajuanPercepatanMenunggu.map(
                                                (pp) => (
                                                    <p
                                                        key={pp.id}
                                                        className="text-[11px] text-amber-700"
                                                    >
                                                        {tipeLabel[pp.tipe]} •
                                                        Pinjaman{' '}
                                                        {formatRupiah(
                                                            pp.pinjaman_nominal
                                                        )}{' '}
                                                        • {pp.tenor_lama} →{' '}
                                                        {pp.tenor_baru ??
                                                            'lunas'} bulan •{' '}
                                                        {statusPengajuanLabel[
                                                            pp.status
                                                        ]?.text ?? pp.status}
                                                    </p>
                                                )
                                            )}
                                        </div>
                                    </div>
                                </div>
                            )}

                        {/* =================================================
                            INFORMASI HAK PERUBAHAN TENOR
                        ================================================== */}
                        {pinjamanAktifCount > 0 &&
                            pengajuanPercepatanMenunggu.length === 0 &&
                            pinjamanAktifList.some(
                                (p) => p.sudah_pakai_percepatan
                            ) && (
                                <div className="flex items-start gap-3 bg-slate-50 border border-slate-200 rounded-xl px-4 py-3">
                                    <Info
                                        className="text-slate-500 shrink-0 mt-0.5"
                                        size={17}
                                    />

                                    <p className="text-xs text-slate-600 leading-relaxed">
                                        Hak perubahan tenor atau pelunasan
                                        dipercepat telah digunakan pada
                                        pinjaman yang masih berjalan.
                                        Pengajuan berikutnya dapat dilakukan
                                        setelah pinjaman tersebut dilunasi.
                                    </p>
                                </div>
                            )}

                        {/* =================================================
                            AKTIVITAS TERBARU
                        ================================================== */}
                        <div className="bg-white rounded-xl border border-slate-100 overflow-hidden">
                            <div className="flex items-center justify-between px-4 py-3 border-b border-slate-100">
                                <div>
                                    <p className="text-sm font-bold text-slate-700">
                                        Aktivitas Terbaru
                                    </p>

                                    <p className="text-[11px] text-slate-400 mt-0.5">
                                        Riwayat transaksi terakhir
                                    </p>
                                </div>

                                <Link
                                    href={route('portal.riwayat')}
                                    className="text-xs font-semibold text-brand-green flex items-center gap-1 hover:text-brand-green-dark"
                                >
                                    Lihat riwayat
                                    <ArrowRight size={13} />
                                </Link>
                            </div>

                            {riwayatGabungan.length === 0 ? (
                                <p className="text-sm text-slate-400 text-center py-8">
                                    Belum terdapat aktivitas transaksi.
                                </p>
                            ) : (
                                <div className="divide-y divide-slate-100">
                                    {riwayatGabungan.map((item, i) => (
                                        <div
                                            key={i}
                                            className="flex items-center gap-3 px-4 py-3"
                                        >
                                            {item.tipe === 'simpanan' ? (
                                                <div className="w-8 h-8 rounded-full bg-brand-green-light text-brand-green-dark flex items-center justify-center shrink-0">
                                                    <ArrowDownCircle
                                                        size={14}
                                                    />
                                                </div>
                                            ) : (
                                                <div className="w-8 h-8 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                                                    <CheckCircle2 size={14} />
                                                </div>
                                            )}

                                            <div className="flex-1 min-w-0">
                                                <p className="text-xs sm:text-sm font-semibold text-slate-700 truncate">
                                                    {item.label}
                                                </p>

                                                <p className="text-[11px] text-slate-400">
                                                    {item.tanggal_format}
                                                </p>
                                            </div>

                                            <p className="text-xs sm:text-sm font-bold text-slate-800 shrink-0">
                                                {formatRupiah(item.nominal)}
                                            </p>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>

                    {/* =====================================================
                        SIDEBAR
                    ====================================================== */}
                    <div className="space-y-5">

                        {/* Rincian Simpanan */}
                        <div className="bg-white rounded-xl border border-slate-100 p-4">
                            <div className="flex items-center gap-2 mb-3">
                                <PiggyBank
                                    size={16}
                                    className="text-brand-green"
                                />

                                <p className="text-sm font-bold text-slate-700">
                                    Rincian Simpanan
                                </p>
                            </div>

                            <div className="space-y-2">
                                <div className="flex items-center justify-between">
                                    <span className="text-xs text-slate-400">
                                        Simpanan Pokok
                                    </span>

                                    <span className="text-xs font-semibold text-slate-700">
                                        {formatRupiah(simpananPokok)}
                                    </span>
                                </div>

                                <div className="flex items-center justify-between">
                                    <span className="text-xs text-slate-400">
                                        Simpanan Wajib
                                    </span>

                                    <span className="text-xs font-semibold text-slate-700">
                                        {formatRupiah(simpananWajib)}
                                    </span>
                                </div>

                                <div className="pt-2 mt-1 border-t border-slate-100 flex items-center justify-between">
                                    <span className="text-xs font-semibold text-slate-600">
                                        Total Simpanan
                                    </span>

                                    <span className="text-sm font-bold text-slate-800">
                                        {formatRupiah(totalSimpanan)}
                                    </span>
                                </div>

                                {settingSimpanan.length > 0 && (
                                    <div className="pt-3 mt-2 border-t border-slate-100">
                                        <p className="text-[10px] uppercase tracking-wide font-semibold text-slate-400 mb-2">
                                            Ketentuan Simpanan
                                        </p>

                                        <div className="space-y-1.5">
                                            {settingSimpanan.map((s, i) => (
                                                <div
                                                    key={i}
                                                    className="flex items-center justify-between gap-3 text-[11px]"
                                                >
                                                    <span className="text-slate-400">
                                                        {s.label}
                                                    </span>

                                                    <span className="font-semibold text-slate-600">
                                                        {formatRupiah(s.nominal)}
                                                    </span>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Ketentuan Tenor */}
                        <div className="bg-white rounded-xl border border-slate-100 p-4">
                            <div className="flex items-center gap-2 mb-3">
                                <Percent
                                    size={16}
                                    className="text-brand-green"
                                />

                                <p className="text-sm font-bold text-slate-700">
                                    Ketentuan Tenor
                                </p>
                            </div>

                            <div className="space-y-2">
                                {tabelTenor.map((t, i) => (
                                    <div
                                        key={i}
                                        className="flex items-center justify-between gap-3 text-[11px]"
                                    >
                                        <span className="text-slate-400">
                                            {formatRupiah(t.nominal_min)}
                                            &ndash;
                                            {formatRupiah(t.nominal_max)}
                                        </span>

                                        <span className="font-semibold text-slate-600 shrink-0">
                                            Maks. {t.tenor_maksimal_bulan}{' '}
                                            bulan
                                        </span>
                                    </div>
                                ))}
                            </div>
                        </div>

                        {/* Informasi */}
                        <div className="bg-slate-50 rounded-xl border border-slate-100 p-4">
                            <div className="flex items-center gap-2 mb-2">
                                <Info
                                    size={15}
                                    className="text-brand-navy"
                                />

                                <p className="text-xs font-bold text-slate-700">
                                    Informasi
                                </p>
                            </div>

                            <p className="text-[11px] text-slate-500 leading-relaxed">
                                Informasi simpanan, pinjaman, dan angsuran
                                ditampilkan berdasarkan data administrasi
                                koperasi.
                            </p>

                            <div className="flex items-start gap-2 mt-3 pt-3 border-t border-slate-200">
                                <ShieldCheck
                                    size={15}
                                    className="text-brand-green shrink-0 mt-0.5"
                                />

                                <div>
                                    <p className="text-[11px] font-semibold text-slate-600 mb-0.5">
                                        Proses Persetujuan
                                    </p>

                                    <p className="text-[11px] text-slate-500 leading-relaxed">
                                        Pengajuan diperiksa oleh Bendahara dan
                                        selanjutnya memperoleh persetujuan
                                        Ketua Koperasi sebelum proses
                                        pencairan.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AnggotaLayout>
    );
}