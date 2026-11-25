<?php

namespace App\Services\Tabungan;

use App\Models\AuditLog;
use App\Models\RekeningTabungan;
use App\Models\SettingBungaTabungan;
use App\Models\SettingTabungan;
use App\Models\TabunganBungaPeriode;
use App\Models\TabunganTransaksi;
use App\Services\Dokumen\PenomoranDokumenService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PerhitunganBungaTabunganService
{
    public function __construct(private PenomoranDokumenService $penomoran) {}

    /**
     * Hitung bunga tabungan untuk satu rekening pada periode YYYY-MM (opsional sampai batasTanggal).
     * Mendukung perubahan tarif bunga di tengah bulan dengan memecah segmen per tanggal berlaku.
     *
     * @param  array<string, mixed>|null  $konteks
     */
    public function hitungPeriode(RekeningTabungan $rekening, string $periode, ?Carbon $batasTanggal = null, ?array $konteks = null): array
    {
        if (! preg_match('/^\d{4}-\d{2}$/', $periode)) {
            throw new RuntimeException("Format periode '{$periode}' tidak valid (wajib YYYY-MM).");
        }

        $setting = $konteks['setting'] ?? SettingTabungan::current();
        $metode = $setting->metode_bunga ?: 'saldo_harian';

        $awalBulan = Carbon::createFromFormat('Y-m', $periode)->startOfMonth()->startOfDay();
        $akhirBulan = $awalBulan->copy()->endOfMonth()->startOfDay();

        $rateKosong = function () use ($konteks, $akhirBulan): float {
            if (isset($konteks['tarif_histori'], $konteks['default_rate'])) {
                return $this->cariTarifTanggal($konteks['tarif_histori'], $akhirBulan->toDateString(), (float) $konteks['default_rate']);
            }

            return SettingBungaTabungan::persentaseAktif($akhirBulan);
        };

        if (! $rekening->tanggal_buka) {
            return $this->hasilKosong($periode, $metode, $rateKosong());
        }

        $tanggalBuka = $rekening->tanggal_buka->copy()->startOfDay();
        $mulai = $tanggalBuka->greaterThan($awalBulan) ? $tanggalBuka : $awalBulan->copy();

        $akhir = $batasTanggal
            ? ($batasTanggal->copy()->startOfDay()->lessThan($akhirBulan) ? $batasTanggal->copy()->startOfDay() : $akhirBulan->copy())
            : $akhirBulan->copy();

        if ($rekening->tanggal_tutup) {
            $tutup = $rekening->tanggal_tutup->copy()->startOfDay();
            if ($tutup->lessThan($akhir)) {
                $akhir = $tutup;
            }
        }

        if ($mulai->greaterThan($akhir)) {
            return $this->hasilKosong($periode, $metode, $rateKosong());
        }

        $saldoSebelumPeriode = isset($konteks['saldo_sebelum'])
            ? (float) $konteks['saldo_sebelum']
            : $this->saldoSebelumTanggal($rekening->id, $mulai);

        $mutasiRentang = $konteks['mutasi_rentang']
            ?? TabunganTransaksi::where('rekening_tabungan_id', $rekening->id)
                ->whereNull('dibatalkan_pada')
                ->whereNull('koreksi_dari_id')
                ->where('tanggal', '>=', $mulai->toDateString())
                ->where('tanggal', '<', $akhir->copy()->addDay()->toDateString())
                ->orderBy('tanggal')
                ->orderBy('id')
                ->get(['tanggal', 'jenis', 'nominal']);

        $deltaPerHari = [];
        foreach ($mutasiRentang as $trx) {
            $tgl = $trx->tanggal->toDateString();
            $delta = $trx->isKredit() ? (float) $trx->nominal : -(float) $trx->nominal;
            $deltaPerHari[$tgl] = ($deltaPerHari[$tgl] ?? 0.0) + $delta;
        }

        if (isset($konteks['tarif_histori']) && array_key_exists('default_rate', $konteks)) {
            $tarifHistori = $konteks['tarif_histori'];
            $defaultRate = (float) $konteks['default_rate'];
        } else {
            $semuaTarif = SettingBungaTabungan::orderByDesc('berlaku_dari_tanggal')
                ->orderByDesc('id')
                ->get(['persentase', 'berlaku_dari_tanggal']);
            $akhirStr = $akhir->toDateString();
            $tarifHistori = $semuaTarif
                ->filter(fn ($t) => $t->berlaku_dari_tanggal->toDateString() <= $akhirStr)
                ->values();
            $tarifFallback = $semuaTarif->sortBy([['berlaku_dari_tanggal', 'asc'], ['id', 'asc']])->first()?->persentase;
            $defaultRate = $tarifFallback !== null ? (float) $tarifFallback : 2.00;
        }

        $saldoBerjalan = $saldoSebelumPeriode;
        $daftarSaldoHarian = [];
        $segmen = [];
        $totalBungaHarian = 0.0;
        $sumRate = 0.0;
        $jumlahHari = 0;

        $cursor = $mulai->copy();
        while ($cursor->lte($akhir)) {
            $tglStr = $cursor->toDateString();
            if (isset($deltaPerHari[$tglStr])) {
                $saldoBerjalan += $deltaPerHari[$tglStr];
            }

            $saldoPositif = max(0.0, round($saldoBerjalan, 2));
            $rate = $this->cariTarifTanggal($tarifHistori, $tglStr, $defaultRate);
            $bungaHari = ($metode === 'saldo_harian')
                ? ($saldoPositif * ($rate / 100) / 365)
                : 0.0;

            $daftarSaldoHarian[] = $saldoPositif;
            $totalBungaHarian += $bungaHari;
            $sumRate += $rate;
            $jumlahHari++;

            $lastIdx = count($segmen) - 1;
            if ($lastIdx >= 0 && abs($segmen[$lastIdx]['persentase'] - $rate) < 0.0001) {
                $segmen[$lastIdx]['sampai'] = $tglStr;
                $segmen[$lastIdx]['hari']++;
                $segmen[$lastIdx]['bunga_raw'] += $bungaHari;
            } else {
                $segmen[] = [
                    'persentase' => round($rate, 2),
                    'dari' => $tglStr,
                    'sampai' => $tglStr,
                    'hari' => 1,
                    'bunga_raw' => $bungaHari,
                ];
            }

            $cursor->addDay();
        }

        $rataRate = $jumlahHari > 0 ? round($sumRate / $jumlahHari, 2) : $defaultRate;
        $rataSaldo = $jumlahHari > 0 ? round(array_sum($daftarSaldoHarian) / $jumlahHari, 2) : 0.0;
        $saldoTerendah = ! empty($daftarSaldoHarian) ? round(min($daftarSaldoHarian), 2) : 0.0;

        if ($metode === 'saldo_terendah') {
            $saldoDasar = $saldoTerendah;
            $rateAkhir = $this->cariTarifTanggal($tarifHistori, $akhir->toDateString(), $defaultRate);
            $nominalBunga = round($saldoDasar * ($rateAkhir / 100) / 12, 2);
            $sukuSnapshot = round($rateAkhir, 2);
            $rincianTarif = [[
                'persentase' => $sukuSnapshot,
                'dari' => $mulai->toDateString(),
                'sampai' => $akhir->toDateString(),
                'hari' => $jumlahHari,
                'bunga' => $nominalBunga,
            ]];
        } elseif ($metode === 'rata_rata_harian') {
            $saldoDasar = $rataSaldo;
            $nominalBunga = round($saldoDasar * ($rataRate / 100) / 12, 2);
            $sukuSnapshot = $rataRate;
            $rincianTarif = [[
                'persentase' => $sukuSnapshot,
                'dari' => $mulai->toDateString(),
                'sampai' => $akhir->toDateString(),
                'hari' => $jumlahHari,
                'bunga' => $nominalBunga,
            ]];
        } else {
            $saldoDasar = $rataSaldo;
            $nominalBunga = round($totalBungaHarian, 2);
            $sukuSnapshot = $rataRate;
            $rincianTarif = array_map(fn (array $s) => [
                'persentase' => $s['persentase'],
                'dari' => $s['dari'],
                'sampai' => $s['sampai'],
                'hari' => $s['hari'],
                'bunga' => round($s['bunga_raw'], 2),
            ], $segmen);
        }

        return [
            'periode' => $periode,
            'metode_hitung' => $metode,
            'suku_bunga_snapshot' => $sukuSnapshot,
            'rincian_tarif' => $rincianTarif,
            'saldo_dasar' => $saldoDasar,
            'jumlah_hari' => $jumlahHari,
            'nominal_bunga' => $nominalBunga,
            'pajak_bunga' => 0.0,
            'bunga_bersih' => $nominalBunga,
        ];
    }

    /**
     * Estimasi akrual bunga bulan berjalan (real-time hingga hari ini).
     */
    public function estimasiBulanBerjalan(RekeningTabungan $rekening): array
    {
        $periode = now()->format('Y-m');
        $sudah = TabunganBungaPeriode::where('rekening_tabungan_id', $rekening->id)
            ->where('periode', $periode)
            ->first();

        if ($sudah) {
            return [
                'periode' => $periode,
                'sudah_diposting' => true,
                'metode_hitung' => $sudah->metode_hitung_snapshot,
                'suku_bunga_snapshot' => (float) $sudah->suku_bunga_snapshot,
                'rincian_tarif' => $sudah->rincian_tarif_json ?? [],
                'saldo_dasar' => (float) $sudah->saldo_dasar,
                'jumlah_hari' => (int) $sudah->jumlah_hari,
                'nominal_bunga' => (float) $sudah->nominal_bunga,
                'pajak_bunga' => (float) $sudah->pajak_bunga,
                'bunga_bersih' => (float) $sudah->bunga_bersih,
            ];
        }

        $hasil = $this->hitungPeriode($rekening, $periode, now());
        $hasil['sudah_diposting'] = false;

        return $hasil;
    }

    /**
     * Pratinjau seluruh rekening aktif sebelum/sesudah posting bunga periode YYYY-MM.
     */
    public function previewPostingPeriode(string $periode): array
    {
        if (! preg_match('/^\d{4}-\d{2}$/', $periode)) {
            throw new RuntimeException("Format periode '{$periode}' tidak valid.");
        }

        $awalBulan = Carbon::createFromFormat('Y-m', $periode)->startOfMonth()->startOfDay();
        $akhirBulan = $awalBulan->copy()->endOfMonth()->startOfDay();
        $batasHitung = $periode === now()->format('Y-m') ? now() : $akhirBulan;
        $batasHari = $batasHitung->copy()->startOfDay();

        $daftarRekening = RekeningTabungan::with('anggota:id,nama,no_karyawan,cabang')
            ->where('status', 'aktif')
            ->where('tanggal_buka', '<', $akhirBulan->copy()->addDay()->toDateString())
            ->orderBy('no_rekening')
            ->get();

        $sudahMap = TabunganBungaPeriode::where('periode', $periode)
            ->whereIn('rekening_tabungan_id', $daftarRekening->pluck('id'))
            ->get()
            ->keyBy('rekening_tabungan_id');

        $belumIds = $daftarRekening->reject(fn ($r) => $sudahMap->has($r->id))->pluck('id')->all();

        $konteksBatch = [];
        $saldoAwalMap = collect();
        $mutasiMap = collect();

        if (! empty($belumIds)) {
            $setting = SettingTabungan::current();
            $semuaTarif = SettingBungaTabungan::orderByDesc('berlaku_dari_tanggal')
                ->orderByDesc('id')
                ->get(['persentase', 'berlaku_dari_tanggal']);
            $batasStr = $batasHari->toDateString();
            $tarifHistori = $semuaTarif
                ->filter(fn ($t) => $t->berlaku_dari_tanggal->toDateString() <= $batasStr)
                ->values();
            $defaultRate = (float) ($semuaTarif->sortBy([['berlaku_dari_tanggal', 'asc'], ['id', 'asc']])->first()?->persentase ?? 2.00);

            $konteksBatch = [
                'setting' => $setting,
                'tarif_histori' => $tarifHistori,
                'default_rate' => $defaultRate,
            ];

            $awalBulanStr = $awalBulan->toDateString();
            $saldoAwalMap = TabunganTransaksi::whereIn('rekening_tabungan_id', $belumIds)
                ->whereNull('dibatalkan_pada')
                ->whereNull('koreksi_dari_id')
                ->where('tanggal', '<', $awalBulanStr)
                ->selectRaw("rekening_tabungan_id, COALESCE(SUM(CASE WHEN jenis IN ('setor', 'bunga', 'koreksi_masuk') THEN nominal ELSE -nominal END), 0) as saldo")
                ->groupBy('rekening_tabungan_id')
                ->pluck('saldo', 'rekening_tabungan_id');

            $mutasiMap = TabunganTransaksi::whereIn('rekening_tabungan_id', $belumIds)
                ->whereNull('dibatalkan_pada')
                ->whereNull('koreksi_dari_id')
                ->where('tanggal', '>=', $awalBulanStr)
                ->where('tanggal', '<', $batasHari->copy()->addDay()->toDateString())
                ->orderBy('tanggal')
                ->orderBy('id')
                ->get(['rekening_tabungan_id', 'tanggal', 'jenis', 'nominal'])
                ->groupBy('rekening_tabungan_id');
        }

        $baris = [];
        $belumDiposting = 0;
        $totalBunga = 0.0;

        foreach ($daftarRekening as $rek) {
            $sudah = $sudahMap->get($rek->id);
            if ($sudah) {
                $bunga = (float) $sudah->bunga_bersih;
                $baris[] = [
                    'rekening_id' => $rek->id,
                    'no_rekening' => $rek->no_rekening,
                    'nama' => $rek->anggota?->nama ?? '-',
                    'no_karyawan' => $rek->anggota?->no_karyawan ?? '-',
                    'saldo_sekarang' => (float) $rek->saldo,
                    'saldo_dasar' => (float) $sudah->saldo_dasar,
                    'jumlah_hari' => (int) $sudah->jumlah_hari,
                    'suku_bunga_snapshot' => (float) $sudah->suku_bunga_snapshot,
                    'bunga_bersih' => $bunga,
                    'sudah_diposting' => true,
                    'diposting_pada' => $sudah->diposting_pada?->format('d M Y H:i'),
                ];
                $totalBunga += $bunga;
            } else {
                $saldoSebelumRek = (float) ($saldoAwalMap[$rek->id] ?? 0);
                $semuaMutasiRek = $mutasiMap->get($rek->id, collect());

                $mulaiRek = ($rek->tanggal_buka && $rek->tanggal_buka->copy()->startOfDay()->greaterThan($awalBulan))
                    ? $rek->tanggal_buka->copy()->startOfDay()
                    : $awalBulan;
                $akhirRek = $batasHari->lessThan($akhirBulan) ? $batasHari : $akhirBulan;
                if ($rek->tanggal_tutup && $rek->tanggal_tutup->copy()->startOfDay()->lessThan($akhirRek)) {
                    $akhirRek = $rek->tanggal_tutup->copy()->startOfDay();
                }

                $mulaiStr = $mulaiRek->toDateString();
                $akhirStr = $akhirRek->toDateString();
                $mutasiRentangRek = collect();
                foreach ($semuaMutasiRek as $trx) {
                    $tglTrx = $trx->tanggal->toDateString();
                    if ($tglTrx < $mulaiStr) {
                        $saldoSebelumRek += $trx->isKredit() ? (float) $trx->nominal : -(float) $trx->nominal;
                    } elseif ($tglTrx <= $akhirStr) {
                        $mutasiRentangRek->push($trx);
                    }
                }

                $hitung = $this->hitungPeriode($rek, $periode, $batasHitung, [
                    ...$konteksBatch,
                    'saldo_sebelum' => $saldoSebelumRek,
                    'mutasi_rentang' => $mutasiRentangRek,
                ]);
                $belumDiposting++;
                $totalBunga += $hitung['bunga_bersih'];
                $baris[] = [
                    'rekening_id' => $rek->id,
                    'no_rekening' => $rek->no_rekening,
                    'nama' => $rek->anggota?->nama ?? '-',
                    'no_karyawan' => $rek->anggota?->no_karyawan ?? '-',
                    'saldo_sekarang' => (float) $rek->saldo,
                    'saldo_dasar' => $hitung['saldo_dasar'],
                    'jumlah_hari' => $hitung['jumlah_hari'],
                    'suku_bunga_snapshot' => $hitung['suku_bunga_snapshot'],
                    'bunga_bersih' => $hitung['bunga_bersih'],
                    'sudah_diposting' => false,
                    'diposting_pada' => null,
                ];
            }
        }

        return [
            'periode' => $periode,
            'total_rekening' => count($baris),
            'belum_diposting' => $belumDiposting,
            'sudah_diposting' => count($baris) - $belumDiposting,
            'total_bunga' => round($totalBunga, 2),
            'baris' => $baris,
        ];
    }

    /**
     * Posting bunga bulanan ke seluruh rekening aktif (idempoten per periode).
     *
     * @return array{periode: string, diproses: int, dilewati: int, total_bunga: float}
     */
    public function postingPeriode(string $periode, ?int $aktorId = null, ?Carbon $tanggalPosting = null): array
    {
        if (! preg_match('/^\d{4}-\d{2}$/', $periode)) {
            throw new RuntimeException("Format periode '{$periode}' tidak valid.");
        }

        $akhirBulan = Carbon::createFromFormat('Y-m', $periode)->endOfMonth()->startOfDay();
        $tanggalEfektif = ($tanggalPosting ?? $akhirBulan)->copy()->startOfDay();

        $daftarIds = RekeningTabungan::where('status', 'aktif')
            ->where('tanggal_buka', '<', $akhirBulan->copy()->addDay()->toDateString())
            ->orderBy('id')
            ->pluck('id');

        $konteksPosting = [];
        if ($daftarIds->isNotEmpty()) {
            $setting = SettingTabungan::current();
            $semuaTarif = SettingBungaTabungan::orderByDesc('berlaku_dari_tanggal')
                ->orderByDesc('id')
                ->get(['persentase', 'berlaku_dari_tanggal']);
            $batasStr = ($akhirBulan->lessThan($tanggalEfektif) ? $akhirBulan : $tanggalEfektif)->toDateString();
            $konteksPosting = [
                'setting' => $setting,
                'tarif_histori' => $semuaTarif->filter(fn ($t) => $t->berlaku_dari_tanggal->toDateString() <= $batasStr)->values(),
                'default_rate' => (float) ($semuaTarif->sortBy([['berlaku_dari_tanggal', 'asc'], ['id', 'asc']])->first()?->persentase ?? 2.00),
            ];
        }

        $diproses = 0;
        $dilewati = 0;
        $totalBunga = 0.0;

        foreach ($daftarIds as $rekeningId) {
            $hasilRek = DB::transaction(function () use ($rekeningId, $periode, $tanggalEfektif, $aktorId, $konteksPosting) {
                $rekening = RekeningTabungan::lockForUpdate()->find($rekeningId);
                if (! $rekening || $rekening->status !== 'aktif') {
                    return ['dilewati' => true, 'bunga' => 0.0];
                }

                $sudahAda = TabunganBungaPeriode::where('rekening_tabungan_id', $rekening->id)
                    ->where('periode', $periode)
                    ->exists();

                if ($sudahAda) {
                    return ['dilewati' => true, 'bunga' => 0.0];
                }

                $hitung = $this->hitungPeriode($rekening, $periode, $tanggalEfektif, $konteksPosting);
                $bunga = $hitung['bunga_bersih'];
                $transaksiId = null;

                if ($bunga > 0) {
                    $transaksi = $this->kreditkanTransaksiBunga(
                        rekening: $rekening,
                        periode: $periode,
                        hitung: $hitung,
                        tanggal: $tanggalEfektif,
                        aktorId: $aktorId,
                        keterangan: "Bunga tabungan periode {$periode} ({$hitung['suku_bunga_snapshot']}% p.a.)",
                    );
                    $transaksiId = $transaksi->id;
                }

                TabunganBungaPeriode::create([
                    'rekening_tabungan_id' => $rekening->id,
                    'periode' => $periode,
                    'metode_hitung_snapshot' => $hitung['metode_hitung'],
                    'suku_bunga_snapshot' => $hitung['suku_bunga_snapshot'],
                    'rincian_tarif_json' => $hitung['rincian_tarif'],
                    'saldo_dasar' => $hitung['saldo_dasar'],
                    'jumlah_hari' => $hitung['jumlah_hari'],
                    'nominal_bunga' => $hitung['nominal_bunga'],
                    'pajak_bunga' => $hitung['pajak_bunga'],
                    'bunga_bersih' => $bunga,
                    'transaksi_id' => $transaksiId,
                    'diposting_oleh' => $aktorId,
                    'diposting_pada' => now(),
                ]);

                return ['dilewati' => false, 'bunga' => $bunga];
            });

            if ($hasilRek['dilewati']) {
                $dilewati++;
            } else {
                $diproses++;
                $totalBunga += $hasilRek['bunga'];
            }
        }

        if ($diproses > 0) {
            AuditLog::catat(
                aksi: 'tabungan_bunga_diposting',
                keterangan: "Posting bunga tabungan periode {$periode} untuk {$diproses} rekening sebesar Rp ".number_format($totalBunga, 0, ',', '.'),
                dataBaru: [
                    'periode' => $periode,
                    'diproses' => $diproses,
                    'dilewati' => $dilewati,
                    'total_bunga' => round($totalBunga, 2),
                ],
                userId: $aktorId,
            );
        }

        return [
            'periode' => $periode,
            'diproses' => $diproses,
            'dilewati' => $dilewati,
            'total_bunga' => round($totalBunga, 2),
        ];
    }

    /**
     * Kreditkan bunga berjalan pro-rata saat penutupan rekening atau resign.
     * Harus dipanggil di dalam DB transaction pemanggil dengan rekening sudah di-lock.
     */
    public function kreditkanBungaPenutupan(RekeningTabungan $rekening, Carbon $tanggalTutup, ?int $aktorId = null): float
    {
        $periode = $tanggalTutup->format('Y-m');
        $sudahAda = TabunganBungaPeriode::where('rekening_tabungan_id', $rekening->id)
            ->where('periode', $periode)
            ->exists();

        if ($sudahAda) {
            return 0.0;
        }

        $hitung = $this->hitungPeriode($rekening, $periode, $tanggalTutup);
        $bunga = $hitung['bunga_bersih'];
        $transaksiId = null;

        if ($bunga > 0) {
            $transaksi = $this->kreditkanTransaksiBunga(
                rekening: $rekening,
                periode: $periode,
                hitung: $hitung,
                tanggal: $tanggalTutup,
                aktorId: $aktorId,
                keterangan: "Bunga tabungan pro-rata penutupan periode {$periode} ({$hitung['suku_bunga_snapshot']}% p.a.)",
            );
            $transaksiId = $transaksi->id;
        }

        TabunganBungaPeriode::create([
            'rekening_tabungan_id' => $rekening->id,
            'periode' => $periode,
            'metode_hitung_snapshot' => $hitung['metode_hitung'],
            'suku_bunga_snapshot' => $hitung['suku_bunga_snapshot'],
            'rincian_tarif_json' => $hitung['rincian_tarif'],
            'saldo_dasar' => $hitung['saldo_dasar'],
            'jumlah_hari' => $hitung['jumlah_hari'],
            'nominal_bunga' => $hitung['nominal_bunga'],
            'pajak_bunga' => $hitung['pajak_bunga'],
            'bunga_bersih' => $bunga,
            'transaksi_id' => $transaksiId,
            'catatan' => 'Penutupan rekening pro-rata',
            'diposting_oleh' => $aktorId,
            'diposting_pada' => now(),
        ]);

        return $bunga;
    }

    private function kreditkanTransaksiBunga(
        RekeningTabungan $rekening,
        string $periode,
        array $hitung,
        Carbon $tanggal,
        ?int $aktorId,
        string $keterangan,
    ): TabunganTransaksi {
        $bunga = (float) $hitung['bunga_bersih'];
        $saldoSebelum = (float) $rekening->saldo;
        $saldoSesudah = round($saldoSebelum + $bunga, 2);

        $noBukti = $this->penomoran->berikutnya(PenomoranDokumenService::JENIS_BUNGA_TABUNGAN, $tanggal);

        $trx = TabunganTransaksi::create([
            'rekening_tabungan_id' => $rekening->id,
            'anggota_id' => $rekening->anggota_id,
            'no_bukti' => $noBukti,
            'jenis' => 'bunga',
            'kanal' => 'sistem',
            'nominal' => $bunga,
            'saldo_sebelum' => $saldoSebelum,
            'saldo_sesudah' => $saldoSesudah,
            'suku_bunga_snapshot' => $hitung['suku_bunga_snapshot'],
            'tanggal' => $tanggal->toDateString(),
            'keterangan' => $keterangan,
            'jurnal_kas_id' => null,
            'created_by' => $aktorId,
        ]);

        $rekening->update(['saldo' => $saldoSesudah]);

        return $trx;
    }

    private function saldoSebelumTanggal(int $rekeningId, Carbon $tanggal): float
    {
        $agregat = TabunganTransaksi::where('rekening_tabungan_id', $rekeningId)
            ->whereNull('dibatalkan_pada')
            ->whereNull('koreksi_dari_id')
            ->where('tanggal', '<', $tanggal->toDateString())
            ->selectRaw("
                COALESCE(SUM(CASE
                    WHEN jenis IN ('setor', 'bunga', 'koreksi_masuk') THEN nominal
                    ELSE -nominal
                END), 0) as saldo
            ")
            ->first();

        return (float) ($agregat->saldo ?? 0);
    }

    private function cariTarifTanggal(Collection $tarifHistori, string $tanggalStr, float $defaultRate): float
    {
        foreach ($tarifHistori as $tarif) {
            if ($tarif->berlaku_dari_tanggal->toDateString() <= $tanggalStr) {
                return (float) $tarif->persentase;
            }
        }

        return $defaultRate;
    }

    private function hasilKosong(string $periode, string $metode, float $tarif): array
    {
        return [
            'periode' => $periode,
            'metode_hitung' => $metode,
            'suku_bunga_snapshot' => round($tarif, 2),
            'rincian_tarif' => [],
            'saldo_dasar' => 0.0,
            'jumlah_hari' => 0,
            'nominal_bunga' => 0.0,
            'pajak_bunga' => 0.0,
            'bunga_bersih' => 0.0,
        ];
    }
}
