import {
    Dialog,
    DialogPanel,
    Transition,
    TransitionChild,
} from '@headlessui/react';
import {
    AlertCircle,
    CheckCircle2,
    Download,
    Eye,
    EyeOff,
    KeyRound,
    Play,
    RefreshCw,
    RotateCcw,
    Terminal,
    Trash2,
    X,
} from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { resolveEndpointPath } from '../../Pages/ApiDocs/apiSpec';
import CopyButton from './CopyButton';

const HTTP_METHODS = ['GET', 'POST', 'PUT', 'DELETE'];

function generateUuidV4() {
    if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
        return crypto.randomUUID();
    }

    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (char) => {
        const rand = (Math.random() * 16) | 0;
        const val = char === 'x' ? rand : (rand & 0x3) | 0x8;
        return val.toString(16);
    });
}

function getXsrfTokenFromCookie() {
    if (typeof document === 'undefined') {
        return '';
    }
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}

function extractPathParams(pathTemplate) {
    const matches = [...pathTemplate.matchAll(/\{([^}]+)\}/g)];
    return [...new Set(matches.map((m) => m[1]))];
}

function serializeQueryObject(queryObj) {
    if (!queryObj || typeof queryObj !== 'object') {
        return '';
    }
    const params = new URLSearchParams();
    Object.entries(queryObj).forEach(([key, val]) => {
        if (val !== undefined && val !== null && val !== '') {
            params.set(key, String(val));
        }
    });
    return params.toString();
}

function formatBytes(bytes) {
    if (!Number.isFinite(bytes) || bytes <= 0) {
        return '0 B';
    }
    if (bytes < 1024) {
        return `${bytes} B`;
    }
    return `${(bytes / 1024).toFixed(2)} KB`;
}

/**
 * Panel slide-over interaktif (Try-It Console) untuk menguji endpoint API v1 langsung dari browser.
 */
export default function TryConsoleDrawer({
    show,
    onClose,
    endpoint,
    token,
    onTokenChange,
}) {
    const [method, setMethod] = useState('GET');
    const [pathTemplate, setPathTemplate] = useState('/api/v1/me');
    const [pathValues, setPathValues] = useState({});
    const [queryString, setQueryString] = useState('');
    const [bodyText, setBodyText] = useState('');
    const [idempotencyKey, setIdempotencyKey] = useState('');
    const [showToken, setShowToken] = useState(false);

    const [loading, setLoading] = useState(false);
    const [errorMsg, setErrorMsg] = useState('');
    const [tokenCapturedNotice, setTokenCapturedNotice] = useState(false);
    const [result, setResult] = useState(null);
    const [blobDownloadUrl, setBlobDownloadUrl] = useState(null);

    // Sinkronkan formulir setiap kali endpoint yang dipilih berubah atau drawer dibuka
    useEffect(() => {
        if (!endpoint) {
            return;
        }

        const resolvedPath = resolveEndpointPath(endpoint);
        const paramNames = extractPathParams(resolvedPath);
        const defaults = {};

        paramNames.forEach((name) => {
            if (name === 'slug') {
                defaults[name] = 'pinjaman-per-status';
            } else if (name === 'id' && endpoint.path?.includes('/notifications')) {
                defaults[name] = '9f86d081884c7d659a2feaa0c55ad015';
            } else {
                defaults[name] = '1';
            }
        });

        setMethod(endpoint.method || 'GET');
        setPathTemplate(resolvedPath);
        setPathValues(defaults);
        setQueryString(serializeQueryObject(endpoint.query));
        setBodyText(
            endpoint.body !== undefined
                ? JSON.stringify(endpoint.body, null, 2)
                : '',
        );
        setIdempotencyKey(endpoint.idempotent ? generateUuidV4() : '');
        setErrorMsg('');
        setTokenCapturedNotice(false);
        setResult(null);
    }, [endpoint]);

    useEffect(() => {
        return () => {
            if (blobDownloadUrl) {
                URL.revokeObjectURL(blobDownloadUrl);
            }
        };
    }, [blobDownloadUrl]);

    const pathParams = useMemo(() => extractPathParams(pathTemplate), [pathTemplate]);

    const finalUrl = useMemo(() => {
        let built = pathTemplate;
        pathParams.forEach((paramName) => {
            const rawVal = pathValues[paramName];
            const replacement = rawVal ? encodeURIComponent(rawVal) : `{${paramName}}`;
            built = built.replace(`{${paramName}}`, replacement);
        });

        const cleanQuery = queryString.trim().replace(/^\?/, '');
        return cleanQuery ? `${built}?${cleanQuery}` : built;
    }, [pathTemplate, pathParams, pathValues, queryString]);

    const handleFormatJson = () => {
        if (!bodyText.trim()) {
            return;
        }
        try {
            const parsed = JSON.parse(bodyText);
            setBodyText(JSON.stringify(parsed, null, 2));
            setErrorMsg('');
        } catch (err) {
            setErrorMsg(`JSON tidak valid: ${err.message}`);
        }
    };

    const handleResetDefaults = () => {
        if (!endpoint) {
            return;
        }
        setMethod(endpoint.method || 'GET');
        setPathTemplate(resolveEndpointPath(endpoint));
        setQueryString(serializeQueryObject(endpoint.query));
        setBodyText(
            endpoint.body !== undefined
                ? JSON.stringify(endpoint.body, null, 2)
                : '',
        );
        setIdempotencyKey(endpoint.idempotent ? generateUuidV4() : '');
        setErrorMsg('');
    };

    const handleSendRequest = async (event) => {
        event.preventDefault();
        setErrorMsg('');
        setTokenCapturedNotice(false);

        if (/\{[^}]+\}/.test(finalUrl)) {
            setErrorMsg('Lengkapi seluruh parameter path ({...}) sebelum mengirim request.');
            return;
        }

        const hasBodyMethod = ['POST', 'PUT', 'PATCH', 'DELETE'].includes(method);
        let parsedBodyPayload = undefined;
        if (hasBodyMethod && bodyText.trim() !== '') {
            try {
                parsedBodyPayload = JSON.stringify(JSON.parse(bodyText));
            } catch (err) {
                setErrorMsg(`Format JSON request body tidak valid: ${err.message}`);
                return;
            }
        }

        if (blobDownloadUrl) {
            URL.revokeObjectURL(blobDownloadUrl);
            setBlobDownloadUrl(null);
        }

        const headers = {
            Accept: endpoint?.binaryResponse ? 'application/pdf, application/json' : 'application/json',
        };

        if (parsedBodyPayload !== undefined) {
            headers['Content-Type'] = 'application/json';
        }

        if (token.trim()) {
            headers.Authorization = `Bearer ${token.trim()}`;
        }

        if (idempotencyKey.trim()) {
            headers['Idempotency-Key'] = idempotencyKey.trim();
        }

        const xsrf = getXsrfTokenFromCookie();
        if (xsrf && hasBodyMethod) {
            headers['X-XSRF-TOKEN'] = xsrf;
        }

        const startedAt = performance.now();
        setLoading(true);

        try {
            const response = await fetch(finalUrl, {
                method,
                headers,
                body: parsedBodyPayload,
                credentials: 'same-origin',
                redirect: endpoint?.statusCode === 302 ? 'manual' : 'follow',
            });

            const elapsedMs = Math.round(performance.now() - startedAt);
            const contentType = response.headers.get('content-type') || '';
            const replayHeader = response.headers.get('x-idempotency-replay');
            const cacheControl = response.headers.get('cache-control');
            const rateRemaining = response.headers.get('x-ratelimit-remaining');

            if (response.type === 'opaqueredirect' || (response.status >= 300 && response.status < 400)) {
                setResult({
                    status: response.status || 302,
                    statusText: 'Redirect (302)',
                    durationMs: elapsedMs,
                    sizeBytes: 0,
                    contentType: 'redirect',
                    isOpaqueRedirect: true,
                    headers: { replayHeader, cacheControl, rateRemaining },
                    bodyText:
                        'Endpoint mengembalikan HTTP 302 Redirect (mis. menuju Gate SSO IdP). Karena batasan CORS redirect browser pada fetch(), gunakan tab browser langsung untuk menguji alur PKCE.',
                });
                return;
            }

            if (contentType.includes('application/pdf') || contentType.includes('application/octet-stream')) {
                const blob = await response.blob();
                const objUrl = URL.createObjectURL(blob);
                setBlobDownloadUrl(objUrl);

                setResult({
                    status: response.status,
                    statusText: response.statusText || 'OK',
                    durationMs: elapsedMs,
                    sizeBytes: blob.size,
                    contentType,
                    isBinary: true,
                    headers: { replayHeader, cacheControl, rateRemaining },
                    bodyText: `Respons biner diterima (${contentType}, ${formatBytes(blob.size)}). Klik tombol Unduh File di bawah untuk membuka hasil.`,
                });
                return;
            }

            const rawText = await response.text();
            const sizeBytes = new Blob([rawText]).size;
            let formattedBody = rawText;

            if (contentType.includes('application/json') || rawText.trim().startsWith('{') || rawText.trim().startsWith('[')) {
                try {
                    const parsedJson = JSON.parse(rawText);
                    formattedBody = JSON.stringify(parsedJson, null, 2);

                    if (
                        response.ok &&
                        typeof parsedJson?.token === 'string' &&
                        parsedJson.token.length > 0 &&
                        (finalUrl.includes('/login') ||
                            finalUrl.includes('/sso/mobile/exchange') ||
                            finalUrl.includes('/sso/token'))
                    ) {
                        onTokenChange(parsedJson.token);
                        setTokenCapturedNotice(true);
                    }
                } catch {
                    // Biarkan rawText apa adanya bila JSON gagal diparse
                }
            }

            setResult({
                status: response.status,
                statusText: response.statusText || '',
                durationMs: elapsedMs,
                sizeBytes,
                contentType,
                isBinary: false,
                headers: { replayHeader, cacheControl, rateRemaining },
                bodyText: formattedBody || '(empty response body)',
            });
        } catch (err) {
            setErrorMsg(`Gagal menghubungi server: ${err.message}`);
        } finally {
            setLoading(false);
        }
    };

    const statusBadgeClass = useMemo(() => {
        if (!result) return '';
        if (result.status >= 200 && result.status < 300) {
            return 'border-brand-green/30 bg-brand-green-light text-brand-green-dark';
        }
        if (result.status >= 300 && result.status < 400) {
            return 'border-sky-200 bg-sky-50 text-sky-700';
        }
        if (result.status >= 400 && result.status < 500) {
            return 'border-amber-200 bg-amber-50 text-amber-800';
        }
        return 'border-rose-200 bg-rose-50 text-rose-700';
    }, [result]);

    return (
        <Transition show={show} leave="duration-200">
            <Dialog as="div" className="fixed inset-0 z-50 overflow-hidden" onClose={onClose}>
                <TransitionChild
                    enter="ease-out duration-200"
                    enterFrom="opacity-0"
                    enterTo="opacity-100"
                    leave="ease-in duration-150"
                    leaveFrom="opacity-100"
                    leaveTo="opacity-0"
                >
                    <div className="absolute inset-0 bg-brand-navy/60 backdrop-blur-[1px]" />
                </TransitionChild>

                <div className="fixed inset-0 flex justify-end">
                    <TransitionChild
                        enter="ease-out duration-250"
                        enterFrom="translate-x-full"
                        enterTo="translate-x-0"
                        leave="ease-in duration-200"
                        leaveFrom="translate-x-0"
                        leaveTo="translate-x-full"
                    >
                        <DialogPanel className="relative flex h-full w-full max-w-2xl flex-col bg-white shadow-2xl">
                            {/* Header Konsol */}
                            <div className="flex items-center justify-between border-b border-white/10 bg-brand-navy px-5 py-4 text-white">
                                <div className="flex min-w-0 items-center gap-3">
                                    <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-green/20 text-emerald-300 ring-1 ring-brand-green/40">
                                        <Terminal className="h-4 w-4" aria-hidden="true" />
                                    </div>
                                    <div className="min-w-0">
                                        <h2 className="truncate text-sm font-bold tracking-tight">
                                            Konsol Uji API v1
                                        </h2>
                                        <p className="truncate text-xs text-slate-300">
                                            {endpoint?.summary || 'Kirim request HTTP langsung ke server aktif'}
                                        </p>
                                    </div>
                                </div>

                                <div className="flex items-center gap-2">
                                    {endpoint && (
                                        <button
                                            type="button"
                                            onClick={handleResetDefaults}
                                            className="inline-flex items-center gap-1 rounded-md border border-white/15 bg-white/5 px-2.5 py-1.5 text-xs font-medium text-slate-200 transition hover:bg-white/10"
                                            title="Kembalikan payload bawaan endpoint"
                                        >
                                            <RotateCcw className="h-3.5 w-3.5" aria-hidden="true" />
                                            <span className="hidden sm:inline">Reset</span>
                                        </button>
                                    )}
                                    <button
                                        type="button"
                                        onClick={onClose}
                                        className="rounded-lg p-1.5 text-slate-300 transition hover:bg-white/10 hover:text-white"
                                        aria-label="Tutup Konsol"
                                    >
                                        <X className="h-5 w-5" />
                                    </button>
                                </div>
                            </div>

                            {/* Isi Konsol */}
                            <form
                                onSubmit={handleSendRequest}
                                className="flex-1 space-y-5 overflow-y-auto p-5"
                            >
                                {/* Bearer Token Manager */}
                                <div className="rounded-xl border border-slate-200 bg-slate-50/80 p-3.5">
                                    <div className="mb-2 flex items-center justify-between gap-2">
                                        <label
                                            htmlFor="console-bearer-token"
                                            className="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-700"
                                        >
                                            <KeyRound className="h-3.5 w-3.5 text-brand-green" aria-hidden="true" />
                                            <span>Authorization Bearer Token (sessionStorage)</span>
                                        </label>
                                        <div className="flex items-center gap-1.5">
                                            <button
                                                type="button"
                                                onClick={() => setShowToken((prev) => !prev)}
                                                className="inline-flex items-center gap-1 rounded border border-slate-200 bg-white px-2 py-0.5 text-[11px] font-medium text-slate-600 hover:bg-slate-100"
                                            >
                                                {showToken ? (
                                                    <>
                                                        <EyeOff className="h-3 w-3" />
                                                        <span>Sembunyikan</span>
                                                    </>
                                                ) : (
                                                    <>
                                                        <Eye className="h-3 w-3" />
                                                        <span>Tampilkan</span>
                                                    </>
                                                )}
                                            </button>
                                            {token && (
                                                <button
                                                    type="button"
                                                    onClick={() => onTokenChange('')}
                                                    className="inline-flex items-center gap-1 rounded border border-rose-200 bg-white px-2 py-0.5 text-[11px] font-medium text-rose-600 hover:bg-rose-50"
                                                >
                                                    <Trash2 className="h-3 w-3" />
                                                    <span>Hapus</span>
                                                </button>
                                            )}
                                        </div>
                                    </div>
                                    <input
                                        id="console-bearer-token"
                                        type={showToken ? 'text' : 'password'}
                                        value={token}
                                        onChange={(e) => onTokenChange(e.target.value)}
                                        placeholder="Tempel Bearer token Sanctum atau jalankan /api/v1/login atau /api/v1/sso/token"
                                        className="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 font-mono text-xs text-slate-900 placeholder:text-slate-400 focus:border-brand-green focus:outline-none focus:ring-2 focus:ring-brand-green/20"
                                    />
                                    <p className="mt-1.5 text-[11px] text-slate-500">
                                        Disimpan hanya di sessionStorage tab ini. Untuk{' '}
                                        <code className="font-mono text-slate-700">POST /api/v1/sso/token</code>, header{' '}
                                        <code className="font-mono text-slate-700">X-XSRF-TOKEN</code> &amp; cookie sesi dikirim otomatis.
                                    </p>
                                </div>

                                {/* Method + Path */}
                                <div>
                                    <label className="mb-1.5 block text-xs font-semibold text-slate-700">
                                        Method &amp; Endpoint URL
                                    </label>
                                    <div className="flex flex-col gap-2 sm:flex-row">
                                        <select
                                            value={method}
                                            onChange={(e) => setMethod(e.target.value)}
                                            aria-label="HTTP Method"
                                            className="rounded-lg border border-slate-300 bg-white px-3 py-2 font-mono text-xs font-bold text-brand-navy focus:border-brand-green focus:outline-none focus:ring-2 focus:ring-brand-green/20 sm:w-28"
                                        >
                                            {HTTP_METHODS.map((m) => (
                                                <option key={m} value={m}>
                                                    {m}
                                                </option>
                                            ))}
                                        </select>
                                        <input
                                            type="text"
                                            value={pathTemplate}
                                            onChange={(e) => setPathTemplate(e.target.value)}
                                            aria-label="Path Template"
                                            className="flex-1 rounded-lg border border-slate-300 bg-white px-3 py-2 font-mono text-xs text-slate-900 focus:border-brand-green focus:outline-none focus:ring-2 focus:ring-brand-green/20"
                                        />
                                    </div>
                                    <div className="mt-1.5 flex flex-wrap items-center gap-1.5 text-[11px] text-slate-500">
                                        <span>Target URL:</span>
                                        <code className="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-slate-800">
                                            {finalUrl}
                                        </code>
                                    </div>
                                </div>

                                {/* Dynamic Path Params */}
                                {pathParams.length > 0 && (
                                    <div className="rounded-xl border border-slate-200 p-3.5">
                                        <p className="mb-2.5 text-xs font-semibold text-slate-700">
                                            Parameter Path ({pathParams.length})
                                        </p>
                                        <div className="grid gap-3 sm:grid-cols-2">
                                            {pathParams.map((paramName) => {
                                                const specParam = endpoint?.params?.find(
                                                    (p) => p.in === 'path' && p.name === paramName,
                                                );
                                                return (
                                                    <div key={paramName}>
                                                        <label
                                                            htmlFor={`path-param-${paramName}`}
                                                            className="mb-1 flex items-center justify-between text-xs font-medium text-slate-700"
                                                        >
                                                            <span className="font-mono">{`{${paramName}}`}</span>
                                                            {specParam?.type && (
                                                                <span className="text-[10px] text-slate-400">
                                                                    {specParam.type}
                                                                </span>
                                                            )}
                                                        </label>
                                                        <input
                                                            id={`path-param-${paramName}`}
                                                            type="text"
                                                            value={pathValues[paramName] ?? ''}
                                                            onChange={(e) =>
                                                                setPathValues((prev) => ({
                                                                    ...prev,
                                                                    [paramName]: e.target.value,
                                                                }))
                                                            }
                                                            className="w-full rounded-lg border border-slate-300 px-2.5 py-1.5 font-mono text-xs text-slate-900 focus:border-brand-green focus:outline-none focus:ring-2 focus:ring-brand-green/20"
                                                        />
                                                    </div>
                                                );
                                            })}
                                        </div>
                                    </div>
                                )}

                                {/* Query String */}
                                <div>
                                    <label
                                        htmlFor="console-query-string"
                                        className="mb-1.5 block text-xs font-semibold text-slate-700"
                                    >
                                        Query String (opsional, tanpa tanda ?)
                                    </label>
                                    <input
                                        id="console-query-string"
                                        type="text"
                                        value={queryString}
                                        onChange={(e) => setQueryString(e.target.value)}
                                        placeholder="contoh: status=aktif&per_page=10"
                                        className="w-full rounded-lg border border-slate-300 px-3 py-2 font-mono text-xs text-slate-900 placeholder:text-slate-400 focus:border-brand-green focus:outline-none focus:ring-2 focus:ring-brand-green/20"
                                    />
                                </div>

                                {/* Idempotency-Key */}
                                {(endpoint?.idempotent || ['POST', 'PUT', 'DELETE'].includes(method)) && (
                                    <div>
                                        <div className="mb-1.5 flex items-center justify-between gap-2">
                                            <label
                                                htmlFor="console-idempotency-key"
                                                className="text-xs font-semibold text-slate-700"
                                            >
                                                Idempotency-Key (UUID v4{' '}
                                                {endpoint?.idempotent ? 'didukung route ini' : 'opsional'})
                                            </label>
                                            <div className="flex items-center gap-1.5">
                                                <button
                                                    type="button"
                                                    onClick={() => setIdempotencyKey(generateUuidV4())}
                                                    className="inline-flex items-center gap-1 rounded border border-slate-200 bg-slate-50 px-2 py-0.5 text-[11px] font-medium text-slate-700 hover:bg-slate-100"
                                                >
                                                    <RefreshCw className="h-3 w-3" />
                                                    <span>Generate UUID</span>
                                                </button>
                                                {idempotencyKey && (
                                                    <button
                                                        type="button"
                                                        onClick={() => setIdempotencyKey('')}
                                                        className="rounded border border-slate-200 px-2 py-0.5 text-[11px] text-slate-500 hover:bg-slate-100"
                                                    >
                                                        Kosongkan
                                                    </button>
                                                )}
                                            </div>
                                        </div>
                                        <input
                                            id="console-idempotency-key"
                                            type="text"
                                            value={idempotencyKey}
                                            onChange={(e) => setIdempotencyKey(e.target.value)}
                                            placeholder="UUID v4 untuk mencegah eksekusi ganda"
                                            className="w-full rounded-lg border border-slate-300 px-3 py-2 font-mono text-xs text-slate-900 placeholder:text-slate-400 focus:border-brand-green focus:outline-none focus:ring-2 focus:ring-brand-green/20"
                                        />
                                    </div>
                                )}

                                {/* JSON Request Body */}
                                {['POST', 'PUT', 'PATCH', 'DELETE'].includes(method) && (
                                    <div>
                                        <div className="mb-1.5 flex items-center justify-between">
                                            <label
                                                htmlFor="console-json-body"
                                                className="text-xs font-semibold text-slate-700"
                                            >
                                                Request Body (JSON)
                                            </label>
                                            <button
                                                type="button"
                                                onClick={handleFormatJson}
                                                className="rounded border border-slate-200 bg-slate-50 px-2 py-0.5 text-[11px] font-medium text-slate-600 hover:bg-slate-100"
                                            >
                                                Rapikan JSON
                                            </button>
                                        </div>
                                        <textarea
                                            id="console-json-body"
                                            rows={7}
                                            value={bodyText}
                                            onChange={(e) => setBodyText(e.target.value)}
                                            placeholder='{\n  "key": "value"\n}'
                                            className="w-full rounded-xl border border-slate-300 bg-[#0A1526] p-3 font-mono text-xs leading-relaxed text-slate-100 placeholder:text-slate-500 focus:border-brand-green focus:outline-none focus:ring-2 focus:ring-brand-green/30"
                                        />
                                    </div>
                                )}

                                {/* Alert Error */}
                                {errorMsg && (
                                    <div className="flex items-start gap-2 rounded-lg border border-rose-200 bg-rose-50 px-3.5 py-2.5 text-xs text-rose-700">
                                        <AlertCircle className="mt-0.5 h-4 w-4 shrink-0" />
                                        <span>{errorMsg}</span>
                                    </div>
                                )}

                                {/* Token Captured Notice */}
                                {tokenCapturedNotice && (
                                    <div className="flex items-start gap-2 rounded-lg border border-brand-green/30 bg-brand-green-light px-3.5 py-2.5 text-xs text-brand-green-dark">
                                        <CheckCircle2 className="mt-0.5 h-4 w-4 shrink-0" />
                                        <span>
                                            Token Sanctum dari respons berhasil disimpan otomatis ke konsol &amp; sessionStorage.
                                        </span>
                                    </div>
                                )}

                                {/* Tombol Aksi */}
                                <div className="flex flex-wrap items-center gap-2.5">
                                    <button
                                        type="submit"
                                        disabled={loading}
                                        className="inline-flex items-center gap-2 rounded-lg bg-brand-green px-4 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-brand-green-dark disabled:cursor-not-allowed disabled:opacity-60"
                                    >
                                        <Play className="h-3.5 w-3.5 fill-current" />
                                        <span>{loading ? 'Mengirim Request...' : 'Kirim Request'}</span>
                                    </button>
                                    {endpoint?.statusCode === 302 && (
                                        <a
                                            href={finalUrl}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                                        >
                                            Buka URL di Tab Baru
                                        </a>
                                    )}
                                </div>

                                {/* Hasil Respons */}
                                {result && (
                                    <div className="space-y-2.5 border-t border-slate-200 pt-4">
                                        <div className="flex flex-wrap items-center justify-between gap-2">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <span
                                                    className={`inline-flex items-center rounded-md border px-2.5 py-0.5 font-mono text-xs font-bold ${statusBadgeClass}`}
                                                >
                                                    HTTP {result.status} {result.statusText}
                                                </span>
                                                <span className="rounded bg-slate-100 px-2 py-0.5 font-mono text-[11px] text-slate-600">
                                                    {result.durationMs} ms
                                                </span>
                                                <span className="rounded bg-slate-100 px-2 py-0.5 font-mono text-[11px] text-slate-600">
                                                    {formatBytes(result.sizeBytes)}
                                                </span>
                                                {result.headers?.replayHeader && (
                                                    <span className="rounded border border-sky-200 bg-sky-50 px-2 py-0.5 font-mono text-[11px] font-medium text-sky-700">
                                                        X-Idempotency-Replay: {result.headers.replayHeader}
                                                    </span>
                                                )}
                                            </div>
                                            {!result.isBinary && (
                                                <CopyButton text={result.bodyText} label="Salin Respons" />
                                            )}
                                        </div>

                                        {result.isBinary && blobDownloadUrl && (
                                            <div>
                                                <a
                                                    href={blobDownloadUrl}
                                                    download={`${pathValues.slug || 'laporan'}.pdf`}
                                                    className="inline-flex items-center gap-2 rounded-lg bg-brand-navy px-3.5 py-2 text-xs font-semibold text-white transition hover:bg-brand-navy-light"
                                                >
                                                    <Download className="h-3.5 w-3.5" />
                                                    <span>Unduh File PDF ({formatBytes(result.sizeBytes)})</span>
                                                </a>
                                            </div>
                                        )}

                                        <pre className="max-h-96 overflow-x-auto overflow-y-auto rounded-xl border border-brand-navy-light bg-[#0A1526] p-4 font-mono text-xs leading-relaxed text-slate-100">
                                            <code>{result.bodyText}</code>
                                        </pre>
                                    </div>
                                )}
                            </form>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </Dialog>
        </Transition>
    );
}
