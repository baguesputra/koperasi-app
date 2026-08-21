<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['no_karyawan' => 'ADM-000001'],
            ['name' => 'Admin Koperasi', 'email' => 'admin@koperasi.test', 'password' => Hash::make('ADM-000001')]
        );
        $admin->assignRole('admin');

        $bendahara = User::firstOrCreate(
            ['no_karyawan' => 'BEN-000001'],
            ['name' => 'Bendahara Koperasi', 'email' => 'bendahara@koperasi.test', 'password' => Hash::make('BEN-000001')]
        );
        $bendahara->assignRole('bendahara');

        $ketua = User::firstOrCreate(
            ['no_karyawan' => 'KET-000001'],
            ['name' => 'Ketua Koperasi', 'email' => 'ketua@koperasi.test', 'password' => Hash::make('KET-000001')]
        );
        $ketua->assignRole('ketua_koperasi');

        $anggotaBulk = $this->anggotaBulk();

        $anggotaUsers = [
            ['no_karyawan' => 'TOP-100001', 'email' => 'anggota.baru@koperasi.test', 'name' => 'Anggota Baru'],
            ['no_karyawan' => 'TOP-100002', 'email' => 'anggota.sedang@koperasi.test', 'name' => 'Anggota Sedang'],
            ['no_karyawan' => 'TOP-100003', 'email' => 'anggota.lama@koperasi.test', 'name' => 'Anggota Lama'],
            ['no_karyawan' => 'TOP-100004', 'email' => 'anggota.reloan@koperasi.test', 'name' => 'Anggota Reloan'],
        ];

        foreach (array_merge($anggotaUsers, $anggotaBulk) as $i => $data) {
            $user = User::firstOrCreate(
                ['no_karyawan' => $data['no_karyawan'], 'email' => $data['email']],
                ['name' => $data['name'], 'password' => Hash::make($data['no_karyawan'])]
            );
            $user->assignRole('anggota');
        }
    }

    /**
     * Nama anggota tambahan: TOP-100005 s.d TOP-100050 (46 user).
     */
    private function anggotaBulk(): array
    {
        $namaBulk = [
            'Agus Wijaya', 'Rina Marlina', 'Bambang Sutrisno', 'Sari Rahayu', 'Hendra Gunawan',
            'Dewi Anggraini', 'Joko Susanto', 'Maya Sari', 'Adi Nugroho', 'Lina Wijayanti',
            'Rizky Pratama', 'Nia Kurniawati', 'Eko Prasetyo', 'Putri Handayani', 'Fajar Ramadhan',
            'Indah Permata', 'Yudha Pradana', 'Sri Wahyuni', 'Andi Firmansyah', 'Ratna Sari',
            'Deni Setiawan', 'Fitriani', 'Rudi Hartono', 'Susi Susanti', 'Bayu Saputra',
            'Ayu Lestari', 'Toni Kurniawan', 'Tuti Herawati', 'Ferry Ardiansyah', 'Desi Ratnasari',
            'Imam Santoso', 'Widya Astuti', 'Galih Prakoso', 'Nur Aini', 'Satria Bima',
            'Laila Amalia', 'Wisnu Prasetyo', 'Mega Puspita', 'Dimas Anggara', 'Nabila Putri',
            'Candra Wijaya', 'Yuni Astuti', 'Arif Hidayat', 'Rina Kusuma', 'Bagus Pamungkas',
            'Citra Ramadhani',
        ];

        $anggota = [];

        foreach ($namaBulk as $i => $nama) {
            $noKaryawan = sprintf('TOP-%06d', 100005 + $i);
            $anggota[] = [
                'no_karyawan' => $noKaryawan,
                'email' => 'anggota.'.strtolower(str_replace(' ', '', $nama)).'@koperasi.test',
                'name' => $nama,
            ];
        }

        return $anggota;
    }
}
