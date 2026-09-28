<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuan_aktivasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anggota_id')->constrained('anggota')->cascadeOnDelete();
            $table->string('status')->default('diajukan');
            $table->string('versi_syarat');
            $table->boolean('data_benar')->default(false);
            $table->boolean('setuju_syarat')->default(false);
            $table->text('catatan_ketua')->nullable();
            $table->date('tanggal_pengajuan');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_aktivasi');
    }
};
