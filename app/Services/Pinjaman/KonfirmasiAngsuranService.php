<?php

namespace App\Services\Pinjaman;

use App\Models\Anggota;
use App\Models\Angsuran;
use App\Models\AngsuranPercepatan;
use App\Models\AuditLog;
use App\Models\JurnalKas;
use App\Models\PengajuanLimit;
use App\Models\PengajuanPercepatan;
use App\Models\Pinjaman;
use App\Services\Anggota\ResignService;
use App\Services\Keuangan\JurnalKasService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

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

    public function alasanTidakBisaDibatalkan(
        Angsuran|AngsuranPercepatan $angsuran,
        Pinjaman $pinjaman,
        Anggota $anggota
    ): ?string {
        if ($angsuran->status !== 'lunas') {
            return 'Angsuran ini belum berstatus lunas.';
        }

        if ($anggota->status !== 'aktif') {
            return "Anggota berstatus '{$anggota->status}', konfirmasi angsuran tidak dapat dibatalkan.";
        }

        if ($angsuran instanceof AngsuranPercepatan) {
            $adaCicilanBerikut = AngsuranPercepatan::where('pengajuan_percepatan_id', $angsuran->pengajuan_percepatan_id)
                ->where('cicilan_ke', '>', $angsuran->cicilan_ke)
                ->where('status', 'lunas')
                ->exists();
            if ($adaCicilanBerikut) {
                return 'Terdapat cicilan berikutnya yang sudah lunas; batalkan dari cicilan terakhir terlebih dahulu.';
            }
        } else {
            $adaCicilanBerikut = Angsuran::where('pinjaman_id', $pinjaman->id)
                ->where('cicilan_ke', '>', $angsuran->cicilan_ke)
                ->where('status', 'lunas')
                ->exists();
            if ($adaCicilanBerikut) {
                return 'Terdapat cicilan berikutnya yang sudah lunas; batalkan dari cicilan terakhir terlebih dahulu.';
            }

            $adaPercepatanLunas = AngsuranPercepatan::join('pengajuan_percepatan', 'pengajuan_percepatan.id', '=', 'angsuran_percepatan.pengajuan_percepatan_id')
                ->where('pengajuan_percepatan.pinjaman_id', $pinjaman->id)
                ->where('pengajuan_percepatan.status', 'aktif')
                ->where('angsuran_percepatan.status', 'lunas')
                ->exists();
            if ($adaPercepatanLunas) {
                return 'Terdapat cicilan perubahan tenor yang sudah lunas pada pinjaman ini.';
            }
        }

        $adaPinjamanBaru = Pinjaman::where('anggota_id', $anggota->id)
            ->where('id', '>', $pinjaman->id)
            ->where('status', '!=', 'ditolak')
            ->exists();
        if ($adaPinjamanBaru) {
            return 'Anggota sudah memiliki pengajuan pinjaman baru setelah pinjaman ini.';
        }

        $waktuKonfirmasi = $angsuran->updated_at ?? $angsuran->tanggal_konfirmasi_bayar?->copy()->endOfDay();
        if ($waktuKonfirmasi) {
            $adaLimitBaru = PengajuanLimit::where('anggota_id', $anggota->id)
                ->where('status', '!=', 'ditolak')
                ->where('created_at', '>', $waktuKonfirmasi)
                ->exists();
            if ($adaLimitBaru) {
                return 'Anggota sudah mengajukan kenaikan limit setelah konfirmasi angsuran ini.';
            }

            $adaPercepatanBaru = PengajuanPercepatan::where('pinjaman_id', $pinjaman->id)
                ->where('status', '!=', 'ditolak')
                ->where('created_at', '>', $waktuKonfirmasi)
                ->exists();
            if ($adaPercepatanBaru) {
                return 'Terdapat pengajuan perubahan tenor setelah konfirmasi angsuran ini.';
            }
        }

        return null;
    }

    public function batalkanKonfirmasi(string $item, string $alasan, int $userId): void
    {
        if (! preg_match('/^(n|p)-(\d+)$/', $item, $m)) {
            throw new RuntimeException('Format ID angsuran tidak valid.');
        }

        $isPercepatan = $m[1] === 'p';
        $id = (int) $m[2];
        $alasan = trim($alasan);

        DB::transaction(function () use ($isPercepatan, $id, $item, $alasan, $userId) {
            if ($isPercepatan) {
                $angsuran = AngsuranPercepatan::with('pengajuan.pinjaman.anggota')
                    ->lockForUpdate()
                    ->findOrFail($id);
                $pinjaman = Pinjaman::lockForUpdate()->findOrFail($angsuran->pengajuan->pinjaman_id);
            } else {
                $angsuran = Angsuran::with('pinjaman.anggota')
                    ->lockForUpdate()
                    ->findOrFail($id);
                $pinjaman = Pinjaman::lockForUpdate()->findOrFail($angsuran->pinjaman_id);
            }

            $anggota = Anggota::lockForUpdate()->findOrFail($pinjaman->anggota_id);

            $alasanBlokir = $this->alasanTidakBisaDibatalkan($angsuran, $pinjaman, $anggota);
            if ($alasanBlokir !== null) {
                throw new RuntimeException($alasanBlokir);
            }

            $polaKet = $isPercepatan
                ? "Angsuran (perubahan tenor) ke-{$angsuran->cicilan_ke} - %"
                : "Angsuran ke-{$angsuran->cicilan_ke} - %";

            $jurnal = JurnalKas::where('kategori', 'pembayaran_angsuran')
                ->where('referensi_id', $angsuran->id)
                ->where('keterangan', 'like', $polaKet)
                ->whereNull('dibatalkan_pada')
                ->whereNull('koreksi_dari_id')
                ->orderByDesc('id')
                ->first();

            if (! $jurnal) {
                $jurnal = JurnalKas::where('kategori', 'pembayaran_angsuran')
                    ->where('referensi_id', $angsuran->id)
                    ->where('jumlah', (float) $angsuran->total_bayar)
                    ->whereNull('dibatalkan_pada')
                    ->whereNull('koreksi_dari_id')
                    ->orderByDesc('id')
                    ->first();
            }

            $statusPinjamanLama = $pinjaman->status;

            if ($jurnal) {
                $jurnalPembalikId = $this->jurnalKas->batalkanBarisInternal($jurnal, $alasan, $userId)->id;
            } else {
                $jurnalPembalikId = $this->jurnalKas->catat(
                    tipe: 'keluar',
                    kategori: 'pembayaran_angsuran',
                    kantong: 'pinjaman',
                    jumlah: (float) $angsuran->total_bayar,
                    keterangan: "[PEMBALIK ANGSURAN KE-{$angsuran->cicilan_ke}] {$alasan}",
                    referensiId: $angsuran->id,
                    tanggal: now()->format('Y-m-d'),
                    userId: $userId,
                    subJudul: 'Jurnal Pembalik (Storno)',
                )->id;
            }

            $angsuran->update([
                'status' => 'belum_bayar',
                'tanggal_konfirmasi_bayar' => null,
                'confirmed_by' => null,
            ]);

            if ($pinjaman->status === 'lunas') {
                $pinjaman->update(['status' => 'aktif']);
            }

            AuditLog::catat(
                aksi: 'angsuran_konfirmasi_dibatalkan',
                keterangan: "Rollback angsuran ke-{$angsuran->cicilan_ke} ({$anggota->nama}): {$alasan}",
                dataLama: [
                    'item' => $item,
                    'pinjaman_id' => $pinjaman->id,
                    'anggota_id' => $anggota->id,
                    'cicilan_ke' => $angsuran->cicilan_ke,
                    'total_bayar' => (float) $angsuran->total_bayar,
                    'status_pinjaman' => $statusPinjamanLama,
                ],
                dataBaru: [
                    'jurnal_pembalik_id' => $jurnalPembalikId,
                    'status_angsuran' => 'belum_bayar',
                    'status_pinjaman' => $pinjaman->status,
                    'alasan' => $alasan,
                ],
                userId: $userId,
            );
        });
    }
}
