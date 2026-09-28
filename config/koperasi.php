<?php

return [
    // Pagu pinjaman per bulan kalender (angka fix, bisa diubah via Pengaturan → Kas).
    'pagu_pinjaman_bulanan' => (float) env('KOPERASI_PAGU_PINJAMAN', 50_000_000),
    // Cadangan sosial per bulan: tidak boleh dipakai pinjaman (P0-3).
    'cadangan_sosial_bulan' => (float) env('KOPERASI_CADANGAN_SOSIAL', 5_000_000),
    'nama' => env('KOPERASI_NAMA', 'KOPERASI KARYAWAN'),
    'unit' => env('KOPERASI_UNIT', 'KARYA MANDIRI DUTA MALL BANJARMASIN'),
    'alamat' => env('KOPERASI_ALAMAT', 'Jalan Jenderal Ahmad Yani KM 2 No. 98, Melayu'),
    'telepon' => env('KOPERASI_TELEPON', ''),
    'email' => env('KOPERASI_EMAIL', ''),
    'kota_ttd' => env('KOPERASI_KOTA_TTD', 'Banjarmasin'),
];
