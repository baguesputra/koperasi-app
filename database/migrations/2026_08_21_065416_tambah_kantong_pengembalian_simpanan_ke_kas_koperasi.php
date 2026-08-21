<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kas_koperasi', function (Blueprint $table) {
            $table->decimal('saldo_pengembalian_simpanan', 15, 2)->default(0)->after('saldo_dana_sosial');
        });
    }

    public function down(): void
    {
        Schema::table('kas_koperasi', function (Blueprint $table) {
            $table->dropColumn('saldo_pengembalian_simpanan');
        });
    }
};
