<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            Schema::table('pengajuan_limit', function (Blueprint $table) {
                $table->decimal('limit_disetujui_bendahara', 15, 2)->nullable()->after('limit_diminta');
                $table->decimal('limit_disetujui', 15, 2)->nullable()->after('limit_disetujui_bendahara');
                $table->text('catatan_bendahara')->nullable()->after('keterangan');
            });
            DB::statement("ALTER TABLE pengajuan_limit MODIFY COLUMN status ENUM('diajukan','approved_bendahara','disetujui','ditolak') NOT NULL DEFAULT 'diajukan'");

            return;
        }

        // SQLite (test): enum = CHECK constraint lama, rebuild penuh tanpa CHECK status
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('pengajuan_limit');
        Schema::create('pengajuan_limit', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anggota_id')->constrained('anggota')->cascadeOnDelete();
            $table->decimal('limit_saat_ini', 15, 2);
            $table->decimal('limit_diminta', 15, 2);
            $table->decimal('limit_disetujui_bendahara', 15, 2)->nullable();
            $table->decimal('limit_disetujui', 15, 2)->nullable();
            $table->text('keterangan');
            $table->text('catatan_bendahara')->nullable();
            $table->string('status')->default('diajukan');
            $table->text('catatan_ketua')->nullable();
            $table->date('tanggal_pengajuan');
            $table->timestamps();
        });
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            Schema::table('pengajuan_limit', function (Blueprint $table) {
                $table->dropColumn(['limit_disetujui_bendahara', 'limit_disetujui', 'catatan_bendahara']);
            });
            DB::statement("ALTER TABLE pengajuan_limit MODIFY COLUMN status ENUM('diajukan','disetujui','ditolak') NOT NULL DEFAULT 'diajukan'");
        }
    }
};
