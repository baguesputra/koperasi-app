<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jurnal_kas', function (Blueprint $table) {
            $table->string('kantong')->default('pinjaman')->after('kategori'); // pinjaman | dana_sosial
        });
    }

    public function down(): void
    {
        Schema::table('jurnal_kas', function (Blueprint $table) {
            $table->dropColumn('kantong');
        });
    }
};
