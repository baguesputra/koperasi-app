<?php

namespace App\Services\Anggota;

use App\Models\Anggota;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ReaktivasiService
{
    /**
     * Aktifkan kembali anggota yang sebelumnya di-resign.
     *
     * PENTING:
     *   - Data historis (simpanan, pinjaman, jurnal kas) TIDAK diubah dan TIDAK dihapus.
     *   - reaktivasi_history_json di-append (bukan replace) untuk audit trail.
     *   - no_anggota tetap sama (menjadi bukti historis keanggotan).
     *   - users.status disinkronkan ke 'aktif'.
     *
     * @throws RuntimeException jika status bukan 'resign' atau tidak ada user terkait.
     */
    public function proses(Anggota $anggota, string $alasan, User $aktor): void
    {
        DB::transaction(function () use ($anggota, $alasan, $aktor) {
            $anggotaLocked = Anggota::lockForUpdate()->findOrFail($anggota->id);

            if ($anggotaLocked->status !== 'resign') {
                throw new RuntimeException(
                    "Anggota {$anggotaLocked->nama} berstatus '{$anggotaLocked->status}', tidak bisa diaktifkan kembali."
                );
            }

            $dataLama = [
                'status' => 'resign',
                'tanggal_resign' => $anggotaLocked->tanggal_resign?->format('Y-m-d'),
                'alasan_resign' => $anggotaLocked->alasan_resign,
                'resigned_by' => $anggotaLocked->resigned_by,
                'resigned_settlement_json' => $anggotaLocked->resigned_settlement_json,
            ];

            // Append ke reaktivasi_history_json (immutable trail).
            $histori = $anggotaLocked->reaktivasi_history_json ?? [];
            $histori[] = [
                'tanggal' => now()->format('Y-m-d'),
                'alasan' => $alasan,
                'oleh' => $aktor->no_karyawan ?? $aktor->name,
                'user_id_aktor' => $aktor->id,
                'resign_sebelumnya' => [
                    'tanggal_resign' => $anggotaLocked->tanggal_resign?->format('Y-m-d'),
                    'alasan_resign' => $anggotaLocked->alasan_resign,
                ],
            ];

            // Update anggota: reset field resign, set status=aktif.
            // Data settlement & histori resign TIDAK dihapus — jadi lampiran audit.
            $anggotaLocked->update([
                'status' => 'aktif',
                'reaktivasi_history_json' => $histori,
            ]);

            // Sinkron users.status=aktif.
            if ($anggotaLocked->user_id) {
                User::where('id', $anggotaLocked->user_id)->update(['status' => 'aktif']);
            }

            // Audit log.
            AuditLog::catat(
                aksi: 'anggota_aktifkan_kembali',
                keterangan: "Aktifkan kembali anggota {$anggotaLocked->nama} ({$anggotaLocked->no_anggota}). ".
                    'Resign sebelumnya: '.($anggotaLocked->tanggal_resign?->format('Y-m-d') ?? '-').
                    ". Alasan reaktivasi: {$alasan}",
                dataLama: $dataLama,
                dataBaru: [
                    'status' => 'aktif',
                    'alasan_reaktivasi' => $alasan,
                    'diaktifkan_oleh' => $aktor->no_karyawan ?? $aktor->name,
                ]
            );
        });
    }
}
