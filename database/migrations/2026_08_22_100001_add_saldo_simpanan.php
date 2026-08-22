<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kas_koperasi', function (Blueprint $table) {
            $table->decimal('saldo_simpanan', 15, 2)->default(0)->after('saldo_pengembalian_simpanan');
        });

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
                'simpanan_pokok_masuk',
                'simpanan_wajib_masuk',
                'transfer_ke_dana_pinjaman',
                'terima_dari_pengembalian_simpanan'
            ) NOT NULL");

            DB::statement("ALTER TABLE jurnal_kas MODIFY COLUMN kantong ENUM(
                'pinjaman',
                'dana_sosial',
                'pengembalian_simpanan',
                'simpanan'
            ) NOT NULL");
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE jurnal_kas DROP CONSTRAINT IF EXISTS jurnal_kas_kategori_check');
            DB::statement("ALTER TABLE jurnal_kas ADD CONSTRAINT jurnal_kas_kategori_check CHECK (kategori IN (
                'topup_bulanan','pencairan_pinjaman','pembayaran_angsuran',
                'dana_sosial_bulanan','pengeluaran_koperasi','pengeluaran_dana_sosial','saldo_awal',
                'pelunasan_resign_pinjaman','pelunasan_resign_simpanan','simpanan_resign_masuk',
                'return_simpanan_pokok','return_simpanan_wajib',
                'simpanan_pokok_masuk','simpanan_wajib_masuk',
                'transfer_ke_dana_pinjaman','terima_dari_pengembalian_simpanan'
            ))");

            DB::statement('ALTER TABLE jurnal_kas DROP CONSTRAINT IF EXISTS jurnal_kas_kantong_check');
            DB::statement("ALTER TABLE jurnal_kas ADD CONSTRAINT jurnal_kas_kantong_check CHECK (kantong IN (
                'pinjaman','dana_sosial','pengembalian_simpanan','simpanan'
            ))");
        }
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
                'pelunasan_resign_simpanan',
                'simpanan_resign_masuk',
                'return_simpanan_pokok',
                'return_simpanan_wajib',
                'transfer_ke_dana_pinjaman',
                'terima_dari_pengembalian_simpanan'
            ) NOT NULL");

            DB::statement("ALTER TABLE jurnal_kas MODIFY COLUMN kantong ENUM(
                'pinjaman',
                'dana_sosial',
                'pengembalian_simpanan'
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

            DB::statement('ALTER TABLE jurnal_kas DROP CONSTRAINT IF EXISTS jurnal_kas_kantong_check');
            DB::statement("ALTER TABLE jurnal_kas ADD CONSTRAINT jurnal_kas_kantong_check CHECK (kantong IN (
                'pinjaman','dana_sosial','pengembalian_simpanan'
            ))");
        }

        Schema::table('kas_koperasi', function (Blueprint $table) {
            $table->dropColumn('saldo_simpanan');
        });
    }
};