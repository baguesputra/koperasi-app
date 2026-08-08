<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('setting_simpanan', function (Blueprint $table) {
            $table->id();
            $table->string('jenis')->unique(); // pokok, wajib, dana_sosial
            $table->string('label');
            $table->decimal('nominal', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('setting_simpanan');
    }
};
