<?php

namespace App\Services\Pinjaman;

use App\Models\Anggota;
use App\Models\Angsuran;
use App\Models\AngsuranPercepatan;
use App\Models\AuditLog;
use App\Models\JurnalKas;
use App\Models\PengajuanLimit;
use App\Models\PengajuanPenghapusanPinjaman;
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
            $kunciTerurut = [];

            foreach ($items as $item) {
                $teks = trim((string) $item);
                if (preg_match('/^(n|p)-(\d+)$/i', $teks, $m)) {
                    $prefix = strtolower($m[1]);
                    $id = (int) $m[2];
                    $prefix === 'p' ? $percepatanIds[] = $id : $normalIds[] = $id;
                    $kunciTerurut[] = "{$prefix}-{$id}";
                } elseif (ctype_digit($teks) && (int) $teks > 0) {
                    $id = (int) $teks;
                    $normalIds[] = $id;
                    $kunciTerurut[] = "n-{$id}";
                }
            }

            $normalIds = array_values(array_unique($normalIds));
            $percepatanIds = array_values(array_unique($percepatanIds));
            $kunciTerurut = array_values(array_unique($kunciTerurut));

            if ($normalIds === [] && $percepatanIds === []) {
                return 0;
            }

            $listNormal = $normalIds !== []
                ? Angsuran::with('pinjaman.anggota')
                    ->whereIn('id', $normalIds)
                    ->where('status', 'belum_bayar')
                    ->lockForUpdate()
                    ->get()
                : collect();

            $listPercepatan = $percepatanIds !== []
                ? AngsuranPercepatan::with('pengajuan.pinjaman.anggota')
                    ->whereIn('id', $percepatanIds)
                    ->where('status', 'belum_bayar')
                    ->lockForUpdate()
                    ->get()
                : collect();

            if ($listNormal->isEmpty() && $listPercepatan->isEmpty()) {
                return 0;
            }

            $pinjamanIds = $listNormal->pluck('pinjaman_id')
                ->merge($listPercepatan->map(fn (AngsuranPercepatan $a) => $a->pengajuan?->pinjaman_id))
                ->filter()
                ->unique()
                ->values();

            $pinjamanLocked = Pinjaman::with('anggota')
                ->whereIn('id', $pinjamanIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $akhirBulanIni = now()->endOfMonth()->endOfDay();

            $penghapusanAktifIds = PengajuanPenghapusanPinjaman::whereIn('pinjaman_id', $pinjamanIds)
                ->whereNotIn('status', ['ditolak', 'disetujui'])
                ->pluck('pinjaman_id')
                ->flip();

            $percepatanPendingIds = PengajuanPercepatan::whereIn('pinjaman_id', $pinjamanIds)
                ->whereIn('status', ['diajukan', 'approved_bendahara'])
                ->pluck('pinjaman_id')
                ->flip();

            foreach ($pinjamanLocked as $pid => $pinjaman) {
                if ($pinjaman->status !== 'aktif') {
                    throw new RuntimeException("Pinjaman #{$pinjaman->id} tidak berstatus aktif (status saat ini: {$pinjaman->status}).");
                }

                if ($penghapusanAktifIds->has($pid)) {
                    throw new RuntimeException("Pinjaman #{$pinjaman->id} sedang dalam proses pengajuan penghapusan sehingga angsuran dikunci.");
                }

                if ($percepatanPendingIds->has($pid)) {
                    throw new RuntimeException("Pinjaman #{$pinjaman->id} sedang memiliki pengajuan perubahan tenor/pelunasan yang menunggu persetujuan.");
                }
            }

            // Kumpulkan seluruh target terpilih per pinjaman + validasi periode mendatang
            $selectedPerLoan = [];
            $selectedEntries = [];

            foreach ($listNormal as $angsuran) {
                if ($angsuran->tanggal_jatuh_tempo && $angsuran->tanggal_jatuh_tempo->gt($akhirBulanIni)) {
                    throw new RuntimeException(
                        "Angsuran ke-{$angsuran->cicilan_ke} jatuh tempo pada periode mendatang ({$angsuran->tanggal_jatuh_tempo->translatedFormat('F Y')}) dan belum dapat dikonfirmasi."
                    );
                }
                $key = 'n-'.$angsuran->id;
                $selectedPerLoan[$angsuran->pinjaman_id][$key] = true;
                $selectedEntries[] = [
                    'key' => $key,
                    'is_percepatan' => false,
                    'pinjaman_id' => $angsuran->pinjaman_id,
                    'cicilan_ke' => (int) $angsuran->cicilan_ke,
                    'jatuh_tempo' => $angsuran->tanggal_jatuh_tempo?->format('Y-m-d') ?? '9999-12-31',
                    'model' => $angsuran,
                ];
            }

            foreach ($listPercepatan as $angsuran) {
                if ($angsuran->tanggal_jatuh_tempo && $angsuran->tanggal_jatuh_tempo->gt($akhirBulanIni)) {
                    throw new RuntimeException(
                        "Angsuran ke-{$angsuran->cicilan_ke} jatuh tempo pada periode mendatang ({$angsuran->tanggal_jatuh_tempo->translatedFormat('F Y')}) dan belum dapat dikonfirmasi."
                    );
                }
                $pid = (int) $angsuran->pengajuan->pinjaman_id;
                $key = 'p-'.$angsuran->id;
                $selectedPerLoan[$pid][$key] = true;
                $selectedEntries[] = [
                    'key' => $key,
                    'is_percepatan' => true,
                    'pinjaman_id' => $pid,
                    'cicilan_ke' => (int) $angsuran->cicilan_ke,
                    'jatuh_tempo' => $angsuran->tanggal_jatuh_tempo?->format('Y-m-d') ?? '9999-12-31',
                    'model' => $angsuran,
                ];
            }

            // Validasi urutan kronologis: tidak boleh melompati angsuran belum_bayar sebelumnya (lintas jadwal normal -> percepatan)
            $percepatanAktifByLoan = PengajuanPercepatan::whereIn('pinjaman_id', $pinjamanIds)
                ->where('status', 'aktif')
                ->get()
                ->groupBy('pinjaman_id');

            $semuaNormalBelum = Angsuran::whereIn('pinjaman_id', $pinjamanIds)
                ->where('status', 'belum_bayar')
                ->get()
                ->groupBy('pinjaman_id');

            $semuaPercepatanAktifIds = $percepatanAktifByLoan->flatten(1)->pluck('id')->all();
            $semuaPercepatanBelum = $semuaPercepatanAktifIds !== []
                ? AngsuranPercepatan::with('pengajuan:id,pinjaman_id')
                    ->whereIn('pengajuan_percepatan_id', $semuaPercepatanAktifIds)
                    ->where('status', 'belum_bayar')
                    ->get()
                    ->groupBy(fn (AngsuranPercepatan $a) => $a->pengajuan?->pinjaman_id)
                : collect();

            foreach ($selectedPerLoan as $pid => $selectedKeys) {
                $pinjaman = $pinjamanLocked->get($pid);
                $namaAnggota = $pinjaman?->anggota?->nama ?? "Pinjaman #{$pid}";

                $unpaidList = collect();
                foreach ($semuaNormalBelum->get($pid, collect()) as $a) {
                    $unpaidList->push([
                        'key' => 'n-'.$a->id,
                        'is_percepatan' => 0,
                        'cicilan_ke' => (int) $a->cicilan_ke,
                        'tanggal_jatuh_tempo' => $a->tanggal_jatuh_tempo,
                        'sort_date' => $a->tanggal_jatuh_tempo?->format('Y-m-d') ?? '9999-12-31',
                    ]);
                }
                foreach ($semuaPercepatanBelum->get($pid, collect()) as $a) {
                    $unpaidList->push([
                        'key' => 'p-'.$a->id,
                        'is_percepatan' => 1,
                        'cicilan_ke' => (int) $a->cicilan_ke,
                        'tanggal_jatuh_tempo' => $a->tanggal_jatuh_tempo,
                        'sort_date' => $a->tanggal_jatuh_tempo?->format('Y-m-d') ?? '9999-12-31',
                    ]);
                }

                $unpaidSorted = $unpaidList->sort(function (array $a, array $b) {
                    return [$a['is_percepatan'], $a['cicilan_ke'], $a['sort_date'], $a['key']]
                        <=> [$b['is_percepatan'], $b['cicilan_ke'], $b['sort_date'], $b['key']];
                })->values();

                // Pastikan selectedKeys membentuk prefiks berurutan tanpa ada angsuran lebih awal yang terlewat
                $firstUnselected = null;
                foreach ($unpaidSorted as $slot) {
                    $isSelected = isset($selectedKeys[$slot['key']]);
                    if (! $isSelected && $firstUnselected === null) {
                        $firstUnselected = $slot;
                    } elseif ($isSelected && $firstUnselected !== null) {
                        $tempoLabel = $firstUnselected['tanggal_jatuh_tempo']?->translatedFormat('d M Y') ?? '-';
                        throw new RuntimeException(
                            "Angsuran ke-{$slot['cicilan_ke']} ({$namaAnggota}) tidak dapat dikonfirmasi karena Angsuran ke-{$firstUnselected['cicilan_ke']} (jatuh tempo {$tempoLabel}) belum lunas."
                        );
                    }
                }
            }

            // Proses berurutan (normal lebih dulu, lalu cicilan_ke terkecil)
            usort($selectedEntries, function (array $a, array $b) {
                return [(int) $a['is_percepatan'], $a['cicilan_ke'], $a['jatuh_tempo'], $a['key']]
                    <=> [(int) $b['is_percepatan'], $b['cicilan_ke'], $b['jatuh_tempo'], $b['key']];
            });

            $jumlah = 0;
            $totalBayar = 0.0;
            $pinjamanTersentuh = [];

            foreach ($selectedEntries as $entry) {
                if (! $entry['is_percepatan']) {
                    /** @var Angsuran $angsuran */
                    $angsuran = $entry['model'];
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
                } else {
                    /** @var AngsuranPercepatan $angsuran */
                    $angsuran = $entry['model'];
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
                    'ids' => $kunciTerurut,
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

        $adaPenghapusanAktif = PengajuanPenghapusanPinjaman::where('pinjaman_id', $pinjaman->id)
            ->whereNotIn('status', ['ditolak', 'disetujui'])
            ->exists();
        if ($adaPenghapusanAktif) {
            return 'Pinjaman sedang dalam proses pengajuan penghapusan.';
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
