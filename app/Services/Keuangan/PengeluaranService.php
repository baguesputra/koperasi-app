<?php

namespace App\Services\Keuangan;

use App\Models\Pengeluaran;
use Illuminate\Support\Facades\DB;

class PengeluaranService
{
    public function __construct(private JurnalKasService $jurnalKas) {}

    public function catat(string $jenis, float $jumlah, string $keterangan, string $tanggal, int $userId): Pengeluaran
    {
        return DB::transaction(function () use ($jenis, $jumlah, $keterangan, $tanggal, $userId) {
            $pengeluaran = Pengeluaran::create([
                'jenis' => $jenis,
                'jumlah' => $jumlah,
                'keterangan' => $keterangan,
                'tanggal' => $tanggal,
                'input_by' => $userId,
            ]);

            // Validasi saldo cukup sudah otomatis ditangani di JurnalKasService
            $this->jurnalKas->catat(
                tipe: 'keluar',
                kategori: $jenis === 'koperasi' ? 'pengeluaran_koperasi' : 'pengeluaran_dana_sosial',
                kantong: $jenis === 'koperasi' ? 'pinjaman' : 'dana_sosial',
                jumlah: $jumlah,
                keterangan: $keterangan,
                referensiId: $pengeluaran->id,
                tanggal: $tanggal,
                userId: $userId,
            );

            return $pengeluaran;
        });
    }
}