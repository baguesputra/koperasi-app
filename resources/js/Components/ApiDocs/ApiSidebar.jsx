import { BookOpen, ShieldAlert, Terminal } from 'lucide-react';

/**
 * Navigasi sisi sticky (Table of Contents) untuk melompat cepat ke panduan dan 8 grup endpoint.
 */
export default function ApiSidebar({
    groups,
    activeSection,
    onJump,
    onOpenConsole,
    totalCount,
}) {
    return (
        <aside className="hidden lg:block lg:w-64 lg:shrink-0">
            <div className="sticky top-20 space-y-5">
                {/* Panduan Cepat */}
                <div>
                    <p className="mb-2 px-2.5 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                        Panduan Integrasi
                    </p>
                    <nav className="space-y-0.5" aria-label="Panduan API">
                        <a
                            href="#ringkasan"
                            onClick={(e) => {
                                e.preventDefault();
                                onJump('ringkasan');
                            }}
                            className={`flex items-center gap-2 rounded-lg px-2.5 py-2 text-xs font-medium transition ${
                                activeSection === 'ringkasan'
                                    ? 'bg-brand-navy text-white shadow-sm'
                                    : 'text-slate-600 hover:bg-slate-100 hover:text-brand-navy'
                            }`}
                        >
                            <BookOpen className="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
                            <span>Konvensi &amp; Alur Kerja</span>
                        </a>
                        <a
                            href="#referensi-error"
                            onClick={(e) => {
                                e.preventDefault();
                                onJump('referensi-error');
                            }}
                            className={`flex items-center gap-2 rounded-lg px-2.5 py-2 text-xs font-medium transition ${
                                activeSection === 'referensi-error'
                                    ? 'bg-brand-navy text-white shadow-sm'
                                    : 'text-slate-600 hover:bg-slate-100 hover:text-brand-navy'
                            }`}
                        >
                            <ShieldAlert className="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
                            <span>Kode Status &amp; Error</span>
                        </a>
                    </nav>
                </div>

                {/* Daftar Grup Endpoint */}
                <div>
                    <div className="mb-2 flex items-center justify-between px-2.5">
                        <p className="text-[11px] font-bold uppercase tracking-wider text-slate-400">
                            Referensi Endpoint
                        </p>
                        <span className="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-[10px] font-semibold text-slate-600">
                            {totalCount}
                        </span>
                    </div>
                    <nav className="space-y-0.5" aria-label="Daftar Grup Endpoint">
                        {groups.map((group) => {
                            const active = activeSection === group.id;
                            return (
                                <a
                                    key={group.id}
                                    href={`#${group.id}`}
                                    onClick={(e) => {
                                        e.preventDefault();
                                        onJump(group.id);
                                    }}
                                    className={`flex items-center justify-between gap-2 rounded-lg px-2.5 py-2 text-xs font-medium transition ${
                                        active
                                            ? 'bg-brand-green-light font-semibold text-brand-green-dark ring-1 ring-brand-green/30'
                                            : 'text-slate-600 hover:bg-slate-100 hover:text-brand-navy'
                                    }`}
                                >
                                    <span className="truncate">{group.name}</span>
                                    <span
                                        className={`shrink-0 rounded-full px-1.5 py-0.5 font-mono text-[10px] ${
                                            active
                                                ? 'bg-brand-green text-white'
                                                : 'bg-slate-100 text-slate-500'
                                        }`}
                                    >
                                        {group.endpoints.length}
                                    </span>
                                </a>
                            );
                        })}
                    </nav>
                </div>

                {/* Shortcut Konsol */}
                <div className="rounded-xl border border-slate-200 bg-slate-50 p-3.5">
                    <p className="text-xs font-bold text-brand-navy">Uji Live API</p>
                    <p className="mt-1 text-[11px] leading-relaxed text-slate-500">
                        Kirim request nyata ke server menggunakan Bearer token atau sesi aktif Anda.
                    </p>
                    <button
                        type="button"
                        onClick={onOpenConsole}
                        className="mt-3 inline-flex w-full items-center justify-center gap-1.5 rounded-lg bg-brand-navy px-3 py-2 text-xs font-semibold text-white transition hover:bg-brand-navy-light"
                    >
                        <Terminal className="h-3.5 w-3.5" aria-hidden="true" />
                        <span>Buka Konsol Uji</span>
                    </button>
                </div>
            </div>
        </aside>
    );
}
