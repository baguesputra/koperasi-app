<?php

namespace Database\Seeders;

use App\Models\KasKoperasi;
use App\Models\Simpanan;
use App\Models\User;
use App\Services\Keuangan\JurnalKasService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KasKoperasiSeeder extends Seeder
{
    public function run(): void
    {
        // Hitung saldo_simpanan dari simpanan wajib yang sudah ada (sebelum seeder simpanan jalan)
        $saldoSimpananAwal = (float) DB::table('simpanan')
            ->join('anggota', 'anggota.id', '=', 'simpanan.anggota_id')
            ->whereIn('simpanan.jenis', ['wajib'])
            ->where('anggota.status', 'aktif')
            ->sum('simpanan.jumlah');

        KasKoperasi::firstOrCreate(['id' => 1], [
            'saldo_pinjaman' => 100_000_000,
            'saldo_dana_sosial' => 3_000_000,
            'saldo_simpanan' => $saldoSimpananAwal,
            'saldo_pengembalian_simpanan' => 0,
        ]);

        $adminId = User::where('no_karyawan', 'ADM-000001')->value('id') ?? 1;

        (new JurnalKasService)->catatSaldoAwal('pinjaman', 100_000_000, $adminId);

        // Catat saldo awal simpanan jika ada
        if ($saldoSimpananAwal > 0) {
            (new JurnalKasService)->catatSaldoAwal('simpanan', $saldoSimpananAwal, $adminId);
        }
    }
}
