<?php

namespace App\Services\Keuangan;

use App\Models\AuditLog;
use App\Models\Pengeluaran;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PengeluaranService
{
    public function __construct(private JurnalKasService $jurnalKas) {}

    public function catat(string $jenis, float $jumlah, string $keterangan, string $tanggal, int $userId, string $tipe = 'lain_lain', string $akun = 'kas_kecil'): Pengeluaran
    {
        if (! in_array($akun, Pengeluaran::AKUN, true)) {
            throw new RuntimeException("Sumber dana '{$akun}' tidak dikenal.");
        }

        return DB::transaction(function () use ($jenis, $jumlah, $keterangan, $tanggal, $userId, $tipe, $akun) {
            $pengeluaran = Pengeluaran::create([
                'jenis' => $jenis,
                'tipe' => $tipe,
                'akun' => $akun,
                'jumlah' => $jumlah,
                'keterangan' => $keterangan,
                'tanggal' => $tanggal,
                'input_by' => $userId,
            ]);

            $this->jurnalKas->catat(
                tipe: 'keluar',
                kategori: $jenis === 'koperasi' ? 'pengeluaran_koperasi' : 'pengeluaran_dana_sosial',
                kantong: $akun,
                jumlah: $jumlah,
                keterangan: $keterangan,
                referensiId: $pengeluaran->id,
                tanggal: $tanggal,
                userId: $userId,
            );

            // Audit log untuk pencatatan pengeluaran
            $labelJenis = $jenis === 'koperasi' ? 'Pengeluaran Koperasi' : 'Pengeluaran Dana Sosial';
            $labelTipe = Pengeluaran::TIPE_LABEL[$tipe] ?? $tipe;
            $labelAkun = Pengeluaran::AKUN_LABEL[$akun] ?? $akun;

            AuditLog::catat(
                aksi: 'pengeluaran_dicatat',
                keterangan: "{$labelJenis} ({$labelTipe}) dicatat: {$keterangan}, nominal: ".number_format($jumlah, 0, ',', '.').", dari {$labelAkun}",
                dataLama: null,
                dataBaru: [
                    'pengeluaran_id' => $pengeluaran->id,
                    'jenis' => $jenis,
                    'tipe' => $tipe,
                    'akun' => $akun,
                    'jumlah' => $jumlah,
                    'keterangan' => $keterangan,
                    'tanggal' => $tanggal,
                    'input_by' => $userId,
                    'kantong' => $akun,
                ]
            );

            return $pengeluaran;
        });
    }
}