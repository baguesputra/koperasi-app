<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class IdempotencyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('idempotency.enabled')) {
            return $next($request);
        }

        $headerName = config('idempotency.header_name', 'Idempotency-Key');
        $key = $request->header($headerName) ?? $request->header('X-'.$headerName);

        if (! $key) {
            return $next($request);
        }

        if (! Str::isUuid($key)) {
            return response()->json([
                'message' => 'Invalid Idempotency-Key format (must be UUID)',
            ], 400);
        }

        $userId = auth()->id() ?? 0;

        $cached = DB::table('idempotency_keys')
            ->where('key', $key)
            ->where('user_id', $userId)
            ->first();

        if ($cached && $cached->expires_at > now()) {
            return response($cached->response, $cached->status_code)
                ->header('Content-Type', 'application/json')
                ->header('X-Idempotency-Replay', 'true');
        }

        $response = $next($request);

        $statusCode = $response->getStatusCode();
        $cachedStatusCodes = config('idempotency.cached_status_codes', [200, 201, 422]);

        if (in_array($statusCode, $cachedStatusCodes, true)) {
            DB::table('idempotency_keys')->insert([
                'key' => $key,
                'user_id' => $userId,
                'response' => $response->getContent(),
                'status_code' => $statusCode,
                'endpoint' => $request->route()?->getName() ?? 'unknown',
                'expires_at' => now()->addHours(config('idempotency.ttl', 24)),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $response;
    }
}
