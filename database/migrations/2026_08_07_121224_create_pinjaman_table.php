<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pinjaman', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anggota_id')->constrained('anggota')->cascadeOnDelete();
            $table->decimal('nominal', 15, 2);
            $table->unsignedInteger('tenor_bulan');
            $table->decimal('persentase_bunga', 5, 2); // snapshot, tidak berubah meski setting global berubah
            $table->enum('status', [
                'diajukan',
                'ditinjau_bendahara',
                'approved_bendahara',
                'approved_ketua',
                'aktif',
                'lunas',
                'ditolak',
            ])->default('diajukan');
            $table->boolean('sudah_pakai_privilege_reloan')->default(false);
            $table->date('tanggal_pengajuan');
            $table->date('tanggal_pencairan')->nullable();
            $table->text('catatan_bendahara')->nullable();
            $table->text('catatan_ketua')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pinjaman');
    }
};
