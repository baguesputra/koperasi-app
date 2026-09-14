<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('divisi', function (Blueprint $table) {
            $table->foreignId('departemen_id')->nullable()->after('perusahaan_id')->constrained('departemen')->nullOnDelete();
        });

        Schema::table('jabatan', function (Blueprint $table) {
            $table->foreignId('division_id')->nullable()->after('departemen_id')->constrained('divisi')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('jabatan', function (Blueprint $table) {
            $table->dropConstrainedForeignId('division_id');
        });
        Schema::table('divisi', function (Blueprint $table) {
            $table->dropConstrainedForeignId('departemen_id');
        });
    }
};
