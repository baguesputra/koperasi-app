<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const IZIN_PENGAWAS = [
        'anggota.lihat',
        'simpanan.lihat',
        'tabungan.lihat',
        'pinjaman.lihat',
        'penghapusan-pinjaman.lihat',
        'penghapusan-pinjaman.approve-pengawas',
        'kas.lihat',
        'laporan.lihat',
        'portal.akses',
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $pengawas = Role::where('name', 'pengawas')->first();
        if ($pengawas) {
            foreach (self::IZIN_PENGAWAS as $perm) {
                Permission::firstOrCreate(['name' => $perm]);
            }
            $pengawas->syncPermissions(self::IZIN_PENGAWAS);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $pengawas = Role::where('name', 'pengawas')->first();
        if ($pengawas) {
            $izinLama = [
                ...self::IZIN_PENGAWAS,
                'jurnal.lihat',
                'shu.lihat',
                'pengaturan.kebijakan.lihat',
                'pengaturan.audit.lihat',
            ];
            foreach ($izinLama as $perm) {
                Permission::firstOrCreate(['name' => $perm]);
            }
            $pengawas->syncPermissions($izinLama);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
