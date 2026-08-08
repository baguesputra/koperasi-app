<?php

namespace App\Services\Pinjaman;

use App\Models\Angsuran;
use App\Models\JurnalKas;
use App\Models\KasKoperasi;
use Illuminate\Support\Facades\DB;

class KonfirmasiAngsuranService
{
    /**
     * Konfirmasi pembayaran beberapa angsuran sekaligus (bulk).
     * Setiap angsuran yang lunas otomatis: catat jurnal kas, tambah saldo,
     * dan cek apakah pinjaman induknya sudah lunas semua.
     */
    public function konfirmasiMassal(array $angsuranIds, int $confirmedByUserId): int
    {
        $angsuranList = Angsuran::with('pinjaman.anggota')
            ->whereIn('id', $angsuranIds)
            ->where('status', 'belum_bayar')
            ->get();

        DB::transaction(function () use ($angsuranList, $confirmedByUserId) {
            $kas = KasKoperasi::firstOrFail();

            foreach ($angsuranList as $angsuran) {
                $angsuran->update([
                    'status' => 'lunas',
                    'tanggal_konfirmasi_bayar' => now(),
                    'confirmed_by' => $confirmedByUserId,
                ]);

                JurnalKas::create([
                    'tipe' => 'masuk',
                    'kategori' => 'pembayaran_angsuran',
                    'jumlah' => $angsuran->total_bayar,
                    'keterangan' => "Angsuran ke-{$angsuran->cicilan_ke} - {$angsuran->pinjaman->anggota->nama}",
                    'referensi_id' => $angsuran->id,
                    'tanggal' => now(),
                    'created_by' => $confirmedByUserId,
                ]);

                $kas->increment('saldo_saat_ini', $angsuran->total_bayar);

                $this->tandaiLunasJikaSelesai($angsuran->pinjaman);
            }
        });

        return $angsuranList->count();
    }

    private function tandaiLunasJikaSelesai($pinjaman): void
    {
        $masihAdaBelumBayar = $pinjaman->angsuran()->where('status', 'belum_bayar')->exists();

        if (! $masihAdaBelumBayar && $pinjaman->status === 'aktif') {
            $pinjaman->update(['status' => 'lunas']);
        }
    }
}