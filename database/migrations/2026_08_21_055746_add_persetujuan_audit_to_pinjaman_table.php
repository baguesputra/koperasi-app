<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pinjaman', function (Blueprint $table) {
            $table->timestamp('disetujui_pada')->nullable()->after('tanggal_pencairan');
            $table->string('versi_syarat')->nullable()->after('disetujui_pada');
            $table->string('ip_address_setuju', 45)->nullable()->after('versi_syarat');
            $table->string('user_agent_setuju', 255)->nullable()->after('ip_address_setuju');
        });
    }

    public function down(): void
    {
        Schema::table('pinjaman', function (Blueprint $table) {
            $table->dropColumn(['disetujui_pada', 'versi_syarat', 'ip_address_setuju', 'user_agent_setuju']);
        });
    }
};
