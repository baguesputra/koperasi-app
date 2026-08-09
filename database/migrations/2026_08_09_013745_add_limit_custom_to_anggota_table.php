<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('anggota', function (Blueprint $table) {
            $table->decimal('limit_custom', 15, 2)->nullable()->after('status');
            $table->string('limit_custom_keterangan')->nullable()->after('limit_custom');
        });
    }

    public function down(): void
    {
        Schema::table('anggota', function (Blueprint $table) {
            $table->dropColumn(['limit_custom', 'limit_custom_keterangan']);
        });
    }
};
