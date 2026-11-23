<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('setting_bunga_tabungan', function (Blueprint $table) {
            $table->id();
            $table->decimal('persentase', 5, 2);
            $table->date('berlaku_dari_tanggal');
            $table->string('keterangan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('berlaku_dari_tanggal', 'idx_sbt_berlaku_dari');
        });

        Schema::create('setting_tabungan', function (Blueprint $table) {
            $table->id();
            $table->string('metode_bunga', 30)->default('saldo_harian');
            $table->decimal('min_setor', 15, 2)->default(50000);
            $table->decimal('saldo_minimal', 15, 2)->default(50000);
            $table->decimal('min_tarik', 15, 2)->default(50000);
            $table->string('bank_tujuan_nama', 100)->nullable();
            $table->string('bank_tujuan_rekening', 100)->nullable();
            $table->string('bank_tujuan_atas_nama', 150)->nullable();
            $table->boolean('buka_rekening_aktif')->default(true);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('rekening_tabungan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anggota_id')->constrained('anggota')->cascadeOnDelete();
            $table->string('no_rekening', 40)->nullable()->unique();
            $table->string('status', 20)->default('diajukan');
            $table->decimal('saldo', 15, 2)->default(0);
            $table->decimal('suku_bunga_saat_buka', 5, 2)->default(2.00);
            $table->string('versi_syarat', 50)->nullable();
            $table->boolean('setuju_syarat')->default(false);
            $table->date('tanggal_pengajuan');
            $table->date('tanggal_buka')->nullable();
            $table->date('tanggal_tutup')->nullable();
            $table->foreignId('diproses_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->text('catatan_pengurus')->nullable();
            $table->timestamps();

            $table->index(['anggota_id', 'status'], 'idx_rekening_tabungan_anggota_status');
        });

        Schema::create('pengajuan_transaksi_tabungan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rekening_tabungan_id')->constrained('rekening_tabungan')->cascadeOnDelete();
            $table->foreignId('anggota_id')->constrained('anggota')->cascadeOnDelete();
            $table->string('jenis', 20);
            $table->string('kanal', 20)->default('transfer');
            $table->decimal('nominal', 15, 2);
            $table->date('tanggal_transaksi');
            $table->string('bukti_path')->nullable();
            $table->string('bukti_nama_asli')->nullable();
            $table->string('bukti_mime', 100)->nullable();
            $table->json('rekening_tujuan_snapshot')->nullable();
            $table->text('catatan_anggota')->nullable();
            $table->string('status', 20)->default('menunggu');
            $table->foreignId('diproses_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diproses_pada')->nullable();
            $table->text('catatan_pengurus')->nullable();
            $table->string('bukti_pencairan_path')->nullable();
            $table->string('bukti_pencairan_nama_asli')->nullable();
            $table->string('bukti_pencairan_mime', 100)->nullable();
            $table->unsignedBigInteger('transaksi_id')->nullable();
            $table->timestamps();

            $table->index(['status', 'jenis'], 'idx_ptt_status_jenis');
            $table->index(['rekening_tabungan_id', 'status'], 'idx_ptt_rekening_status');
        });

        Schema::create('tabungan_transaksi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rekening_tabungan_id')->constrained('rekening_tabungan')->cascadeOnDelete();
            $table->foreignId('anggota_id')->constrained('anggota')->cascadeOnDelete();
            $table->string('no_bukti', 50)->unique();
            $table->string('jenis', 20);
            $table->string('kanal', 20);
            $table->decimal('nominal', 15, 2);
            $table->decimal('saldo_sebelum', 15, 2);
            $table->decimal('saldo_sesudah', 15, 2);
            $table->decimal('suku_bunga_snapshot', 5, 2)->nullable();
            $table->date('tanggal');
            $table->string('keterangan');
            $table->string('bukti_path')->nullable();
            $table->string('bukti_nama_asli')->nullable();
            $table->string('bukti_mime', 100)->nullable();
            $table->foreignId('jurnal_kas_id')->nullable()->constrained('jurnal_kas')->nullOnDelete();
            $table->unsignedBigInteger('pengajuan_id')->nullable();
            $table->unsignedBigInteger('koreksi_dari_id')->nullable();
            $table->timestamp('dibatalkan_pada')->nullable();
            $table->foreignId('dibatalkan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->string('alasan_batal')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['rekening_tabungan_id', 'tanggal'], 'idx_tt_rekening_tanggal');
            $table->index(['jenis', 'tanggal'], 'idx_tt_jenis_tanggal');
            $table->index(['anggota_id', 'tanggal'], 'idx_tt_anggota_tanggal');
        });

        Schema::create('tabungan_bunga_periode', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rekening_tabungan_id')->constrained('rekening_tabungan')->cascadeOnDelete();
            $table->char('periode', 7);
            $table->string('metode_hitung_snapshot', 30);
            $table->decimal('suku_bunga_snapshot', 5, 2);
            $table->json('rincian_tarif_json')->nullable();
            $table->decimal('saldo_dasar', 15, 2);
            $table->unsignedSmallInteger('jumlah_hari')->default(0);
            $table->decimal('nominal_bunga', 15, 2);
            $table->decimal('pajak_bunga', 15, 2)->default(0);
            $table->decimal('bunga_bersih', 15, 2);
            $table->foreignId('transaksi_id')->nullable()->constrained('tabungan_transaksi')->nullOnDelete();
            $table->string('catatan')->nullable();
            $table->foreignId('diposting_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diposting_pada');
            $table->timestamps();

            $table->unique(['rekening_tabungan_id', 'periode'], 'uniq_tbp_rekening_periode');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tabungan_bunga_periode');
        Schema::dropIfExists('tabungan_transaksi');
        Schema::dropIfExists('pengajuan_transaksi_tabungan');
        Schema::dropIfExists('rekening_tabungan');
        Schema::dropIfExists('setting_tabungan');
        Schema::dropIfExists('setting_bunga_tabungan');
    }
};
