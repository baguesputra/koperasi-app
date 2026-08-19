<?php

namespace App\Services\Pinjaman;

use App\Models\Angsuran;
use App\Models\AngsuranPercepatan;
use App\Services\Keuangan\JurnalKasService;
use Illuminate\Support\Facades\DB;

class KonfirmasiAngsuranService
{
    public function __construct(private JurnalKasService $jurnalKas) {}

    public function konfirmasiMassal(array $angsuranIds, int $confirmedByUserId): int
    {
        return DB::transaction(function () use ($angsuranIds, $confirmedByUserId) {
            // Lock baris yang mau diproses - proses lain harus antre kalau coba pegang baris yang sama
            $angsuranList = Angsuran::with('pinjaman.anggota')
                ->whereIn('id', $angsuranIds)
                ->where('status', 'belum_bayar')
                ->lockForUpdate()
                ->get();

            foreach ($angsuranList as $angsuran) {
                $angsuran->update([
                    'status' => 'lunas',
                    'tanggal_konfirmasi_bayar' => now(),
                    'confirmed_by' => $confirmedByUserId,
                ]);

                $this->jurnalKas->catat(
                    tipe: 'masuk',
                    kategori: 'pembayaran_angsuran',
                    kantong: 'pinjaman',
                    jumlah: $angsuran->total_bayar,
                    keterangan: "Angsuran ke-{$angsuran->cicilan_ke} - {$angsuran->pinjaman->anggota->nama}",
                    referensiId: $angsuran->id,
                    tanggal: now()->format('Y-m-d'),
                    userId: $confirmedByUserId,
                );

                $this->tandaiLunasJikaSelesai($angsuran->pinjaman);
            }

            return $angsuranList->count();
        });
    }

    public function konfirmasiMassalPercepatan(array $angsuranPercepatanIds, int $confirmedByUserId): int
    {
        return DB::transaction(function () use ($angsuranPercepatanIds, $confirmedByUserId) {
            $angsuranList = AngsuranPercepatan::with('pengajuanPercepatan.pinjaman.anggota')
                ->whereIn('id', $angsuranPercepatanIds)
                ->where('status', 'belum_bayar')
                ->lockForUpdate()
                ->get();

            foreach ($angsuranList as $angsuran) {
                $angsuran->update([
                    'status' => 'lunas',
                    'tanggal_konfirmasi_bayar' => now(),
                    'confirmed_by' => $confirmedByUserId,
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
                    userId: $confirmedByUserId,
                );

                $this->tandaiLunasJikaSelesai($pinjaman);
            }

            return $angsuranList->count();
        });
    }

    private function tandaiLunasJikaSelesai($pinjaman): void
    {
        $masihAdaBelumBayar = $pinjaman->angsuran()->where('status', 'belum_bayar')->exists()
            || AngsuranPercepatan::whereHas('pengajuanPercepatan', fn ($q) => $q->where('pinjaman_id', $pinjaman->id))
                ->where('status', 'belum_bayar')
                ->exists();

        if (! $masihAdaBelumBayar && $pinjaman->status === 'aktif') {
            $pinjaman->update(['status' => 'lunas']);
        }
    }
}
