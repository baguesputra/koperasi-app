<?php

namespace App\Services\Pinjaman;

use App\Models\Anggota;
use App\Models\TabelTenor;
use App\Models\SettingLimitPinjaman;

class EligibilitasPinjamanService
{
    /**
     * Cek apakah anggota boleh mengajukan pinjaman baru.
     * Return array: ['boleh' => bool, 'alasan' => string|null]
     */
    public function cek(Anggota $anggota): array
    {
        $pinjamanAktif = $anggota->pinjamanAktif();

        // Belum punya pinjaman aktif sama sekali -> pasti boleh
        if (! $pinjamanAktif) {
            return $this->boleh();
        }

        $sisaAngsuran = $pinjamanAktif->sisaAngsuran();
        $anggotaBaru = $this->isAnggotaBaru($anggota);

        // Anggota < 1 tahun: wajib lunas dulu, tidak dapat privilege reloan
        if ($anggotaBaru) {
            return $sisaAngsuran === 0
                ? $this->boleh()
                : $this->tidakBoleh('Pinjaman sebelumnya belum lunas. Anggota baru (< 1 tahun) wajib melunasi pinjaman sebelum mengajukan pinjaman baru.');
        }

        // Anggota lama: boleh reloan kalau sisa angsuran <= 2, TAPI cuma sekali
        if ($sisaAngsuran <= 2) {
            if ($pinjamanAktif->sudah_pakai_privilege_reloan) {
                return $this->tidakBoleh('Anda sudah menggunakan hak pengajuan lebih awal untuk pinjaman ini. Selesaikan pelunasan terlebih dahulu sebelum mengajukan pinjaman baru.');
            }

            return $this->boleh();
        }

        return $this->tidakBoleh("Pinjaman aktif masih tersisa {$sisaAngsuran} kali angsuran. Selesaikan pelunasan terlebih dahulu.");
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

    private function boleh(): array
    {
        return ['boleh' => true, 'alasan' => null];
    }

    private function tidakBoleh(string $alasan): array
    {
        return ['boleh' => false, 'alasan' => $alasan];
    }
}