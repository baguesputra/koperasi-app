<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const STATUS_LAMA = "'diajukan','ditinjau_bendahara','approved_bendahara','approved_ketua','aktif','lunas','ditolak'";

    private const STATUS_BARU = "'diajukan','ditinjau_bendahara','approved_admin','approved_bendahara','approved_ketua','aktif','lunas','ditolak'";

    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE pinjaman MODIFY COLUMN status ENUM(".self::STATUS_BARU.") NOT NULL DEFAULT 'diajukan'");
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::table('pinjaman')->where('status', 'approved_admin')->update(['status' => 'diajukan']);

        DB::statement("ALTER TABLE pinjaman MODIFY COLUMN status ENUM(".self::STATUS_LAMA.") NOT NULL DEFAULT 'diajukan'");
    }
};
