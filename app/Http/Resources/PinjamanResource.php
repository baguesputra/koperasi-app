<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Pinjaman penuh untuk aplikasi mobile.
 *
 * Mirror: koperasi-mobile src/api/types.ts (Pinjaman) + mock toPinjaman
 * (mock/logic.ts). Tahapan mengikuti logika Pinjaman::getVerificationTimeline.
 */
class PinjamanResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $agg = $this->agregatJadwalAktif();
        $jadwal = $this->jadwalAktif();
        $berikutnya = $jadwal->where('status', 'belum_bayar')->sortBy('cicilan_ke')->first();

        return [
            'id' => $this->id,
            'nomor_dokumen' => $this->nomor_dokumen,
            'nominal' => (float) $this->nominal,
            'nominal_diminta' => $this->nominal_diminta !== null ? (float) $this->nominal_diminta : null,
            'tenor_bulan' => (int) $this->tenor_bulan,
            'persentase_bunga' => (float) $this->persentase_bunga,
            'status' => $this->status,
            'keperluan' => $this->keperluan,
            'tanggal_pengajuan' => $this->tanggal_pengajuan?->format('Y-m-d'),
            'tanggal_pencairan' => $this->tanggal_pencairan?->format('Y-m-d'),
            'disetujui_pada' => $this->disetujui_pada?->format('Y-m-d'),
            'catatan_admin' => $this->catatan_admin,
            'catatan_bendahara' => $this->catatan_bendahara,
            'catatan_ketua' => $this->catatan_ketua,
            'nominal_disetujui_bendahara' => $this->nominal_disetujui_bendahara !== null ? (float) $this->nominal_disetujui_bendahara : null,
            'tenor_disetujui_bendahara' => $this->tenor_disetujui_bendahara !== null ? (int) $this->tenor_disetujui_bendahara : null,
            'cair_oleh_bendahara' => (bool) $this->cair_oleh_bendahara,
            'snapshot_bank' => $this->snapshot_bank,
            'snapshot_no_rekening' => $this->snapshot_no_rekening,
            'snapshot_atas_nama' => $this->snapshot_atas_nama,
            'sisa_angsuran' => $agg['sisa'],
            'total_angsuran' => $agg['total'],
            'sisa_total_bayar' => (float) $agg['sisa_bayar'],
            'angsuran_berikutnya' => $berikutnya ? [
                'cicilan_ke' => $berikutnya->cicilan_ke,
                'total_bayar' => (float) $berikutnya->total_bayar,
                'tanggal_jatuh_tempo' => $berikutnya->tanggal_jatuh_tempo ? Carbon::parse($berikutnya->tanggal_jatuh_tempo)->format('Y-m-d') : null,
            ] : null,
            'sudah_pakai_percepatan' => (bool) $this->sudah_pakai_percepatan,
            'angsuran' => $jadwal->map(fn ($a) => [
                'id' => $a->id,
                'pinjaman_id' => $this->id,
                'cicilan_ke' => $a->cicilan_ke,
                'nominal_pokok' => (float) $a->nominal_pokok,
                'nominal_bunga' => (float) $a->nominal_bunga,
                'total_bayar' => (float) $a->total_bayar,
                'status' => $a->status,
                'tanggal_jatuh_tempo' => $a->tanggal_jatuh_tempo ? Carbon::parse($a->tanggal_jatuh_tempo)->format('Y-m-d') : null,
                'tanggal_konfirmasi_bayar' => $a->tanggal_konfirmasi_bayar ? Carbon::parse($a->tanggal_konfirmasi_bayar)->format('Y-m-d') : null,
            ])->values()->all(),
            'tahapan' => $this->tahapan(),
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
            'pengaju' => $this->pengaju ? [
                'nama' => $this->pengaju->name,
                'peran' => $this->pengaju->getRoleNames()->first(),
            ] : null,
        ];
    }

    /**
     * Tahapan verifikasi — mirror logika Pinjaman::getVerificationTimeline
     * dengan bentuk field kontrak mobile (label/status/tanggal/oleh/catatan).
     *
     * @return array<int, array<string, mixed>>
     */
    private function tahapan(): array
    {
        $ditolak = $this->status === 'ditolak';
        // Catatan 'Diajukan mandiri oleh Bendahara' adalah penanda awal, bukan keputusan.
        $adaKeputusanBendahara = ! empty($this->catatan_bendahara)
            && $this->catatan_bendahara !== 'Diajukan mandiri oleh Bendahara';
        $ditolakAdmin = $ditolak && ! empty($this->catatan_admin) && ! $adaKeputusanBendahara && empty($this->catatan_ketua);
        $ditolakBendahara = $ditolak && $adaKeputusanBendahara && empty($this->catatan_ketua);
        $ditolakKetua = $ditolak && ! empty($this->catatan_ketua);
        $adminDone = in_array($this->status, ['approved_admin', 'approved_bendahara', 'approved_ketua', 'aktif', 'lunas'], true)
            || $ditolakBendahara || $ditolakKetua;
        $bendaharaDone = in_array($this->status, ['approved_bendahara', 'approved_ketua', 'aktif', 'lunas'], true) || $ditolakKetua;
        $ketuaDone = in_array($this->status, ['approved_ketua', 'aktif', 'lunas'], true);

        return [
            [
                'label' => 'Pengajuan diterima',
                'status' => 'done',
                'tanggal' => $this->tanggal_pengajuan?->format('Y-m-d'),
                'oleh' => $this->pengaju?->name,
                'catatan' => $this->keperluan,
            ],
            [
                'label' => 'Verifikasi Admin',
                'status' => $ditolakAdmin ? 'rejected' : ($adminDone ? 'done' : 'current'),
                'tanggal' => null,
                'oleh' => $this->catatan_admin ? 'Admin Koperasi' : null,
                'catatan' => $this->catatan_admin,
            ],
            [
                'label' => 'Verifikasi Bendahara',
                'status' => $ditolakBendahara ? 'rejected' : ($bendaharaDone ? 'done' : ($adminDone ? 'current' : 'pending')),
                'tanggal' => null,
                'oleh' => $adaKeputusanBendahara ? 'Bendahara' : null,
                'catatan' => $adaKeputusanBendahara ? $this->catatan_bendahara : null,
            ],
            [
                'label' => $this->cair_oleh_bendahara ? 'Pencairan Bendahara' : 'Persetujuan Ketua',
                'status' => $ditolakKetua ? 'rejected' : ($ketuaDone ? 'done' : ($bendaharaDone ? 'current' : 'pending')),
                'tanggal' => $this->disetujui_pada?->format('Y-m-d'),
                'oleh' => $ketuaDone ? ($this->cair_oleh_bendahara ? 'Bendahara' : 'Ketua Koperasi') : null,
                'catatan' => $this->catatan_ketua,
            ],
        ];
    }
}
