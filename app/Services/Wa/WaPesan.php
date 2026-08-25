<?php

namespace App\Services\Wa;

class WaPesan
{
    public static function rupiah(float|int|string|null $angka): string
    {
        return 'Rp '.number_format((float) $angka, 0, ',', '.');
    }

    /**
     * Bungkus isi pesan dengan kop, salam pembuka (Yth.), dan penutup resmi.
     * $isi berupa teks multi-baris; gunakan *teks* untuk penekanan bold.
     */
    public static function susun(?string $nama, ?string $noAnggota, string $isi): string
    {
        $yth = '';
        if ($nama) {
            $yth = "\nKepada Yth. Sdr/i. {$nama}".($noAnggota ? " (No. Anggota: {$noAnggota})" : '')."\n";
        }

        return "*KOPERASI KARYAWAN*\n{$yth}\n{$isi}\n\nHormat kami,\nPengurus Koperasi Karyawan\n\n_Pesan ini dikirim otomatis oleh sistem koperasi. Mohon tidak membalas pesan ini._";
    }
}
