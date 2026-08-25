<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wa_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anggota_id')->nullable()->constrained('anggota')->nullOnDelete();
            $table->string('penerima', 25);
            $table->string('event', 50);
            $table->text('pesan');
            $table->enum('status', ['terkirim', 'gagal', 'dilewati']);
            $table->string('error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_logs');
    }
};
