<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const TAMBAHAN = [
        'bank',
        'kas_kecil',
    ];

    private const DASAR = [
        'pinjaman',
        'dana_sosial',
        'pengembalian_simpanan',
        'simpanan',
    ];

    public function up(): void
    {
        $driver = DB::connection()->getDriverName();
        $semua = array_merge(self::DASAR, self::TAMBAHAN);
        $daftar = "'".implode("','", $semua)."'";

        if ($driver === 'mysql') {
            $enum = implode(",\n                ", array_map(fn ($k) => "'{$k}'", $semua));
            DB::statement("ALTER TABLE jurnal_kas MODIFY COLUMN kantong ENUM(\n                {$enum}\n            ) NOT NULL");
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE jurnal_kas DROP CONSTRAINT IF EXISTS jurnal_kas_kantong_check');
            DB::statement("ALTER TABLE jurnal_kas ADD CONSTRAINT jurnal_kas_kantong_check CHECK (kantong IN ({$daftar}))");
        }
        // SQLite: enum validasi di level aplikasi.
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();
        $daftar = "'".implode("','", self::DASAR)."'";

        if ($driver === 'mysql') {
            $enum = implode(",\n                ", array_map(fn ($k) => "'{$k}'", self::DASAR));
            DB::statement("ALTER TABLE jurnal_kas MODIFY COLUMN kantong ENUM(\n                {$enum}\n            ) NOT NULL");
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE jurnal_kas DROP CONSTRAINT IF EXISTS jurnal_kas_kantong_check');
            DB::statement("ALTER TABLE jurnal_kas ADD CONSTRAINT jurnal_kas_kantong_check CHECK (kantong IN ({$daftar}))");
        }
    }
};
