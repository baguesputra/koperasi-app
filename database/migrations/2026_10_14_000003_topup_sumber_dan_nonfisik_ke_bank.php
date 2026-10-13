<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jurnal_kas', function (Blueprint $table) {
            $table->string('sumber_topup', 20)->nullable()->after('kategori');
        });

        // Topup lama dianggap modal (bukan pendapatan SHU).
        DB::table('jurnal_kas')
            ->where('kategori', 'topup_bulanan')
            ->whereNull('sumber_topup')
            ->update(['sumber_topup' => 'modal']);

        // Hapus konsep non-kas: semua baris kini menggerakkan saldo fisik
        // (kantong kas_kecil → kas kecil, selainnya → bank). Hitung ulang
        // saldo_setelah kronologis agar baris ex-audit (dulu 0) jadi clean.
        $bank = 0.0;
        $kas = 0.0;

        DB::table('jurnal_kas')
            ->orderBy('tanggal')
            ->orderBy('id')
            ->chunk(500, function ($rows) use (&$bank, &$kas) {
                foreach ($rows as $row) {
                    $jumlah = (float) $row->jumlah;

                    if ($row->kantong === 'kas_kecil') {
                        $kas += $row->tipe === 'masuk' ? $jumlah : -$jumlah;
                        $saldo = round($kas, 2);
                    } else {
                        $bank += $row->tipe === 'masuk' ? $jumlah : -$jumlah;
                        $saldo = round($bank, 2);
                    }

                    DB::table('jurnal_kas')
                        ->where('id', $row->id)
                        ->update(['saldo_setelah' => $saldo]);
                }
            });

        if (DB::table('kas_koperasi')->where('id', 1)->exists()) {
            DB::table('kas_koperasi')->where('id', 1)->update([
                'saldo_bank' => round($bank, 2),
                'saldo_kas_kecil' => round($kas, 2),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('jurnal_kas', function (Blueprint $table) {
            $table->dropColumn('sumber_topup');
        });
        // saldo_setelah hasil hitung ulang tidak dikembalikan (irreversible).
    }
};
