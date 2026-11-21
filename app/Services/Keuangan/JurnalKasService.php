<?php

namespace App\Services\Keuangan;

use App\Models\AuditLog;
use App\Models\JurnalKas;
use App\Models\KasKoperasi;
use App\Models\Pengeluaran;
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
     * Kategori operasional Bendahara yang boleh dibatalkan lewat jurnal pembalik (storno).
     */
    public const KATEGORI_BISA_DIBATALKAN = [
        'topup_bulanan',
        'pengeluaran_koperasi',
        'pengeluaran_dana_sosial',
        'sisih_kas_kecil',
        'terima_sisih_kas_kecil',
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
                    'Saldo Bank tidak mencukupi untuk transfer dana. Saldo saat ini: Rp '.number_format((float) $kas->saldo_bank, 0, ',', '.')
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
                'sub_judul' => 'Transfer dana',
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
                'sub_judul' => 'Transfer dana',
                'referensi_id' => null,
                'tanggal' => $tanggal,
                'created_by' => $userId,
            ]);
        });
    }

    /**
     * Batalkan transaksi kas operasional dengan menerbitkan jurnal pembalik (storno)
     * secara atomik tanpa menghapus nomor bukti lama.
     */
    public function batalkan(JurnalKas $jurnalAsal, string $alasan, int $userId): JurnalKas
    {
        $alasan = trim($alasan);

        return DB::transaction(function () use ($jurnalAsal, $alasan, $userId) {
            $kas = KasKoperasi::lockForUpdate()->firstOrFail();
            $asal = JurnalKas::lockForUpdate()->findOrFail($jurnalAsal->id);

            $this->validasiBisaDibatalkan($asal);

            $jumlah = (float) $asal->jumlah;

            if (in_array($asal->kategori, ['sisih_kas_kecil', 'terima_sisih_kas_kecil'], true)) {
                $pasangan = $this->cariPasanganSisih($asal);
                if (! $pasangan) {
                    throw new RuntimeException('Pasangan jurnal transfer dana tidak ditemukan.');
                }

                if ((float) $kas->saldo_kas_kecil < $jumlah) {
                    throw new RuntimeException(
                        'Saldo Kas kecil tidak mencukupi untuk membatalkan transfer dana. Saldo saat ini: Rp '.number_format((float) $kas->saldo_kas_kecil, 0, ',', '.')
                    );
                }

                $kas->decrement('saldo_kas_kecil', $jumlah);
                $kas->increment('saldo_bank', $jumlah);
                $kas->refresh();

                $barisKasKecil = $asal->kategori === 'terima_sisih_kas_kecil' ? $asal : $pasangan;
                $barisBank = $asal->kategori === 'sisih_kas_kecil' ? $asal : $pasangan;

                $pembalikKasKecil = $this->buatBarisPembalik($barisKasKecil, 'keluar', $alasan, $userId);
                $pembalikBank = $this->buatBarisPembalik($barisBank, 'masuk', $alasan, $userId);

                $this->tandaiDibatalkan($asal, $alasan, $userId);
                $this->tandaiDibatalkan($pasangan, $alasan, $userId);

                $pembalikUtama = $asal->id === $barisKasKecil->id ? $pembalikKasKecil : $pembalikBank;
                $dataBaruEkstra = [
                    'pasangan_id' => $pasangan->id,
                    'jurnal_pembalik_kas_kecil_id' => $pembalikKasKecil->id,
                    'jurnal_pembalik_bank_id' => $pembalikBank->id,
                ];
            } else {
                $pembalikUtama = $this->eksekusiPembalikTunggal($kas, $asal, $alasan, $userId);

                if (in_array($asal->kategori, ['pengeluaran_koperasi', 'pengeluaran_dana_sosial'], true) && $asal->referensi_id) {
                    Pengeluaran::where('id', $asal->referensi_id)->update(['dibatalkan_pada' => now()]);
                }

                $dataBaruEkstra = [];
            }

            $noBuktiAsal = $asal->no_bukti ?? ('#'.$asal->id);

            AuditLog::catat(
                aksi: 'kas_dibatalkan',
                keterangan: "Pembatalan transaksi kas ({$noBuktiAsal}): {$alasan}",
                dataLama: [
                    'jurnal_kas_id' => $asal->id,
                    'no_bukti' => $asal->no_bukti,
                    'kategori' => $asal->kategori,
                    'kantong' => $asal->kantong,
                    'tipe' => $asal->tipe,
                    'jumlah' => $jumlah,
                    'tanggal' => $asal->tanggal->toDateString(),
                    'referensi_id' => $asal->referensi_id,
                ],
                dataBaru: array_merge([
                    'jurnal_pembalik_id' => $pembalikUtama->id,
                    'no_bukti_pembalik' => $pembalikUtama->no_bukti,
                    'alasan_batal' => $alasan,
                ], $dataBaruEkstra),
                userId: $userId,
            );

            return $pembalikUtama;
        });
    }

    /**
     * Membalik satu baris jurnal di dalam transaksi DB pemanggil (dipakai modul Simpanan / Angsuran).
     */
    public function batalkanBarisInternal(JurnalKas $asal, string $alasan, int $userId): JurnalKas
    {
        $alasan = trim($alasan);
        $kas = KasKoperasi::lockForUpdate()->firstOrFail();
        $baris = JurnalKas::lockForUpdate()->findOrFail($asal->id);

        if ($baris->dibatalkan_pada !== null) {
            throw new RuntimeException('Jurnal kas ini sudah pernah dibatalkan.');
        }

        if ($baris->koreksi_dari_id !== null) {
            throw new RuntimeException('Jurnal pembalik (storno) tidak dapat dibatalkan lagi.');
        }

        return $this->eksekusiPembalikTunggal($kas, $baris, $alasan, $userId);
    }

    private function eksekusiPembalikTunggal(KasKoperasi $kas, JurnalKas $baris, string $alasan, int $userId): JurnalKas
    {
        $jumlah = (float) $baris->jumlah;
        $tipePembalik = $baris->tipe === 'masuk' ? 'keluar' : 'masuk';
        $kolomFisik = $baris->kantong === 'kas_kecil' ? 'saldo_kas_kecil' : 'saldo_bank';

        if ($tipePembalik === 'keluar' && (float) $kas->{$kolomFisik} < $jumlah) {
            $label = $kolomFisik === 'saldo_bank' ? 'Bank' : 'Kas kecil';
            throw new RuntimeException(
                "Saldo {$label} tidak mencukupi untuk membatalkan transaksi ini. Saldo saat ini: Rp ".number_format((float) $kas->{$kolomFisik}, 0, ',', '.')
            );
        }

        if ($tipePembalik === 'masuk') {
            $kas->increment($kolomFisik, $jumlah);
        } else {
            $kas->decrement($kolomFisik, $jumlah);
        }
        $kas->refresh();

        $pembalik = $this->buatBarisPembalik($baris, $tipePembalik, $alasan, $userId);
        $this->tandaiDibatalkan($baris, $alasan, $userId);

        return $pembalik;
    }

    private function validasiBisaDibatalkan(JurnalKas $asal): void
    {
        if ($asal->dibatalkan_pada !== null) {
            throw new RuntimeException('Transaksi ini sudah pernah dibatalkan.');
        }

        if ($asal->koreksi_dari_id !== null) {
            throw new RuntimeException('Jurnal pembalik (storno) tidak dapat dibatalkan lagi.');
        }

        if (! in_array($asal->kategori, self::KATEGORI_BISA_DIBATALKAN, true)) {
            throw new RuntimeException('Kategori transaksi ini tidak dapat dibatalkan dari menu Kas Koperasi.');
        }
    }

    private function buatBarisPembalik(JurnalKas $barisAsal, string $tipePembalik, string $alasan, int $userId): JurnalKas
    {
        $kanal = self::kanalFisik($barisAsal->kantong, $barisAsal->kategori);
        $tanggalAsal = $barisAsal->tanggal->toDateString();
        $batasAkhirHari = $barisAsal->tanggal->copy()->endOfDay();
        $jumlah = (float) $barisAsal->jumlah;
        $delta = $tipePembalik === 'masuk' ? $jumlah : -$jumlah;

        $saldoSebelum = (float) ($this->queryKanalFisik($kanal)
            ->where('tanggal', '<=', $batasAkhirHari)
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->value('saldo_setelah') ?? 0);

        $this->queryKanalFisik($kanal)
            ->where('tanggal', '>', $batasAkhirHari)
            ->update(['saldo_setelah' => DB::raw("saldo_setelah + ({$delta})")]);

        $refBukti = $barisAsal->no_bukti ?? ('#'.$barisAsal->id);

        return JurnalKas::create([
            'tipe' => $tipePembalik,
            'kategori' => $barisAsal->kategori,
            'sumber_topup' => $barisAsal->sumber_topup,
            'kantong' => $barisAsal->kantong,
            'no_bukti' => $this->noBuktiBerikutnya($tipePembalik, $barisAsal->kategori, $tanggalAsal),
            'jumlah' => $jumlah,
            'saldo_setelah' => $saldoSebelum + $delta,
            'keterangan' => "[PEMBALIK {$refBukti}] {$alasan}",
            'sub_judul' => 'Jurnal Pembalik (Storno)',
            'referensi_id' => $barisAsal->referensi_id,
            'tanggal' => $tanggalAsal,
            'created_by' => $userId,
            'koreksi_dari_id' => $barisAsal->id,
        ]);
    }

    private function queryKanalFisik(string $kanal)
    {
        return JurnalKas::query()->when(
            $kanal === 'kas_kecil',
            fn ($q) => $q->where('kantong', 'kas_kecil'),
            fn ($q) => $q->where('kantong', '!=', 'kas_kecil')
        );
    }

    private function tandaiDibatalkan(JurnalKas $baris, string $alasan, int $userId): void
    {
        $baris->update([
            'dibatalkan_pada' => now(),
            'dibatalkan_oleh' => $userId,
            'alasan_batal' => $alasan,
        ]);
    }

    private function cariPasanganSisih(JurnalKas $asal): ?JurnalKas
    {
        $kategoriPasangan = $asal->kategori === 'sisih_kas_kecil'
            ? 'terima_sisih_kas_kecil'
            : 'sisih_kas_kecil';
        $prioritasId = $asal->kategori === 'sisih_kas_kecil'
            ? 'CASE WHEN id > ? THEN 0 ELSE 1 END'
            : 'CASE WHEN id < ? THEN 0 ELSE 1 END';

        return JurnalKas::lockForUpdate()
            ->where('kategori', $kategoriPasangan)
            ->where('jumlah', $asal->jumlah)
            ->whereBetween('tanggal', [
                $asal->tanggal->copy()->startOfDay(),
                $asal->tanggal->copy()->endOfDay(),
            ])
            ->whereNull('dibatalkan_pada')
            ->whereNull('koreksi_dari_id')
            ->orderByRaw("{$prioritasId}, ABS(id - ?) ASC", [$asal->id, $asal->id])
            ->first();
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

    /**
     * Saldo dana sosial live: neto kantong `dana_sosial` di `jurnal_kas`
     * (masuk − keluar, mengecualikan baris dibatalkan & jurnal pembalik storno),
     * selaras dengan Laporan Neraca & Rekap Dana Sosial.
     * Fallback ke kolom `kas_koperasi.saldo_dana_sosial` bila belum ada jurnal dana sosial.
     */
    public function saldoDanaSosial(?KasKoperasi $kas = null): float
    {
        $agregat = JurnalKas::where('kantong', 'dana_sosial')
            ->selectRaw("
                COUNT(*) as semua_baris,
                COALESCE(SUM(CASE
                    WHEN dibatalkan_pada IS NULL AND koreksi_dari_id IS NULL AND tipe = 'masuk' THEN jumlah
                    WHEN dibatalkan_pada IS NULL AND koreksi_dari_id IS NULL THEN -jumlah
                    ELSE 0
                END), 0) as total
            ")
            ->first();

        if ((int) ($agregat->semua_baris ?? 0) > 0) {
            return (float) $agregat->total;
        }

        $kas ??= KasKoperasi::first();

        return (float) ($kas?->saldo_dana_sosial ?? 0);
    }
}
