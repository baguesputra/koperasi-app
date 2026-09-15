<?php

namespace App\Imports;

use App\Models\Anggota;
use App\Services\Migrasi\MigrasiPinjamanService;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class PinjamanMigrasiImport implements ToCollection, WithHeadingRow
{
    public array $berhasil = [];

    public array $gagal = [];

    public function __construct(private MigrasiPinjamanService $migrasi) {}

    public function collection($rows)
    {
        $userId = auth()->id();

        foreach ($rows as $index => $row) {
            $baris = $index + 2;

            $validasi = $this->validasiBaris($row, $baris);
            if ($validasi !== true) {
                $this->gagal[] = $validasi;

                continue;
            }

            $anggota = Anggota::where('no_karyawan', trim((string) $row['no_karyawan']))->first();

            try {
                $pinjaman = $this->migrasi->proses(
                    anggota: $anggota,
                    tanggalPengajuan: $this->parseTanggal($row['tanggal_pengajuan']),
                    tanggalPencairan: $this->parseTanggal($row['tanggal_pencairan']),
                    nominal: (float) $row['nominal'],
                    tenorBulan: (int) $row['tenor_bulan'],
                    persentaseBunga: (float) $row['bunga_persen'],
                    sudahBayar: (int) ($row['sudah_bayar_cicilan_ke'] ?? 0),
                    tanggalBayarTerakhir: empty($row['tanggal_bayar_terakhir'])
                        ? null
                        : $this->parseTanggal($row['tanggal_bayar_terakhir']),
                    userId: $userId,
                );

                $this->berhasil[] = "{$anggota->nama} ({$anggota->no_karyawan}) - Rp ".number_format($pinjaman->nominal, 0, ',', '.');
            } catch (\Throwable $e) {
                $this->gagal[] = "Baris {$baris}: {$e->getMessage()}";
            }
        }
    }

    private function validasiBaris($row, int $baris): true|string
    {
        $noKaryawan = trim((string) ($row['no_karyawan'] ?? ''));

        if ($noKaryawan === '') {
            return "Baris {$baris}: No Karyawan wajib diisi.";
        }

        if (! Anggota::where('no_karyawan', $noKaryawan)->where('status', 'aktif')->exists()) {
            return "Baris {$baris}: Anggota '{$noKaryawan}' tidak ditemukan atau tidak aktif.";
        }

        if (! $this->parseTanggal($row['tanggal_pengajuan'] ?? null)) {
            return "Baris {$baris}: Tanggal Pengajuan tidak valid.";
        }

        if (! $this->parseTanggal($row['tanggal_pencairan'] ?? null)) {
            return "Baris {$baris}: Tanggal Pencairan tidak valid.";
        }

        if (! is_numeric($row['nominal'] ?? null) || (float) $row['nominal'] <= 0) {
            return "Baris {$baris}: Nominal harus angka lebih dari 0.";
        }

        if (! is_numeric($row['tenor_bulan'] ?? null) || (int) $row['tenor_bulan'] < 1) {
            return "Baris {$baris}: Tenor Bulan minimal 1.";
        }

        if (! is_numeric($row['bunga_persen'] ?? null) || (float) $row['bunga_persen'] < 0) {
            return "Baris {$baris}: Bunga Persen harus angka 0 atau lebih.";
        }

        $sudahBayar = (int) ($row['sudah_bayar_cicilan_ke'] ?? 0);

        if ($sudahBayar < 0 || $sudahBayar > (int) $row['tenor_bulan']) {
            return "Baris {$baris}: Sudah Bayar Cicilan Ke harus 0 sampai tenor.";
        }

        if ($sudahBayar > 0 && ! empty($row['tanggal_bayar_terakhir']) && ! $this->parseTanggal($row['tanggal_bayar_terakhir'])) {
            return "Baris {$baris}: Tanggal Bayar Terakhir tidak valid.";
        }

        return true;
    }

    private function parseTanggal($value): ?string
    {
        try {
            if (empty($value)) {
                return null;
            }

            if (is_numeric($value)) {
                return Date::excelToDateTimeObject($value)->format('Y-m-d');
            }

            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}
