<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::firstOrCreate(['name' => 'migrasi.kelola']);

        Role::firstOrCreate(['name' => 'admin'])->givePermissionTo($permission);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $permission = Permission::where('name', 'migrasi.kelola')->first();

        if ($permission) {
            Role::where('name', 'admin')->first()?->revokePermissionTo($permission);
            $permission->delete();
        }
    }
};
