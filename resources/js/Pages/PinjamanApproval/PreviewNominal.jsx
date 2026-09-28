import { useEffect, useState } from 'react';
import { formatRupiah } from '@/Utils/formatCurrency';

export default function PreviewNominal({ routeName, pinjamanId, nominal, tenorBulan, cicilanLabel = 'cicilan' }) {
    const [preview, setPreview] = useState(null);
    const [gagal, setGagal] = useState('');

    useEffect(() => {
        if (!nominal || Number(nominal) < 1) {
            setPreview(null);
            setGagal('');

            return undefined;
        }

        setGagal('');
        const timeout = setTimeout(async () => {
            try {
                const res = await window.axios.post(route(routeName, pinjamanId), {
                    nominal: Number(nominal),
                    ...(tenorBulan ? { tenor_bulan: Number(tenorBulan) } : {}),
                });
                setPreview(res.data);
            } catch (e) {
                setPreview(null);
                setGagal(e.response?.data?.pesan ?? 'Gagal memuat pratinjau.');
            }
        }, 500);

        return () => clearTimeout(timeout);
    }, [routeName, pinjamanId, nominal, tenorBulan]);

    if (gagal) {
        return <p className="text-sm text-red-600 mt-2">{gagal}</p>;
    }

    if (!preview) return null;

    return (
        <div className="mt-3 rounded-xl bg-slate-50 border border-slate-200 p-4 text-sm">
            <div className="flex items-center justify-between gap-4">
                <span className="text-slate-500">Tenor berlaku (auto-clamp maks {preview.tenor_maksimal} bln)</span>
                <span className="font-bold text-slate-800">{preview.tenor_bulan} bulan</span>
            </div>
            <div className="flex items-center justify-between gap-4 mt-1.5">
                <span className="text-slate-500">Cicilan pertama</span>
                <span className="font-semibold text-slate-800">
                    {preview.cicilan_pertama ? formatRupiah(preview.cicilan_pertama.total_bayar) : '-'}
                </span>
            </div>
            <div className="flex items-center justify-between gap-4 mt-1.5">
                <span className="text-slate-500">Total {preview.total_cicilan} {cicilanLabel}</span>
                <span className="font-bold text-brand-navy">{formatRupiah(preview.total_bayar)}</span>
            </div>
            <div className="flex items-center justify-between gap-4 mt-1.5 pt-1.5 border-t border-slate-200">
                <span className="text-slate-500">Proyeksi sisa kas</span>
                <span className={`font-bold ${preview.kas_sisa < 0 ? 'text-red-600' : 'text-brand-green-dark'}`}>
                    {formatRupiah(preview.kas_sisa)}
                </span>
            </div>
        </div>
    );
}
