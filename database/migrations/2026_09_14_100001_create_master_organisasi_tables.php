<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perusahaan', function (Blueprint $table) {
            $table->id();
            $table->string('gate_id', 50)->nullable()->unique();
            $table->string('kode', 50)->nullable()->unique();
            $table->string('nama');
            $table->string('direktur_gate_id', 50)->nullable();
            $table->string('gm_gate_id', 50)->nullable();
            $table->timestamps();
        });

        Schema::create('departemen', function (Blueprint $table) {
            $table->id();
            $table->string('gate_id', 50)->nullable()->unique();
            $table->foreignId('perusahaan_id')->nullable()->constrained('perusahaan')->nullOnDelete();
            $table->string('kode', 50)->nullable();
            $table->string('nama');
            $table->string('leadership_title')->nullable();
            $table->unsignedInteger('users_count')->nullable();
            $table->unsignedInteger('positions_count')->nullable();
            $table->timestamps();
        });

        Schema::create('divisi', function (Blueprint $table) {
            $table->id();
            $table->string('gate_id', 50)->nullable()->unique();
            $table->foreignId('perusahaan_id')->nullable()->constrained('perusahaan')->nullOnDelete();
            $table->string('kode', 50)->nullable();
            $table->string('nama');
            $table->string('leadership_title')->nullable();
            $table->unsignedInteger('users_count')->nullable();
            $table->unsignedInteger('positions_count')->nullable();
            $table->timestamps();
        });

        Schema::create('jabatan', function (Blueprint $table) {
            $table->id();
            $table->string('gate_id', 50)->nullable()->unique();
            $table->foreignId('perusahaan_id')->nullable()->constrained('perusahaan')->nullOnDelete();
            $table->foreignId('departemen_id')->nullable()->constrained('departemen')->nullOnDelete();
            $table->string('nama');
            $table->unsignedInteger('level')->nullable();
            $table->string('level_label')->nullable();
            $table->timestamps();
        });

        Schema::table('anggota', function (Blueprint $table) {
            $table->foreignId('perusahaan_id')->nullable()->constrained('perusahaan')->nullOnDelete();
            $table->foreignId('departemen_id')->nullable()->constrained('departemen')->nullOnDelete();
            $table->foreignId('divisi_id')->nullable()->constrained('divisi')->nullOnDelete();
            $table->foreignId('jabatan_id')->nullable()->constrained('jabatan')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('anggota', function (Blueprint $table) {
            $table->dropConstrainedForeignId('jabatan_id');
            $table->dropConstrainedForeignId('divisi_id');
            $table->dropConstrainedForeignId('departemen_id');
            $table->dropConstrainedForeignId('perusahaan_id');
        });
        Schema::dropIfExists('jabatan');
        Schema::dropIfExists('divisi');
        Schema::dropIfExists('departemen');
        Schema::dropIfExists('perusahaan');
    }
};
