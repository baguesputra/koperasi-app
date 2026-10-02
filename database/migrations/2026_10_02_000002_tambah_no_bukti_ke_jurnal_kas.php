<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jurnal_kas', function (Blueprint $table) {
            $table->string('no_bukti', 40)->nullable()->after('kantong')->unique();
        });
    }

    public function down(): void
    {
        Schema::table('jurnal_kas', function (Blueprint $table) {
            $table->dropUnique(['no_bukti']);
            $table->dropColumn('no_bukti');
        });
    }
};
