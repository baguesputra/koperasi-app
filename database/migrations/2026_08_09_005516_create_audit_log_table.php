<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->string('aksi'); // contoh: "update_persentase_bunga"
            $table->string('keterangan'); // teks manusiawi, misal "Bunga diubah dari 1% ke 1.5%"
            $table->json('data_lama')->nullable();
            $table->json('data_baru')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_log');
    }
};
