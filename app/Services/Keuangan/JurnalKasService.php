<?php

namespace App\Services\Keuangan;

use App\Models\JurnalKas;
use App\Models\KasKoperasi;
use App\Services\Dokumen\PenomoranDokumenService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class JurnalKasService
{
    /**
     * Nama kantong yang sah untuk kolom `kantong` jurnal.
     * Uang fisik hanya ada di `bank` dan `kas_kecil`: kantong `kas_kecil`
     * menggerakkan kas kecil, kantong lain (bank/pinjaman/dana_sosial/
     * simpanan/pengembalian_simpanan sebagai label klasifikasi) menggerakkan bank.
     */
    public const KANTONG_SALDO = [
        'pinjaman' => 'saldo_pinjaman',
        'dana_sosial' => 'saldo_dana_sosial',
        'pengembalian_simpanan' => 'saldo_pengembalian_simpanan',
        'simpanan' => 'saldo_simpanan',
        'bank' => 'saldo_bank',
        'kas_kecil' => 'saldo_kas_kecil',
    ];

    /**
     * Label Indonesia untuk error message dan UI.
     */
    public const KANTONG_LABEL = [
        'pinjaman' => 'Dana Pinjaman',
        'dana_sosial' => 'Dana Sosial',
        'pengembalian_simpanan' => 'Pengembalian Simpanan',
        'simpanan' => 'Simpanan Anggota',
        'bank' => 'Bank',
        'kas_kecil' => 'Kas Kecil',
    ];

    /**
     * Kategori yang dibayar dari kas kecil (kantong jurnalnya `kas_kecil`).
     */
    public const KATEGORI_KAS_KECIL = [
        'pengeluaran_koperasi',
        'pengeluaran_dana_sosial',
    ];

    /**
     * Sumber topup bank yang sah untuk kolom `sumber_topup` (hanya dipakai
     * kategori `topup_bulanan`): modal vs bunga bank (pendapatan SHU).
     */
    public const SUMBER_TOPUP = ['modal', 'bunga_bank'];

    public const SUMBER_TOPUP_LABEL = [
        'modal' => 'Modal',
        'bunga_bank' => 'Bunga Bank',
    ];

    /**
     * Kanal fisik satu baris jurnal untuk tampilan: `bank` atau `kas_kecil`.
     */
    public static function kanalFisik(string $kantong, string $kategori): string
    {
        return $kantong === 'kas_kecil' ? 'kas_kecil' : 'bank';
    }

    /**
     * Jenis nomor bukti jurnal: masuk → JKM, keluar → JKK.
     */
    public static function jenisBukti(string $tipe, string $kategori): string
    {
        return $tipe === 'masuk' ? PenomoranDokumenService::JENIS_JKM : PenomoranDokumenService::JENIS_JKK;
    }

    /**
     * Ambil no bukti berikutnya. Aman dipanggil di dalam transaksi pemanggil
     * (nested transaction → savepoint, lock counter tetap berlaku).
     */
    protected function noBuktiBerikutnya(string $tipe, string $kategori, string $tanggal): string
    {
        return app(PenomoranDokumenService::class)->berikutnya(
            self::jenisBukti($tipe, $kategori),
            Carbon::parse($tanggal)
        );
    }

    /**
     * Satu-satunya pintu untuk mengubah saldo kas + mencatat jurnal.
     * Selalu dipanggil sebagai 1 paket atomic dengan lock, supaya aman dari race condition.
     *
     * Hanya `saldo_bank` / `saldo_kas_kecil` yang bergerak.
     * - kantong `kas_kecil` → kas kecil; kantong lain → bank.
     * - masuk → tambah; keluar → kurang (validasi saldo cukup).
     *
     * @param  string  $kantong  label klasifikasi, salah satu dari self::KANTONG_SALDO
     * @param  string|null  $subJudul  catatan tambahan untuk transparansi
     * @param  string|null  $sumberTopup  'modal'|'bunga_bank' (hanya kategori topup_bulanan)
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
        ?string $subJudul = null,
        ?string $sumberTopup = null
    ): JurnalKas {
        if (! isset(self::KANTONG_SALDO[$kantong])) {
            throw new RuntimeException("Kantong '{$kantong}' tidak dikenal.");
        }

        if ($sumberTopup !== null && ! in_array($sumberTopup, self::SUMBER_TOPUP, true)) {
            throw new RuntimeException("Sumber topup '{$sumberTopup}' tidak dikenal.");
        }

        $kolomFisik = $kantong === 'kas_kecil' ? 'saldo_kas_kecil' : 'saldo_bank';

        return DB::transaction(function () use ($tipe, $kategori, $kantong, $jumlah, $keterangan, $referensiId, $tanggal, $userId, $subJudul, $sumberTopup, $kolomFisik) {
            $kas = KasKoperasi::lockForUpdate()->firstOrFail();

            if ($tipe === 'masuk') {
                $kas->increment($kolomFisik, $jumlah);
            } else {
                if ((float) $kas->{$kolomFisik} < $jumlah) {
                    $label = $kolomFisik === 'saldo_bank' ? 'Bank' : 'Kas kecil';
                    throw new RuntimeException(
                        "Saldo {$label} tidak mencukupi. Saldo saat ini: Rp ".number_format((float) $kas->{$kolomFisik}, 0, ',', '.')
                    );
                }
                $kas->decrement($kolomFisik, $jumlah);
            }

            $kas->refresh();

            return JurnalKas::create([
                'tipe' => $tipe,
                'kategori' => $kategori,
                'sumber_topup' => $sumberTopup,
                'kantong' => $kantong,
                'no_bukti' => $this->noBuktiBerikutnya($tipe, $kategori, $tanggal),
                'jumlah' => $jumlah,
                'saldo_setelah' => $kas->{$kolomFisik},
                'keterangan' => $keterangan,
                'sub_judul' => $subJudul,
                'referensi_id' => $referensiId,
                'tanggal' => $tanggal,
                'created_by' => $userId,
            ]);
        });
    }

    /**
     * Pindahkan uang fisik bank → kas kecil dalam 1 transaksi atomic.
     * Mencatat 2 jurnal: keluar dari bank + masuk ke kas kecil.
     */
    public function sisihKasKecil(
        float $jumlah,
        string $keterangan,
        string $tanggal,
        int $userId
    ): void {
        DB::transaction(function () use ($jumlah, $keterangan, $tanggal, $userId) {
            $kas = KasKoperasi::lockForUpdate()->firstOrFail();

            if ((float) $kas->saldo_bank < $jumlah) {
                throw new RuntimeException(
                    'Saldo Bank tidak mencukupi untuk sisih kas kecil. Saldo saat ini: Rp '.number_format((float) $kas->saldo_bank, 0, ',', '.')
                );
            }

            $kas->decrement('saldo_bank', $jumlah);
            $kas->increment('saldo_kas_kecil', $jumlah);
            $kas->refresh();

            JurnalKas::create([
                'tipe' => 'keluar',
                'kategori' => 'sisih_kas_kecil',
                'kantong' => 'bank',
                'no_bukti' => $this->noBuktiBerikutnya('keluar', 'sisih_kas_kecil', $tanggal),
                'jumlah' => $jumlah,
                'saldo_setelah' => $kas->saldo_bank,
                'keterangan' => $keterangan,
                'sub_judul' => 'Sisih kas kecil',
                'referensi_id' => null,
                'tanggal' => $tanggal,
                'created_by' => $userId,
            ]);

            JurnalKas::create([
                'tipe' => 'masuk',
                'kategori' => 'terima_sisih_kas_kecil',
                'kantong' => 'kas_kecil',
                'no_bukti' => $this->noBuktiBerikutnya('masuk', 'terima_sisih_kas_kecil', $tanggal),
                'jumlah' => $jumlah,
                'saldo_setelah' => $kas->saldo_kas_kecil,
                'keterangan' => $keterangan,
                'sub_judul' => 'Sisih kas kecil',
                'referensi_id' => null,
                'tanggal' => $tanggal,
                'created_by' => $userId,
            ]);
        });
    }

    /**
     * Total uang fisik koperasi: bank + kas kecil.
     */
    public function saldoOperasional(?KasKoperasi $kas = null): float
    {
        $kas ??= KasKoperasi::first();

        if (! $kas) {
            return 0.0;
        }

        return (float) $kas->saldo_bank + (float) $kas->saldo_kas_kecil;
    }
}
