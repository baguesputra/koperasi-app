<?php

namespace Database\Seeders;

use App\Models\Anggota;
use App\Models\Simpanan;
use App\Models\SettingSimpanan;
use App\Models\User;
use Illuminate\Database\Seeder;

class AnggotaSeeder extends Seeder
{
    public function run(): void
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

        $nominalPokok = SettingSimpanan::where('jenis', 'pokok')->value('nominal') ?? 50_000;

        foreach ($data as $row) {
            $user = User::where('no_karyawan', $row['no_karyawan_user'])->first();

            $anggota = Anggota::firstOrCreate(
                ['no_anggota' => $row['no_anggota']],
                [
                    'user_id' => $user?->id,
                    'no_karyawan' => $row['no_karyawan_user'],
                    'nama' => $row['nama'],
                    'cabang' => $row['cabang'],
                    'unit_bisnis' => $row['unit_bisnis'],
                    'department' => $row['department'],
                    'divisi' => $row['divisi'],
                    'jabatan' => $row['jabatan'],
                    'tanggal_mulai_kerja' => $row['tanggal_mulai_kerja'],
                    'tanggal_jadi_anggota' => $row['tanggal_jadi_anggota'],
                    'status' => 'aktif',
                ]
            );

            // Otomatis catat Simpanan Pokok, konsisten dengan alur "Tambah Anggota" di UI
            Simpanan::firstOrCreate(
                ['anggota_id' => $anggota->id, 'jenis' => 'pokok'],
                [
                    'jumlah' => $nominalPokok,
                    'bulan_periode' => $row['tanggal_jadi_anggota']->format('Y-m'),
                    'tanggal_input' => $row['tanggal_jadi_anggota'],
                    'input_by' => $user?->id,
                ]
            );
        }
    }
}