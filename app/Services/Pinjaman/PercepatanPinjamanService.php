<?php

namespace App\Services\Pinjaman;

use App\Models\Angsuran;
use App\Models\PengajuanPercepatan;
use App\Models\Pinjaman;
use App\Services\Keuangan\JurnalKasService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PercepatanPinjamanService
{
    public function __construct(
        private EligibilitasPinjamanService $eligibilitas,
        private JurnalKasService $jurnalKas,
    ) {}

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
            $nominalPinjaman = (float) $pinjaman->nominal;
            $tenorMaksimal = $this->eligibilitas->tenorMaksimal($nominalPinjaman);

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

    public function approveBendahara(PengajuanPercepatan $pengajuan, string $catatan): void
    {
        $pengajuan->update([
            'status' => 'approved_bendahara',
            'catatan_bendahara' => $catatan,
        ]);
    }

    public function rejectBendahara(PengajuanPercepatan $pengajuan, string $catatan): void
    {
        $pengajuan->update([
            'status' => 'ditolak',
            'catatan_bendahara' => $catatan,
        ]);
    }

    public function rejectKetua(PengajuanPercepatan $pengajuan, string $catatan): void
    {
        $pengajuan->update([
            'status' => 'ditolak',
            'catatan_ketua' => $catatan,
        ]);
    }

    public function approveKetua(PengajuanPercepatan $pengajuan, string $catatan, string $bulanBerlaku): void
    {
        DB::transaction(function () use ($pengajuan, $catatan, $bulanBerlaku) {
            $pinjaman = $pengajuan->pinjaman;

            $angsuranBelumBayar = $pinjaman->angsuran()
                ->where('status', 'belum_bayar')
                ->orderBy('cicilan_ke')
                ->lockForUpdate()
                ->get();

            $cicilanBulanIni = null;
            $angsuranDigantikan = $angsuranBelumBayar;

            if ($bulanBerlaku === 'bulan_depan') {
                $akhirBulanIni = now()->endOfMonth()->toDateString();
                $cicilanBulanIni = $angsuranBelumBayar->firstWhere('tanggal_jatuh_tempo', $akhirBulanIni)
                    ?? $angsuranBelumBayar->first();

                $angsuranDigantikan = $angsuranBelumBayar->reject(
                    fn ($a) => $cicilanBulanIni && $a->id === $cicilanBulanIni->id
                );
            }

            $sisaPokok = (float) $angsuranDigantikan->sum('nominal_pokok');

            // Tandai angsuran lama sebagai digantikan
            foreach ($angsuranDigantikan as $angsuran) {
                $angsuran->update([
                    'status' => 'digantikan',
                    'pengajuan_percepatan_id' => $pengajuan->id,
                ]);
            }

            $pengajuan->update([
                'status' => 'aktif',
                'catatan_ketua' => $catatan,
                'bulan_berlaku' => $bulanBerlaku,
                'sisa_pokok_saat_approval' => $sisaPokok,
            ]);

            $tanggalMulai = $bulanBerlaku === 'bulan_ini'
                ? now()
                : now()->addMonthNoOverflow();

            if ($pengajuan->tipe === 'lunas_total') {
                $persentase = (float) $pinjaman->persentase_bunga / 100;
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
                $persentase = (float) $pinjaman->persentase_bunga / 100;
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

    public function konfirmasiLunasTotal(\App\Models\AngsuranPercepatan $angsuran, int $userId): void
    {
        DB::transaction(function () use ($angsuran, $userId) {
            $angsuran->update([
                'status' => 'lunas',
                'tanggal_konfirmasi_bayar' => now(),
                'confirmed_by' => $userId,
            ]);

            $pinjaman = $angsuran->pengajuan->pinjaman;

            $this->jurnalKas->catat(
                tipe: 'masuk',
                kategori: 'pembayaran_angsuran',
                kantong: 'pinjaman',
                jumlah: (float) $angsuran->total_bayar,
                keterangan: "Pelunasan dipercepat - {$pinjaman->anggota->nama}",
                referensiId: $angsuran->id,
                tanggal: now()->format('Y-m-d'),
                userId: $userId,
            );

            $pinjaman->update(['status' => 'lunas']);
        });
    }
}