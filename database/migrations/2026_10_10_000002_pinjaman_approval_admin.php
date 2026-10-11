<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pinjaman', function (Blueprint $table) {
            $table->text('catatan_admin')->nullable()->after('catatan_bendahara');
        });

        $permission = Permission::firstOrCreate(['name' => 'pinjaman.tinjau-admin']);
        Role::firstOrCreate(['name' => 'admin'])->givePermissionTo($permission);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::table('pinjaman', function (Blueprint $table) {
            $table->dropColumn('catatan_admin');
        });

        Permission::where('name', 'pinjaman.tinjau-admin')->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
