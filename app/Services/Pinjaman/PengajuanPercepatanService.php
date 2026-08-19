<?php

namespace App\Services\Pinjaman;

use App\Models\AngsuranPercepatan;
use App\Models\PengajuanPercepatan;
use App\Models\Pinjaman;
use App\Services\Keuangan\JurnalKasService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PengajuanPercepatanService
{
    public function __construct(
        private EligibilitasPinjamanService $eligibilitas,
        private JurnalKasService $jurnalKas,
    ) {}

    public function ajukan(Pinjaman $pinjaman, string $tipe, ?int $tenorBaru, string $keterangan): PengajuanPercepatan
    {
        return DB::transaction(function () use ($pinjaman, $tipe, $tenorBaru, $keterangan) {
            $pinjaman = Pinjaman::with('angsuran')->whereKey($pinjaman->id)->lockForUpdate()->firstOrFail();

            if ($pinjaman->status !== 'aktif') {
                throw new RuntimeException('Percepatan hanya bisa diajukan untuk pinjaman aktif.');
            }

            if ($pinjaman->pengajuanPercepatan()->exists()) {
                throw new RuntimeException('Pinjaman ini sudah pernah memakai pengajuan percepatan.');
            }

            $sisaPokok = $this->sisaPokok($pinjaman);
            if ($sisaPokok <= 0) {
                throw new RuntimeException('Pinjaman ini tidak memiliki sisa pokok.');
            }

            if ($tipe === 'ubah_tenor') {
                $this->validasiTenorBaru($pinjaman, $tenorBaru);
            } else {
                $tenorBaru = null;
            }

            return PengajuanPercepatan::create([
                'pinjaman_id' => $pinjaman->id,
                'tipe' => $tipe,
                'tenor_lama' => $pinjaman->tenor_bulan,
                'tenor_baru' => $tenorBaru,
                'sisa_pokok_saat_ajukan' => $sisaPokok,
                'nominal_final' => $tipe === 'lunas_total' ? $this->totalLunas($sisaPokok, (float) $pinjaman->persentase_bunga) : null,
                'keterangan' => $keterangan,
                'status' => 'diajukan',
                'tanggal_pengajuan' => now(),
            ]);
        });
    }

    public function preview(Pinjaman $pinjaman, string $tipe, ?int $tenorBaru): array
    {
        $sisaPokok = $this->sisaPokok($pinjaman);
        $tenorMaksimal = $this->eligibilitas->tenorMaksimal((float) $pinjaman->nominal);

        if ($tipe === 'lunas_total') {
            $jadwal = $this->buatJadwal($sisaPokok, 1, (float) $pinjaman->persentase_bunga, now()->endOfMonth());

            return [
                'sisa_pokok' => $sisaPokok,
                'tenor_maksimal' => $tenorMaksimal,
                'nominal_final' => collect($jadwal)->sum('total_bayar'),
                'jadwal' => $this->formatJadwal($jadwal),
                'error' => null,
            ];
        }

        try {
            $this->validasiTenorBaru($pinjaman, $tenorBaru);
        } catch (RuntimeException $e) {
            return [
                'sisa_pokok' => $sisaPokok,
                'tenor_maksimal' => $tenorMaksimal,
                'nominal_final' => null,
                'jadwal' => [],
                'error' => $e->getMessage(),
            ];
        }

        $jadwal = $this->buatJadwal($sisaPokok, $tenorBaru, (float) $pinjaman->persentase_bunga, now()->endOfMonth());

        return [
            'sisa_pokok' => $sisaPokok,
            'tenor_maksimal' => $tenorMaksimal,
            'nominal_final' => null,
            'jadwal' => $this->formatJadwal($jadwal),
            'error' => null,
        ];
    }

    private function formatJadwal(array $jadwal): array
    {
        return array_map(fn ($b) => [
            'cicilan_ke' => $b['cicilan_ke'],
            'nominal_pokok' => (float) $b['nominal_pokok'],
            'nominal_bunga' => (float) $b['nominal_bunga'],
            'total_bayar' => (float) $b['total_bayar'],
            'tanggal_jatuh_tempo' => $b['tanggal_jatuh_tempo']->format('d M Y'),
        ], $jadwal);
    }

    public function approveBendahara(PengajuanPercepatan $pengajuan, string $catatan): void
    {
        if ($pengajuan->status !== 'diajukan') {
            throw new RuntimeException('Pengajuan tidak bisa diproses Bendahara.');
        }

        $pengajuan->update([
            'status' => 'approved_bendahara',
            'catatan_bendahara' => $catatan,
        ]);
    }

    public function rejectBendahara(PengajuanPercepatan $pengajuan, string $catatan): void
    {
        if ($pengajuan->status !== 'diajukan') {
            throw new RuntimeException('Pengajuan tidak bisa ditolak Bendahara.');
        }

        $pengajuan->update([
            'status' => 'ditolak',
            'catatan_bendahara' => $catatan,
        ]);
    }

    public function approveKetua(PengajuanPercepatan $pengajuan, string $catatan, string $bulanBerlaku, int $userId): void
    {
        DB::transaction(function () use ($pengajuan, $catatan, $bulanBerlaku) {
            $pengajuan = PengajuanPercepatan::with('pinjaman.anggota')->whereKey($pengajuan->id)->lockForUpdate()->firstOrFail();

            if ($pengajuan->status !== 'approved_bendahara') {
                throw new RuntimeException('Pengajuan belum disetujui Bendahara.');
            }

            $pinjaman = Pinjaman::whereKey($pengajuan->pinjaman_id)->lockForUpdate()->firstOrFail();
            $sisaPokok = $this->sisaPokok($pinjaman);
            $mulai = $this->tanggalMulai($bulanBerlaku);
            $tenor = $pengajuan->tipe === 'lunas_total' ? 1 : (int) $pengajuan->tenor_baru;
            $jadwal = $this->buatJadwal($sisaPokok, $tenor, (float) $pinjaman->persentase_bunga, $mulai);

            $pinjaman->angsuran()->where('status', 'belum_bayar')->update([
                'status' => 'digantikan',
                'pengajuan_percepatan_id' => $pengajuan->id,
            ]);

            foreach ($jadwal as $baris) {
                $pengajuan->angsuranPercepatan()->create($baris + ['status' => 'belum_bayar']);
            }

            $pengajuan->update([
                'status' => 'aktif',
                'catatan_ketua' => $catatan,
                'bulan_berlaku' => $bulanBerlaku,
                'sisa_pokok_saat_approval' => $sisaPokok,
                'nominal_final' => $pengajuan->tipe === 'lunas_total' ? collect($jadwal)->sum('total_bayar') : null,
            ]);

            if ($pengajuan->tipe === 'ubah_tenor') {
                $pinjaman->update(['tenor_bulan' => $pengajuan->tenor_baru]);
            }

            // lunas_total: cukup buat 1 tagihan final (belum_bayar). Pinjaman baru menjadi
            // 'lunas' SETELAH tagihan tersebut dikonfirmasi Bendahara (lihat konfirmasiAngsuranPercepatan).
        });
    }

    public function rejectKetua(PengajuanPercepatan $pengajuan, string $catatan): void
    {
        if ($pengajuan->status !== 'approved_bendahara') {
            throw new RuntimeException('Pengajuan tidak bisa ditolak Ketua.');
        }

        $pengajuan->update([
            'status' => 'ditolak',
            'catatan_ketua' => $catatan,
        ]);
    }

    public function konfirmasiAngsuranPercepatan(AngsuranPercepatan $angsuran, int $userId): void
    {
        $angsuran = AngsuranPercepatan::with('pengajuanPercepatan.pinjaman.anggota')->whereKey($angsuran->id)->lockForUpdate()->firstOrFail();

        if ($angsuran->status !== 'belum_bayar') {
            return;
        }

        $angsuran->update([
            'status' => 'lunas',
            'tanggal_konfirmasi_bayar' => now(),
            'confirmed_by' => $userId,
        ]);

        $pinjaman = $angsuran->pengajuanPercepatan->pinjaman;

        $this->jurnalKas->catat(
            tipe: 'masuk',
            kategori: 'pembayaran_angsuran',
            kantong: 'pinjaman',
            jumlah: (float) $angsuran->total_bayar,
            keterangan: "Angsuran percepatan ke-{$angsuran->cicilan_ke} - {$pinjaman->anggota->nama}",
            referensiId: $angsuran->id,
            tanggal: now()->format('Y-m-d'),
            userId: $userId,
        );

        if (! $angsuran->pengajuanPercepatan->angsuranPercepatan()->where('status', 'belum_bayar')->exists()) {
            $pinjaman->update(['status' => 'lunas']);
        }
    }

    private function validasiTenorBaru(Pinjaman $pinjaman, ?int $tenorBaru): void
    {
        if (! $tenorBaru || $tenorBaru < 1) {
            throw new RuntimeException('Tenor baru wajib diisi.');
        }

        $sisaAngsuran = $pinjaman->angsuran()->where('status', 'belum_bayar')->count();
        if ($tenorBaru === $sisaAngsuran) {
            throw new RuntimeException('Tenor baru harus berbeda dari sisa tenor saat ini.');
        }

        $tenorMaksimal = $this->eligibilitas->tenorMaksimal((float) $pinjaman->nominal);
        if ($tenorMaksimal && $tenorBaru > $tenorMaksimal) {
            throw new RuntimeException("Tenor maksimal untuk nominal ini adalah {$tenorMaksimal} bulan.");
        }
    }

    private function sisaPokok(Pinjaman $pinjaman): float
    {
        $pokokTerbayar = (float) $pinjaman->angsuran()->where('status', 'lunas')->sum('nominal_pokok');

        return max(0, round((float) $pinjaman->nominal - $pokokTerbayar, 2));
    }

    private function totalLunas(float $sisaPokok, float $persentaseBunga): float
    {
        return round($sisaPokok + ($sisaPokok * $persentaseBunga / 100), 2);
    }

    private function buatJadwal(float $sisaPokok, int $tenor, float $persentaseBunga, Carbon $mulai): array
    {
        $pokokPerBulan = round($sisaPokok / $tenor, 2);
        $sisa = $sisaPokok;
        $jadwal = [];

        for ($i = 1; $i <= $tenor; $i++) {
            $pokok = $i === $tenor ? round($sisa, 2) : $pokokPerBulan;
            $bunga = round($sisa * $persentaseBunga / 100, 2);

            $jadwal[] = [
                'cicilan_ke' => $i,
                'nominal_pokok' => $pokok,
                'nominal_bunga' => $bunga,
                'total_bayar' => $pokok + $bunga,
                'tanggal_jatuh_tempo' => $mulai->copy()->addMonths($i - 1)->endOfMonth(),
            ];

            $sisa -= $pokok;
        }

        return $jadwal;
    }

    private function tanggalMulai(string $bulanBerlaku): Carbon
    {
        return match ($bulanBerlaku) {
            'bulan_ini' => now()->endOfMonth(),
            'bulan_depan' => now()->addMonthNoOverflow()->endOfMonth(),
            default => throw new RuntimeException('Bulan berlaku tidak valid.'),
        };
    }
}
