<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Anggota: status + cabang filter (Dashboard, Anggota, Simpanan controllers)
        Schema::table('anggota', function (Blueprint $table) {
            $table->index(['status', 'cabang'], 'idx_anggota_status_cabang');
            $table->index(['status'], 'idx_anggota_status');
        });

        // Simpanan: anggota + jenis (Dashboard, Portal, Simpanan controllers)
        // Simpanan: jenis + tanggal_input (Dashboard grafik 6 bulan)
        Schema::table('simpanan', function (Blueprint $table) {
            $table->index(['anggota_id', 'jenis'], 'idx_simpanan_anggota_jenis');
            $table->index(['jenis', 'tanggal_input'], 'idx_simpanan_jenis_tanggal');
        });

        // Pinjaman: anggota + status (Portal, Pinjaman controllers)
        Schema::table('pinjaman', function (Blueprint $table) {
            $table->index(['anggota_id', 'status'], 'idx_pinjaman_anggota_status');
        });

        // Angsuran: pinjaman + status (Pinjaman, Portal controllers)
        Schema::table('angsuran', function (Blueprint $table) {
            $table->index(['pinjaman_id', 'status'], 'idx_angsuran_pinjaman_status');
        });

        // JurnalKas: kategori + tipe + tanggal (Dashboard, KasKoperasi controllers)
        // JurnalKas: referensi_id (PinjamanController pelunasan_resign batch query)
        Schema::table('jurnal_kas', function (Blueprint $table) {
            $table->index(['kategori', 'tipe', 'tanggal'], 'idx_jurnal_kas_kat_tipe_tgl');
            $table->index(['referensi_id'], 'idx_jurnal_kas_referensi_id');
        });

        // PengajuanPercepatan: pinjaman + status (Portal, Ketua controllers)
        Schema::table('pengajuan_percepatan', function (Blueprint $table) {
            $table->index(['pinjaman_id', 'status'], 'idx_pengajuan_percepatan_pinjaman_status');
        });
    }

    public function down(): void
    {
        Schema::table('anggota', function (Blueprint $table) {
            $table->dropIndex('idx_anggota_status_cabang');
            $table->dropIndex('idx_anggota_status');
        });

        Schema::table('simpanan', function (Blueprint $table) {
            $table->dropIndex('idx_simpanan_anggota_jenis');
            $table->dropIndex('idx_simpanan_jenis_tanggal');
        });

        Schema::table('pinjaman', function (Blueprint $table) {
            $table->dropIndex('idx_pinjaman_anggota_status');
        });

        Schema::table('angsuran', function (Blueprint $table) {
            $table->dropIndex('idx_angsuran_pinjaman_status');
        });

        Schema::table('jurnal_kas', function (Blueprint $table) {
            $table->dropIndex('idx_jurnal_kas_kat_tipe_tgl');
            $table->dropIndex('idx_jurnal_kas_referensi_id');
        });

        Schema::table('pengajuan_percepatan', function (Blueprint $table) {
            $table->dropIndex('idx_pengajuan_percepatan_pinjaman_status');
        });
    }
};