<?php

namespace App\Services\Migrasi;

use App\Models\Anggota;
use App\Models\AuditLog;
use App\Models\Pinjaman;
use App\Services\Dokumen\PenomoranDokumenService;
use App\Services\Keuangan\JurnalKasService;
use App\Services\Pinjaman\PerhitunganBungaService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MigrasiPinjamanService
{
    public function __construct(
        private PerhitunganBungaService $bunga,
        private JurnalKasService $jurnalKas,
        private PenomoranDokumenService $penomoran,
    ) {}

    /**
     * Buat satu pinjaman historis hasil migrasi data manual.
     * Eligibilitas/limit/tenor sengaja dilewati — data sudah terjadi.
     */
    public function proses(
        Anggota $anggota,
        string $tanggalPengajuan,
        string $tanggalPencairan,
        float $nominal,
        int $tenorBulan,
        float $persentaseBunga,
        int $sudahBayar,
        ?string $tanggalBayarTerakhir,
        int $userId,
    ): Pinjaman {
        return DB::transaction(function () use (
            $anggota, $tanggalPengajuan, $tanggalPencairan, $nominal,
            $tenorBulan, $persentaseBunga, $sudahBayar, $tanggalBayarTerakhir, $userId,
        ) {
            $duplikat = Pinjaman::where('anggota_id', $anggota->id)
                ->where('nominal', $nominal)
                ->whereDate('tanggal_pencairan', $tanggalPencairan)
                ->exists();

            if ($duplikat) {
                throw new RuntimeException("Pinjaman {$anggota->nama} nominal {$nominal} tanggal {$tanggalPencairan} sudah ada (duplikat).");
            }

            $pinjaman = Pinjaman::create([
                'anggota_id' => $anggota->id,
                'pengaju_user_id' => $userId,
                'nominal' => $nominal,
                'tenor_bulan' => $tenorBulan,
                'keperluan' => 'Migrasi data manual',
                'persentase_bunga' => $persentaseBunga,
                'status' => 'aktif',
                'tanggal_pengajuan' => $tanggalPengajuan,
                'tanggal_pencairan' => $tanggalPencairan,
                'nomor_dokumen' => $this->penomoran->berikutnya(
                    PenomoranDokumenService::JENIS_PINJAMAN,
                    Carbon::parse($tanggalPencairan)
                ),
                'catatan_ketua' => 'Migrasi data manual.',
            ]);

            $this->bunga->simpanJadwal($pinjaman);

            // Jurnal keluar pencairan — validasi saldo otomatis di JurnalKasService.
            $this->jurnalKas->catat(
                tipe: 'keluar',
                kategori: 'pencairan_pinjaman',
                kantong: 'pinjaman',
                jumlah: $nominal,
                keterangan: "Pencairan pinjaman (migrasi) - {$anggota->nama}",
                referensiId: $pinjaman->id,
                tanggal: Carbon::parse($tanggalPencairan)->format('Y-m-d'),
                userId: $userId,
            );

            // Tandai angsuran 1..N lunas + jurnal masuk per cicilan.
            $batasBayar = $tanggalBayarTerakhir ? Carbon::parse($tanggalBayarTerakhir) : null;

            $lunas = $pinjaman->angsuran()->orderBy('cicilan_ke')->get();

            foreach ($lunas as $angsuran) {
                if ($angsuran->cicilan_ke > $sudahBayar) {
                    break;
                }

                $tanggalBayar = $angsuran->tanggal_jatuh_tempo
                    ? Carbon::parse($angsuran->tanggal_jatuh_tempo->format('Y-m-d'))
                    : $batasBayar;

                if ($batasBayar && $tanggalBayar && $tanggalBayar->greaterThan($batasBayar)) {
                    $tanggalBayar = $batasBayar;
                }

                $tanggalBayar ??= Carbon::parse($tanggalPencairan);

                $angsuran->update([
                    'status' => 'lunas',
                    'tanggal_konfirmasi_bayar' => $tanggalBayar->format('Y-m-d'),
                    'confirmed_by' => $userId,
                ]);

                $this->jurnalKas->catat(
                    tipe: 'masuk',
                    kategori: 'pembayaran_angsuran',
                    kantong: 'pinjaman',
                    jumlah: (float) $angsuran->total_bayar,
                    keterangan: "Angsuran ke-{$angsuran->cicilan_ke} (migrasi) - {$anggota->nama}",
                    referensiId: $angsuran->id,
                    tanggal: $tanggalBayar->format('Y-m-d'),
                    userId: $userId,
                );
            }

            if ($sudahBayar >= $tenorBulan) {
                $pinjaman->update(['status' => 'lunas']);
            }

            AuditLog::catat(
                aksi: 'migrasi_pinjaman',
                keterangan: "Migrasi pinjaman #{$pinjaman->id} ({$anggota->nama}), nominal {$nominal}, tenor {$tenorBulan}, lunas {$sudahBayar} cicilan.",
                dataLama: null,
                dataBaru: [
                    'pinjaman_id' => $pinjaman->id,
                    'anggota_id' => $anggota->id,
                    'nominal' => $nominal,
                    'tenor_bulan' => $tenorBulan,
                    'sudah_bayar' => $sudahBayar,
                ],
                userId: $userId,
            );

            return $pinjaman->refresh();
        });
    }
}
