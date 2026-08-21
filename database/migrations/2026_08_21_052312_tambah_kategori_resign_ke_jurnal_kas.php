<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            // Perlebar enum: tambah kategori baru untuk fitur resign
            DB::statement("ALTER TABLE jurnal_kas MODIFY COLUMN kategori ENUM(
                'topup_bulanan',
                'pencairan_pinjaman',
                'pembayaran_angsuran',
                'dana_sosial_bulanan',
                'pengeluaran_koperasi',
                'pengeluaran_dana_sosial',
                'saldo_awal',
                'pelunasan_resign_pinjaman',
                'return_simpanan_pokok',
                'return_simpanan_wajib'
            ) NOT NULL");
        }
        // SQLite: enum adalah CHECK constraint, validasi di level aplikasi sudah cukup.
    }

    public function down(): void
    {
        // SQLite test environment tidak support ALTER COLUMN. Untuk test,
        // validasi enum sudah cukup di level aplikasi. Down hanya relevan untuk MySQL/PG.
        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE jurnal_kas MODIFY COLUMN kategori ENUM(
                'topup_bulanan',
                'pencairan_pinjaman',
                'pembayaran_angsuran',
                'dana_sosial_bulanan',
                'pengeluaran_koperasi',
                'pengeluaran_dana_sosial',
                'saldo_awal'
            ) NOT NULL");
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE jurnal_kas DROP CONSTRAINT IF EXISTS jurnal_kas_kategori_check');
            DB::statement("ALTER TABLE jurnal_kas ADD CONSTRAINT jurnal_kas_kategori_check CHECK (kategori IN (
                'topup_bulanan','pencairan_pinjaman','pembayaran_angsuran',
                'dana_sosial_bulanan','pengeluaran_koperasi','pengeluaran_dana_sosial','saldo_awal'
            ))");
        }
    }
};
