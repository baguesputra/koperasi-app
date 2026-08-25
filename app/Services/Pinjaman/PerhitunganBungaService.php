<?php

namespace App\Services\Pinjaman;

use App\Models\Pinjaman;
use App\Models\SettingBunga;

class PerhitunganBungaService
{
    /**
     * Ambil persentase bunga yang sedang berlaku (untuk snapshot saat pinjaman dibuat).
     */
    public function persentaseBungaBerlaku(): float
    {
        $setting = SettingBunga::where('berlaku_dari_tanggal', '<=', now())
            ->orderByDesc('berlaku_dari_tanggal')
            ->first();

        return (float) ($setting->persentase ?? 1.00);
    }

    /**
     * Generate rincian jadwal angsuran (belum disimpan ke database).
     * Bunga dihitung menurun (declining balance) dari sisa pokok tiap bulan.
     *
     * Return array of: ['cicilan_ke', 'nominal_pokok', 'nominal_bunga', 'total_bayar']
     */
    public function buatJadwal(float $nominal, int $tenorBulan, float $persentaseBunga): array
    {
        $sisaPokok = $nominal;
        $pokokPerBulan = round($nominal / $tenorBulan, 2);
        $persentase = $persentaseBunga / 100;

        $jadwal = [];

        for ($cicilanKe = 1; $cicilanKe <= $tenorBulan; $cicilanKe++) {
            $bunga = round($sisaPokok * $persentase, 2);

            // Cicilan terakhir menyerap sisa pembulatan supaya Σpokok == nominal persis
            $pokok = $cicilanKe === $tenorBulan ? $sisaPokok : $pokokPerBulan;

            $jadwal[] = [
                'cicilan_ke' => $cicilanKe,
                'nominal_pokok' => $pokok,
                'nominal_bunga' => $bunga,
                'total_bayar' => $pokok + $bunga,
            ];

            $sisaPokok -= $pokok;
        }

        return $jadwal;
    }

    /**
     * Simpan jadwal angsuran ke database untuk pinjaman yang sudah aktif.
     */
    public function simpanJadwal(Pinjaman $pinjaman): void
    {
        $jadwal = $this->buatJadwal(
            (float) $pinjaman->nominal,
            $pinjaman->tenor_bulan,
            (float) $pinjaman->persentase_bunga
        );

        foreach ($jadwal as $baris) {
            $pinjaman->angsuran()->create([
                ...$baris,
                'status' => 'belum_bayar',
                'tanggal_jatuh_tempo' => $pinjaman->tanggal_pencairan->copy()
                    ->addMonths($baris['cicilan_ke'] - 1)
                    ->endOfMonth(),
            ]);
        }
    }
}
