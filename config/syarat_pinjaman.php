<?php

/*
 * Konfigurasi surat persetujuan pengajuan pinjaman.
 * Update `versi` setiap kali ada perubahan poin syarat agar audit trail terdokumentasi.
 */

return [
    'versi' => 'v1.0-2026-08-21',

    'poin' => [
        [
            'judul' => 'Potong Gaji Bulanan',
            'deskripsi' => 'Cicilan pinjaman akan dipotong langsung dari gaji bulanan anggota setiap akhir bulan melalui sistem payroll perusahaan.',
        ],
        [
            'judul' => 'Pelunasan Saat Resign',
            'deskripsi' => 'Apabila anggota mengajukan pengundurkan diri dari perusahaan, seluruh sisa pinjaman yang belum dilunasi wajib diselesaikan terlebih dahulu sebagai salah satu syarat proses resign.',
        ],
        [
            'judul' => 'Keringanan Perubahan Tenor (1x)',
            'deskripsi' => 'Anggota berhak mengajukan keringanan berupa perubahan tenor (perpanjang atau percepat) atau pelunasan dipercepat sebanyak 1 (satu) kali selama masa pinjaman berlangsung, dengan persetujuan pengurus koperasi.',
        ],
        [
            'judul' => 'Penggunaan Sesuai Keperluan',
            'deskripsi' => 'Dana pinjaman wajib digunakan sesuai dengan keperluan yang telah diajukan dan tidak diperkenankan dialokasikan untuk keperluan yang bertentangan dengan peraturan perusahaan, AD/ART koperasi, serta hukum yang berlaku.',
        ],
        [
            'judul' => 'Sanksi Pelanggaran',
            'deskripsi' => 'Pelanggaran terhadap ketentuan ini akan dikenakan sanksi administratif sesuai Peraturan Perusahaan dan Anggaran Dasar/Anggaran Rumah Tangga (AD/ART) Koperasi yang berlaku, termasuk namun tidak terbatas pada pemutusan fasilitas pinjaman, penagihan paksa, hingga proses hukum.',
        ],
    ],
];
