import { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import { Activity, ChevronDown, ChevronRight, Search, RotateCcw, ChevronLeft } from 'lucide-react';
import Button from '@/Components/ui/Button';
import { infoAksi, inisialNama, waktuRelatif } from '@/Utils/auditActions';

const fokusRing = 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-green/40';

function DataDiff({ dataLama, dataBaru }) {
    const punya = (obj) => obj && Object.keys(obj).length > 0;

    if (!punya(dataLama) && !punya(dataBaru)) return null;

    const semuaKunci = [...new Set([...Object.keys(dataLama ?? {}), ...Object.keys(dataBaru ?? {})])];

    return (
        <div className="overflow-hidden rounded-lg border border-slate-200">
            <table className="w-full text-xs">
                <thead>
                    <tr className="bg-slate-100 text-left text-slate-500">
                        <th className="py-2 px-3 font-semibold w-1/4">Field</th>
                        <th className="py-2 px-3 font-semibold w-3/8 text-red-600">Sebelum</th>
                        <th className="py-2 px-3 font-semibold w-3/8 text-brand-green-dark">Sesudah</th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-slate-100 bg-white">
                    {semuaKunci.map((kunci) => {
                        const lama = dataLama?.[kunci];
                        const baru = dataBaru?.[kunci];
                        const berubah = JSON.stringify(lama) !== JSON.stringify(baru);

                        if (!berubah && punya(dataLama) && punya(dataBaru)) return null;

                        return (
                            <tr key={kunci} className={berubah ? 'bg-amber-50/50' : ''}>
                                <td className="py-1.5 px-3 font-mono text-slate-500 break-all">{kunci}</td>
                                <td className="py-1.5 px-3 text-slate-500 break-all line-through decoration-red-300">
                                    {formatNilai(lama)}
                                </td>
                                <td className="py-1.5 px-3 text-slate-800 font-medium break-all">{formatNilai(baru)}</td>
                            </tr>
                        );
                    })}
                </tbody>
            </table>
        </div>
    );
}

function formatNilai(nilai) {
    if (nilai === null || nilai === undefined) return '—';
    if (typeof nilai === 'object') return JSON.stringify(nilai);
    if (typeof nilai === 'boolean') return nilai ? 'true' : 'false';
    if (nilai === '') return '(kosong)';
    return String(nilai);
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

            {/* List */}
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
                <ol className="space-y-2.5">
                    {auditLogs.data.map((log) => {
                        const expanded = expandedId === log.id;
                        const info = infoAksi(log.aksi);
                        const punyaData = (log.data_lama && Object.keys(log.data_lama).length > 0)
                            || (log.data_baru && Object.keys(log.data_baru).length > 0);

                        return (
                            <li key={log.id} className={`rounded-xl border transition-colors ${
                                expanded ? 'border-slate-200 bg-white shadow-sm' : 'border-transparent hover:bg-slate-50'
                            }`}>
                                <button
                                    type="button"
                                    onClick={() => punyaData && toggleExpand(log.id)}
                                    disabled={!punyaData}
                                    aria-expanded={expanded}
                                    className={`w-full text-left px-4 sm:px-5 py-3.5 flex items-start gap-3 sm:gap-4 ${punyaData ? 'cursor-pointer' : 'cursor-default'}`}
                                >
                                    {/* Chevron / spacer */}
                                    {punyaData ? (
                                        <span className={`shrink-0 mt-0.5 w-6 h-6 rounded-full flex items-center justify-center ${info.outcomeStyle}`}>
                                            {expanded ? <ChevronDown size={14} /> : <ChevronRight size={14} />}
                                        </span>
                                    ) : (
                                        <span className={`shrink-0 mt-0.5 w-6 h-6 rounded-full flex items-center justify-center ${info.outcomeStyle}`}>
                                            <Activity size={13} />
                                        </span>
                                    )}

                                    {/* Konten utama */}
                                    <div className="flex-1 min-w-0">
                                        <div className="flex items-center gap-2 flex-wrap mb-1">
                                            <span className="text-sm font-bold text-slate-800">{info.label}</span>
                                            {info.kategoriLabel && (
                                                <span className={`inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold ${info.kategoriStyle}`}>
                                                    {info.kategoriLabel}
                                                </span>
                                            )}
                                            <span className={`inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold ${info.outcomeStyle}`}>
                                                {info.label.includes('Tolak') ? 'Ditolak' : info.label.includes('Setujui') ? 'Disetujui' : info.label.includes('Cair') ? 'Dicairkan' : 'Aktivitas'}
                                            </span>
                                        </div>
                                        <p className="text-sm text-slate-600 leading-snug">{log.keterangan}</p>
                                    </div>

                                    {/* Metadata kanan */}
                                    <div className="shrink-0 flex items-center gap-2.5 sm:flex-col sm:items-end sm:gap-1">
                                        {log.user ? (
                                            <span className="flex items-center gap-2">
                                                <span className="w-7 h-7 rounded-full bg-brand-navy text-white text-[11px] font-bold flex items-center justify-center shrink-0">
                                                    {inisialNama(log.user.name)}
                                                </span>
                                                <span className="hidden sm:inline text-xs font-medium text-slate-700 max-w-[140px] truncate">
                                                    {log.user.name}
                                                </span>
                                            </span>
                                        ) : (
                                            <span className="w-7 h-7 rounded-full bg-slate-200 text-slate-500 text-[10px] font-bold flex items-center justify-center shrink-0">SY</span>
                                        )}
                                        <time
                                            title={log.created_at}
                                            className="text-xs text-slate-400 whitespace-nowrap"
                                        >
                                            {waktuRelatif(log.created_at)}
                                        </time>
                                    </div>
                                </button>

                                {/* Detail diff */}
                                {expanded && (
                                    <div className="px-4 sm:px-5 pb-4 pl-[52px] sm:pl-[60px]">
                                        <p className="text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-2">
                                            Detail perubahan &middot; oleh {log.user ? `${log.user.name} (${log.user.no_karyawan})` : 'Sistem'} pada {log.created_at}
                                        </p>
                                        <DataDiff dataLama={log.data_lama} dataBaru={log.data_baru} />
                                    </div>
                                )}
                            </li>
                        );
                    })}
                </ol>
            )}

            {/* Pagination */}
            {auditLogs && auditLogs.last_page > 1 && (
                <div className="flex flex-wrap items-center justify-between gap-3 mt-5">
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