<?php

/*
 * Konfigurasi pernyataan pembukaan rekening tabungan anggota.
 * Update `versi` setiap kali ada perubahan ketentuan agar audit trail terdokumentasi.
 */

return [
    'versi' => 'v1.0-2026-11-23',

    'poin' => [
        [
            'judul' => 'Sifat Tabungan Sukarela',
            'deskripsi' => 'Tabungan Anggota merupakan simpanan sukarela di luar Simpanan Pokok dan Simpanan Wajib. Satu anggota hanya dapat memiliki satu rekening tabungan aktif.',
            'wajib_contreng' => false,
        ],
        [
            'judul' => 'Bunga Tabungan',
            'deskripsi' => 'Saldo tabungan memperoleh bunga sesuai suku bunga tahunan yang berlaku. Bunga dihitung berdasarkan saldo harian dan dikreditkan ke saldo tabungan setiap akhir bulan.',
            'wajib_contreng' => false,
        ],
        [
            'judul' => 'Penyetoran dan Verifikasi',
            'deskripsi' => 'Setoran dapat dilakukan melalui transfer bank dengan melampirkan bukti transfer di Portal Anggota atau setor tunai langsung melalui Bendahara Koperasi.',
            'wajib_contreng' => false,
        ],
        [
            'judul' => 'Penarikan dan Saldo Mengendap',
            'deskripsi' => 'Penarikan saldo dapat diajukan sewaktu-waktu melalui Portal Anggota dengan tetap menyisakan saldo minimal mengendap, kecuali saat mengajukan penutupan rekening.',
            'wajib_contreng' => false,
        ],
        [
            'judul' => 'Pengunduran Diri (Resign)',
            'deskripsi' => 'Apabila anggota mengundurkan diri, rekening tabungan akan ditutup dan seluruh saldo beserta bunga berjalan hingga tanggal resign dikembalikan kepada anggota.',
            'wajib_contreng' => false,
        ],
        [
            'judul' => 'Persetujuan Ketentuan Tabungan',
            'deskripsi' => 'Saya menyetujui seluruh ketentuan Tabungan Anggota Koperasi Karya Mandiri dan menyatakan bahwa dana yang disetorkan berasal dari sumber yang sah.',
            'wajib_contreng' => true,
        ],
    ],
];
