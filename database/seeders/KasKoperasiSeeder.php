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

        // Modal awal: fisik bank + kas kecil (kolom pot virtual = 0, tak dipakai).
        // Jurnal saldo_awal virtual (pinjaman 100M + dana_sosial 20M) masuk bank
        // via aturan efek jurnal; sisih 20M bank → kas kecil.
        KasKoperasi::firstOrCreate(['id' => 1], [
            'saldo_pinjaman' => 0,
            'saldo_dana_sosial' => 0,
            'saldo_simpanan' => 0,
            'saldo_pengembalian_simpanan' => 0,
            'saldo_bank' => 100_000_000,
            'saldo_kas_kecil' => 20_000_000,
        ]);

        $tglAwal = now()->subYears(2)->toDateString();

        // Virtual journals (audit per kantong) — juga nambah saldo_bank via service logika.
        JurnalKas::firstOrCreate(
            ['tipe' => 'masuk', 'kategori' => 'saldo_awal', 'kantong' => 'pinjaman'],
            ['jumlah' => 100_000_000, 'keterangan' => 'Saldo awal Dana Pinjaman', 'tanggal' => $tglAwal, 'created_by' => $adminId]
        );
        JurnalKas::firstOrCreate(
            ['tipe' => 'masuk', 'kategori' => 'saldo_awal', 'kantong' => 'dana_sosial'],
            ['jumlah' => 20_000_000, 'keterangan' => 'Saldo awal Dana Sosial', 'tanggal' => $tglAwal, 'created_by' => $adminId]
        );

        // Fisik journals: sisih bank → kas kecil (tanpa saldo_awal bank terpisah).
        JurnalKas::firstOrCreate(
            ['tipe' => 'keluar', 'kategori' => 'sisih_kas_kecil', 'kantong' => 'bank'],
            ['jumlah' => 20_000_000, 'keterangan' => 'Sisih kas kecil awal', 'tanggal' => $tglAwal, 'created_by' => $adminId]
        );
        JurnalKas::firstOrCreate(
            ['tipe' => 'masuk', 'kategori' => 'terima_sisih_kas_kecil', 'kantong' => 'kas_kecil'],
            ['jumlah' => 20_000_000, 'keterangan' => 'Terima sisih kas kecil awal', 'tanggal' => $tglAwal, 'created_by' => $adminId]
        );
    }
}
