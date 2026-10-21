<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('audit_log', function (Blueprint $table) {
            $table->string('ip_address', 45)->nullable()->after('data_baru');
            $table->text('user_agent')->nullable()->after('ip_address');
            $table->index(['user_id', 'created_at'], 'idx_audit_user_created');
            $table->index(['aksi', 'created_at'], 'idx_audit_aksi_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_log', function (Blueprint $table) {
            $table->dropIndex('idx_audit_user_created');
            $table->dropIndex('idx_audit_aksi_created');
            $table->dropColumn(['ip_address', 'user_agent']);
        });
    }
};
