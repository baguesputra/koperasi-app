<?php

return [
    // Pagu dana sosial per bulan: batas pengeluaran dana sosial.
    // Pool pinjaman kini dinamis mengikuti saldo Bank (bukan pagu bulanan tetap).
    'cadangan_sosial_bulan' => (float) env('KOPERASI_CADANGAN_SOSIAL', 5_000_000),
    'nama' => env('KOPERASI_NAMA', 'KOPERASI KARYAWAN'),
    'unit' => env('KOPERASI_UNIT', 'KARYA MANDIRI DUTA MALL BANJARMASIN'),
    'alamat' => env('KOPERASI_ALAMAT', 'Jalan Jenderal Ahmad Yani KM 2 No. 98, Melayu'),
    'telepon' => env('KOPERASI_TELEPON', ''),
    'email' => env('KOPERASI_EMAIL', ''),
    'kota_ttd' => env('KOPERASI_KOTA_TTD', 'Banjarmasin'),
];
