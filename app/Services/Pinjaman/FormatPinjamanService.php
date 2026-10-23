<?php

namespace App\Services\Pinjaman;

use App\Helpers\TerbilangHelper;
use App\Models\KasKoperasi;

/**
 * Format pinjaman lengkap untuk halaman approval (Admin/Bendahara/Ketua).
 * Satu sumber agar ketiga halaman menampilkan data identik.
 */
class FormatPinjamanService
{
    private static ?float $saldoBankCache = null;

    public static function resetCache(): void
    {
        self::$saldoBankCache = null;
    }

    public function __construct(
        private PerhitunganBungaService $bunga,
        private EligibilitasPinjamanService $eligibilitas,
    ) {}

    private function ambilSaldoBank(): float
    {
        return self::$saldoBankCache ??= (float) (KasKoperasi::query()->value('saldo_bank') ?? 0);
    }

    public function format($p, bool $withLimit = true): array
    {
        $jadwal = $this->bunga->buatJadwal(
            (float) $p->nominal,
            $p->tenor_bulan,
            (float) $p->persentase_bunga
        );
        $totalPokok = array_sum(array_column($jadwal, 'nominal_pokok'));
        $totalBunga = array_sum(array_column($jadwal, 'nominal_bunga'));
        $totalAngsuran = array_sum(array_column($jadwal, 'total_bayar'));
        $cicilanPertama = $jadwal[0]['total_bayar'] ?? 0;
        $cicilanTerakhir = ! empty($jadwal) ? end($jadwal)['total_bayar'] : 0;
        $cicilanRataRata = count($jadwal) > 0 ? round($totalAngsuran / count($jadwal), 2) : 0;
        $pokokPerBulan = $jadwal[0]['nominal_pokok'] ?? 0;
        $bungaPertama = $jadwal[0]['nominal_bunga'] ?? 0;

        $saldoBank = $this->ambilSaldoBank();
        $limitTersedia = $withLimit ? (float) $this->eligibilitas->limitTersedia($p->anggota) : 0.0;
        $tenorMaks = $this->eligibilitas->tenorMaksimal((float) ($p->nominal_diminta ?? $p->nominal));

        return [
            'id' => $p->id,
            'nominal' => (float) $p->nominal,
            'terbilang' => TerbilangHelper::angkaKeTerbilang($p->nominal),
            'tenor_bulan' => $p->tenor_bulan,
            'nominal_diminta' => $p->nominal_diminta !== null ? (float) $p->nominal_diminta : (float) $p->nominal,
            'tenor_diminta' => $p->tenor_diminta ?? $p->tenor_bulan,
            'nominal_disetujui_bendahara' => $p->nominal_disetujui_bendahara !== null ? (float) $p->nominal_disetujui_bendahara : null,
            'tenor_disetujui_bendahara' => $p->tenor_disetujui_bendahara,
            'nominal_disetujui' => $p->nominal_disetujui !== null ? (float) $p->nominal_disetujui : null,
            'tenor_disetujui' => $p->tenor_disetujui,
            'kas' => [
                'saldo_bendahara' => $p->kas_saldo_bendahara !== null ? (float) $p->kas_saldo_bendahara : null,
                'saldo_ketua' => $p->kas_saldo_ketua !== null ? (float) $p->kas_saldo_ketua : null,
                'sisa_ketua' => $p->kas_sisa_ketua !== null ? (float) $p->kas_sisa_ketua : null,
                'saldo_bank' => $saldoBank,
                'saldo_sekarang' => $saldoBank,
            ],
            'limit_tersedia' => $limitTersedia,
            'tenor_maksimal_diminta' => $tenorMaks,
            'persentase_bunga' => (float) $p->persentase_bunga,
            'keperluan' => $p->keperluan,
            'rekening' => [
                'bank' => $p->snapshot_bank,
                'no_rekening' => $p->snapshot_no_rekening,
                'atas_nama' => $p->snapshot_atas_nama,
            ],
            'status' => $p->status,
            'mandiri_bendahara' => $p->catatan_bendahara === 'Diajukan mandiri oleh Bendahara',
            'cair_oleh_bendahara' => (bool) $p->cair_oleh_bendahara,
            'tanggal_pengajuan' => $p->tanggal_pengajuan->format('d M Y'),
            'sudah_pakai_privilege_reloan' => $p->sudah_pakai_privilege_reloan,
            'catatan_admin' => $p->catatan_admin,
            'catatan_bendahara' => $p->catatan_bendahara,
            'jadwal_angsuran' => $jadwal,
            'total_angsuran' => $totalAngsuran,
            'total_pokok_angsuran' => $totalPokok,
            'total_bunga_angsuran' => $totalBunga,
            'beban_angsuran' => [
                'cicilan_pertama' => $cicilanPertama,
                'cicilan_terakhir' => $cicilanTerakhir,
                'cicilan_rata_rata' => $cicilanRataRata,
                'pokok_per_bulan' => $pokokPerBulan,
                'bunga_pertama' => $bungaPertama,
                'total_pokok' => $totalPokok,
                'total_bunga' => $totalBunga,
                'total_angsuran' => $totalAngsuran,
            ],
            'anggota' => [
                'nama' => $p->anggota->nama,
                'no_anggota' => $p->anggota->no_anggota,
                'no_karyawan' => $p->anggota->no_karyawan,
                'cabang' => $p->anggota->cabang,
                'foto_url' => $p->anggota->foto_url,
                'jabatan' => $p->anggota->jabatan,
                'lama_keanggotaan_tahun' => round($p->anggota->lama_keanggotaan_tahun, 1),
            ],
        ];
    }
}
