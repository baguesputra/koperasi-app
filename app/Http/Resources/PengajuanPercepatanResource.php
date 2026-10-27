<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Pengajuan percepatan/perpanjangan tenor untuk aplikasi mobile.
 *
 * Mirror: koperasi-mobile src/api/types.ts (PengajuanPercepatan) + mock
 * toPercepatan (mock/logic.ts). Tahapan mengikuti alur
 * diajukan -> approved_bendahara -> aktif.
 */
class PengajuanPercepatanResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $ditolakBendahara = $this->status === 'ditolak' && ! $this->catatan_ketua;
        $ditolakKetua = $this->status === 'ditolak' && $this->catatan_ketua;
        $namaAnggota = $this->pinjaman?->anggota?->nama;

        return [
            'id' => $this->id,
            'pinjaman_id' => $this->pinjaman_id,
            'pinjaman_nominal' => (float) ($this->pinjaman->nominal ?? 0),
            'pinjaman_tenor' => (int) ($this->pinjaman->tenor_bulan ?? 0),
            'tipe' => $this->tipe,
            'tenor_lama' => (int) $this->tenor_lama,
            'tenor_baru' => $this->tenor_baru !== null ? (int) $this->tenor_baru : null,
            'keterangan' => $this->keterangan,
            'status' => $this->status,
            'catatan_bendahara' => $this->catatan_bendahara,
            'catatan_ketua' => $this->catatan_ketua,
            'tanggal_pengajuan' => $this->tanggal_pengajuan?->format('Y-m-d'),
            'berlaku_mulai' => $this->bulan_berlaku,
            'anggota' => $this->pinjaman?->anggota ? [
                'id' => $this->pinjaman->anggota->id,
                'no_anggota' => $this->pinjaman->anggota->no_anggota,
                'no_karyawan' => $this->pinjaman->anggota->no_karyawan,
                'nama' => $this->pinjaman->anggota->nama,
                'jabatan' => $this->pinjaman->anggota->jabatan,
                'cabang' => $this->pinjaman->anggota->cabang,
                'unit_bisnis' => $this->pinjaman->anggota->unit_bisnis,
                'department' => $this->pinjaman->anggota->department,
                'divisi' => $this->pinjaman->anggota->divisi,
                'no_hp' => $this->pinjaman->anggota->no_hp,
                'status' => $this->pinjaman->anggota->status,
            ] : null,
            'tahapan' => [
                [
                    'label' => 'Pengajuan diterima',
                    'status' => 'done',
                    'tanggal' => $this->tanggal_pengajuan?->format('Y-m-d'),
                    'oleh' => $namaAnggota,
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
                    'status' => $ditolakKetua ? 'rejected' : ($this->status === 'aktif' ? 'done' : ($this->status === 'approved_bendahara' ? 'current' : 'pending')),
                    'tanggal' => $this->status === 'aktif' ? $this->tanggal_pengajuan?->copy()->addDays(2)->format('Y-m-d') : null,
                    'oleh' => $this->status === 'aktif' ? 'Ketua Koperasi' : null,
                    'catatan' => $this->catatan_ketua,
                ],
            ],
        ];
    }
}
