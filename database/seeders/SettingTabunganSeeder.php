<?php

namespace Database\Seeders;

use App\Models\SettingBungaTabungan;
use App\Models\SettingTabungan;
use Illuminate\Database\Seeder;

class SettingTabunganSeeder extends Seeder
{
    public function run(): void
    {
        if (! SettingBungaTabungan::exists()) {
            SettingBungaTabungan::create([
                'persentase' => 2.00,
                'berlaku_dari_tanggal' => '2026-01-01',
                'keterangan' => 'Tarif awal bunga tabungan anggota 2% per tahun',
            ]);
        }

        if (! SettingTabungan::exists()) {
            SettingTabungan::create([
                'metode_bunga' => 'saldo_harian',
                'min_setor' => 50_000,
                'saldo_minimal' => 50_000,
                'min_tarik' => 50_000,
                'bank_tujuan_nama' => 'Bank Mandiri',
                'bank_tujuan_rekening' => '000-00-0000000-0',
                'bank_tujuan_atas_nama' => 'Koperasi Karyawan Karya Mandiri',
                'buka_rekening_aktif' => true,
            ]);
        }
    }
}
