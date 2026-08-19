<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('angsuran_percepatan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengajuan_percepatan_id')->constrained('pengajuan_percepatan')->cascadeOnDelete();
            $table->unsignedInteger('cicilan_ke');
            $table->decimal('nominal_pokok', 15, 2);
            $table->decimal('nominal_bunga', 15, 2);
            $table->decimal('total_bayar', 15, 2);
            $table->enum('status', ['belum_bayar', 'lunas'])->default('belum_bayar');
            $table->date('tanggal_jatuh_tempo');
            $table->date('tanggal_konfirmasi_bayar')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->unique(['pengajuan_percepatan_id', 'cicilan_ke']);
            $table->index(['status', 'tanggal_jatuh_tempo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('angsuran_percepatan');
    }
};
