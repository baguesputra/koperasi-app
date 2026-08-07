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
            ['email' => 'admin@koperasi.test'],
            ['name' => 'Admin Koperasi', 'password' => Hash::make('password')]
        );
        $admin->assignRole('admin');

        $bendahara = User::firstOrCreate(
            ['email' => 'bendahara@koperasi.test'],
            ['name' => 'Bendahara Koperasi', 'password' => Hash::make('password')]
        );
        $bendahara->assignRole('bendahara');

        $ketua = User::firstOrCreate(
            ['email' => 'ketua@koperasi.test'],
            ['name' => 'Ketua Koperasi', 'password' => Hash::make('password')]
        );
        $ketua->assignRole('ketua_koperasi');

        // 4 akun anggota dummy, sesuai skenario testing
        $anggotaUsers = [
            ['email' => 'anggota.baru@koperasi.test', 'name' => 'Anggota Baru'],
            ['email' => 'anggota.sedang@koperasi.test', 'name' => 'Anggota Sedang'],
            ['email' => 'anggota.lama@koperasi.test', 'name' => 'Anggota Lama'],
            ['email' => 'anggota.reloan@koperasi.test', 'name' => 'Anggota Reloan'],
        ];

        foreach ($anggotaUsers as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                ['name' => $data['name'], 'password' => Hash::make('password')]
            );
            $user->assignRole('anggota');
        }
    }
}