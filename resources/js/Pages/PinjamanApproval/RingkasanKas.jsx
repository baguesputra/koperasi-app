import { AlertTriangle, Landmark, ShieldCheck } from 'lucide-react';
import PrivacyWrapper from '@/Components/ui/PrivacyWrapper';
import { formatRupiah } from '@/Utils/formatCurrency';

const PIPELINE_STAGES = [
    { key: 'admin', label: 'Sekretaris' },
    { key: 'bendahara', label: 'Bendahara' },
    { key: 'ketua', label: 'Ketua' },
    { key: 'pencairan', label: 'Pencairan' },
];

/**
 * Ringkasan posisi beban approval & kapasitas kas bank dalam satu kartu
 * 4 kolom terpadu agar mudah dipindai dari kiri ke kanan tanpa memakan
 * ruang vertikal di atas tabel approval.
 */
export default function RingkasanKas({ ringkasan }) {
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
        <section
            aria-label="Ringkasan beban persetujuan pinjaman"
            className="bg-white rounded-2xl border border-slate-200/80 shadow-[0_2px_10px_-3px_rgba(15,30,54,0.06),0_1px_3px_rgba(15,30,54,0.04)] mb-4 overflow-hidden"
        >
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">
                {/* 1. Antrean Anda */}
                <div className="px-4 py-3 sm:px-4.5 sm:py-3.5 bg-gradient-to-br from-brand-navy/[0.03] to-brand-green-light/20 border-b lg:border-b-0 sm:border-r border-slate-200/75 flex flex-col justify-between">
                    <div className="flex items-center justify-between gap-2">
                        <span className="text-xs font-bold text-brand-navy truncate">
                            {beban_peran?.label ?? 'Antrean Anda'}
                        </span>
                        <span className="inline-flex items-center px-2 py-0.5 rounded-full bg-brand-navy text-white text-[11px] font-bold tabular-nums shrink-0">
                            {beban_peran?.jumlah ?? 0} pengajuan
                        </span>
                    </div>
                    <p className="text-lg sm:text-xl font-extrabold text-slate-900 tabular-nums mt-1.5 tracking-tight">
                        <PrivacyWrapper>{formatRupiah(beban_peran?.total ?? 0)}</PrivacyWrapper>
                    </p>
                    <p className="text-xs text-slate-500 mt-1 truncate">
                        {beban_peran?.tahap ?? 'Menunggu tindakan Anda'}
                        {Boolean(beban_peran?.cair_mandiri_jumlah) && (
                            <span className="text-amber-700 font-semibold">
                                {' '}• +{beban_peran.cair_mandiri_jumlah} mandiri (
                                <PrivacyWrapper>{formatRupiah(beban_peran.cair_mandiri_total)}</PrivacyWrapper>)
                            </span>
                        )}
                    </p>
                </div>

                {/* 2. Saldo Bank Koperasi */}
                <div className="px-4 py-3 sm:px-4.5 sm:py-3.5 border-b lg:border-b-0 lg:border-r border-slate-200/75 flex flex-col justify-between">
                    <div className="flex items-center justify-between gap-2">
                        <span className="text-xs font-semibold text-slate-500 truncate">
                            Saldo Bank Koperasi
                        </span>
                        <Landmark size={15} className="text-slate-400 shrink-0" aria-hidden="true" />
                    </div>
                    <p className="text-lg sm:text-xl font-bold text-slate-800 tabular-nums mt-1.5 tracking-tight">
                        <PrivacyWrapper>{formatRupiah(saldo_bank)}</PrivacyWrapper>
                    </p>
                    <p className="text-xs text-slate-500 mt-1 truncate">Kas bank siap dicairkan</p>
                </div>

                {/* 3. Total Antrean Berjalan */}
                <div className="px-4 py-3 sm:px-4.5 sm:py-3.5 border-b sm:border-b-0 sm:border-r border-slate-200/75 flex flex-col justify-between">
                    <div className="flex items-center justify-between gap-2">
                        <span className="text-xs font-semibold text-slate-500 truncate">
                            Total Antrean Berjalan
                        </span>
                        <span className="inline-flex items-center px-2 py-0.5 rounded-full bg-amber-50 text-amber-800 border border-amber-200/80 text-[11px] font-bold tabular-nums shrink-0">
                            {jumlah_antrean} total
                        </span>
                    </div>
                    <p className="text-lg sm:text-xl font-bold text-slate-800 tabular-nums mt-1.5 tracking-tight">
                        <PrivacyWrapper>{formatRupiah(total_antrean)}</PrivacyWrapper>
                    </p>
                    {pipeline ? (
                        <div className="flex flex-wrap items-center gap-1.5 mt-1.5">
                            {PIPELINE_STAGES.map((stage) => {
                                const aktif = peran === stage.key;
                                const jumlah = pipeline[stage.key]?.jumlah ?? 0;
                                return (
                                    <span
                                        key={stage.key}
                                        className={`inline-flex items-center gap-1 text-[11px] rounded px-1.5 py-0.5 tabular-nums ${
                                            aktif
                                                ? 'bg-brand-green-light text-brand-green-dark font-bold'
                                                : 'bg-slate-100 text-slate-600 font-medium'
                                        }`}
                                    >
                                        <span>{stage.label}</span>
                                        <span className={aktif ? 'font-extrabold' : 'font-semibold text-slate-700'}>
                                            {jumlah}
                                        </span>
                                    </span>
                                );
                            })}
                        </div>
                    ) : (
                        <p className="text-xs text-slate-500 mt-1 truncate">Di seluruh tahap persetujuan</p>
                    )}
                </div>

                {/* 4. Proyeksi Sisa Bank */}
                <div
                    className={`px-4 py-3 sm:px-4.5 sm:py-3.5 flex flex-col justify-between ${
                        defisit ? 'bg-rose-50/35' : 'bg-emerald-50/30'
                    }`}
                >
                    <div className="flex items-center justify-between gap-2">
                        <span className={`text-xs font-semibold truncate ${defisit ? 'text-rose-800' : 'text-emerald-800'}`}>
                            Proyeksi Sisa Bank
                        </span>
                        <span
                            className={`inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold border shrink-0 ${
                                defisit
                                    ? 'bg-rose-100/80 text-rose-700 border-rose-200/80'
                                    : 'bg-brand-green-light text-brand-green-dark border-emerald-200/80'
                            }`}
                        >
                            {defisit ? (
                                <AlertTriangle size={12} aria-hidden="true" />
                            ) : (
                                <ShieldCheck size={12} aria-hidden="true" />
                            )}
                            <span>{defisit ? 'Defisit' : 'Aman'}</span>
                        </span>
                    </div>
                    <p
                        className={`text-lg sm:text-xl font-extrabold tabular-nums mt-1.5 tracking-tight ${
                            defisit ? 'text-rose-700' : 'text-brand-green-dark'
                        }`}
                    >
                        <PrivacyWrapper>{formatRupiah(sisa_proyeksi)}</PrivacyWrapper>
                    </p>
                    <p className={`text-xs mt-1 truncate ${defisit ? 'text-rose-700' : 'text-emerald-800/80'}`}>
                        {defisit ? 'Saldo kurang jika semua cair' : 'Estimasi sisa setelah cair'}
                    </p>
                </div>
            </div>
        </section>
    );
}
