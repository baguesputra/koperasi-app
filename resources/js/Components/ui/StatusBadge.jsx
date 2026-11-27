import { CheckCircle2, Clock, XCircle, AlertCircle } from 'lucide-react';

const statusConfig = {
    disetujui: { label: 'Disetujui', color: 'bg-gradient-to-r from-brand-green-light to-emerald-50 text-brand-green-dark border-emerald-200/80', icon: CheckCircle2 },
    lunas: { label: 'Lunas', color: 'bg-gradient-to-r from-brand-green-light to-emerald-50 text-brand-green-dark border-emerald-200/80', icon: CheckCircle2 },
    aktif: { label: 'Aktif', color: 'bg-gradient-to-r from-sky-50 to-blue-50 text-blue-700 border-blue-200/80', icon: Clock },
    pending: { label: 'Menunggu', color: 'bg-gradient-to-r from-amber-50 to-orange-50/70 text-amber-700 border-amber-200/80', icon: AlertCircle },
    ditolak: { label: 'Ditolak', color: 'bg-gradient-to-r from-red-50 to-rose-50 text-red-700 border-red-200/80', icon: XCircle },
    dibatalkan: { label: 'Dibatalkan', color: 'bg-slate-100 text-slate-600 border-slate-200/90', icon: XCircle },
    belum_bayar: { label: 'Belum Bayar', color: 'bg-slate-50 text-slate-600 border-slate-200/90', icon: Clock },
    diajukan: { label: 'Diajukan', color: 'bg-gradient-to-r from-amber-50 to-orange-50/70 text-amber-700 border-amber-200/80', icon: Clock },
    approved_admin: { label: 'Diteruskan Sekretaris', color: 'bg-gradient-to-r from-sky-50 to-cyan-50 text-sky-700 border-sky-200/80', icon: Clock },
    approved_bendahara: { label: 'Disetujui Bendahara', color: 'bg-gradient-to-r from-indigo-50 to-blue-50 text-indigo-700 border-indigo-200/80', icon: Clock },
    approved_ketua: { label: 'Disetujui Ketua', color: 'bg-gradient-to-r from-emerald-50 to-teal-50 text-emerald-700 border-emerald-200/80', icon: CheckCircle2 },
    approved_pengawas_3: { label: 'Disetujui Pengawas 3', color: 'bg-gradient-to-r from-violet-50 to-purple-50 text-violet-700 border-violet-200/80', icon: Clock },
    approved_pengawas_2: { label: 'Disetujui Pengawas 2', color: 'bg-gradient-to-r from-fuchsia-50 to-purple-50 text-fuchsia-700 border-fuchsia-200/80', icon: Clock },
    dihapusbukukan: { label: 'Dihapusbukukan', color: 'bg-gradient-to-r from-rose-50 to-slate-100 text-rose-700 border-rose-200/80', icon: XCircle },
    nonaktif: { label: 'Nonaktif', color: 'bg-slate-50 text-slate-600 border-slate-200/90', icon: XCircle },
    resign: { label: 'Resign', color: 'bg-gradient-to-r from-rose-50 to-red-50 text-rose-700 border-rose-200/80', icon: XCircle },
    resign_menunggu: { label: 'Resign Menunggu', color: 'bg-gradient-to-r from-amber-50 to-orange-50/70 text-amber-700 border-amber-200/80', icon: Clock },
};

export default function StatusBadge({ status }) {
    const config = statusConfig[status] ?? statusConfig.pending;
    const Icon = config.icon;

    return (
        <span className={`inline-flex items-center gap-1.5 px-2.5 py-1 sm:py-0.5 rounded-full border text-xs font-semibold shadow-[0_1px_2px_rgba(15,30,54,0.04)] ${config.color}`}>
            <Icon size={13} className="shrink-0" />
            <span>{config.label}</span>
        </span>
    );
}