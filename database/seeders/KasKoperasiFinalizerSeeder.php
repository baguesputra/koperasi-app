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

        $turunFisik = $this->turunFisik();

        KasKoperasi::where('id', 1)->update([
            'saldo_pinjaman' => max(0, (float) $hitung('pinjaman')),
            'saldo_dana_sosial' => max(0, (float) $hitung('dana_sosial')),
            'saldo_simpanan' => max(0, (float) $hitung('simpanan')),
            'saldo_pengembalian_simpanan' => max(0, (float) $hitung('pengembalian_simpanan')),
            'saldo_bank' => round(max(0, $turunFisik['bank']), 2),
            'saldo_kas_kecil' => round(max(0, $turunFisik['kas_kecil']), 2),
        ]);
    }

    /**
     * @return array{bank: float, kas_kecil: float}
     */
    private function turunFisik(): array
    {
        $bank = 0.0;
        $kas = 0.0;

        foreach (\App\Models\JurnalKas::cursor() as $j) {
            $jumlah = (float) $j->jumlah;

            if ($j->kantong === 'bank') {
                $bank += $j->tipe === 'masuk' ? $jumlah : -$jumlah;
            } elseif ($j->kantong === 'kas_kecil') {
                $kas += $j->tipe === 'masuk' ? $jumlah : -$jumlah;
            } elseif (in_array($j->kategori, \App\Services\Keuangan\JurnalKasService::KATEGORI_VIRTUAL_SAJA, true)) {
                continue;
            } elseif ($j->tipe === 'masuk') {
                $bank += $jumlah;
            } elseif ($j->kantong === 'kas_kecil' && in_array($j->kategori, \App\Services\Keuangan\JurnalKasService::KATEGORI_KAS_KECIL, true)) {
                $kas -= $jumlah;
            } else {
                $bank -= $jumlah;
            }
        }

        return ['bank' => $bank, 'kas_kecil' => $kas];
    }
}
