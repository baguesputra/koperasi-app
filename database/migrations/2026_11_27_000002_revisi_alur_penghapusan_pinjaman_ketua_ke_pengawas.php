<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $ajukan = Permission::firstOrCreate(['name' => 'penghapusan-pinjaman.ajukan']);

        foreach (['sekretaris', 'bendahara'] as $roleName) {
            if ($role = Role::where('name', $roleName)->first()) {
                if ($role->hasPermissionTo($ajukan)) {
                    $role->revokePermissionTo($ajukan);
                }
            }
        }

        foreach (['ketua_koperasi', 'super_admin'] as $roleName) {
            if ($role = Role::where('name', $roleName)->first()) {
                $role->givePermissionTo($ajukan);
            }
        }

        foreach (['penghapusan-pinjaman.approve-bendahara', 'penghapusan-pinjaman.approve-ketua'] as $permName) {
            if ($perm = Permission::where('name', $permName)->first()) {
                $perm->delete();
            }
        }

        if (Schema::hasTable('pengajuan_penghapusan_pinjaman')) {
            DB::table('pengajuan_penghapusan_pinjaman')
                ->whereIn('status', ['approved_bendahara', 'approved_ketua'])
                ->update(['status' => 'diajukan']);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $approveBendahara = Permission::firstOrCreate(['name' => 'penghapusan-pinjaman.approve-bendahara']);
        $approveKetua = Permission::firstOrCreate(['name' => 'penghapusan-pinjaman.approve-ketua']);
        $ajukan = Permission::firstOrCreate(['name' => 'penghapusan-pinjaman.ajukan']);

        if ($role = Role::where('name', 'sekretaris')->first()) {
            $role->givePermissionTo($ajukan);
        }
        if ($role = Role::where('name', 'bendahara')->first()) {
            $role->givePermissionTo([$ajukan, $approveBendahara]);
        }
        if ($role = Role::where('name', 'ketua_koperasi')->first()) {
            if ($role->hasPermissionTo($ajukan)) {
                $role->revokePermissionTo($ajukan);
            }
            $role->givePermissionTo($approveKetua);
        }
        if ($role = Role::where('name', 'super_admin')->first()) {
            $role->givePermissionTo([$approveBendahara, $approveKetua]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
