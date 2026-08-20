<?php

/*
 * Konfigurasi surat persetujuan pengajuan pinjaman.
 * Update `versi` setiap kali ada perubahan poin syarat agar audit trail terdokumentasi.
 */

return [
    'versi' => 'v1.1-2026-08-21',

    'poin' => [
        [
            'judul' => 'Potongan Gaji Setiap Akhir Bulan',
            'deskripsi' => 'Pinjaman yang disetujui akan dipotong dari gaji anggota setiap akhir bulan melalui mekanisme payroll perusahaan hingga lunas.',
            'wajib_contreng' => true,
        ],
        [
            'judul' => 'Pelunasan Sebelum Resign',
            'deskripsi' => 'Apabila anggota mengajukan pengunduran diri dari perusahaan, maka seluruh sisa pinjaman yang masih berjalan wajib dilunasi terlebih dahulu sebagai salah satu syarat diprosesnya resign.',
            'wajib_contreng' => true,
        ],
        [
            'judul' => 'Keringanan Perubahan Tenor (1 Kali)',
            'deskripsi' => 'Anggota berhak mendapatkan keringanan berupa perpanjangan/perubahan tenor atau pelunasan lebih awal sebanyak 1 (satu) kali selama masa pinjaman, dengan persetujuan pengurus koperasi.',
            'wajib_contreng' => true,
        ],
        [
            'judul' => 'Penggunaan Dana Sesuai Keperluan',
            'deskripsi' => 'Dana pinjaman wajib digunakan sesuai keperluan yang telah disampaikan saat pengajuan dan tidak boleh digunakan untuk kepentingan yang bertentangan dengan peraturan perusahaan, AD/ART koperasi, maupun ketentuan hukum yang berlaku.',
            'wajib_contreng' => true,
        ],
        [
            'judul' => 'Sanksi Atas Pelanggaran',
            'deskripsi' => 'Pelanggaran terhadap ketentuan di atas akan dikenai sanksi sesuai Peraturan Perusahaan dan AD/ART Koperasi, mulai dari teguran, pemutusan fasilitas pinjaman, penagihan paksa, hingga proses hukum.',
            'wajib_contreng' => true,
        ],
        [
            'judul' => 'Pernyataan Bukan untuk Pihak Ketiga',
            'deskripsi' => 'Saya menyatakan bahwa pinjaman ini digunakan untuk kepentingan pribadi saya sebagai anggota, bukan atas nama, untuk, atau dialihkan kepada pihak lain manapun. Apabila di kemudian hari terbukti sebaliknya, saya bersedia menanggung seluruh konsekuensi hukum sesuai peraturan yang berlaku.',
            'wajib_contreng' => true,
        ],
        [
            'judul' => 'Pernyataan Kebenaran Data',
            'deskripsi' => 'Saya menyatakan bahwa seluruh data yang saya berikan dalam pengajuan ini adalah benar, lengkap, dan dapat dipertanggungjawabkan. Apabila di kemudian hari ditemukan adanya ketidakbenaran data, saya bersedia menerima sanksi berupa pembatalan pinjaman, pemutusan fasilitas koperasi, serta proses hukum sesuai peraturan yang berlaku.',
            'wajib_contreng' => true,
        ],
    ],
];
