<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'anggota.lihat',
            'anggota.kelola',
            'anggota.resign',
            'simpanan.lihat',
            'simpanan.konfirmasi',
            'pinjaman.lihat',
            'pinjaman.tinjau-admin',
            'pinjaman.tinjau-bendahara',
            'pinjaman.approve-ketua',
            'limit.tinjau-bendahara',
            'limit.approve-ketua',
            'aktivasi.approve-ketua',
            'angsuran.konfirmasi',
            'kas.lihat',
            'kas.topup',
            'jurnal.lihat',
            'laporan.lihat',
            'shu.kelola',
            'pengaturan.kelola',
            'user.kelola',
            'portal.akses',
            'migrasi.kelola',
            'api.akses-pinjaman',
            'api.akses-simpanan',
            'api.akses-limit',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
    }
}
