<?php

namespace Database\Seeders;

use App\Models\SettingLimitPinjaman;
use Illuminate\Database\Seeder;

class SettingLimitPinjamanSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['kategori' => 'kurang_1_tahun', 'label' => 'Anggota < 1 Tahun', 'min_bulan' => 0, 'maks_bulan' => 11, 'limit_maksimal' => 1_000_000],
            ['kategori' => 'satu_sampai_3_tahun', 'label' => 'Anggota 1-3 Tahun', 'min_bulan' => 12, 'maks_bulan' => 35, 'limit_maksimal' => 5_000_000],
            ['kategori' => 'tiga_sampai_5_tahun', 'label' => 'Anggota 3-5 Tahun', 'min_bulan' => 36, 'maks_bulan' => 59, 'limit_maksimal' => 7_000_000],
            ['kategori' => 'lebih_5_tahun', 'label' => 'Anggota > 5 Tahun', 'min_bulan' => 60, 'maks_bulan' => null, 'limit_maksimal' => 10_000_000],
        ];

        foreach ($data as $row) {
            SettingLimitPinjaman::updateOrCreate(['kategori' => $row['kategori']], $row);
        }
    }
}
