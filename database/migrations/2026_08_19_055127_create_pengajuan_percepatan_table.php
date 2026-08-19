<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuan_percepatan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pinjaman_id')->constrained('pinjaman')->cascadeOnDelete();
            $table->enum('tipe', ['percepat', 'perpanjang', 'lunas_total']);
            $table->unsignedInteger('tenor_lama');
            $table->unsignedInteger('tenor_baru')->nullable();
            $table->decimal('sisa_pokok_saat_approval', 15, 2)->nullable();
            $table->decimal('nominal_final', 15, 2)->nullable();
            $table->enum('bulan_berlaku', ['bulan_ini', 'bulan_depan'])->nullable();
            $table->text('keterangan');
            $table->enum('status', ['diajukan', 'approved_bendahara', 'aktif', 'ditolak'])->default('diajukan');
            $table->text('catatan_bendahara')->nullable();
            $table->text('catatan_ketua')->nullable();
            $table->date('tanggal_pengajuan');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_percepatan');
    }
};
