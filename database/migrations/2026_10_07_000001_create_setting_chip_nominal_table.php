<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('setting_chip_nominal', function (Blueprint $table) {
            $table->id();
            $table->string('grup', 50);
            $table->decimal('nominal', 15, 2);
            $table->unsignedTinyInteger('urutan')->default(0);
            $table->timestamps();
            $table->unique(['grup', 'nominal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('setting_chip_nominal');
    }
};
