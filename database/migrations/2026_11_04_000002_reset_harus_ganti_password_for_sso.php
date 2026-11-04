<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('users')
            ->where('harus_ganti_password', true)
            ->update(['harus_ganti_password' => false]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Tidak perlu me-revert flag harus_ganti_password karena aplikasi berbasis SSO (GATE)
    }
};

