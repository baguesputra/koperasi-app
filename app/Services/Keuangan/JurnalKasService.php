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
        'bank' => 'saldo_bank',
        'kas_kecil' => 'saldo_kas_kecil',
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
        'bank' => 'Bank',
        'kas_kecil' => 'Kas Kecil',
    ];

    /**
     * Kategori jurnal yang HANYA menggerakkan kantong virtual (arus kas /
     * audit, tanpa perpindahan uang fisik bank/kas kecil):
     * - Jurnal funding resign (danai transit → pelunasan → return): simpanan
     *   anggota dipakai alokasi internal, uang fisik cuma bergerak saat
     *   return benar-benar dibayar ke anggota.
     * - Talangan & pengembaliannya (hutang antar-kantong).
     * - Transfer antar-kantong penyeimbang transit.
     */
    public const KATEGORI_VIRTUAL_SAJA = [
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
     * Kategori pengeluaran kas kecil: keluar dari saldo_kas_kecil.
     * Semua pengeluaran (koperasi, dana sosial, klaim santunan) dibayar dari sini.
     */
    public const KATEGORI_KAS_KECIL = [
        'pengeluaran_koperasi',
        'pengeluaran_dana_sosial',
    ];

    /**
     * Satu-satunya pintu untuk mengubah saldo kas + mencatat jurnal.
     * Selalu dipanggil sebagai 1 paket atomic dengan lock, supaya aman dari race condition.
     *
     * Dua lapis saldo:
     * - Kantong virtual (`saldo_pinjaman` dst) SELALU di-update: untuk arus kas & audit per kantong.
     * - Fisik (`saldo_bank` / `saldo_kas_kecil`) hanya untuk kategori yang benar-benar
     *   memindahkan uang: masuk → bank, pengeluaran_* → kas kecil,
     *   pencairan/return → bank. KATEGORI_VIRTUAL_SAJA tidak menyentuh fisik.
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

        $isFisikKantong = in_array($kantong, ['bank', 'kas_kecil'], true);

        return DB::transaction(function () use ($tipe, $kategori, $kantong, $jumlah, $keterangan, $referensiId, $tanggal, $userId, $subJudul, $isFisikKantong) {
            $kas = KasKoperasi::lockForUpdate()->firstOrFail();

            $kolom = self::KANTONG_SALDO[$kantong];
            $isVirtualSaja = in_array($kategori, self::KATEGORI_VIRTUAL_SAJA, true);
            $isKasKecil = in_array($kategori, self::KATEGORI_KAS_KECIL, true);

            if ($tipe === 'keluar' && ! in_array($kantong, self::KANTONG_TRANSIT, true) && ! $isFisikKantong && (float) $kas->{$kolom} < $jumlah) {
                $labelKantong = self::KANTONG_LABEL[$kantong] ?? $kantong;
                throw new RuntimeException(
                    "Saldo {$labelKantong} tidak mencukupi. Saldo saat ini: Rp ".number_format((float) $kas->{$kolom}, 0, ',', '.')
                );
            }

            if ($isFisikKantong) {
                if ($tipe === 'masuk') {
                    $kas->increment($kolom, $jumlah);
                } else {
                    if ((float) $kas->{$kolom} < $jumlah) {
                        $label = $kantong === 'bank' ? 'Bank' : 'Kas kecil';
                        throw new RuntimeException(
                            "Saldo {$label} tidak mencukupi. Saldo saat ini: Rp ".number_format((float) $kas->{$kolom}, 0, ',', '.')
                        );
                    }
                    $kas->decrement($kolom, $jumlah);
                }
            } else {
                if (! $isVirtualSaja) {
                    if ($tipe === 'masuk') {
                        $kas->increment('saldo_bank', $jumlah);
                    } elseif ($isKasKecil) {
                        if ((float) $kas->saldo_kas_kecil < $jumlah) {
                            throw new RuntimeException(
                                'Saldo kas kecil tidak mencukupi. Saldo saat ini: Rp '.number_format((float) $kas->saldo_kas_kecil, 0, ',', '.')
                            );
                        }
                        $kas->decrement('saldo_kas_kecil', $jumlah);
                    } else {
                        if ((float) $kas->saldo_bank < $jumlah) {
                            throw new RuntimeException(
                                'Saldo bank tidak mencukupi. Saldo saat ini: Rp '.number_format((float) $kas->saldo_bank, 0, ',', '.')
                            );
                        }
                        $kas->decrement('saldo_bank', $jumlah);
                    }
                }

                if ($tipe === 'masuk') {
                    $kas->increment($kolom, $jumlah);
                } else {
                    $kas->decrement($kolom, $jumlah);
                }
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
     *
     * Dipanggil dari dalam transaksi induk (mis. cairkan) maupun mandiri:
     * deteksi level transaksi aktif agar tidak nested transaction error.
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
        $kerja = fn () => $this->prosesTransfer($kantongAsal, $kantongTujuan, $jumlah, $keterangan, $referensiId, $tanggal, $userId, $subJudul, $kategoriKeluar, $kategoriMasuk);

        // Sudah di dalam transaksi induk (cairkan): gabung, jangan nested.
        if (DB::transactionLevel() > 0) {
            return $kerja();
        }

        return DB::transaction($kerja);
    }

    private function prosesTransfer(
        string $kantongAsal,
        string $kantongTujuan,
        float $jumlah,
        string $keterangan,
        ?int $referensiId,
        string $tanggal,
        int $userId,
        ?string $subJudul,
        ?string $kategoriKeluar,
        ?string $kategoriMasuk
    ): array {
        if (! isset(self::KANTONG_SALDO[$kantongAsal]) || ! isset(self::KANTONG_SALDO[$kantongTujuan])) {
            throw new RuntimeException('Kantong asal atau tujuan tidak dikenal.');
        }

        if ($kantongAsal === $kantongTujuan) {
            throw new RuntimeException('Kantong asal dan tujuan tidak boleh sama.');
        }

        $kas = KasKoperasi::lockForUpdate()->firstOrFail();

        $kolomAsal = self::KANTONG_SALDO[$kantongAsal];
        $kolomTujuan = self::KANTONG_SALDO[$kantongTujuan];

        // Validasi fisik per baris (keluar dari asal, masuk ke tujuan)
        $fisikAsal = $this->kolomFisik('keluar', $kategoriKeluar, $kantongAsal);
        $fisikTujuan = $this->kolomFisik('masuk', $kategoriMasuk, $kantongTujuan);

        if ($fisikAsal && (float) $kas->{$fisikAsal} < $jumlah) {
            $label = array_search($fisikAsal, self::KANTONG_SALDO);
            throw new RuntimeException(
                "Saldo {$label} tidak mencukupi untuk transfer. Saldo saat ini: Rp ".number_format((float) $kas->{$fisikAsal}, 0, ',', '.')
            );
        }

        // Validasi virtual (selalu cek asal, kecuali transit)
        if (! in_array($kantongAsal, self::KANTONG_TRANSIT, true) && (float) $kas->{$kolomAsal} < $jumlah) {
            $labelAsal = self::KANTONG_LABEL[$kantongAsal];
            throw new RuntimeException(
                "Saldo {$labelAsal} tidak cukup untuk transfer. Saldo saat ini: Rp ".number_format((float) $kas->{$kolomAsal}, 0, ',', '.')
            );
        }

        // Update fisik (jika ada)
        if ($fisikAsal) {
            $kas->decrement($fisikAsal, $jumlah);
        }
        if ($fisikTujuan) {
            $kas->increment($fisikTujuan, $jumlah);
        }

        // Update virtual (selalu)
        $kas->decrement($kolomAsal, $jumlah);
        $kas->increment($kolomTujuan, $jumlah);
        $kas->refresh();

        $saldoAsalSetelah = (float) $kas->{$kolomAsal};
        $saldoTujuanSetelah = (float) $kas->{$kolomTujuan};

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
    }

    /**
     * Tentukan kolom fisik (saldo_bank / saldo_kas_kecil) yang berubah
     * untuk baris jurnal dengan (tipe, kategori, kantong).
     * Null = hanya virtual, tidak menggerakkan uang fisik.
     */
    private function kolomFisik(string $tipe, string $kategori, string $kantong): ?string
    {
        if (in_array($kantong, ['bank', 'kas_kecil'], true)) {
            // Baris yang KANTONG-nya fisik: kolom fisik sudah dijaga via mekanisme
            // update kolom kantong itu sendiri (mis. keluar kantong 'bank' → saldo_bank turun).
            return null;
        }
        if (in_array($kategori, self::KATEGORI_VIRTUAL_SAJA, true)) {
            return null;
        }
        if ($tipe === 'masuk') {
            return 'saldo_bank';
        }
        return in_array($kategori, self::KATEGORI_KAS_KECIL, true) ? 'saldo_kas_kecil' : 'saldo_bank';
    }

    /**
     * Kategori jurnal talangan & pengembaliannya.
     * Talangan = hutang kantong pinjaman ke sosial/simpanan, wajib dikembalikan
     * dari angsuran (prioritas simpanan, lalu sosial). Hak simpanan anggota
     * di tabel `simpanan` tidak tersentuh — yang bergerak hanya kas.
     */
    public const KATEGORI_TALANGAN = [
        'dana_sosial' => 'talangan_sosial_ke_pinjaman',
        'simpanan' => 'talangan_simpanan_ke_pinjaman',
    ];

    public const KATEGORI_TALANGAN_MASUK = [
        'dana_sosial' => 'terima_talangan_dari_sosial',
        'simpanan' => 'terima_talangan_dari_simpanan',
    ];

    public const KATEGORI_KEMBALI_KELUAR = 'kembali_talangan_dari_pinjaman';

    public const KATEGORI_KEMBALI = [
        'simpanan' => 'kembali_talangan_ke_simpanan',
        'dana_sosial' => 'kembali_talangan_ke_sosial',
    ];

    /**
     * Cover defisit kantong pinjaman dari sosial lalu simpanan.
     * Wajib dipanggil di dalam transaksi induk yang sudah lock kas
     * (mis. cairkan) agar atomic dengan pencairan.
     *
     * @return array[] rincian ['kantong' => ..., 'jumlah' => ...]
     */
    public function talangiPinjaman(float $defisit, string $keterangan, ?int $referensiId, string $tanggal, int $userId): array
    {
        $rincian = [];

        foreach (['dana_sosial', 'simpanan'] as $sumber) {
            if ($defisit <= 0) {
                break;
            }

            $kas = KasKoperasi::lockForUpdate()->firstOrFail();
            $kolom = self::KANTONG_SALDO[$sumber];
            $ambil = min((float) $kas->{$kolom}, $defisit);

            if ($ambil <= 0) {
                continue;
            }

            $this->transferAntarKantong(
                $sumber,
                'pinjaman',
                $ambil,
                $keterangan,
                $referensiId,
                $tanggal,
                $userId,
                'Talangan, dikembalikan dari angsuran',
                self::KATEGORI_TALANGAN[$sumber],
                self::KATEGORI_TALANGAN_MASUK[$sumber],
            );

            $rincian[] = ['kantong' => $sumber, 'jumlah' => $ambil];
            $defisit -= $ambil;
        }

        if ($defisit > 0) {
            throw new RuntimeException(
                'Dana talangan tidak mencukupi. Kurang: Rp '.number_format($defisit, 0, ',', '.')
            );
        }

        return $rincian;
    }

    /**
     * Utang talangan terbuka per kantong sumber = total talangan keluar
     * dikurangi total pengembalian masuk. Dihitung dari jurnal (bukan state
     * memory) agar tidak miss bila proses terputus di tengah.
     *
     * @return array{dana_sosial: float, simpanan: float}
     */
    public function utangTalanganTerbuka(): array
    {
        $keluar = JurnalKas::whereIn('kategori', array_values(self::KATEGORI_TALANGAN))
            ->selectRaw('kantong, SUM(jumlah) as total')
            ->groupBy('kantong')
            ->pluck('total', 'kantong');

        $masuk = JurnalKas::whereIn('kategori', array_values(self::KATEGORI_KEMBALI))
            ->selectRaw('kantong, SUM(jumlah) as total')
            ->groupBy('kantong')
            ->pluck('total', 'kantong');

        return [
            'dana_sosial' => max(0.0, (float) ($keluar['dana_sosial'] ?? 0) - (float) ($masuk['dana_sosial'] ?? 0)),
            'simpanan' => max(0.0, (float) ($keluar['simpanan'] ?? 0) - (float) ($masuk['simpanan'] ?? 0)),
        ];
    }

    /**
     * Kembalikan talangan dari kas pinjaman ke simpanan dulu lalu sosial,
     * sebesar min(utang terbuka, saldo pinjaman tersedia). Idempoten:
     * tanpa utang terbuka = no-op. Dipanggil setelah jurnal angsuran masuk
     * agar urutan jurnal kronologis benar.
     *
     * @return array[] rincian ['kantong' => ..., 'jumlah' => ...]
     */
    public function kembalikanTalangan(string $keterangan, ?int $referensiId, string $tanggal, int $userId): array
    {
        $rincian = [];

        foreach (['simpanan', 'dana_sosial'] as $tujuan) {
            $utang = $this->utangTalanganTerbuka()[$tujuan] ?? 0.0;

            if ($utang <= 0) {
                continue;
            }

            $kas = KasKoperasi::lockForUpdate()->firstOrFail();
            $kembali = min($utang, (float) $kas->saldo_pinjaman);

            if ($kembali <= 0) {
                continue;
            }

            $this->transferAntarKantong(
                'pinjaman',
                $tujuan,
                $kembali,
                $keterangan,
                $referensiId,
                $tanggal,
                $userId,
                'Pengembalian talangan dari angsuran',
                self::KATEGORI_KEMBALI_KELUAR,
                self::KATEGORI_KEMBALI[$tujuan],
            );

            $rincian[] = ['kantong' => $tujuan, 'jumlah' => $kembali];
        }

        return $rincian;
    }

    /**
     * Total uang fisik koperasi: bank + kas kecil (harus = Σ kantong virtual).
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
