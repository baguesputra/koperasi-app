<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengeluaran', function (Blueprint $table) {
            $table->string('tipe', 20)->default('lain_lain')->after('jenis');
        });

        // Backfill: yang keterangannya menyebut santunan dianggap tipe santunan.
        DB::table('pengeluaran')
            ->where('keterangan', 'like', '%santunan%')
            ->update(['tipe' => 'santunan']);
    }

    public function down(): void
    {
        Schema::table('pengeluaran', function (Blueprint $table) {
            $table->dropColumn('tipe');
        });
    }
};
