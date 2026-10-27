<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Handle an incoming request and enforce defensive security headers and anti-indexing tags.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Anti-Indexing & Anti-Search Engine Scraping (Defense in Depth)
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive, nosnippet');

        // Clickjacking Protection: Disallow embedding in external frames
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // MIME-Sniffing Protection: Force browser to honor declared Content-Type
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Disable deprecated / vulnerable legacy browser XSS auditor (favor modern CSP & sanitization)
        $response->headers->set('X-XSS-Protection', '0');

        // Content Security Policy (CSP): Mitigasi XSS, data injection, dan untrusted execution
        $csp = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com data:",
            "img-src 'self' data: blob:",
            "connect-src 'self' ws: wss: https:",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
        ];
        $response->headers->set('Content-Security-Policy', implode('; ', $csp));

        // Referrer Privacy: Prevent leaking internal paths and query parameters to external hosts
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions Policy: Restrict unnecessary browser sensors and APIs
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');

        // HTTP Strict Transport Security (HSTS) when served over HTTPS
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Information Disclosure Prevention: Remove runtime version headers
        $response->headers->remove('X-Powered-By');
        if (function_exists('header_remove')) {
            @header_remove('X-Powered-By');
        }

        // HTTP Compression (Gzip) for text/html, json, and svg when client supports gzip
        if (function_exists('gzencode') &&
            !$response instanceof \Symfony\Component\HttpFoundation\BinaryFileResponse &&
            !$response instanceof \Symfony\Component\HttpFoundation\StreamedResponse &&
            !$response->headers->has('Content-Encoding') &&
            $request->headers->has('Accept-Encoding') &&
            str_contains((string) $request->header('Accept-Encoding'), 'gzip')) {
            $content = $response->getContent();
            if ($content !== false && strlen($content) > 1024) {
                $contentType = (string) $response->headers->get('Content-Type', '');
                if (str_contains($contentType, 'text/html') ||
                    str_contains($contentType, 'application/json') ||
                    str_contains($contentType, 'image/svg+xml') ||
                    str_contains($contentType, 'text/css') ||
                    str_contains($contentType, 'application/javascript')) {
                    $compressed = gzencode($content, 6);
                    if ($compressed !== false) {
                        $response->setContent($compressed);
                        $response->headers->set('Content-Encoding', 'gzip');
                        $response->headers->set('Content-Length', (string) strlen($compressed));
                    }
                }
            }
        }

        return $response;
    }
}
