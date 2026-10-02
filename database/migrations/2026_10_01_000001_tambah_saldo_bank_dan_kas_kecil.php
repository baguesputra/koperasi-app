<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const VIRTUAL_SAJA = [
        'simpanan_resign_masuk',
        'pelunasan_resign_pinjaman',
        'pelunasan_resign_simpanan',
        'talangan_sosial_ke_pinjaman',
        'talangan_simpanan_ke_pinjaman',
        'terima_talangan_dari_sosial',
        'terima_talangan_dari_simpanan',
        'kembali_talangan_dari_pinjaman',
        'kembali_talangan_ke_simpanan',
        'kembali_talangan_ke_sosial',
        'transfer_ke_dana_pinjaman',
        'terima_dari_pengembalian_simpanan',
    ];

    public function up(): void
    {
        // Tanpa after(): skema lokal lama tidak punya kolom saldo_saat_ini.
        Schema::table('kas_koperasi', function (Blueprint $table) {
            $table->decimal('saldo_bank', 15, 2)->default(0);
            $table->decimal('saldo_kas_kecil', 15, 2)->default(0);
        });

        $bank = 0.0;
        $kas = 0.0;

        foreach (DB::table('jurnal_kas')->cursor() as $j) {
            $jumlah = (float) $j->jumlah;

            if ($j->kantong === 'bank') {
                $bank += $j->tipe === 'masuk' ? $jumlah : -$jumlah;
            } elseif ($j->kantong === 'kas_kecil') {
                $kas += $j->tipe === 'masuk' ? $jumlah : -$jumlah;
            } elseif (in_array($j->kategori, self::VIRTUAL_SAJA, true)) {
                continue;
            } elseif ($j->tipe === 'masuk') {
                $bank += $jumlah;
            } elseif (str_starts_with($j->kategori, 'pengeluaran_')) {
                $kas -= $jumlah;
            } else {
                $bank -= $jumlah;
            }
        }

        DB::table('kas_koperasi')->orderBy('id')->get()->each(function ($kasRow) use ($bank, $kas) {
            DB::table('kas_koperasi')->where('id', $kasRow->id)->update([
                'saldo_bank' => round(max(0, $bank), 2),
                'saldo_kas_kecil' => round(max(0, $kas), 2),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('kas_koperasi', function (Blueprint $table) {
            $table->dropColumn(['saldo_bank', 'saldo_kas_kecil']);
        });
    }
};
