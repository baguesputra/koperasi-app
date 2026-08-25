<?php

namespace App\Services\Pinjaman;

use App\Models\Anggota;
use App\Models\AuditLog;
use App\Models\PengajuanLimit;
use App\Services\Wa\WaService;
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
            throw new RuntimeException('Limit yang diajukan harus lebih besar dari limit Anda saat ini: Rp '.number_format($limitSaatIni, 0, ',', '.'));
        }

        $pengajuan = PengajuanLimit::create([
            'anggota_id' => $anggota->id,
            'limit_saat_ini' => $limitSaatIni,
            'limit_diminta' => $limitDiminta,
            'keterangan' => $keterangan,
            'status' => 'diajukan',
            'tanggal_pengajuan' => now(),
        ]);

        WaService::keAnggota(
            $anggota,
            'limit_diajukan',
            "Halo {$anggota->nama}, pengajuan kenaikan limit Anda (dari Rp ".number_format($limitSaatIni, 0, ',', '.').' menjadi Rp '.number_format($limitDiminta, 0, ',', '.').') sudah diterima dan sedang diproses.'
        );

        WaService::kePengurus(
            'limit_diajukan',
            "Ada pengajuan kenaikan limit dari {$anggota->nama}: dari Rp ".number_format($limitSaatIni, 0, ',', '.').' menjadi Rp '.number_format($limitDiminta, 0, ',', '.').'. Silakan tinjau di sistem koperasi.'
        );

        return $pengajuan;
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
            "Limit khusus {$pengajuan->anggota->nama} disetujui menjadi Rp ".number_format($pengajuan->limit_diminta, 0, ',', '.'),
            ['limit_custom' => $pengajuan->anggota->limit_custom],
            ['limit_custom' => $pengajuan->limit_diminta]
        );

        WaService::keAnggota(
            $pengajuan->anggota,
            'limit_disetujui',
            "Selamat {$pengajuan->anggota->nama}, pengajuan limit Anda disetujui. Limit pinjaman Anda kini Rp ".number_format($pengajuan->limit_diminta, 0, ',', '.').'.'
        );
    }

    public function tolak(PengajuanLimit $pengajuan, string $catatan): void
    {
        $pengajuan->update([
            'status' => 'ditolak',
            'catatan_ketua' => $catatan,
        ]);

        WaService::keAnggota(
            $pengajuan->anggota,
            'limit_ditolak',
            'Mohon maaf, pengajuan limit Anda ditolak.'.($catatan ? " Catatan: {$catatan}" : '')
        );
    }
}
