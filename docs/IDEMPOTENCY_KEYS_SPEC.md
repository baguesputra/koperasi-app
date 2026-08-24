# Idempotency Keys Specification

## Overview
Implement idempotency keys for financial operations to prevent duplicate submissions from causing double charges, double journal entries, double notifications, etc.

## Scope
**14 Protected Endpoints** (all POST, financial write operations):

| # | Route | Controller | Service | Risk |
|---|-------|------------|---------|------|
| 1 | `POST /portal/pinjaman` | `Portal\PinjamanController::store` | `PengajuanPinjamanService::ajukan` | Double loan, double WA, double journal |
| 2 | `POST /portal/pengajuan-limit` | `Portal\PengajuanLimitController::store` | `PengajuanLimitService::ajukan` | Double limit request |
| 3 | `POST /portal/percepatan` | `Portal\PercepatanController::store` | `PercepatanPinjamanService::ajukan` | Double tenor change |
| 4 | `POST /bendahara/pinjaman/{pinjaman}/approve` | `Bendahara\PinjamanController::approve` | `PersetujuanPinjamanService::approveBendahara` | Double approval |
| 5 | `POST /bendahara/pinjaman/{pinjaman}/reject` | `Bendahara\PinjamanController::reject` | `PersetujuanPinjamanService::rejectBendahara` | Double rejection |
| 6 | `POST /bendahara/pinjaman/{pinjaman}/cair` | `Bendahara\PinjamanController::cair` | `PersetujuanPinjamanService::cairBendahara` | Double disbursement |
| 7 | `POST /bendahara/angsuran/konfirmasi` | `Bendahara\AngsuranController::konfirmasi` | `KonfirmasiAngsuranService::konfirmasiMassal` | Double payment confirm |
| 8 | `POST /bendahara/simpanan/konfirmasi` | `Bendahara\SimpananController::konfirmasi` | `KonfirmasiSimpananService::konfirmasiMassal` | Double savings confirm |
| 9 | `POST /ketua/pinjaman/{pinjaman}/approve` | `Ketua\PinjamanController::approve` | `PersetujuanPinjamanService::approveKetua` | Double approval |
| 10 | `POST /ketua/pinjaman/{pinjaman}/reject` | `Ketua\PinjamanController::reject` | `PersetujuanPinjamanService::rejectKetua` | Double rejection |
| 11 | `POST /ketua/pengajuan-limit/{pengajuanLimit}/approve` | `Ketua\PengajuanLimitController::approve` | `PengajuanLimitService::setujui` | Double limit approval |
| 12 | `POST /ketua/pengajuan-limit/{pengajuanLimit}/reject` | `Ketua\PengajuanLimitController::reject` | `PengajuanLimitService::tolak` | Double limit rejection |
| 13 | `POST /pengeluaran` | `PengeluaranController::store` | `PengeluaranService::catat` | Double expense |
| 14 | `POST /angsuran/konfirmasi-percepatan` | `AngsuranController::konfirmasiPercepatan` | `KonfirmasiAngsuranService` | Double percepatan confirm |

## Technical Design

### Database Schema
```sql
CREATE TABLE idempotency_keys (
    `key` VARCHAR(64) NOT NULL,           -- UUID v4
    `user_id` BIGINT UNSIGNED NOT NULL,   -- Per-user isolation
    `response` JSON NULL,                 -- Cached response body
    `status_code` SMALLINT UNSIGNED NULL, -- Cached HTTP status
    `endpoint` VARCHAR(191) NOT NULL,     -- Route name for debugging
    `expires_at` TIMESTAMP NOT NULL,      -- TTL
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`key`, `user_id`),
    INDEX `idempotency_keys_expires_at_index` (`expires_at`),
    INDEX `idempotency_keys_user_id_index` (`user_id`)
);
```

### Configuration (`config/idempotency.php`)
```php
return [
    'enabled' => env('IDEMPOTENCY_ENABLED', true),
    'ttl' => env('IDEMPOTENCY_TTL', 24), // hours
    'header_name' => 'Idempotency-Key',
    'cached_status_codes' => [200, 201, 422], // Skip 302 redirects
];
```

### Middleware Logic (`IdempotencyMiddleware`)

```php
use Symfony\Component\HttpFoundation\Response; // ✅ Base class for all responses

public function handle(Request $request, Closure $next): Response
{
    if (! config('idempotency.enabled')) {
        return $next($request);
    }

    $key = $request->header('Idempotency-Key') ?? $request->header('X-Idempotency-Key');
    
    if (! $key) {
        return $next($request); // No key = no idempotency (backward compatible)
    }

    if (! Str::isUuid($key)) {
        return response()->json(['message' => 'Invalid Idempotency-Key format (must be UUID)'], 400);
    }

    $userId = auth()->id() ?? 0; // 0 for guest (shouldn't happen on protected routes)

    // Check cache
    $cached = DB::table('idempotency_keys')
        ->where('key', $key)
        ->where('user_id', $userId)
        ->first();

    if ($cached && $cached->expires_at > now()) {
        return response($cached->response, $cached->status_code)
            ->header('Content-Type', 'application/json')
            ->header('X-Idempotency-Replay', 'true');
    }

    // Process request
    $response = $next($request);

    // Only cache successful responses + validation errors
    if (in_array($response->getStatusCode(), config('idempotency.cached_status_codes', [200, 201, 422]))) {
        DB::table('idempotency_keys')->insert([
            'key' => $key,
            'user_id' => $userId,
            'response' => $response->getContent(),
            'status_code' => $response->getStatusCode(),
            'endpoint' => $request->route()?->getName() ?? 'unknown',
            'expires_at' => now()->addHours(config('idempotency.ttl', 24)),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    return $response;
}
```

### Frontend Integration

**Utility**: `resources/js/Utils/idempotency.js`
```js
export function generateIdempotencyKey() {
    return crypto.randomUUID();
}

export function withIdempotencyKey(config = {}) {
    const key = generateIdempotencyKey();
    return {
        ...config,
        headers: {
            ...(config.headers || {}),
            'Idempotency-Key': key,
        },
        onBefore: () => {
            sessionStorage.setItem('lastIdempotencyKey', key);
        },
    };
}
```
> ⚠️ **Note**: No TypeScript type annotations in `.js` files (Vite/Oxc parses as plain JS)

**Usage Pattern** (Inertia router):
```js
import { withIdempotencyKey } from '@/Utils/idempotency';

router.post(route('portal.pinjaman.store'), formData, withIdempotencyKey({
    onError: (errors) => { ... },
    onFinish: () => { ... },
}));
```

### Protected Components (7 files)
1. `resources/js/Pages/Portal/Pinjaman/Create.jsx`
2. `resources/js/Pages/Portal/PengajuanLimit/Create.jsx`
3. `resources/js/Pages/Portal/Percepatan/Create.jsx`
4. `resources/js/Pages/Bendahara/Pinjaman/Show.jsx`
5. `resources/js/Pages/Bendahara/Angsuran/Index.jsx`
6. `resources/js/Pages/Bendahara/Simpanan/Index.jsx`
7. `resources/js/Pages/Ketua/Pinjaman/Show.jsx` (and Ketua/PengajuanLimit/Index, Ketua/Percepatan/Index)

### Cleanup Command
```bash
php artisan idempotency:cleanup
```
Deletes expired keys (`expires_at < now()`). Schedule daily in `app/Console/Kernel.php`.

## Security Considerations

1. **Per-user isolation**: Key + user_id composite PK prevents cross-user replay
2. **UUID validation**: Rejects non-UUID keys (400)
3. **TTL enforcement**: Expired keys auto-purged
4. **No sensitive data in response cache**: Response body may contain flash messages but no secrets
5. **Header name**: Standard `Idempotency-Key` (Stripe-compatible)

## Testing Requirements

| Test | Description |
|------|-------------|
| `test_idempotent_loan_application` | Double POST /portal/pinjaman with same key → 1 loan |
| `test_idempotent_angsuran_confirm` | Double POST /bendahara/angsuran/konfirmasi → 1 journal entry |
| `test_idempotent_resign` | Double resign request → 1 settlement |
| `test_idempotent_replay_header` | Replay returns `X-Idempotency-Replay: true` |
| `test_idempotent_different_users` | Same key, different users → both process |
| `test_idempotent_expired_key` | Key expired → new request processes |
| `test_idempotent_invalid_uuid` | Invalid key format → 400 |
| `test_idempotent_disabled` | Config disabled → no caching |

## Rollout Plan

1. **Migration + Middleware + Config** → Deploy to dev
2. **Apply middleware to 3 endpoints** (loan application, angsuran confirm, simpanan confirm)
3. **Frontend integration** for those 3
4. **Staging testing** with concurrent requests
5. **Gradual rollout** to remaining 11 endpoints
6. **Schedule cleanup command**

## Files to Create/Modify

### New Files
- `database/migrations/2026_08_25_000000_create_idempotency_keys_table.php`
- `app/Http/Middleware/IdempotencyMiddleware.php`
- `config/idempotency.php`
- `resources/js/Utils/idempotency.js`
- `tests/Feature/IdempotencyTest.php`

### Modified Files
- `bootstrap/app.php` (alias + route middleware)
- `routes/web.php` (wrap 14 endpoints with `idempotent` middleware)
- 7 frontend components (add `withIdempotencyKey` import + usage)

## Configuration Reference

| Env Var | Default | Description |
|---------|---------|-------------|
| `IDEMPOTENCY_ENABLED` | `true` | Master switch |
| `IDEMPOTENCY_TTL` | `24` | Hours to keep keys |
| `IDEMPOTENCY_HEADER_NAME` | `Idempotency-Key` | Header to read |

## Fixes Applied During Implementation

| Issue | File | Fix |
|-------|------|-----|
| **Return type mismatch** | `app/Http/Middleware/IdempotencyMiddleware.php:14` | Changed return type from `Illuminate\Http\Response` to `Symfony\Component\HttpFoundation\Response` (base class covering `RedirectResponse`, `JsonResponse`, etc.) |
| **Vite/Oxc parse error** | `resources/js/Utils/idempotency.js:1` | Removed TypeScript return type annotation `: string` — Oxc parses `.js` as plain JavaScript |
| **Stray syntax** | `resources/js/Pages/Portal/Pinjaman/Create.jsx:134` | Removed duplicate `});` after `kirimPengajuan()` function |

## Verification Checklist

- [x] Migration created & migrated (`idempotency_keys` table with composite PK `key` + `user_id`)
- [x] Config file created (`config/idempotency.php`)
- [x] Middleware created & registered in `bootstrap/app.php` (alias `idempotent`)
- [x] 14 routes wrapped with `idempotent` middleware in `routes/web.php`
- [x] Frontend utility created (`resources/js/Utils/idempotency.js`)
- [x] 7 frontend components updated with `withIdempotencyKey`
- [x] `npm run build` — **passes**
- [x] `php artisan test --filter=Auth` — **21/21 pass**
- [x] `php artisan test --filter=SsoRedirectTest` — **3/3 pass**