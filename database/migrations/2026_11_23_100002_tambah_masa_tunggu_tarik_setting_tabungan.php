<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('setting_tabungan', 'masa_tunggu_tarik_bulan')) {
            Schema::table('setting_tabungan', function (Blueprint $table) {
                $table->unsignedSmallInteger('masa_tunggu_tarik_bulan')->default(12)->after('min_tarik');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('setting_tabungan', 'masa_tunggu_tarik_bulan')) {
            Schema::table('setting_tabungan', function (Blueprint $table) {
                $table->dropColumn('masa_tunggu_tarik_bulan');
            });
        }
    }
};
