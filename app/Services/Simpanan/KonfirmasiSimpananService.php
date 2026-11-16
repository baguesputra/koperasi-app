<?php

namespace App\Services\Simpanan;

use App\Models\Anggota;
use App\Models\AuditLog;
use App\Models\JurnalKas;
use App\Models\SettingSimpanan;
use App\Models\Simpanan;
use App\Services\Keuangan\JurnalKasService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class KonfirmasiSimpananService
{
    public function __construct(private JurnalKasService $jurnalKas) {}

    public function anggotaBelumSimpananWajib(string $bulanPeriode, ?string $cabang = null)
    {
        return Anggota::where('status', 'aktif')
            ->when($cabang, fn ($q) => $q->where('cabang', $cabang))
            ->whereDoesntHave('simpanan', fn ($q) => $q
                ->where('jenis', 'wajib')
                ->where('bulan_periode', $bulanPeriode)
            )
            ->get();
    }

    public function konfirmasiMassal(array $anggotaIds, string $bulanPeriode, int $inputByUserId): int
    {
        $nominalWajib = SettingSimpanan::where('jenis', 'wajib')->value('nominal') ?? 0;
        $nominalDanaSosial = SettingSimpanan::where('jenis', 'dana_sosial')->value('nominal') ?? 0;

        return DB::transaction(function () use ($anggotaIds, $bulanPeriode, $inputByUserId, $nominalWajib, $nominalDanaSosial) {
            // Lock baris anggota yang mau diproses, supaya proses lain tidak bisa
            // proses anggota yang sama secara bersamaan
            $anggotaTerkunci = Anggota::whereIn('id', $anggotaIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $jumlahDiproses = 0;
            $totalWajib = 0.0;
            $totalDanaSosial = 0.0;
            $processedAnggota = [];

            // 1 query daftar yang sudah ada (ganti exists() per anggota)
            $sudahAdaIds = Simpanan::whereIn('anggota_id', $anggotaIds)
                ->where('jenis', 'wajib')
                ->where('bulan_periode', $bulanPeriode)
                ->pluck('anggota_id')
                ->flip();

            $barisWajib = [];
            $barisSosial = [];
            $sekarang = now();

            foreach ($anggotaIds as $anggotaId) {
                if (! $anggotaTerkunci->has($anggotaId)) {
                    continue;
                }

                // Cek dari prefetch + cek ulang di dalam lock via koleksi terkunci
                // (mencegah duplikasi kalau ada proses lain yang barusan submit)
                if ($sudahAdaIds->has($anggotaId)) {
                    continue; // sudah dikonfirmasi proses lain, lewati
                }
                $sudahAdaIds[$anggotaId] = true; // cegah duplikat dalam batch yang sama

                $barisWajib[] = [
                    'anggota_id' => $anggotaId,
                    'jenis' => 'wajib',
                    'jumlah' => $nominalWajib,
                    'bulan_periode' => $bulanPeriode,
                    'tanggal_input' => $sekarang,
                    'input_by' => $inputByUserId,
                    'created_at' => $sekarang,
                    'updated_at' => $sekarang,
                ];

                $barisSosial[] = [
                    'anggota_id' => $anggotaId,
                    'jenis' => 'dana_sosial',
                    'jumlah' => $nominalDanaSosial,
                    'bulan_periode' => $bulanPeriode,
                    'tanggal_input' => $sekarang,
                    'input_by' => $inputByUserId,
                    'created_at' => $sekarang,
                    'updated_at' => $sekarang,
                ];

                $processedAnggota[] = $anggotaId;
            }

            // 2 bulk insert (ganti 2x create per anggota)
            if ($barisWajib !== []) {
                Simpanan::insert($barisWajib);
                Simpanan::insert($barisSosial);
            }

            // Jurnal per anggota tetap per baris (butuh lock kas + nomor dokumen sekuensial)
            foreach ($processedAnggota as $anggotaId) {

                $this->jurnalKas->catat(
                    tipe: 'masuk',
                    kategori: 'dana_sosial_bulanan',
                    kantong: 'dana_sosial',
                    jumlah: $nominalDanaSosial,
                    keterangan: "Dana sosial bulan {$bulanPeriode}",
                    referensiId: $anggotaId,
                    tanggal: now()->format('Y-m-d'),
                    userId: $inputByUserId,
                );

                // Jurnal simpanan wajib masuk (kantong simpanan)
                $this->jurnalKas->catat(
                    tipe: 'masuk',
                    kategori: 'simpanan_wajib_masuk',
                    kantong: 'simpanan',
                    jumlah: $nominalWajib,
                    keterangan: "Simpanan wajib bulan {$bulanPeriode}",
                    referensiId: $anggotaId,
                    tanggal: now()->format('Y-m-d'),
                    userId: $inputByUserId,
                    subJudul: 'Simpanan wajib masuk',
                );

                $jumlahDiproses++;
                $totalWajib += $nominalWajib;
                $totalDanaSosial += $nominalDanaSosial;
            }

            // Audit log untuk konfirmasi massal simpanan
            AuditLog::catat(
                aksi: 'simpanan_konfirmasi_massal',
                keterangan: "Konfirmasi simpanan {$jumlahDiproses} anggota (wajib: ".number_format($totalWajib, 0, ',', '.').', dana sosial: '.number_format($totalDanaSosial, 0, ',', '.').") untuk periode {$bulanPeriode} oleh user #{$inputByUserId}",
                dataLama: ['requested_ids' => $anggotaIds, 'bulan_periode' => $bulanPeriode],
                dataBaru: [
                    'confirmed_count' => $jumlahDiproses,
                    'total_wajib' => $totalWajib,
                    'total_dana_sosial' => $totalDanaSosial,
                    'input_by' => $inputByUserId,
                    'bulan_periode' => $bulanPeriode,
                    'anggota_ids' => $processedAnggota,
                ]
            );

            return $jumlahDiproses;
        });
    }

    public function anggotaSudahSimpananWajib(string $bulanPeriode, ?string $cabang = null): Collection
    {
        $perAnggota = Simpanan::with('anggota:id,nama,no_anggota,no_karyawan,cabang,status,foto_url')
            ->where('bulan_periode', $bulanPeriode)
            ->whereIn('jenis', ['wajib', 'dana_sosial'])
            ->get()
            ->groupBy('anggota_id');

        $hasil = collect();
        foreach ($perAnggota as $rows) {
            $wajib = $rows->firstWhere('jenis', 'wajib');
            if (! $wajib || ! $wajib->anggota) {
                continue;
            }
            $anggota = $wajib->anggota;
            if ($cabang !== null && $cabang !== '' && $anggota->cabang !== $cabang) {
                continue;
            }

            $sosial = $rows->firstWhere('jenis', 'dana_sosial');
            $nominalWajib = (float) $wajib->jumlah;
            $nominalSosial = (float) ($sosial?->jumlah ?? 0);

            $hasil->push([
                'id' => $anggota->id,
                'nama' => $anggota->nama,
                'no_anggota' => $anggota->no_anggota,
                'no_karyawan' => $anggota->no_karyawan,
                'cabang' => $anggota->cabang,
                'foto_url' => $anggota->foto_url,
                'status_anggota' => $anggota->status,
                'nominal_wajib' => $nominalWajib,
                'nominal_dana_sosial' => $nominalSosial,
                'nominal_total' => $nominalWajib + $nominalSosial,
                'tanggal_input' => $wajib->tanggal_input?->format('d M Y'),
                'bisa_dibatalkan' => $anggota->status === 'aktif',
            ]);
        }

        return $hasil->sortBy('nama', SORT_NATURAL | SORT_FLAG_CASE)->values();
    }

    public function batalkanKonfirmasi(int $anggotaId, string $bulanPeriode, string $alasan, int $userId): void
    {
        $alasan = trim($alasan);

        DB::transaction(function () use ($anggotaId, $bulanPeriode, $alasan, $userId) {
            $anggota = Anggota::lockForUpdate()->findOrFail($anggotaId);

            if ($anggota->status !== 'aktif') {
                throw new RuntimeException(
                    "Anggota {$anggota->nama} berstatus '{$anggota->status}', konfirmasi simpanan tidak dapat dibatalkan."
                );
            }

            $daftarSimpanan = Simpanan::where('anggota_id', $anggotaId)
                ->where('bulan_periode', $bulanPeriode)
                ->whereIn('jenis', ['wajib', 'dana_sosial'])
                ->lockForUpdate()
                ->get();

            if ($daftarSimpanan->isEmpty()) {
                throw new RuntimeException("Data simpanan wajib periode {$bulanPeriode} untuk anggota ini tidak ditemukan.");
            }

            $nominalWajib = (float) ($daftarSimpanan->firstWhere('jenis', 'wajib')?->jumlah ?? 0);
            $nominalSosial = (float) ($daftarSimpanan->firstWhere('jenis', 'dana_sosial')?->jumlah ?? 0);

            $jurnalWajib = JurnalKas::where('kategori', 'simpanan_wajib_masuk')
                ->where('referensi_id', $anggotaId)
                ->where('keterangan', "Simpanan wajib bulan {$bulanPeriode}")
                ->whereNull('dibatalkan_pada')
                ->whereNull('koreksi_dari_id')
                ->orderByDesc('id')
                ->first();

            $jurnalSosial = JurnalKas::where('kategori', 'dana_sosial_bulanan')
                ->where('referensi_id', $anggotaId)
                ->where('keterangan', "Dana sosial bulan {$bulanPeriode}")
                ->whereNull('dibatalkan_pada')
                ->whereNull('koreksi_dari_id')
                ->orderByDesc('id')
                ->first();

            $jurnalPembalikIds = [];

            if ($jurnalWajib) {
                $jurnalPembalikIds[] = $this->jurnalKas->batalkanBarisInternal($jurnalWajib, $alasan, $userId)->id;
            } elseif ($nominalWajib > 0) {
                $jurnalPembalikIds[] = $this->jurnalKas->catat(
                    tipe: 'keluar',
                    kategori: 'simpanan_wajib_masuk',
                    kantong: 'simpanan',
                    jumlah: $nominalWajib,
                    keterangan: "[PEMBALIK SIMPANAN {$bulanPeriode}] {$alasan}",
                    referensiId: $anggotaId,
                    tanggal: now()->format('Y-m-d'),
                    userId: $userId,
                    subJudul: 'Jurnal Pembalik (Storno)',
                )->id;
            }

            if ($jurnalSosial) {
                $jurnalPembalikIds[] = $this->jurnalKas->batalkanBarisInternal($jurnalSosial, $alasan, $userId)->id;
            } elseif ($nominalSosial > 0) {
                $jurnalPembalikIds[] = $this->jurnalKas->catat(
                    tipe: 'keluar',
                    kategori: 'dana_sosial_bulanan',
                    kantong: 'dana_sosial',
                    jumlah: $nominalSosial,
                    keterangan: "[PEMBALIK DANA SOSIAL {$bulanPeriode}] {$alasan}",
                    referensiId: $anggotaId,
                    tanggal: now()->format('Y-m-d'),
                    userId: $userId,
                    subJudul: 'Jurnal Pembalik (Storno)',
                )->id;
            }

            Simpanan::whereIn('id', $daftarSimpanan->pluck('id'))->delete();

            AuditLog::catat(
                aksi: 'simpanan_konfirmasi_dibatalkan',
                keterangan: "Rollback simpanan wajib {$anggota->nama} periode {$bulanPeriode}: {$alasan}",
                dataLama: [
                    'anggota_id' => $anggotaId,
                    'bulan_periode' => $bulanPeriode,
                    'nominal_wajib' => $nominalWajib,
                    'nominal_dana_sosial' => $nominalSosial,
                    'simpanan_ids' => $daftarSimpanan->pluck('id')->all(),
                ],
                dataBaru: [
                    'jurnal_pembalik_ids' => $jurnalPembalikIds,
                    'alasan' => $alasan,
                ],
                userId: $userId,
            );
        });
    }
}
