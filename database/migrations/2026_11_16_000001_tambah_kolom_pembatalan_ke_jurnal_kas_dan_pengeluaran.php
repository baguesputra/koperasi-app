<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jurnal_kas', function (Blueprint $table) {
            if (! Schema::hasColumn('jurnal_kas', 'dibatalkan_pada')) {
                $table->timestamp('dibatalkan_pada')->nullable()->after('created_by');
            }
            if (! Schema::hasColumn('jurnal_kas', 'dibatalkan_oleh')) {
                $table->foreignId('dibatalkan_oleh')->nullable()->after('dibatalkan_pada')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('jurnal_kas', 'alasan_batal')) {
                $table->string('alasan_batal', 255)->nullable()->after('dibatalkan_oleh');
            }
            if (! Schema::hasColumn('jurnal_kas', 'koreksi_dari_id')) {
                $table->foreignId('koreksi_dari_id')->nullable()->after('alasan_batal')->constrained('jurnal_kas')->nullOnDelete();
            }
        });

        if (! Schema::hasColumn('pengeluaran', 'dibatalkan_pada')) {
            Schema::table('pengeluaran', function (Blueprint $table) {
                $table->timestamp('dibatalkan_pada')->nullable()->after('input_by');
            });
        }
    }

    public function down(): void
    {
        Schema::table('jurnal_kas', function (Blueprint $table) {
            if (Schema::hasColumn('jurnal_kas', 'koreksi_dari_id')) {
                $table->dropConstrainedForeignId('koreksi_dari_id');
            }
            if (Schema::hasColumn('jurnal_kas', 'dibatalkan_oleh')) {
                $table->dropConstrainedForeignId('dibatalkan_oleh');
            }
            $kolom = array_values(array_filter(
                ['alasan_batal', 'dibatalkan_pada'],
                fn ($c) => Schema::hasColumn('jurnal_kas', $c)
            ));
            if ($kolom !== []) {
                $table->dropColumn($kolom);
            }
        });

        if (Schema::hasColumn('pengeluaran', 'dibatalkan_pada')) {
            Schema::table('pengeluaran', function (Blueprint $table) {
                $table->dropColumn('dibatalkan_pada');
            });
        }
    }
};
