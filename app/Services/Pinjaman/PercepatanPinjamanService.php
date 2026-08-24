<?php

namespace App\Services\Pinjaman;

use App\Models\PengajuanPercepatan;
use App\Models\Pinjaman;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PercepatanPinjamanService
{
    public function __construct(private EligibilitasPinjamanService $eligibilitas) {}

    public function ajukan(Pinjaman $pinjaman, string $tipe, ?int $tenorBaru, string $keterangan): PengajuanPercepatan
    {
        if ($pinjaman->status !== 'aktif') {
            throw new RuntimeException('Hanya pinjaman aktif yang bisa diajukan perubahan.');
        }
        if ($pinjaman->sudah_pakai_percepatan) {
            throw new RuntimeException('Pinjaman ini sudah pernah menggunakan hak perubahan tenor/pelunasan dipercepat.');
        }

        $adaPengajuanMenunggu = PengajuanPercepatan::where('pinjaman_id', $pinjaman->id)
            ->whereIn('status', ['diajukan', 'approved_bendahara'])
            ->exists();
        if ($adaPengajuanMenunggu) {
            throw new RuntimeException('Sudah ada pengajuan yang masih diproses untuk pinjaman ini.');
        }

        if ($tipe === 'perpanjang') {
            $tenorMaksimal = $this->eligibilitas->tenorMaksimal((float) $pinjaman->nominal);
            if (! $tenorMaksimal || $tenorBaru > $tenorMaksimal) {
                throw new RuntimeException("Tenor maksimal untuk nominal pinjaman ini adalah {$tenorMaksimal} bulan.");
            }
        }

        return PengajuanPercepatan::create([
            'pinjaman_id' => $pinjaman->id,
            'tipe' => $tipe,
            'tenor_lama' => $pinjaman->tenor_bulan,
            'tenor_baru' => in_array($tipe, ['percepat', 'perpanjang']) ? $tenorBaru : null,
            'keterangan' => $keterangan,
            'status' => 'diajukan',
            'tanggal_pengajuan' => now(),
        ]);
    }

    /**
     * Preview simulasi jadwal/nominal SEBELUM disubmit, dipakai di form pengajuan.
     */
    public function preview(Pinjaman $pinjaman, string $tipe, ?int $tenorBaru): array
    {
        $sisaPokok = (float) $pinjaman->angsuranBelumBayar()->sum('nominal_pokok');
        $persentase = (float) $pinjaman->persentase_bunga / 100;

        if ($tipe === 'lunas_total') {
            $bunga = round($sisaPokok * $persentase, 2);

            return [
                'sisa_pokok' => $sisaPokok,
                'bunga' => $bunga,
                'total_bayar' => $sisaPokok + $bunga,
            ];
        }

        $pokokPerBulan = round($sisaPokok / $tenorBaru, 2);
        $sisa = $sisaPokok;
        $jadwal = [];

        for ($i = 1; $i <= $tenorBaru; $i++) {
            $bunga = round($sisa * $persentase, 2);
            $jadwal[] = [
                'cicilan_ke' => $i,
                'nominal_pokok' => $pokokPerBulan,
                'nominal_bunga' => $bunga,
                'total_bayar' => $pokokPerBulan + $bunga,
            ];
            $sisa -= $pokokPerBulan;
        }

        return ['sisa_pokok' => $sisaPokok, 'jadwal' => $jadwal];
    }

    public function approveBendahara(PengajuanPercepatan $p, string $catatan): void
    {
        $p->update(['status' => 'approved_bendahara', 'catatan_bendahara' => $catatan]);
    }

    public function rejectBendahara(PengajuanPercepatan $p, string $catatan): void
    {
        $p->update(['status' => 'ditolak', 'catatan_bendahara' => $catatan]);
    }

    public function rejectKetua(PengajuanPercepatan $p, string $catatan): void
    {
        $p->update(['status' => 'ditolak', 'catatan_ketua' => $catatan]);
    }

    public function approveKetua(PengajuanPercepatan $pengajuan, string $catatan, string $bulanBerlaku): void
    {
        DB::transaction(function () use ($pengajuan, $catatan, $bulanBerlaku) {
            $pinjaman = $pengajuan->pinjaman;

            $semuaBelumBayar = $pinjaman->angsuran()
                ->where('status', 'belum_bayar')
                ->orderBy('cicilan_ke')
                ->lockForUpdate()
                ->get();

            // Lunas Total SELALU ganti semua sisa cicilan (tidak ada pengecualian bulan berjalan)
            if ($pengajuan->tipe === 'lunas_total') {
                $angsuranDigantikan = $semuaBelumBayar;
            } elseif ($bulanBerlaku === 'bulan_depan') {
                $akhirBulanIni = now()->endOfMonth()->toDateString();
                $cicilanBulanIni = $semuaBelumBayar->first(
                    fn ($a) => $a->tanggal_jatuh_tempo->toDateString() === $akhirBulanIni
                );
                $angsuranDigantikan = $cicilanBulanIni
                    ? $semuaBelumBayar->reject(fn ($a) => $a->id === $cicilanBulanIni->id)
                    : $semuaBelumBayar;
            } else {
                $angsuranDigantikan = $semuaBelumBayar;
            }

            $sisaPokok = (float) $angsuranDigantikan->sum('nominal_pokok');

            foreach ($angsuranDigantikan as $angsuran) {
                $angsuran->update(['status' => 'digantikan', 'pengajuan_percepatan_id' => $pengajuan->id]);
            }

            $pengajuan->update([
                'status' => 'aktif',
                'catatan_ketua' => $catatan,
                'bulan_berlaku' => $bulanBerlaku,
                'sisa_pokok_saat_approval' => $sisaPokok,
            ]);

            $tanggalMulai = $bulanBerlaku === 'bulan_ini' ? now() : now()->addMonthNoOverflow();
            $persentase = (float) $pinjaman->persentase_bunga / 100;

            if ($pengajuan->tipe === 'lunas_total') {
                $bunga = round($sisaPokok * $persentase, 2);
                $totalBayar = $sisaPokok + $bunga;

                $pengajuan->update(['nominal_final' => $totalBayar]);

                $pengajuan->angsuranBaru()->create([
                    'cicilan_ke' => 1,
                    'nominal_pokok' => $sisaPokok,
                    'nominal_bunga' => $bunga,
                    'total_bayar' => $totalBayar,
                    'status' => 'belum_bayar',
                    'tanggal_jatuh_tempo' => $tanggalMulai->copy()->endOfMonth(),
                ]);
            } else {
                $tenorBaru = $pengajuan->tenor_baru;
                $pokokPerBulan = round($sisaPokok / $tenorBaru, 2);
                $sisa = $sisaPokok;

                for ($i = 1; $i <= $tenorBaru; $i++) {
                    $bunga = round($sisa * $persentase, 2);

                    $pengajuan->angsuranBaru()->create([
                        'cicilan_ke' => $i,
                        'nominal_pokok' => $pokokPerBulan,
                        'nominal_bunga' => $bunga,
                        'total_bayar' => $pokokPerBulan + $bunga,
                        'status' => 'belum_bayar',
                        'tanggal_jatuh_tempo' => $tanggalMulai->copy()->addMonths($i - 1)->endOfMonth(),
                    ]);

                    $sisa -= $pokokPerBulan;
                }
            }

            $pinjaman->update(['sudah_pakai_percepatan' => true]);
        });
    }
}
