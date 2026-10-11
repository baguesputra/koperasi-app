<?php

namespace Database\Seeders;

use App\Models\SettingKas;
use Illuminate\Database\Seeder;

class SettingKasSeeder extends Seeder
{
    public function run(): void
    {
        SettingKas::updateOrCreate(
            ['kunci' => SettingKas::CADANGAN],
            ['label' => 'Pagu Kas Kecil', 'nominal' => 5_000_000]
        );
    }
}
