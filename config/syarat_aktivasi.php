<?php

/*
 * Konfigurasi pernyataan pengajuan aktivasi keanggotaan.
 * Update `versi` setiap kali ada perubahan poin agar audit trail terdokumentasi.
 */

return [
    'versi' => 'v1.1-2026-09-28',

    'poin' => [
        [
            'judul' => 'Simpanan Pokok',
            'deskripsi' => 'Simpanan Pokok dibayarkan satu kali pada saat aktivasi keanggotaan disetujui. Nominal mengikuti ketentuan yang ditetapkan pengurus.',
            'wajib_contreng' => false,
        ],
        [
            'judul' => 'Simpanan Wajib dan Dana Sosial',
            'deskripsi' => 'Simpanan Wajib dibayarkan setiap bulan. Sebagian dari iuran bulanan disalurkan sebagai Dana Sosial koperasi.',
            'wajib_contreng' => false,
        ],
        [
            'judul' => 'Batas Maksimal Pinjaman Awal',
            'deskripsi' => 'Pada tahun pertama keanggotaan berlaku batas maksimal pinjaman awal. Batas tersebut dapat bertambah mengikuti masa keanggotaan atau melalui pengajuan penambahan limit yang disetujui.',
            'wajib_contreng' => false,
        ],
        [
            'judul' => 'Tahapan Persetujuan Aktivasi',
            'deskripsi' => 'Pengajuan aktivasi ditinjau oleh pengurus dan diputuskan oleh Ketua Koperasi. Keputusan atas pengajuan disampaikan melalui WhatsApp dan halaman portal.',
            'wajib_contreng' => false,
        ],
        [
            'judul' => 'Kepatuhan terhadap Anggaran dan Peraturan',
            'deskripsi' => 'Saya menyatakan tunduk dan patuh terhadap seluruh ketentuan yang tercantum dalam Anggaran Dasar, Anggaran Rumah Tangga, peraturan khusus, serta seluruh kebijakan lain yang berlaku di Koperasi Karya Mandiri.',
            'wajib_contreng' => true,
        ],
        [
            'judul' => 'Kebenaran Data Pengajuan',
            'deskripsi' => 'Demikian pengajuan ini saya isi dengan sebenarnya. Apabila di kemudian hari ditemukan ketidakbenaran data, saya bersedia menerima sanksi sesuai peraturan yang berlaku.',
            'wajib_contreng' => true,
        ],
    ],
];
