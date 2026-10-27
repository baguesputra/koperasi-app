<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Pengajuan limit pinjaman untuk aplikasi mobile.
 *
 * Mirror: koperasi-mobile src/api/types.ts (PengajuanLimit) + mock
 * toLimit (mock/logic.ts). Tahapan mengikuti alur
 * diajukan -> approved_bendahara -> disetujui.
 */
class PengajuanLimitResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $ditolakBendahara = $this->status === 'ditolak' && ! $this->catatan_ketua;
        $ditolakKetua = $this->status === 'ditolak' && $this->catatan_ketua;

        return [
            'id' => $this->id,
            'anggota_id' => $this->anggota_id,
            'limit_saat_ini' => (float) $this->limit_saat_ini,
            'limit_diminta' => (float) $this->limit_diminta,
            'limit_disetujui_bendahara' => $this->limit_disetujui_bendahara !== null ? (float) $this->limit_disetujui_bendahara : null,
            'limit_disetujui' => $this->limit_disetujui !== null ? (float) $this->limit_disetujui : null,
            'status' => $this->status,
            'keterangan' => $this->keterangan,
            'catatan_bendahara' => $this->catatan_bendahara,
            'catatan_ketua' => $this->catatan_ketua,
            'tanggal_pengajuan' => $this->tanggal_pengajuan?->format('Y-m-d'),
            'anggota' => $this->anggota ? [
                'id' => $this->anggota->id,
                'no_anggota' => $this->anggota->no_anggota,
                'no_karyawan' => $this->anggota->no_karyawan,
                'nama' => $this->anggota->nama,
                'jabatan' => $this->anggota->jabatan,
                'cabang' => $this->anggota->cabang,
                'unit_bisnis' => $this->anggota->unit_bisnis,
                'department' => $this->anggota->department,
                'divisi' => $this->anggota->divisi,
                'no_hp' => $this->anggota->no_hp,
                'status' => $this->anggota->status,
            ] : null,
            'tahapan' => [
                [
                    'label' => 'Pengajuan diterima',
                    'status' => 'done',
                    'tanggal' => $this->tanggal_pengajuan?->format('Y-m-d'),
                    'oleh' => $this->anggota?->nama,
                    'catatan' => $this->keterangan,
                ],
                [
                    'label' => 'Verifikasi Bendahara',
                    'status' => $ditolakBendahara ? 'rejected' : ($this->status === 'diajukan' ? 'current' : 'done'),
                    'tanggal' => $this->catatan_bendahara ? $this->tanggal_pengajuan?->copy()->addDays(1)->format('Y-m-d') : null,
                    'oleh' => $this->catatan_bendahara ? 'Bendahara' : null,
                    'catatan' => $this->catatan_bendahara,
                ],
                [
                    'label' => 'Persetujuan Ketua',
                    'status' => $ditolakKetua ? 'rejected' : ($this->status === 'disetujui' ? 'done' : ($this->status === 'approved_bendahara' ? 'current' : 'pending')),
                    'tanggal' => $this->status === 'disetujui' ? $this->tanggal_pengajuan?->copy()->addDays(2)->format('Y-m-d') : null,
                    'oleh' => $this->status === 'disetujui' ? 'Ketua Koperasi' : null,
                    'catatan' => $this->catatan_ketua,
                ],
            ],
        ];
    }
}
