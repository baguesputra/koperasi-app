import Card from '@/Components/ui/Card';
import { formatRupiah } from '@/Utils/formatCurrency';

/**
 * Rekap dana ala kolom F sheet Komponen SHU Excel:
 * F7 pool anggota, F11/F14 jasa pengurus/pengawas,
 * F17 pengurus+pengawas, F20 dana dibagikan, F24 dana tak dibagi.
 */
export default function RingkasanDana({ preview }) {
    const k = preview.komponen ?? {};
    const baris = [
        { label: 'Pool anggota (simpanan + transaksi)', nilai: preview.pool_anggota ?? 0 },
        { label: 'Jasa pengurus', nilai: k.pengurus ?? 0 },
        { label: 'Jasa pengawas', nilai: k.pengawas ?? 0 },
        { label: 'Pengurus + pengawas', nilai: (k.pengurus ?? 0) + (k.pengawas ?? 0) },
        { label: 'Dana yang dibagikan', nilai: preview.dana_dibagi ?? 0, penting: true },
        { label: 'Dana tak dibagi (cadangan + pendidikan + wilker)', nilai: preview.dana_tak_dibagi ?? 0 },
    ];

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
        </Card>
    );
}
