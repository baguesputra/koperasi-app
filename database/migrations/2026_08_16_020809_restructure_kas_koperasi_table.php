<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up(): void
{
    Schema::table('kas_koperasi', function (Blueprint $table) {
        $table->renameColumn('saldo_saat_ini', 'saldo_pinjaman');
        $table->decimal('saldo_dana_sosial', 15, 2)->default(0)->after('saldo_pinjaman');
    });
}

public function down(): void
{
    Schema::table('kas_koperasi', function (Blueprint $table) {
        $table->renameColumn('saldo_pinjaman', 'saldo_saat_ini');
        $table->dropColumn('saldo_dana_sosial');
    });
}
};
