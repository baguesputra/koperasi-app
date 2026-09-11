<?php

namespace App\Console\Commands;

use App\Models\Anggota;
use App\Models\Pinjaman;
use App\Services\Dokumen\PenomoranDokumenService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class BackfillNomorDokumen extends Command
{
    protected $signature = 'dokumen:backfill-nomor';

    protected $description = 'Isi nomor dokumen pinjaman & resign lama yang belum punya (urut per tahun).';

    public function handle(PenomoranDokumenService $penomoran): int
    {
        $pinjaman = 0;
        Pinjaman::whereNull('nomor_dokumen')
            ->whereIn('status', ['aktif', 'lunas'])
            ->orderBy('tanggal_pencairan')
            ->each(function (Pinjaman $p) use ($penomoran, &$pinjaman) {
                $p->update([
                    'nomor_dokumen' => $penomoran->berikutnya(
                        PenomoranDokumenService::JENIS_PINJAMAN,
                        Carbon::parse($p->tanggal_pencairan ?? now())
                    ),
                ]);
                $pinjaman++;
            });

        $resign = 0;
        Anggota::where('status', 'resign')
            ->orderBy('tanggal_resign')
            ->each(function (Anggota $a) use ($penomoran, &$resign) {
                $settlement = $a->resigned_settlement_json ?? [];
                if (! empty($settlement['doc_no'])) {
                    return;
                }
                $settlement['doc_no'] = $penomoran->berikutnya(
                    PenomoranDokumenService::JENIS_RESIGN,
                    Carbon::parse($a->tanggal_resign ?? now())
                );
                $a->update(['resigned_settlement_json' => $settlement]);
                $resign++;
            });

        $this->info("Pinjaman: {$pinjaman}, resign: {$resign} nomor diisi.");

        return self::SUCCESS;
    }
}
