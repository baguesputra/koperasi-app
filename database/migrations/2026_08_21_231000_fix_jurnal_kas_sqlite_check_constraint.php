<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Dahulu: rebuild tabel sqlite untuk memperbarui CHECK constraint enum kategori.
     *
     * Kini no-op: kolom kategori dibuat sebagai string biasa (lihat create_jurnal_kas_table),
     * validasi nilai kategori dilakukan di level aplikasi (JurnalKasService).
     * ENUM penuh tetap diterapkan untuk MySQL via migrasi ALTER terpisah.
     */
    public function up(): void {}

    public function down(): void {}
};
