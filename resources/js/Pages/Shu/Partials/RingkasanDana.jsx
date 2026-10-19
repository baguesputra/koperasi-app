import Card from '@/Components/ui/Card';
import { formatRupiah } from '@/Utils/formatCurrency';

/**
 * Rekap dana ala kolom F sheet Komponen SHU Excel:
 * F7 pool anggota, F11/F14 jasa pengurus/pengawas,
 * F17 pengurus+pengawas, F20 dana dibagikan, F24 dana tak dibagi.
 */
export default function RingkasanDana({ preview, ringkasan }) {
    const k = preview.komponen ?? {};
    const custom = preview.custom ?? [];
    const baris = [
        { label: 'Simpanan + Transaksi untuk Anggota', nilai: preview.pool_anggota ?? 0 },
        { label: 'Jasa Pengurus', nilai: k.pengurus ?? 0 },
        { label: 'Jasa Pengawas', nilai: k.pengawas ?? 0 },
        { label: 'Pengurus + Pengawas', nilai: (k.pengurus ?? 0) + (k.pengawas ?? 0) },
        ...custom.map((c) => ({ label: `Tambahan: ${c.label}`, nilai: c.nominal ?? 0 })),
        { label: 'Dana yang Dibagikan', nilai: preview.dana_dibagi ?? 0, penting: true },
        { label: 'Dana yang Tidak Dibagi (sisanya)', nilai: preview.dana_tak_dibagi ?? 0 },
    ];

    const sisa = ringkasan?.sisa_dana;

    return (
        <Card>
            <p className="text-base font-bold text-slate-800 mb-2">Ringkasan dana</p>
            <dl className="divide-y divide-slate-50">
                {baris.map((b) => (
                    <div key={b.label} className="flex items-baseline justify-between gap-2 py-1.5">
                        <dt className={`text-sm ${b.penting ? 'font-bold text-slate-800' : 'text-slate-500'}`}>{b.label}</dt>
                        <dd className={`text-sm tabular-nums ${b.penting ? 'font-bold text-brand-green-dark' : 'text-slate-700'}`}>
                            {formatRupiah(b.nilai)}
                        </dd>
                    </div>
                ))}
            </dl>

            {sisa && (
                <div className="mt-4 pt-3 border-t border-slate-100">
                    <p className="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">
                        Sisa Dana yang Tidak Diserahkan (Tetap di Kas)
                    </p>
                    <dl className="divide-y divide-slate-50">
                        <div className="flex items-baseline justify-between gap-2 py-1 text-xs">
                            <dt className="text-slate-500">Sisa Pembulatan Anggota (FLOOR 1.000)</dt>
                            <dd className="tabular-nums font-semibold text-slate-700">{formatRupiah(sisa.anggota)}</dd>
                        </div>
                        <div className="flex items-baseline justify-between gap-2 py-1 text-xs">
                            <dt className="text-slate-500">Sisa Alokasi Pengurus</dt>
                            <dd className="tabular-nums font-semibold text-slate-700">{formatRupiah(sisa.pengurus)}</dd>
                        </div>
                        <div className="flex items-baseline justify-between gap-2 py-1 text-xs">
                            <dt className="text-slate-500">Sisa Alokasi Pengawas</dt>
                            <dd className="tabular-nums font-semibold text-slate-700">{formatRupiah(sisa.pengawas)}</dd>
                        </div>
                        <div className="flex items-baseline justify-between gap-2 py-1.5 text-sm font-bold bg-emerald-50/50 px-2 rounded-lg mt-1">
                            <dt className="text-emerald-800">Total Sisa Dana Mengendap di Kas</dt>
                            <dd className="tabular-nums text-emerald-700">{formatRupiah(sisa.total)}</dd>
                        </div>
                    </dl>
                </div>
            )}
        </Card>
    );
}
