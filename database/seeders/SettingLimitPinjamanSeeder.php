<?php

namespace Database\Seeders;

use App\Models\SettingLimitPinjaman;
use Illuminate\Database\Seeder;

class SettingLimitPinjamanSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['kategori' => 'kurang_1_tahun', 'label' => 'Anggota < 1 Tahun', 'limit_maksimal' => 1_000_000],
            ['kategori' => 'satu_sampai_3_tahun', 'label' => 'Anggota 1-3 Tahun', 'limit_maksimal' => 5_000_000],
            ['kategori' => 'tiga_sampai_5_tahun', 'label' => 'Anggota 3-5 Tahun', 'limit_maksimal' => 7_000_000],
            ['kategori' => 'lebih_5_tahun', 'label' => 'Anggota > 5 Tahun', 'limit_maksimal' => 10_000_000],
        ];

        foreach ($data as $row) {
            SettingLimitPinjaman::updateOrCreate(['kategori' => $row['kategori']], $row);
        }
    }
}
