<?php

namespace App\Services\Keuangan;

use App\Models\JurnalKas;
use App\Models\KasKoperasi;

class JurnalKasService
{
    /**
     * Catat 1 baris jurnal kas, otomatis hitung saldo_setelah berdasarkan
     * saldo kantong TERKINI (harus dipanggil SETELAH saldo di kas_koperasi diupdate).
     */
    public function catat(
        string $tipe,
        string $kategori,
        string $kantong,
        float $jumlah,
        string $keterangan,
        ?int $referensiId,
        string $tanggal,
        int $userId
    ): JurnalKas {
        $kas = KasKoperasi::firstOrFail();
        $saldoSetelah = $kantong === 'pinjaman' ? $kas->saldo_pinjaman : $kas->saldo_dana_sosial;

        return JurnalKas::create([
            'tipe' => $tipe,
            'kategori' => $kategori,
            'kantong' => $kantong,
            'jumlah' => $jumlah,
            'saldo_setelah' => $saldoSetelah,
            'keterangan' => $keterangan,
            'referensi_id' => $referensiId,
            'tanggal' => $tanggal,
            'created_by' => $userId,
        ]);
    }

    /**
     * Catat saldo awal resmi sebagai baris jurnal pertama (dipakai sekali saat setup).
     */
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