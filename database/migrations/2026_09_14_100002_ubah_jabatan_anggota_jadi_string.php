<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE anggota MODIFY jabatan VARCHAR(255) NOT NULL DEFAULT 'staff'");
        } else {
            Schema::table('anggota', function (Blueprint $table) {
                $table->string('jabatan', 255)->default('staff')->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('anggota', function (Blueprint $table) {
            $table->string('jabatan', 255)->default('staff')->change();
        });
    }
};
