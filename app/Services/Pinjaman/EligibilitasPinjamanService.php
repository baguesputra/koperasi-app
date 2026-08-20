<?php

namespace App\Services\Pinjaman;

use App\Models\Anggota;
use App\Models\SettingLimitPinjaman;
use App\Models\TabelTenor;

class EligibilitasPinjamanService
{
    /**
     * Cek apakah anggota boleh mengajukan pinjaman baru.
     * Return array:
     *   - boleh: bool
     *   - alasan: string|null
     *   - limit_tersedia: float (limit efektif = limitKategori - sisaTotal×cicilanPokokWeighted,
     *                            atau full limit jika tidak ada pinjaman aktif)
     *   - sisa_angsuran: int (0 jika tidak ada pinjaman aktif; gabungan semua pinjaman aktif)
     *   - cicilan_pokok: float (0 jika tidak ada angsuran belum_bayar; weighted avg dari semua)
     *
     * Aturan:
     *   - Tidak ada pinjaman aktif -> boleh (limit penuh)
     *   - Ada pinjaman aktif, anggota < 1 tahun -> tolak (wajib lunas)
     *   - Ada pinjaman aktif, salah satu flag reloan sudah dipakai -> tolak (harus lunas dulu)
     *   - Ada pinjaman aktif, semua flag reloan belum dipakai -> boleh (limit dikurangi)
     */
    public function cek(Anggota $anggota): array
    {
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
     */
    public function limitMaksimal(Anggota $anggota): float
    {
        if ($anggota->limit_custom !== null) {
            return (float) $anggota->limit_custom;
        }

        $lamaTahun = $anggota->lama_keanggotaan_tahun;

        $kategori = match (true) {
            $lamaTahun < 1 => 'kurang_1_tahun',
            $lamaTahun < 3 => 'satu_sampai_3_tahun',
            $lamaTahun < 5 => 'tiga_sampai_5_tahun',
            default => 'lebih_5_tahun',
        };

        $setting = SettingLimitPinjaman::where('kategori', $kategori)->first();

        return (float) ($setting->limit_maksimal ?? 1_000_000);
    }

    /**
     * Limit efektif yang boleh diajukan anggota saat ini.
     * Aggregate dari SEMUA pinjaman aktif (bisa >1 setelah reloan).
     * Sisa = total dari semua angsuran belum_bayar (gabungan angsuran biasa +
     *        angsuran_percepatan dari pengajuan aktif).
     * Cicilan = weighted avg dari cicilan_pokok per pinjaman.
     * Floor di 0 supaya aturan validasi natural menolak (nominal min:1).
     */
    public function limitTersedia(Anggota $anggota): float
    {
        $agg = $anggota->pinjamanAktifDenganAgregat();

        if ($agg === null) {
            return $this->limitMaksimal($anggota);
        }

        $limitMaksimal = $this->limitMaksimal($anggota);
        $sisaTotal = (int) $agg['sisa_total'];
        $cicilanPokok = (float) $agg['cicilan_pokok_weighted_avg'];

        return max(0.0, $limitMaksimal - ($sisaTotal * $cicilanPokok));
    }

    /**
     * Cari tenor maksimal untuk nominal pinjaman tertentu (dari master data tabel_tenor).
     */
    public function tenorMaksimal(float $nominal): ?int
    {
        $tenor = TabelTenor::where('nominal_min', '<=', $nominal)
            ->where('nominal_max', '>=', $nominal)
            ->first();

        return $tenor?->tenor_maksimal_bulan;
    }

    private function isAnggotaBaru(Anggota $anggota): bool
    {
        return $anggota->lama_keanggotaan_tahun < 1;
    }
}
