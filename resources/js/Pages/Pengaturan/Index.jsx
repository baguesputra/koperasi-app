import AppLayout from '@/Layouts/AppLayout';
import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import { Percent, HandCoins, CalendarRange, PiggyBank, Shield } from 'lucide-react';
import Card from '@/Components/ui/Card';
import TabBunga from './Partials/TabBunga';
import TabLimit from './Partials/TabLimit';
import TabTenor from './Partials/TabTenor';
import TabSimpanan from './Partials/TabSimpanan';

const tabs = [
    { key: 'bunga', label: 'Bunga', icon: Percent },
    { key: 'limit', label: 'Limit Pinjaman', icon: HandCoins },
    { key: 'tenor', label: 'Tenor', icon: CalendarRange },
    { key: 'simpanan', label: 'Simpanan', icon: PiggyBank },
];

export default function Index({ limitPinjaman, tabelTenor, bungaSaatIni, settingSimpanan }) {
    const [activeTab, setActiveTab] = useState('bunga');

    return (
        <AppLayout>
            <Head title="Pengaturan" />

            <div className="flex items-center justify-between mb-6">
                <div>
                    <h1 className="text-2xl font-bold text-slate-800">Pengaturan</h1>
                    <p className="text-base text-slate-400 mt-1">
                        Kelola nominal dan ketentuan yang berlaku di sistem
                    </p>
                </div>
                <Link href={route('role.index')}>
                    <button className="flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-brand-navy border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors">
                        <Shield size={16} />
                        Kelola Role
                    </button>
                </Link>
            </div>

            {/* Tab horizontal */}
            <div className="flex items-center gap-1 mb-5 bg-slate-100 p-1 rounded-xl w-fit overflow-x-auto">
                {tabs.map((tab) => {
                    const Icon = tab.icon;
                    const isActive = activeTab === tab.key;
                    return (
                        <button
                            key={tab.key}
                            onClick={() => setActiveTab(tab.key)}
                            className={`flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg whitespace-nowrap transition-colors ${
                                isActive ? 'bg-white text-brand-navy shadow-sm' : 'text-slate-500 hover:text-slate-700'
                            }`}
                        >
                            <Icon size={16} />
                            {tab.label}
                        </button>
                    );
                })}
            </div>

            <Card padding="lg">
                {activeTab === 'bunga' && <TabBunga bungaSaatIni={bungaSaatIni} />}
                {activeTab === 'limit' && <TabLimit limitPinjaman={limitPinjaman} />}
                {activeTab === 'tenor' && <TabTenor tabelTenor={tabelTenor} />}
                {activeTab === 'simpanan' && <TabSimpanan settingSimpanan={settingSimpanan} />}
            </Card>
        </AppLayout>
    );
}