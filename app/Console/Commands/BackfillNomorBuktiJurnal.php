<?php

namespace App\Console\Commands;

use App\Models\JurnalKas;
use App\Services\Dokumen\PenomoranDokumenService;
use App\Services\Keuangan\JurnalKasService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class BackfillNomorBuktiJurnal extends Command
{
    protected $signature = 'jurnal:backfill-bukti';

    protected $description = 'Isi no_bukti jurnal kas lama yang belum punya (urut tanggal, per jenis/tahun).';

    public function handle(PenomoranDokumenService $penomoran): int
    {
        $isi = 0;

        // While-loop tanpa offset: baris yang sudah diisi keluar dari hasil
        // query sehingga tidak ada yang terlewat (aman dari bug chunk+offset).
        while (true) {
            $batch = JurnalKas::whereNull('no_bukti')
                ->orderBy('tanggal')
                ->orderBy('id')
                ->limit(500)
                ->get();

            if ($batch->isEmpty()) {
                break;
            }

            foreach ($batch as $j) {
                $j->update([
                    'no_bukti' => $penomoran->berikutnya(
                        JurnalKasService::jenisBukti($j->tipe, $j->kategori),
                        Carbon::parse($j->tanggal)
                    ),
                ]);
                $isi++;
            }
        }

        $this->info("No. bukti diisi: {$isi} baris.");

        return self::SUCCESS;
    }
}
