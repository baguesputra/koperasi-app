<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('no_karyawan')->nullable()->unique()->after('email');
            $table->boolean('harus_ganti_password')->default(false)->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['no_karyawan', 'harus_ganti_password']);
        });
    }
};
