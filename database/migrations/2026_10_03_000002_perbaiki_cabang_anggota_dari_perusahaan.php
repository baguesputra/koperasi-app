<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Sinkron massal Gate selalu menulis cabang 'Banjarmasin', sehingga anggota
     * Big Mall Samarinda & Duta Mall Palangkaraya tercatat di cabang yang salah.
     * Perbaiki dari kode perusahaan (lihat SinkronisasiAnggotaService::CABANG_PER_KODE).
     */
    public function up(): void
    {
        $peta = ['BM' => 'Samarinda', 'DMP' => 'Palangka'];

        foreach ($peta as $kode => $cabang) {
            $ids = DB::table('perusahaan')->where('kode', $kode)->pluck('id');
            if ($ids->isNotEmpty()) {
                DB::table('anggota')->whereIn('perusahaan_id', $ids)->update(['cabang' => $cabang]);
            }
        }
    }

    public function down(): void
    {
        // Kondisi sebelum migration: semua baris tercatat 'Banjarmasin'.
        $ids = DB::table('perusahaan')->whereIn('kode', ['BM', 'DMP'])->pluck('id');
        if ($ids->isNotEmpty()) {
            DB::table('anggota')->whereIn('perusahaan_id', $ids)->update(['cabang' => 'Banjarmasin']);
        }
    }
};
