<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('setting_chip_nominal')->where('grup', 'santunan')->delete();

        foreach (['klaim.tinjau-bendahara', 'klaim.approve-ketua'] as $nama) {
            Permission::where('name', $nama)->delete();
        }

        Schema::dropIfExists('klaim_dana_sosial');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Modul klaim dihapus permanen (data tidak diarsip atas permintaan).
        // Down hanya mengembalikan permission agar seeder lama tetap valid.
        foreach (['klaim.tinjau-bendahara', 'klaim.approve-ketua'] as $nama) {
            Permission::firstOrCreate(['name' => $nama]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
