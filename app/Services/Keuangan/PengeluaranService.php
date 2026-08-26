<?php

namespace App\Services\Keuangan;

use App\Models\AuditLog;
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

            // Audit log untuk pencatatan pengeluaran
            $labelJenis = $jenis === 'koperasi' ? 'Pengeluaran Koperasi' : 'Pengeluaran Dana Sosial';
            $labelKantong = $jenis === 'koperasi' ? 'Dana Pinjaman' : 'Dana Sosial';

            AuditLog::catat(
                aksi: 'pengeluaran_dicatat',
                keterangan: "{$labelJenis} dicatat: {$keterangan}, nominal: ".number_format($jumlah, 0, ',', '.').", dari {$labelKantong}",
                dataLama: null,
                dataBaru: [
                    'pengeluaran_id' => $pengeluaran->id,
                    'jenis' => $jenis,
                    'jumlah' => $jumlah,
                    'keterangan' => $keterangan,
                    'tanggal' => $tanggal,
                    'input_by' => $userId,
                    'kantong' => $jenis === 'koperasi' ? 'pinjaman' : 'dana_sosial',
                ]
            );

            return $pengeluaran;
        });
    }
}
