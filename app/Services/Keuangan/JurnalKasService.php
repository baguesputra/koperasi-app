<?php

namespace App\Services\Keuangan;

use App\Models\JurnalKas;
use App\Models\KasKoperasi;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class JurnalKasService
{
    /**
     * Satu-satunya pintu untuk mengubah saldo kas + mencatat jurnal.
     * Selalu dipanggil sebagai 1 paket atomic dengan lock, supaya aman dari race condition.
     */
    public function catat(
        string $tipe,       // 'masuk' | 'keluar'
        string $kategori,
        string $kantong,    // 'pinjaman' | 'dana_sosial'
        float $jumlah,
        string $keterangan,
        ?int $referensiId,
        string $tanggal,
        int $userId
    ): JurnalKas {
        return DB::transaction(function () use ($tipe, $kategori, $kantong, $jumlah, $keterangan, $referensiId, $tanggal, $userId) {
            // Lock baris kas_koperasi - proses lain harus antre sampai transaksi ini selesai
            $kas = KasKoperasi::lockForUpdate()->firstOrFail();

            $kolom = $kantong === 'pinjaman' ? 'saldo_pinjaman' : 'saldo_dana_sosial';

            if ($tipe === 'keluar' && $kas->{$kolom} < $jumlah) {
                $labelKantong = $kantong === 'pinjaman' ? 'Dana Pinjaman' : 'Dana Sosial';
                throw new RuntimeException(
                    "Saldo {$labelKantong} tidak mencukupi. Saldo saat ini: Rp ".number_format($kas->{$kolom}, 0, ',', '.')
                );
            }

            if ($tipe === 'masuk') {
                $kas->increment($kolom, $jumlah);
            } else {
                $kas->decrement($kolom, $jumlah);
            }

            $kas->refresh();

            return JurnalKas::create([
                'tipe' => $tipe,
                'kategori' => $kategori,
                'kantong' => $kantong,
                'jumlah' => $jumlah,
                'saldo_setelah' => $kas->{$kolom},
                'keterangan' => $keterangan,
                'referensi_id' => $referensiId,
                'tanggal' => $tanggal,
                'created_by' => $userId,
            ]);
        });
    }

    public function catatSaldoAwal(string $kantong, float $jumlah, int $userId): JurnalKas
    {
        return $this->catat(
            tipe: 'masuk',
            kategori: 'saldo_awal',
            kantong: $kantong,
            jumlah: $jumlah,
            keterangan: 'Saldo awal koperasi',
            referensiId: null,
            tanggal: now()->format('Y-m-d'),
            userId: $userId,
        );
    }
}
