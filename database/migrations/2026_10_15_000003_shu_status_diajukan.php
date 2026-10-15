<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shu_periode', function (Blueprint $table) {
            // Alur: draft → diajukan (bahan meeting, dikunci) → final.
            $table->foreignId('diajukan_by')->nullable()->after('finalized_by')->constrained('users')->nullOnDelete();
            $table->timestamp('diajukan_at')->nullable()->after('diajukan_by');
        });
    }

    public function down(): void
    {
        Schema::table('shu_periode', function (Blueprint $table) {
            $table->dropConstrainedForeignId('diajukan_by');
            $table->dropColumn('diajukan_at');
        });
    }
};
