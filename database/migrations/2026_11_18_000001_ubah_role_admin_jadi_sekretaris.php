<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $admin = DB::table('roles')
            ->where('name', 'admin')
            ->where('guard_name', 'web')
            ->first();

        $sekretaris = DB::table('roles')
            ->where('name', 'sekretaris')
            ->where('guard_name', 'web')
            ->first();

        if ($admin && ! $sekretaris) {
            DB::table('roles')->where('id', $admin->id)->update(['name' => 'sekretaris']);
        } elseif ($admin && $sekretaris) {
            $modelRoles = DB::table('model_has_roles')->where('role_id', $admin->id)->get();
            foreach ($modelRoles as $row) {
                DB::table('model_has_roles')->insertOrIgnore([
                    'role_id' => $sekretaris->id,
                    'model_type' => $row->model_type,
                    'model_id' => $row->model_id,
                ]);
            }

            $rolePermissions = DB::table('role_has_permissions')->where('role_id', $admin->id)->get();
            foreach ($rolePermissions as $row) {
                DB::table('role_has_permissions')->insertOrIgnore([
                    'permission_id' => $row->permission_id,
                    'role_id' => $sekretaris->id,
                ]);
            }

            DB::table('model_has_roles')->where('role_id', $admin->id)->delete();
            DB::table('role_has_permissions')->where('role_id', $admin->id)->delete();
            DB::table('roles')->where('id', $admin->id)->delete();
        }

        DB::table('users')
            ->where('no_karyawan', 'ADM-000001')
            ->where('name', 'Admin Koperasi')
            ->update(['name' => 'Sekretaris Koperasi']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $sekretaris = DB::table('roles')
            ->where('name', 'sekretaris')
            ->where('guard_name', 'web')
            ->first();

        $admin = DB::table('roles')
            ->where('name', 'admin')
            ->where('guard_name', 'web')
            ->first();

        if ($sekretaris && ! $admin) {
            DB::table('roles')->where('id', $sekretaris->id)->update(['name' => 'admin']);
        }

        DB::table('users')
            ->where('no_karyawan', 'ADM-000001')
            ->where('name', 'Sekretaris Koperasi')
            ->update(['name' => 'Admin Koperasi']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
