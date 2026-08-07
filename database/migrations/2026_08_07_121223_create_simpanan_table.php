<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::create('simpanan', function (Blueprint $table) {
        $table->id();
        $table->foreignId('anggota_id')->constrained('anggota')->cascadeOnDelete();
        $table->enum('jenis', ['pokok', 'wajib', 'dana_sosial']);
        $table->decimal('jumlah', 15, 2);
        $table->string('bulan_periode'); // format: '2026-08'
        $table->date('tanggal_input');
        $table->foreignId('input_by')->constrained('users'); // bendahara yang input
        $table->timestamps();
    });
}

    public function down(): void
    {
        Schema::dropIfExists('simpanan');
    }
};
