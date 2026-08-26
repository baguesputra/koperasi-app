<?php

namespace App\Services\Simpanan;

use App\Models\Anggota;
use App\Models\AuditLog;
use App\Models\SettingSimpanan;
use App\Models\Simpanan;
use App\Services\Keuangan\JurnalKasService;
use Illuminate\Support\Facades\DB;

class KonfirmasiSimpananService
{
    public function __construct(private JurnalKasService $jurnalKas) {}

    public function anggotaBelumSimpananWajib(string $bulanPeriode, ?string $cabang = null)
    {
        return Anggota::where('status', 'aktif')
            ->when($cabang, fn ($q) => $q->where('cabang', $cabang))
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

        return DB::transaction(function () use ($anggotaIds, $bulanPeriode, $inputByUserId, $nominalWajib, $nominalDanaSosial) {
            // Lock baris anggota yang mau diproses, supaya proses lain tidak bisa
            // proses anggota yang sama secara bersamaan
            $anggotaTerkunci = Anggota::whereIn('id', $anggotaIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $jumlahDiproses = 0;
            $totalWajib = 0.0;
            $totalDanaSosial = 0.0;
            $processedAnggota = [];

            foreach ($anggotaIds as $anggotaId) {
                if (! $anggotaTerkunci->has($anggotaId)) {
                    continue;
                }

                // Cek ULANG di dalam lock - pastikan belum ada simpanan wajib bulan ini
                // (mencegah duplikasi kalau ada proses lain yang barusan submit)
                $sudahAda = Simpanan::where('anggota_id', $anggotaId)
                    ->where('jenis', 'wajib')
                    ->where('bulan_periode', $bulanPeriode)
                    ->exists();

                if ($sudahAda) {
                    continue; // sudah dikonfirmasi proses lain, lewati
                }

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

                // Jurnal simpanan wajib masuk (kantong simpanan)
                $this->jurnalKas->catat(
                    tipe: 'masuk',
                    kategori: 'simpanan_wajib_masuk',
                    kantong: 'simpanan',
                    jumlah: $nominalWajib,
                    keterangan: "Simpanan wajib bulan {$bulanPeriode}",
                    referensiId: $anggotaId,
                    tanggal: now()->format('Y-m-d'),
                    userId: $inputByUserId,
                    subJudul: 'Simpanan wajib masuk',
                );

                $jumlahDiproses++;
                $totalWajib += $nominalWajib;
                $totalDanaSosial += $nominalDanaSosial;
                $processedAnggota[] = $anggotaId;
            }

            // Audit log untuk konfirmasi massal simpanan
            AuditLog::catat(
                aksi: 'simpanan_konfirmasi_massal',
                keterangan: "Konfirmasi simpanan {$jumlahDiproses} anggota (wajib: ".number_format($totalWajib, 0, ',', '.').", dana sosial: ".number_format($totalDanaSosial, 0, ',', '.').") untuk periode {$bulanPeriode} oleh user #{$inputByUserId}",
                dataLama: ['requested_ids' => $anggotaIds, 'bulan_periode' => $bulanPeriode],
                dataBaru: [
                    'confirmed_count' => $jumlahDiproses,
                    'total_wajib' => $totalWajib,
                    'total_dana_sosial' => $totalDanaSosial,
                    'input_by' => $inputByUserId,
                    'bulan_periode' => $bulanPeriode,
                    'anggota_ids' => $processedAnggota,
                ]
            );

            return $jumlahDiproses;
        });
    }
}
