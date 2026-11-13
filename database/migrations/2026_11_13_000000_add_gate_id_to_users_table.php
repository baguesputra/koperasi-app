<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'gate_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('gate_id')->nullable()->after('no_karyawan');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'gate_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('gate_id');
            });
        }
    }
};
