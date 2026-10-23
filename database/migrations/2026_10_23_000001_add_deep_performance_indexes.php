<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Audit Log: Filter tab audit, aktor, dan histori aksi kritis
        if (Schema::hasTable('audit_log')) {
            Schema::table('audit_log', function (Blueprint $table) {
                if (! Schema::hasIndex('audit_log', 'idx_audit_user_created')) {
                    $table->index(['user_id', 'created_at'], 'idx_audit_user_created');
                }
                if (! Schema::hasIndex('audit_log', 'idx_audit_aksi_created')) {
                    $table->index(['aksi', 'created_at'], 'idx_audit_aksi_created');
                }
            });
        }

        // 2. Pengajuan Limit: Notifikasi middleware, dashboard, & pipeline 3 jenjang
        if (Schema::hasTable('pengajuan_limit')) {
            Schema::table('pengajuan_limit', function (Blueprint $table) {
                if (! Schema::hasIndex('pengajuan_limit', 'idx_limit_status_tgl')) {
                    $table->index(['status', 'tanggal_pengajuan'], 'idx_limit_status_tgl');
                }
                if (! Schema::hasIndex('pengajuan_limit', 'idx_limit_anggota_status')) {
                    $table->index(['anggota_id', 'status'], 'idx_limit_anggota_status');
                }
            });
        }

        // 3. Pengajuan Aktivasi: Notifikasi middleware & dashboard
        if (Schema::hasTable('pengajuan_aktivasi')) {
            Schema::table('pengajuan_aktivasi', function (Blueprint $table) {
                if (! Schema::hasIndex('pengajuan_aktivasi', 'idx_aktivasi_status_tgl')) {
                    $table->index(['status', 'tanggal_pengajuan'], 'idx_aktivasi_status_tgl');
                }
                if (! Schema::hasIndex('pengajuan_aktivasi', 'idx_aktivasi_anggota_status')) {
                    $table->index(['anggota_id', 'status'], 'idx_aktivasi_anggota_status');
                }
            });
        }

        // 4. SHU Detail: Filter status bayar di tab distribusi per periode
        if (Schema::hasTable('shu_detail')) {
            Schema::table('shu_detail', function (Blueprint $table) {
                if (! Schema::hasIndex('shu_detail', 'idx_shu_detail_periode_bayar')) {
                    $table->index(['shu_periode_id', 'status_bayar'], 'idx_shu_detail_periode_bayar');
                }
            });
        }

        // 5. Pengeluaran: Filter tipe pengeluaran & tanggal
        if (Schema::hasTable('pengeluaran')) {
            Schema::table('pengeluaran', function (Blueprint $table) {
                if (! Schema::hasIndex('pengeluaran', 'idx_pengeluaran_tipe_tanggal')) {
                    $table->index(['tipe', 'tanggal'], 'idx_pengeluaran_tipe_tanggal');
                }
            });
        }

        // 6. Users: Filter status aktif/nonaktif di tab pengaturan pengguna
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (! Schema::hasIndex('users', 'idx_users_status')) {
                    $table->index(['status'], 'idx_users_status');
                }
            });
        }

        // 7. WA Logs: Monitoring dan retry berdasarkan status & waktu
        if (Schema::hasTable('wa_logs')) {
            Schema::table('wa_logs', function (Blueprint $table) {
                if (! Schema::hasIndex('wa_logs', 'idx_wa_logs_status_created')) {
                    $table->index(['status', 'created_at'], 'idx_wa_logs_status_created');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('audit_log')) {
            Schema::table('audit_log', function (Blueprint $table) {
                if (Schema::hasIndex('audit_log', 'idx_audit_user_created')) {
                    $table->dropIndex('idx_audit_user_created');
                }
                if (Schema::hasIndex('audit_log', 'idx_audit_aksi_created')) {
                    $table->dropIndex('idx_audit_aksi_created');
                }
            });
        }

        if (Schema::hasTable('pengajuan_limit')) {
            Schema::table('pengajuan_limit', function (Blueprint $table) {
                if (Schema::hasIndex('pengajuan_limit', 'idx_limit_status_tgl')) {
                    $table->dropIndex('idx_limit_status_tgl');
                }
                if (Schema::hasIndex('pengajuan_limit', 'idx_limit_anggota_status')) {
                    $table->dropIndex('idx_limit_anggota_status');
                }
            });
        }

        if (Schema::hasTable('pengajuan_aktivasi')) {
            Schema::table('pengajuan_aktivasi', function (Blueprint $table) {
                if (Schema::hasIndex('pengajuan_aktivasi', 'idx_aktivasi_status_tgl')) {
                    $table->dropIndex('idx_aktivasi_status_tgl');
                }
                if (Schema::hasIndex('pengajuan_aktivasi', 'idx_aktivasi_anggota_status')) {
                    $table->dropIndex('idx_aktivasi_anggota_status');
                }
            });
        }

        if (Schema::hasTable('shu_detail')) {
            Schema::table('shu_detail', function (Blueprint $table) {
                if (Schema::hasIndex('shu_detail', 'idx_shu_detail_periode_bayar')) {
                    $table->dropIndex('idx_shu_detail_periode_bayar');
                }
            });
        }

        if (Schema::hasTable('pengeluaran')) {
            Schema::table('pengeluaran', function (Blueprint $table) {
                if (Schema::hasIndex('pengeluaran', 'idx_pengeluaran_tipe_tanggal')) {
                    $table->dropIndex('idx_pengeluaran_tipe_tanggal');
                }
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasIndex('users', 'idx_users_status')) {
                    $table->dropIndex('idx_users_status');
                }
            });
        }

        if (Schema::hasTable('wa_logs')) {
            Schema::table('wa_logs', function (Blueprint $table) {
                if (Schema::hasIndex('wa_logs', 'idx_wa_logs_status_created')) {
                    $table->dropIndex('idx_wa_logs_status_created');
                }
            });
        }
    }
};
