<?php

return [
    // Arsip pengaturan lama, tak lagi dipakai pool pinjaman (pool = saldo bank dinamis).
    'pagu_pinjaman_bulanan' => (float) env('KOPERASI_PAGU_PINJAMAN', 50_000_000),
    // Pagu dana sosial per bulan: batas klaim santunan + pengeluaran dana sosial (P1-5).
    // Pool pinjaman kini dinamis mengikuti saldo Bank (bukan pagu bulanan tetap).
    'cadangan_sosial_bulan' => (float) env('KOPERASI_CADANGAN_SOSIAL', 5_000_000),
    'nama' => env('KOPERASI_NAMA', 'KOPERASI KARYAWAN'),
    'unit' => env('KOPERASI_UNIT', 'KARYA MANDIRI DUTA MALL BANJARMASIN'),
    'alamat' => env('KOPERASI_ALAMAT', 'Jalan Jenderal Ahmad Yani KM 2 No. 98, Melayu'),
    'telepon' => env('KOPERASI_TELEPON', ''),
    'email' => env('KOPERASI_EMAIL', ''),
    'kota_ttd' => env('KOPERASI_KOTA_TTD', 'Banjarmasin'),
];
