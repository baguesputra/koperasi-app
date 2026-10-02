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
     * Sejak konsep Kas Tunggal, kantong virtual (pinjaman, dana_sosial,
     * simpanan, pengembalian_simpanan) hanya LABEL klasifikasi audit —
     * uang fisik hanya ada di `bank` dan `kas_kecil`.
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
     * Kategori audit/offset murni (tanpa perpindahan uang fisik). Berlaku
     * untuk baris histori (funding transit, talangan, transfer penyeimbang)
     * maupun baris baru (pelunasan resign = offset simpanan, tanpa gerak kas).
     */
    public const KATEGORI_NON_FISIK = [
        'simpanan_resign_masuk',
        'pelunasan_resign_pinjaman',
        'pelunasan_resign_simpanan',
        'talangan_sosial_ke_pinjaman',
        'talangan_simpanan_ke_pinjaman',
        'terima_talangan_dari_sosial',
        'terima_talangan_dari_simpanan',
        'kembali_talangan_dari_pinjaman',
        'kembali_talangan_ke_simpanan',
        'kembali_talangan_ke_sosial',
        'transfer_ke_dana_pinjaman',
        'terima_dari_pengembalian_simpanan',
    ];

    /**
     * Kanal fisik satu baris jurnal untuk tampilan: `bank`, `kas_kecil`, atau `audit`.
     * Urutan meniru catat(): kategori NON_FISIK tidak menggerakkan saldo dulu,
     * lalu kantong kas_kecil → kas kecil; selainnya → bank.
     */
    public static function kanalFisik(string $kantong, string $kategori): string
    {
        if (in_array($kategori, self::KATEGORI_NON_FISIK, true)) {
            return 'audit';
        }

        return $kantong === 'kas_kecil' ? 'kas_kecil' : 'bank';
    }

    /**
     * Jenis nomor bukti jurnal: non-fisik → JNK (memorial), masuk → JKM,
     * keluar → JKK. Dipakai PenomoranDokumenService (counter tahunan).
     */
    public static function jenisBukti(string $tipe, string $kategori): string
    {
        if (in_array($kategori, self::KATEGORI_NON_FISIK, true)) {
            return PenomoranDokumenService::JENIS_JNK;
        }

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
     * Konsep Kas Tunggal: hanya `saldo_bank` / `saldo_kas_kecil` yang bergerak.
     * - kantong `kas_kecil` → kas kecil; kantong lain → bank.
     * - masuk → tambah; keluar → kurang (validasi saldo cukup).
     * - KATEGORI_NON_FISIK: tulis jurnal audit saja, saldo tak tersentuh.
     *
     * @param  string  $kantong  label klasifikasi, salah satu dari self::KANTONG_SALDO
     * @param  string|null  $subJudul  catatan tambahan untuk transparansi
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

        $kolomFisik = $kantong === 'kas_kecil' ? 'saldo_kas_kecil' : 'saldo_bank';
        $nonFisik = in_array($kategori, self::KATEGORI_NON_FISIK, true);

        return DB::transaction(function () use ($tipe, $kategori, $kantong, $jumlah, $keterangan, $referensiId, $tanggal, $userId, $subJudul, $kolomFisik, $nonFisik) {
            $kas = KasKoperasi::lockForUpdate()->firstOrFail();

            if (! $nonFisik) {
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
            }

            $kas->refresh();

            return JurnalKas::create([
                'tipe' => $tipe,
                'kategori' => $kategori,
                'kantong' => $kantong,
                'no_bukti' => $this->noBuktiBerikutnya($tipe, $kategori, $tanggal),
                'jumlah' => $jumlah,
                'saldo_setelah' => $nonFisik ? 0 : $kas->{$kolomFisik},
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

    /**
     * Pool pinjaman = saldo bank saat ini (dinamis, bukan pagu bulanan tetap).
     * Struktur return dipertahankan agar pemanggil (controller/UI) tidak berubah.
     */
    public function sisaPaguBulan(?KasKoperasi $kas = null, ?string $bulan = null): array
    {
        $bulan ??= now()->format('Y-m');
        $kas ??= KasKoperasi::first();

        $bank = $kas ? (float) $kas->saldo_bank : 0.0;

        return [
            'bulan' => $bulan,
            'pagu' => $bank,
            'cadangan' => 0.0,
            'saldo_operasional' => $this->saldoOperasional($kas),
            'saldo_bank' => $bank,
            'saldo_kas_kecil' => $kas ? (float) $kas->saldo_kas_kecil : 0.0,
            'sudah_cair' => 0.0,
            'layak' => max(0.0, $bank),
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
}
