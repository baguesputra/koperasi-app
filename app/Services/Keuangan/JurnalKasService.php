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
        'simpanan' => 'saldo_simpanan',
    ];

    /**
     * Kantong transit: diisi di awal proses & dikuras sampai 0.
     * Saldo TIDAK divalidasi di jurnal individual karena validasi dilakukan
     * di level proses (lihat ResignService::proses()).
     */
    public const KANTONG_TRANSIT = ['pengembalian_simpanan'];

    /**
     * Label Indonesia untuk error message dan UI.
     */
    public const KANTONG_LABEL = [
        'pinjaman' => 'Dana Pinjaman',
        'dana_sosial' => 'Dana Sosial',
        'pengembalian_simpanan' => 'Pengembalian Simpanan',
        'simpanan' => 'Simpanan Anggota',
    ];

    /**
     * Satu-satunya pintu untuk mengubah saldo kas + mencatat jurnal.
     * Selalu dipanggil sebagai 1 paket atomic dengan lock, supaya aman dari race condition.
     *
     * @param  string  $kantong  salah satu dari self::KANTONG_SALDO
     * @param  string|null  $subJudul  catatan tambahan untuk transparansi
     *                                 (mis. "Diambil dari simpanan anggota")
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

            if ($tipe === 'keluar' && ! in_array($kantong, self::KANTONG_TRANSIT, true) && (float) $kas->{$kolom} < $jumlah) {
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

    /**
     * Saldo operasional gabungan (Opsi B): pinjaman + dana sosial + simpanan.
     * Transit pengembalian_simpanan tidak ikut (dalam proses, kembali ke 0).
     */
    public function saldoOperasional(?KasKoperasi $kas = null): float
    {
        $kas ??= KasKoperasi::first();

        if (! $kas) {
            return 0.0;
        }

        return (float) $kas->saldo_pinjaman + (float) $kas->saldo_dana_sosial + (float) $kas->saldo_simpanan;
    }

    /**
     * Sisa pagu pinjaman bulan kalender berjalan:
     * layak = saldo_operasional − cadangan − sudah_cair_bulan_ini.
     */
    public function sisaPaguBulan(?KasKoperasi $kas = null, ?string $bulan = null): array
    {
        $bulan ??= now()->format('Y-m');
        [$tahun, $bln] = explode('-', $bulan);

        $saldo = $this->saldoOperasional($kas);
        $cadangan = $this->nilaiSettingKas(
            \App\Models\SettingKas::CADANGAN,
            (float) config('koperasi.cadangan_sosial_bulan', 5_000_000)
        );
        $pagu = $this->nilaiSettingKas(
            \App\Models\SettingKas::PAGU,
            (float) config('koperasi.pagu_pinjaman_bulanan', 50_000_000)
        );

        $sudahCair = (float) JurnalKas::where('kategori', 'pencairan_pinjaman')
            ->where('tipe', 'keluar')
            ->whereYear('tanggal', $tahun)
            ->whereMonth('tanggal', $bln)
            ->sum('jumlah');

        $layak = min($pagu - $sudahCair, $saldo - $cadangan - $sudahCair);
        $layak = max(0.0, $layak);

        return [
            'bulan' => $bulan,
            'pagu' => $pagu,
            'cadangan' => $cadangan,
            'saldo_operasional' => $saldo,
            'sudah_cair' => $sudahCair,
            'layak' => $layak,
        ];
    }

    /**
     * Klasifikasi operasional bulan berjalan dari jurnal: pinjaman keluar
     * vs arus iuran (masuk: simpanan + dana sosial; keluar: pengembalian
     * ke anggota + pelunasan dari simpanan). Untuk transparansi UI/laporan.
     */
    public function klasifikasiBulan(?string $bulan = null): array
    {
        $bulan ??= now()->format('Y-m');
        [$tahun, $bln] = explode('-', $bulan);

        $keluarPinjaman = (float) JurnalKas::where('kategori', 'pencairan_pinjaman')
            ->where('tipe', 'keluar')
            ->whereYear('tanggal', $tahun)
            ->whereMonth('tanggal', $bln)
            ->sum('jumlah');

        $masukIuran = (float) JurnalKas::whereIn('kategori', ['simpanan_pokok_masuk', 'simpanan_wajib_masuk', 'dana_sosial_bulanan'])
            ->where('tipe', 'masuk')
            ->whereYear('tanggal', $tahun)
            ->whereMonth('tanggal', $bln)
            ->sum('jumlah');

        $keluarIuran = (float) JurnalKas::whereIn('kategori', ['return_simpanan_pokok', 'return_simpanan_wajib', 'pelunasan_resign_simpanan'])
            ->where('tipe', 'keluar')
            ->whereYear('tanggal', $tahun)
            ->whereMonth('tanggal', $bln)
            ->sum('jumlah');

        return [
            'bulan' => $bulan,
            'pinjaman_keluar' => $keluarPinjaman,
            'iuran_masuk' => $masukIuran,
            'iuran_keluar' => $keluarIuran,
            'iuran_bersih' => $masukIuran - $keluarIuran,
        ];
    }

    private function nilaiSettingKas(string $kunci, float $default): float
    {
        try {
            return \App\Models\SettingKas::nilai($kunci, $default);
        } catch (\Throwable) {
            // Tabel belum termigrasi (mis. environment lama): pakai default config.
            return $default;
        }
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
