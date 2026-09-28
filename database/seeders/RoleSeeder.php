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
            'anggota.lihat', 'anggota.kelola', 'anggota.resign',
            'simpanan.lihat', 'simpanan.konfirmasi',
            'angsuran.konfirmasi',
            'pinjaman.lihat', 'pinjaman.tinjau-bendahara', 'pinjaman.approve-ketua',
            'limit.tinjau-bendahara', 'limit.approve-ketua',
            'kas.lihat', 'kas.topup',
            'laporan.lihat',
            'pengaturan.kelola', 'user.kelola',
            'migrasi.kelola',
        ]);

        $bendahara = Role::firstOrCreate(['name' => 'bendahara']);
        $bendahara->syncPermissions([
            'anggota.lihat',
            'simpanan.lihat', 'simpanan.konfirmasi',
            'pinjaman.lihat', 'pinjaman.tinjau-bendahara',
            'limit.tinjau-bendahara',
            'angsuran.konfirmasi',
            'kas.lihat', 'kas.topup',
            'laporan.lihat',
            'portal.akses',
        ]);

        $ketua = Role::firstOrCreate(['name' => 'ketua_koperasi']);
        $ketua->syncPermissions([
            'anggota.lihat',
            'simpanan.lihat',
            'pinjaman.lihat', 'pinjaman.approve-ketua',
            'limit.approve-ketua',
            'kas.lihat',
            'laporan.lihat',
            'portal.akses',
        ]);

        $anggota = Role::firstOrCreate(['name' => 'anggota']);
        $anggota->syncPermissions(['portal.akses']);
    }
}
