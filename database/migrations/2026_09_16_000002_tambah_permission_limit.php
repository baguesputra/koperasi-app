<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const PERMISSIONS = ['limit.tinjau-bendahara', 'limit.approve-ketua'];

    public function up(): void
    {
        foreach (self::PERMISSIONS as $name) {
            Permission::firstOrCreate(['name' => $name]);
        }

        Role::firstOrCreate(['name' => 'admin'])->givePermissionTo(self::PERMISSIONS);
        Role::firstOrCreate(['name' => 'bendahara'])->givePermissionTo('limit.tinjau-bendahara');
        Role::firstOrCreate(['name' => 'ketua_koperasi'])->givePermissionTo('limit.approve-ketua');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        foreach (self::PERMISSIONS as $name) {
            $permission = Permission::where('name', $name)->first();
            if ($permission) {
                $permission->delete();
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
