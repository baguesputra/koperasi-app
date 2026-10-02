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
            'pinjaman.tinjau-bendahara',
            'pinjaman.approve-ketua',
            'limit.tinjau-bendahara',
            'limit.approve-ketua',
            'klaim.tinjau-bendahara',
            'klaim.approve-ketua',
            'aktivasi.approve-ketua',
            'angsuran.konfirmasi',
            'kas.lihat',
            'kas.topup',
            'jurnal.lihat',
            'laporan.lihat',
            'pengaturan.kelola',
            'user.kelola',
            'portal.akses',
            'migrasi.kelola',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
    }
}
