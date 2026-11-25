<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rekening_tabungan', function (Blueprint $table) {
            $table->index('status', 'idx_rekening_tabungan_status');
        });

        Schema::table('pengajuan_percepatan', function (Blueprint $table) {
            $table->index('status', 'idx_pengajuan_percepatan_status');
        });

        Schema::table('pinjaman', function (Blueprint $table) {
            $table->index(['status', 'cair_oleh_bendahara'], 'idx_pinjaman_status_cair_bendahara');
        });

        Schema::table('tabungan_bunga_periode', function (Blueprint $table) {
            $table->index('periode', 'idx_tbp_periode');
        });
    }

    public function down(): void
    {
        Schema::table('rekening_tabungan', function (Blueprint $table) {
            $table->dropIndex('idx_rekening_tabungan_status');
        });

        Schema::table('pengajuan_percepatan', function (Blueprint $table) {
            $table->dropIndex('idx_pengajuan_percepatan_status');
        });

        Schema::table('pinjaman', function (Blueprint $table) {
            $table->dropIndex('idx_pinjaman_status_cair_bendahara');
        });

        Schema::table('tabungan_bunga_periode', function (Blueprint $table) {
            $table->dropIndex('idx_tbp_periode');
        });
    }
};
