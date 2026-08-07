<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::create('tabel_tenor', function (Blueprint $table) {
        $table->id();
        $table->decimal('nominal_min', 15, 2);
        $table->decimal('nominal_max', 15, 2);
        $table->unsignedInteger('tenor_maksimal_bulan');
        $table->timestamps();
    });
}

    public function down(): void
    {
        Schema::dropIfExists('tabel_tenor');
    }
};
