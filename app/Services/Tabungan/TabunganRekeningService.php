<?php

namespace App\Services\Tabungan;

use App\Models\Anggota;
use App\Models\AuditLog;
use App\Models\RekeningTabungan;
use App\Models\SettingBungaTabungan;
use App\Models\SettingTabungan;
use App\Services\Dokumen\PenomoranDokumenService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TabunganRekeningService
{
    public function __construct(private PenomoranDokumenService $penomoran) {}

    public function ajukanBuka(Anggota $anggota, bool $setujuSyarat, ?int $aktorId = null): RekeningTabungan
    {
        $setting = SettingTabungan::current();
        if (! $setting->buka_rekening_aktif) {
            throw new RuntimeException('Pembukaan rekening tabungan baru sedang ditutup sementara oleh pengurus.');
        }

        if ($anggota->status !== 'aktif') {
            throw new RuntimeException('Hanya anggota berstatus aktif yang dapat membuka tabungan.');
        }

        if (! $setujuSyarat) {
            throw new RuntimeException('Persetujuan ketentuan tabungan wajib dicentang.');
        }

        return DB::transaction(function () use ($anggota, $aktorId) {
            $anggotaLocked = Anggota::lockForUpdate()->findOrFail($anggota->id);

            $sudahAda = RekeningTabungan::where('anggota_id', $anggotaLocked->id)
                ->whereIn('status', ['diajukan', 'aktif'])
                ->exists();

            if ($sudahAda) {
                throw new RuntimeException('Anda sudah memiliki rekening tabungan aktif atau pengajuan yang sedang diproses.');
            }

            $tarifSaatIni = SettingBungaTabungan::persentaseAktif();
            $versiSyarat = (string) config('syarat_tabungan.versi', 'v1.0-2026-11-23');

            $rekening = RekeningTabungan::create([
                'anggota_id' => $anggotaLocked->id,
                'status' => 'diajukan',
                'saldo' => 0,
                'suku_bunga_saat_buka' => $tarifSaatIni,
                'versi_syarat' => $versiSyarat,
                'setuju_syarat' => true,
                'tanggal_pengajuan' => now()->toDateString(),
            ]);

            AuditLog::catat(
                aksi: 'tabungan_rekening_diajukan',
                keterangan: "Pengajuan pembukaan rekening tabungan oleh {$anggotaLocked->nama} ({$anggotaLocked->no_karyawan}).",
                dataBaru: [
                    'rekening_tabungan_id' => $rekening->id,
                    'anggota_id' => $anggotaLocked->id,
                    'suku_bunga_saat_buka' => $tarifSaatIni,
                    'versi_syarat' => $versiSyarat,
                ],
                userId: $aktorId ?? $anggotaLocked->user_id,
            );

            return $rekening;
        });
    }

    public function setujuiBuka(RekeningTabungan $rekening, ?string $catatan, int $aktorId): RekeningTabungan
    {
        return DB::transaction(function () use ($rekening, $catatan, $aktorId) {
            $rekLocked = RekeningTabungan::lockForUpdate()->findOrFail($rekening->id);
            $anggota = Anggota::lockForUpdate()->findOrFail($rekLocked->anggota_id);

            if ($rekLocked->status !== 'diajukan') {
                throw new RuntimeException('Hanya pengajuan berstatus Diajukan yang dapat disetujui.');
            }

            if ($anggota->status !== 'aktif') {
                throw new RuntimeException("Anggota {$anggota->nama} tidak berstatus aktif.");
            }

            $sudahPunyaAktif = RekeningTabungan::where('anggota_id', $anggota->id)
                ->where('status', 'aktif')
                ->where('id', '!=', $rekLocked->id)
                ->exists();

            if ($sudahPunyaAktif) {
                throw new RuntimeException("Anggota {$anggota->nama} sudah memiliki rekening tabungan aktif lain.");
            }

            $noRekening = $this->penomoran->rekeningTabunganBerikutnya(now());
            $tarifBerlaku = SettingBungaTabungan::persentaseAktif();

            $rekLocked->update([
                'no_rekening' => $noRekening,
                'status' => 'aktif',
                'suku_bunga_saat_buka' => $tarifBerlaku,
                'tanggal_buka' => now()->toDateString(),
                'diproses_oleh' => $aktorId,
                'catatan_pengurus' => $catatan ? trim($catatan) : null,
            ]);

            AuditLog::catat(
                aksi: 'tabungan_rekening_disetujui',
                keterangan: "Pembukaan rekening tabungan {$anggota->nama} disetujui ({$noRekening}).",
                dataLama: ['status' => 'diajukan'],
                dataBaru: [
                    'rekening_tabungan_id' => $rekLocked->id,
                    'no_rekening' => $noRekening,
                    'status' => 'aktif',
                    'suku_bunga_saat_buka' => $tarifBerlaku,
                    'catatan_pengurus' => $catatan,
                ],
                userId: $aktorId,
            );

            return $rekLocked->refresh();
        });
    }

    public function tolakBuka(RekeningTabungan $rekening, string $catatan, int $aktorId): RekeningTabungan
    {
        $catatan = trim($catatan);
        if (mb_strlen($catatan) < 3) {
            throw new RuntimeException('Alasan penolakan minimal 3 karakter.');
        }

        return DB::transaction(function () use ($rekening, $catatan, $aktorId) {
            $rekLocked = RekeningTabungan::lockForUpdate()->findOrFail($rekening->id);
            $rekLocked->loadMissing('anggota');

            if ($rekLocked->status !== 'diajukan') {
                throw new RuntimeException('Hanya pengajuan berstatus Diajukan yang dapat ditolak.');
            }

            $rekLocked->update([
                'status' => 'ditolak',
                'diproses_oleh' => $aktorId,
                'catatan_pengurus' => $catatan,
            ]);

            AuditLog::catat(
                aksi: 'tabungan_rekening_ditolak',
                keterangan: "Pengajuan pembukaan rekening tabungan {$rekLocked->anggota?->nama} ditolak: {$catatan}",
                dataLama: ['status' => 'diajukan'],
                dataBaru: [
                    'rekening_tabungan_id' => $rekLocked->id,
                    'status' => 'ditolak',
                    'catatan_pengurus' => $catatan,
                ],
                userId: $aktorId,
            );

            return $rekLocked->refresh();
        });
    }
}
