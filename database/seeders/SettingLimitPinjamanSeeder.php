<?php

namespace Database\Seeders;

use App\Models\SettingLimitPinjaman;
use Illuminate\Database\Seeder;

class SettingLimitPinjamanSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['kategori' => 'anggota_baru', 'label' => 'Anggota < 1 Tahun', 'limit_maksimal' => 1_000_000],
            ['kategori' => 'staff', 'label' => 'Staff (1-5 Tahun)', 'limit_maksimal' => 7_000_000],
            ['kategori' => 'hod', 'label' => 'HOD (1-5 Tahun)', 'limit_maksimal' => 10_000_000],
            ['kategori' => 'anggota_lama', 'label' => 'Anggota ≥ 5 Tahun', 'limit_maksimal' => 10_000_001],
        ];

        foreach ($data as $row) {
            SettingLimitPinjaman::firstOrCreate(['kategori' => $row['kategori']], $row);
        }
    }
}