import AppLayout from '@/Layouts/AppLayout';
import { Head } from '@inertiajs/react';
import { Users, PiggyBank, HandCoins, ClipboardCheck } from 'lucide-react';

const widgets = [
    {
        label: 'Total Anggota Aktif',
        value: '-',
        icon: Users,
        color: 'bg-blue-50 text-blue-600',
    },
    {
        label: 'Total Simpanan',
        value: '-',
        icon: PiggyBank,
        color: 'bg-emerald-50 text-emerald-600',
    },
    {
        label: 'Pinjaman Outstanding',
        value: '-',
        icon: HandCoins,
        color: 'bg-amber-50 text-amber-600',
    },
    {
        label: 'Menunggu Approval',
        value: '-',
        icon: ClipboardCheck,
        color: 'bg-rose-50 text-rose-600',
    },
];

export default function Dashboard() {
    return (
        <AppLayout>
            <Head title="Dashboard" />

            <div className="mb-6">
                <h1 className="text-2xl font-semibold text-gray-800">
                    Dashboard
                </h1>
                <p className="text-sm text-gray-400 mt-1">
                    Ringkasan aktivitas koperasi hari ini
                </p>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                {widgets.map((widget) => {
                    const Icon = widget.icon;
                    return (
                        <div
                            key={widget.label}
                            className="bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition-shadow"
                        >
                            <div className={`w-10 h-10 rounded-lg flex items-center justify-center mb-3 ${widget.color}`}>
                                <Icon size={20} />
                            </div>
                            <p className="text-sm text-gray-500">{widget.label}</p>
                            <p className="text-2xl font-semibold text-gray-800 mt-1">
                                {widget.value}
                            </p>
                        </div>
                    );
                })}
            </div>

            <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <p className="text-sm font-semibold text-gray-700 mb-4">
                    Aktivitas Terbaru
                </p>
                <div className="flex flex-col items-center justify-center py-10 text-center">
                    <p className="text-sm text-gray-400">Belum ada data aktivitas.</p>
                    <p className="text-xs text-gray-300 mt-1">
                        Data akan muncul setelah modul transaksi aktif.
                    </p>
                </div>
            </div>
        </AppLayout>
    );
}