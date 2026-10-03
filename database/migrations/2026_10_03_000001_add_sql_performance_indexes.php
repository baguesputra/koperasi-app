<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jurnal_kas', function (Blueprint $table) {
            $table->index(['tanggal', 'id'], 'idx_jurnal_tanggal_id');
            $table->index(['kantong', 'kategori', 'tipe', 'tanggal'], 'idx_jurnal_kanal');
            $table->index(['tipe', 'tanggal'], 'idx_jurnal_tipe_tanggal');
        });

        Schema::table('angsuran', function (Blueprint $table) {
            $table->index(['status', 'tanggal_jatuh_tempo'], 'idx_angsuran_status_jt');
            $table->index(['status', 'tanggal_konfirmasi_bayar'], 'idx_angsuran_status_konf');
            $table->index(['pinjaman_id', 'status', 'tanggal_jatuh_tempo'], 'idx_angsuran_pin_status_jt');
        });

        Schema::table('angsuran_percepatan', function (Blueprint $table) {
            $table->index(['status', 'tanggal_jatuh_tempo'], 'idx_angperc_status_jt');
            $table->index(['status', 'tanggal_konfirmasi_bayar'], 'idx_angperc_status_konf');
        });

        Schema::table('simpanan', function (Blueprint $table) {
            $table->index(['tanggal_input'], 'idx_simpanan_tgl');
            $table->index(['jenis', 'tanggal_input'], 'idx_simpanan_jenis_tgl');
            $table->index(['bulan_periode', 'jenis'], 'idx_simpanan_periode_jenis');
            $table->index(['anggota_id', 'jenis', 'bulan_periode'], 'idx_simpanan_anggota_jenis_periode');
        });

        Schema::table('pinjaman', function (Blueprint $table) {
            $table->index(['status', 'tanggal_pengajuan'], 'idx_pinjaman_status_pengajuan');
            $table->index(['tanggal_pencairan'], 'idx_pinjaman_cair');
            $table->index(['status', 'tanggal_pencairan'], 'idx_pinjaman_status_cair');
        });

        Schema::table('pengeluaran', function (Blueprint $table) {
            $table->index(['jenis', 'tanggal'], 'idx_pengeluaran_jenis_tgl');
        });

        Schema::table('audit_log', function (Blueprint $table) {
            $table->index(['created_at'], 'idx_audit_created');
        });

        Schema::table('anggota', function (Blueprint $table) {
            $table->index(['nama'], 'idx_anggota_nama');
            $table->index(['no_karyawan'], 'idx_anggota_no_karyawan');
        });

        Schema::table('pengajuan_percepatan', function (Blueprint $table) {
            $table->index(['status', 'tanggal_pengajuan'], 'idx_pp_status_tgl');
        });

        Schema::table('klaim_dana_sosial', function (Blueprint $table) {
            $table->index(['status'], 'idx_klaim_status');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index(['no_karyawan'], 'idx_users_no_karyawan');
        });
    }

    public function down(): void
    {
        Schema::table('jurnal_kas', function (Blueprint $table) {
            $table->dropIndex('idx_jurnal_tanggal_id');
            $table->dropIndex('idx_jurnal_kanal');
            $table->dropIndex('idx_jurnal_tipe_tanggal');
        });

        Schema::table('angsuran', function (Blueprint $table) {
            $table->dropIndex('idx_angsuran_status_jt');
            $table->dropIndex('idx_angsuran_status_konf');
            $table->dropIndex('idx_angsuran_pin_status_jt');
        });

        Schema::table('angsuran_percepatan', function (Blueprint $table) {
            $table->dropIndex('idx_angperc_status_jt');
            $table->dropIndex('idx_angperc_status_konf');
        });

        Schema::table('simpanan', function (Blueprint $table) {
            $table->dropIndex('idx_simpanan_tgl');
            $table->dropIndex('idx_simpanan_jenis_tgl');
            $table->dropIndex('idx_simpanan_periode_jenis');
            $table->dropIndex('idx_simpanan_anggota_jenis_periode');
        });

        Schema::table('pinjaman', function (Blueprint $table) {
            $table->dropIndex('idx_pinjaman_status_pengajuan');
            $table->dropIndex('idx_pinjaman_cair');
            $table->dropIndex('idx_pinjaman_status_cair');
        });

        Schema::table('pengeluaran', function (Blueprint $table) {
            $table->dropIndex('idx_pengeluaran_jenis_tgl');
        });

        Schema::table('audit_log', function (Blueprint $table) {
            $table->dropIndex('idx_audit_created');
        });

        Schema::table('anggota', function (Blueprint $table) {
            $table->dropIndex('idx_anggota_nama');
            $table->dropIndex('idx_anggota_no_karyawan');
        });

        Schema::table('pengajuan_percepatan', function (Blueprint $table) {
            $table->dropIndex('idx_pp_status_tgl');
        });

        Schema::table('klaim_dana_sosial', function (Blueprint $table) {
            $table->dropIndex('idx_klaim_status');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_no_karyawan');
        });
    }
};
