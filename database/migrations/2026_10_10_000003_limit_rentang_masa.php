<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('setting_limit_pinjaman', function (Blueprint $table) {
            $table->unsignedInteger('min_bulan')->nullable()->after('label');
            $table->unsignedInteger('maks_bulan')->nullable()->after('min_bulan');
        });

        // Konversi 4 kategori bawaan ke rentang bulan (maks null = tanpa batas atas).
        $rentang = [
            'kurang_1_tahun' => [0, 11],
            'satu_sampai_3_tahun' => [12, 35],
            'tiga_sampai_5_tahun' => [36, 59],
            'lebih_5_tahun' => [60, null],
        ];

        foreach ($rentang as $kategori => [$min, $maks]) {
            DB::table('setting_limit_pinjaman')
                ->where('kategori', $kategori)
                ->whereNull('min_bulan')
                ->update(['min_bulan' => $min, 'maks_bulan' => $maks]);
        }
    }

    public function down(): void
    {
        Schema::table('setting_limit_pinjaman', function (Blueprint $table) {
            $table->dropColumn(['min_bulan', 'maks_bulan']);
        });
    }
};
