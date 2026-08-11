<?php

namespace Database\Seeders;

use App\Models\KasKoperasi;
use Illuminate\Database\Seeder;

class KasKoperasiSeeder extends Seeder
{
    public function run(): void
    {
        KasKoperasi::firstOrCreate(['id' => 1], [
            'saldo_saat_ini' => 100_000_000, // sesuai angka dari meeting
        ]);
    }
}