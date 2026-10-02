<?php

namespace Database\Seeders;

use App\Models\JurnalKas;
use App\Models\KasKoperasi;
use App\Services\Keuangan\JurnalKasService;
use Illuminate\Database\Seeder;

class KasKoperasiFinalizerSeeder extends Seeder
{
    /**
     * Tahap 2 (AKHIR seed): hitung saldo fisik real dari SEMUA jurnal.
     * Konsep Kas Tunggal: kolom pot virtual di-nol-kan (tak dipakai lagi).
     */
    public function run(): void
    {
        $turunFisik = $this->turunFisik();

        KasKoperasi::where('id', 1)->update([
            'saldo_pinjaman' => 0,
            'saldo_dana_sosial' => 0,
            'saldo_simpanan' => 0,
            'saldo_pengembalian_simpanan' => 0,
            'saldo_bank' => round($turunFisik['bank'], 2),
            'saldo_kas_kecil' => round($turunFisik['kas_kecil'], 2),
        ]);
    }

    /**
     * @return array{bank: float, kas_kecil: float}
     */
    private function turunFisik(): array
    {
        $bank = 0.0;
        $kas = 0.0;

        foreach (JurnalKas::cursor() as $j) {
            $jumlah = (float) $j->jumlah;

            if ($j->kantong === 'kas_kecil') {
                $kas += $j->tipe === 'masuk' ? $jumlah : -$jumlah;
            } elseif ($j->kantong === 'bank') {
                $bank += $j->tipe === 'masuk' ? $jumlah : -$jumlah;
            } elseif (in_array($j->kategori, JurnalKasService::KATEGORI_NON_FISIK, true)) {
                continue;
            } elseif ($j->tipe === 'masuk') {
                $bank += $jumlah;
            } else {
                $bank -= $jumlah;
            }
        }

        return ['bank' => $bank, 'kas_kecil' => $kas];
    }
}
