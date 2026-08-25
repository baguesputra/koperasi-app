<?php

namespace Database\Seeders;

use App\Models\TabelTenor;
use Illuminate\Database\Seeder;

class TabelTenorSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['nominal_min' => 0, 'nominal_max' => 1_000_000, 'tenor_maksimal_bulan' => 3],
            ['nominal_min' => 1_000_001, 'nominal_max' => 2_000_000, 'tenor_maksimal_bulan' => 4],
            ['nominal_min' => 2_000_001, 'nominal_max' => 3_000_000, 'tenor_maksimal_bulan' => 6],
            ['nominal_min' => 3_000_001, 'nominal_max' => 4_000_000, 'tenor_maksimal_bulan' => 9],
            ['nominal_min' => 4_000_001, 'nominal_max' => 5_000_000, 'tenor_maksimal_bulan' => 12],
            ['nominal_min' => 5_000_001, 'nominal_max' => 6_000_000, 'tenor_maksimal_bulan' => 12],
            ['nominal_min' => 6_000_001, 'nominal_max' => 7_000_000, 'tenor_maksimal_bulan' => 12],
            ['nominal_min' => 7_000_001, 'nominal_max' => 8_000_000, 'tenor_maksimal_bulan' => 12],
            ['nominal_min' => 8_000_001, 'nominal_max' => 9_000_000, 'tenor_maksimal_bulan' => 12],
            ['nominal_min' => 9_000_001, 'nominal_max' => 10_000_000, 'tenor_maksimal_bulan' => 12],
        ];

        foreach ($data as $row) {
            TabelTenor::firstOrCreate($row);
        }
    }
}
