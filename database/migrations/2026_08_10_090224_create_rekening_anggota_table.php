<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::create('rekening_anggota', function (Blueprint $table) {
        $table->id();
        $table->foreignId('anggota_id')->constrained('anggota')->cascadeOnDelete();
        $table->string('nama_bank');
        $table->string('no_rekening');
        $table->string('atas_nama');
        $table->boolean('is_default')->default(false);
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('rekening_anggota');
}
};
