<?php

namespace Database\Seeders;

use App\Models\SettingChipNominal;
use Illuminate\Database\Seeder;

class SettingChipNominalSeeder extends Seeder
{
    public function run(): void
    {
        foreach (SettingChipNominal::DEFAULT as $grup => $daftar) {
            foreach (array_values($daftar) as $i => $nominal) {
                SettingChipNominal::updateOrCreate(
                    ['grup' => $grup, 'nominal' => $nominal],
                    ['urutan' => $i]
                );
            }
        }
    }
}
