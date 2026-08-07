<?php

namespace App\Services\Pinjaman;

use App\Models\Anggota;
use App\Models\Pinjaman;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PengajuanPinjamanService
{
    public function __construct(
        private EligibilitasPinjamanService $eligibilitas,
        private PerhitunganBungaService $bunga,
    ) {}

    /**
     * Proses pengajuan pinjaman baru oleh anggota.
     * Melempar RuntimeException dengan pesan jelas kalau tidak memenuhi syarat.
     */
    public function ajukan(Anggota $anggota, float $nominal, int $tenorBulan): Pinjaman
    {
        $cekEligibilitas = $this->eligibilitas->cek($anggota);

        if (! $cekEligibilitas['boleh']) {
            throw new RuntimeException($cekEligibilitas['alasan']);
        }

        $limitMaksimal = $this->eligibilitas->limitMaksimal($anggota);
        if ($nominal > $limitMaksimal) {
            throw new RuntimeException(
                'Nominal pinjaman melebihi limit maksimal Anda: Rp ' . number_format($limitMaksimal, 0, ',', '.')
            );
        }

        $tenorMaksimal = $this->eligibilitas->tenorMaksimal($nominal);
        if (! $tenorMaksimal) {
            throw new RuntimeException('Nominal pinjaman tidak sesuai dengan ketentuan yang berlaku.');
        }
        if ($tenorBulan > $tenorMaksimal) {
            throw new RuntimeException("Tenor maksimal untuk nominal ini adalah {$tenorMaksimal} bulan.");
        }

        return DB::transaction(function () use ($anggota, $nominal, $tenorBulan) {
            $pinjamanAktif = $anggota->pinjamanAktif();
            $pakaiPrivilegeReloan = $pinjamanAktif && $pinjamanAktif->sisaAngsuran() <= 2;

            // Tandai pinjaman lama sudah pakai privilege, supaya tidak bisa dipakai lagi
            if ($pakaiPrivilegeReloan) {
                $pinjamanAktif->update(['sudah_pakai_privilege_reloan' => true]);
            }

            return Pinjaman::create([
                'anggota_id' => $anggota->id,
                'nominal' => $nominal,
                'tenor_bulan' => $tenorBulan,
                'persentase_bunga' => $this->bunga->persentaseBungaBerlaku(),
                'status' => 'diajukan',
                'sudah_pakai_privilege_reloan' => false,
                'tanggal_pengajuan' => now(),
            ]);
        });
    }

    /**
     * Preview simulasi cicilan sebelum benar-benar submit (dipakai di form pengajuan).
     */
    public function preview(float $nominal, int $tenorBulan): array
    {
        $persentase = $this->bunga->persentaseBungaBerlaku();

        return $this->bunga->buatJadwal($nominal, $tenorBulan, $persentase);
    }
}