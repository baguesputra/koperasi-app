<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::create('jurnal_kas', function (Blueprint $table) {
        $table->id();
        $table->enum('tipe', ['masuk', 'keluar']);
        $table->enum('kategori', ['topup_bulanan', 'pencairan_pinjaman', 'pembayaran_angsuran']);
        $table->decimal('jumlah', 15, 2);
        $table->text('keterangan')->nullable();
        $table->unsignedBigInteger('referensi_id')->nullable(); // merujuk ke pinjaman_id atau angsuran_id, tanpa FK constraint (polymorphic sederhana)
        $table->date('tanggal');
        $table->foreignId('created_by')->constrained('users');
        $table->timestamps();
    });
}

    public function down(): void
    {
        Schema::dropIfExists('jurnal_kas');
    }
};
