<?php

namespace Database\Seeders;

use App\Models\Anggota;
use App\Models\User;
use Illuminate\Database\Seeder;

class AnggotaSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'email' => 'anggota.baru@koperasi.test',
                'no_anggota' => 'ANG-2026-0001',
                'nama' => 'Budi Santoso',
                'cabang' => 'Banjarmasin',
                'unit_bisnis' => 'Operasional',
                'jabatan' => 'staff',
                'tanggal_mulai_kerja' => now()->subMonths(9),
                'tanggal_jadi_anggota' => now()->subMonths(6), // < 1 tahun
            ],
            [
                'email' => 'anggota.sedang@koperasi.test',
                'no_anggota' => 'ANG-2023-0045',
                'nama' => 'Siti Aminah',
                'cabang' => 'Samarinda',
                'unit_bisnis' => 'Keuangan',
                'jabatan' => 'hod',
                'tanggal_mulai_kerja' => now()->subYears(3)->subMonths(2),
                'tanggal_jadi_anggota' => now()->subYears(3), // 1-5 tahun
            ],
            [
                'email' => 'anggota.lama@koperasi.test',
                'no_anggota' => 'ANG-2019-0012',
                'nama' => 'Ahmad Ridwan',
                'cabang' => 'Palangka',
                'unit_bisnis' => 'Operasional',
                'jabatan' => 'staff',
                'tanggal_mulai_kerja' => now()->subYears(7),
                'tanggal_jadi_anggota' => now()->subYears(6), // >= 5 tahun
            ],
            [
                'email' => 'anggota.reloan@koperasi.test',
                'no_anggota' => 'ANG-2018-0003',
                'nama' => 'Dewi Lestari',
                'cabang' => 'Banjarmasin',
                'unit_bisnis' => 'Marketing',
                'jabatan' => 'hod',
                'tanggal_mulai_kerja' => now()->subYears(8),
                'tanggal_jadi_anggota' => now()->subYears(7), // >= 5 tahun, untuk test privilege reloan
            ],
        ];

        foreach ($data as $row) {
            $user = User::where('email', $row['email'])->first();

            Anggota::firstOrCreate(
                ['no_anggota' => $row['no_anggota']],
                [
                    'user_id' => $user?->id,
                    'nama' => $row['nama'],
                    'cabang' => $row['cabang'],
                    'unit_bisnis' => $row['unit_bisnis'],
                    'jabatan' => $row['jabatan'],
                    'tanggal_mulai_kerja' => $row['tanggal_mulai_kerja'],
                    'tanggal_jadi_anggota' => $row['tanggal_jadi_anggota'],
                    'status' => 'aktif',
                ]
            );
        }
    }
}