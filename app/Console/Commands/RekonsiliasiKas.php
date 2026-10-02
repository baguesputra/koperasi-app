<?php

namespace App\Console\Commands;

use App\Models\JurnalKas;
use App\Models\KasKoperasi;
use App\Services\Keuangan\JurnalKasService;
use Illuminate\Console\Command;

class RekonsiliasiKas extends Command
{
    protected $signature = 'kas:rekonsiliasi';

    protected $description = 'Cek kas_koperasi vs jumlah jurnal per kantong (virtual + fisik) + utang talangan terbuka. Mismatch = gagal, tanpa koreksi otomatis.';

    public function handle(JurnalKasService $jurnalKas): int
    {
        $kas = KasKoperasi::firstOrFail();
        $gagal = false;
        $baris = [];

        $virtual = array_diff_key(JurnalKasService::KANTONG_SALDO, array_flip(['bank', 'kas_kecil']));

        foreach ($virtual as $kantong => $kolom) {
            $dariJurnal = (float) JurnalKas::where('kantong', $kantong)
                ->selectRaw("SUM(CASE WHEN tipe = 'masuk' THEN jumlah ELSE -jumlah END) as total")
                ->value('total');

            $diKas = (float) $kas->{$kolom};
            $selisih = round($diKas - $dariJurnal, 2);
            $ok = abs($selisih) < 0.01;

            if (! $ok) {
                $gagal = true;
            }

            $baris[] = [$kantong, $kolom, $diKas, $dariJurnal, $selisih, $ok ? 'OK' : 'SELISIH'];
        }

        // Fisik: turun dari aturan efek per baris jurnal (harus identik dgn catat()).
        [$bankJurnal, $kasJurnal] = $this->turunFisik();
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

        $utang = $jurnalKas->utangTalanganTerbuka();
        $this->table(['Utang talangan terbuka', 'Rp'], [
            ['Ke dana sosial', number_format($utang['dana_sosial'], 0, ',', '.')],
            ['Ke simpanan', number_format($utang['simpanan'], 0, ',', '.')],
        ]);

        if ($gagal) {
            $this->error('Rekonsiliasi GAGAL: ada selisih kas vs jurnal. Selidiki jurnal terakhir sebelum lanjut.');

            return self::FAILURE;
        }

        $this->info('Rekonsiliasi OK: kas = jurnal per kantong (virtual + fisik).');

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

            if ($j->kantong === 'bank') {
                $bank += $j->tipe === 'masuk' ? $jumlah : -$jumlah;
            } elseif ($j->kantong === 'kas_kecil') {
                $kas += $j->tipe === 'masuk' ? $jumlah : -$jumlah;
            } elseif (in_array($j->kategori, JurnalKasService::KATEGORI_VIRTUAL_SAJA, true)) {
                continue;
            } elseif ($j->tipe === 'masuk') {
                $bank += $jumlah;
            } elseif (in_array($j->kategori, JurnalKasService::KATEGORI_KAS_KECIL, true)) {
                $kas -= $jumlah;
            } else {
                $bank -= $jumlah;
            }
        }

        return [$bank, $kas];
    }
}
