import { useState } from 'react';
import { UserCog, Shield, ChevronRight } from 'lucide-react';
import Drawer from '@/Components/ui/Drawer';
import SheetKelolaPengguna from './SheetKelolaPengguna';
import SheetKelolaRole from './SheetKelolaRole';

export default function SectionAkses({ pengguna, filterPengguna, daftarRole, roleList, semuaPermission, panelAktif, tabAktif }) {
    const [sheet, setSheet] = useState(panelAktif);

    const items = [
        { key: 'kelola-pengguna', title: 'Kelola Pengguna', desc: 'Akun login, role, dan status pengguna', icon: UserCog },
        { key: 'kelola-role', title: 'Kelola Role', desc: 'Hak akses dan wewenang tiap role', icon: Shield },
    ];

    return (
        <div className="space-y-4">
            <p className="text-sm text-slate-400">Pilih kelola pengguna atau role — panel terbuka di samping tanpa pindah halaman.</p>
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {items.map((item) => {
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
                                <div className="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 bg-brand-green-light text-brand-green-dark">
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
        </div>
    );
}
