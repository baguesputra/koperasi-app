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

        $anggotaUsers = [
            ['no_karyawan' => 'TOP-100001', 'email' => 'anggota.baru@koperasi.test','name' => 'Anggota Baru'],
            ['no_karyawan' => 'TOP-100002', 'email' => 'anggota.sedang@koperasi.test','name' => 'Anggota Sedang'],
            ['no_karyawan' => 'TOP-100003', 'email' => 'anggota.lama@koperasi.test','name' => 'Anggota Lama'],
            ['no_karyawan' => 'TOP-100004', 'email' => 'anggota.reloan@koperasi.test','name' => 'Anggota Reloan'],
        ];

        foreach ($anggotaUsers as $data) {
            $user = User::firstOrCreate(
                ['no_karyawan' => $data['no_karyawan'],'email' => $data['email']],
                ['name' => $data['name'], 'password' => Hash::make($data['no_karyawan'])]
            );
            $user->assignRole('anggota');
        }
    }
}