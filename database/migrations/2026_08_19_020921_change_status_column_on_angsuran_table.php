<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        Schema::table('angsuran', function (Blueprint $table) {
            $table->string('status', 30)->default('belum_bayar')->change();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        Schema::table('angsuran', function (Blueprint $table) {
            $table->enum('status', ['belum_bayar', 'lunas'])->default('belum_bayar')->change();
        });
    }
};
