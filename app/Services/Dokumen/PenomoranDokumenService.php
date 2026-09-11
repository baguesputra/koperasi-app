<?php

namespace App\Services\Dokumen;

use App\Models\PenomoranDokumen;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PenomoranDokumenService
{
    public const JENIS_PINJAMAN = 'KOP-PJM';

    public const JENIS_RESIGN = 'KOP-RSGN';

    private const ROMAWI = [1 => 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];

    public function berikutnya(string $jenis, Carbon $tanggal): string
    {
        return DB::transaction(function () use ($jenis, $tanggal) {
            $counter = PenomoranDokumen::lockForUpdate()
                ->firstOrCreate(
                    ['jenis' => $jenis, 'tahun' => $tanggal->year],
                    ['terakhir' => 0]
                );

            $counter->increment('terakhir');

            return sprintf(
                '%03d/%s/%s/%d',
                $counter->terakhir,
                $jenis,
                self::ROMAWI[$tanggal->month],
                $tanggal->year
            );
        });
    }

    public function resetTahunan(string $jenis, int $tahun): void
    {
        PenomoranDokumen::updateOrCreate(
            ['jenis' => $jenis, 'tahun' => $tahun],
            ['terakhir' => 0]
        );
    }
}
