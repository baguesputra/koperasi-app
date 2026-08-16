<?php

namespace App\Services\Pinjaman;

use App\Models\Anggota;
use App\Models\AuditLog;
use App\Models\PengajuanLimit;
use RuntimeException;

class PengajuanLimitService
{
    public function __construct(private EligibilitasPinjamanService $eligibilitas) {}

    public function ajukan(Anggota $anggota, float $limitDiminta, string $keterangan): PengajuanLimit
    {
        $adaPengajuanMenunggu = PengajuanLimit::where('anggota_id', $anggota->id)
            ->where('status', 'diajukan')
            ->exists();

        if ($adaPengajuanMenunggu) {
            throw new RuntimeException('Anda masih memiliki pengajuan limit yang belum diproses.');
        }

        $limitSaatIni = $this->eligibilitas->limitMaksimal($anggota);

        if ($limitDiminta <= $limitSaatIni) {
            throw new RuntimeException('Limit yang diajukan harus lebih besar dari limit Anda saat ini: Rp ' . number_format($limitSaatIni, 0, ',', '.'));
        }

        return PengajuanLimit::create([
            'anggota_id' => $anggota->id,
            'limit_saat_ini' => $limitSaatIni,
            'limit_diminta' => $limitDiminta,
            'keterangan' => $keterangan,
            'status' => 'diajukan',
            'tanggal_pengajuan' => now(),
        ]);
    }

    public function setujui(PengajuanLimit $pengajuan, string $catatan): void
    {
        $pengajuan->update([
            'status' => 'disetujui',
            'catatan_ketua' => $catatan,
        ]);

        $pengajuan->anggota->update(['limit_custom' => $pengajuan->limit_diminta]);

        AuditLog::catat(
            'setujui_pengajuan_limit',
            "Limit khusus {$pengajuan->anggota->nama} disetujui menjadi Rp " . number_format($pengajuan->limit_diminta, 0, ',', '.'),
            ['limit_custom' => $pengajuan->anggota->limit_custom],
            ['limit_custom' => $pengajuan->limit_diminta]
        );
    }

    public function tolak(PengajuanLimit $pengajuan, string $catatan): void
    {
        $pengajuan->update([
            'status' => 'ditolak',
            'catatan_ketua' => $catatan,
        ]);
    }
}