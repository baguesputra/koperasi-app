<?php

namespace App\Services\Pinjaman;

use App\Models\Angsuran;
use App\Models\AngsuranPercepatan;
use App\Models\AuditLog;
use App\Services\Keuangan\JurnalKasService;
use Illuminate\Support\Facades\DB;

class KonfirmasiAngsuranService
{
    public function __construct(private JurnalKasService $jurnalKas) {}

    /**
     * @param  array  $items  string dengan prefix "n-{id}" (normal) atau "p-{id}" (hasil percepatan)
     */
    public function konfirmasiMassal(array $items, int $confirmedByUserId): int
    {
        return DB::transaction(function () use ($items, $confirmedByUserId) {
            $normalIds = [];
            $percepatanIds = [];

            foreach ($items as $item) {
                [$prefix, $id] = explode('-', $item, 2);
                $prefix === 'p' ? $percepatanIds[] = (int) $id : $normalIds[] = (int) $id;
            }

            $jumlah = 0;
            $totalBayar = 0.0;

            if ($normalIds) {
                $list = Angsuran::with('pinjaman.anggota')
                    ->whereIn('id', $normalIds)->where('status', 'belum_bayar')
                    ->lockForUpdate()->get();

                foreach ($list as $angsuran) {
                    $angsuran->update(['status' => 'lunas', 'tanggal_konfirmasi_bayar' => now(), 'confirmed_by' => $confirmedByUserId]);

                    $this->jurnalKas->catat(
                        tipe: 'masuk', kategori: 'pembayaran_angsuran', kantong: 'pinjaman',
                        jumlah: (float) $angsuran->total_bayar,
                        keterangan: "Angsuran ke-{$angsuran->cicilan_ke} - {$angsuran->pinjaman->anggota->nama}",
                        referensiId: $angsuran->id, tanggal: now()->format('Y-m-d'), userId: $confirmedByUserId,
                    );

                    $this->tandaiLunasJikaSelesai($angsuran->pinjaman);
                    $jumlah++;
                    $totalBayar += (float) $angsuran->total_bayar;
                }
            }

            if ($percepatanIds) {
                $list = AngsuranPercepatan::with('pengajuan.pinjaman.anggota')
                    ->whereIn('id', $percepatanIds)->where('status', 'belum_bayar')
                    ->lockForUpdate()->get();

                foreach ($list as $angsuran) {
                    $angsuran->update(['status' => 'lunas', 'tanggal_konfirmasi_bayar' => now(), 'confirmed_by' => $confirmedByUserId]);

                    $pinjaman = $angsuran->pengajuan->pinjaman;

                    $this->jurnalKas->catat(
                        tipe: 'masuk', kategori: 'pembayaran_angsuran', kantong: 'pinjaman',
                        jumlah: (float) $angsuran->total_bayar,
                        keterangan: "Angsuran (perubahan tenor) ke-{$angsuran->cicilan_ke} - {$pinjaman->anggota->nama}",
                        referensiId: $angsuran->id, tanggal: now()->format('Y-m-d'), userId: $confirmedByUserId,
                    );

                    $this->tandaiLunasJikaSelesai($pinjaman);
                    $jumlah++;
                    $totalBayar += (float) $angsuran->total_bayar;
                }
            }

            // Audit log untuk konfirmasi massal
            AuditLog::catat(
                aksi: 'angsuran_konfirmasi_massal',
                keterangan: "Konfirmasi {$jumlah} angsuran, total ".number_format($totalBayar, 0, ',', '.')." oleh user #{$confirmedByUserId}",
                dataLama: ['requested_ids' => $items],
                dataBaru: [
                    'confirmed_count' => $jumlah,
                    'total_amount' => $totalBayar,
                    'confirmed_by' => $confirmedByUserId,
                    'ids' => $items,
                ]
            );

            return $jumlah;
        });
    }

    private function tandaiLunasJikaSelesai($pinjaman): void
    {
        $pinjaman->refresh();
        if ($pinjaman->status !== 'aktif') {
            return;
        }

        $sisaLama = $pinjaman->angsuran()->where('status', 'belum_bayar')->count();
        $pengajuanAktif = $pinjaman->pengajuanPercepatan()->where('status', 'aktif')->latest()->first();
        $sisaBaru = $pengajuanAktif
            ? $pengajuanAktif->angsuranBaru()->where('status', 'belum_bayar')->count()
            : 0;

        if ($sisaLama === 0 && $sisaBaru === 0) {
            $pinjaman->update(['status' => 'lunas']);
        }
    }
}
