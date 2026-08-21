<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE jurnal_kas MODIFY COLUMN kategori ENUM(
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
                'transfer_ke_dana_pinjaman',
                'terima_dari_pengembalian_simpanan'
            ) NOT NULL");
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE jurnal_kas DROP CONSTRAINT IF EXISTS jurnal_kas_kategori_check');
            DB::statement("ALTER TABLE jurnal_kas ADD CONSTRAINT jurnal_kas_kategori_check CHECK (kategori IN (
                'topup_bulanan','pencairan_pinjaman','pembayaran_angsuran',
                'dana_sosial_bulanan','pengeluaran_koperasi','pengeluaran_dana_sosial','saldo_awal',
                'pelunasan_resign_pinjaman','pelunasan_resign_simpanan','simpanan_resign_masuk',
                'return_simpanan_pokok','return_simpanan_wajib',
                'transfer_ke_dana_pinjaman','terima_dari_pengembalian_simpanan'
            ))");
        }
        // SQLite: enum validasi di level aplikasi.
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
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
                'return_simpanan_wajib',
                'transfer_ke_dana_pinjaman',
                'terima_dari_pengembalian_simpanan'
            ) NOT NULL");
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE jurnal_kas DROP CONSTRAINT IF EXISTS jurnal_kas_kategori_check');
            DB::statement("ALTER TABLE jurnal_kas ADD CONSTRAINT jurnal_kas_kategori_check CHECK (kategori IN (
                'topup_bulanan','pencairan_pinjaman','pembayaran_angsuran',
                'dana_sosial_bulanan','pengeluaran_koperasi','pengeluaran_dana_sosial','saldo_awal',
                'pelunasan_resign_pinjaman','return_simpanan_pokok','return_simpanan_wajib',
                'transfer_ke_dana_pinjaman','terima_dari_pengembalian_simpanan'
            ))");
        }
    }
};
