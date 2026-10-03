<?php

namespace App\Services\Pinjaman;

use App\Models\Angsuran;
use App\Models\AngsuranPercepatan;
use App\Models\AuditLog;
use App\Models\PengajuanPercepatan;
use App\Models\Pinjaman;
use App\Services\Anggota\ResignService;
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
            $pinjamanTersentuh = [];

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

                    // Angsuran masuk kas bank; tanpa talangan (konsep Kas Tunggal).
                    $pinjamanTersentuh[$angsuran->pinjaman_id] = true;
                    app(ResignService::class)->finalisasiJikaMenunggu($angsuran, $confirmedByUserId);
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

                    $pinjamanTersentuh[$pinjaman->id] = true;
                    $jumlah++;
                    $totalBayar += (float) $angsuran->total_bayar;
                }
            }

            // Tandai lunas sekaligus (ganti refresh + 2-3 query per angsuran)
            $this->tandaiLunasBatch(array_keys($pinjamanTersentuh));

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

    /**
     * Tandai pinjaman lunas sekaligus untuk semua pinjaman tersentuh.
     * 4 query GROUP BY (ganti refresh + 2-3 query per angsuran).
     */
    private function tandaiLunasBatch(array $pinjamanIds): void
    {
        if (empty($pinjamanIds)) {
            return;
        }

        $statusMap = Pinjaman::whereIn('id', $pinjamanIds)->pluck('status', 'id');
        $sisaLama = Angsuran::whereIn('pinjaman_id', $pinjamanIds)
            ->where('status', 'belum_bayar')
            ->selectRaw('pinjaman_id, COUNT(*) as c')
            ->groupBy('pinjaman_id')
            ->pluck('c', 'pinjaman_id');

        $pengajuanAktif = PengajuanPercepatan::whereIn('pinjaman_id', $pinjamanIds)
            ->where('status', 'aktif')
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('pinjaman_id')
            ->map(fn ($rows) => $rows->first());

        $sisaBaru = collect();
        if ($pengajuanAktif->isNotEmpty()) {
            $sisaBaru = AngsuranPercepatan::whereIn('pengajuan_percepatan_id', $pengajuanAktif->pluck('id'))
                ->where('status', 'belum_bayar')
                ->selectRaw('pengajuan_percepatan_id, COUNT(*) as c')
                ->groupBy('pengajuan_percepatan_id')
                ->pluck('c', 'pengajuan_percepatan_id');
        }

        $lunasIds = [];
        foreach ($pinjamanIds as $pid) {
            if (($statusMap[$pid] ?? null) !== 'aktif') {
                continue;
            }
            $lama = (int) ($sisaLama[$pid] ?? 0);
            $peng = $pengajuanAktif[$pid] ?? null;
            $baru = $peng ? (int) ($sisaBaru[$peng->id] ?? 0) : 0;
            if ($lama === 0 && $baru === 0) {
                $lunasIds[] = $pid;
            }
        }

        if ($lunasIds !== []) {
            Pinjaman::whereIn('id', $lunasIds)->update(['status' => 'lunas']);
        }
    }
}
