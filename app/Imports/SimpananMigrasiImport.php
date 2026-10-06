<?php

namespace App\Imports;

use App\Models\Anggota;
use App\Services\Migrasi\MigrasiSimpananService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class SimpananMigrasiImport implements ToCollection, WithHeadingRow
{
    public array $berhasil = [];

    public array $gagal = [];

    public function __construct(private MigrasiSimpananService $migrasi) {}

    public function collection(Collection $rows): void
    {
        if ($rows->count() > 5000) {
            $this->gagal[] = 'File melebihi 5000 baris. Pecah file lalu upload ulang.';

            return;
        }

        $userId = auth()->id();

        foreach ($rows as $index => $row) {
            $baris = $index + 2;

            $validasi = $this->validasiBaris($row, $baris);
            if ($validasi !== true) {
                $this->gagal[] = $validasi;

                continue;
            }

            $anggota = Anggota::where('no_karyawan', trim((string) $row['no_karyawan']))->first();
            $jenis = strtolower(trim((string) $row['jenis']));

            try {
                $this->migrasi->proses(
                    anggota: $anggota,
                    jenis: $jenis,
                    bulanPeriode: $this->parsePeriode($row['bulan_periode']),
                    jumlah: (float) $row['jumlah'],
                    tanggalInput: $this->parseTanggal($row['tanggal_input']),
                    userId: $userId,
                );

                $this->berhasil[] = "{$anggota->nama} ({$anggota->no_karyawan}) - {$jenis} {$row['bulan_periode']}";
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

        if (! in_array(strtolower(trim((string) ($row['jenis'] ?? ''))), ['pokok', 'wajib', 'dana_sosial'], true)) {
            return "Baris {$baris}: Jenis harus pokok, wajib, atau dana_sosial.";
        }

        if (! $this->parsePeriode($row['bulan_periode'] ?? null)) {
            return "Baris {$baris}: Bulan Periode tidak valid (format: 2024-01).";
        }

        if (! is_numeric($row['jumlah'] ?? null) || (float) $row['jumlah'] <= 0) {
            return "Baris {$baris}: Jumlah harus angka lebih dari 0.";
        }

        if (! $this->parseTanggal($row['tanggal_input'] ?? null)) {
            return "Baris {$baris}: Tanggal Input tidak valid.";
        }

        return true;
    }

    private function parsePeriode($value): ?string
    {
        try {
            if (empty($value)) {
                return null;
            }

            if (is_numeric($value)) {
                return Date::excelToDateTimeObject($value)->format('Y-m');
            }

            $periode = Carbon::parse($value)->format('Y-m');

            return preg_match('/^\d{4}-\d{2}$/', (string) $value) || strlen((string) $value) >= 7 ? $periode : null;
        } catch (\Exception $e) {
            return null;
        }
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
