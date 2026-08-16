<?php

namespace App\Services\Keuangan;

use App\Models\JurnalKas;
use App\Models\KasKoperasi;
use App\Models\Pengeluaran;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PengeluaranService
{
    public function catat(string $jenis, float $jumlah, string $keterangan, string $tanggal, int $userId): Pengeluaran
    {
        $kas = KasKoperasi::firstOrFail();
        $kolomSaldo = $jenis === 'koperasi' ? 'saldo_pinjaman' : 'saldo_dana_sosial';

        if ($kas->{$kolomSaldo} < $jumlah) {
            $labelKantong = $jenis === 'koperasi' ? 'Dana Pinjaman' : 'Dana Sosial';
            throw new RuntimeException(
                "Saldo {$labelKantong} tidak mencukupi. Saldo saat ini: Rp " . number_format($kas->{$kolomSaldo}, 0, ',', '.')
            );
        }

        return DB::transaction(function () use ($jenis, $jumlah, $keterangan, $tanggal, $userId, $kas, $kolomSaldo) {
            $pengeluaran = Pengeluaran::create([
                'jenis' => $jenis,
                'jumlah' => $jumlah,
                'keterangan' => $keterangan,
                'tanggal' => $tanggal,
                'input_by' => $userId,
            ]);

            $kas->decrement($kolomSaldo, $jumlah);

            JurnalKas::create([
                'tipe' => 'keluar',
                'kategori' => $jenis === 'koperasi' ? 'pengeluaran_koperasi' : 'pengeluaran_dana_sosial',
                'kantong' => $jenis === 'koperasi' ? 'pinjaman' : 'dana_sosial',
                'jumlah' => $jumlah,
                'keterangan' => $keterangan,
                'referensi_id' => $pengeluaran->id,
                'tanggal' => $tanggal,
                'created_by' => $userId,
            ]);

            return $pengeluaran;
        });
    }
}