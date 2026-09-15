<?php

namespace App\Services\Migrasi;

use App\Models\Anggota;
use App\Models\AuditLog;
use App\Models\Simpanan;
use App\Services\Keuangan\JurnalKasService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MigrasiSimpananService
{
    private const KATEGORI = [
        'pokok' => 'simpanan_pokok_masuk',
        'wajib' => 'simpanan_wajib_masuk',
        'dana_sosial' => 'dana_sosial_bulanan',
    ];

    private const KANTONG = [
        'pokok' => 'simpanan',
        'wajib' => 'simpanan',
        'dana_sosial' => 'dana_sosial',
    ];

    public function __construct(private JurnalKasService $jurnalKas) {}

    /**
     * Catat satu setoran historis hasil migrasi data manual.
     */
    public function proses(
        Anggota $anggota,
        string $jenis,
        string $bulanPeriode,
        float $jumlah,
        string $tanggalInput,
        int $userId,
    ): Simpanan {
        return DB::transaction(function () use ($anggota, $jenis, $bulanPeriode, $jumlah, $tanggalInput, $userId) {
            $duplikat = Simpanan::where('anggota_id', $anggota->id)
                ->where('jenis', $jenis)
                ->where('bulan_periode', $bulanPeriode)
                ->exists();

            if ($duplikat) {
                throw new RuntimeException("Simpanan {$jenis} {$anggota->nama} periode {$bulanPeriode} sudah ada (duplikat).");
            }

            $simpanan = Simpanan::create([
                'anggota_id' => $anggota->id,
                'jenis' => $jenis,
                'jumlah' => $jumlah,
                'bulan_periode' => $bulanPeriode,
                'tanggal_input' => $tanggalInput,
                'input_by' => $userId,
            ]);

            $this->jurnalKas->catat(
                tipe: 'masuk',
                kategori: self::KATEGORI[$jenis],
                kantong: self::KANTONG[$jenis],
                jumlah: $jumlah,
                keterangan: "Simpanan {$jenis} (migrasi) {$bulanPeriode} - {$anggota->nama}",
                referensiId: $anggota->id,
                tanggal: $tanggalInput,
                userId: $userId,
            );

            AuditLog::catat(
                aksi: 'migrasi_simpanan',
                keterangan: "Migrasi simpanan {$jenis} {$anggota->nama} periode {$bulanPeriode}, jumlah {$jumlah}.",
                dataLama: null,
                dataBaru: [
                    'simpanan_id' => $simpanan->id,
                    'anggota_id' => $anggota->id,
                    'jenis' => $jenis,
                    'bulan_periode' => $bulanPeriode,
                    'jumlah' => $jumlah,
                ],
                userId: $userId,
            );

            return $simpanan;
        });
    }
}
