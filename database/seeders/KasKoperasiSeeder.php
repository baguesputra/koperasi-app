<?php

namespace Database\Seeders;

use App\Models\JurnalKas;
use App\Models\KasKoperasi;
use App\Models\User;
use Illuminate\Database\Seeder;

class KasKoperasiSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = User::where('no_karyawan', 'ADM-000001')->value('id') ?? 1;

        // Modal awal positif agar seeders berikutnya (pencairan, pengeluaran) lolos validasi saldo.
        KasKoperasi::firstOrCreate(['id' => 1], [
            'saldo_pinjaman' => 100_000_000,
            'saldo_dana_sosial' => 20_000_000,
            'saldo_simpanan' => 0,
            'saldo_pengembalian_simpanan' => 0,
        ]);

        // Jurnal saldo awal — supaya Laporan Arus Kas punya jejak dari mana modal datang.
        // Tanpa ini, saldo awal "muncul tanpa jejak" di laporan per kantong.
        JurnalKas::firstOrCreate(
            ['tipe' => 'masuk', 'kategori' => 'saldo_awal', 'kantong' => 'pinjaman'],
            ['jumlah' => 100_000_000, 'keterangan' => 'Saldo awal Dana Pinjaman', 'tanggal' => now()->subYears(2)->toDateString(), 'created_by' => $adminId]
        );
        JurnalKas::firstOrCreate(
            ['tipe' => 'masuk', 'kategori' => 'saldo_awal', 'kantong' => 'dana_sosial'],
            ['jumlah' => 20_000_000, 'keterangan' => 'Saldo awal Dana Sosial', 'tanggal' => now()->subYears(2)->toDateString(), 'created_by' => $adminId]
        );
    }
}
