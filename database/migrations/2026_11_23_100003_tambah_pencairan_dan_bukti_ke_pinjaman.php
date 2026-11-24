<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pinjaman', function (Blueprint $table) {
            if (! Schema::hasColumn('pinjaman', 'disetujui_ketua_pada')) {
                $table->timestamp('disetujui_ketua_pada')->nullable()->after('disetujui_pada');
            }
            if (! Schema::hasColumn('pinjaman', 'dicairkan_oleh')) {
                $table->foreignId('dicairkan_oleh')->nullable()->after('disetujui_ketua_pada')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('pinjaman', 'catatan_pencairan')) {
                $table->text('catatan_pencairan')->nullable()->after('catatan_ketua');
            }
            if (! Schema::hasColumn('pinjaman', 'bukti_pencairan_path')) {
                $table->string('bukti_pencairan_path')->nullable()->after('catatan_pencairan');
            }
            if (! Schema::hasColumn('pinjaman', 'bukti_pencairan_nama_asli')) {
                $table->string('bukti_pencairan_nama_asli')->nullable()->after('bukti_pencairan_path');
            }
            if (! Schema::hasColumn('pinjaman', 'bukti_pencairan_mime')) {
                $table->string('bukti_pencairan_mime', 100)->nullable()->after('bukti_pencairan_nama_asli');
            }
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE pinjaman MODIFY COLUMN status ENUM('diajukan','ditinjau_bendahara','approved_admin','approved_bendahara','approved_ketua','aktif','lunas','ditolak','dihapusbukukan') NOT NULL DEFAULT 'diajukan'");
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permCairkan = Permission::firstOrCreate(['name' => 'pinjaman.cairkan']);
        foreach (['super_admin', 'bendahara'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo($permCairkan);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::where('name', 'pinjaman.cairkan')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Schema::table('pinjaman', function (Blueprint $table) {
            if (Schema::hasColumn('pinjaman', 'dicairkan_oleh')) {
                $table->dropConstrainedForeignId('dicairkan_oleh');
            }
            foreach ([
                'disetujui_ketua_pada',
                'catatan_pencairan',
                'bukti_pencairan_path',
                'bukti_pencairan_nama_asli',
                'bukti_pencairan_mime',
            ] as $kolom) {
                if (Schema::hasColumn('pinjaman', $kolom)) {
                    $table->dropColumn($kolom);
                }
            }
        });
    }
};
