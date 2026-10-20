<?php

return [
    'nama' => env('KOPERASI_NAMA', 'KOPERASI KARYAWAN'),
    'unit' => env('KOPERASI_UNIT', 'KARYA MANDIRI DUTA MALL BANJARMASIN'),
    'alamat' => env('KOPERASI_ALAMAT', 'Jalan Jenderal Ahmad Yani KM 2 No. 98, Melayu'),
    'telepon' => env('KOPERASI_TELEPON', ''),
    'email' => env('KOPERASI_EMAIL', ''),
    'kota_ttd' => env('KOPERASI_KOTA_TTD', 'Banjarmasin'),
    // Modal tetap: Modal di Excel (18.969.831) + Saldo Anggota Lama (1.440.000) = 20.409.831
    'modal_tetap' => (float) env('KOPERASI_MODAL_TETAP', 20_409_831),
    // Base Laba/Rugi Ditahan historis dari sisa SHU tahun-tahun sebelum sistem (sel G18 neraca.xls)
    'laba_ditahan_historis' => (float) env('KOPERASI_LABA_DITAHAN_HISTORIS', 17_663_447.20),
];
