<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions([
            'anggota.lihat', 'anggota.kelola',
            'simpanan.lihat',
            'pinjaman.lihat',
            'kas.lihat',
            'laporan.lihat',
            'pengaturan.kelola',
        ]);

        $bendahara = Role::firstOrCreate(['name' => 'bendahara']);
        $bendahara->syncPermissions([
            'anggota.lihat',
            'simpanan.lihat', 'simpanan.konfirmasi',
            'pinjaman.lihat', 'pinjaman.tinjau-bendahara',
            'angsuran.konfirmasi',
            'kas.lihat', 'kas.topup',
            'laporan.lihat',
        ]);

        $ketua = Role::firstOrCreate(['name' => 'ketua_koperasi']);
        $ketua->syncPermissions([
            'anggota.lihat',
            'simpanan.lihat',
            'pinjaman.lihat', 'pinjaman.approve-ketua',
            'kas.lihat',
            'laporan.lihat',
        ]);

        $anggota = Role::firstOrCreate(['name' => 'anggota']);
        $anggota->syncPermissions(['portal.akses']);
    }
}