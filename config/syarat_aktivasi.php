<?php

/*
 * Konfigurasi pernyataan pengajuan aktivasi keanggotaan.
 * Update `versi` setiap kali ada perubahan poin agar audit trail terdokumentasi.
 */

return [
    'versi' => 'v1.0-2026-09-28',

    'poin' => [
        [
            'judul' => 'Simpanan Pokok Sekali Bayar',
            'deskripsi' => 'Simpanan Pokok sebesar Rp 50.000 dibayar satu kali pada saat aktivasi keanggotaan disetujui.',
            'wajib_contreng' => false,
        ],
        [
            'judul' => 'Simpanan Wajib Bulanan',
            'deskripsi' => 'Simpanan Wajib sebesar Rp 50.000 setiap bulan, dengan rincian Rp 45.000 sebagai simpanan dan Rp 5.000 disumbangkan untuk Dana Sosial koperasi.',
            'wajib_contreng' => false,
        ],
        [
            'judul' => 'Limit Pinjaman Awal',
            'deskripsi' => 'Pada tahun pertama keanggotaan, batas maksimal pinjaman adalah Rp 1.000.000. Limit bertambah mengikuti lama keanggotaan atau pengajuan limit yang disetujui.',
            'wajib_contreng' => false,
        ],
        [
            'judul' => 'Alur Persetujuan Aktivasi',
            'deskripsi' => 'Pengajuan aktivasi diverifikasi oleh pengurus dan disetujui final oleh Ketua Koperasi. Keputusan disampaikan melalui WhatsApp dan halaman portal ini.',
            'wajib_contreng' => false,
        ],
        [
            'judul' => 'Kepatuhan Anggaran dan Peraturan',
            'deskripsi' => 'Saya tunduk dan patuh pada seluruh ketentuan yang tertera dalam Anggaran Dasar, Anggaran Rumah Tangga, peraturan khusus, dan kebijakan lainnya yang berlaku di Koperasi Karya Mandiri.',
            'wajib_contreng' => true,
        ],
        [
            'judul' => 'Kebenaran Data',
            'deskripsi' => 'Demikian pengajuan ini saya isi dengan benar. Apabila di kemudian hari ditemukan ketidakbenaran data, saya bersedia menerima sanksi sesuai peraturan yang berlaku.',
            'wajib_contreng' => true,
        ],
    ],
];
