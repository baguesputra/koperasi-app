<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('klaim_dana_sosial', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anggota_id')->constrained('anggota')->cascadeOnDelete();
            $table->string('jenis');
            $table->string('sub_tipe')->nullable();
            $table->string('hubungan')->nullable();
            $table->date('tanggal_kejadian');
            $table->integer('lama_hari')->nullable();
            $table->text('keterangan');
            $table->string('foto_path', 500);
            $table->decimal('nominal_bendahara', 15, 2)->nullable();
            $table->decimal('nominal_final', 15, 2)->nullable();
            $table->string('status')->default('diajukan');
            $table->text('catatan_bendahara')->nullable();
            $table->text('catatan_ketua')->nullable();
            $table->foreignId('pengeluaran_id')->nullable()->constrained('pengeluaran')->nullOnDelete();
            $table->date('tanggal_pengajuan');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('klaim_dana_sosial');
    }
};
