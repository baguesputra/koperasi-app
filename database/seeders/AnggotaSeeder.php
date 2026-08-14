<?php

namespace Database\Seeders;

use App\Models\Anggota;
use App\Models\SettingSimpanan;
use App\Models\Simpanan;
use App\Models\User;
use Illuminate\Database\Seeder;

class AnggotaSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedAnggotaUtama();
        $this->seedAnggotaBulk();
        $this->seedPengurus();
    }

    /**
     * Buat record anggota untuk pengurus (Bendahara & Ketua) supaya mereka
     * bisa mengajukan pinjaman mandiri lewat portal yang sama dengan anggota.
     */
    private function seedPengurus(): void
    {
        $data = [
            [
                'no_karyawan_user' => 'BEN-000001',
                'no_anggota' => 'ANG-2020-0001',
                'nama' => 'Bendahara Koperasi',
                'cabang' => 'Banjarmasin',
                'unit_bisnis' => 'Keuangan',
                'department' => 'Keuangan',
                'divisi' => 'Akuntansi',
                'jabatan' => 'staff',
                'tanggal_mulai_kerja' => now()->subYears(8),
                'tanggal_jadi_anggota' => now()->subYears(7), // >= 5 tahun
            ],
            [
                'no_karyawan_user' => 'KET-000001',
                'no_anggota' => 'ANG-2019-0001',
                'nama' => 'Ketua Koperasi',
                'cabang' => 'Banjarmasin',
                'unit_bisnis' => 'Keuangan',
                'department' => 'Keuangan',
                'divisi' => 'Akuntansi',
                'jabatan' => 'hod',
                'tanggal_mulai_kerja' => now()->subYears(10),
                'tanggal_jadi_anggota' => now()->subYears(9), // >= 5 tahun
            ],
        ];

        foreach ($data as $row) {
            $user = User::where('no_karyawan', $row['no_karyawan_user'])->first();

            $anggota = Anggota::updateOrCreate(
                ['no_karyawan' => $row['no_karyawan_user']],
                $this->atributAnggota($row, $user?->id)
            );

            $this->catatSimpananPokok($anggota, $user?->id);
        }
    }

    /**
     * 4 anggota inti (skenario khusus pinjaman).
     */
    private function seedAnggotaUtama(): void
    {
        $data = [
            [
                'no_karyawan_user' => 'TOP-100001',
                'no_anggota' => 'ANG-2026-0001',
                'nama' => 'Budi Santoso',
                'cabang' => 'Banjarmasin',
                'unit_bisnis' => 'Operasional',
                'department' => 'Operasional',
                'divisi' => 'Lapangan',
                'jabatan' => 'staff',
                'tanggal_mulai_kerja' => now()->subMonths(9),
                'tanggal_jadi_anggota' => now()->subMonths(6), // < 1 tahun
            ],
            [
                'no_karyawan_user' => 'TOP-100002',
                'no_anggota' => 'ANG-2023-0045',
                'nama' => 'Siti Aminah',
                'cabang' => 'Samarinda',
                'unit_bisnis' => 'Keuangan',
                'department' => 'Keuangan',
                'divisi' => 'Akuntansi',
                'jabatan' => 'hod',
                'tanggal_mulai_kerja' => now()->subYears(3)->subMonths(2),
                'tanggal_jadi_anggota' => now()->subYears(3), // 1-5 tahun
            ],
            [
                'no_karyawan_user' => 'TOP-100003',
                'no_anggota' => 'ANG-2019-0012',
                'nama' => 'Ahmad Ridwan',
                'cabang' => 'Palangka',
                'unit_bisnis' => 'Operasional',
                'department' => 'Operasional',
                'divisi' => 'Gudang',
                'jabatan' => 'staff',
                'tanggal_mulai_kerja' => now()->subYears(7),
                'tanggal_jadi_anggota' => now()->subYears(6), // >= 5 tahun
            ],
            [
                'no_karyawan_user' => 'TOP-100004',
                'no_anggota' => 'ANG-2018-0003',
                'nama' => 'Dewi Lestari',
                'cabang' => 'Banjarmasin',
                'unit_bisnis' => 'Marketing',
                'department' => 'Marketing',
                'divisi' => 'Promosi',
                'jabatan' => 'hod',
                'tanggal_mulai_kerja' => now()->subYears(8),
                'tanggal_jadi_anggota' => now()->subYears(7), // >= 5 tahun, untuk test privilege reloan
            ],
        ];

        foreach ($data as $row) {
            $user = User::where('no_karyawan', $row['no_karyawan_user'])->first();

            $anggota = Anggota::firstOrCreate(
                ['no_anggota' => $row['no_anggota']],
                $this->atributAnggota($row, $user?->id)
            );

            $this->catatSimpananPokok($anggota, $user?->id);
        }
    }

    /**
     * 46 anggota tambahan (TOP-100005 s.d TOP-100050).
     */
    private function seedAnggotaBulk(): void
    {
        $cabang = ['Banjarmasin', 'Samarinda', 'Palangka'];
        $unitBisnis = ['Operasional', 'Keuangan', 'Marketing', 'HRD', 'Teknologi', 'Produksi'];
        $divisi = ['Lapangan', 'Akuntansi', 'Promosi', 'Umum', 'Gudang', 'Dukungan'];
        $nonaktifIndex = [8, 22];

        for ($i = 0; $i < 46; $i++) {
            $noKaryawan = sprintf('TOP-%06d', 100005 + $i);
            $user = User::where('no_karyawan', $noKaryawan)->first();

            // Variasi lama keanggotaan: i%4==0 -> <1 tahun; ==1|2 -> 1-5 tahun; ==3 -> >=5 tahun
            $durasi = match ($i % 4) {
                0 => now()->subMonths(4 + ($i % 5)),
                1 => now()->subYears(2)->subMonths($i % 11),
                2 => now()->subYears((3 + $i % 2))->subMonths($i % 7),
                3 => now()->subYears((5 + $i % 6))->subMonths($i % 5),
            };

            $tanggalMulaiKerja = $durasi->copy()->subMonths(3 + ($i % 8));

            $data = [
                'no_karyawan_user' => $noKaryawan,
                'no_anggota' => Anggota::generateNoAnggota(),
                'no_ktp' => '3207'.str_pad((string) $i, 12, '0', STR_PAD_LEFT),
                'nama' => $user?->name ?? "Anggota {$noKaryawan}",
                'cabang' => $cabang[$i % 3],
                'unit_bisnis' => $unitBisnis[$i % 6],
                'department' => $unitBisnis[$i % 6],
                'divisi' => $divisi[$i % 6],
                'jabatan' => $i % 6 === 0 ? 'hod' : 'staff',
                'tanggal_mulai_kerja' => $tanggalMulaiKerja,
                'tanggal_jadi_anggota' => $durasi,
                'status' => in_array($i, $nonaktifIndex) ? 'nonaktif' : 'aktif',
            ];

            $anggota = Anggota::firstOrCreate(
                ['no_karyawan' => $noKaryawan],
                $this->atributAnggota($data, $user?->id)
            );

            $this->catatSimpananPokok($anggota, $user?->id);
        }
    }

    private function atributAnggota(array $row, ?int $userId): array
    {
        return [
            'user_id' => $userId,
            'no_anggota' => $row['no_anggota'],
            'no_ktp' => $row['no_ktp'] ?? null,
            'no_karyawan' => $row['no_karyawan_user'],
            'nama' => $row['nama'],
            'cabang' => $row['cabang'],
            'unit_bisnis' => $row['unit_bisnis'],
            'department' => $row['department'] ?? null,
            'divisi' => $row['divisi'] ?? null,
            'jabatan' => $row['jabatan'],
            'tanggal_mulai_kerja' => $row['tanggal_mulai_kerja'],
            'tanggal_jadi_anggota' => $row['tanggal_jadi_anggota'],
            'status' => $row['status'] ?? 'aktif',
        ];
    }

    private function catatSimpananPokok(Anggota $anggota, ?int $userId): void
    {
        $nominalPokok = SettingSimpanan::where('jenis', 'pokok')->value('nominal') ?? 50_000;

        Simpanan::firstOrCreate(
            ['anggota_id' => $anggota->id, 'jenis' => 'pokok'],
            [
                'jumlah' => $nominalPokok,
                'bulan_periode' => $anggota->tanggal_jadi_anggota->format('Y-m'),
                'tanggal_input' => $anggota->tanggal_jadi_anggota,
                'input_by' => $userId,
            ]
        );
    }
}
