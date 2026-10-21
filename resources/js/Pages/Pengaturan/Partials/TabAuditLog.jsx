import { useEffect, useRef, useState } from 'react';
import { router } from '@inertiajs/react';
import useDebouncedValue from '@/Utils/useDebouncedValue';
import {
    Activity,
    ChevronDown,
    ChevronRight,
    Search,
    RotateCcw,
    ChevronLeft,
    FileSpreadsheet,
    Globe,
    ShieldAlert,
    Users,
    Laptop,
    Filter,
} from 'lucide-react';
import Button from '@/Components/ui/Button';
import { infoAksi, inisialNama, waktuRelatif } from '@/Utils/auditActions';

const fokusRing = 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-green/40';

function DataDiff({ dataLama, dataBaru }) {
    const punya = (obj) => obj && Object.keys(obj).length > 0;

    if (!punya(dataLama) && !punya(dataBaru)) return null;

    const semuaKunci = [...new Set([...Object.keys(dataLama ?? {}), ...Object.keys(dataBaru ?? {})])];

    return (
        <div className="overflow-hidden rounded-xl border border-slate-200 shadow-xs">
            <table className="w-full text-xs">
                <thead>
                    <tr className="bg-slate-50 text-left text-slate-500 border-b border-slate-200">
                        <th className="py-2.5 px-3.5 font-semibold w-1/4">Field / Properti</th>
                        <th className="py-2.5 px-3.5 font-semibold w-3/8 text-red-600 bg-red-50/50">Sebelum</th>
                        <th className="py-2.5 px-3.5 font-semibold w-3/8 text-brand-green-dark bg-emerald-50/50">Sesudah</th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-slate-100 bg-white">
                    {semuaKunci.map((kunci) => {
                        const lama = dataLama?.[kunci];
                        const baru = dataBaru?.[kunci];
                        const berubah = JSON.stringify(lama) !== JSON.stringify(baru);

                        if (!berubah && punya(dataLama) && punya(dataBaru)) return null;

                        return (
                            <tr key={kunci} className={berubah ? 'bg-amber-50/40 hover:bg-amber-50/70' : 'hover:bg-slate-50'}>
                                <td className="py-2 px-3.5 font-mono text-slate-600 font-medium break-all">{kunci}</td>
                                <td className="py-2 px-3.5 text-slate-500 break-all line-through decoration-red-300 bg-red-50/20">
                                    {formatNilai(lama)}
                                </td>
                                <td className="py-2 px-3.5 text-slate-800 font-medium break-all bg-emerald-50/20">
                                    {formatNilai(baru)}
                                </td>
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
    if (typeof nilai === 'object') return JSON.stringify(nilai, null, 2);
    if (typeof nilai === 'boolean') return nilai ? 'true' : 'false';
    if (nilai === '') return '(kosong)';
    return String(nilai);
}

export default function TabAuditLog({
    auditLogs,
    filterAudit,
    statAudit,
    daftarUserAudit = [],
    daftarKategoriAudit = {},
}) {
    const [search, setSearch] = useState(filterAudit?.search ?? '');
    const [userId, setUserId] = useState(filterAudit?.user_id ?? '');
    const [kategori, setKategori] = useState(filterAudit?.kategori ?? '');
    const [dateFrom, setDateFrom] = useState(filterAudit?.date_from ?? '');
    const [dateTo, setDateTo] = useState(filterAudit?.date_to ?? '');
    const [expandedId, setExpandedId] = useState(null);

    const cariDebounced = useDebouncedValue(search);
    const pertama = useRef(true);

    useEffect(() => {
        if (pertama.current) {
            pertama.current = false;
            return;
        }

        if ((cariDebounced ?? '') !== (filterAudit?.search ?? '')) {
            terapkanFilter({ search: cariDebounced ?? '' });
        }
    }, [cariDebounced]);

    useEffect(() => {
        setSearch(filterAudit?.search ?? '');
        setUserId(filterAudit?.user_id ?? '');
        setKategori(filterAudit?.kategori ?? '');
        setDateFrom(filterAudit?.date_from ?? '');
        setDateTo(filterAudit?.date_to ?? '');
    }, [filterAudit]);

    function terapkanFilter(overrides = {}) {
        const params = {
            tab: 'audit',
            search: overrides.search !== undefined ? overrides.search : (cariDebounced ?? ''),
            user_id: overrides.user_id !== undefined ? overrides.user_id : userId,
            kategori: overrides.kategori !== undefined ? overrides.kategori : kategori,
            date_from: overrides.date_from !== undefined ? overrides.date_from : dateFrom,
            date_to: overrides.date_to !== undefined ? overrides.date_to : dateTo,
        };

        Object.keys(params).forEach((k) => {
            if (params[k] === '' || params[k] === null || params[k] === undefined) {
                delete params[k];
            }
        });

        router.get(route('pengaturan.index'), params, { preserveState: true, replace: true });
    }

    function reset() {
        setSearch('');
        setUserId('');
        setKategori('');
        setDateFrom('');
        setDateTo('');
        router.get(route('pengaturan.index'), { tab: 'audit' }, { preserveState: true, replace: true });
    }

    function exportExcel() {
        const params = new URLSearchParams();
        if (search) params.append('search', search);
        if (userId) params.append('user_id', userId);
        if (kategori) params.append('kategori', kategori);
        if (dateFrom) params.append('date_from', dateFrom);
        if (dateTo) params.append('date_to', dateTo);

        const url = route('pengaturan.audit.export') + (params.toString() ? `?${params.toString()}` : '');
        window.open(url, '_blank');
    }

    function gantiHalaman(url) {
        if (!url) return;
        router.get(url, {}, { preserveState: true });
    }

    function toggleExpand(id) {
        setExpandedId((prev) => (prev === id ? null : id));
    }

    const adaFilter = Boolean(search || userId || kategori || dateFrom || dateTo);

    const auditLinks = (auditLogs?.links ?? [])
        .filter((link) => !link.label.includes('&laquo;') && !link.label.includes('&raquo;'));

    return (
        <div className="space-y-6">
            {/* Header info */}
            <div>
                <h3 className="text-base font-bold text-slate-800">Audit Trail & Rekam Jejak Forensik</h3>
                <p className="text-sm text-slate-500 mt-0.5">
                    Memantau seluruh aktivitas sistem, persetujuan keuangan, perubahan konfigurasi, serta mendeteksi anomali akses demi menjaga integritas koperasi.
                </p>
            </div>

            {/* Statistik Ringkas */}
            {statAudit && (
                <div className="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
                    <div className="bg-slate-50/80 border border-slate-200/80 rounded-2xl p-4">
                        <div className="flex items-center gap-2 text-slate-500 mb-1">
                            <Activity size={16} className="text-brand-navy" />
                            <span className="text-xs font-semibold">Total Log</span>
                        </div>
                        <p className="text-xl sm:text-2xl font-bold text-slate-800">
                            {adaFilter ? statAudit.total_filtered : statAudit.total_semua}
                            {adaFilter && (
                                <span className="text-xs font-normal text-slate-400 ml-1.5">
                                    dari {statAudit.total_semua}
                                </span>
                            )}
                        </p>
                    </div>

                    <div className="bg-amber-50/50 border border-amber-200/70 rounded-2xl p-4">
                        <div className="flex items-center gap-2 text-amber-700 mb-1">
                            <ShieldAlert size={16} className="text-amber-600" />
                            <span className="text-xs font-semibold">Aksi Kritis</span>
                        </div>
                        <p className="text-xl sm:text-2xl font-bold text-amber-900">
                            {statAudit.aksi_kritis}
                        </p>
                    </div>

                    <div className="bg-emerald-50/50 border border-emerald-200/70 rounded-2xl p-4">
                        <div className="flex items-center gap-2 text-emerald-700 mb-1">
                            <Users size={16} className="text-brand-green" />
                            <span className="text-xs font-semibold">Aktor Terdaftar</span>
                        </div>
                        <p className="text-xl sm:text-2xl font-bold text-emerald-950">
                            {statAudit.total_aktor} <span className="text-xs font-normal text-slate-400">user</span>
                        </p>
                    </div>

                    <div className="bg-slate-50/80 border border-slate-200/80 rounded-2xl p-4 flex flex-col justify-between">
                        <div className="flex items-center gap-2 text-slate-500 mb-1">
                            <Filter size={16} className="text-slate-600" />
                            <span className="text-xs font-semibold">Status Filter</span>
                        </div>
                        <p className="text-xs font-medium text-slate-600 truncate">
                            {adaFilter ? 'Filter aktif diterapkan' : 'Menampilkan seluruh data'}
                        </p>
                    </div>
                </div>
            )}

            {/* Filter Bar Lengkap */}
            <div className="bg-slate-50/90 border border-slate-200/90 rounded-2xl p-4 sm:p-5 space-y-3.5">
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                    {/* Pencarian bebas */}
                    <div className="lg:col-span-2">
                        <label className="block text-xs font-semibold text-slate-600 mb-1">Cari Keterangan / IP / Aksi</label>
                        <div className="relative">
                            <Search size={16} aria-hidden="true" className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                            <input
                                type="search"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Cari nama, no. karyawan, IP, keterangan..."
                                aria-label="Cari log audit"
                                className={`w-full pl-9 pr-3 py-2 text-sm rounded-xl border border-slate-300 bg-white placeholder:text-slate-400 focus:border-brand-green outline-none ${fokusRing}`}
                            />
                        </div>
                    </div>

                    {/* Filter User / Aktor */}
                    <div>
                        <label htmlFor="audit-user-select" className="block text-xs font-semibold text-slate-600 mb-1">Aktor (Per-User)</label>
                        <select
                            id="audit-user-select"
                            value={userId}
                            onChange={(e) => {
                                setUserId(e.target.value);
                                terapkanFilter({ user_id: e.target.value });
                            }}
                            className={`w-full px-3 py-2 text-sm rounded-xl border border-slate-300 bg-white focus:border-brand-green outline-none ${fokusRing}`}
                        >
                            <option value="">Semua Aktor</option>
                            <option value="system">Sistem Otomatis (Tanpa User)</option>
                            {daftarUserAudit.map((u) => (
                                <option key={u.id} value={u.id}>
                                    {u.name} {u.no_karyawan ? `(${u.no_karyawan})` : ''}
                                </option>
                            ))}
                        </select>
                    </div>

                    {/* Filter Kategori Modul */}
                    <div>
                        <label htmlFor="audit-kategori-select" className="block text-xs font-semibold text-slate-600 mb-1">Modul / Kategori</label>
                        <select
                            id="audit-kategori-select"
                            value={kategori}
                            onChange={(e) => {
                                setKategori(e.target.value);
                                terapkanFilter({ kategori: e.target.value });
                            }}
                            className={`w-full px-3 py-2 text-sm rounded-xl border border-slate-300 bg-white focus:border-brand-green outline-none ${fokusRing}`}
                        >
                            <option value="">Semua Modul</option>
                            {Object.entries(daftarKategoriAudit).map(([k, label]) => (
                                <option key={k} value={k}>
                                    {label}
                                </option>
                            ))}
                        </select>
                    </div>

                    {/* Rentang Tanggal */}
                    <div className="grid grid-cols-2 gap-2">
                        <div>
                            <label htmlFor="audit-date-from" className="block text-xs font-semibold text-slate-600 mb-1">Dari</label>
                            <input
                                id="audit-date-from"
                                type="date"
                                value={dateFrom}
                                onChange={(e) => {
                                    setDateFrom(e.target.value);
                                    terapkanFilter({ date_from: e.target.value });
                                }}
                                className={`w-full px-2.5 py-2 text-xs rounded-xl border border-slate-300 bg-white focus:border-brand-green outline-none ${fokusRing}`}
                            />
                        </div>
                        <div>
                            <label htmlFor="audit-date-to" className="block text-xs font-semibold text-slate-600 mb-1">Sampai</label>
                            <input
                                id="audit-date-to"
                                type="date"
                                value={dateTo}
                                onChange={(e) => {
                                    setDateTo(e.target.value);
                                    terapkanFilter({ date_to: e.target.value });
                                }}
                                className={`w-full px-2.5 py-2 text-xs rounded-xl border border-slate-300 bg-white focus:border-brand-green outline-none ${fokusRing}`}
                            />
                        </div>
                    </div>
                </div>

                {/* Tombol Aksi Filter & Export */}
                <div className="flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-slate-200">
                    <div className="flex items-center gap-2">
                        {adaFilter && (
                            <Button variant="ghost" size="sm" onClick={reset} className="text-slate-600 hover:text-red-600">
                                <RotateCcw size={14} className="mr-1" />
                                Reset Filter
                            </Button>
                        )}
                        <span className="text-xs text-slate-400">
                            {auditLogs ? `Ditemukan ${auditLogs.total} entri audit` : 'Memuat data...'}
                        </span>
                    </div>

                    <Button
                        type="button"
                        variant="secondary"
                        size="sm"
                        onClick={exportExcel}
                        className="bg-emerald-700 hover:bg-emerald-800 text-white font-medium shadow-xs"
                    >
                        <FileSpreadsheet size={15} className="mr-1.5" />
                        Export Excel (Multi-Sheet)
                    </Button>
                </div>
            </div>

            {/* List Audit Log */}
            {!auditLogs ? (
                <p className="text-base text-slate-400 text-center py-10">Memuat log audit...</p>
            ) : auditLogs.data.length === 0 ? (
                <div className="text-center py-14 px-4 bg-slate-50/50 rounded-2xl border border-dashed border-slate-200">
                    <Activity size={32} aria-hidden="true" className="mx-auto text-slate-300 mb-3" />
                    <p className="text-base font-semibold text-slate-600">Tidak ada log audit ditemukan</p>
                    <p className="text-xs text-slate-400 mt-1 max-w-sm mx-auto">
                        {adaFilter ? 'Coba ubah kata kunci atau bersihkan filter tanggal/user di atas.' : 'Belum ada aktivitas tercatat di dalam sistem.'}
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
                            <li
                                key={log.id}
                                className={`rounded-2xl border transition-all duration-150 ${
                                    expanded
                                        ? 'border-brand-green/30 bg-white shadow-sm ring-1 ring-brand-green/10'
                                        : 'border-slate-200/90 bg-white hover:border-slate-300 hover:shadow-xs'
                                }`}
                            >
                                <button
                                    type="button"
                                    onClick={() => punyaData && toggleExpand(log.id)}
                                    disabled={!punyaData}
                                    aria-expanded={expanded}
                                    className={`w-full text-left px-4 sm:px-5 py-3.5 flex items-start gap-3 sm:gap-4 ${
                                        punyaData ? 'cursor-pointer' : 'cursor-default'
                                    }`}
                                >
                                    {/* Icon / Chevron */}
                                    {punyaData ? (
                                        <span className={`shrink-0 mt-0.5 w-7 h-7 rounded-xl flex items-center justify-center transition-colors ${info.outcomeStyle}`}>
                                            {expanded ? <ChevronDown size={15} /> : <ChevronRight size={15} />}
                                        </span>
                                    ) : (
                                        <span className={`shrink-0 mt-0.5 w-7 h-7 rounded-xl flex items-center justify-center ${info.outcomeStyle}`}>
                                            <Activity size={14} />
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
                                            {log.ip_address && (
                                                <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-mono font-medium bg-slate-100 text-slate-600">
                                                    <Globe size={11} className="text-slate-400" />
                                                    {log.ip_address}
                                                </span>
                                            )}
                                        </div>
                                        <p className="text-sm text-slate-600 leading-snug">{log.keterangan}</p>
                                    </div>

                                    {/* Metadata kanan (Aktor & Waktu) */}
                                    <div className="shrink-0 flex items-center gap-2.5 sm:flex-col sm:items-end sm:gap-1">
                                        {log.user ? (
                                            <span className="flex items-center gap-2" title={`User ID #${log.user.id} - ${log.user.name}`}>
                                                <span className="w-7 h-7 rounded-full bg-brand-navy text-white text-[11px] font-bold flex items-center justify-center shrink-0 shadow-xs">
                                                    {inisialNama(log.user.name)}
                                                </span>
                                                <div className="hidden sm:block text-right">
                                                    <span className="block text-xs font-semibold text-slate-800 max-w-[150px] truncate leading-tight">
                                                        {log.user.name}
                                                    </span>
                                                    {log.user.no_karyawan && (
                                                        <span className="block text-[10px] text-slate-400 font-mono">
                                                            {log.user.no_karyawan}
                                                        </span>
                                                    )}
                                                </div>
                                            </span>
                                        ) : (
                                            <span className="flex items-center gap-1.5" title="Dijalankan oleh sistem otomatis / scheduler">
                                                <span className="w-7 h-7 rounded-full bg-slate-200 text-slate-600 text-[10px] font-bold flex items-center justify-center shrink-0">
                                                    SYS
                                                </span>
                                                <span className="hidden sm:inline text-xs font-medium text-slate-500">
                                                    Sistem
                                                </span>
                                            </span>
                                        )}
                                        <time
                                            title={log.created_at}
                                            className="text-xs text-slate-400 whitespace-nowrap mt-0.5"
                                        >
                                            {waktuRelatif(log.created_at)}
                                        </time>
                                    </div>
                                </button>

                                {/* Detail diff & Forensik saat di-expand */}
                                {expanded && (
                                    <div className="px-4 sm:px-5 pb-4.5 pt-1 pl-[52px] sm:pl-[64px] border-t border-slate-100 bg-slate-50/40 rounded-b-2xl space-y-3">
                                        <div className="flex flex-wrap items-center justify-between gap-2 text-xs text-slate-500">
                                            <span className="font-semibold text-slate-700">
                                                Detail Forensik Log #{log.id}
                                            </span>
                                            <span className="text-slate-400">
                                                Waktu: {log.created_at} WITA
                                            </span>
                                        </div>

                                        {/* IP & User Agent Info */}
                                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs bg-white p-3 rounded-xl border border-slate-200">
                                            <div className="flex items-start gap-2">
                                                <Globe size={14} className="text-slate-400 shrink-0 mt-0.5" />
                                                <div>
                                                    <span className="font-semibold text-slate-600 block">IP Address:</span>
                                                    <span className="font-mono text-slate-800">{log.ip_address ?? 'Tidak terekam (internal)'}</span>
                                                </div>
                                            </div>
                                            <div className="flex items-start gap-2">
                                                <Laptop size={14} className="text-slate-400 shrink-0 mt-0.5" />
                                                <div className="min-w-0 flex-1">
                                                    <span className="font-semibold text-slate-600 block">Perangkat / Browser:</span>
                                                    <span className="text-slate-700 break-all text-[11px] font-mono leading-tight block">
                                                        {log.user_agent ?? 'Tidak tersedia'}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        {/* Perubahan Data */}
                                        <div>
                                            <p className="text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-2">
                                                Perubahan Data (State Diff):
                                            </p>
                                            <DataDiff dataLama={log.data_lama} dataBaru={log.data_baru} />
                                        </div>
                                    </div>
                                )}
                            </li>
                        );
                    })}
                </ol>
            )}

            {/* Pagination */}
            {auditLogs && auditLogs.last_page > 1 && (
                <div className="flex flex-wrap items-center justify-between gap-3 pt-3">
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
