import { AlertTriangle, CheckCircle2 } from 'lucide-react';
import Card from '@/Components/ui/Card';
import { formatRupiah } from '@/Utils/formatCurrency';

function formatNilai(nilai, isSummary = false) {
    if (!isSummary && Number(nilai) === 0) {
        return <span className="text-slate-300">-</span>;
    }

    return formatRupiah(nilai);
}

function KolomSisi({ sisi }) {
    return (
        <div className="flex flex-col justify-between bg-white">
            <div className="flex-1">
                <div className="px-5 py-3 border-b border-slate-200/80 flex items-center justify-between gap-2">
                    <h3 className="text-xs font-extrabold uppercase tracking-wider text-[#0F1E36]">
                        {sisi.judul}
                    </h3>
                    <span className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                        Nilai
                    </span>
                </div>

                <div>
                    {sisi.kelompok.map((grup, gi) => (
                        <div key={grup.judul} className={gi > 0 ? 'pt-2' : ''}>
                            <p className="px-5 pt-4 pb-1.5 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                                {grup.judul}
                            </p>
                            <div className="divide-y divide-slate-100">
                                {grup.baris.map((b) => (
                                    <div key={b.label} className="px-5 py-2.5 flex items-baseline justify-between gap-4 hover:bg-[#F2FBF5] transition-colors">
                                        <span className="text-sm text-slate-700">{b.label}</span>
                                        <span className="text-sm font-medium text-slate-800 whitespace-nowrap tabular-nums privacy-blur">
                                            {formatNilai(b.nilai)}
                                        </span>
                                    </div>
                                ))}
                            </div>
                            {grup.subtotal && (
                                <div className="px-5 py-2.5 border-y border-slate-200/75 flex items-baseline justify-between gap-4">
                                    <span className="text-sm font-bold text-slate-800">{grup.subtotal.label}</span>
                                    <span className="text-sm font-bold text-[#0F1E36] whitespace-nowrap tabular-nums privacy-blur">
                                        {formatNilai(grup.subtotal.nilai, true)}
                                    </span>
                                </div>
                            )}
                        </div>
                    ))}
                </div>
            </div>

            <div className="px-5 py-3.5 border-t-2 border-[#0F1E36] bg-white flex items-baseline justify-between gap-4 mt-4 lg:mt-0">
                <span className="text-xs sm:text-sm font-extrabold uppercase tracking-wide text-[#0F1E36]">
                    {sisi.total.label}
                </span>
                <span className="text-base font-extrabold text-[#0F1E36] whitespace-nowrap tabular-nums privacy-blur">
                    {formatNilai(sisi.total.nilai, true)}
                </span>
            </div>
        </div>
    );
}

export default function NeracaSkontro({ skontro, periodeLabel, catatan, memuat = false }) {
    const seimbang = Boolean(skontro.seimbang);
    const totalAktiva = skontro.kiri?.total?.nilai ?? 0;
    const totalPasiva = skontro.kanan?.total?.nilai ?? 0;
    const selisih = skontro.selisih ?? 0;

    return (
        <Card padding="none" className="shadow-md border-slate-200/80 overflow-hidden bg-white">
            {/* Ringkasan & Status Keseimbangan */}
            <div className={`border-b border-slate-100 px-5 py-4 grid grid-cols-1 sm:grid-cols-3 gap-y-3 sm:divide-x sm:divide-slate-100 transition-opacity ${memuat ? 'opacity-50' : ''}`}>
                <div className="sm:pr-4 min-w-0">
                    <p className="text-[11px] font-medium uppercase tracking-wider text-slate-400">Total Aktiva</p>
                    <p className="text-lg sm:text-xl font-bold text-[#0F1E36] tabular-nums mt-0.5 truncate privacy-blur">
                        {formatRupiah(totalAktiva)}
                    </p>
                </div>

                <div className="sm:px-4 min-w-0">
                    <p className="text-[11px] font-medium uppercase tracking-wider text-slate-400">Total Kewajiban &amp; Modal</p>
                    <p className="text-lg sm:text-xl font-bold text-[#0F1E36] tabular-nums mt-0.5 truncate privacy-blur">
                        {formatRupiah(totalPasiva)}
                    </p>
                </div>

                <div className="sm:pl-4 min-w-0 flex flex-col justify-center">
                    <div className="flex items-center justify-between gap-2 flex-wrap">
                        <p className="text-[11px] font-medium uppercase tracking-wider text-slate-400">Status Keseimbangan</p>
                        <span
                            className={`inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold border ${
                                seimbang
                                    ? 'bg-emerald-50 text-emerald-700 border-emerald-200/80'
                                    : 'bg-rose-50 text-rose-700 border-rose-200/80'
                            }`}
                        >
                            {seimbang ? <CheckCircle2 size={12} aria-hidden="true" /> : <AlertTriangle size={12} aria-hidden="true" />}
                            {seimbang ? 'Seimbang' : 'Tidak Seimbang'}
                        </span>
                    </div>
                    <p className="text-xs text-slate-500 mt-1 truncate">
                        Selisih: <strong className={`tabular-nums privacy-blur ${seimbang ? 'text-emerald-700' : 'text-rose-600'}`}>{formatRupiah(selisih)}</strong>
                        {periodeLabel ? ` • ${periodeLabel}` : ''}
                    </p>
                </div>
            </div>

            {/* Tabel Skontro 2 Sisi */}
            <div
                className={`grid grid-cols-1 lg:grid-cols-2 divide-y lg:divide-y-0 lg:divide-x divide-slate-200/80 transition-opacity ${memuat ? 'opacity-50' : ''}`}
                aria-busy={memuat}
            >
                <KolomSisi sisi={skontro.kiri} />
                <KolomSisi sisi={skontro.kanan} />
            </div>

            {catatan && (
                <div className="px-5 py-3 border-t border-slate-100 bg-white">
                    <p className="text-xs italic text-slate-500">{catatan}</p>
                </div>
            )}
        </Card>
    );
}
