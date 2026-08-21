<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE angsuran MODIFY COLUMN status ENUM('belum_bayar', 'lunas', 'digantikan') DEFAULT 'belum_bayar'");
        }

        Schema::table('angsuran', function (Blueprint $table) {
            $table->foreignId('pengajuan_percepatan_id')->nullable()->after('status')->constrained('pengajuan_percepatan')->nullOnDelete();
        });

        Schema::table('pinjaman', function (Blueprint $table) {
            $table->boolean('sudah_pakai_percepatan')->default(false)->after('sudah_pakai_privilege_reloan');
        });
    }

    public function down(): void
    {
        Schema::table('pinjaman', function (Blueprint $table) {
            $table->dropColumn('sudah_pakai_percepatan');
        });

        Schema::table('angsuran', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pengajuan_percepatan_id');
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE angsuran MODIFY COLUMN status ENUM('belum_bayar', 'lunas') DEFAULT 'belum_bayar'");
        }
    }
};
