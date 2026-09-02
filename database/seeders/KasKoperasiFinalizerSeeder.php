<?php

namespace Database\Seeders;

use App\Models\KasKoperasi;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KasKoperasiFinalizerSeeder extends Seeder
{
    /**
     * Tahap 2 (AKHIR seed): hitung saldo real dari SEMUA jurnal,
     * lalu update kas_koperasi dengan nilai benar.
     */
    public function run(): void
    {
        $hitung = fn (string $kantong) => (float) DB::table('jurnal_kas')
            ->where('kantong', $kantong)
            ->selectRaw("SUM(CASE WHEN tipe = 'masuk' THEN jumlah ELSE -jumlah END) as total")
            ->value('total') ?? 0;

        KasKoperasi::where('id', 1)->update([
            'saldo_pinjaman' => max(0, (float) $hitung('pinjaman')),
            'saldo_dana_sosial' => max(0, (float) $hitung('dana_sosial')),
            'saldo_simpanan' => max(0, (float) $hitung('simpanan')),
            'saldo_pengembalian_simpanan' => max(0, (float) $hitung('pengembalian_simpanan')),
        ]);
    }
}
