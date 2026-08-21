<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pinjaman', function (Blueprint $table) {
            $table->text('keperluan')->nullable()->after('tenor_bulan');
            $table->string('snapshot_bank')->nullable()->after('keperluan');
            $table->string('snapshot_no_rekening')->nullable()->after('snapshot_bank');
            $table->string('snapshot_atas_nama')->nullable()->after('snapshot_no_rekening');
        });
    }

    public function down(): void
    {
        Schema::table('pinjaman', function (Blueprint $table) {
            $table->dropColumn(['keperluan', 'snapshot_bank', 'snapshot_no_rekening', 'snapshot_atas_nama']);
        });
    }
};
