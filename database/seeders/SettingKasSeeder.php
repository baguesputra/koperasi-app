<?php

namespace Database\Seeders;

use App\Models\SettingKas;
use Illuminate\Database\Seeder;

class SettingKasSeeder extends Seeder
{
    public function run(): void
    {
        SettingKas::updateOrCreate(
            ['kunci' => SettingKas::PAGU],
            ['label' => 'Pagu Pinjaman Bulanan', 'nominal' => 50_000_000]
        );
        SettingKas::updateOrCreate(
            ['kunci' => SettingKas::CADANGAN],
            ['label' => 'Cadangan Sosial Bulanan', 'nominal' => 5_000_000]
        );
    }
}
