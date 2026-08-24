<?php

return [
    'enabled' => env('IDEMPOTENCY_ENABLED', true),
    'ttl' => env('IDEMPOTENCY_TTL', 24),
    'header_name' => env('IDEMPOTENCY_HEADER_NAME', 'Idempotency-Key'),
    'cached_status_codes' => [200, 201, 422],
];