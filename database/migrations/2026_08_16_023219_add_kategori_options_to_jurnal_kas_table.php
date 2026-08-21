<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE jurnal_kas MODIFY COLUMN kategori ENUM(
            'topup_bulanan',
            'pencairan_pinjaman',
            'pembayaran_angsuran',
            'dana_sosial_bulanan',
            'pengeluaran_koperasi',
            'pengeluaran_dana_sosial'
        )");
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE jurnal_kas MODIFY COLUMN kategori ENUM(
            'topup_bulanan',
            'pencairan_pinjaman',
            'pembayaran_angsuran'
        )");
        }
    }
};
