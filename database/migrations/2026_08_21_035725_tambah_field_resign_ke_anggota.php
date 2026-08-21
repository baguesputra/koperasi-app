<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ubah enum status untuk menambah nilai 'resign'.
        // Pakai raw SQL karena Doctrine/Schema tidak support modifikasi enum langsung di semua DB.
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE anggota MODIFY COLUMN status ENUM('aktif','nonaktif','resign') NOT NULL DEFAULT 'aktif'");
        } elseif ($driver === 'sqlite') {
            // SQLite: enum adalah CHECK constraint. Tidak bisa drop constraint tanpa recreate table.
            // Karena ini development/test yang pakai SQLite, kita skip strict enum check.
            // Frontend validasi di level aplikasi sudah cukup.
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE anggota DROP CONSTRAINT IF EXISTS anggota_status_check');
            DB::statement("ALTER TABLE anggota ADD CONSTRAINT anggota_status_check CHECK (status IN ('aktif','nonaktif','resign'))");
        }

        Schema::table('anggota', function (Blueprint $table) {
            $table->date('tanggal_resign')->nullable()->after('status');
            $table->text('alasan_resign')->nullable()->after('tanggal_resign');
            $table->foreignId('resigned_by')->nullable()->after('alasan_resign')->constrained('users')->nullOnDelete();
            $table->json('resigned_settlement_json')->nullable()->after('resigned_by');
            $table->json('reaktivasi_history_json')->nullable()->after('resigned_settlement_json');
        });
    }

    public function down(): void
    {
        Schema::table('anggota', function (Blueprint $table) {
            $table->dropForeign(['resigned_by']);
            $table->dropColumn([
                'tanggal_resign',
                'alasan_resign',
                'resigned_by',
                'resigned_settlement_json',
                'reaktivasi_history_json',
            ]);
        });

        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE anggota MODIFY COLUMN status ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif'");
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE anggota DROP CONSTRAINT IF EXISTS anggota_status_check');
            DB::statement("ALTER TABLE anggota ADD CONSTRAINT anggota_status_check CHECK (status IN ('aktif','nonaktif'))");
        }
    }
};
