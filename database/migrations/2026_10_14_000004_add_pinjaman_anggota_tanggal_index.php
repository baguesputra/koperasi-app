<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pinjaman', function (Blueprint $table) {
            $table->index(['anggota_id', 'tanggal_pengajuan'], 'idx_pinjaman_anggota_pengajuan');
        });
    }

    public function down(): void
    {
        Schema::table('pinjaman', function (Blueprint $table) {
            $table->dropIndex('idx_pinjaman_anggota_pengajuan');
        });
    }
};
