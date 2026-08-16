<?php

namespace Database\Seeders;

use App\Models\KasKoperasi;
use Illuminate\Database\Seeder;

class KasKoperasiSeeder extends Seeder
{
    public function run(): void
    {
        KasKoperasi::firstOrCreate(['id' => 1], [
            'saldo_pinjaman' => 100_000_000,
            'saldo_dana_sosial' => 0,
        ]);
    }
}