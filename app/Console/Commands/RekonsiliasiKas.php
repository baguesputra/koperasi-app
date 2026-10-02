<?php

namespace App\Console\Commands;

use App\Models\JurnalKas;
use App\Models\KasKoperasi;
use App\Services\Keuangan\JurnalKasService;
use Illuminate\Console\Command;

class RekonsiliasiKas extends Command
{
    protected $signature = 'kas:rekonsiliasi';

    protected $description = 'Cek saldo fisik kas_koperasi (bank + kas kecil) vs derivasi aturan efek per baris jurnal. Mismatch = gagal, tanpa koreksi otomatis.';

    public function handle(): int
    {
        $kas = KasKoperasi::firstOrFail();
        $gagal = false;

        [$bankJurnal, $kasJurnal] = $this->turunFisik();

        $baris = [];
        foreach ([['bank', 'saldo_bank', $bankJurnal], ['kas_kecil', 'saldo_kas_kecil', $kasJurnal]] as [$kantong, $kolom, $dariJurnal]) {
            $diKas = (float) $kas->{$kolom};
            $selisih = round($diKas - $dariJurnal, 2);
            $ok = abs($selisih) < 0.01;

            if (! $ok) {
                $gagal = true;
            }

            $baris[] = [$kantong, $kolom, $diKas, $dariJurnal, $selisih, $ok ? 'OK' : 'SELISIH'];
        }

        $this->table(['Kantong', 'Kolom', 'kas_koperasi', 'sum(jurnal)', 'Selisih', 'Status'], $baris);

        if ($gagal) {
            $this->error('Rekonsiliasi GAGAL: ada selisih kas vs jurnal. Selidiki jurnal terakhir sebelum lanjut.');

            return self::FAILURE;
        }

        $this->info('Rekonsiliasi OK: saldo fisik = derivasi jurnal.');

        return self::SUCCESS;
    }

    /**
     * Derivasi saldo fisik bank & kas kecil dari seluruh baris jurnal,
     * mengikuti aturan yang sama dengan JurnalKasService::catat().
     *
     * @return array{0: float, 1: float} [bank, kas_kecil]
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

        return [$bank, $kas];
    }
}
