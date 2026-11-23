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
     */
    public function hitungPeriode(RekeningTabungan $rekening, string $periode, ?Carbon $batasTanggal = null): array
    {
        if (! preg_match('/^\d{4}-\d{2}$/', $periode)) {
            throw new RuntimeException("Format periode '{$periode}' tidak valid (wajib YYYY-MM).");
        }

        $setting = SettingTabungan::current();
        $metode = $setting->metode_bunga ?: 'saldo_harian';

        $awalBulan = Carbon::createFromFormat('Y-m', $periode)->startOfMonth()->startOfDay();
        $akhirBulan = $awalBulan->copy()->endOfMonth()->startOfDay();

        if (! $rekening->tanggal_buka) {
            return $this->hasilKosong($periode, $metode, SettingBungaTabungan::persentaseAktif($akhirBulan));
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
            return $this->hasilKosong($periode, $metode, SettingBungaTabungan::persentaseAktif($akhirBulan));
        }

        $saldoSebelumPeriode = $this->saldoSebelumTanggal($rekening->id, $mulai);

        $mutasiRentang = TabunganTransaksi::where('rekening_tabungan_id', $rekening->id)
            ->whereNull('dibatalkan_pada')
            ->whereNull('koreksi_dari_id')
            ->whereDate('tanggal', '>=', $mulai->toDateString())
            ->whereDate('tanggal', '<=', $akhir->toDateString())
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get(['tanggal', 'jenis', 'nominal']);

        $deltaPerHari = [];
        foreach ($mutasiRentang as $trx) {
            $tgl = $trx->tanggal->toDateString();
            $delta = $trx->isKredit() ? (float) $trx->nominal : -(float) $trx->nominal;
            $deltaPerHari[$tgl] = ($deltaPerHari[$tgl] ?? 0.0) + $delta;
        }

        $tarifHistori = SettingBungaTabungan::whereDate('berlaku_dari_tanggal', '<=', $akhir->toDateString())
            ->orderByDesc('berlaku_dari_tanggal')
            ->orderByDesc('id')
            ->get(['persentase', 'berlaku_dari_tanggal']);

        $tarifFallback = SettingBungaTabungan::orderBy('berlaku_dari_tanggal')->orderBy('id')->value('persentase');
        $defaultRate = $tarifFallback !== null ? (float) $tarifFallback : 2.00;

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

        $akhirBulan = Carbon::createFromFormat('Y-m', $periode)->endOfMonth();
        $batasHitung = $periode === now()->format('Y-m') ? now() : $akhirBulan;

        $daftarRekening = RekeningTabungan::with('anggota:id,nama,no_karyawan,cabang')
            ->where('status', 'aktif')
            ->whereDate('tanggal_buka', '<=', $akhirBulan->toDateString())
            ->orderBy('no_rekening')
            ->get();

        $sudahMap = TabunganBungaPeriode::where('periode', $periode)
            ->whereIn('rekening_tabungan_id', $daftarRekening->pluck('id'))
            ->get()
            ->keyBy('rekening_tabungan_id');

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
                $hitung = $this->hitungPeriode($rek, $periode, $batasHitung);
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
            ->whereDate('tanggal_buka', '<=', $akhirBulan->toDateString())
            ->orderBy('id')
            ->pluck('id');

        $diproses = 0;
        $dilewati = 0;
        $totalBunga = 0.0;

        foreach ($daftarIds as $rekeningId) {
            $hasilRek = DB::transaction(function () use ($rekeningId, $periode, $tanggalEfektif, $aktorId) {
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

                $hitung = $this->hitungPeriode($rekening, $periode, $tanggalEfektif);
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
            ->whereDate('tanggal', '<', $tanggal->toDateString())
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
