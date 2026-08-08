<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
            Schema::create('setting_limit_pinjaman', function (Blueprint $table) {
            $table->id();
            $table->string('kategori'); // 'anggota_baru', 'staff', 'hod', 'anggota_lama'
            $table->string('label'); // teks yang ditampilkan di UI, misal "Anggota < 1 Tahun"
            $table->decimal('limit_maksimal', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('setting_limit_pinjaman');
    }
};
