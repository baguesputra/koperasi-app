import AppLayout from '@/Layouts/AppLayout';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { Percent, HandCoins, CalendarRange, PiggyBank, Shield, UserCog, ChevronRight, QrCode, Activity } from 'lucide-react';
import Card from '@/Components/ui/Card';
import Drawer from '@/Components/ui/Drawer';
import TabBunga from './Partials/TabBunga';
import TabLimit from './Partials/TabLimit';
import TabTenor from './Partials/TabTenor';
import TabSimpanan from './Partials/TabSimpanan';
import TabWa from './Partials/TabWa';
import TabAuditLog from './Partials/TabAuditLog';
import SheetKelolaPengguna from './Partials/SheetKelolaPengguna';
import SheetKelolaRole from './Partials/SheetKelolaRole';

const tabs = [
    { key: 'bunga', label: 'Bunga', icon: Percent },
    { key: 'limit', label: 'Limit Pinjaman', icon: HandCoins },
    { key: 'tenor', label: 'Tenor', icon: CalendarRange },
    { key: 'simpanan', label: 'Simpanan', icon: PiggyBank },
    { key: 'wa', label: 'WhatsApp', icon: QrCode },
    { key: 'audit', label: 'Audit Log', icon: Activity },
];

export default function Index({
    tabAktif,
    panelAktif,
    pengguna,
    filterPengguna,
    daftarRole,
    roleList,
    semuaPermission,
    permissionPerRole,
    limitPinjaman,
    tabelTenor,
    bungaSaatIni,
    settingSimpanan,
    auditLogs,
    filterAudit,
}) {
    const [sheet, setSheet] = useState(panelAktif);

    function pindahTab(key) {
        if (key === tabAktif) {
            return;
        }
        router.get(route('pengaturan.index'), { tab: key }, { preserveState: true, replace: true });
    }

    const management = [
        {
            key: 'kelola-pengguna',
            title: 'Kelola Pengguna',
            desc: 'Akun login, role, dan status pengguna',
            icon: UserCog,
        },
        {
            key: 'kelola-role',
            title: 'Kelola Role',
            desc: 'Hak akses dan wewenang tiap role',
            icon: Shield,
        },
    ];

    return (
        <AppLayout>
            <Head title="Pengaturan" />

            <div className="mb-6">
                <h1 className="text-2xl font-bold text-slate-800">Pengaturan</h1>
                <p className="text-base text-slate-400 mt-1">
                    Kelola nominal dan ketentuan yang berlaku di sistem
                </p>
            </div>

            {/* Tab pengaturan umum */}
            <div className="flex items-center gap-1 mb-5 bg-slate-100 p-1 rounded-xl w-fit overflow-x-auto">
                {tabs.map((tab) => {
                    const Icon = tab.icon;
                    const isActive = tabAktif === tab.key;
                    return (
                        <button
                            key={tab.key}
                            onClick={() => pindahTab(tab.key)}
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

            <Card padding="lg" className="max-h-[calc(100vh-280px)] overflow-y-auto">
                {tabAktif === 'bunga' && <TabBunga bungaSaatIni={bungaSaatIni} />}
                {tabAktif === 'limit' && <TabLimit limitPinjaman={limitPinjaman} />}
                {tabAktif === 'tenor' && <TabTenor tabelTenor={tabelTenor} />}
                {tabAktif === 'simpanan' && <TabSimpanan settingSimpanan={settingSimpanan} />}
                {tabAktif === 'wa' && <TabWa />}
                {tabAktif === 'audit' && <TabAuditLog auditLogs={auditLogs} filterAudit={filterAudit} />}
            </Card>

            {/* Manajemen akses */}
            <div className="mt-6">
                <p className="text-sm font-bold uppercase tracking-wider text-slate-400 mb-3">
                    Manajemen Akses
                </p>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {management.map((item) => {
                        const Icon = item.icon;
                        const active = sheet === item.key;
                        return (
                            <button
                                key={item.key}
                                type="button"
                                onClick={() => setSheet(active ? null : item.key)}
                                className={`bg-white rounded-2xl border p-5 flex items-center justify-between gap-4 text-left transition-colors ${
                                    active ? 'border-brand-green ring-1 ring-brand-green' : 'border-slate-100 hover:bg-slate-50'
                                }`}
                            >
                                <div className="flex items-center gap-3 min-w-0">
                                    <div className={`w-10 h-10 rounded-xl flex items-center justify-center shrink-0 ${
                                        item.key === 'kelola-pengguna'
                                            ? 'bg-brand-green-light text-brand-green-dark'
                                            : 'bg-slate-100 text-slate-600'
                                    }`}>
                                        <Icon size={20} />
                                    </div>
                                    <div className="min-w-0">
                                        <p className="text-base font-bold text-slate-800">{item.title}</p>
                                        <p className="text-sm text-slate-400">{item.desc}</p>
                                    </div>
                                </div>
                                <ChevronRight size={18} className={`shrink-0 transition-transform ${active ? 'rotate-90' : 'text-slate-300'}`} />
                            </button>
                        );
                    })}
                </div>
            </div>

            <Drawer
                show={sheet === 'kelola-pengguna'}
                onClose={() => setSheet(null)}
                maxWidth="3xl"
                title="Kelola Pengguna"
            >
                <SheetKelolaPengguna
                    pengguna={pengguna}
                    filterPengguna={filterPengguna}
                    daftarRole={daftarRole}
                    tabAktif={tabAktif}
                />
            </Drawer>

            <Drawer
                show={sheet === 'kelola-role'}
                onClose={() => setSheet(null)}
                maxWidth="3xl"
                title="Kelola Role"
            >
                <SheetKelolaRole
                    roleList={roleList}
                    semuaPermission={semuaPermission}
                />
            </Drawer>
        </AppLayout>
    );
}
