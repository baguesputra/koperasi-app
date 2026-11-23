<?php

namespace App\Console\Commands;

use App\Services\Tabungan\PerhitunganBungaTabunganService;
use Illuminate\Console\Command;
use RuntimeException;

class PostingBungaTabungan extends Command
{
    protected $signature = 'tabungan:posting-bunga {--periode= : Periode YYYY-MM yang akan diposting (default: bulan lalu)}';

    protected $description = 'Menghitung dan mengkreditkan bunga bulanan tabungan anggota secara idempoten';

    public function handle(PerhitunganBungaTabunganService $service): int
    {
        $periode = $this->option('periode') ?: now()->subMonthNoOverflow()->format('Y-m');

        try {
            $hasil = $service->postingPeriode((string) $periode, null);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Posting bunga periode %s selesai: %d diproses, %d dilewati, total Rp %s.',
            $hasil['periode'],
            $hasil['diproses'],
            $hasil['dilewati'],
            number_format($hasil['total_bunga'], 0, ',', '.')
        ));

        return self::SUCCESS;
    }
}
