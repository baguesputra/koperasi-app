<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('anggota', function (Blueprint $table) {
            if (! Schema::hasColumn('anggota', 'status_pencairan_resign')) {
                $table->string('status_pencairan_resign', 24)->nullable()->index();
            }
            if (! Schema::hasColumn('anggota', 'resign_snapshot_bank')) {
                $table->string('resign_snapshot_bank', 60)->nullable();
            }
            if (! Schema::hasColumn('anggota', 'resign_snapshot_no_rekening')) {
                $table->string('resign_snapshot_no_rekening', 60)->nullable();
            }
            if (! Schema::hasColumn('anggota', 'resign_snapshot_atas_nama')) {
                $table->string('resign_snapshot_atas_nama', 120)->nullable();
            }
            if (! Schema::hasColumn('anggota', 'resign_akun_kas_pencairan')) {
                $table->string('resign_akun_kas_pencairan', 20)->nullable();
            }
            if (! Schema::hasColumn('anggota', 'tanggal_pencairan_resign')) {
                $table->date('tanggal_pencairan_resign')->nullable()->index();
            }
            if (! Schema::hasColumn('anggota', 'dicairkan_resign_oleh')) {
                $table->foreignId('dicairkan_resign_oleh')->nullable()->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('anggota', 'catatan_pencairan_resign')) {
                $table->text('catatan_pencairan_resign')->nullable();
            }
            if (! Schema::hasColumn('anggota', 'bukti_pencairan_resign_path')) {
                $table->string('bukti_pencairan_resign_path', 255)->nullable();
            }
            if (! Schema::hasColumn('anggota', 'bukti_pencairan_resign_nama_asli')) {
                $table->string('bukti_pencairan_resign_nama_asli', 255)->nullable();
            }
            if (! Schema::hasColumn('anggota', 'bukti_pencairan_resign_mime')) {
                $table->string('bukti_pencairan_resign_mime', 120)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('anggota', function (Blueprint $table) {
            if (Schema::hasColumn('anggota', 'dicairkan_resign_oleh')) {
                $table->dropConstrainedForeignId('dicairkan_resign_oleh');
            }

            $columns = [
                'status_pencairan_resign',
                'resign_snapshot_bank',
                'resign_snapshot_no_rekening',
                'resign_snapshot_atas_nama',
                'resign_akun_kas_pencairan',
                'tanggal_pencairan_resign',
                'catatan_pencairan_resign',
                'bukti_pencairan_resign_path',
                'bukti_pencairan_resign_nama_asli',
                'bukti_pencairan_resign_mime',
            ];
            $existing = array_filter($columns, fn (string $c) => Schema::hasColumn('anggota', $c));
            if ($existing !== []) {
                $table->dropColumn($existing);
            }
        });
    }
};
