import { Head, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    ArrowRight,
    CheckCircle2,
    ChevronsDownUp,
    ChevronsUpDown,
    KeyRound,
    Layers,
    Lock,
    Search,
    Terminal,
    X,
} from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import ApiSidebar from '../../Components/ApiDocs/ApiSidebar';
import CodeBlock from '../../Components/ApiDocs/CodeBlock';
import CopyButton from '../../Components/ApiDocs/CopyButton';
import EndpointCard from '../../Components/ApiDocs/EndpointCard';
import TryConsoleDrawer from '../../Components/ApiDocs/TryConsoleDrawer';
import {
    API_BASE_PATH,
    CONVENTIONS,
    ENDPOINT_GROUPS,
    ERROR_CODES,
    INTEGRATION_FLOWS,
    resolveEndpointPath,
    SAMPLE_AUTH_ERROR,
    SAMPLE_CONFLICT_ERROR,
    SAMPLE_VALIDATION_ERROR,
} from './apiSpec';

const TOKEN_STORAGE_KEY = 'koperasi_api_docs_token';
const METHOD_FILTERS = ['SEMUA', 'GET', 'POST', 'PUT', 'DELETE'];

/**
 * Halaman dokumentasi interaktif Koperasi API v1 (53 endpoint + Konsol Uji langsung).
 */
export default function ApiDocsIndex({ authMode = 'local' }) {
    const [searchQuery, setSearchQuery] = useState('');
    const [methodFilter, setMethodFilter] = useState('SEMUA');
    const [expandedIds, setExpandedIds] = useState(() => new Set(['auth-login', 'sso-mobile-exchange']));
    const [activeSection, setActiveSection] = useState('ringkasan');

    const [baseOrigin, setBaseOrigin] = useState('');
    const [consoleOpen, setConsoleOpen] = useState(false);
    const [consoleEndpoint, setConsoleEndpoint] = useState(() => ENDPOINT_GROUPS[0].endpoints[6]); // GET /api/v1/me default
    const [bearerToken, setBearerToken] = useState('');

    const searchInputRef = useRef(null);

    const allEndpoints = useMemo(
        () => ENDPOINT_GROUPS.flatMap((group) => group.endpoints),
        [],
    );

    useEffect(() => {
        if (typeof window !== 'undefined') {
            setBaseOrigin(window.location.origin);
            try {
                const saved = window.sessionStorage.getItem(TOKEN_STORAGE_KEY);
                if (saved) {
                    setBearerToken(saved);
                }
            } catch {
                // Abaikan jika sessionStorage dibatasi browser
            }
        }
    }, []);

    // Pintasan keyboard '/' untuk fokus pencarian
    useEffect(() => {
        const handleKeyDown = (event) => {
            if (event.key !== '/') {
                return;
            }
            const tag = event.target?.tagName?.toLowerCase();
            if (tag === 'input' || tag === 'textarea' || tag === 'select' || event.target?.isContentEditable) {
                return;
            }
            event.preventDefault();
            searchInputRef.current?.focus();
        };

        window.addEventListener('keydown', handleKeyDown);
        return () => window.removeEventListener('keydown', handleKeyDown);
    }, []);

    // Scrollspy untuk menyorot bagian aktif di sidebar
    useEffect(() => {
        if (typeof IntersectionObserver === 'undefined') {
            return undefined;
        }

        const sectionIds = ['ringkasan', ...ENDPOINT_GROUPS.map((g) => g.id), 'referensi-error'];
        const elements = sectionIds
            .map((id) => document.getElementById(id))
            .filter(Boolean);

        if (elements.length === 0) {
            return undefined;
        }

        const observer = new IntersectionObserver(
            (entries) => {
                const visible = entries
                    .filter((entry) => entry.isIntersecting)
                    .sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top);

                if (visible.length > 0) {
                    setActiveSection(visible[0].target.id);
                }
            },
            {
                rootMargin: '-96px 0px -65% 0px',
                threshold: 0.01,
            },
        );

        elements.forEach((el) => observer.observe(el));
        return () => observer.disconnect();
    }, []);

    const handleTokenChange = (nextToken) => {
        setBearerToken(nextToken);
        if (typeof window !== 'undefined') {
            try {
                if (nextToken) {
                    window.sessionStorage.setItem(TOKEN_STORAGE_KEY, nextToken);
                } else {
                    window.sessionStorage.removeItem(TOKEN_STORAGE_KEY);
                }
            } catch {
                // Abaikan kegagalan storage
            }
        }
    };

    const filteredGroups = useMemo(() => {
        const keyword = searchQuery.trim().toLowerCase();

        return ENDPOINT_GROUPS.map((group) => {
            const matchedEndpoints = group.endpoints.filter((endpoint) => {
                const methodOk =
                    methodFilter === 'SEMUA' || endpoint.method === methodFilter;

                if (!methodOk) {
                    return false;
                }

                if (!keyword) {
                    return true;
                }

                const resolvedPath = resolveEndpointPath(endpoint).toLowerCase();
                return (
                    resolvedPath.includes(keyword) ||
                    endpoint.summary.toLowerCase().includes(keyword) ||
                    endpoint.description.toLowerCase().includes(keyword) ||
                    (endpoint.permission && endpoint.permission.toLowerCase().includes(keyword)) ||
                    endpoint.method.toLowerCase().includes(keyword) ||
                    group.name.toLowerCase().includes(keyword)
                );
            });

            return {
                ...group,
                endpoints: matchedEndpoints,
            };
        }).filter((group) => group.endpoints.length > 0);
    }, [searchQuery, methodFilter]);

    const visibleEndpointCount = useMemo(
        () => filteredGroups.reduce((acc, g) => acc + g.endpoints.length, 0),
        [filteredGroups],
    );

    const methodCounts = useMemo(() => {
        const counts = { SEMUA: allEndpoints.length, GET: 0, POST: 0, PUT: 0, DELETE: 0 };
        allEndpoints.forEach((ep) => {
            if (counts[ep.method] !== undefined) {
                counts[ep.method] += 1;
            }
        });
        return counts;
    }, [allEndpoints]);

    const toggleCard = (id) => {
        setExpandedIds((prev) => {
            const next = new Set(prev);
            if (next.has(id)) {
                next.delete(id);
            } else {
                next.add(id);
            }
            return next;
        });
    };

    const expandAllVisible = () => {
        setExpandedIds((prev) => {
            const next = new Set(prev);
            filteredGroups.forEach((group) => {
                group.endpoints.forEach((ep) => next.add(ep.id));
            });
            return next;
        });
    };

    const collapseAll = () => {
        setExpandedIds(new Set());
    };

    const jumpToElement = (elementId, expandEndpoint = false) => {
        if (expandEndpoint) {
            setExpandedIds((prev) => {
                const next = new Set(prev);
                next.add(elementId);
                return next;
            });
        }

        requestAnimationFrame(() => {
            const el = document.getElementById(elementId);
            if (el) {
                el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                setActiveSection(elementId);
            }
        });
    };

    const openConsoleForEndpoint = (endpoint) => {
        setConsoleEndpoint(endpoint);
        setConsoleOpen(true);
    };

    const fullBaseUrl = baseOrigin ? `${baseOrigin}${API_BASE_PATH}` : API_BASE_PATH;
    const isSsoMode = authMode === 'sso';

    return (
        <div className="min-h-screen bg-[#F5F7FA] text-slate-900">
            <Head title="Dokumentasi API v1" />

            {/* Sticky Top Navigation */}
            <header className="sticky top-0 z-40 border-b border-slate-200/80 bg-white/95 backdrop-blur">
                <div className="mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 py-3 sm:px-6 lg:px-8">
                    <div className="flex min-w-0 items-center gap-3">
                        <Link
                            href="/dashboard"
                            className="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50"
                        >
                            <ArrowLeft className="h-3.5 w-3.5" aria-hidden="true" />
                            <span className="hidden sm:inline">Dashboard</span>
                        </Link>

                        <div className="flex min-w-0 items-center gap-2.5">
                            <img
                                src="/images/logo-ui.webp"
                                alt="Logo Koperasi"
                                className="h-8 w-8 shrink-0 rounded-lg border border-slate-200 bg-white object-contain p-0.5"
                            />
                            <div className="min-w-0">
                                <div className="flex items-center gap-2">
                                    <span className="truncate text-sm font-bold tracking-tight text-brand-navy">
                                        Koperasi API Reference
                                    </span>
                                    <span className="rounded-md bg-brand-navy px-1.5 py-0.5 font-mono text-[10px] font-bold text-white">
                                        v1
                                    </span>
                                </div>
                                <p className="hidden truncate text-[11px] text-slate-500 sm:block">
                                    Mode Autentikasi:{' '}
                                    <span className="font-semibold text-brand-navy">
                                        {isSsoMode ? 'SSO Gate (PKCE)' : 'Lokal (Dev / no_karyawan)'}
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="flex items-center gap-2">
                        <div className="hidden items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1 md:flex">
                            <span className="text-[11px] font-medium text-slate-500">Base</span>
                            <code className="font-mono text-xs font-semibold text-brand-navy">
                                {fullBaseUrl}
                            </code>
                            <CopyButton text={fullBaseUrl} label="Salin" />
                        </div>

                        <button
                            type="button"
                            onClick={() => setConsoleOpen(true)}
                            className="inline-flex items-center gap-1.5 rounded-lg bg-brand-green px-3 py-1.5 text-xs font-bold text-white shadow-sm transition hover:bg-brand-green-dark"
                        >
                            <Terminal className="h-3.5 w-3.5" aria-hidden="true" />
                            <span>Konsol Uji</span>
                            {bearerToken && (
                                <span
                                    className="ml-0.5 h-2 w-2 rounded-full bg-emerald-200 ring-2 ring-white/40"
                                    title="Bearer token tersimpan"
                                />
                            )}
                        </button>
                    </div>
                </div>

                {/* Mobile Section Jump Bar */}
                <div className="flex gap-1.5 overflow-x-auto border-t border-slate-100 bg-slate-50/70 px-4 py-2 lg:hidden">
                    <button
                        type="button"
                        onClick={() => jumpToElement('ringkasan')}
                        className="shrink-0 rounded-full border border-slate-200 bg-white px-3 py-1 text-[11px] font-semibold text-slate-700"
                    >
                        Ringkasan
                    </button>
                    {ENDPOINT_GROUPS.map((group) => (
                        <button
                            key={group.id}
                            type="button"
                            onClick={() => jumpToElement(group.id)}
                            className="shrink-0 rounded-full border border-slate-200 bg-white px-3 py-1 text-[11px] font-medium text-slate-600 hover:text-brand-navy"
                        >
                            {group.name} ({group.endpoints.length})
                        </button>
                    ))}
                    <button
                        type="button"
                        onClick={() => jumpToElement('referensi-error')}
                        className="shrink-0 rounded-full border border-slate-200 bg-white px-3 py-1 text-[11px] font-semibold text-slate-700"
                    >
                        Error HTTP
                    </button>
                </div>
            </header>

            {/* Main Container */}
            <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="flex gap-8">
                    <ApiSidebar
                        groups={ENDPOINT_GROUPS}
                        activeSection={activeSection}
                        onJump={(id) => jumpToElement(id)}
                        onOpenConsole={() => setConsoleOpen(true)}
                        totalCount={allEndpoints.length}
                    />

                    <main className="min-w-0 flex-1 space-y-10">
                        {/* Hero Banner (Navy & Brand Green) */}
                        <section className="relative overflow-hidden rounded-3xl bg-brand-navy px-6 py-7 text-white shadow-lg sm:px-8 sm:py-9">
                            <div
                                aria-hidden="true"
                                className="pointer-events-none absolute -right-20 -top-24 h-72 w-72 rounded-full bg-brand-green/20 blur-3xl"
                            />
                            <div
                                aria-hidden="true"
                                className="pointer-events-none absolute -bottom-24 left-1/3 h-56 w-56 rounded-full bg-sky-500/10 blur-3xl"
                            />

                            <div className="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                                <div className="max-w-2xl space-y-3">
                                    <div className="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold text-emerald-300">
                                        <Lock className="h-3.5 w-3.5" aria-hidden="true" />
                                        <span>Terproteksi Auth • Koperasi Internal API v1</span>
                                    </div>
                                    <h1 className="text-2xl font-bold tracking-tight sm:text-3xl">
                                        Dokumentasi API Koperasi Mobile &amp; Pengurus
                                    </h1>
                                    <p className="text-sm leading-relaxed text-slate-300">
                                        Referensi lengkap seluruh {allEndpoints.length} endpoint aktif untuk
                                        aplikasi anggota, workflow persetujuan berjenjang pengurus, pengelolaan
                                        kas, laporan PDF, serta jembatan autentikasi SSO Gate (PKCE).
                                    </p>

                                    <div className="flex flex-wrap items-center gap-3 pt-1">
                                        <button
                                            type="button"
                                            onClick={() => openConsoleForEndpoint(ENDPOINT_GROUPS[0].endpoints[6])}
                                            className="inline-flex items-center gap-2 rounded-xl bg-brand-green px-4 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-brand-green-dark"
                                        >
                                            <Terminal className="h-4 w-4" aria-hidden="true" />
                                            <span>Buka Konsol Uji Sekarang</span>
                                        </button>
                                        <div className="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-3.5 py-2 text-xs text-slate-200">
                                            <KeyRound className="h-3.5 w-3.5 text-emerald-300" aria-hidden="true" />
                                            <span>
                                                {bearerToken
                                                    ? 'Token Sanctum aktif di konsol'
                                                    : 'Belum ada token tersimpan di konsol'}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div className="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-2">
                                    <div className="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                                        <p className="text-[11px] font-medium text-slate-400">Endpoint Aktif</p>
                                        <p className="mt-0.5 font-mono text-xl font-bold text-white">
                                            {allEndpoints.length}
                                        </p>
                                    </div>
                                    <div className="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                                        <p className="text-[11px] font-medium text-slate-400">Rate Limit</p>
                                        <p className="mt-0.5 font-mono text-xl font-bold text-emerald-300">
                                            60/m
                                        </p>
                                    </div>
                                    <div className="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                                        <p className="text-[11px] font-medium text-slate-400">Idempotensi</p>
                                        <p className="mt-0.5 font-mono text-xl font-bold text-white">24h</p>
                                    </div>
                                    <div className="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                                        <p className="text-[11px] font-medium text-slate-400">Approval</p>
                                        <p className="mt-0.5 font-mono text-xl font-bold text-white">
                                            3 Jenjang
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </section>

                        {/* Section: Ringkasan, Konvensi & Alur Integrasi */}
                        <section id="ringkasan" className="scroll-mt-24 space-y-6">
                            <div>
                                <h2 className="text-lg font-bold tracking-tight text-brand-navy">
                                    Konvensi &amp; Arsitektur API
                                </h2>
                                <p className="mt-1 text-xs text-slate-600">
                                    Aturan header, autentikasi Sanctum, kunci idempotensi, dan pembatasan trafik.
                                </p>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                {CONVENTIONS.map((item) => (
                                    <div
                                        key={item.title}
                                        className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"
                                    >
                                        <div className="flex items-center justify-between gap-2">
                                            <h3 className="text-xs font-bold uppercase tracking-wider text-slate-500">
                                                {item.title}
                                            </h3>
                                            <code className="rounded-md bg-brand-green-light px-2 py-0.5 font-mono text-[11px] font-bold text-brand-green-dark">
                                                {item.value}
                                            </code>
                                        </div>
                                        <p className="mt-2 text-xs leading-relaxed text-slate-600">
                                            {item.detail}
                                        </p>
                                    </div>
                                ))}
                            </div>

                            {/* 4 Kartu Alur Integrasi */}
                            <div className="space-y-3">
                                <div className="flex items-center gap-2">
                                    <Layers className="h-4 w-4 text-brand-green" aria-hidden="true" />
                                    <h3 className="text-sm font-bold text-brand-navy">
                                        Alur Integrasi Utama (Klik untuk lompat ke endpoint awal)
                                    </h3>
                                </div>
                                <div className="grid gap-4 md:grid-cols-2">
                                    {INTEGRATION_FLOWS.map((flow) => (
                                        <button
                                            key={flow.id}
                                            type="button"
                                            onClick={() => jumpToElement(flow.targetId, true)}
                                            className="group flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-4 text-left shadow-sm transition hover:border-brand-green/50 hover:shadow-md"
                                        >
                                            <div>
                                                <div className="flex items-center justify-between gap-2">
                                                    <span className="rounded-md bg-brand-navy px-2 py-0.5 font-mono text-[11px] font-bold text-white">
                                                        ALUR {flow.step}
                                                    </span>
                                                    <span className="inline-flex items-center gap-1 text-[11px] font-semibold text-brand-green transition group-hover:translate-x-0.5">
                                                        <span>Lihat Endpoint</span>
                                                        <ArrowRight className="h-3 w-3" aria-hidden="true" />
                                                    </span>
                                                </div>
                                                <h4 className="mt-2.5 text-sm font-bold text-brand-navy">
                                                    {flow.title}
                                                </h4>
                                                <p className="text-[11px] font-medium text-slate-500">
                                                    {flow.subtitle}
                                                </p>
                                                <ol className="mt-3 space-y-1.5">
                                                    {flow.steps.map((stepText, idx) => (
                                                        <li
                                                            key={idx}
                                                            className="flex items-start gap-2 text-xs leading-relaxed text-slate-600"
                                                        >
                                                            <CheckCircle2
                                                                className="mt-0.5 h-3.5 w-3.5 shrink-0 text-brand-green"
                                                                aria-hidden="true"
                                                            />
                                                            <span>{stepText}</span>
                                                        </li>
                                                    ))}
                                                </ol>
                                            </div>
                                        </button>
                                    ))}
                                </div>
                            </div>
                        </section>

                        {/* Search & Method Filter Toolbar */}
                        <section className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                            <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                <div className="relative flex-1">
                                    <Search
                                        className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                                        aria-hidden="true"
                                    />
                                    <input
                                        ref={searchInputRef}
                                        type="text"
                                        value={searchQuery}
                                        onChange={(e) => setSearchQuery(e.target.value)}
                                        placeholder="Cari path, deskripsi, atau permission... (tekan /)"
                                        className="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-9 pr-9 text-xs text-slate-900 placeholder:text-slate-400 focus:border-brand-green focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-green/20"
                                    />
                                    {searchQuery && (
                                        <button
                                            type="button"
                                            onClick={() => setSearchQuery('')}
                                            className="absolute right-2.5 top-1/2 -translate-y-1/2 rounded p-0.5 text-slate-400 hover:bg-slate-200 hover:text-slate-700"
                                            aria-label="Hapus pencarian"
                                        >
                                            <X className="h-3.5 w-3.5" />
                                        </button>
                                    )}
                                </div>

                                <div className="flex flex-wrap items-center justify-between gap-2">
                                    <div className="flex flex-wrap items-center gap-1">
                                        {METHOD_FILTERS.map((method) => {
                                            const active = methodFilter === method;
                                            return (
                                                <button
                                                    key={method}
                                                    type="button"
                                                    onClick={() => setMethodFilter(method)}
                                                    className={`inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 font-mono text-[11px] font-bold transition ${
                                                        active
                                                            ? 'bg-brand-navy text-white shadow-sm'
                                                            : 'bg-slate-100 text-slate-600 hover:bg-slate-200 hover:text-slate-900'
                                                    }`}
                                                >
                                                    <span>{method}</span>
                                                    <span
                                                        className={`rounded px-1 text-[10px] ${
                                                            active
                                                                ? 'bg-white/20 text-white'
                                                                : 'bg-white text-slate-500'
                                                        }`}
                                                    >
                                                        {methodCounts[method]}
                                                    </span>
                                                </button>
                                            );
                                        })}
                                    </div>

                                    <div className="flex items-center gap-1.5 border-l border-slate-200 pl-2">
                                        <button
                                            type="button"
                                            onClick={expandAllVisible}
                                            className="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[11px] font-semibold text-slate-600 hover:bg-slate-50"
                                            title="Buka semua detail endpoint yang tampil"
                                        >
                                            <ChevronsUpDown className="h-3.5 w-3.5" />
                                            <span className="hidden sm:inline">Buka Semua</span>
                                        </button>
                                        <button
                                            type="button"
                                            onClick={collapseAll}
                                            className="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[11px] font-semibold text-slate-600 hover:bg-slate-50"
                                            title="Tutup seluruh kartu endpoint"
                                        >
                                            <ChevronsDownUp className="h-3.5 w-3.5" />
                                            <span className="hidden sm:inline">Tutup</span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <p className="mt-2.5 text-[11px] text-slate-500">
                                Menampilkan{' '}
                                <span className="font-mono font-bold text-brand-navy">
                                    {visibleEndpointCount}
                                </span>{' '}
                                dari{' '}
                                <span className="font-mono font-semibold text-slate-700">
                                    {allEndpoints.length}
                                </span>{' '}
                                endpoint.
                            </p>
                        </section>

                        {/* Endpoint Groups */}
                        {filteredGroups.length > 0 ? (
                            <div className="space-y-10">
                                {filteredGroups.map((group) => (
                                    <section
                                        key={group.id}
                                        id={group.id}
                                        className="scroll-mt-24 space-y-3.5"
                                    >
                                        <div className="flex flex-col gap-1 border-b border-slate-200 pb-3 sm:flex-row sm:items-end sm:justify-between">
                                            <div>
                                                <h2 className="text-lg font-bold tracking-tight text-brand-navy">
                                                    {group.name}
                                                </h2>
                                                <p className="text-xs text-slate-600">{group.description}</p>
                                            </div>
                                            <span className="inline-flex w-fit items-center rounded-full border border-slate-200 bg-white px-2.5 py-0.5 font-mono text-[11px] font-semibold text-slate-600">
                                                {group.endpoints.length} endpoint
                                            </span>
                                        </div>

                                        <div className="space-y-3">
                                            {group.endpoints.map((endpoint) => (
                                                <EndpointCard
                                                    key={endpoint.id}
                                                    endpoint={endpoint}
                                                    baseOrigin={baseOrigin}
                                                    isExpanded={expandedIds.has(endpoint.id)}
                                                    onToggle={() => toggleCard(endpoint.id)}
                                                    onTry={openConsoleForEndpoint}
                                                />
                                            ))}
                                        </div>
                                    </section>
                                ))}
                            </div>
                        ) : (
                            <div className="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center">
                                <p className="text-sm font-bold text-brand-navy">
                                    Tidak ada endpoint yang cocok dengan filter
                                </p>
                                <p className="mt-1 text-xs text-slate-500">
                                    Coba kata kunci lain atau ubah filter HTTP method ke SEMUA.
                                </p>
                                <button
                                    type="button"
                                    onClick={() => {
                                        setSearchQuery('');
                                        setMethodFilter('SEMUA');
                                    }}
                                    className="mt-4 inline-flex items-center gap-1.5 rounded-lg bg-brand-navy px-4 py-2 text-xs font-semibold text-white hover:bg-brand-navy-light"
                                >
                                    Reset Pencarian &amp; Filter
                                </button>
                            </div>
                        )}

                        {/* Referensi Error HTTP */}
                        <section id="referensi-error" className="scroll-mt-24 space-y-5">
                            <div className="border-b border-slate-200 pb-3">
                                <h2 className="text-lg font-bold tracking-tight text-brand-navy">
                                    Kode Status &amp; Format Error JSON
                                </h2>
                                <p className="mt-1 text-xs text-slate-600">
                                    Seluruh route di bawah prefix <code className="font-mono">/api/*</code> selalu
                                    mengembalikan JSON ketika terjadi validasi gagal atau exception.
                                </p>
                            </div>

                            <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                                <div className="divide-y divide-slate-100">
                                    {ERROR_CODES.map((err) => (
                                        <div
                                            key={err.code}
                                            className="flex flex-col gap-1.5 px-5 py-3.5 sm:flex-row sm:items-center sm:gap-5"
                                        >
                                            <span className="inline-flex w-16 shrink-0 items-center justify-center rounded-md border border-slate-200 bg-slate-50 px-2 py-0.5 font-mono text-xs font-bold text-brand-navy">
                                                {err.code}
                                            </span>
                                            <span className="w-56 shrink-0 text-xs font-bold text-slate-800">
                                                {err.title}
                                            </span>
                                            <p className="text-xs text-slate-600">{err.description}</p>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            <div className="grid gap-4 lg:grid-cols-3">
                                <CodeBlock
                                    title="Validasi Gagal"
                                    badge="HTTP 422"
                                    code={SAMPLE_VALIDATION_ERROR}
                                />
                                <CodeBlock
                                    title="Bentrok Status Approval"
                                    badge="HTTP 409"
                                    code={SAMPLE_CONFLICT_ERROR}
                                />
                                <CodeBlock
                                    title="Token Invalid / Kosong"
                                    badge="HTTP 401"
                                    code={SAMPLE_AUTH_ERROR}
                                />
                            </div>
                        </section>

                        {/* Footer */}
                        <footer className="flex flex-col items-start justify-between gap-2 border-t border-slate-200 pt-6 text-xs text-slate-500 sm:flex-row sm:items-center">
                            <p>
                                Koperasi Internal API Documentation • Terlindungi autentikasi sesi &amp;{' '}
                                <code className="font-mono">X-Robots-Tag: noindex</code>
                            </p>
                            <Link
                                href="/dashboard"
                                className="font-semibold text-brand-navy hover:text-brand-green"
                            >
                                Kembali ke Dashboard &rarr;
                            </Link>
                        </footer>
                    </main>
                </div>
            </div>

            <TryConsoleDrawer
                show={consoleOpen}
                onClose={() => setConsoleOpen(false)}
                endpoint={consoleEndpoint}
                token={bearerToken}
                onTokenChange={handleTokenChange}
            />
        </div>
    );
}
