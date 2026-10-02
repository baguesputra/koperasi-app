<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const TAMBAHAN = [
        'sisih_kas_kecil',
        'terima_sisih_kas_kecil',
    ];

    private const DASAR = [
        'topup_bulanan',
        'pencairan_pinjaman',
        'pembayaran_angsuran',
        'dana_sosial_bulanan',
        'pengeluaran_koperasi',
        'pengeluaran_dana_sosial',
        'saldo_awal',
        'pelunasan_resign_pinjaman',
        'pelunasan_resign_simpanan',
        'simpanan_resign_masuk',
        'return_simpanan_pokok',
        'return_simpanan_wajib',
        'simpanan_pokok_masuk',
        'simpanan_wajib_masuk',
        'transfer_ke_dana_pinjaman',
        'terima_dari_pengembalian_simpanan',
        'talangan_sosial_ke_pinjaman',
        'talangan_simpanan_ke_pinjaman',
        'terima_talangan_dari_sosial',
        'terima_talangan_dari_simpanan',
        'kembali_talangan_dari_pinjaman',
        'kembali_talangan_ke_simpanan',
        'kembali_talangan_ke_sosial',
    ];

    public function up(): void
    {
        $driver = DB::connection()->getDriverName();
        $semua = array_merge(self::DASAR, self::TAMBAHAN);
        $daftar = "'".implode("','", $semua)."'";

        if ($driver === 'mysql') {
            $enum = implode(",\n                ", array_map(fn ($k) => "'{$k}'", $semua));
            DB::statement("ALTER TABLE jurnal_kas MODIFY COLUMN kategori ENUM(\n                {$enum}\n            ) NOT NULL");
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE jurnal_kas DROP CONSTRAINT IF EXISTS jurnal_kas_kategori_check');
            DB::statement("ALTER TABLE jurnal_kas ADD CONSTRAINT jurnal_kas_kategori_check CHECK (kategori IN ({$daftar}))");
        }
        // SQLite: enum validasi di level aplikasi.
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();
        $daftar = "'".implode("','", self::DASAR)."'";

        if ($driver === 'mysql') {
            $enum = implode(",\n                ", array_map(fn ($k) => "'{$k}'", self::DASAR));
            DB::statement("ALTER TABLE jurnal_kas MODIFY COLUMN kategori ENUM(\n                {$enum}\n            ) NOT NULL");
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE jurnal_kas DROP CONSTRAINT IF EXISTS jurnal_kas_kategori_check');
            DB::statement("ALTER TABLE jurnal_kas ADD CONSTRAINT jurnal_kas_kategori_check CHECK (kategori IN ({$daftar}))");
        }
    }
};
