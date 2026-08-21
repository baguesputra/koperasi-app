<?php

namespace Database\Seeders;

use App\Models\SettingBunga;
use Illuminate\Database\Seeder;

class SettingBungaSeeder extends Seeder
{
    public function run(): void
    {
        SettingBunga::firstOrCreate([
            'persentase' => 1.00,
            'berlaku_dari_tanggal' => now()->startOfYear(),
        ]);
    }
}
