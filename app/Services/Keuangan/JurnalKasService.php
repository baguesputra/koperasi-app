<?php

namespace App\Services\Keuangan;

use App\Models\JurnalKas;
use App\Models\KasKoperasi;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class JurnalKasService
{
    /**
     * Mapping nama kantong (string) ke kolom saldo di tabel kas_koperasi.
     * Extend di sini kalau ada kantong baru.
     */
    public const KANTONG_SALDO = [
        'pinjaman' => 'saldo_pinjaman',
        'dana_sosial' => 'saldo_dana_sosial',
        'pengembalian_simpanan' => 'saldo_pengembalian_simpanan',
    ];

    /**
     * Label Indonesia untuk error message dan UI.
     */
    public const KANTONG_LABEL = [
        'pinjaman' => 'Dana Pinjaman',
        'dana_sosial' => 'Dana Sosial',
        'pengembalian_simpanan' => 'Pengembalian Simpanan',
    ];

    /**
     * Satu-satunya pintu untuk mengubah saldo kas + mencatat jurnal.
     * Selalu dipanggil sebagai 1 paket atomic dengan lock, supaya aman dari race condition.
     *
     * @param  string  $kantong  salah satu dari self::KANTONG_SALDO
     * @param  string|null  $subJudul  catatan tambahan untuk transparansi
     *                                              (mis. "Diambil dari simpanan anggota")
     */
    public function catat(
        string $tipe,
        string $kategori,
        string $kantong,
        float $jumlah,
        string $keterangan,
        ?int $referensiId,
        string $tanggal,
        int $userId,
        ?string $subJudul = null
    ): JurnalKas {
        if (! isset(self::KANTONG_SALDO[$kantong])) {
            throw new RuntimeException("Kantong '{$kantong}' tidak dikenal.");
        }

        return DB::transaction(function () use ($tipe, $kategori, $kantong, $jumlah, $keterangan, $referensiId, $tanggal, $userId, $subJudul) {
            // Lock baris kas_koperasi - proses lain harus antre sampai transaksi ini selesai
            $kas = KasKoperasi::lockForUpdate()->firstOrFail();

            $kolom = self::KANTONG_SALDO[$kantong];

            if ($tipe === 'keluar' && (float) $kas->{$kolom} < $jumlah) {
                $labelKantong = self::KANTONG_LABEL[$kantong];
                throw new RuntimeException(
                    "Saldo {$labelKantong} tidak mencukupi. Saldo saat ini: Rp ".number_format((float) $kas->{$kolom}, 0, ',', '.')
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
                'sub_judul' => $subJudul,
                'referensi_id' => $referensiId,
                'tanggal' => $tanggal,
                'created_by' => $userId,
            ]);
        });
    }

    /**
     * Transfer saldo antar-kantong dalam 1 transaksi atomic.
     * Mencatat 2 jurnal: keluar dari kantongAsal + masuk ke kantongTujuan.
     * Saldo_koperasi di-lock supaya konsisten.
     */
    public function transferAntarKantong(
        string $kantongAsal,
        string $kantongTujuan,
        float $jumlah,
        string $keterangan,
        ?int $referensiId,
        string $tanggal,
        int $userId,
        ?string $subJudul = null,
        ?string $kategoriKeluar = 'transfer_ke_dana_pinjaman',
        ?string $kategoriMasuk = 'terima_dari_pengembalian_simpanan'
    ): array {
        return DB::transaction(function () use ($kantongAsal, $kantongTujuan, $jumlah, $keterangan, $referensiId, $tanggal, $userId, $subJudul, $kategoriKeluar, $kategoriMasuk) {
            if (! isset(self::KANTONG_SALDO[$kantongAsal]) || ! isset(self::KANTONG_SALDO[$kantongTujuan])) {
                throw new RuntimeException('Kantong asal atau tujuan tidak dikenal.');
            }

            if ($kantongAsal === $kantongTujuan) {
                throw new RuntimeException('Kantong asal dan tujuan tidak boleh sama.');
            }

            $kas = KasKoperasi::lockForUpdate()->firstOrFail();

            $kolomAsal = self::KANTONG_SALDO[$kantongAsal];
            $kolomTujuan = self::KANTONG_SALDO[$kantongTujuan];

            if ((float) $kas->{$kolomAsal} < $jumlah) {
                $labelAsal = self::KANTONG_LABEL[$kantongAsal];
                throw new RuntimeException(
                    "Saldo {$labelAsal} tidak cukup untuk transfer. Saldo saat ini: Rp ".number_format((float) $kas->{$kolomAsal}, 0, ',', '.')
                );
            }

            // Update kedua saldo
            $kas->decrement($kolomAsal, $jumlah);
            $kas->increment($kolomTujuan, $jumlah);
            $kas->refresh();

            $saldoAsalSetelah = (float) $kas->{$kolomAsal};
            $saldoTujuanSetelah = (float) $kas->{$kolomTujuan};

            // Jurnal keluar dari kantong asal
            $jurnalKeluar = JurnalKas::create([
                'tipe' => 'keluar',
                'kategori' => $kategoriKeluar,
                'kantong' => $kantongAsal,
                'jumlah' => $jumlah,
                'saldo_setelah' => $saldoAsalSetelah,
                'keterangan' => $keterangan,
                'sub_judul' => $subJudul,
                'referensi_id' => $referensiId,
                'tanggal' => $tanggal,
                'created_by' => $userId,
            ]);

            // Jurnal masuk ke kantong tujuan
            $jurnalMasuk = JurnalKas::create([
                'tipe' => 'masuk',
                'kategori' => $kategoriMasuk,
                'kantong' => $kantongTujuan,
                'jumlah' => $jumlah,
                'saldo_setelah' => $saldoTujuanSetelah,
                'keterangan' => $keterangan,
                'sub_judul' => $subJudul,
                'referensi_id' => $referensiId,
                'tanggal' => $tanggal,
                'created_by' => $userId,
            ]);

            return [
                'keluar' => $jurnalKeluar,
                'masuk' => $jurnalMasuk,
            ];
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
