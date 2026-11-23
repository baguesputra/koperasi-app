<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('setting_bunga_tabungan')->count() === 0) {
            DB::table('setting_bunga_tabungan')->insert([
                'persentase' => 2.00,
                'berlaku_dari_tanggal' => '2026-01-01',
                'keterangan' => 'Tarif awal bunga tabungan anggota 2% per tahun',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (DB::table('setting_tabungan')->count() === 0) {
            DB::table('setting_tabungan')->insert([
                'metode_bunga' => 'saldo_harian',
                'min_setor' => 50_000,
                'saldo_minimal' => 50_000,
                'min_tarik' => 50_000,
                'bank_tujuan_nama' => 'Bank Mandiri',
                'bank_tujuan_rekening' => '000-00-0000000-0',
                'bank_tujuan_atas_nama' => 'Koperasi Karyawan Karya Mandiri',
                'buka_rekening_aktif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permLihat = Permission::firstOrCreate(['name' => 'tabungan.lihat']);
        $permKelola = Permission::firstOrCreate(['name' => 'tabungan.kelola']);

        foreach (['super_admin', 'sekretaris', 'bendahara', 'ketua_koperasi', 'pengawas'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo($permLihat);
            }
        }

        foreach (['super_admin', 'bendahara'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo($permKelola);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::whereIn('name', ['tabungan.lihat', 'tabungan.kelola'])->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
