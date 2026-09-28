import AppLayout from '@/Layouts/AppLayout';
import { Head, router } from '@inertiajs/react';
import Card from '@/Components/ui/Card';
import PageHeader from '@/Components/ui/PageHeader';
import { SECTIONS } from './Sections';
import TabBunga from './Partials/TabBunga';
import TabLimit from './Partials/TabLimit';
import TabTenor from './Partials/TabTenor';
import TabSimpanan from './Partials/TabSimpanan';
import TabKas from './Partials/TabKas';
import TabWa from './Partials/TabWa';
import TabAuditLog from './Partials/TabAuditLog';
import SectionAkses from './Partials/SectionAkses';
import SectionOrganisasi from './Partials/SectionOrganisasi';

const JUDUL = {
    bunga: 'Bunga', limit: 'Limit Pinjaman', tenor: 'Tenor', simpanan: 'Simpanan', kas: 'Kas Operasional',
    wa: 'WhatsApp', akses: 'Akses', organisasi: 'Organisasi GATE', audit: 'Audit Log',
};

export default function Index({
    tabAktif,
    panelAktif,
    pengguna,
    filterPengguna,
    daftarRole,
    roleList,
    semuaPermission,
    limitPinjaman,
    tabelTenor,
    bungaSaatIni,
    settingSimpanan,
    settingKas,
    ringkasanMaster,
    gateStatus,
    auditLogs,
    filterAudit,
}) {
    function pindahTab(key) {
        if (key === tabAktif) {
            return;
        }
        router.get(route('pengaturan.index'), { tab: key, panel: panelAktif ?? undefined }, { preserveState: false, replace: true });
    }

    return (
        <AppLayout>
            <Head title="Pengaturan" />

            <PageHeader title="Pengaturan" subtitle="Kelola nominal dan ketentuan yang berlaku di sistem" />

            {/* Mobile: navigasi horizontal */}
            <div className="lg:hidden mb-4 -mx-1 px-1 overflow-x-auto">
                <div className="flex gap-1 bg-slate-100 p-1 rounded-xl w-max min-w-full">
                    {SECTIONS.flatMap((s) => s.items).map((item) => {
                        const Icon = item.icon;
                        const isActive = tabAktif === item.key;
                        return (
                            <button
                                key={item.key}
                                onClick={() => pindahTab(item.key)}
                                className={`flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg whitespace-nowrap transition-colors ${
                                    isActive ? 'bg-white text-brand-navy shadow-sm' : 'text-slate-500 hover:text-slate-700'
                                }`}
                            >
                                <Icon size={16} />
                                {item.label}
                            </button>
                        );
                    })}
                </div>
            </div>

            <Card padding="none" className="overflow-hidden">
            <div className="grid grid-cols-1 lg:grid-cols-[240px_minmax(0,1fr)] items-start">
                {/* Desktop: navigasi grup */}
                <nav className="hidden lg:block p-3 space-y-5 border-r border-slate-100 self-stretch">
                    {SECTIONS.map((section) => (
                        <div key={section.grup}>
                            <p className="px-2 mb-1.5 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                                {section.grup}
                            </p>
                            <div className="space-y-0.5">
                                {section.items.map((item) => {
                                    const Icon = item.icon;
                                    const isActive = tabAktif === item.key;
                                    return (
                                        <button
                                            key={item.key}
                                            onClick={() => pindahTab(item.key)}
                                            title={item.desc}
                                            className={`w-full flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-sm font-semibold text-left transition-colors ${
                                                isActive ? 'bg-brand-navy text-white' : 'text-slate-600 hover:bg-slate-50'
                                            }`}
                                        >
                                            <Icon size={17} className={`shrink-0 ${isActive ? 'text-brand-green' : ''}`} />
                                            <span className="min-w-0">
                                                <span className="block truncate">{item.label}</span>
                                                <span className={`block text-xs font-normal truncate ${isActive ? 'text-slate-300' : 'text-slate-400'}`}>
                                                    {item.desc}
                                                </span>
                                            </span>
                                        </button>
                                    );
                                })}
                            </div>
                        </div>
                    ))}
                </nav>

                {/* Konten aktif */}
                <div className="p-6 sm:p-8 min-w-0">
                    <div className="mb-5">
                        <h2 className="text-lg font-bold text-slate-800">{JUDUL[tabAktif] ?? 'Pengaturan'}</h2>
                        <p className="text-sm text-slate-400 mt-0.5">
                            {SECTIONS.flatMap((s) => s.items).find((i) => i.key === tabAktif)?.desc ?? ''}
                        </p>
                    </div>

                    {tabAktif === 'bunga' && <TabBunga bungaSaatIni={bungaSaatIni} />}
                    {tabAktif === 'limit' && <TabLimit limitPinjaman={limitPinjaman} />}
                    {tabAktif === 'tenor' && <TabTenor tabelTenor={tabelTenor} />}
                    {tabAktif === 'simpanan' && <TabSimpanan settingSimpanan={settingSimpanan} />}
                    {tabAktif === 'kas' && <TabKas settingKas={settingKas} />}
                    {tabAktif === 'wa' && <TabWa />}
                    {tabAktif === 'akses' && (
                        <SectionAkses
                            pengguna={pengguna}
                            filterPengguna={filterPengguna}
                            daftarRole={daftarRole}
                            roleList={roleList}
                            semuaPermission={semuaPermission}
                            panelAktif={panelAktif}
                            tabAktif={tabAktif}
                        />
                    )}
                    {tabAktif === 'organisasi' && (
                        <SectionOrganisasi ringkasanMaster={ringkasanMaster} gateStatus={gateStatus} />
                    )}
                    {tabAktif === 'audit' && <TabAuditLog auditLogs={auditLogs} filterAudit={filterAudit} />}
                </div>
            </div>
            </Card>
        </AppLayout>
    );
}
