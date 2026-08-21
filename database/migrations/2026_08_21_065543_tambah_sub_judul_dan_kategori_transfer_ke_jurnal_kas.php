<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jurnal_kas', function (Blueprint $table) {
            $table->string('sub_judul')->nullable()->after('keterangan');
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
            DB::statement("ALTER TABLE jurnal_kas DROP CONSTRAINT IF EXISTS jurnal_kas_kategori_check");
            DB::statement("ALTER TABLE jurnal_kas ADD CONSTRAINT jurnal_kas_kategori_check CHECK (kategori IN (
                'topup_bulanan','pencairan_pinjaman','pembayaran_angsuran',
                'dana_sosial_bulanan','pengeluaran_koperasi','pengeluaran_dana_sosial','saldo_awal',
                'pelunasan_resign_pinjaman','return_simpanan_pokok','return_simpanan_wajib',
                'transfer_ke_dana_pinjaman','terima_dari_pengembalian_simpanan'
            ))");

            DB::statement("ALTER TABLE jurnal_kas DROP CONSTRAINT IF EXISTS jurnal_kas_kantong_check");
            DB::statement("ALTER TABLE jurnal_kas ADD CONSTRAINT jurnal_kas_kantong_check CHECK (kantung IN ('pinjaman','dana_sosial','pengembalian_simpanan'))");
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
                'return_simpanan_wajib'
            ) NOT NULL");

            DB::statement("ALTER TABLE jurnal_kas MODIFY COLUMN kantong ENUM(
                'pinjaman',
                'dana_sosial'
            ) NOT NULL");
        } elseif ($driver === 'pgsql') {
            DB::statement("ALTER TABLE jurnal_kas DROP CONSTRAINT IF EXISTS jurnal_kas_kategori_check");
            DB::statement("ALTER TABLE jurnal_kas ADD CONSTRAINT jurnal_kas_kategori_check CHECK (kategori IN (
                'topup_bulanan','pencairan_pinjaman','pembayaran_angsuran',
                'dana_sosial_bulanan','pengeluaran_koperasi','pengeluaran_dana_sosial','saldo_awal',
                'pelunasan_resign_pinjaman','return_simpanan_pokok','return_simpanan_wajib'
            ))");

            DB::statement("ALTER TABLE jurnal_kas DROP CONSTRAINT IF EXISTS jurnal_kas_kantong_check");
            DB::statement("ALTER TABLE jurnal_kas ADD CONSTRAINT jurnal_kas_kantong_check CHECK (kantong IN ('pinjaman','dana_sosial'))");
        }

        Schema::table('jurnal_kas', function (Blueprint $table) {
            $table->dropColumn('sub_judul');
        });
    }
};
