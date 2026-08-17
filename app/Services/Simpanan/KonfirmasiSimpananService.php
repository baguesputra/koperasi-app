<?php

namespace App\Services\Simpanan;

use App\Models\Anggota;
use App\Models\Simpanan;
use App\Models\SettingSimpanan;
use App\Services\Keuangan\JurnalKasService;
use Illuminate\Support\Facades\DB;

class KonfirmasiSimpananService
{
    public function __construct(private JurnalKasService $jurnalKas) {}

    public function anggotaBelumSimpananWajib(string $bulanPeriode)
    {
        return Anggota::where('status', 'aktif')
            ->whereDoesntHave('simpanan', fn ($q) => $q
                ->where('jenis', 'wajib')
                ->where('bulan_periode', $bulanPeriode)
            )
            ->get();
    }

    public function konfirmasiMassal(array $anggotaIds, string $bulanPeriode, int $inputByUserId): int
    {
        $nominalWajib = SettingSimpanan::where('jenis', 'wajib')->value('nominal') ?? 0;
        $nominalDanaSosial = SettingSimpanan::where('jenis', 'dana_sosial')->value('nominal') ?? 0;

        DB::transaction(function () use ($anggotaIds, $bulanPeriode, $inputByUserId, $nominalWajib, $nominalDanaSosial) {
            foreach ($anggotaIds as $anggotaId) {
                Simpanan::create([
                    'anggota_id' => $anggotaId,
                    'jenis' => 'wajib',
                    'jumlah' => $nominalWajib,
                    'bulan_periode' => $bulanPeriode,
                    'tanggal_input' => now(),
                    'input_by' => $inputByUserId,
                ]);

                Simpanan::create([
                    'anggota_id' => $anggotaId,
                    'jenis' => 'dana_sosial',
                    'jumlah' => $nominalDanaSosial,
                    'bulan_periode' => $bulanPeriode,
                    'tanggal_input' => now(),
                    'input_by' => $inputByUserId,
                ]);

                $this->jurnalKas->catat(
                    tipe: 'masuk',
                    kategori: 'dana_sosial_bulanan',
                    kantong: 'dana_sosial',
                    jumlah: $nominalDanaSosial,
                    keterangan: "Dana sosial bulan {$bulanPeriode}",
                    referensiId: $anggotaId,
                    tanggal: now()->format('Y-m-d'),
                    userId: $inputByUserId,
                );
            }
        });

        return count($anggotaIds);
    }
}