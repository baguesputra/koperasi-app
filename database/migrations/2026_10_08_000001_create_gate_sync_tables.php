<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gate_sync_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('schedule_enabled')->default(true);
            $table->unsignedInteger('karyawan_interval_minutes')->default(15);
            $table->string('master_daily_at', 5)->default('02:00');
            $table->boolean('jit_enabled')->default(true);
            $table->unsignedTinyInteger('grace_miss_count')->default(2);
            $table->timestamps();
        });

        Schema::create('gate_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 20);
            $table->string('source', 10)->default('manual');
            $table->boolean('is_dry_run')->default(false);
            $table->unsignedInteger('count_baru')->default(0);
            $table->unsignedInteger('count_diperbarui')->default(0);
            $table->unsignedInteger('count_gagal')->default(0);
            $table->unsignedInteger('count_dilewati')->default(0);
            $table->unsignedInteger('count_nonaktif')->default(0);
            $table->unsignedInteger('count_perusahaan')->default(0);
            $table->unsignedInteger('count_departemen')->default(0);
            $table->unsignedInteger('count_divisi')->default(0);
            $table->unsignedInteger('count_jabatan')->default(0);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('anggota', function (Blueprint $table) {
            $table->timestamp('gate_synced_at')->nullable()->after('gate_id');
            $table->unsignedTinyInteger('gate_miss_count')->default(0)->after('gate_synced_at');
        });
    }

    public function down(): void
    {
        Schema::table('anggota', function (Blueprint $table) {
            $table->dropColumn(['gate_synced_at', 'gate_miss_count']);
        });

        Schema::dropIfExists('gate_sync_logs');
        Schema::dropIfExists('gate_sync_settings');
    }
};
