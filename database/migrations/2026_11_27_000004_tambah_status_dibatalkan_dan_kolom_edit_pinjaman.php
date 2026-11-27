<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pinjaman', function (Blueprint $table) {
            if (! Schema::hasColumn('pinjaman', 'diedit_pada')) {
                $table->timestamp('diedit_pada')->nullable()->after('tanggal_pengajuan');
            }
            if (! Schema::hasColumn('pinjaman', 'jumlah_edit')) {
                $table->unsignedSmallInteger('jumlah_edit')->default(0)->after('diedit_pada');
            }
            if (! Schema::hasColumn('pinjaman', 'dibatalkan_pada')) {
                $table->timestamp('dibatalkan_pada')->nullable()->after('disetujui_ketua_pada');
            }
            if (! Schema::hasColumn('pinjaman', 'dibatalkan_oleh')) {
                $table->foreignId('dibatalkan_oleh')
                    ->nullable()
                    ->after('dibatalkan_pada')
                    ->constrained('users')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('pinjaman', 'alasan_batal')) {
                $table->string('alasan_batal', 500)->nullable()->after('dibatalkan_oleh');
            }
        });

        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE pinjaman MODIFY COLUMN status ENUM('diajukan','ditinjau_bendahara','approved_admin','approved_bendahara','approved_ketua','aktif','lunas','ditolak','dihapusbukukan','dibatalkan') NOT NULL DEFAULT 'diajukan'");
        } elseif ($driver === 'sqlite') {
            Schema::table('pinjaman', function (Blueprint $table) {
                $table->string('status', 30)->default('diajukan')->change();
            });
        }
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql') {
            DB::table('pinjaman')->where('status', 'dibatalkan')->update(['status' => 'ditolak']);
            DB::statement("ALTER TABLE pinjaman MODIFY COLUMN status ENUM('diajukan','ditinjau_bendahara','approved_admin','approved_bendahara','approved_ketua','aktif','lunas','ditolak','dihapusbukukan') NOT NULL DEFAULT 'diajukan'");
        }

        Schema::table('pinjaman', function (Blueprint $table) {
            if (Schema::hasColumn('pinjaman', 'dibatalkan_oleh')) {
                $table->dropConstrainedForeignId('dibatalkan_oleh');
            }
            foreach (['alasan_batal', 'dibatalkan_pada', 'jumlah_edit', 'diedit_pada'] as $kolom) {
                if (Schema::hasColumn('pinjaman', $kolom)) {
                    $table->dropColumn($kolom);
                }
            }
        });
    }
};
