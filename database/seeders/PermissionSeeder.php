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
            'simpanan.lihat',
            'simpanan.konfirmasi',
            'pinjaman.lihat',
            'pinjaman.tinjau-bendahara',
            'pinjaman.approve-ketua',
            'angsuran.konfirmasi',
            'kas.lihat',
            'kas.topup',
            'laporan.lihat',
            'pengaturan.kelola',
            'portal.akses',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
    }
}