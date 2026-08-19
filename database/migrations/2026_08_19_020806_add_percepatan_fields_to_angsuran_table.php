<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('angsuran', function (Blueprint $table) {
            $table->foreignId('pengajuan_percepatan_id')->nullable()->after('pinjaman_id')->constrained('pengajuan_percepatan')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('angsuran', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pengajuan_percepatan_id');
        });
    }
};
