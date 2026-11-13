import { useEffect, useState } from 'react';
import CopyButton from './CopyButton';

/**
 * Blok kode bernuansa navy gelap dengan dukungan switcher multi-tab (JSON/cURL/JS/PHP)
 * serta tombol salin yang menyalin konten tab aktif.
 */
export default function CodeBlock({ title, code, tabs, badge, className = '' }) {
    const hasTabs = Array.isArray(tabs) && tabs.length > 0;
    const [activeTabId, setActiveTabId] = useState(hasTabs ? tabs[0].id : null);

    useEffect(() => {
        if (hasTabs && !tabs.some((tab) => tab.id === activeTabId)) {
            setActiveTabId(tabs[0].id);
        }
    }, [hasTabs, tabs, activeTabId]);

    const currentTab = hasTabs ? tabs.find((tab) => tab.id === activeTabId) ?? tabs[0] : null;
    const rawCode = currentTab ? currentTab.content : code ?? '';
    const formatted =
        typeof rawCode === 'string' ? rawCode : JSON.stringify(rawCode, null, 2);

    return (
        <div
            className={`overflow-hidden rounded-xl border border-brand-navy-light/80 bg-[#0A1526] shadow-sm ${className}`}
        >
            <div className="flex flex-wrap items-center justify-between gap-2 border-b border-white/10 bg-brand-navy px-3.5 py-2">
                <div className="flex flex-wrap items-center gap-1.5">
                    {title && (
                        <span className="mr-1 font-mono text-[11px] font-semibold uppercase tracking-wider text-slate-300">
                            {title}
                        </span>
                    )}
                    {hasTabs &&
                        tabs.map((tab) => {
                            const active = tab.id === currentTab?.id;
                            return (
                                <button
                                    key={tab.id}
                                    type="button"
                                    onClick={() => setActiveTabId(tab.id)}
                                    className={`rounded-md px-2.5 py-1 text-[11px] font-semibold transition ${
                                        active
                                            ? 'bg-brand-green text-white shadow-sm'
                                            : 'text-slate-300 hover:bg-white/10 hover:text-white'
                                    }`}
                                >
                                    {tab.label}
                                </button>
                            );
                        })}
                </div>
                <div className="flex items-center gap-2">
                    {badge && (
                        <span className="rounded bg-white/10 px-2 py-0.5 font-mono text-[10px] font-medium text-emerald-300">
                            {badge}
                        </span>
                    )}
                    <CopyButton text={formatted} tone="dark" />
                </div>
            </div>
            <pre className="max-h-[420px] overflow-x-auto overflow-y-auto p-4 font-mono text-xs leading-relaxed text-slate-100">
                <code>{formatted}</code>
            </pre>
        </div>
    );
}
