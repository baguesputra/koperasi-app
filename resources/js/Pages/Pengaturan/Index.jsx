import AppLayout from '@/Layouts/AppLayout';
import { Head, router } from '@inertiajs/react';
import Card from '@/Components/ui/Card';
import PageHeader from '@/Components/ui/PageHeader';
import { SECTIONS } from './Sections';
import TabAturanPinjaman from './Partials/TabAturanPinjaman';
import TabDanaOperasional from './Partials/TabDanaOperasional';
import TabWa from './Partials/TabWa';
import TabAuditLog from './Partials/TabAuditLog';
import SectionAkses from './Partials/SectionAkses';
import SectionOrganisasi from './Partials/SectionOrganisasi';

const JUDUL = {
    'aturan-pinjaman': 'Aturan Pinjaman', 'dana-operasional': 'Dana Operasional',
    wa: 'WhatsApp', akses: 'Akses', organisasi: 'Organisasi GATE', audit: 'Audit Log',
};

const SUB_NAV = {
    'aturan-pinjaman': [
        { id: 'bunga', label: 'Bunga' },
        { id: 'limit', label: 'Limit' },
        { id: 'tenor', label: 'Tenor' },
    ],
    'dana-operasional': [
        { id: 'simpanan', label: 'Simpanan' },
        { id: 'kas', label: 'Kas' },
        { id: 'chip', label: 'Chip' },
    ],
    organisasi: [
        { id: 'org-status', label: 'Status' },
        { id: 'org-aksi', label: 'Sinkron' },
        { id: 'org-jadwal', label: 'Jadwal' },
        { id: 'org-riwayat', label: 'Riwayat' },
    ],
};

export default function Index({
    tabAktif,
    panelAktif,
    sectionAktif,
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
    chipNominal,
    ringkasanMaster,
    gateStatus,
    gateSetting,
    gateHistory,
    auditLogs,
    filterAudit,
}) {
    function pindahTab(key) {
        if (key === tabAktif) {
            return;
        }
        router.get(route('pengaturan.index'), { tab: key, panel: panelAktif ?? undefined }, { preserveState: false, replace: true });
    }

    function lompatSection(id) {
        const el = document.getElementById(id);
        if (!el) return;
        const gerakKecil = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        el.scrollIntoView({ behavior: gerakKecil ? 'auto' : 'smooth', block: 'start' });
    }

    const subNav = SUB_NAV[tabAktif] ?? [];

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
                                className={`flex min-h-[40px] items-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg whitespace-nowrap transition-colors ${
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
                <nav aria-label="Navigasi pengaturan" className="hidden lg:block p-3 space-y-5 border-r border-slate-100 self-stretch">
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
                                            aria-current={isActive ? 'page' : undefined}
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
                        {subNav.length > 0 && (
                            <nav aria-label="Lompat ke bagian" className="sticky top-0 z-10 -mx-1 mt-3 flex flex-wrap gap-1.5 bg-white/95 py-2 backdrop-blur">
                                {subNav.map((s) => (
                                    <button
                                        key={s.id}
                                        type="button"
                                        onClick={() => lompatSection(s.id)}
                                        className="min-h-[40px] px-3.5 inline-flex items-center rounded-full text-xs font-bold text-brand-navy bg-slate-100 hover:bg-brand-green-light hover:text-brand-green-dark transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-green/40"
                                    >
                                        {s.label}
                                    </button>
                                ))}
                            </nav>
                        )}
                    </div>

                    {tabAktif === 'aturan-pinjaman' && <TabAturanPinjaman bungaSaatIni={bungaSaatIni} limitPinjaman={limitPinjaman} tabelTenor={tabelTenor} sectionAktif={sectionAktif} />}
                    {tabAktif === 'dana-operasional' && <TabDanaOperasional settingSimpanan={settingSimpanan} settingKas={settingKas} chipNominal={chipNominal} sectionAktif={sectionAktif} />}
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
                        <SectionOrganisasi ringkasanMaster={ringkasanMaster} gateStatus={gateStatus} gateSetting={gateSetting} gateHistory={gateHistory} />
                    )}
                    {tabAktif === 'audit' && <TabAuditLog auditLogs={auditLogs} filterAudit={filterAudit} />}
                </div>
            </div>
            </Card>
        </AppLayout>
    );
}
