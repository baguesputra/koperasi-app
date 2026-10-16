<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shu_komponen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shu_periode_id')->constrained('shu_periode')->cascadeOnDelete();
            // Slug stabil untuk key nominal & sync (dibuat dari label).
            $table->string('slug', 50);
            $table->string('label', 100);
            // Fraksi 0-1 seperti pct_* bawaan (input UI % dibagi 100).
            $table->decimal('persen', 5, 4)->default(0);
            $table->timestamps();

            $table->unique(['shu_periode_id', 'slug'], 'uniq_shu_komponen_periode_slug');
            $table->unique(['shu_periode_id', 'label'], 'uniq_shu_komponen_periode_label');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shu_komponen');
    }
};
