import { CheckCircle2, Clock, XCircle, AlertCircle } from 'lucide-react';

const statusConfig = {
    disetujui: { label: 'Disetujui', color: 'bg-brand-green-light text-brand-green-dark', icon: CheckCircle2 },
    lunas: { label: 'Lunas', color: 'bg-brand-green-light text-brand-green-dark', icon: CheckCircle2 },
    aktif: { label: 'Aktif', color: 'bg-blue-50 text-blue-700', icon: Clock },
    pending: { label: 'Menunggu', color: 'bg-amber-50 text-amber-700', icon: AlertCircle },
    ditolak: { label: 'Ditolak', color: 'bg-red-50 text-red-700', icon: XCircle },
    belum_bayar: { label: 'Belum Bayar', color: 'bg-slate-100 text-slate-600', icon: Clock },
    diajukan: { label: 'Diajukan', color: 'bg-amber-50 text-amber-700', icon: Clock },
    approved_bendahara: { label: 'Disetujui Bendahara', color: 'bg-blue-50 text-blue-700', icon: Clock },
    nonaktif: { label: 'Nonaktif', color: 'bg-slate-100 text-slate-600', icon: XCircle },
    resign: { label: 'Resign', color: 'bg-rose-50 text-rose-700', icon: XCircle },
};

export default function StatusBadge({ status }) {
    const config = statusConfig[status] ?? statusConfig.pending;
    const Icon = config.icon;

    return (
        <span className={`inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-semibold ${config.color}`}>
            <Icon size={16} />
            {config.label}
        </span>
    );
}