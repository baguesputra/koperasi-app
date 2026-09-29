<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $bendahara = Permission::firstOrCreate(['name' => 'klaim.tinjau-bendahara']);
        $ketua = Permission::firstOrCreate(['name' => 'klaim.approve-ketua']);

        Role::firstOrCreate(['name' => 'admin'])->givePermissionTo([$bendahara, $ketua]);
        Role::firstOrCreate(['name' => 'bendahara'])->givePermissionTo($bendahara);
        Role::firstOrCreate(['name' => 'ketua_koperasi'])->givePermissionTo($ketua);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        foreach (['klaim.tinjau-bendahara', 'klaim.approve-ketua'] as $nama) {
            $permission = Permission::where('name', $nama)->first();

            if ($permission) {
                $permission->delete();
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
