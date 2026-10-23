<?php

namespace App\Services\Pinjaman;

use App\Models\Anggota;
use App\Models\SettingLimitPinjaman;
use App\Models\TabelTenor;

class EligibilitasPinjamanService
{
    /** Memo seluruh setting limit terurut selama request (ganti query berulang). */
    private static ?array $semuaLimitCache = null;

    /** Memo tabel tenor selama request (ganti query berulang per pinjaman). */
    private static ?array $tabelTenorCache = null;

    public static function resetCache(): void
    {
        self::$semuaLimitCache = null;
        self::$tabelTenorCache = null;
    }

    /** Semua baris tabel tenor terurut nominal_min menaik. */
    private function semuaTenor(): array
    {
        if (self::$tabelTenorCache === null) {
            self::$tabelTenorCache = TabelTenor::orderBy('nominal_min')->get()->all();
        }

        return self::$tabelTenorCache;
    }

    /** Semua baris setting limit terurut min_bulan menaik (null dianggap 0). */
    private function semuaLimit(): array
    {
        if (self::$semuaLimitCache === null) {
            self::$semuaLimitCache = SettingLimitPinjaman::orderBy('min_bulan')->get()->all();
        }

        return self::$semuaLimitCache;
    }

    /**
     * Cek apakah anggota boleh mengajukan pinjaman baru.
     * Return array:
     *   - boleh: bool
     *   - alasan: string|null
     *   - limit_tersedia: float (0 selama terkunci; rumus berjalan saat terbuka;
     *                            full limit jika tidak ada pinjaman aktif)
     *   - sisa_angsuran: int (0 jika tidak ada pinjaman aktif; gabungan semua pinjaman aktif)
     *   - cicilan_pokok: float (0 jika tidak ada angsuran belum_bayar; weighted avg dari semua)
     *
     * Aturan:
     *   - Tidak ada pinjaman aktif -> boleh (limit penuh)
     *   - Sisa > 2 angsuran -> terkunci (limit 0, wajib melunasi hingga sisa <= 2)
     *   - Sisa <= 2, anggota < 1 tahun -> tolak (wajib lunas)
     *   - Sisa <= 2, salah satu flag reloan sudah dipakai -> tolak (harus lunas dulu)
     *   - Sisa <= 2, semua flag reloan belum dipakai -> boleh (limit dikurangi rumus berjalan)
     */
    public function cek(Anggota $anggota): array
    {
        // Anggota nonaktif / resign / menunggu tidak boleh mengajukan pinjaman.
        if ($anggota->status !== 'aktif') {
            $alasan = $anggota->status === 'resign_menunggu'
                ? 'Penyelesaian resign Anda menunggu pelunasan cicilan akhir. Pengajuan baru dibuka setelah lunas.'
                : "Anggota berstatus '{$anggota->status}', tidak dapat mengajukan pinjaman.";

            return [
                'boleh' => false,
                'alasan' => $alasan,
                'limit_tersedia' => 0.0,
                'sisa_angsuran' => 0,
                'cicilan_pokok' => 0.0,
            ];
        }

        $agg = $anggota->pinjamanAktifDenganAgregat();

        // Belum punya pinjaman aktif sama sekali -> pasti boleh
        if ($agg === null) {
            $limit = $this->limitMaksimal($anggota);

            return [
                'boleh' => true,
                'alasan' => null,
                'limit_tersedia' => $limit,
                'sisa_angsuran' => 0,
                'cicilan_pokok' => 0.0,
            ];
        }

        $sisaAngsuran = (int) $agg['sisa_total'];
        $cicilanPokok = (float) $agg['cicilan_pokok_weighted_avg'];

        // Kuncian: angsuran yang dibayar tidak langsung membuka limit.
        // Baru terbuka saat tersisa <= 2 angsuran; lunas penuh = limit awal pulih.
        if ($sisaAngsuran > 2) {
            return [
                'boleh' => false,
                'alasan' => 'Pinjaman sebelumnya belum lunas. Selesaikan pembayaran hingga tersisa maksimal 2 angsuran untuk membuka pengajuan berikutnya.',
                'limit_tersedia' => 0.0,
                'sisa_angsuran' => $sisaAngsuran,
                'cicilan_pokok' => $cicilanPokok,
            ];
        }

        $limitMaksimal = $this->limitMaksimal($anggota);
        $limitTersedia = max(0.0, $limitMaksimal - ($sisaAngsuran * $cicilanPokok));

        // Anggota < 1 tahun: wajib lunas dulu, tidak dapat privilege reloan
        if ($this->isAnggotaBaru($anggota)) {
            $result = $sisaAngsuran === 0
                ? ['boleh' => true, 'alasan' => null]
                : ['boleh' => false, 'alasan' => 'Pinjaman sebelumnya belum lunas. Anggota baru (< 1 tahun) wajib melunasi pinjaman sebelum mengajukan pinjaman baru.'];

            return $result + [
                'limit_tersedia' => $limitTersedia,
                'sisa_angsuran' => $sisaAngsuran,
                'cicilan_pokok' => $cicilanPokok,
            ];
        }

        // Reloan privilege: 1x per siklus. Cek flag di SEMUA pinjaman aktif.
        // Jika salah satu sudah true, anggota tidak boleh ajukan lagi sampai pinjaman-pinjaman tsb lunas.
        $privilegeSudahDipakai = $agg['pinjaman_aktif_list']->contains(
            'sudah_pakai_privilege_reloan',
            true
        );

        if ($privilegeSudahDipakai) {
            return [
                'boleh' => false,
                'alasan' => 'Anda sudah menggunakan hak pengajuan lebih awal untuk pinjaman ini. Selesaikan pelunasan terlebih dahulu sebelum mengajukan pinjaman baru.',
                'limit_tersedia' => $limitTersedia,
                'sisa_angsuran' => $sisaAngsuran,
                'cicilan_pokok' => $cicilanPokok,
            ];
        }

        return [
            'boleh' => true,
            'alasan' => null,
            'limit_tersedia' => $limitTersedia,
            'sisa_angsuran' => $sisaAngsuran,
            'cicilan_pokok' => $cicilanPokok,
        ];
    }

    /**
     * Hitung limit maksimal nominal pinjaman untuk anggota ini.
     * Rentang masa keanggotaan (min_bulan–maks_bulan, maks null = tanpa batas)
     * dibaca dari setting_limit_pinjaman agar bisa diubah admin.
     */
    public function limitMaksimal(Anggota $anggota): float
    {
        if ($anggota->limit_custom !== null) {
            return (float) $anggota->limit_custom;
        }

        $bulan = (int) floor($anggota->lama_keanggotaan_tahun * 12);

        foreach ($this->semuaLimit() as $setting) {
            $min = (int) ($setting->min_bulan ?? 0);
            $maks = $setting->maks_bulan === null ? null : (int) $setting->maks_bulan;

            if ($bulan >= $min && ($maks === null || $bulan <= $maks)) {
                return (float) ($setting->limit_maksimal ?? 1_000_000);
            }
        }

        return 1_000_000.0;
    }

    /**
     * Plafon tertinggi limit pinjaman dari seluruh kebijakan master SettingLimitPinjaman.
     */
    public function plafonTertinggiKoperasi(): float
    {
        $settings = $this->semuaLimit();
        if (empty($settings)) {
            return 10_000_000.0;
        }

        $maks = 0.0;
        foreach ($settings as $setting) {
            $val = (float) ($setting->limit_maksimal ?? 0);
            if ($val > $maks) {
                $maks = $val;
            }
        }

        return $maks > 0 ? $maks : 10_000_000.0;
    }

    /**
     * Limit efektif yang boleh diajukan anggota saat ini.
     * - Tanpa pinjaman aktif: limit maksimal penuh.
     * - Sisa > 2: 0 (terkunci; angsuran yang dibayar belum membuka limit).
     * - Sisa <= 2: rumus berjalan (maks - sisa × cicilan), floor 0.
     */
    public function limitTersedia(Anggota $anggota): float
    {
        $agg = $anggota->pinjamanAktifDenganAgregat();

        if ($agg === null) {
            return $this->limitMaksimal($anggota);
        }

        $sisaTotal = (int) $agg['sisa_total'];

        if ($sisaTotal > 2) {
            return 0.0;
        }

        $limitMaksimal = $this->limitMaksimal($anggota);
        $cicilanPokok = (float) $agg['cicilan_pokok_weighted_avg'];

        return max(0.0, $limitMaksimal - ($sisaTotal * $cicilanPokok));
    }

    /**
     * Cari tenor maksimal untuk nominal pinjaman tertentu (dari master data tabel_tenor ter-memo).
     */
    public function tenorMaksimal(float $nominal): ?int
    {
        foreach ($this->semuaTenor() as $tenor) {
            if ($nominal >= (float) $tenor->nominal_min && $nominal <= (float) $tenor->nominal_max) {
                return $tenor->tenor_maksimal_bulan;
            }
        }

        return null;
    }

    private function isAnggotaBaru(Anggota $anggota): bool
    {
        return $anggota->lama_keanggotaan_tahun < 1;
    }
}
