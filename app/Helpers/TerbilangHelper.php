<?php

namespace App\Helpers;

class TerbilangHelper
{
    private static array $satuan = [
        '', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima',
        'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh',
        'Sebelas',
    ];

    public static function angkaKeTerbilang(float $angka): string
    {
        $angka = (int) $angka;

        if ($angka < 0) {
            return 'Minus '.self::konversi(abs($angka));
        }

        if ($angka === 0) {
            return 'Nol Rupiah';
        }

        return trim(self::konversi($angka)).' Rupiah';
    }

    private static function konversi(int $angka): string
    {
        if ($angka < 12) {
            return self::$satuan[$angka];
        }

        if ($angka < 20) {
            return self::konversi($angka - 10).' Belas';
        }

        if ($angka < 100) {
            return self::konversi(intdiv($angka, 10)).' Puluh '.self::konversi($angka % 10);
        }

        if ($angka < 200) {
            return 'Seratus '.self::konversi($angka - 100);
        }

        if ($angka < 1000) {
            return self::konversi(intdiv($angka, 100)).' Ratus '.self::konversi($angka % 100);
        }

        if ($angka < 2000) {
            return 'Seribu '.self::konversi($angka - 1000);
        }

        if ($angka < 1_000_000) {
            return self::konversi(intdiv($angka, 1000)).' Ribu '.self::konversi($angka % 1000);
        }

        if ($angka < 1_000_000_000) {
            return self::konversi(intdiv($angka, 1_000_000)).' Juta '.self::konversi($angka % 1_000_000);
        }

        if ($angka < 1_000_000_000_000) {
            return self::konversi(intdiv($angka, 1_000_000_000)).' Miliar '.self::konversi($angka % 1_000_000_000);
        }

        return self::konversi(intdiv($angka, 1_000_000_000_000)).' Triliun '.self::konversi($angka % 1_000_000_000_000);
    }
}
