import { ChevronDown, Play, Shield, Sparkles } from 'lucide-react';
import { useMemo } from 'react';
import { resolveEndpointPath } from '../../Pages/ApiDocs/apiSpec';
import CodeBlock from './CodeBlock';
import CopyButton from './CopyButton';

const METHOD_BADGE_STYLES = {
    GET: 'border-brand-green/30 bg-brand-green-light text-brand-green-dark',
    POST: 'border-sky-200 bg-sky-50 text-sky-800',
    PUT: 'border-amber-200 bg-amber-50 text-amber-800',
    DELETE: 'border-rose-200 bg-rose-50 text-rose-700',
};

const PARAM_LOCATION_LABELS = {
    path: 'path',
    query: 'query',
    body: 'body',
};

function renderHighlightedPath(path) {
    const parts = path.split(/(\{[^}]+\})/g);
    return parts.map((part, idx) => {
        if (/^\{[^}]+\}$/.test(part)) {
            return (
                <span
                    key={`${part}-${idx}`}
                    className="mx-0.5 rounded bg-amber-100/80 px-1 py-0.5 text-amber-900"
                >
                    {part}
                </span>
            );
        }
        return <span key={`${part}-${idx}`}>{part}</span>;
    });
}

function buildUrlWithSampleQuery(endpoint, resolvedPath) {
    if (!endpoint.query || Object.keys(endpoint.query).length === 0) {
        return resolvedPath;
    }
    const params = new URLSearchParams();
    Object.entries(endpoint.query).forEach(([k, v]) => {
        if (v !== undefined && v !== null) {
            params.set(k, String(v));
        }
    });
    return `${resolvedPath}?${params.toString()}`;
}

function buildRequestTabs(endpoint, baseOrigin) {
    const resolvedPath = resolveEndpointPath(endpoint);
    const fullUrl = `${baseOrigin}${buildUrlWithSampleQuery(endpoint, resolvedPath)}`;
    const needsBearer = endpoint.auth === 'Bearer Token';
    const isWebCsrf = endpoint.auth === 'Web Session + CSRF';
    const hasBody = endpoint.body !== undefined && endpoint.method !== 'GET';

    const curlLines = [`curl -X ${endpoint.method} "${fullUrl}"`];
    curlLines.push('  -H "Accept: application/json"');
    if (needsBearer) {
        curlLines.push('  -H "Authorization: Bearer $TOKEN"');
    }
    if (isWebCsrf) {
        curlLines.push('  -b cookies.txt -H "X-XSRF-TOKEN: $XSRF_TOKEN"');
    }
    if (endpoint.idempotent) {
        curlLines.push('  -H "Idempotency-Key: 550e8400-e29b-41d4-a716-446655440000"');
    }
    if (hasBody) {
        curlLines.push('  -H "Content-Type: application/json"');
        curlLines.push(`  -d '${JSON.stringify(endpoint.body)}'`);
    }

    const jsHeaders = {
        Accept: endpoint.binaryResponse ? 'application/pdf' : 'application/json',
    };
    if (needsBearer) {
        jsHeaders.Authorization = 'Bearer YOUR_SANCTUM_TOKEN';
    }
    if (endpoint.idempotent) {
        jsHeaders['Idempotency-Key'] = 'crypto.randomUUID()';
    }
    if (hasBody) {
        jsHeaders['Content-Type'] = 'application/json';
    }

    const jsOptions = [`  method: '${endpoint.method}',`];
    if (isWebCsrf) {
        jsOptions.push("  credentials: 'same-origin',");
    }
    jsOptions.push(`  headers: ${JSON.stringify(jsHeaders, null, 4).replace(/\n/g, '\n  ')},`);
    if (hasBody) {
        jsOptions.push(
            `  body: JSON.stringify(${JSON.stringify(endpoint.body, null, 4).replace(/\n/g, '\n  ')}),`,
        );
    }

    const jsSnippet = `const res = await fetch('${fullUrl}', {\n${jsOptions.join('\n')}\n});\nconst data = await res.${endpoint.binaryResponse ? 'blob()' : 'json()'};`;

    const phpHeaders = [];
    if (endpoint.idempotent) {
        phpHeaders.push("    'Idempotency-Key' => (string) \\Illuminate\\Support\\Str::uuid(),");
    }
    const phpMethod = endpoint.method.toLowerCase();
    const phpAuth = needsBearer ? "    ->withToken($token)\n" : '';
    const phpHeaderBlock =
        phpHeaders.length > 0 ? `    ->withHeaders([\n${phpHeaders.join('\n')}\n    ])\n` : '';
    const phpPayloadArg = hasBody
        ? `, ${JSON.stringify(endpoint.body, null, 4)}`
        : '';
    const phpSnippet = `$response = Http::acceptJson()\n${phpAuth}${phpHeaderBlock}    ->${phpMethod}('${fullUrl}'${phpPayloadArg});`;

    const tabs = [];
    if (endpoint.body !== undefined) {
        tabs.push({
            id: 'json',
            label: 'JSON Body',
            content: JSON.stringify(endpoint.body, null, 2),
        });
    }
    tabs.push(
        {
            id: 'curl',
            label: 'cURL',
            content: curlLines.join(' \\\n'),
        },
        {
            id: 'javascript',
            label: 'JavaScript',
            content: jsSnippet,
        },
        {
            id: 'php',
            label: 'PHP (Http)',
            content: phpSnippet,
        },
    );

    return tabs;
}

/**
 * Kartu detail endpoint API yang mendukung expand/collapse, snippet multi-bahasa, dan integrasi Konsol Uji.
 */
export default function EndpointCard({
    endpoint,
    baseOrigin,
    isExpanded,
    onToggle,
    onTry,
}) {
    const resolvedPath = resolveEndpointPath(endpoint);
    const fullUrlToCopy = `${baseOrigin}${resolvedPath}`;
    const methodClass =
        METHOD_BADGE_STYLES[endpoint.method] ||
        'border-slate-200 bg-slate-100 text-slate-700';

    const requestTabs = useMemo(
        () => buildRequestTabs(endpoint, baseOrigin),
        [endpoint, baseOrigin],
    );

    return (
        <article
            id={endpoint.id}
            className={`scroll-mt-24 overflow-hidden rounded-2xl border bg-white transition ${
                isExpanded
                    ? 'border-brand-navy/30 shadow-md ring-1 ring-brand-navy/5'
                    : 'border-slate-200/90 shadow-sm hover:border-slate-300'
            }`}
        >
            {/* Header Bar */}
            <div
                role="button"
                tabIndex={0}
                onClick={onToggle}
                onKeyDown={(e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        onToggle();
                    }
                }}
                aria-expanded={isExpanded}
                className="flex cursor-pointer flex-col gap-3 px-4 py-3.5 transition hover:bg-slate-50/70 sm:flex-row sm:items-center sm:justify-between sm:px-5"
            >
                <div className="flex min-w-0 flex-col gap-2 sm:flex-row sm:items-center sm:gap-3">
                    <div className="flex items-center gap-2.5">
                        <span
                            className={`inline-flex w-[68px] shrink-0 items-center justify-center rounded-lg border px-2 py-1 font-mono text-[11px] font-bold tracking-wide ${methodClass}`}
                        >
                            {endpoint.method}
                        </span>
                        <code className="break-all font-mono text-xs font-semibold text-brand-navy sm:text-[13px]">
                            {renderHighlightedPath(resolvedPath)}
                        </code>
                    </div>
                    <p className="truncate text-xs text-slate-600 sm:border-l sm:border-slate-200 sm:pl-3">
                        {endpoint.summary}
                    </p>
                </div>

                <div className="flex flex-wrap items-center justify-between gap-2 sm:justify-end">
                    <div className="flex flex-wrap items-center gap-1.5">
                        {endpoint.idempotent && (
                            <span
                                className="inline-flex items-center gap-1 rounded-md border border-teal-200 bg-teal-50 px-2 py-0.5 text-[10px] font-semibold text-teal-700"
                                title="Mendukung header Idempotency-Key (UUID v4)"
                            >
                                <Sparkles className="h-3 w-3" aria-hidden="true" />
                                <span>Idempotent</span>
                            </span>
                        )}
                        {endpoint.permission && (
                            <span
                                className="inline-flex max-w-[180px] items-center gap-1 truncate rounded-md border border-indigo-200 bg-indigo-50 px-2 py-0.5 text-[10px] font-semibold text-indigo-700"
                                title={`Permission: ${endpoint.permission}`}
                            >
                                <Shield className="h-3 w-3 shrink-0" aria-hidden="true" />
                                <span className="truncate">{endpoint.permission}</span>
                            </span>
                        )}
                        <span className="rounded-md border border-slate-200 bg-slate-50 px-2 py-0.5 font-mono text-[10px] font-medium text-slate-600">
                            {endpoint.auth}
                        </span>
                    </div>

                    <div className="flex items-center gap-1.5">
                        <CopyButton text={fullUrlToCopy} label="URL" />
                        <ChevronDown
                            className={`h-4 w-4 text-slate-400 transition-transform duration-200 ${
                                isExpanded ? 'rotate-180 text-brand-navy' : ''
                            }`}
                            aria-hidden="true"
                        />
                    </div>
                </div>
            </div>

            {/* Body Detail */}
            {isExpanded && (
                <div className="space-y-5 border-t border-slate-200/80 bg-slate-50/40 px-4 py-5 sm:px-5">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div className="space-y-1.5">
                            <h4 className="text-sm font-bold text-brand-navy">{endpoint.summary}</h4>
                            <p className="max-w-3xl text-xs leading-relaxed text-slate-600">
                                {endpoint.description}
                            </p>
                        </div>

                        <button
                            type="button"
                            onClick={() => onTry(endpoint)}
                            className="inline-flex shrink-0 items-center justify-center gap-1.5 rounded-lg bg-brand-green px-3.5 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-brand-green-dark"
                        >
                            <Play className="h-3.5 w-3.5 fill-current" aria-hidden="true" />
                            <span>Uji di Konsol</span>
                        </button>
                    </div>

                    {/* Tabel Parameter */}
                    <div>
                        <h5 className="mb-2 text-xs font-bold uppercase tracking-wider text-slate-500">
                            Parameter Request
                        </h5>
                        {endpoint.params && endpoint.params.length > 0 ? (
                            <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white">
                                <table className="min-w-full divide-y divide-slate-200 text-left text-xs">
                                    <thead className="bg-slate-50/80 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                                        <tr>
                                            <th className="px-3.5 py-2.5">Field</th>
                                            <th className="px-3.5 py-2.5">Lokasi</th>
                                            <th className="px-3.5 py-2.5">Tipe</th>
                                            <th className="px-3.5 py-2.5">Aturan</th>
                                            <th className="px-3.5 py-2.5">Keterangan</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100">
                                        {endpoint.params.map((param) => (
                                            <tr key={`${param.in}-${param.name}`} className="hover:bg-slate-50/60">
                                                <td className="whitespace-nowrap px-3.5 py-2.5 font-mono font-semibold text-brand-navy">
                                                    {param.name}
                                                </td>
                                                <td className="whitespace-nowrap px-3.5 py-2.5">
                                                    <span className="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-[10px] font-medium text-slate-700">
                                                        {PARAM_LOCATION_LABELS[param.in] || param.in}
                                                    </span>
                                                </td>
                                                <td className="whitespace-nowrap px-3.5 py-2.5 font-mono text-[11px] text-slate-600">
                                                    {param.type}
                                                </td>
                                                <td className="whitespace-nowrap px-3.5 py-2.5">
                                                    {param.required ? (
                                                        <span className="rounded bg-rose-50 px-1.5 py-0.5 text-[10px] font-semibold text-rose-700">
                                                            Wajib
                                                        </span>
                                                    ) : (
                                                        <span className="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-500">
                                                            Opsional
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="px-3.5 py-2.5 text-slate-600">
                                                    {param.description}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        ) : (
                            <div className="rounded-xl border border-dashed border-slate-200 bg-white px-4 py-3 text-xs text-slate-500">
                                Tidak membutuhkan parameter query maupun JSON body.
                            </div>
                        )}
                    </div>

                    {/* Contoh Request & Respons */}
                    <div className="grid gap-4 lg:grid-cols-2">
                        <CodeBlock title="Contoh Request" tabs={requestTabs} />
                        <CodeBlock
                            title="Contoh Respons"
                            badge={`HTTP ${endpoint.statusCode || 200}`}
                            code={endpoint.returns}
                        />
                    </div>
                </div>
            )}
        </article>
    );
}
