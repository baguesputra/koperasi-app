<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shu_komponen_pembayaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shu_periode_id')->constrained('shu_periode')->cascadeOnDelete();
            $table->string('kode_komponen', 50);
            $table->string('nama_komponen', 100);
            $table->decimal('nominal', 15, 2)->default(0);
            $table->string('status_bayar', 20)->default('belum');
            $table->date('tanggal_bayar')->nullable();
            $table->timestamp('dibayar_pada')->nullable();
            $table->foreignId('dibayar_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('jurnal_id')->nullable()->constrained('jurnal_kas')->nullOnDelete();
            $table->string('akun', 20)->nullable();
            $table->json('rincian')->nullable();
            $table->timestamps();

            $table->unique(['shu_periode_id', 'kode_komponen'], 'uniq_shu_komponen_bayar_periode_kode');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shu_komponen_pembayaran');
    }
};

