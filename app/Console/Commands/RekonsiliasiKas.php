<?php

namespace App\Console\Commands;

use App\Models\JurnalKas;
use App\Models\KasKoperasi;
use App\Services\Keuangan\JurnalKasService;
use Illuminate\Console\Command;

class RekonsiliasiKas extends Command
{
    protected $signature = 'kas:rekonsiliasi';

    protected $description = 'Cek kas_koperasi vs jumlah jurnal per kantong + utang talangan terbuka. Mismatch = gagal, tanpa koreksi otomatis.';

    public function handle(JurnalKasService $jurnalKas): int
    {
        $kas = KasKoperasi::firstOrFail();
        $gagal = false;
        $baris = [];

        foreach (JurnalKasService::KANTONG_SALDO as $kantong => $kolom) {
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

        $this->info('Rekonsiliasi OK: kas = jurnal per kantong.');

        return self::SUCCESS;
    }
}
