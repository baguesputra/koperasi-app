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
            $anggotaLocked = Anggota::with('user')->lockForUpdate()->findOrFail($anggota->id);

            $sudahAda = RekeningTabungan::where('anggota_id', $anggotaLocked->id)
                ->whereIn('status', [...RekeningTabungan::STATUS_MENUNGGU, 'aktif'])
                ->exists();

            if ($sudahAda) {
                throw new RuntimeException('Anda sudah memiliki rekening tabungan aktif atau pengajuan yang sedang diproses.');
            }

            $tarifSaatIni = SettingBungaTabungan::persentaseAktif();
            $versiSyarat = (string) config('syarat_tabungan.versi', 'v1.0-2026-11-23');
            $mandiriSekretaris = $anggotaLocked->user?->hasRole('sekretaris')
                && ! $anggotaLocked->user->hasAnyRole(['bendahara', 'ketua_koperasi']);

            $rekening = RekeningTabungan::create([
                'anggota_id' => $anggotaLocked->id,
                'status' => $mandiriSekretaris ? 'approved_admin' : 'diajukan',
                'saldo' => 0,
                'suku_bunga_saat_buka' => $tarifSaatIni,
                'versi_syarat' => $versiSyarat,
                'setuju_syarat' => true,
                'tanggal_pengajuan' => now()->toDateString(),
                'disetujui_admin_pada' => $mandiriSekretaris ? now() : null,
                'catatan_admin' => $mandiriSekretaris ? 'Diajukan mandiri oleh Sekretaris' : null,
            ]);

            AuditLog::catat(
                aksi: 'tabungan_rekening_diajukan',
                keterangan: "Pengajuan pembukaan rekening tabungan oleh {$anggotaLocked->nama} ({$anggotaLocked->no_karyawan})."
                    .($mandiriSekretaris ? ' (pengajuan mandiri Sekretaris: langsung ke Bendahara).' : ''),
                dataBaru: [
                    'rekening_tabungan_id' => $rekening->id,
                    'anggota_id' => $anggotaLocked->id,
                    'status' => $rekening->status,
                    'suku_bunga_saat_buka' => $tarifSaatIni,
                    'versi_syarat' => $versiSyarat,
                ],
                userId: $aktorId ?? $anggotaLocked->user_id,
            );

            return $rekening;
        });
    }

    public function approveAdminBuka(RekeningTabungan $rekening, ?string $catatan, int $aktorId): RekeningTabungan
    {
        return DB::transaction(function () use ($rekening, $catatan, $aktorId) {
            $rekLocked = RekeningTabungan::lockForUpdate()->findOrFail($rekening->id);
            $rekLocked->loadMissing('anggota.user');
            $this->tolakBilaSelfApproval($rekLocked, $aktorId);

            if ($rekLocked->status !== 'diajukan') {
                throw new RuntimeException('Hanya pengajuan berstatus Diajukan yang dapat diverifikasi Sekretaris.');
            }

            if ($rekLocked->anggota?->status !== 'aktif') {
                throw new RuntimeException("Anggota {$rekLocked->anggota?->nama} tidak berstatus aktif.");
            }

            $mandiriBendahara = $rekLocked->anggota?->user?->hasRole('bendahara')
                && ! $rekLocked->anggota->user->hasRole('ketua_koperasi');
            $catatanBersih = $catatan ? trim($catatan) : null;
            $update = [
                'status' => $mandiriBendahara ? 'approved_bendahara' : 'approved_admin',
                'admin_oleh' => $aktorId,
                'disetujui_admin_pada' => now(),
                'catatan_admin' => $catatanBersih,
            ];

            if ($mandiriBendahara) {
                $update['disetujui_bendahara_pada'] = now();
                $update['catatan_bendahara'] = 'Diajukan mandiri oleh Bendahara';
            }

            $rekLocked->update($update);

            AuditLog::catat(
                aksi: 'tabungan_rekening_disetujui_admin',
                keterangan: "Verifikasi Sekretaris pembukaan rekening tabungan {$rekLocked->anggota?->nama} selesai, diteruskan ke "
                    .($mandiriBendahara ? 'Ketua Koperasi (tahap Bendahara dilewati agar tak self-approve).' : 'Bendahara.'),
                dataLama: ['status' => 'diajukan'],
                dataBaru: [
                    'rekening_tabungan_id' => $rekLocked->id,
                    'status' => $update['status'],
                    'catatan_admin' => $catatanBersih,
                ],
                userId: $aktorId,
            );

            return $rekLocked->refresh();
        });
    }

    public function approveBendaharaBuka(RekeningTabungan $rekening, ?string $catatan, int $aktorId): RekeningTabungan
    {
        return DB::transaction(function () use ($rekening, $catatan, $aktorId) {
            $rekLocked = RekeningTabungan::lockForUpdate()->findOrFail($rekening->id);
            $rekLocked->loadMissing('anggota.user');
            $this->tolakBilaSelfApproval($rekLocked, $aktorId);

            if ($rekLocked->status !== 'approved_admin') {
                throw new RuntimeException('Hanya pengajuan yang sudah lolos Sekretaris yang dapat ditinjau Bendahara.');
            }

            if ($rekLocked->anggota?->status !== 'aktif') {
                throw new RuntimeException("Anggota {$rekLocked->anggota?->nama} tidak berstatus aktif.");
            }

            $catatanBersih = $catatan ? trim($catatan) : null;
            $mandiriKetua = $rekLocked->anggota?->user?->hasRole('ketua_koperasi') ?? false;

            if ($mandiriKetua) {
                $anggota = Anggota::lockForUpdate()->findOrFail($rekLocked->anggota_id);
                $sudahPunyaAktif = RekeningTabungan::where('anggota_id', $anggota->id)
                    ->where('status', 'aktif')
                    ->where('id', '!=', $rekLocked->id)
                    ->exists();
                if ($sudahPunyaAktif) {
                    throw new RuntimeException("Anggota {$anggota->nama} sudah memiliki rekening tabungan aktif lain.");
                }

                $noRekening = $this->penomoran->rekeningTabunganBerikutnya(now());
                $tarifBerlaku = SettingBungaTabungan::persentaseAktif();
                $catatanKetuaAuto = 'Disetujui tanpa self-approval Ketua (diverifikasi Sekretaris & Bendahara)';

                $rekLocked->update([
                    'no_rekening' => $noRekening,
                    'status' => 'aktif',
                    'suku_bunga_saat_buka' => $tarifBerlaku,
                    'tanggal_buka' => now()->toDateString(),
                    'bendahara_oleh' => $aktorId,
                    'disetujui_bendahara_pada' => now(),
                    'catatan_bendahara' => $catatanBersih,
                    'disetujui_ketua_pada' => now(),
                    'catatan_ketua' => $catatanKetuaAuto,
                    'diproses_oleh' => $aktorId,
                    'catatan_pengurus' => $catatanBersih,
                ]);

                AuditLog::catat(
                    aksi: 'tabungan_rekening_disetujui',
                    keterangan: "Pembukaan rekening tabungan Ketua ({$anggota->nama}) disetujui langsung melalui verifikasi Bendahara dan diaktifkan ({$noRekening}).",
                    dataLama: ['status' => 'approved_admin'],
                    dataBaru: [
                        'rekening_tabungan_id' => $rekLocked->id,
                        'no_rekening' => $noRekening,
                        'status' => 'aktif',
                        'suku_bunga_saat_buka' => $tarifBerlaku,
                        'catatan_bendahara' => $catatanBersih,
                    ],
                    userId: $aktorId,
                );

                return $rekLocked->refresh();
            }

            $rekLocked->update([
                'status' => 'approved_bendahara',
                'bendahara_oleh' => $aktorId,
                'disetujui_bendahara_pada' => now(),
                'catatan_bendahara' => $catatanBersih,
            ]);

            AuditLog::catat(
                aksi: 'tabungan_rekening_disetujui_bendahara',
                keterangan: "Tinjauan Bendahara pembukaan rekening tabungan {$rekLocked->anggota?->nama} disetujui, diteruskan ke Ketua Koperasi.",
                dataLama: ['status' => 'approved_admin'],
                dataBaru: [
                    'rekening_tabungan_id' => $rekLocked->id,
                    'status' => 'approved_bendahara',
                    'catatan_bendahara' => $catatanBersih,
                ],
                userId: $aktorId,
            );

            return $rekLocked->refresh();
        });
    }

    public function approveKetuaBuka(RekeningTabungan $rekening, ?string $catatan, int $aktorId): RekeningTabungan
    {
        return DB::transaction(function () use ($rekening, $catatan, $aktorId) {
            $rekLocked = RekeningTabungan::lockForUpdate()->findOrFail($rekening->id);
            $anggota = Anggota::lockForUpdate()->findOrFail($rekLocked->anggota_id);
            $rekLocked->setRelation('anggota', $anggota);
            $this->tolakBilaSelfApproval($rekLocked, $aktorId);

            if ($rekLocked->status !== 'approved_bendahara') {
                throw new RuntimeException('Hanya pengajuan yang sudah ditinjau Bendahara yang dapat disetujui Ketua Koperasi.');
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
            $catatanBersih = $catatan ? trim($catatan) : null;

            $rekLocked->update([
                'no_rekening' => $noRekening,
                'status' => 'aktif',
                'suku_bunga_saat_buka' => $tarifBerlaku,
                'tanggal_buka' => now()->toDateString(),
                'ketua_oleh' => $aktorId,
                'disetujui_ketua_pada' => now(),
                'catatan_ketua' => $catatanBersih,
                'diproses_oleh' => $aktorId,
                'catatan_pengurus' => $catatanBersih,
            ]);

            AuditLog::catat(
                aksi: 'tabungan_rekening_disetujui',
                keterangan: "Pembukaan rekening tabungan {$anggota->nama} disetujui Ketua dan diaktifkan ({$noRekening}).",
                dataLama: ['status' => 'approved_bendahara'],
                dataBaru: [
                    'rekening_tabungan_id' => $rekLocked->id,
                    'no_rekening' => $noRekening,
                    'status' => 'aktif',
                    'suku_bunga_saat_buka' => $tarifBerlaku,
                    'catatan_ketua' => $catatanBersih,
                ],
                userId: $aktorId,
            );

            return $rekLocked->refresh();
        });
    }

    public function tolakBuka(RekeningTabungan $rekening, string $catatan, int $aktorId, ?string $tahap = null): RekeningTabungan
    {
        $catatan = trim($catatan);
        if (mb_strlen($catatan) < 3) {
            throw new RuntimeException('Alasan penolakan minimal 3 karakter.');
        }

        return DB::transaction(function () use ($rekening, $catatan, $aktorId, $tahap) {
            $rekLocked = RekeningTabungan::lockForUpdate()->findOrFail($rekening->id);
            $rekLocked->loadMissing('anggota');
            $this->tolakBilaSelfApproval($rekLocked, $aktorId);

            $statusDiharapkan = match ($tahap) {
                'admin' => 'diajukan',
                'bendahara' => 'approved_admin',
                'ketua' => 'approved_bendahara',
                default => null,
            };

            if ($statusDiharapkan !== null && $rekLocked->status !== $statusDiharapkan) {
                throw new RuntimeException('Status pengajuan pembukaan rekening tidak sesuai dengan tahap ini.');
            }

            if (! in_array($rekLocked->status, RekeningTabungan::STATUS_MENUNGGU, true)) {
                throw new RuntimeException('Pengajuan pembukaan rekening ini sudah diproses.');
            }

            $statusLama = $rekLocked->status;
            $update = [
                'status' => 'ditolak',
                'diproses_oleh' => $aktorId,
                'catatan_pengurus' => $catatan,
            ];

            if ($statusLama === 'diajukan') {
                $update['admin_oleh'] = $aktorId;
                $update['catatan_admin'] = $catatan;
            } elseif ($statusLama === 'approved_admin') {
                $update['bendahara_oleh'] = $aktorId;
                $update['catatan_bendahara'] = $catatan;
            } elseif ($statusLama === 'approved_bendahara') {
                $update['ketua_oleh'] = $aktorId;
                $update['catatan_ketua'] = $catatan;
            }

            $rekLocked->update($update);

            AuditLog::catat(
                aksi: 'tabungan_rekening_ditolak',
                keterangan: "Pengajuan pembukaan rekening tabungan {$rekLocked->anggota?->nama} ditolak: {$catatan}",
                dataLama: ['status' => $statusLama],
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

    private function tolakBilaSelfApproval(RekeningTabungan $rekening, int $aktorId): void
    {
        if ((int) $rekening->anggota?->user_id === $aktorId) {
            throw new RuntimeException('Anda tidak diperbolehkan memproses pengajuan pembukaan rekening milik Anda sendiri.');
        }
    }
}
