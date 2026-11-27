<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $portalAkses = Permission::firstOrCreate([
            'name' => 'portal.akses',
            'guard_name' => 'web',
        ]);

        foreach (['sekretaris', 'bendahara', 'ketua_koperasi', 'pengawas'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            if ($role && ! $role->hasPermissionTo($portalAkses)) {
                $role->givePermissionTo($portalAkses);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Tidak mencabut portal.akses pada rollback demi keamanan akses portal berjalan.
    }
};
