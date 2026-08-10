<?php

namespace App\Services\Pinjaman;

use App\Models\Anggota;
use App\Models\Pinjaman;
use App\Models\RekeningAnggota;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PengajuanPinjamanService
{
    public function __construct(
        private EligibilitasPinjamanService $eligibilitas,
        private PerhitunganBungaService $bunga,
    ) {}

    /**
     * @param array $rekening ['mode' => 'tersimpan'|'baru', 'rekening_id' => ?int,
     *                          'nama_bank' => ?string, 'no_rekening' => ?string, 'atas_nama' => ?string]
     */
    public function ajukan(Anggota $anggota, float $nominal, int $tenorBulan, string $keperluan, array $rekening): Pinjaman
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

        return DB::transaction(function () use ($anggota, $nominal, $tenorBulan, $keperluan, $rekening) {
            $pinjamanAktif = $anggota->pinjamanAktif();
            $pakaiPrivilegeReloan = $pinjamanAktif && $pinjamanAktif->sisaAngsuran() <= 2;

            if ($pakaiPrivilegeReloan) {
                $pinjamanAktif->update(['sudah_pakai_privilege_reloan' => true]);
            }

            $dataRekening = $this->siapkanRekening($anggota, $rekening);

            return Pinjaman::create([
                'anggota_id' => $anggota->id,
                'nominal' => $nominal,
                'tenor_bulan' => $tenorBulan,
                'keperluan' => $keperluan,
                'snapshot_bank' => $dataRekening['nama_bank'],
                'snapshot_no_rekening' => $dataRekening['no_rekening'],
                'snapshot_atas_nama' => $dataRekening['atas_nama'],
                'persentase_bunga' => $this->bunga->persentaseBungaBerlaku(),
                'status' => 'diajukan',
                'sudah_pakai_privilege_reloan' => false,
                'tanggal_pengajuan' => now(),
            ]);
        });
    }

    /**
     * Ambil data rekening untuk snapshot. Kalau mode "baru", sekalian simpan
     * ke rekening_anggota supaya bisa dipakai lagi nanti.
     */
    private function siapkanRekening(Anggota $anggota, array $rekening): array
    {
        if ($rekening['mode'] === 'tersimpan') {
            $r = RekeningAnggota::where('anggota_id', $anggota->id)
                ->findOrFail($rekening['rekening_id']);

            return [
                'nama_bank' => $r->nama_bank,
                'no_rekening' => $r->no_rekening,
                'atas_nama' => $r->atas_nama,
            ];
        }

        // Mode "baru" - simpan ke rekening_anggota supaya tersedia untuk pengajuan berikutnya
        $jumlahRekening = RekeningAnggota::where('anggota_id', $anggota->id)->count();

        $rekeningBaru = RekeningAnggota::create([
            'anggota_id' => $anggota->id,
            'nama_bank' => $rekening['nama_bank'],
            'no_rekening' => $rekening['no_rekening'],
            'atas_nama' => $rekening['atas_nama'],
            'is_default' => $jumlahRekening === 0, // rekening pertama otomatis jadi default
        ]);

        return [
            'nama_bank' => $rekeningBaru->nama_bank,
            'no_rekening' => $rekeningBaru->no_rekening,
            'atas_nama' => $rekeningBaru->atas_nama,
        ];
    }

    public function preview(float $nominal, int $tenorBulan): array
    {
        $persentase = $this->bunga->persentaseBungaBerlaku();

        return $this->bunga->buatJadwal($nominal, $tenorBulan, $persentase);
    }
}