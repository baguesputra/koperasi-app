<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::table('anggota', function (Blueprint $table) {
        $table->string('no_karyawan')->nullable()->unique()->after('no_anggota');
        $table->string('no_ktp', 20)->nullable()->after('no_karyawan');
        $table->string('department')->nullable()->after('unit_bisnis');
        $table->string('divisi')->nullable()->after('department');
    });
    }

    public function down(): void
    {
        Schema::table('anggota', function (Blueprint $table) {
            $table->dropColumn(['no_karyawan', 'no_ktp', 'department', 'divisi']);
        });
    }
};
