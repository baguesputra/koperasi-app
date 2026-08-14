<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pinjaman', function (Blueprint $table) {
            $table->foreignId('pengaju_user_id')->nullable()->after('anggota_id')
                ->constrained('users')->nullOnDelete();
            $table->boolean('cair_oleh_bendahara')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('pinjaman', function (Blueprint $table) {
            $table->dropForeign(['pengaju_user_id']);
            $table->dropColumn(['pengaju_user_id', 'cair_oleh_bendahara']);
        });
    }
};
