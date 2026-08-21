<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anggota', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('no_anggota')->unique();
            $table->string('nama');
            $table->string('cabang'); // Banjarmasin, Samarinda, Palangka - langsung string, bukan FK
            $table->string('unit_bisnis'); // sesuai form fisik koperasi
            $table->enum('jabatan', ['staff', 'hod']);
            $table->date('tanggal_mulai_kerja');
            $table->date('tanggal_jadi_anggota');
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anggota');
    }
};
