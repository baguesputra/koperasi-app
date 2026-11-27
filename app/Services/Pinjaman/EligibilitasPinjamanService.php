<?php

namespace App\Services\Pinjaman;

use App\Models\Anggota;
use App\Models\PengajuanLimit;
use App\Models\Pinjaman;
use App\Models\SettingLimitPinjaman;
use App\Models\SettingSimpanan;
use App\Models\Simpanan;
use App\Models\TabelTenor;

class EligibilitasPinjamanService
{
    public const MIN_BULAN_KEANGGOTAAN = 6;

    public const MAKS_SISA_ANGSURAN_RELOAN = 1;

    /** Memo seluruh setting limit terurut selama request (ganti query berulang). */
    private static ?array $semuaLimitCache = null;

    /** Memo tabel tenor selama request (ganti query berulang per pinjaman). */
    private static ?array $tabelTenorCache = null;

    /** Memo tarif simpanan wajib & dana sosial selama request. */
    private static ?array $tarifSimpananCache = null;

    public static function resetCache(): void
    {
        self::$semuaLimitCache = null;
        self::$tabelTenorCache = null;
        self::$tarifSimpananCache = null;
    }

    /** Tarif iuran bulanan (simpanan wajib + dana sosial) dengan fallback aman. */
    private function tarifSimpanan(): array
    {
        if (self::$tarifSimpananCache === null) {
            $rows = SettingSimpanan::whereIn('jenis', ['wajib', 'dana_sosial'])
                ->pluck('nominal', 'jenis');

            self::$tarifSimpananCache = [
                'wajib' => (float) ($rows['wajib'] ?? 45_000),
                'dana_sosial' => (float) ($rows['dana_sosial'] ?? 5_000),
            ];
        }

        return self::$tarifSimpananCache;
    }

    /** Hitung masa keanggotaan penuh dalam bulan kalender sejak tanggal_jadi_anggota. */
    public function masaKeanggotaanBulan(Anggota $anggota): int
    {
        if (! $anggota->tanggal_jadi_anggota) {
            return 0;
        }

        $mulai = $anggota->tanggal_jadi_anggota->copy()->startOfDay();
        $sekarang = now()->startOfDay();

        if ($mulai->greaterThan($sekarang)) {
            return 0;
        }

        return (int) $mulai->diffInMonths($sekarang);
    }

    /**
     * Cek syarat minimal 6 bulan keanggotaan atau pelunasan simpanan di muka 6 bulan.
     * Anggota dengan masa >= 6 bulan langsung lolos tanpa query tabel simpanan.
     *
     * @return array{
     *     terpenuhi: bool,
     *     masa_bulan: int,
     *     min_bulan: int,
     *     bulan_simpanan_wajib: int,
     *     kekurangan_bulan: int,
     *     nominal_wajib_per_bulan: float,
     *     nominal_dana_sosial_per_bulan: float,
     *     nominal_per_bulan: float,
     *     estimasi_pelunasan_dimuka: float,
     *     alasan: ?string
     * }
     */
    public function cekSyaratMasaKeanggotaan(Anggota $anggota): array
    {
        $masaBulan = $this->masaKeanggotaanBulan($anggota);

        if ($masaBulan >= self::MIN_BULAN_KEANGGOTAAN) {
            return [
                'terpenuhi' => true,
                'masa_bulan' => $masaBulan,
                'min_bulan' => self::MIN_BULAN_KEANGGOTAAN,
                'bulan_simpanan_wajib' => self::MIN_BULAN_KEANGGOTAAN,
                'kekurangan_bulan' => 0,
                'nominal_wajib_per_bulan' => 0.0,
                'nominal_dana_sosial_per_bulan' => 0.0,
                'nominal_per_bulan' => 0.0,
                'estimasi_pelunasan_dimuka' => 0.0,
                'alasan' => null,
            ];
        }

        $bulanWajib = (int) Simpanan::where('anggota_id', $anggota->id)
            ->where('jenis', 'wajib')
            ->distinct('bulan_periode')
            ->count('bulan_periode');

        $tarif = $this->tarifSimpanan();
        $tarifWajib = (float) $tarif['wajib'];
        $tarifSosial = (float) $tarif['dana_sosial'];
        $nominalPerBulan = $tarifWajib + $tarifSosial;

        $kekurangan = max(0, self::MIN_BULAN_KEANGGOTAAN - $bulanWajib);
        $terpenuhi = $kekurangan === 0;
        $estimasi = (float) ($kekurangan * $nominalPerBulan);

        $alasan = $terpenuhi
            ? null
            : "Masa keanggotaan Anda masih di bawah 6 bulan ({$masaBulan} bulan, simpanan wajib tercatat {$bulanWajib}/6 bulan). Pengajuan dibuka setelah 6 bulan keanggotaan, atau bayar simpanan di muka sebanyak {$kekurangan} bulan lagi (Rp ".number_format($estimasi, 0, ',', '.').') melalui Bendahara.';

        return [
            'terpenuhi' => $terpenuhi,
            'masa_bulan' => $masaBulan,
            'min_bulan' => self::MIN_BULAN_KEANGGOTAAN,
            'bulan_simpanan_wajib' => $bulanWajib,
            'kekurangan_bulan' => $kekurangan,
            'nominal_wajib_per_bulan' => $tarifWajib,
            'nominal_dana_sosial_per_bulan' => $tarifSosial,
            'nominal_per_bulan' => $nominalPerBulan,
            'estimasi_pelunasan_dimuka' => $estimasi,
            'alasan' => $alasan,
        ];
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
     *   - limit_tersedia: float (sisa plafon = maks − sisa×cicilan; selalu dihitung
     *                            apa adanya sebagai informasi, termasuk saat terkunci;
     *                            full limit jika tidak ada pinjaman aktif)
     *     PENTING: limit_tersedia BUKAN gerbang izin. Gerbangnya `boleh`. Saat
     *     sisa > 1 angsuran, limit_tersedia tetap > 0 tapi `boleh` = false.
     *   - sisa_angsuran: int (0 jika tidak ada pinjaman aktif; gabungan semua pinjaman aktif)
     *   - cicilan_pokok: float (0 jika tidak ada angsuran belum_bayar; weighted avg dari semua)
     *
     * Aturan:
     *   - Tidak ada pinjaman aktif -> boleh (limit penuh)
     *   - Sisa > 1 angsuran -> terkunci (limit 0, wajib melunasi hingga sisa <= 1)
     *   - Sisa <= 1, anggota < 1 tahun -> tolak (wajib lunas)
     *   - Sisa <= 1, salah satu flag reloan sudah dipakai -> tolak (harus lunas dulu)
     *   - Sisa <= 1, semua flag reloan belum dipakai -> boleh (limit dikurangi rumus berjalan)
     */
    public function cek(Anggota $anggota, ?array $preloadedAgg = null, bool $pakaiPreloadedAgg = false, ?Pinjaman $sedangDiedit = null): array
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
                'syarat_masa_keanggotaan' => null,
            ];
        }

        $syaratMasa = $this->cekSyaratMasaKeanggotaan($anggota);
        if (! $syaratMasa['terpenuhi']) {
            return [
                'boleh' => false,
                'alasan' => $syaratMasa['alasan'],
                'limit_tersedia' => $this->limitMaksimal($anggota),
                'sisa_angsuran' => 0,
                'cicilan_pokok' => 0.0,
                'syarat_masa_keanggotaan' => $syaratMasa,
            ];
        }

        $agg = $pakaiPreloadedAgg ? $preloadedAgg : $anggota->pinjamanAktifDenganAgregat();

        // Belum punya pinjaman aktif sama sekali -> pasti boleh
        if ($agg === null) {
            $limit = $this->limitMaksimal($anggota);

            return [
                'boleh' => true,
                'alasan' => null,
                'limit_tersedia' => $limit,
                'sisa_angsuran' => 0,
                'cicilan_pokok' => 0.0,
                'syarat_masa_keanggotaan' => $syaratMasa,
            ];
        }

        $sisaAngsuran = (int) $agg['sisa_total'];
        $cicilanPokok = (float) $agg['cicilan_pokok_weighted_avg'];

        // Sisa plafon selalu dihitung apa adanya (informatif), terlepas dari kuncian.
        $limitMaksimal = $this->limitMaksimal($anggota);
        $limitTersedia = max(0.0, $limitMaksimal - ($sisaAngsuran * $cicilanPokok));

        // Kuncian: angsuran yang dibayar tidak langsung membuka pengajuan.
        // Baru terbuka saat tersisa <= 1 angsuran; lunas penuh = limit awal pulih.
        if ($sisaAngsuran > self::MAKS_SISA_ANGSURAN_RELOAN) {
            return [
                'boleh' => false,
                'alasan' => 'Pinjaman sebelumnya belum lunas. Selesaikan pembayaran hingga tersisa maksimal 1 angsuran untuk membuka pengajuan berikutnya.',
                'limit_tersedia' => $limitTersedia,
                'sisa_angsuran' => $sisaAngsuran,
                'cicilan_pokok' => $cicilanPokok,
                'syarat_masa_keanggotaan' => $syaratMasa,
            ];
        }

        // Anggota < 1 tahun: wajib lunas dulu, tidak dapat privilege reloan
        if ($this->isAnggotaBaru($anggota)) {
            $result = $sisaAngsuran === 0
                ? ['boleh' => true, 'alasan' => null]
                : ['boleh' => false, 'alasan' => 'Pinjaman sebelumnya belum lunas. Anggota baru (< 1 tahun) wajib melunasi pinjaman sebelum mengajukan pinjaman baru.'];

            return $result + [
                'limit_tersedia' => $limitTersedia,
                'sisa_angsuran' => $sisaAngsuran,
                'cicilan_pokok' => $cicilanPokok,
                'syarat_masa_keanggotaan' => $syaratMasa,
            ];
        }

        // Reloan privilege: 1x per siklus. Cek flag di SEMUA pinjaman aktif.
        // Jika salah satu sudah true, anggota tidak boleh ajukan lagi sampai pinjaman-pinjaman tsb lunas.
        $privilegeSudahDipakai = $agg['pinjaman_aktif_list']->contains(
            'sudah_pakai_privilege_reloan',
            true
        );

        // Saat pemohon mengedit pengajuan reloan miliknya sendiri yang belum cair,
        // flag privilege di pinjaman aktif memang dinyalakan oleh pengajuan yang sedang diedit ini.
        if ($privilegeSudahDipakai && $sedangDiedit !== null && $agg['pinjaman_aktif_list']->count() === 1) {
            $aktifPertama = $agg['pinjaman_aktif_list']->first();
            $adaPinjamanPascaAktifLain = $aktifPertama
                ? Pinjaman::where('anggota_id', $anggota->id)
                    ->where('id', '!=', $sedangDiedit->id)
                    ->where('id', '>', $aktifPertama->id)
                    ->whereIn('status', ['aktif', 'lunas'])
                    ->exists()
                : true;

            if ($aktifPertama && $sedangDiedit->id > $aktifPertama->id && ! $adaPinjamanPascaAktifLain) {
                $privilegeSudahDipakai = false;
            }
        }

        if ($privilegeSudahDipakai) {
            return [
                'boleh' => false,
                'alasan' => 'Anda sudah menggunakan hak pengajuan lebih awal untuk pinjaman ini. Selesaikan pelunasan terlebih dahulu sebelum mengajukan pinjaman baru.',
                'limit_tersedia' => $limitTersedia,
                'sisa_angsuran' => $sisaAngsuran,
                'cicilan_pokok' => $cicilanPokok,
                'syarat_masa_keanggotaan' => $syaratMasa,
            ];
        }

        return [
            'boleh' => true,
            'alasan' => null,
            'limit_tersedia' => $limitTersedia,
            'sisa_angsuran' => $sisaAngsuran,
            'cicilan_pokok' => $cicilanPokok,
            'syarat_masa_keanggotaan' => $syaratMasa,
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
     * Cek eligibilitas anggota untuk mengajukan kenaikan limit pinjaman.
     * Menggabungkan aturan:
     * 1. Status anggota aktif.
     * 2. Tidak sedang memiliki pengajuan pinjaman dalam proses verifikasi.
     * 3. Tidak sedang memiliki pengajuan limit yang belum diproses/diverifikasi.
     * 4. Limit saat ini belum menyentuh plafon maksimal kebijakan koperasi (10 jt).
     * 5. Jika ada pinjaman aktif, sisa angsuran harus <= 1 angsuran atau telah lunas.
     * 6. Jika pernah mendapat persetujuan kenaikan limit sebelumnya, wajib memanfaatkan
     *    limit tersebut untuk pinjaman terlebih dahulu sebelum mengajukan kenaikan lagi.
     *
     * @return array{
     *     boleh: bool,
     *     alasan: ?string,
     *     status_badge: string,
     *     state_label: string,
     *     limit_saat_ini: float,
     *     plafon_koperasi: float
     * }
     */
    public function cekEligibilitasTambahLimit(
        Anggota $anggota,
        ?array $preloadedAgg = null,
        bool $pakaiPreloadedAgg = false,
        ?bool $adaPinjamanDiproses = null,
        ?bool $adaLimitDiproses = null,
    ): array {
        $limitSaatIni = $this->limitMaksimal($anggota);
        $plafonMaksimalKoperasi = $this->plafonTertinggiKoperasi();

        // 1. Status anggota
        if ($anggota->status !== 'aktif') {
            $alasan = $anggota->status === 'resign_menunggu'
                ? 'Penyelesaian resign Anda sedang menunggu pelunasan.'
                : "Anggota berstatus '{$anggota->status}', tidak dapat mengajukan kenaikan limit.";

            return [
                'boleh' => false,
                'alasan' => $alasan,
                'status_badge' => 'terkunci',
                'state_label' => 'Belum memenuhi',
                'limit_saat_ini' => $limitSaatIni,
                'plafon_koperasi' => $plafonMaksimalKoperasi,
            ];
        }

        // 1b. Minimal 6 bulan keanggotaan atau pelunasan simpanan wajib 6 bulan di muka
        $syaratMasa = $this->cekSyaratMasaKeanggotaan($anggota);
        if (! $syaratMasa['terpenuhi']) {
            return [
                'boleh' => false,
                'alasan' => "Pengajuan kenaikan limit belum tersedia. {$syaratMasa['alasan']}",
                'status_badge' => 'terkunci',
                'state_label' => "Minimal 6 bln ({$syaratMasa['bulan_simpanan_wajib']}/6)",
                'limit_saat_ini' => $limitSaatIni,
                'plafon_koperasi' => $plafonMaksimalKoperasi,
            ];
        }

        // 2. Ada pengajuan pinjaman sedang diproses
        $adaPinjamanDiproses ??= $anggota->pinjaman()
            ->whereIn('status', ['diajukan', 'approved_admin', 'approved_bendahara', 'approved_ketua'])
            ->exists();

        if ($adaPinjamanDiproses) {
            return [
                'boleh' => false,
                'alasan' => 'Pengajuan pinjaman sedang diproses.',
                'status_badge' => 'diproses',
                'state_label' => 'Pengajuan diproses',
                'limit_saat_ini' => $limitSaatIni,
                'plafon_koperasi' => $plafonMaksimalKoperasi,
            ];
        }

        // 3. Ada pengajuan limit sedang diverifikasi
        $adaLimitDiproses ??= PengajuanLimit::where('anggota_id', $anggota->id)
            ->whereIn('status', ['diajukan', 'approved_bendahara'])
            ->exists();

        if ($adaLimitDiproses) {
            return [
                'boleh' => false,
                'alasan' => 'Anda masih memiliki pengajuan limit yang belum diproses.',
                'status_badge' => 'diproses',
                'state_label' => 'Sedang diverifikasi',
                'limit_saat_ini' => $limitSaatIni,
                'plafon_koperasi' => $plafonMaksimalKoperasi,
            ];
        }

        // 4. Plafon maksimal kebijakan koperasi tercapai
        if ($limitSaatIni >= $plafonMaksimalKoperasi) {
            return [
                'boleh' => false,
                'alasan' => 'Limit Anda telah mencapai batas maksimal kebijakan koperasi (Rp '.number_format($plafonMaksimalKoperasi, 0, ',', '.').').',
                'status_badge' => 'maksimal',
                'state_label' => 'Plafon maksimal',
                'limit_saat_ini' => $limitSaatIni,
                'plafon_koperasi' => $plafonMaksimalKoperasi,
            ];
        }

        // 5. Pinjaman aktif berjalan (sisa angsuran > 1)
        $agg = $pakaiPreloadedAgg ? $preloadedAgg : $anggota->pinjamanAktifDenganAgregat();
        if ($agg !== null) {
            $sisaAngsuran = (int) $agg['sisa_total'];
            if ($sisaAngsuran > self::MAKS_SISA_ANGSURAN_RELOAN) {
                return [
                    'boleh' => false,
                    'alasan' => 'Pinjaman sebelumnya belum lunas. Kenaikan limit baru dapat diajukan setelah pinjaman berjalan tersisa maksimal 1 angsuran atau telah lunas.',
                    'status_badge' => 'terkunci',
                    'state_label' => 'Angsuran berjalan',
                    'limit_saat_ini' => $limitSaatIni,
                    'plafon_koperasi' => $plafonMaksimalKoperasi,
                ];
            }
        }

        // 6. Opsi 1: Pemanfaatan limit setelah kenaikan disetujui
        $limitTerakhirDisetujui = PengajuanLimit::where('anggota_id', $anggota->id)
            ->where('status', 'disetujui')
            ->latest('updated_at')
            ->first();

        if ($limitTerakhirDisetujui) {
            $waktuSetuju = $limitTerakhirDisetujui->updated_at ?? $limitTerakhirDisetujui->created_at;
            $adaPinjamanSetelahLimit = $anggota->pinjaman()
                ->where('created_at', '>=', $waktuSetuju ? $waktuSetuju->copy()->subSeconds(2) : now())
                ->whereIn('status', ['aktif', 'lunas'])
                ->exists();

            if (! $adaPinjamanSetelahLimit) {
                return [
                    'boleh' => false,
                    'alasan' => 'Anda telah mendapatkan kenaikan limit menjadi Rp '.number_format($limitSaatIni, 0, ',', '.').'. Manfaatkan limit ini untuk pinjaman terlebih dahulu sebelum mengajukan kenaikan berikutnya.',
                    'status_badge' => 'terkunci',
                    'state_label' => 'Belum digunakan',
                    'limit_saat_ini' => $limitSaatIni,
                    'plafon_koperasi' => $plafonMaksimalKoperasi,
                ];
            }
        }

        return [
            'boleh' => true,
            'alasan' => null,
            'status_badge' => 'tersedia',
            'state_label' => 'Ajukan kenaikan limit',
            'limit_saat_ini' => $limitSaatIni,
            'plafon_koperasi' => $plafonMaksimalKoperasi,
        ];
    }

    /**
     * Limit efektif yang boleh diajukan anggota saat ini.
     * - Tanpa pinjaman aktif: limit maksimal penuh.
     * - Ada pinjaman aktif: rumus berjalan (maks - sisa × cicilan), floor 0 —
     *   dihitung juga saat terkunci karena hanya informatif.
     *
     * CATATAN: nilai ini BUKAN gerbang izin mengajukan. Gunakan cek()['boleh']
     * untuk memutuskan boleh/tidak (terkunci saat sisa > 1 angsuran).
     */
    public function limitTersedia(Anggota $anggota): float
    {
        $agg = $anggota->pinjamanAktifDenganAgregat();

        if ($agg === null) {
            return $this->limitMaksimal($anggota);
        }

        $sisaTotal = (int) $agg['sisa_total'];
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
