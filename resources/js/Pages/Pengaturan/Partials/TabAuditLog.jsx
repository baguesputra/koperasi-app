import { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import { Activity, ChevronDown, ChevronRight, Search, RotateCcw, ChevronLeft } from 'lucide-react';
import Button from '@/Components/ui/Button';

const fokusRing = 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-green/40';

function JsonBlock({ label, data }) {
    if (!data || (typeof data === 'object' && Object.keys(data).length === 0)) {
        return null;
    }

    return (
        <div className="mb-3 last:mb-0">
            <p className="text-xs font-bold uppercase tracking-wide text-slate-400 mb-1">{label}</p>
            <pre className="text-xs bg-slate-50 border border-slate-100 rounded-lg p-3 overflow-x-auto whitespace-pre-wrap break-words">
                {JSON.stringify(data, null, 2)}
            </pre>
        </div>
    );
}

export default function TabAuditLog({ auditLogs, filterAudit }) {
    const [search, setSearch] = useState(filterAudit?.search ?? '');
    const [dateFrom, setDateFrom] = useState(filterAudit?.date_from ?? '');
    const [dateTo, setDateTo] = useState(filterAudit?.date_to ?? '');
    const [expandedId, setExpandedId] = useState(null);
    const [debouncedSearch, setDebouncedSearch] = useState(search);

    useEffect(() => {
        const timer = setTimeout(() => setDebouncedSearch(search), 300);
        return () => clearTimeout(timer);
    }, [search]);

    useEffect(() => {
        terapkanFilter();
    }, [debouncedSearch]);

    function terapkanFilter(overrides = {}) {
        const params = {
            tab: 'audit',
            search: overrides.search ?? debouncedSearch,
            date_from: overrides.date_from ?? dateFrom,
            date_to: overrides.date_to ?? dateTo,
        };

        Object.keys(params).forEach((k) => {
            if (!params[k]) delete params[k];
        });

        router.get(route('pengaturan.index'), params, { preserveState: true, replace: true });
    }

    function reset() {
        setSearch('');
        setDateFrom('');
        setDateTo('');
        setDebouncedSearch('');
        router.get(route('pengaturan.index'), { tab: 'audit' }, { preserveState: true, replace: true });
    }

    function gantiHalaman(url) {
        if (!url) return;
        router.get(url, {}, { preserveState: true });
    }

    function toggleExpand(id) {
        setExpandedId((prev) => (prev === id ? null : id));
    }

    const adaFilter = Boolean(search || dateFrom || dateTo);

    // Link halaman tengah (buang prev/next yang sudah ditangani tombol Sebelumnya/Berikutnya)
    const auditLinks = (auditLogs?.links ?? [])
        .filter((link) => !link.label.includes('&laquo;') && !link.label.includes('&raquo;'));

    return (
        <div>
            <p className="text-sm text-slate-400 mb-4">
                Riwayat semua perubahan penting sistem: pengaturan, pengajuan, persetujuan, konfirmasi keuangan, dan manajemen pengguna.
            </p>

            {/* Filter bar */}
            <div className="flex flex-wrap items-end gap-3 mb-5">
                <div className="relative flex-1 min-w-[220px] max-w-sm">
                    <Search size={16} aria-hidden="true" className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                    <input
                        type="search"
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Cari aksi atau keterangan..."
                        aria-label="Cari log audit"
                        className={`w-full pl-9 pr-3 py-2.5 text-sm rounded-xl border border-slate-300 bg-white placeholder:text-slate-400 focus:border-brand-green outline-none ${fokusRing}`}
                    />
                </div>
                <div>
                    <label htmlFor="audit-date-from" className="block text-xs font-semibold text-slate-500 mb-1">Dari</label>
                    <input
                        id="audit-date-from"
                        type="date"
                        value={dateFrom}
                        onChange={(e) => { setDateFrom(e.target.value); terapkanFilter({ date_from: e.target.value }); }}
                        className={`px-3 py-2 text-sm rounded-xl border border-slate-300 bg-white focus:border-brand-green outline-none ${fokusRing}`}
                    />
                </div>
                <div>
                    <label htmlFor="audit-date-to" className="block text-xs font-semibold text-slate-500 mb-1">Sampai</label>
                    <input
                        id="audit-date-to"
                        type="date"
                        value={dateTo}
                        onChange={(e) => { setDateTo(e.target.value); terapkanFilter({ date_to: e.target.value }); }}
                        className={`px-3 py-2 text-sm rounded-xl border border-slate-300 bg-white focus:border-brand-green outline-none ${fokusRing}`}
                    />
                </div>
                {adaFilter && (
                    <Button variant="ghost" size="sm" onClick={reset}>
                        <RotateCcw size={14} />
                        Reset
                    </Button>
                )}
            </div>

            {/* Table */}
            {!auditLogs ? (
                <p className="text-base text-slate-400 text-center py-10">Memuat log audit...</p>
            ) : auditLogs.data.length === 0 ? (
                <div className="text-center py-12 px-4">
                    <Activity size={28} aria-hidden="true" className="mx-auto text-slate-300 mb-3" />
                    <p className="text-base text-slate-500">
                        {adaFilter ? 'Tidak ada log yang cocok dengan filter.' : 'Belum ada aktivitas tercatat.'}
                    </p>
                </div>
            ) : (
                <div className="divide-y divide-slate-50 border border-slate-100 rounded-xl overflow-hidden">
                    {auditLogs.data.map((log) => {
                        const expanded = expandedId === log.id;
                        const punyaData = (log.data_lama && Object.keys(log.data_lama).length > 0)
                            || (log.data_baru && Object.keys(log.data_baru).length > 0);

                        return (
                            <div key={log.id} className={expanded ? 'bg-slate-50' : 'hover:bg-slate-50/60 transition-colors'}>
                                <button
                                    type="button"
                                    onClick={() => punyaData && toggleExpand(log.id)}
                                    disabled={!punyaData}
                                    aria-expanded={expanded}
                                    className={`w-full text-left px-4 sm:px-5 py-3.5 flex items-start gap-3 sm:gap-4 ${punyaData ? 'cursor-pointer' : 'cursor-default'}`}
                                >
                                    {punyaData ? (
                                        expanded
                                            ? <ChevronDown size={16} className="shrink-0 mt-1 text-slate-400" />
                                            : <ChevronRight size={16} className="shrink-0 mt-1 text-slate-300" />
                                    ) : (
                                        <span className="w-4 shrink-0" />
                                    )}
                                    <div className="flex-1 min-w-0">
                                        <p className="text-sm font-semibold text-slate-800 break-all">{log.keterangan}</p>
                                        <p className="text-xs text-slate-400 mt-0.5 flex flex-wrap gap-x-2">
                                            <span className="font-mono bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded">{log.aksi}</span>
                                            <span>{log.user ? `${log.user.name} (${log.user.no_karyawan})` : 'Sistem'}</span>
                                            <span>&bull; {log.created_at}</span>
                                        </p>
                                    </div>
                                </button>

                                {expanded && (
                                    <div className="px-4 sm:px-5 pb-4 pl-11 sm:pl-13">
                                        <JsonBlock label="Data Lama" data={log.data_lama} />
                                        <JsonBlock label="Data Baru" data={log.data_baru} />
                                    </div>
                                )}
                            </div>
                        );
                    })}
                </div>
            )}

            {/* Pagination */}
            {auditLogs && auditLogs.last_page > 1 && (
                <div className="flex flex-wrap items-center justify-between gap-3 mt-4">
                    <p className="text-xs text-slate-400">
                        Menampilkan {auditLogs.from}–{auditLogs.to} dari {auditLogs.total} log
                    </p>
                    <div className="flex items-center gap-1 flex-wrap">
                        <Button
                            variant="ghost"
                            size="sm"
                            disabled={!auditLogs.prev_page_url}
                            onClick={() => gantiHalaman(auditLogs.prev_page_url)}
                            aria-label="Halaman sebelumnya"
                        >
                            <ChevronLeft size={16} />
                            Sebelumnya
                        </Button>
                        {auditLinks.map((link, i) => (
                            link.label === '…' ? (
                                <span key={`dots-${i}`} className="px-1 text-sm text-slate-400">…</span>
                            ) : (
                                <button
                                    key={`page-${i}`}
                                    type="button"
                                    onClick={() => gantiHalaman(link.url)}
                                    aria-current={link.active ? 'page' : undefined}
                                    className={`min-w-9 h-9 px-2 text-sm font-semibold rounded-xl transition-colors ${fokusRing} ${
                                        link.active
                                            ? 'bg-brand-navy text-white'
                                            : 'text-slate-600 hover:bg-slate-100'
                                    }`}
                                >
                                    {link.label}
                                </button>
                            )
                        ))}
                        <Button
                            variant="ghost"
                            size="sm"
                            disabled={!auditLogs.next_page_url}
                            onClick={() => gantiHalaman(auditLogs.next_page_url)}
                            aria-label="Halaman berikutnya"
                        >
                            Berikutnya
                            <ChevronRight size={16} />
                        </Button>
                    </div>
                </div>
            )}
        </div>
    );
}