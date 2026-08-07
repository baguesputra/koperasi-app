import AppLayout from '@/Layouts/AppLayout';
import { Head } from '@inertiajs/react';
import { Users, PiggyBank, HandCoins, ClipboardCheck } from 'lucide-react';
import { formatRupiah } from '@/Utils/formatCurrency';

export default function Dashboard({ stats, aktivitasTerbaru }) {
    const widgets = [
        {
            label: 'Total Anggota Aktif',
            value: stats.total_anggota_aktif,
            icon: Users,
            tone: 'bg-brand-navy/5 text-brand-navy',
        },
        {
            label: 'Total Simpanan',
            value: formatRupiah(stats.total_simpanan),
            icon: PiggyBank,
            tone: 'bg-brand-green-light text-brand-green-dark',
        },
        {
            label: 'Pinjaman Outstanding',
            value: formatRupiah(stats.pinjaman_outstanding),
            icon: HandCoins,
            tone: 'bg-amber-50 text-amber-700',
        },
        {
            label: 'Menunggu Approval',
            value: stats.menunggu_approval,
            icon: ClipboardCheck,
            tone: 'bg-red-50 text-red-600',
        },
    ];

    const statusLabel = {
        diajukan: 'Diajukan',
        ditinjau_bendahara: 'Ditinjau Bendahara',
        approved_bendahara: 'Disetujui Bendahara',
        approved_ketua: 'Disetujui Ketua',
        aktif: 'Aktif',
        lunas: 'Lunas',
        ditolak: 'Ditolak',
    };

    return (
        <AppLayout>
            <Head title="Dashboard" />

            <div className="mb-6">
                <h1 className="text-2xl font-bold text-slate-800">Dashboard</h1>
                <p className="text-base text-slate-400 mt-1">
                    Ringkasan aktivitas koperasi hari ini
                </p>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                {widgets.map((widget) => {
                    const Icon = widget.icon;
                    return (
                        <div
                            key={widget.label}
                            className="bg-white rounded-2xl border border-slate-100 shadow-sm p-5"
                        >
                            <div className={`w-11 h-11 rounded-xl flex items-center justify-center mb-3 ${widget.tone}`}>
                                <Icon size={22} />
                            </div>
                            <p className="text-sm text-slate-500">{widget.label}</p>
                            <p className="text-2xl font-bold text-slate-800 mt-1">
                                {widget.value}
                            </p>
                        </div>
                    );
                })}
            </div>

            <div className="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
                <p className="text-base font-bold text-slate-700 mb-4">
                    Pengajuan Pinjaman Terbaru
                </p>

                {aktivitasTerbaru.length === 0 ? (
                    <div className="flex flex-col items-center justify-center py-10 text-center">
                        <p className="text-base text-slate-400">Belum ada data aktivitas.</p>
                    </div>
                ) : (
                    <div className="divide-y divide-slate-100">
                        {aktivitasTerbaru.map((item, i) => (
                            <div key={i} className="flex items-center justify-between py-3.5">
                                <div>
                                    <p className="text-base font-semibold text-slate-800">
                                        {item.nama}
                                    </p>
                                    <p className="text-sm text-slate-400">{item.tanggal}</p>
                                </div>
                                <div className="text-right">
                                    <p className="text-base font-bold text-slate-800">
                                        {formatRupiah(item.nominal)}
                                    </p>
                                    <p className="text-sm text-slate-500">
                                        {statusLabel[item.status] ?? item.status}
                                    </p>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}