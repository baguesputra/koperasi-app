<?php

namespace App\Exports;

use App\Exports\Sheets\AuditLogSheet;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AuditLogExport implements Export, WithMultipleSheets
{
    use Exportable;

    private Collection $logs;

    public function __construct(
        Builder|Collection $data
    ) {
        $this->logs = $data instanceof Builder ? $data->with('user')->latest()->get() : $data;
    }

    /**
     * @return array<int, AuditLogSheet>
     */
    public function sheets(): array
    {
        $sheets = [];

        // Sheet 1: Semua Log / Ringkasan Utama
        $sheets[] = new AuditLogSheet('Semua Aktivitas', $this->logs);

        // Kategori sheet pemisah
        $grupEvent = [
            'Pinjaman & Tenor' => function (AuditLog $log) {
                return str_starts_with($log->aksi, 'pinjaman_') || str_starts_with($log->aksi, 'percepatan_');
            },
            'Simpanan & Angsuran' => function (AuditLog $log) {
                return str_starts_with($log->aksi, 'simpanan_') || str_starts_with($log->aksi, 'angsuran_');
            },
            'Kenaikan Limit' => function (AuditLog $log) {
                return str_contains($log->aksi, 'limit') || $log->aksi === 'setujui_pengajuan_limit';
            },
            'Keuangan & Kas' => function (AuditLog $log) {
                return str_starts_with($log->aksi, 'pengeluaran_')
                    || $log->aksi === 'penuhi_pagu_kas_kecil'
                    || $log->aksi === 'pinjaman_talangan_kembali';
            },
            'Keanggotaan & User' => function (AuditLog $log) {
                return str_contains($log->aksi, 'anggota')
                    || str_contains($log->aksi, 'aktivasi')
                    || str_contains($log->aksi, 'pengguna')
                    || str_contains($log->aksi, 'role');
            },
            'Pengaturan Sistem' => function (AuditLog $log) {
                return str_starts_with($log->aksi, 'update_')
                    || str_starts_with($log->aksi, 'tambah_')
                    || str_starts_with($log->aksi, 'hapus_');
            },
            'Autentikasi & Gate' => function (AuditLog $log) {
                return str_starts_with($log->aksi, 'sso_')
                    || str_starts_with($log->aksi, 'sinkron_')
                    || str_starts_with($log->aksi, 'migrasi_');
            },
        ];

        foreach ($grupEvent as $namaSheet => $filterFn) {
            $filtered = $this->logs->filter($filterFn)->values();
            // Jika ada datanya, atau sertakan sheet kosong ber-header agar lengkap
            if ($filtered->isNotEmpty()) {
                $sheets[] = new AuditLogSheet($namaSheet, $filtered);
            }
        }

        return $sheets;
    }
}
