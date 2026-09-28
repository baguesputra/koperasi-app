<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function kolom(Blueprint $table): void
    {
        $table->decimal('nominal_diminta', 15, 2)->nullable();
        $table->unsignedInteger('tenor_diminta')->nullable();
        $table->decimal('nominal_disetujui_bendahara', 15, 2)->nullable();
        $table->unsignedInteger('tenor_disetujui_bendahara')->nullable();
        $table->decimal('nominal_disetujui', 15, 2)->nullable();
        $table->unsignedInteger('tenor_disetujui')->nullable();
        $table->decimal('kas_saldo_bendahara', 15, 2)->nullable();
        $table->decimal('kas_saldo_ketua', 15, 2)->nullable();
        $table->decimal('kas_sisa_ketua', 15, 2)->nullable();
    }

    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            Schema::table('pinjaman', function (Blueprint $table) {
                $table->decimal('nominal_diminta', 15, 2)->nullable()->after('nominal');
                $table->unsignedInteger('tenor_diminta')->nullable()->after('tenor_bulan');
                $table->decimal('nominal_disetujui_bendahara', 15, 2)->nullable()->after('tenor_diminta');
                $table->unsignedInteger('tenor_disetujui_bendahara')->nullable()->after('nominal_disetujui_bendahara');
                $table->decimal('nominal_disetujui', 15, 2)->nullable()->after('tenor_disetujui_bendahara');
                $table->unsignedInteger('tenor_disetujui')->nullable()->after('nominal_disetujui');
                $table->decimal('kas_saldo_bendahara', 15, 2)->nullable()->after('catatan_bendahara');
                $table->decimal('kas_saldo_ketua', 15, 2)->nullable()->after('kas_saldo_bendahara');
                $table->decimal('kas_sisa_ketua', 15, 2)->nullable()->after('kas_saldo_ketua');
            });

            DB::statement('UPDATE pinjaman SET nominal_diminta = nominal, tenor_diminta = tenor_bulan WHERE nominal_diminta IS NULL');

            return;
        }

        Schema::table('pinjaman', function (Blueprint $table) {
            $this->kolom($table);
        });

        DB::statement('UPDATE pinjaman SET nominal_diminta = nominal, tenor_diminta = tenor_bulan WHERE nominal_diminta IS NULL');
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            Schema::table('pinjaman', function (Blueprint $table) {
                $table->dropColumn([
                    'nominal_diminta', 'tenor_diminta',
                    'nominal_disetujui_bendahara', 'tenor_disetujui_bendahara',
                    'nominal_disetujui', 'tenor_disetujui',
                    'kas_saldo_bendahara', 'kas_saldo_ketua', 'kas_sisa_ketua',
                ]);
            });
        }
    }
};
