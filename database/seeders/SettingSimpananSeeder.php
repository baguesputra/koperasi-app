<?php

namespace Database\Seeders;

use App\Models\SettingSimpanan;
use Illuminate\Database\Seeder;

class SettingSimpananSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['jenis' => 'pokok', 'label' => 'Simpanan Pokok', 'nominal' => 50_000],
            ['jenis' => 'wajib', 'label' => 'Simpanan Wajib', 'nominal' => 40_000],
            ['jenis' => 'dana_sosial', 'label' => 'Dana Sosial', 'nominal' => 5_000],
        ];

        foreach ($data as $row) {
            SettingSimpanan::firstOrCreate(['jenis' => $row['jenis']], $row);
        }
    }
}