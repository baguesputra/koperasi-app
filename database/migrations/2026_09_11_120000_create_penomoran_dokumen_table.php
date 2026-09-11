<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penomoran_dokumen', function (Blueprint $table) {
            $table->id();
            $table->string('jenis', 20);
            $table->year('tahun');
            $table->unsignedInteger('terakhir')->default(0);
            $table->timestamps();

            $table->unique(['jenis', 'tahun']);
        });

        Schema::table('pinjaman', function (Blueprint $table) {
            $table->string('nomor_dokumen')->nullable()->unique()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('pinjaman', function (Blueprint $table) {
            $table->dropColumn('nomor_dokumen');
        });

        Schema::dropIfExists('penomoran_dokumen');
    }
};
