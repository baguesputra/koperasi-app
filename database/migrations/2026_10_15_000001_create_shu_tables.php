<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shu_periode', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('tahun_buku')->unique();
            $table->decimal('total_shu', 15, 2)->default(0);
            $table->decimal('pct_cadangan', 5, 4)->default(0.10);
            $table->decimal('pct_jasa_simpanan', 5, 4)->default(0.20);
            $table->decimal('pct_jasa_transaksi', 5, 4)->default(0.40);
            $table->decimal('pct_pengurus', 5, 4)->default(0.15);
            $table->decimal('pct_pengawas', 5, 4)->default(0.05);
            $table->decimal('pct_pendidikan', 5, 4)->default(0.05);
            $table->decimal('pct_sosial', 5, 4)->default(0);
            $table->decimal('pct_wilker', 5, 4)->default(0.05);
            // Rincian manual per orang hasil RAT: [{nama, nominal}].
            $table->json('pengurus_rincian')->nullable();
            $table->json('pengawas_rincian')->nullable();
            $table->string('status', 20)->default('draft'); // draft | final
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();
        });

        Schema::create('shu_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shu_periode_id')->constrained('shu_periode')->cascadeOnDelete();
            $table->foreignId('anggota_id')->constrained('anggota')->cascadeOnDelete();
            $table->decimal('simp_pokok', 15, 2)->default(0);
            $table->decimal('simp_wajib', 15, 2)->default(0);
            $table->decimal('modal', 15, 2)->default(0);
            $table->decimal('transaksi', 15, 2)->default(0);
            $table->decimal('shu_modal', 15, 2)->default(0);
            $table->decimal('shu_transaksi', 15, 2)->default(0);
            $table->decimal('shu_bruto', 15, 2)->default(0);
            $table->decimal('shu_bersih', 15, 2)->default(0);
            $table->string('status_bayar', 20)->default('belum'); // belum | sudah
            $table->timestamp('dibayar_pada')->nullable();
            $table->foreignId('dibayar_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('jurnal_id')->nullable()->constrained('jurnal_kas')->nullOnDelete();
            $table->timestamps();

            $table->unique(['shu_periode_id', 'anggota_id'], 'uniq_shu_detail_periode_anggota');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shu_detail');
        Schema::dropIfExists('shu_periode');
    }
};
