<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('setting_bunga', function (Blueprint $table) {
            $table->id();
            $table->decimal('persentase', 5, 2); // contoh: 1.00 untuk 1%
            $table->date('berlaku_dari_tanggal');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('setting_bunga');
    }
};
