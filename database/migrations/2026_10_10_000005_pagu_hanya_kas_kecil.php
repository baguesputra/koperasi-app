<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Pagu pinjaman bulanan tak dipakai lagi: pencairan cukup dijaga saldo bank.
        DB::table('setting_kas')->where('kunci', 'pagu_pinjaman_bulanan')->delete();

        // Satu-satunya pagu tersisa = target kas kecil (juga batas belanja dana sosial).
        DB::table('setting_kas')
            ->where('kunci', 'cadangan_sosial_bulan')
            ->update(['label' => 'Pagu Kas Kecil']);
    }

    public function down(): void
    {
        DB::table('setting_kas')->updateOrInsert(
            ['kunci' => 'pagu_pinjaman_bulanan'],
            ['label' => 'Pagu Pinjaman Bulanan', 'nominal' => 50_000_000]
        );

        DB::table('setting_kas')
            ->where('kunci', 'cadangan_sosial_bulan')
            ->update(['label' => 'Cadangan Sosial Bulanan']);
    }
};
