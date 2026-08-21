<?php

namespace App\Imports;

use App\Models\Anggota;
use App\Models\SettingSimpanan;
use App\Models\Simpanan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class AnggotaImport implements ToCollection, WithHeadingRow
{
    public array $berhasil = [];

    public array $gagal = [];

    public function collection($rows)
    {
        $nominalPokok = SettingSimpanan::where('jenis', 'pokok')->value('nominal') ?? 50_000;
        $adminId = auth()->id();

        foreach ($rows as $index => $row) {
            $baris = $index + 2;

            $validasi = $this->validasiBaris($row, $baris);
            if ($validasi !== true) {
                $this->gagal[] = $validasi;

                continue;
            }

            $noKaryawan = trim((string) $row['no_karyawan']);

            $user = User::create([
                'name' => trim((string) $row['nama']),
                'no_karyawan' => $noKaryawan,
                'password' => Hash::make($noKaryawan),
                'harus_ganti_password' => true,
            ]);
            $user->assignRole('anggota');

            $anggota = Anggota::create([
                'user_id' => $user->id,
                'no_anggota' => Anggota::generateNoAnggota(),
                'no_karyawan' => $noKaryawan,
                'no_ktp' => trim((string) $row['no_ktp']),
                'nama' => trim((string) $row['nama']),
                'cabang' => trim((string) $row['cabang']),
                'unit_bisnis' => trim((string) $row['unit_bisnis']),
                'department' => trim((string) $row['department']),
                'divisi' => trim((string) $row['divisi']),
                'jabatan' => strtolower(trim((string) $row['jabatan'])),
                'tanggal_mulai_kerja' => $this->parseTanggal($row['tanggal_mulai_kerja']),
                'tanggal_jadi_anggota' => $this->parseTanggal($row['tanggal_jadi_anggota']),
                'status' => 'aktif',
            ]);

            Simpanan::create([
                'anggota_id' => $anggota->id,
                'jenis' => 'pokok',
                'jumlah' => $nominalPokok,
                'bulan_periode' => now()->format('Y-m'),
                'tanggal_input' => now(),
                'input_by' => $adminId,
            ]);

            $this->berhasil[] = "{$anggota->nama} ({$anggota->no_anggota})";
        }
    }

    private function validasiBaris($row, int $baris): true|string
    {
        if (empty($row['nama'])) {
            return "Baris {$baris}: Nama wajib diisi.";
        }

        if (empty($row['no_karyawan'])) {
            return "Baris {$baris}: No Karyawan wajib diisi.";
        }

        if (User::where('no_karyawan', trim((string) $row['no_karyawan']))->exists()) {
            return "Baris {$baris}: No Karyawan '{$row['no_karyawan']}' sudah terdaftar.";
        }

        $jabatan = strtolower(trim((string) ($row['jabatan'] ?? '')));
        if (! in_array($jabatan, ['staff', 'hod'])) {
            return "Baris {$baris}: Jabatan harus 'staff' atau 'hod'.";
        }

        if (empty($row['tanggal_mulai_kerja']) || ! $this->parseTanggal($row['tanggal_mulai_kerja'])) {
            return "Baris {$baris}: Tanggal Mulai Kerja tidak valid.";
        }

        if (empty($row['tanggal_jadi_anggota']) || ! $this->parseTanggal($row['tanggal_jadi_anggota'])) {
            return "Baris {$baris}: Tanggal Jadi Anggota tidak valid.";
        }

        return true;
    }

    private function parseTanggal($value): ?string
    {
        try {
            if (is_numeric($value)) {
                // Excel serial date
                return Date::excelToDateTimeObject($value)->format('Y-m-d');
            }

            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}
