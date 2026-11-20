<?php

namespace App\Http\Resources;

use App\Helpers\TerbilangHelper;
use App\Models\KasKoperasi;
use App\Services\Pinjaman\EligibilitasPinjamanService;
use App\Services\Pinjaman\PerhitunganBungaService;
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

        $sedangReview = in_array($this->status, ['diajukan', 'approved_admin', 'approved_bendahara'], true);
        $eligibilitas = app(EligibilitasPinjamanService::class);

        $barisBeban = $jadwal->isNotEmpty()
            ? $jadwal->map(fn ($a) => [
                'nominal_pokok' => (float) $a->nominal_pokok,
                'nominal_bunga' => (float) $a->nominal_bunga,
                'total_bayar' => (float) $a->total_bayar,
            ])->values()->all()
            : app(PerhitunganBungaService::class)->buatJadwal(
                (float) $this->nominal,
                max(1, (int) $this->tenor_bulan),
                (float) $this->persentase_bunga
            );

        $totalPokokBeban = array_sum(array_column($barisBeban, 'nominal_pokok'));
        $totalBungaBeban = array_sum(array_column($barisBeban, 'nominal_bunga'));
        $totalAngsuranBeban = array_sum(array_column($barisBeban, 'total_bayar'));
        $jumlahCicilanBeban = count($barisBeban);

        $tglAnggota = $this->anggota?->tanggal_jadi_anggota ?? $this->anggota?->tanggal_mulai_kerja;
        $lamaKeanggotaanTahun = $tglAnggota ? round($tglAnggota->diffInDays(now()) / 365, 1) : 0.0;
        $saldoBankSekarang = $sedangReview ? (float) (KasKoperasi::query()->value('saldo_bank') ?? 0) : null;

        return [
            'id' => $this->id,
            'nomor_dokumen' => $this->nomor_dokumen,
            'nominal' => (float) $this->nominal,
            'terbilang' => TerbilangHelper::angkaKeTerbilang((float) $this->nominal),
            'nominal_diminta' => $this->nominal_diminta !== null ? (float) $this->nominal_diminta : null,
            'tenor_diminta' => $this->tenor_diminta !== null ? (int) $this->tenor_diminta : (int) $this->tenor_bulan,
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
            'nominal_disetujui' => $this->nominal_disetujui !== null ? (float) $this->nominal_disetujui : null,
            'tenor_disetujui' => $this->tenor_disetujui !== null ? (int) $this->tenor_disetujui : null,
            'limit_tersedia' => ($this->anggota && $sedangReview) ? (float) $eligibilitas->limitTersedia($this->anggota) : null,
            'tenor_maksimal_diminta' => $eligibilitas->tenorMaksimal((float) ($this->nominal_diminta ?? $this->nominal)),
            'sudah_pakai_privilege_reloan' => (bool) ($this->sudah_pakai_privilege_reloan ?? false),
            'cair_oleh_bendahara' => (bool) $this->cair_oleh_bendahara,
            'snapshot_bank' => $this->snapshot_bank,
            'snapshot_no_rekening' => $this->snapshot_no_rekening,
            'snapshot_atas_nama' => $this->snapshot_atas_nama,
            'kas' => [
                'saldo_sekarang' => $saldoBankSekarang,
                'saldo_bendahara' => $this->kas_saldo_bendahara !== null ? (float) $this->kas_saldo_bendahara : null,
                'saldo_ketua' => $this->kas_saldo_ketua !== null ? (float) $this->kas_saldo_ketua : null,
                'sisa_ketua' => $this->kas_sisa_ketua !== null ? (float) $this->kas_sisa_ketua : null,
            ],
            'beban_angsuran' => [
                'cicilan_pertama' => (float) ($barisBeban[0]['total_bayar'] ?? 0),
                'cicilan_terakhir' => (float) ($jumlahCicilanBeban > 0 ? $barisBeban[$jumlahCicilanBeban - 1]['total_bayar'] : 0),
                'cicilan_rata_rata' => $jumlahCicilanBeban > 0 ? round($totalAngsuranBeban / $jumlahCicilanBeban, 2) : 0.0,
                'total_pokok_angsuran' => (float) $totalPokokBeban,
                'total_bunga_angsuran' => (float) $totalBungaBeban,
                'total_angsuran' => (float) $totalAngsuranBeban,
            ],
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
                'foto_url' => $this->anggota->foto_url,
                'lama_keanggotaan_tahun' => $lamaKeanggotaanTahun,
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
                'label' => 'Verifikasi Sekretaris',
                'status' => $ditolakAdmin ? 'rejected' : ($adminDone ? 'done' : 'current'),
                'tanggal' => null,
                'oleh' => $this->catatan_admin ? 'Sekretaris Koperasi' : null,
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
