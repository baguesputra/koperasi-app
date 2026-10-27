<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Routes Filter (Except)
    |--------------------------------------------------------------------------
    |
    | Kecualikan rute-rute internal yang tidak dibutuhkan oleh navigasi
    | antarmuka Inertia React untuk mencegah recon/kebocoran skema endpoint
    | serta merampingkan payload HTML awal.
    |
    */
    'except' => [
        '_debugbar.*',
        'ignition.*',
        'sanctum.*',
        'storage.local*',
        'api.v1.*',
        'api.documentation',
    ],
];

