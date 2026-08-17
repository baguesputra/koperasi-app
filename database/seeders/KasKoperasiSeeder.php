<?php

namespace Database\Seeders;

use App\Models\KasKoperasi;
use App\Models\User;
use App\Services\Keuangan\JurnalKasService;
use Illuminate\Database\Seeder;

class KasKoperasiSeeder extends Seeder
{
    public function run(): void
    {
        KasKoperasi::firstOrCreate(['id' => 1], [
            'saldo_pinjaman' => 100_000_000,
            'saldo_dana_sosial' => 3_000_000,
        ]);

        $adminId = User::where('no_karyawan', 'ADM-000001')->value('id') ?? 1;

        (new JurnalKasService())->catatSaldoAwal('pinjaman', 100_000_000, $adminId);
    }
}