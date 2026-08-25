import AnggotaLayout from '@/Layouts/AnggotaLayout';
import { Head, Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import {
    PiggyBank,
    Wallet,
    Gauge,
    ArrowRight,
    CheckCircle2,
    ArrowDownCircle,
    Clock,
    XCircle,
    Info,
    CalendarDays,
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

const focusRing =
    'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-green focus-visible:ring-offset-2';

function StatCard({ icon: Icon, tone, label, value, caption }) {
    const tones = {
        navy: 'bg-brand-navy/5 text-brand-navy',
        green: 'bg-brand-green-light text-brand-green-dark',
        blue: 'bg-blue-50 text-blue-600',
    };

    return (
        <div className="bg-white rounded-xl border border-slate-100 p-4">
            <div className="flex items-center gap-3">
                <div className={`w-9 h-9 rounded-lg flex items-center justify-center shrink-0 ${tones[tone]}`}>
                    <Icon size={18} />
                </div>

                <div className="min-w-0">
                    <p className="text-xs text-slate-500">{label}</p>

                    <p className="text-lg font-bold text-slate-800 leading-tight truncate">
                        {value}
                    </p>
                </div>
            </div>

            {caption && (
                <p className="mt-2.5 text-xs text-slate-400">{caption}</p>
            )}
        </div>
    );
}

function StatusStrip({ dark = false, children }) {
    return (
        <div
            className={`flex items-center gap-2 w-full px-3.5 py-2.5 rounded-lg border ${
                dark
                    ? 'bg-amber-400/15 border-amber-300/30'
                    : 'bg-amber-50 border-amber-200'
            }`}
        >
            <Clock size={14} className={dark ? 'text-amber-300' : 'text-amber-600'} />

            <span className={`text-xs font-semibold ${dark ? 'text-amber-200' : 'text-amber-800'}`}>
                {children}
            </span>
        </div>
    );
}

function KuitansiModal({ judul, rows, paragraf, catatan, onClose }) {
    return (
        <div
            className="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm overflow-y-auto animate-in fade-in duration-200"
            role="dialog"
            aria-modal="true"
            aria-labelledby="konfirmasi-title"
        >
            <div className="min-h-full flex items-center justify-center p-4">
                <div className="bg-white rounded-2xl w-full max-w-md my-8 shadow-2xl overflow-hidden">
                    <div className="px-6 pt-8 pb-6 text-center">
                        <div className="w-16 h-16 rounded-full bg-brand-green-light flex items-center justify-center mx-auto mb-4">
                            <CheckCircle2 size={32} className="text-brand-green" />
                        </div>

                        <h2 id="konfirmasi-title" className="text-xl font-bold text-slate-800">
                            {judul}
                        </h2>
                    </div>

                    <div className="mx-6 border-t-2 border-dashed border-slate-200" />

                    <div className="px-6 py-4 space-y-2.5">
                        {rows.map((r) => (
                            <div key={r.label} className="flex items-start justify-between gap-4 text-sm">
                                <span className="text-slate-500 shrink-0">{r.label}</span>
                                <span className="font-bold text-slate-800 text-right">{r.value}</span>
                            </div>
                        ))}
                    </div>

                    <div className="mx-6 border-t-2 border-dashed border-slate-200" />

                    <div className="px-6 py-5">
                        <p className="text-sm text-slate-600 leading-relaxed">{paragraf}</p>

                        <p className="mt-3 text-xs text-slate-500 leading-relaxed bg-slate-50 border border-slate-100 rounded-lg p-3">
                            {catatan}
                        </p>

                        <button
                            type="button"
                            onClick={onClose}
                            className={`w-full mt-5 py-3 text-sm font-bold rounded-xl bg-brand-navy text-white hover:bg-brand-navy-dark transition-colors ${focusRing}`}
                        >
                            Kembali ke Beranda
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}

const percepatanParagraf = {
    percepat: 'Pengajuan pengurangan lama cicilan Anda telah kami terima dan diteruskan melalui WhatsApp Koperasi untuk diproses lebih lanjut.',
    perpanjang: 'Pengajuan penambahan lama cicilan Anda telah kami terima dan diteruskan melalui WhatsApp Koperasi untuk diproses lebih lanjut.',
    lunas_total: 'Pengajuan pelunasan dipercepat Anda telah kami terima dan diteruskan melalui WhatsApp Koperasi untuk diproses lebih lanjut.',
};

function percepatanRows(p) {
    const rows = [
        { label: 'Pinjaman', value: formatRupiah(p.nominal) },
        { label: 'Tenor saat ini', value: `${p.tenor_lama} bulan` },
    ];

    if (p.tipe === 'lunas_total') {
        rows.push({ label: 'Pelunasan', value: 'Seluruh sisa pokok \u2022 bunga 1 bulan' });
    } else {
        const arah = p.tipe === 'percepat' ? 'Tenor dikurangi' : 'Tenor ditambah';
        rows.push({ label: arah, value: `${p.tenor_lama} \u2192 ${p.tenor_baru} bulan` });
    }

    return rows;
}

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

    const cicilanPerBulan =
        aggregateTotalSisaAngsuran > 0
            ? Math.round(aggregateSisaTotalBayar / aggregateTotalSisaAngsuran)
            : 0;

    const progress =
        aggregateTotalAngsuran > 0
            ? Math.round(
                  ((aggregateTotalAngsuran -
                      aggregateTotalSisaAngsuran) /
                      aggregateTotalAngsuran) *
                      100
              )
            : 0;

    const bisaAjukanLimit = bisaAjukan && !pengajuanLimitBerjalan && limitTersedia > 0;

    const bisaUbahTenor =
        !pengajuanBerjalan &&
        pengajuanPercepatanMenunggu.length === 0 &&
        pinjamanAktifList.some((p) => !p.sudah_pakai_percepatan);

    const { flash } = usePage().props;
    const terkirim = flash.pinjamanTerkirim;
    const percepatanTerkirim = flash.percepatanTerkirim;
    const [konfirmasiDitutup, setKonfirmasiDitutup] = useState(false);
    const [percepatanDitutup, setPercepatanDitutup] = useState(false);

    return (
        <AnggotaLayout>
            <Head title="Beranda" />

            {terkirim && !konfirmasiDitutup && (
                <KuitansiModal
                    judul="Pengajuan Berhasil Terkirim"
                    rows={[
                        { label: 'Nominal Pinjaman', value: formatRupiah(terkirim.nominal) },
                        { label: 'Lama Cicilan', value: `${terkirim.tenor_bulan} bulan` },
                    ]}
                    paragraf="Pengajuan pinjaman Anda telah kami terima dan diteruskan melalui WhatsApp Koperasi untuk diproses lebih lanjut."
                    catatan="Selama masa peninjauan, tidak ada tindakan yang perlu Anda lakukan. Apabila pengajuan disetujui, pemberitahuan akan disampaikan melalui WhatsApp. Status pengajuan juga dapat dipantau pada halaman Beranda."
                    onClose={() => setKonfirmasiDitutup(true)}
                />
            )}

            {percepatanTerkirim && !percepatanDitutup && (
                <KuitansiModal
                    judul="Pengajuan Berhasil Terkirim"
                    rows={percepatanRows(percepatanTerkirim)}
                    paragraf={percepatanParagraf[percepatanTerkirim.tipe]}
                    catatan="Selama masa peninjauan, tidak ada tindakan yang perlu Anda lakukan. Apabila pengajuan disetujui, pemberitahuan beserta bulan mulai berlakunya akan disampaikan melalui WhatsApp. Status pengajuan juga dapat dipantau pada halaman Beranda."
                    onClose={() => setPercepatanDitutup(true)}
                />
            )}

            <div className="space-y-5">

                {/* =====================================================
                    HEADER
                ====================================================== */}
                <div className="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-2">
                    <div>
                        <p className="text-sm text-slate-500">
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

                        <p className="text-xs text-slate-500">
                            Anggota sejak {anggota.lama_keanggotaan_label}
                        </p>
                    </div>
                </div>

                {/* =====================================================
                    RINGKASAN KEUANGAN
                ====================================================== */}
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">

                    <StatCard
                        icon={PiggyBank}
                        tone="green"
                        label="Total Simpanan"
                        value={formatRupiah(totalSimpanan)}
                    />

                    <StatCard
                        icon={Wallet}
                        tone="navy"
                        label="Pinjaman Aktif"
                        value={
                            pinjamanAktifCount > 0
                                ? formatRupiah(aggregateTotalNominal)
                                : 'Rp0'
                        }
                        caption={
                            pinjamanAktifCount > 0
                                ? `${pinjamanAktifCount} pinjaman sedang berjalan`
                                : 'Belum ada pinjaman aktif'
                        }
                    />

                    <StatCard
                        icon={Gauge}
                        tone="blue"
                        label="Limit Tersedia"
                        value={formatRupiah(limitTersedia)}
                        caption={
                            pengajuanLimitBerjalan ? (
                                <span className="inline-flex items-center gap-1.5 text-amber-700 font-medium">
                                    <Clock size={12} />
                                    Pengajuan limit sedang diproses
                                </span>
                            ) : (
                                `dari limit ${formatRupiah(limitMaksimal)}`
                            )
                        }
                    />
                </div>

                {/* =====================================================
                    ANGSURAN BERIKUTNYA (signature)
                ====================================================== */}
                {pinjamanAktifCount > 0 && angsuranBerikutnya && (
                    <Link
                        href={route('portal.riwayat')}
                        className={`relative flex items-stretch bg-brand-green-light rounded-xl group ${focusRing}`}
                    >
                        {/* Lubang tiket kiri-kanan */}
                        <span aria-hidden="true" className="absolute -left-2.5 top-1/2 -translate-y-1/2 w-4 h-4 rounded-full bg-slate-50" />
                        <span aria-hidden="true" className="absolute -right-2.5 top-1/2 -translate-y-1/2 w-4 h-4 rounded-full bg-slate-50" />

                        <div className="flex items-center gap-3.5 flex-1 min-w-0 p-4">
                            <div className="w-10 h-10 rounded-lg bg-white text-brand-green-dark flex items-center justify-center shrink-0 shadow-sm">
                                <CalendarDays size={19} />
                            </div>

                            <div className="min-w-0">
                                <p className="text-xs font-bold uppercase tracking-wide text-brand-green-dark">
                                    Angsuran berikutnya
                                </p>

                                <p className="text-sm font-semibold text-slate-800 truncate mt-0.5">
                                    Angsuran ke-{angsuranBerikutnya.cicilan_ke}
                                    {' \u2022 '}
                                    jatuh tempo {angsuranBerikutnya.tanggal_jatuh_tempo}
                                </p>
                            </div>
                        </div>

                        <div className="flex items-center gap-3 shrink-0 border-l-2 border-dashed border-brand-green/25 bg-white/70 px-4 py-3 my-2 mr-3 rounded-lg sm:my-0 sm:mr-0 sm:py-4 sm:rounded-l-none sm:rounded-r-xl">
                            <div>
                                <p className="text-xs text-slate-500">Total bayar</p>

                                <p className="text-base font-bold text-slate-800 leading-tight whitespace-nowrap">
                                    {formatRupiah(angsuranBerikutnya.total_bayar)}
                                </p>
                            </div>

                            <ArrowRight
                                size={17}
                                className="text-brand-green-dark shrink-0 group-hover:translate-x-0.5 transition-transform motion-reduce:transition-none"
                            />
                        </div>
                    </Link>
                )}

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
                                        <p className="text-xs text-slate-300 mb-1">
                                            Sisa pinjaman
                                            {pinjamanAktifCount > 1
                                                ? ` (${pinjamanAktifCount} pinjaman aktif)`
                                                : ''}
                                        </p>

                                        <p className="text-2xl font-bold">
                                            {formatRupiah(aggregateSisaTotalBayar)}
                                        </p>

                                        <p className="text-xs text-slate-400 mt-1">
                                            dari total {formatRupiah(aggregateTotalNominal)}
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
                                            className="h-full bg-brand-green rounded-full"
                                            style={{
                                                width: `${progress}%`,
                                            }}
                                        />
                                    </div>

                                    <div className="flex items-center justify-between mt-2 text-xs text-slate-300">
                                        <span>
                                            {aggregateTotalAngsuran -
                                                aggregateTotalSisaAngsuran}{' '}
                                            angsuran telah dibayar
                                        </span>

                                        <span>
                                            sisa {aggregateTotalSisaAngsuran}{' '}angsuran
                                        </span>
                                    </div>
                                </div>

                                {/* Ringkasan Pembayaran */}
                                <div className="mt-4 grid grid-cols-2 gap-3">
                                    <div className="bg-white/5 rounded-xl p-3">
                                        <p className="text-xs text-slate-300">
                                            Cicilan per bulan
                                        </p>

                                        <p className="text-sm font-semibold mt-0.5">
                                            {formatRupiah(cicilanPerBulan)}
                                        </p>
                                    </div>

                                    <div className="bg-white/5 rounded-xl p-3">
                                        <p className="text-xs text-slate-300">
                                            Sisa total pembayaran
                                        </p>

                                        <p className="text-sm font-semibold mt-0.5">
                                            {formatRupiah(aggregateSisaTotalBayar)}
                                        </p>
                                    </div>
                                </div>

                                {/* Aksi */}
                                <div className="mt-4 pt-4 border-t border-white/10 space-y-3">
                                    {pengajuanBerjalan ? (
                                        <StatusStrip dark>
                                            Proses pengajuan:{' '}
                                            {statusPengajuanLabel[pengajuanBerjalan.status]?.text ?? 'Menunggu pemeriksaan'}
                                        </StatusStrip>
                                    ) : bisaAjukan ? (
                                        <div className="flex flex-col sm:flex-row gap-2.5">
                                            <Link
                                                href={route('portal.pinjaman.create')}
                                                className={`flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-brand-green text-white text-sm font-bold hover:bg-brand-green-dark transition-colors ${focusRing}`}
                                            >
                                                Ajukan Pinjaman
                                                <ArrowRight size={15} />
                                            </Link>

                                            {bisaAjukanLimit && (
                                                <Link
                                                    href={route('portal.pengajuan-limit.create')}
                                                    className={`inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg border border-white/25 text-white text-sm font-semibold hover:bg-white/10 transition-colors ${focusRing}`}
                                                >
                                                    Ajukan Penambahan Limit
                                                </Link>
                                            )}
                                        </div>
                                    ) : (
                                        <StatusStrip dark>{alasanTidakBisa}</StatusStrip>
                                    )}

                                    {bisaUbahTenor && (
                                        <Link
                                            href={route('portal.percepatan.create')}
                                            className={`inline-flex items-center gap-1.5 text-xs font-semibold text-slate-300 hover:text-white transition-colors rounded ${focusRing}`}
                                        >
                                            <Repeat size={13} />
                                            Ubah Tenor / Lunas Dipercepat
                                        </Link>
                                    )}
                                </div>
                            </div>
                        ) : pengajuanBerjalan ? (
                            <Link
                                href={route('portal.riwayat')}
                                className={`block bg-brand-navy rounded-2xl p-5 text-white hover:bg-brand-navy-light transition-colors ${focusRing}`}
                            >
                                <div className="flex items-center justify-between gap-3">
                                    <div>
                                        <p className="text-xs text-slate-300 mb-1">
                                            Pengajuan Pinjaman
                                        </p>

                                        <p className="text-2xl font-bold">
                                            {formatRupiah(pengajuanBerjalan.nominal)}
                                        </p>
                                    </div>

                                    <Clock size={20} className="text-amber-300" />
                                </div>

                                <div className="flex items-center gap-1.5 mt-4 mb-2.5">
                                    {[1, 2].map((s) => (
                                        <div
                                            key={s}
                                            className={`h-1.5 flex-1 rounded-full ${
                                                s <=
                                                statusPengajuanLabel[pengajuanBerjalan.status].step
                                                    ? 'bg-amber-300'
                                                    : 'bg-white/10'
                                            }`}
                                        />
                                    ))}
                                </div>

                                <p className="text-sm text-slate-300">
                                    {statusPengajuanLabel[pengajuanBerjalan.status].text}
                                </p>
                            </Link>
                        ) : (
                            <div className="bg-brand-navy rounded-2xl p-5 text-white">

                                {pengajuanDitolak && (
                                    <div className="flex items-start gap-3 bg-red-500/15 border border-red-400/20 rounded-xl p-3.5 mb-4">
                                        <XCircle className="text-red-300 shrink-0 mt-0.5" size={17} />

                                        <div>
                                            <p className="text-sm font-semibold text-red-200">
                                                Pengajuan {formatRupiah(pengajuanDitolak.nominal)} ditolak
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
                                            Belum ada pinjaman aktif
                                        </p>

                                        <p className="text-sm text-slate-400 mt-1">
                                            Limit pengajuan Anda {formatRupiah(limitMaksimal)}
                                        </p>
                                    </div>

                                    {bisaAjukan ? (
                                        <div className="flex flex-col sm:flex-row gap-2.5 shrink-0">
                                            <Link
                                                href={route('portal.pinjaman.create')}
                                                className={`inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg bg-brand-green text-white text-sm font-bold hover:bg-brand-green-dark transition-colors ${focusRing}`}
                                            >
                                                Ajukan Pinjaman
                                                <ArrowRight size={15} />
                                            </Link>

                                            {bisaAjukanLimit && (
                                                <Link
                                                    href={route('portal.pengajuan-limit.create')}
                                                    className={`inline-flex items-center justify-center px-4 py-2.5 rounded-lg border border-white/25 text-white text-sm font-semibold hover:bg-white/10 transition-colors ${focusRing}`}
                                                >
                                                    Ajukan Penambahan Limit
                                                </Link>
                                            )}
                                        </div>
                                    ) : (
                                        <div className="bg-white/5 border border-white/10 rounded-lg px-3.5 py-2.5">
                                            <p className="text-xs text-slate-300 leading-relaxed">
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
                                <div className="flex items-start gap-3 bg-amber-50 border border-amber-200 rounded-xl px-4 py-3.5">
                                    <Repeat className="text-amber-600 shrink-0 mt-0.5" size={17} />

                                    <div className="min-w-0">
                                        <p className="text-sm font-semibold text-amber-800">
                                            Pengajuan perubahan tenor / pelunasan dalam proses
                                        </p>

                                        <div className="mt-1 space-y-1">
                                            {pengajuanPercepatanMenunggu.map((pp) => (
                                                <p key={pp.id} className="text-xs text-amber-700">
                                                    {tipeLabel[pp.tipe]} • Pinjaman{' '}
                                                    {formatRupiah(pp.pinjaman_nominal)} •{' '}
                                                    {pp.tenor_lama} → {pp.tenor_baru ?? 'lunas'} bulan •{' '}
                                                    {statusPengajuanLabel[pp.status]?.text ?? pp.status}
                                                </p>
                                            ))}
                                        </div>
                                    </div>
                                </div>
                            )}

                        {/* =================================================
                            INFORMASI HAK PERUBAHAN TENOR
                        ================================================== */}
                        {pinjamanAktifCount > 0 &&
                            pengajuanPercepatanMenunggu.length === 0 &&
                            pinjamanAktifList.some((p) => p.sudah_pakai_percepatan) && (
                                <div className="flex items-start gap-3 bg-slate-50 border border-slate-200 rounded-xl px-4 py-3.5">
                                    <Info className="text-slate-500 shrink-0 mt-0.5" size={17} />

                                    <p className="text-xs text-slate-600 leading-relaxed">
                                        Hak perubahan tenor atau pelunasan dipercepat telah digunakan
                                        pada pinjaman yang masih berjalan. Pengajuan berikutnya dapat
                                        dilakukan setelah pinjaman tersebut dilunasi.
                                    </p>
                                </div>
                            )}

                        {/* =================================================
                            AKTIVITAS TERBARU
                        ================================================== */}
                        <div className="bg-white rounded-xl border border-slate-100 overflow-hidden">
                            <div className="flex items-center justify-between px-4 py-3.5 border-b border-slate-100">
                                <div>
                                    <p className="text-sm font-bold text-slate-700">
                                        Aktivitas Terbaru
                                    </p>

                                    <p className="text-xs text-slate-400 mt-0.5">
                                        Transaksi terakhir Anda
                                    </p>
                                </div>

                                <Link
                                    href={route('portal.riwayat')}
                                    className={`text-xs font-semibold text-brand-green flex items-center gap-1 rounded px-1 py-1 hover:text-brand-green-dark ${focusRing}`}
                                >
                                    Lihat riwayat
                                    <ArrowRight size={13} />
                                </Link>
                            </div>

                            {riwayatGabungan.length === 0 ? (
                                <p className="text-sm text-slate-400 text-center py-8">
                                    Belum ada aktivitas transaksi.
                                </p>
                            ) : (
                                <div className="divide-y divide-slate-100">
                                    {riwayatGabungan.map((item, i) => (
                                        <div key={i} className="flex items-center gap-3 px-4 py-3">
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
                                                <p className="text-sm font-semibold text-slate-700 truncate">
                                                    {item.label}
                                                </p>

                                                <p className="text-xs text-slate-400">
                                                    {item.tanggal_format}
                                                </p>
                                            </div>

                                            <p className="text-sm font-bold text-slate-800 shrink-0">
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
                                <PiggyBank size={16} className="text-brand-green" />

                                <p className="text-sm font-bold text-slate-700">
                                    Rincian Simpanan
                                </p>
                            </div>

                            <div className="space-y-2.5">
                                <div className="flex items-center justify-between">
                                    <span className="text-xs text-slate-500">
                                        Simpanan Pokok
                                    </span>

                                    <span className="text-sm font-semibold text-slate-700">
                                        {formatRupiah(simpananPokok)}
                                    </span>
                                </div>

                                <div className="flex items-center justify-between">
                                    <span className="text-xs text-slate-500">
                                        Simpanan Wajib
                                    </span>

                                    <span className="text-sm font-semibold text-slate-700">
                                        {formatRupiah(simpananWajib)}
                                    </span>
                                </div>

                                <div className="pt-2.5 mt-2.5 border-t border-slate-100 flex items-center justify-between">
                                    <span className="text-sm font-semibold text-slate-600">
                                        Total Simpanan
                                    </span>

                                    <span className="text-base font-bold text-slate-800">
                                        {formatRupiah(totalSimpanan)}
                                    </span>
                                </div>

                                {settingSimpanan.length > 0 && (
                                    <div className="pt-3.5 mt-3 border-t border-slate-100">
                                        <p className="text-xs uppercase tracking-wide font-semibold text-slate-400 mb-2">
                                            Ketentuan Simpanan
                                        </p>

                                        <div className="space-y-1.5">
                                            {settingSimpanan.map((s, i) => (
                                                <div
                                                    key={i}
                                                    className="flex items-center justify-between gap-3 text-xs"
                                                >
                                                    <span className="text-slate-500">
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

                        {/* Ketentuan Pinjaman */}
                        <div className="bg-white rounded-xl border border-slate-100 p-4">
                            <div className="flex items-center gap-2 mb-3">
                                <Percent size={16} className="text-brand-green" />

                                <p className="text-sm font-bold text-slate-700">
                                    Ketentuan Pinjaman
                                </p>
                            </div>

                            <div className="space-y-2">
                                {tabelTenor.map((t, i) => (
                                    <div
                                        key={i}
                                        className="flex items-center justify-between gap-3 text-xs"
                                    >
                                        <span className="text-slate-500">
                                            {formatRupiah(t.nominal_min)}&ndash;{formatRupiah(t.nominal_max)}
                                        </span>

                                        <span className="font-semibold text-slate-600 shrink-0">
                                            Maks. {t.tenor_maksimal_bulan} bulan
                                        </span>
                                    </div>
                                ))}
                            </div>

                            <details className="group mt-3 pt-3 border-t border-slate-100">
                                <summary className="flex items-center gap-2 cursor-pointer select-none list-none [&::-webkit-details-marker]:hidden rounded focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-green focus-visible:ring-offset-2">
                                    <ShieldCheck size={15} className="text-brand-green shrink-0" />

                                    <span className="text-xs font-semibold text-slate-600">
                                        Alur persetujuan pinjaman
                                    </span>

                                    <ArrowRight
                                        size={13}
                                        className="ml-auto text-slate-400 rotate-90 group-open:-rotate-90 transition-transform motion-reduce:transition-none"
                                    />
                                </summary>

                                <p className="text-xs text-slate-500 leading-relaxed mt-2.5">
                                    Pengajuan diperiksa oleh Bendahara dan selanjutnya memperoleh
                                    persetujuan Ketua Koperasi sebelum proses pencairan.
                                    Informasi simpanan, pinjaman, dan angsuran ditampilkan
                                    berdasarkan data administrasi koperasi.
                                </p>
                            </details>
                        </div>
                    </div>
                </div>
            </div>
        </AnggotaLayout>
    );
}
