<?php

namespace App\Services\Simpanan;

use App\Models\Anggota;
use App\Models\AuditLog;
use App\Models\JurnalKas;
use App\Models\SettingSimpanan;
use App\Models\Simpanan;
use App\Services\Keuangan\JurnalKasService;
use App\Services\Pinjaman\EligibilitasPinjamanService;
use Carbon\Carbon;
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
        $bulanSekarang = now()->format('Y-m');
        if ($bulanPeriode > $bulanSekarang) {
            throw new RuntimeException(
                "Simpanan wajib periode mendatang ({$bulanPeriode}) belum dapat dikonfirmasi. Gunakan fitur Bayar di Muka khusus anggota baru < 6 bulan."
            );
        }

        $nominalWajib = SettingSimpanan::where('jenis', 'wajib')->value('nominal') ?? 0;
        $nominalDanaSosial = SettingSimpanan::where('jenis', 'dana_sosial')->value('nominal') ?? 0;

        return DB::transaction(function () use ($anggotaIds, $bulanPeriode, $inputByUserId, $nominalWajib, $nominalDanaSosial) {
            // Lock baris anggota yang mau diproses, supaya proses lain tidak bisa
            // proses anggota yang sama secara bersamaan
            $anggotaTerkunci = Anggota::whereIn('id', $anggotaIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // Validasi tidak ada tunggakan simpanan wajib periode sebelumnya sebelum konfirmasi bulan ini
            $tunggakan = $this->simpananWajibTerlambat($bulanPeriode)
                ->first(fn (array $row) => in_array((int) $row['anggota_id'], array_map('intval', $anggotaIds), true));

            if ($tunggakan !== null) {
                throw new RuntimeException(
                    "Simpanan wajib {$tunggakan['nama']} periode {$bulanPeriode} tidak dapat dikonfirmasi karena periode {$tunggakan['bulan_periode_label']} masih menunggak di tab Terlambat."
                );
            }

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
        $awalBulan = Carbon::createFromFormat('Y-m', $bulanPeriode)->startOfMonth()->toDateString();
        $akhirBulan = Carbon::createFromFormat('Y-m', $bulanPeriode)->endOfMonth()->endOfDay()->toDateTimeString();

        $perAnggota = Simpanan::with('anggota:id,nama,no_anggota,no_karyawan,cabang,status,foto_url')
            ->whereIn('jenis', ['wajib', 'dana_sosial'])
            ->where(function ($q) use ($bulanPeriode, $awalBulan, $akhirBulan) {
                $q->where('bulan_periode', $bulanPeriode)
                    ->orWhere(fn ($qq) => $qq
                        ->where('bulan_periode', '<', $bulanPeriode)
                        ->whereBetween('tanggal_input', [$awalBulan, $akhirBulan]));
            })
            ->get()
            ->groupBy(fn ($s) => "{$s->anggota_id}|{$s->bulan_periode}");

        $maxPeriodePerAnggota = Simpanan::whereIn('anggota_id', $perAnggota->pluck('*.anggota_id')->flatten()->unique()->filter())
            ->where('jenis', 'wajib')
            ->selectRaw('anggota_id, MAX(bulan_periode) as max_periode')
            ->groupBy('anggota_id')
            ->pluck('max_periode', 'anggota_id');

        $labelPeriodeCache = [];
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
            $periode = (string) $wajib->bulan_periode;
            $adaPeriodeLebihBaru = ($maxPeriodePerAnggota[$anggota->id] ?? '') > $periode;

            $alasanTidakBisaBatal = null;
            if ($anggota->status !== 'aktif') {
                $alasanTidakBisaBatal = "Anggota ini berstatus {$anggota->status} sehingga konfirmasi simpanan tidak dapat dibatalkan.";
            } elseif ($adaPeriodeLebihBaru) {
                $alasanTidakBisaBatal = "Sudah ada konfirmasi simpanan wajib setelah periode {$periode}. Batalkan dari periode terbaru terlebih dahulu.";
            }

            if (! isset($labelPeriodeCache[$periode])) {
                $labelPeriodeCache[$periode] = preg_match('/^\d{4}-\d{2}$/', $periode)
                    ? Carbon::createFromFormat('Y-m', $periode)->translatedFormat('M Y')
                    : $periode;
            }

            $hasil->push([
                'id' => $anggota->id,
                'key' => "{$anggota->id}|{$periode}",
                'nama' => $anggota->nama,
                'no_anggota' => $anggota->no_anggota,
                'no_karyawan' => $anggota->no_karyawan,
                'cabang' => $anggota->cabang,
                'foto_url' => $anggota->foto_url,
                'status_anggota' => $anggota->status,
                'bulan_periode' => $periode,
                'bulan_periode_label' => $labelPeriodeCache[$periode],
                'dari_bulan_lain' => $periode !== $bulanPeriode,
                'nominal_wajib' => $nominalWajib,
                'nominal_dana_sosial' => $nominalSosial,
                'nominal_total' => $nominalWajib + $nominalSosial,
                'tanggal_input' => $wajib->tanggal_input?->format('d M Y'),
                'bisa_dibatalkan' => $alasanTidakBisaBatal === null,
                'alasan_tidak_bisa_batal' => $alasanTidakBisaBatal,
            ]);
        }

        return $hasil->sortBy('nama', SORT_NATURAL | SORT_FLAG_CASE)->values();
    }

    /**
     * Daftar tunggakan simpanan wajib dari bulan-bulan sebelumnya (sejak cutoff operasional
     * hingga sebelum bulan berjalan) yang belum memiliki catatan simpanan wajib.
     */
    public function simpananWajibTerlambat(string $bulanBerjalan, ?string $cabang = null, ?string $hanyaPeriode = null): Collection
    {
        $cutoff = (string) config('koperasi.simpanan_terlambat_mulai', '2026-10');
        if (! preg_match('/^\d{4}-\d{2}$/', $cutoff)) {
            $cutoff = '2026-10';
        }

        if ($hanyaPeriode !== null) {
            if (! preg_match('/^\d{4}-\d{2}$/', $hanyaPeriode) || $hanyaPeriode < $cutoff || $hanyaPeriode >= $bulanBerjalan) {
                return collect();
            }
            $daftarPeriode = [$hanyaPeriode];
        } else {
            if ($cutoff >= $bulanBerjalan) {
                return collect();
            }
            $daftarPeriode = [];
            $cursor = Carbon::createFromFormat('Y-m', $cutoff)->startOfMonth();
            $batas = Carbon::createFromFormat('Y-m', $bulanBerjalan)->startOfMonth();
            $iterasi = 0;

            while ($cursor->lt($batas) && $iterasi < 120) {
                $daftarPeriode[] = $cursor->format('Y-m');
                $cursor->addMonthNoOverflow();
                $iterasi++;
            }
        }

        if ($daftarPeriode === []) {
            return collect();
        }

        $daftarAnggota = Anggota::where('status', 'aktif')
            ->when($cabang !== null && $cabang !== '', fn ($q) => $q->where('cabang', $cabang))
            ->orderBy('nama')
            ->get(['id', 'nama', 'no_anggota', 'no_karyawan', 'cabang', 'foto_url', 'tanggal_jadi_anggota']);

        if ($daftarAnggota->isEmpty()) {
            return collect();
        }

        $sudah = [];
        $barisSudah = Simpanan::where('jenis', 'wajib')
            ->whereIn('anggota_id', $daftarAnggota->pluck('id'))
            ->whereIn('bulan_periode', $daftarPeriode)
            ->get(['anggota_id', 'bulan_periode']);

        foreach ($barisSudah as $row) {
            $sudah["{$row->anggota_id}|{$row->bulan_periode}"] = true;
        }

        $nominalWajib = (float) (SettingSimpanan::where('jenis', 'wajib')->value('nominal') ?? 0);
        $nominalDanaSosial = (float) (SettingSimpanan::where('jenis', 'dana_sosial')->value('nominal') ?? 0);
        $nominalPerAnggota = $nominalWajib + $nominalDanaSosial;

        $labelPeriode = [];
        foreach ($daftarPeriode as $p) {
            $labelPeriode[$p] = Carbon::createFromFormat('Y-m', $p)->translatedFormat('M Y');
        }

        $hasil = collect();
        foreach ($daftarAnggota as $a) {
            $bulanGabung = $a->tanggal_jadi_anggota?->format('Y-m');
            foreach ($daftarPeriode as $periode) {
                if ($bulanGabung !== null && $periode < $bulanGabung) {
                    continue;
                }
                if (isset($sudah["{$a->id}|{$periode}"])) {
                    continue;
                }

                $hasil->push([
                    'id' => "{$a->id}|{$periode}",
                    'anggota_id' => $a->id,
                    'nama' => $a->nama,
                    'no_anggota' => $a->no_anggota,
                    'no_karyawan' => $a->no_karyawan,
                    'cabang' => $a->cabang,
                    'foto_url' => $a->foto_url,
                    'bulan_periode' => $periode,
                    'bulan_periode_label' => $labelPeriode[$periode],
                    'nominal' => $nominalPerAnggota,
                    'terlambat' => true,
                ]);
            }
        }

        return $hasil->sort(function (array $a, array $b) {
            $cmpPeriode = strcmp($a['bulan_periode'], $b['bulan_periode']);
            if ($cmpPeriode !== 0) {
                return $cmpPeriode;
            }

            return strnatcasecmp($a['nama'], $b['nama']);
        })->values();
    }

    /**
     * Konfirmasi massal simpanan wajib + dana sosial lintas periode (khusus tab Terlambat).
     *
     * @param  array<int, array{anggota_id: int|string, bulan_periode: string}>  $items
     */
    public function konfirmasiMassalMultiPeriode(array $items, int $inputByUserId): int
    {
        $perPeriode = [];
        foreach ($items as $item) {
            $anggotaId = (int) ($item['anggota_id'] ?? 0);
            $periode = trim((string) ($item['bulan_periode'] ?? ''));
            if ($anggotaId <= 0 || ! preg_match('/^\d{4}-\d{2}$/', $periode)) {
                continue;
            }
            $perPeriode[$periode][$anggotaId] = $anggotaId;
        }

        if ($perPeriode === []) {
            return 0;
        }

        $bulanSekarang = now()->format('Y-m');
        foreach (array_keys($perPeriode) as $periode) {
            if ($periode > $bulanSekarang) {
                throw new RuntimeException(
                    "Simpanan wajib periode mendatang ({$periode}) belum dapat dikonfirmasi."
                );
            }
        }

        ksort($perPeriode);

        return DB::transaction(function () use ($perPeriode, $inputByUserId) {
            $total = 0;
            foreach ($perPeriode as $periode => $idsMap) {
                $total += $this->konfirmasiMassal(array_values($idsMap), $periode, $inputByUserId);
            }

            return $total;
        });
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

            $adaPeriodeLebihBaru = Simpanan::where('anggota_id', $anggotaId)
                ->where('jenis', 'wajib')
                ->where('bulan_periode', '>', $bulanPeriode)
                ->exists();

            if ($adaPeriodeLebihBaru) {
                throw new RuntimeException(
                    "Konfirmasi simpanan periode {$bulanPeriode} tidak dapat dibatalkan karena sudah ada konfirmasi simpanan wajib pada periode setelahnya. Batalkan dari periode terbaru terlebih dahulu."
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

    /**
     * Daftar anggota aktif dengan masa keanggotaan < 6 bulan yang simpanan wajibnya
     * belum genap 6 bulan, beserta rincian kekurangan & periode berikutnya.
     */
    public function anggotaBaruBelumGenapEnamBulan(?string $cabang = null, ?string $mulaiBulan = null): Collection
    {
        $minBulan = EligibilitasPinjamanService::MIN_BULAN_KEANGGOTAAN;
        $batasEnamBulan = now()->startOfDay()->subMonths($minBulan);

        $daftarAnggota = Anggota::where('status', 'aktif')
            ->where(function ($q) use ($batasEnamBulan) {
                $q->whereNull('tanggal_jadi_anggota')
                    ->orWhere('tanggal_jadi_anggota', '>', $batasEnamBulan);
            })
            ->when($cabang, fn ($q) => $q->where('cabang', $cabang))
            ->orderBy('nama')
            ->get();

        if ($daftarAnggota->isEmpty()) {
            return collect();
        }

        $simpananPerAnggota = Simpanan::whereIn('anggota_id', $daftarAnggota->pluck('id'))
            ->where('jenis', 'wajib')
            ->get(['anggota_id', 'bulan_periode'])
            ->groupBy('anggota_id');

        $nominalWajib = (float) (SettingSimpanan::where('jenis', 'wajib')->value('nominal') ?? 45_000);
        $nominalDanaSosial = (float) (SettingSimpanan::where('jenis', 'dana_sosial')->value('nominal') ?? 5_000);
        $nominalPerBulan = $nominalWajib + $nominalDanaSosial;
        $eligibilitas = app(EligibilitasPinjamanService::class);

        $hasil = collect();

        foreach ($daftarAnggota as $a) {
            $masaBulan = $eligibilitas->masaKeanggotaanBulan($a);
            if ($masaBulan >= $minBulan) {
                continue;
            }

            $periodeSudahAda = ($simpananPerAnggota->get($a->id) ?? collect())
                ->pluck('bulan_periode')
                ->filter()
                ->unique()
                ->values()
                ->all();

            $bulanTerbayar = count($periodeSudahAda);
            $kekurangan = max(0, $minBulan - $bulanTerbayar);

            if ($kekurangan === 0) {
                continue;
            }

            $hasil->push([
                'id' => $a->id,
                'nama' => $a->nama,
                'no_anggota' => $a->no_anggota,
                'no_karyawan' => $a->no_karyawan,
                'cabang' => $a->cabang,
                'foto_url' => $a->foto_url,
                'tanggal_jadi_anggota' => $a->tanggal_jadi_anggota?->format('d M Y'),
                'masa_bulan' => $masaBulan,
                'bulan_terbayar' => $bulanTerbayar,
                'kekurangan_bulan' => $kekurangan,
                'nominal_wajib' => $nominalWajib,
                'nominal_dana_sosial' => $nominalDanaSosial,
                'nominal_per_bulan' => $nominalPerBulan,
                'total_pelunasan' => (float) ($kekurangan * $nominalPerBulan),
                'periode_berikutnya' => $this->periodeBerikutnyaBelumBayar($periodeSudahAda, $kekurangan, $mulaiBulan),
            ]);
        }

        return $hasil->values();
    }

    /**
     * Catat pembayaran simpanan wajib + dana sosial di muka untuk melengkapi
     * kekurangan syarat 6 bulan keanggotaan.
     *
     * @return array{
     *     anggota_nama: string,
     *     jumlah_bulan: int,
     *     periode: array<int, string>,
     *     total_wajib: float,
     *     total_dana_sosial: float,
     *     total_nominal: float
     * }
     */
    public function bayarDiMuka(int $anggotaId, int $jumlahBulan, int $inputByUserId, ?string $mulaiBulan = null): array
    {
        $minBulan = EligibilitasPinjamanService::MIN_BULAN_KEANGGOTAAN;

        if ($jumlahBulan < 1 || $jumlahBulan > $minBulan) {
            throw new RuntimeException("Jumlah bulan pembayaran di muka harus antara 1 sampai {$minBulan} bulan.");
        }

        $nominalWajib = (float) (SettingSimpanan::where('jenis', 'wajib')->value('nominal') ?? 45_000);
        $nominalDanaSosial = (float) (SettingSimpanan::where('jenis', 'dana_sosial')->value('nominal') ?? 5_000);

        return DB::transaction(function () use ($anggotaId, $jumlahBulan, $inputByUserId, $mulaiBulan, $minBulan, $nominalWajib, $nominalDanaSosial) {
            $anggota = Anggota::lockForUpdate()->findOrFail($anggotaId);

            if ($anggota->status !== 'aktif') {
                throw new RuntimeException("Anggota {$anggota->nama} berstatus '{$anggota->status}', tidak dapat melakukan pembayaran simpanan di muka.");
            }

            $periodeSudahAda = Simpanan::where('anggota_id', $anggotaId)
                ->where('jenis', 'wajib')
                ->lockForUpdate()
                ->pluck('bulan_periode')
                ->filter()
                ->unique()
                ->values()
                ->all();

            $kekurangan = max(0, $minBulan - count($periodeSudahAda));

            if ($kekurangan === 0) {
                throw new RuntimeException('Anggota ini sudah melunasi minimal 6 bulan simpanan wajib.');
            }

            if ($jumlahBulan > $kekurangan) {
                throw new RuntimeException("Kekurangan simpanan wajib anggota ini hanya {$kekurangan} bulan.");
            }

            $daftarPeriode = $this->periodeBerikutnyaBelumBayar($periodeSudahAda, $jumlahBulan, $mulaiBulan);

            $sekarang = now();
            $tanggalJurnal = $sekarang->format('Y-m-d');
            $barisWajib = [];
            $barisSosial = [];

            foreach ($daftarPeriode as $periode) {
                $barisWajib[] = [
                    'anggota_id' => $anggotaId,
                    'jenis' => 'wajib',
                    'jumlah' => $nominalWajib,
                    'bulan_periode' => $periode,
                    'tanggal_input' => $sekarang,
                    'input_by' => $inputByUserId,
                    'created_at' => $sekarang,
                    'updated_at' => $sekarang,
                ];

                $barisSosial[] = [
                    'anggota_id' => $anggotaId,
                    'jenis' => 'dana_sosial',
                    'jumlah' => $nominalDanaSosial,
                    'bulan_periode' => $periode,
                    'tanggal_input' => $sekarang,
                    'input_by' => $inputByUserId,
                    'created_at' => $sekarang,
                    'updated_at' => $sekarang,
                ];
            }

            Simpanan::insert($barisWajib);
            Simpanan::insert($barisSosial);

            foreach ($daftarPeriode as $periode) {
                $this->jurnalKas->catat(
                    tipe: 'masuk',
                    kategori: 'dana_sosial_bulanan',
                    kantong: 'dana_sosial',
                    jumlah: $nominalDanaSosial,
                    keterangan: "Dana sosial bulan {$periode}",
                    referensiId: $anggotaId,
                    tanggal: $tanggalJurnal,
                    userId: $inputByUserId,
                );

                $this->jurnalKas->catat(
                    tipe: 'masuk',
                    kategori: 'simpanan_wajib_masuk',
                    kantong: 'simpanan',
                    jumlah: $nominalWajib,
                    keterangan: "Simpanan wajib bulan {$periode}",
                    referensiId: $anggotaId,
                    tanggal: $tanggalJurnal,
                    userId: $inputByUserId,
                    subJudul: 'Simpanan wajib masuk',
                );
            }

            $jumlahTercatat = count($daftarPeriode);
            $totalWajib = (float) ($jumlahTercatat * $nominalWajib);
            $totalDanaSosial = (float) ($jumlahTercatat * $nominalDanaSosial);
            $totalNominal = $totalWajib + $totalDanaSosial;

            AuditLog::catat(
                aksi: 'simpanan_bayar_dimuka',
                keterangan: "Pembayaran simpanan di muka {$anggota->nama} sebanyak {$jumlahTercatat} bulan (".implode(', ', $daftarPeriode).') — total Rp '.number_format($totalNominal, 0, ',', '.'),
                dataLama: [
                    'anggota_id' => $anggotaId,
                    'bulan_terbayar_sebelum' => count($periodeSudahAda),
                    'periode_sebelum' => $periodeSudahAda,
                ],
                dataBaru: [
                    'anggota_id' => $anggotaId,
                    'jumlah_bulan' => $jumlahTercatat,
                    'periode' => $daftarPeriode,
                    'total_wajib' => $totalWajib,
                    'total_dana_sosial' => $totalDanaSosial,
                    'total_nominal' => $totalNominal,
                ],
                userId: $inputByUserId,
            );

            return [
                'anggota_nama' => $anggota->nama,
                'jumlah_bulan' => $jumlahTercatat,
                'periode' => $daftarPeriode,
                'total_wajib' => $totalWajib,
                'total_dana_sosial' => $totalDanaSosial,
                'total_nominal' => $totalNominal,
            ];
        });
    }

    /**
     * Hasilkan daftar periode YYYY-MM berikutnya yang belum pernah dibayar anggota.
     *
     * @param  array<int, string>  $periodeSudahAda
     * @return array<int, string>
     */
    private function periodeBerikutnyaBelumBayar(array $periodeSudahAda, int $jumlahBulan, ?string $mulaiBulan = null): array
    {
        $sudah = array_flip($periodeSudahAda);
        $cursor = $mulaiBulan
            ? Carbon::createFromFormat('Y-m', $mulaiBulan)->startOfMonth()
            : now()->startOfMonth();

        $hasil = [];
        $iterasi = 0;

        while (count($hasil) < $jumlahBulan && $iterasi < 60) {
            $kode = $cursor->format('Y-m');
            if (! isset($sudah[$kode])) {
                $hasil[] = $kode;
            }
            $cursor->addMonthNoOverflow();
            $iterasi++;
        }

        return $hasil;
    }
}
