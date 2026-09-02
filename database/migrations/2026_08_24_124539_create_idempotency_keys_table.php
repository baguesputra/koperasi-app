<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('idempotency_keys', function (Blueprint $table) {
            $table->string('key', 64);
            $table->unsignedBigInteger('user_id');
            $table->json('response')->nullable();
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->string('endpoint', 191);
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->primary(['key', 'user_id']);
            $table->index('expires_at');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idempotency_keys');
    }
};
