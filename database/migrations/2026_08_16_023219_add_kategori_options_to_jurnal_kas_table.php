<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    DB::statement("ALTER TABLE jurnal_kas MODIFY COLUMN kategori ENUM(
        'topup_bulanan',
        'pencairan_pinjaman',
        'pembayaran_angsuran',
        'dana_sosial_bulanan',
        'pengeluaran_koperasi',
        'pengeluaran_dana_sosial'
    )");
}

public function down(): void
{
    DB::statement("ALTER TABLE jurnal_kas MODIFY COLUMN kategori ENUM(
        'topup_bulanan',
        'pencairan_pinjaman',
        'pembayaran_angsuran'
    )");
}
};
