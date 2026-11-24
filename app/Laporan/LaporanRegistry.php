<?php

namespace App\Laporan;

use App\Models\Anggota;
use App\Models\Angsuran;
use App\Models\AngsuranPercepatan;
use App\Models\PengajuanPenghapusanPinjaman;
use App\Models\PengajuanPercepatan;
use App\Models\Pengeluaran;
use App\Models\Pinjaman;
use App\Models\SettingSimpanan;
use App\Models\ShuPeriode;
use App\Services\Keuangan\JurnalKasService;
use App\Services\Keuangan\ShuService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Registry semua laporan koperasi.
 * Tiap laporan: meta tampilan + 'data' closure yang mengembalikan bentuk generik:
 *   kolom[], rata_kanan[] (index), rows[][], totals[]|null, ringkasan[]|null, catatan|string|null
 */
class LaporanRegistry
{
    public const KATEGORI_LABEL = [
        'saldo_awal' => 'Saldo Awal',
        'topup_bulanan' => 'Penambahan Saldo Kas',
        'pencairan_pinjaman' => 'Pencairan Pinjaman',
        'pembayaran_angsuran' => 'Pembayaran Angsuran',
        'dana_sosial_bulanan' => 'Dana Sosial Bulanan',
        'pengeluaran_koperasi' => 'Pengeluaran Koperasi',
        'pengeluaran_dana_sosial' => 'Pengeluaran Dana Sosial',
        'pelunasan_resign_pinjaman' => 'Pelunasan Pinjaman (Resign)',
        'pelunasan_resign_simpanan' => 'Pelunasan Pinjaman via Simpanan',
        'simpanan_resign_masuk' => 'Simpanan Anggota (Resign)',
        'return_simpanan_pokok' => 'Pengembalian Simpanan Pokok',
        'return_simpanan_wajib' => 'Pengembalian Simpanan Wajib',
        'simpanan_pokok_masuk' => 'Simpanan Pokok Masuk',
        'simpanan_wajib_masuk' => 'Simpanan Wajib Masuk',
        'setor_tabungan' => 'Setoran Tabungan Anggota',
        'tarik_tabungan' => 'Penarikan Tabungan Anggota',
        'return_tabungan_resign' => 'Pengembalian Tabungan (Resign)',
        'transfer_ke_dana_pinjaman' => 'Transfer ke Dana Pinjaman',
        'terima_dari_pengembalian_simpanan' => 'Terima dari Pengembalian Simpanan',
        'talangan_sosial_ke_pinjaman' => 'Talangan Sosial ke Pinjaman',
        'talangan_simpanan_ke_pinjaman' => 'Talangan Simpanan ke Pinjaman',
        'terima_talangan_dari_sosial' => 'Terima Talangan dari Sosial',
        'terima_talangan_dari_simpanan' => 'Terima Talangan dari Simpanan',
        'kembali_talangan_dari_pinjaman' => 'Pengembalian Talangan dari Pinjaman',
        'kembali_talangan_ke_simpanan' => 'Pengembalian Talangan ke Simpanan',
        'kembali_talangan_ke_sosial' => 'Pengembalian Talangan ke Sosial',
        'sisih_kas_kecil' => 'Transfer Dana',
        'terima_sisih_kas_kecil' => 'Terima Transfer Dana',
        'pembayaran_shu' => 'Pembayaran SHU',
    ];

    public const STATUS_PINJAMAN = [
        'diajukan' => 'Diajukan',
        'approved_admin' => 'Disetujui Sekretaris',
        'approved_bendahara' => 'Disetujui Bendahara',
        'approved_ketua' => 'Disetujui Ketua (Menunggu Pencairan)',
        'aktif' => 'Aktif',
        'lunas' => 'Lunas',
        'ditolak' => 'Ditolak',
        'dihapusbukukan' => 'Dihapusbukukan',
    ];

    public static function semua(): array
    {
        return [
            // ============ KEUANGAN ============
            'arus-kas' => [
                'judul' => 'Laporan Arus Kas',
                'deskripsi' => 'Ringkasan kas masuk, kas keluar, dan rekonsiliasi saldo Bank serta Kas Kecil.',
                'kategori' => 'Keuangan',
                'ikon' => 'wallet',
                'filter' => ['tipe' => 'rentang', 'ekstra' => ['kantong']],
                'periodeDefault' => fn () => [now()->startOfMonth()->format('Y-m'), now()->format('Y-m')],
                'data' => function (Request $r) {
                    [$dari, $sampai] = self::rentang($r);
                    $kantongFilter = $r->input('kantong');

                    // Basis kas fisik: bank + kas kecil, semua baris jurnal.
                    // Kantong tinggal label klasifikasi, bukan rekening.
                    $agregat = DB::table('jurnal_kas')
                        ->whereBetween('tanggal', [$dari, $sampai])
                        ->whereNull('dibatalkan_pada')
                        ->whereNull('koreksi_dari_id')
                        ->when(
                            $kantongFilter && isset(JurnalKasService::KANTONG_SALDO[$kantongFilter]),
                            fn ($q) => $q->where('kantong', $kantongFilter)
                        )
                        ->selectRaw('kategori, tipe, SUM(jumlah) as total')
                        ->groupBy('kategori', 'tipe')
                        ->get();

                    $masuk = [];
                    $keluar = [];
                    foreach ($agregat as $a) {
                        if ($a->tipe === 'masuk') {
                            $masuk[$a->kategori] = round((float) $a->total, 2);
                        } else {
                            $keluar[$a->kategori] = round((float) $a->total, 2);
                        }
                    }

                    $awal = self::kasPerTanggal($dari->copy()->subDay()->endOfDay());
                    $awalBank = round($awal['bank'], 2);
                    $awalKasKecil = round($awal['kas_kecil'], 2);
                    $awalTotal = round($awalBank + $awalKasKecil, 2);

                    $rows = [];
                    $gayaBaris = [];
                    $bagian = function (string $label) use (&$rows, &$gayaBaris) {
                        $rows[] = [$label, null];
                        $gayaBaris[] = 'section';
                    };
                    $baris = function (string $uraian, $nilai, string $gaya = 'data') use (&$rows, &$gayaBaris) {
                        $rows[] = [$uraian, is_numeric($nilai) ? round((float) $nilai, 2) : $nilai];
                        $gayaBaris[] = $gaya;
                    };

                    $totalAwal = $awalTotal;
                    $totalMasuk = 0.0;
                    $totalKeluar = 0.0;

                    $bagian('A. Saldo Awal Periode');
                    $baris('Saldo Awal — Bank', $awalBank);
                    $baris('Saldo Awal — Kas Kecil', $awalKasKecil);
                    $baris('Total Saldo Awal', $totalAwal, 'subtotal');

                    $bagian('B. Penerimaan Kas');
                    foreach (self::KATEGORI_LABEL as $kategori => $label) {
                        if (! empty($masuk[$kategori])) {
                            $totalMasuk = round($totalMasuk + $masuk[$kategori], 2);
                            $baris($label, $masuk[$kategori]);
                        }
                    }
                    if ($totalMasuk == 0) {
                        $baris('Tidak ada penerimaan pada periode ini', 0.0);
                    }
                    $baris('Total Penerimaan Kas', $totalMasuk, 'subtotal');

                    $bagian('C. Pengeluaran Kas');
                    foreach (self::KATEGORI_LABEL as $kategori => $label) {
                        if (! empty($keluar[$kategori])) {
                            $totalKeluar = round($totalKeluar + $keluar[$kategori], 2);
                            $baris($label, $keluar[$kategori]);
                        }
                    }
                    if ($totalKeluar == 0) {
                        $baris('Tidak ada pengeluaran pada periode ini', 0.0);
                    }
                    $baris('Total Pengeluaran Kas', $totalKeluar, 'subtotal');

                    $bersih = round($totalMasuk - $totalKeluar, 2);
                    $akhirHitung = round($totalAwal + $bersih, 2);
                    $akhir = self::kasPerTanggal($sampai);
                    $akhirTotal = round($akhir['bank'] + $akhir['kas_kecil'], 2);

                    $bagian('D. Ringkasan & Rekonsiliasi');
                    $baris('Arus Kas Bersih (Masuk − Keluar)', $bersih, 'subtotal');
                    $baris('Saldo Akhir Perhitungan', $akhirHitung, 'subtotal');
                    $baris('Saldo Akhir Aktual (Buku Kas)', $akhirTotal, 'subtotal');

                    $catatan = 'Mencakup seluruh mutasi kas masuk dan kas keluar (Bank dan Kas Kecil) pada periode terpilih.';
                    if ($kantongFilter && isset(JurnalKasService::KANTONG_LABEL[$kantongFilter])) {
                        $catatan .= ' Filter pos dana: '.JurnalKasService::KANTONG_LABEL[$kantongFilter].'.';
                    }

                    return self::hasil(
                        ['Uraian', 'Nilai'],
                        [1],
                        $rows,
                        null,
                        [
                            ['Saldo Awal Periode', self::rupiah($totalAwal)],
                            ['Total Penerimaan', self::rupiah($totalMasuk)],
                            ['Total Pengeluaran', self::rupiah($totalKeluar)],
                            ['Arus Kas Bersih', self::rupiah($bersih)],
                            ['Saldo Akhir Buku Kas', self::rupiah($akhirTotal)],
                        ],
                        $catatan,
                        $gayaBaris,
                    );
                },
            ],

            'neraca' => [
                'judul' => 'Laporan Neraca',
                'deskripsi' => 'Posisi keuangan koperasi (Aktiva, Kewajiban, dan Modal) per tanggal cut-off.',
                'kategori' => 'Keuangan',
                'ikon' => 'landmark',
                'filter' => ['tipe' => 'tanggal'],
                'periodeDefault' => fn () => [now()->format('Y-m-d')],
                'data' => function (Request $r) {
                    $cutoff = Carbon::parse($r->input('tanggal', now()->format('Y-m-d')))->endOfDay();
                    $tgl = $cutoff->toDateString();
                    $tahun = $cutoff->year;

                    // I. Aktiva Lancar
                    $kas = self::kasPerTanggal($cutoff);
                    $bank = round($kas['bank'], 2);
                    $kasKecil = round($kas['kas_kecil'], 2);
                    $piutang = round(self::piutangPerTanggal($tgl), 2);
                    $aktivaLancar = round($bank + $kasKecil + $piutang, 2);
                    $totalAktiva = $aktivaLancar;

                    // II. Kewajiban Lancar
                    // Simpanan anggota aktif maupun nonaktif (mis. dinonaktifkan karena penghapusan pinjaman,
                    // di mana simpanan belum dicairkan/resign) tetap tercatat sebagai kewajiban simpanan.
                    $simpananPokok = round((float) DB::table('simpanan')
                        ->join('anggota', 'anggota.id', '=', 'simpanan.anggota_id')
                        ->where('simpanan.jenis', 'pokok')
                        ->whereIn('anggota.status', ['aktif', 'nonaktif'])
                        ->where('simpanan.tanggal_input', '<=', $cutoff)
                        ->sum('simpanan.jumlah'), 2);

                    $simpananWajib = round((float) DB::table('simpanan')
                        ->join('anggota', 'anggota.id', '=', 'simpanan.anggota_id')
                        ->where('simpanan.jenis', 'wajib')
                        ->whereIn('anggota.status', ['aktif', 'nonaktif'])
                        ->where('simpanan.tanggal_input', '<=', $cutoff)
                        ->sum('simpanan.jumlah'), 2);

                    $asuransiSosial = round(self::danaSosialPerTanggal($cutoff), 2);
                    $tabunganAnggota = round(self::tabunganPerTanggal($tgl), 2);
                    $shu = self::shuTahunBerjalan($tahun, $cutoff);
                    $shuNilai = round((float) $shu['shu'], 2);
                    $hutang = round(self::hutangPengembalianResignPerTanggal($cutoff), 2);

                    $totalKewajiban = round($simpananPokok + $simpananWajib + $tabunganAnggota + $asuransiSosial + $shuNilai + $hutang, 2);

                    // III. Modal
                    $modalTetap = round((float) config('koperasi.modal_tetap', 20_409_831), 2);
                    $baseLabaDitahan = (float) config('koperasi.laba_ditahan_historis', 17_663_447.20);
                    $sisaDanaShu = self::akumulasiSisaDanaShu($tahun);
                    $labaRugiDitahan = round($baseLabaDitahan + $sisaDanaShu, 2);

                    $totalModal = round($modalTetap + $labaRugiDitahan, 2);
                    $totalKewajibanDanModal = round($totalKewajiban + $totalModal, 2);
                    $selisih = round($totalAktiva - $totalKewajibanDanModal, 2);

                    $rows = [];
                    $gayaBaris = [];
                    $bagian = function (string $label) use (&$rows, &$gayaBaris) {
                        $rows[] = [$label, null];
                        $gayaBaris[] = 'section';
                    };
                    $baris = function (string $uraian, $nilai, string $gaya = 'data') use (&$rows, &$gayaBaris) {
                        $rows[] = [$uraian, is_numeric($nilai) ? round((float) $nilai, 2) : $nilai];
                        $gayaBaris[] = $gaya;
                    };

                    $bagian('I. AKTIVA');
                    $bagian('Aktiva Lancar');
                    $baris('Bank', $bank);
                    $baris('Kas', $kasKecil);
                    $baris('Piutang', $piutang);
                    $baris('Total Aktiva Lancar', $aktivaLancar, 'subtotal');
                    $baris('TOTAL AKTIVA', $totalAktiva, 'subtotal');

                    $bagian('II. KEWAJIBAN DAN MODAL');
                    $bagian('Kewajiban Lancar');
                    $baris('Simpanan Pokok', $simpananPokok);
                    $baris('Simpanan Wajib', $simpananWajib);
                    $baris('Tabungan Anggota', $tabunganAnggota);
                    $baris('Asuransi Sosial', $asuransiSosial);
                    $baris('Selisih Hasil Usaha', $shuNilai);
                    $baris('Hutang', $hutang);
                    $baris('Total Kewajiban', $totalKewajiban, 'subtotal');

                    $bagian('Modal & Ekuitas');
                    $baris('Modal', $modalTetap);
                    $baris('Laba/Rugi Ditahan', $labaRugiDitahan);
                    $baris('Total Modal', $totalModal, 'subtotal');

                    $baris('TOTAL KEWAJIBAN DAN MODAL', $totalKewajibanDanModal, 'subtotal');

                    return self::hasil(
                        ['Pos', 'Nilai'],
                        [1],
                        $rows,
                        null,
                        [
                            ['Total Aktiva', self::rupiah($totalAktiva)],
                            ['Total Kewajiban dan Modal', self::rupiah($totalKewajibanDanModal)],
                            ['Selisih (Aktiva − Kewajiban & Modal)', self::rupiah($selisih).($selisih == 0 ? ' — Seimbang' : ' — TIDAK SEIMBANG')],
                        ],
                        'Laporan posisi keuangan per tanggal cut-off meliputi Kas/Bank, Piutang pokok pinjaman, Simpanan anggota, Asuransi Sosial, Selisih Hasil Usaha tahun berjalan, serta Modal dan Laba/Rugi Ditahan.',
                        $gayaBaris,
                    );
                },
            ],

            'pendapatan-bunga' => [
                'judul' => 'Laporan Pendapatan Bunga',
                'deskripsi' => 'Rekap pokok dan bunga angsuran pinjaman yang telah lunas per bulan.',
                'kategori' => 'Keuangan',
                'ikon' => 'trending-up',
                'filter' => ['tipe' => 'tahun'],
                'periodeDefault' => fn () => [now()->format('Y')],
                'data' => function (Request $r) {
                    $tahun = (int) ($r->input('tahun') ?? now()->format('Y'));

                    // ponytail: dikelompokkan di PHP (bukan SQL MONTH()) supaya portabel mysql/sqlite
                    $awal = sprintf('%04d-01-01 00:00:00', $tahun);
                    $akhir = sprintf('%04d-12-31 23:59:59', $tahun);
                    $kumpulkan = fn ($model) => $model::query()
                        ->where('status', 'lunas')
                        ->whereBetween('tanggal_konfirmasi_bayar', [$awal, $akhir])
                        ->get(['tanggal_konfirmasi_bayar', 'nominal_pokok', 'nominal_bunga']);

                    $perBulan = [];
                    foreach ($kumpulkan(Angsuran::class)->concat($kumpulkan(AngsuranPercepatan::class)) as $a) {
                        $b = (int) $a->tanggal_konfirmasi_bayar->format('n');
                        $perBulan[$b] ??= [0, 0.0, 0.0];
                        $perBulan[$b][0]++;
                        $perBulan[$b][1] += (float) $a->nominal_pokok;
                        $perBulan[$b][2] += (float) $a->nominal_bunga;
                    }
                    ksort($perBulan);

                    $namaBulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                    $rows = [];
                    foreach ($perBulan as $bulan => [$jml, $pokok, $bunga]) {
                        $rows[] = [$namaBulan[$bulan].' '.$tahun, $jml, round($pokok, 2), round($bunga, 2)];
                    }

                    $totalAngsuran = array_sum(array_column($rows, 1));
                    $totalPokok = round(array_sum(array_column($rows, 2)), 2);
                    $totalBunga = round(array_sum(array_column($rows, 3)), 2);

                    return self::hasil(
                        ['Periode', 'Angsuran Lunas', 'Total Pokok', 'Bunga Angsuran'],
                        [2, 3],
                        $rows,
                        ['TOTAL '.$tahun, $totalAngsuran, $totalPokok, $totalBunga],
                        [
                            ['Total Angsuran Lunas', $totalAngsuran.' angsuran'],
                            ['Total Pokok Diterima', self::rupiah($totalPokok)],
                            ['Total Pendapatan Bunga', self::rupiah($totalBunga)],
                        ],
                        'Mencakup pokok dan bunga angsuran pinjaman lunas (reguler dan perubahan tenor) berdasarkan tanggal pembayaran; bunga bank tidak termasuk.'
                    );
                },
            ],

            'laba-rugi' => [
                'judul' => 'Laporan Laba Rugi',
                'deskripsi' => 'Rincian pendapatan, beban operasional, dan Selisih Hasil Usaha (SHU) bulanan per tahun buku.',
                'kategori' => 'Keuangan',
                'ikon' => 'calculator',
                'filter' => ['tipe' => 'tahun'],
                'periodeDefault' => fn () => [now()->format('Y')],
                'data' => function (Request $r) {
                    $tahun = (int) ($r->input('tahun') ?? now()->format('Y'));
                    $laba = self::labaTahunan($tahun);

                    $kolom = ['Uraian', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des', 'Total'];
                    $rows = [];
                    $gayaBaris = [];
                    $bagian = function (string $label) use (&$rows, &$gayaBaris) {
                        $rows[] = array_merge([$label], array_fill(0, 13, null));
                        $gayaBaris[] = 'section';
                    };
                    // Kembalikan total; panggil hanya untuk baris bertotal tak nol.
                    $baris = function (string $uraian, array $perBulan, string $gaya = 'data') use (&$rows, &$gayaBaris) {
                        $perBulan = array_map(fn ($v) => round((float) $v, 2), array_values($perBulan));
                        $total = round(array_sum($perBulan), 2);
                        $rows[] = array_merge([$uraian], $perBulan, [$total]);
                        $gayaBaris[] = $gaya;

                        return $total;
                    };

                    $bagian('A. Pendapatan');
                    if (array_sum($laba['bunga']) != 0) {
                        $baris('Pendapatan Bunga', $laba['bunga']);
                    }
                    if (array_sum($laba['bank']) != 0) {
                        $baris('Bunga Bank', $laba['bank']);
                    }
                    $bruto = [];
                    for ($b = 1; $b <= 12; $b++) {
                        $bruto[$b] = round($laba['bunga'][$b] + $laba['bank'][$b], 2);
                    }
                    $baris('Laba Bruto', $bruto, 'subtotal');

                    $bagian('B. Beban');
                    $labelBeban = [
                        'perlengkapan' => 'Beban Perlengkapan',
                        'perijinan' => 'Beban Perijinan',
                        'bank' => 'Beban Bank',
                        'pajak' => 'Beban Pajak',
                        'bunga_tabungan' => 'Beban Bunga Tabungan',
                        'penghapusan_pinjaman' => 'Beban Penghapusan Pinjaman',
                        'lain_lain' => 'Beban Lain-lain',
                    ];
                    $bebanTotal = array_fill(1, 12, 0.0);
                    foreach ($labelBeban as $tipe => $label) {
                        if (array_sum($laba['beban'][$tipe] ?? []) == 0) {
                            continue;
                        }
                        $baris($label, $laba['beban'][$tipe]);
                        foreach ($laba['beban'][$tipe] as $b => $v) {
                            $bebanTotal[$b] = round($bebanTotal[$b] + $v, 2);
                        }
                    }
                    $baris('Total Beban', $bebanTotal, 'subtotal');

                    $bagian('C. Selisih Hasil Usaha');
                    $shu = [];
                    for ($b = 1; $b <= 12; $b++) {
                        $shu[$b] = round($bruto[$b] - $bebanTotal[$b], 2);
                    }
                    $baris('Selisih Hasil Usaha', $shu, 'subtotal');

                    return self::hasil(
                        $kolom,
                        range(1, 13),
                        $rows,
                        null,
                        [
                            ['Total Pendapatan', self::rupiah(round(array_sum($bruto), 2))],
                            ['Total Beban Operasional', self::rupiah(round(array_sum($bebanTotal), 2))],
                            ['Selisih Hasil Usaha '.$tahun, self::rupiah(round(array_sum($shu), 2))],
                        ],
                        'Pendapatan mencakup bunga angsuran pinjaman lunas dan bunga bank, dikurangi beban operasional koperasi di luar Dana Sosial (selaras dengan pos SHU pada Neraca).',
                        $gayaBaris,
                    );
                },
            ],

            // ============ PINJAMAN ============
            'pinjaman-per-status' => [
                'judul' => 'Rekap Pinjaman per Status',
                'deskripsi' => 'Daftar pinjaman per status beserta sisa cicilan, posisi cicilan belum bayar, dan sisa tagihan.',
                'kategori' => 'Pinjaman',
                'ikon' => 'hand-coins',
                'filter' => ['tipe' => 'rentang', 'ekstra' => ['semua_periode', 'status_pinjaman', 'cabang']],
                'periodeDefault' => fn () => [now()->startOfYear()->format('Y-m'), now()->format('Y-m')],
                'data' => function (Request $r) {
                    $semuaPeriode = $r->boolean('semua_periode');
                    $statusInput = $r->input('status_pinjaman');
                    $statusFilter = is_string($statusInput) && isset(self::STATUS_PINJAMAN[$statusInput])
                        ? $statusInput
                        : null;

                    $q = Pinjaman::with([
                        'anggota',
                        'angsuran' => fn ($aq) => $aq->where('status', '!=', 'digantikan')->orderBy('cicilan_ke'),
                        'pengajuanPercepatan' => fn ($pq) => $pq->where('status', 'aktif')
                            ->latest('created_at')
                            ->latest('id')
                            ->with(['angsuranBaru' => fn ($bq) => $bq->orderBy('cicilan_ke')]),
                    ])->orderBy('tanggal_pengajuan');

                    if (! $semuaPeriode) {
                        [$dari, $sampai] = self::rentang($r);
                        $q->whereBetween('tanggal_pengajuan', [$dari, $sampai->copy()->endOfMonth()]);
                    }
                    if ($statusFilter !== null) {
                        $q->where('status', $statusFilter);
                    }
                    if ($r->filled('cabang')) {
                        $q->whereHas('anggota', fn ($qq) => $qq->where('cabang', $r->input('cabang')));
                    }

                    $totalNominal = 0.0;
                    $totalSisaTagihan = 0.0;
                    $totalSisaCicilan = 0;
                    $jumlahAktif = 0;

                    $rows = $q->get()->values()->map(function ($p, $i) use (&$totalNominal, &$totalSisaTagihan, &$totalSisaCicilan, &$jumlahAktif) {
                        $nominal = round((float) $p->nominal, 2);
                        $totalNominal += $nominal;

                        if ($p->status === 'aktif') {
                            $jumlahAktif++;
                            $normal = $p->angsuran;
                            $percepatan = $p->pengajuanPercepatan->first();
                            $baru = $percepatan ? $percepatan->angsuranBaru : collect();
                            $totalCicilan = $normal->count() + $baru->count();
                            $belumNormal = $normal->where('status', 'belum_bayar');
                            $belumBaru = $baru->where('status', 'belum_bayar');
                            $sisaCount = $belumNormal->count() + $belumBaru->count();
                            $sisaTagihan = round((float) $belumNormal->sum('total_bayar') + (float) $belumBaru->sum('total_bayar'), 2);

                            $totalSisaCicilan += $sisaCount;
                            $totalSisaTagihan += $sisaTagihan;

                            $sisaCicilanLabel = ($totalCicilan > 0 && $sisaCount > 0) ? "{$sisaCount}x" : '-';
                            $pertamaNormal = $belumNormal->first();
                            $pertamaBaru = $belumBaru->first();
                            if ($pertamaNormal) {
                                $cicilanBelumBayar = 'Cicilan ke-'.$pertamaNormal->cicilan_ke;
                            } elseif ($pertamaBaru) {
                                if ($percepatan->tipe === 'lunas_total') {
                                    $cicilanBelumBayar = 'Pelunasan Total';
                                } else {
                                    $prefix = $percepatan->tipe === 'perpanjang' ? 'Perpanjangan' : 'Percepatan';
                                    $cicilanBelumBayar = $prefix.' ke-'.$pertamaBaru->cicilan_ke;
                                }
                            } else {
                                $cicilanBelumBayar = '-';
                            }
                            $sisaTagihanCell = $sisaTagihan;
                        } else {
                            $sisaCicilanLabel = '-';
                            $cicilanBelumBayar = '-';
                            $sisaTagihanCell = '-';
                        }

                        return [
                            $i + 1,
                            $p->anggota?->nama ?? '-',
                            $p->anggota?->cabang ?: '-',
                            $nominal,
                            $p->tenor_bulan.' bln',
                            self::STATUS_PINJAMAN[$p->status] ?? $p->status,
                            self::tanggalId($p->tanggal_pengajuan),
                            $sisaCicilanLabel,
                            $cicilanBelumBayar,
                            $sisaTagihanCell,
                        ];
                    })->all();

                    $totalNominal = round($totalNominal, 2);
                    $totalSisaTagihan = round($totalSisaTagihan, 2);
                    $labelStatus = $statusFilter !== null ? ' berstatus '.self::STATUS_PINJAMAN[$statusFilter] : '';
                    $catatan = $semuaPeriode
                        ? "Mencakup seluruh pinjaman lintas tahun pengajuan{$labelStatus}; kolom Cicilan Belum Bayar menunjukkan nomor cicilan paling awal yang belum lunas."
                        : "Mencakup pinjaman sesuai periode tanggal pengajuan{$labelStatus}; kolom Cicilan Belum Bayar menunjukkan nomor cicilan paling awal yang belum lunas.";

                    return self::hasil(
                        ['No', 'Nama', 'Cabang', 'Nominal', 'Tenor', 'Status', 'Tgl Pengajuan', 'Sisa Cicilan', 'Cicilan Belum Bayar', 'Sisa Tagihan'],
                        [3, 9],
                        $rows,
                        [null, 'Total', null, $totalNominal, count($rows).' pinjaman', null, null, $jumlahAktif > 0 ? $totalSisaCicilan.'x' : null, null, $totalSisaTagihan],
                        [
                            ['Total Pinjaman', count($rows).' pinjaman'.($statusFilter === null && $jumlahAktif > 0 ? " ({$jumlahAktif} aktif)" : '')],
                            ['Total Nominal Pinjaman', self::rupiah($totalNominal)],
                            ['Total Sisa Tagihan (Aktif)', self::rupiah($totalSisaTagihan)],
                        ],
                        $catatan
                    );
                },
            ],

            'perubahan-tenor' => [
                'judul' => 'Laporan Perubahan Tenor',
                'deskripsi' => 'Riwayat pengajuan percepatan, perpanjangan, dan pelunasan total pinjaman beserta statusnya.',
                'kategori' => 'Pinjaman',
                'ikon' => 'repeat',
                'filter' => ['tipe' => 'rentang'],
                'periodeDefault' => fn () => [now()->startOfYear()->format('Y-m'), now()->format('Y-m')],
                'data' => function (Request $r) {
                    [$dari, $sampai] = self::rentang($r);
                    $labelTipe = ['percepat' => 'Percepatan', 'perpanjang' => 'Perpanjangan', 'lunas_total' => 'Pelunasan Total'];
                    $labelStatus = ['diajukan' => 'Diajukan', 'approved_bendahara' => 'Disetujui Bendahara', 'aktif' => 'Disetujui Ketua', 'ditolak' => 'Ditolak'];

                    $rows = PengajuanPercepatan::with('pinjaman.anggota')
                        ->whereBetween('tanggal_pengajuan', [$dari, $sampai->copy()->endOfMonth()])
                        ->orderByDesc('tanggal_pengajuan')
                        ->get()
                        ->map(fn ($p) => [
                            self::tanggalId($p->tanggal_pengajuan),
                            $p->pinjaman?->anggota?->nama ?? '-',
                            '#'.$p->pinjaman_id,
                            $labelTipe[$p->tipe] ?? $p->tipe,
                            $p->tenor_baru ? "{$p->tenor_lama} → {$p->tenor_baru} bln" : "{$p->tenor_lama} bln",
                            $labelStatus[$p->status] ?? $p->status,
                        ])->all();

                    return self::hasil(
                        ['Tanggal', 'Nama', 'No. Pinjaman', 'Jenis Pengajuan', 'Perubahan Tenor', 'Status'],
                        [],
                        $rows,
                        [count($rows).' pengajuan', null, null, null, null, null],
                        null,
                        'Mencakup seluruh pengajuan perubahan tenor dan pelunasan total pinjaman pada periode pengajuan terpilih.'
                    );
                },
            ],

            // ============ SIMPANAN ============
            'simpanan-per-anggota' => [
                'judul' => 'Rekap Simpanan per Anggota',
                'deskripsi' => 'Rekap akumulasi simpanan pokok, simpanan wajib, dan dana sosial per anggota.',
                'kategori' => 'Simpanan',
                'ikon' => 'piggy-bank',
                'filter' => ['tipe' => 'tanpa_periode', 'ekstra' => ['status_anggota', 'cabang']],
                'periodeDefault' => fn () => [],
                'data' => function (Request $r) {
                    $q = DB::table('simpanan')
                        ->join('anggota', 'anggota.id', '=', 'simpanan.anggota_id')
                        ->groupBy('anggota.id', 'anggota.no_karyawan', 'anggota.nama', 'anggota.cabang')
                        ->orderBy('anggota.nama')
                        ->selectRaw("anggota.no_karyawan, anggota.nama, anggota.cabang,
                            SUM(CASE WHEN jenis='pokok' THEN jumlah ELSE 0 END) pokok,
                            SUM(CASE WHEN jenis='wajib' THEN jumlah ELSE 0 END) wajib,
                            SUM(CASE WHEN jenis='dana_sosial' THEN jumlah ELSE 0 END) sosial");
                    if (in_array($r->input('status_anggota'), ['aktif', 'resign'])) {
                        $q->where('anggota.status', $r->input('status_anggota'));
                    }
                    if ($r->filled('cabang')) {
                        $q->where('anggota.cabang', $r->input('cabang'));
                    }
                    $rows = $q->get()->map(function ($a) {
                        $pokok = round((float) $a->pokok, 2);
                        $wajib = round((float) $a->wajib, 2);
                        $sosial = round((float) $a->sosial, 2);

                        return [
                            $a->no_karyawan ?: '-',
                            $a->nama,
                            $a->cabang ?: '-',
                            $pokok,
                            $wajib,
                            $sosial,
                            round($pokok + $wajib, 2),
                        ];
                    })->all();
                    $kolom = ['No. Karyawan', 'Nama', 'Cabang', 'Simpanan Pokok', 'Simpanan Wajib', 'Dana Sosial', 'Total Pokok + Wajib'];
                    $totPokok = round(array_sum(array_column($rows, 3)), 2);
                    $totWajib = round(array_sum(array_column($rows, 4)), 2);
                    $totSosial = round(array_sum(array_column($rows, 5)), 2);
                    $totHak = round(array_sum(array_column($rows, 6)), 2);

                    return self::hasil(
                        $kolom,
                        [3, 4, 5, 6],
                        $rows,
                        [count($rows).' anggota', null, null, $totPokok, $totWajib, $totSosial, $totHak],
                        [
                            ['Total Anggota', count($rows).' anggota'],
                            ['Total Simpanan (Pokok + Wajib)', self::rupiah($totHak)],
                            ['Total Dana Sosial', self::rupiah($totSosial)],
                        ],
                        'Akumulasi seluruh simpanan tercatat per anggota; kolom Total Pokok + Wajib merupakan total hak simpanan anggota.'
                    );
                },
            ],

            'setoran-bulanan' => [
                'judul' => 'Rekap Setoran Bulanan',
                'deskripsi' => 'Rekap total setoran simpanan wajib dan dana sosial yang diterima setiap bulan.',
                'kategori' => 'Simpanan',
                'ikon' => 'calendar-check',
                'filter' => ['tipe' => 'tahun'],
                'periodeDefault' => fn () => [now()->format('Y')],
                'data' => function (Request $r) {
                    $tahun = (int) ($r->input('tahun') ?? now()->format('Y'));

                    // ponytail: dikelompokkan di PHP agar portabel mysql/sqlite
                    $rowsRaw = DB::table('simpanan')
                        ->whereIn('jenis', ['wajib', 'dana_sosial'])
                        ->whereBetween('tanggal_input', [sprintf('%04d-01-01 00:00:00', $tahun), sprintf('%04d-12-31 23:59:59', $tahun)])
                        ->get(['anggota_id', 'jenis', 'jumlah', 'tanggal_input']);

                    $perBulan = [];
                    foreach ($rowsRaw as $s) {
                        $b = (int) Carbon::parse($s->tanggal_input)->format('n');
                        $perBulan[$b] ??= [0, 0.0, 0.0];
                        if ($s->jenis === 'wajib') {
                            $perBulan[$b][1] += (float) $s->jumlah;
                        } else {
                            $perBulan[$b][2] += (float) $s->jumlah;
                        }
                    }

                    $penerimaWajib = [];
                    foreach ($rowsRaw as $s) {
                        if ($s->jenis === 'wajib') {
                            $b = (int) Carbon::parse($s->tanggal_input)->format('n');
                            $penerimaWajib[$b][$s->anggota_id] = true;
                        }
                    }

                    $namaBulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                    $rows = [];
                    ksort($perBulan);
                    foreach ($perBulan as $bulan => [$jml, $w, $s]) {
                        $wajib = round($w, 2);
                        $sosial = round($s, 2);
                        $rows[] = [$namaBulan[$bulan], count($penerimaWajib[$bulan] ?? []), $wajib, $sosial, round($wajib + $sosial, 2)];
                    }

                    return self::hasil(
                        ['Bulan', 'Jumlah Anggota', 'Simpanan Wajib', 'Dana Sosial', 'Total'],
                        [2, 3, 4],
                        $rows,
                        ['Total '.$tahun, array_sum(array_column($rows, 1)), round(array_sum(array_column($rows, 2)), 2), round(array_sum(array_column($rows, 3)), 2), round(array_sum(array_column($rows, 4)), 2)],
                        null,
                        'Jumlah anggota dihitung unik per bulan berdasarkan setoran Simpanan Wajib yang telah dikonfirmasi.'
                    );
                },
            ],

            // ============ ANGGOTA ============
            'anggota-daftar' => [
                'judul' => 'Daftar Anggota',
                'deskripsi' => 'Daftar anggota aktif maupun resign beserta unit kerja dan masa keanggotaan.',
                'kategori' => 'Anggota',
                'ikon' => 'users',
                'filter' => ['tipe' => 'tanpa_periode', 'ekstra' => [['nama' => 'status_anggota', 'default' => 'aktif'], 'cabang']],
                'periodeDefault' => fn () => [],
                'data' => function (Request $r) {
                    $q = Anggota::query()->orderBy('nama');
                    if (in_array($r->input('status_anggota'), ['aktif', 'resign'])) {
                        $q->where('status', $r->input('status_anggota'));
                    }
                    if ($r->filled('cabang')) {
                        $q->where('cabang', $r->input('cabang'));
                    }
                    $rows = $q->get()->map(fn ($a) => [
                        $a->no_karyawan ?: '-',
                        $a->nama,
                        $a->cabang ?: '-',
                        $a->unit_bisnis ?: '-',
                        $a->jabatan ?: '-',
                        self::tanggalId($a->tanggal_jadi_anggota),
                        self::lamaKeanggotaan($a->tanggal_jadi_anggota),
                        ucfirst($a->status),
                    ])->all();

                    return self::hasil(
                        ['No. Karyawan', 'Nama', 'Cabang', 'Unit Bisnis', 'Jabatan', 'Tgl Bergabung', 'Lama Keanggotaan', 'Status'],
                        [],
                        $rows,
                        [count($rows).' anggota', null, null, null, null, null, null, null],
                        null,
                        'Masa keanggotaan dihitung dari tanggal bergabung hingga tanggal cetak laporan.'
                    );
                },
            ],

            'anggota-resign' => [
                'judul' => 'Laporan Anggota Resign',
                'deskripsi' => 'Daftar anggota yang mengundurkan diri beserta nilai pengembalian simpanan.',
                'kategori' => 'Anggota',
                'ikon' => 'user-minus',
                'filter' => ['tipe' => 'rentang'],
                'periodeDefault' => fn () => [now()->startOfYear()->format('Y-m'), now()->format('Y-m')],
                'data' => function (Request $r) {
                    [$dari, $sampai] = self::rentang($r);
                    $rows = Anggota::whereNotNull('tanggal_resign')
                        ->whereBetween('tanggal_resign', [$dari, $sampai->copy()->endOfMonth()])
                        ->orderBy('tanggal_resign')
                        ->get()
                        ->map(function ($a) {
                            $settlement = $a->resigned_settlement_json ?? [];

                            return [
                                $a->no_karyawan ?: '-',
                                $a->nama,
                                $a->cabang ?: '-',
                                self::tanggalId($a->tanggal_resign),
                                $a->alasan_resign ?: '-',
                                round((float) ($settlement['simpanan_pokok'] ?? 0) + (float) ($settlement['simpanan_wajib'] ?? 0), 2),
                            ];
                        })->all();

                    return self::hasil(
                        ['No. Karyawan', 'Nama', 'Cabang', 'Tgl Resign', 'Alasan', 'Simpanan Dikembalikan'],
                        [5],
                        $rows,
                        [count($rows).' anggota', null, null, null, null, round(array_sum(array_column($rows, 5)), 2)],
                        null,
                        'Nilai pengembalian mencakup total Simpanan Pokok dan Simpanan Wajib yang diselesaikan saat anggota resign.'
                    );
                },
            ],

            // ============ OPERASIONAL ============
            'pengeluaran-rekap' => [
                'judul' => 'Rekap Pengeluaran',
                'deskripsi' => 'Rincian pengeluaran operasional Koperasi dan penyaluran Dana Sosial per periode.',
                'kategori' => 'Operasional',
                'ikon' => 'receipt',
                'filter' => ['tipe' => 'rentang'],
                'periodeDefault' => fn () => [now()->startOfMonth()->format('Y-m'), now()->format('Y-m')],
                'data' => function (Request $r) {
                    [$dari, $sampai] = self::rentang($r);
                    $rows = Pengeluaran::with('inputOleh')
                        ->whereNull('dibatalkan_pada')
                        ->whereBetween('tanggal', [$dari, $sampai->copy()->endOfMonth()])
                        ->orderBy('tanggal')
                        ->get()
                        ->map(fn ($p) => [
                            self::tanggalId($p->tanggal),
                            $p->jenis === 'dana_sosial' ? 'Dana Sosial' : 'Koperasi',
                            $p->keterangan,
                            $p->inputOleh?->name ?? '-',
                            round((float) $p->jumlah, 2),
                        ])->all();
                    $koperasi = round(array_sum(array_filter(array_map(fn ($row) => $row[1] === 'Koperasi' ? $row[4] : 0, $rows))), 2);
                    $sosial = round(array_sum(array_filter(array_map(fn ($row) => $row[1] === 'Dana Sosial' ? $row[4] : 0, $rows))), 2);
                    $totSemua = round(array_sum(array_column($rows, 4)), 2);

                    return self::hasil(
                        ['Tanggal', 'Jenis', 'Keterangan', 'Dicatat Oleh', 'Jumlah'],
                        [4],
                        $rows,
                        ['Total', null, count($rows).' transaksi', null, $totSemua],
                        [
                            ['Pengeluaran Koperasi', self::rupiah($koperasi)],
                            ['Pengeluaran Dana Sosial', self::rupiah($sosial)],
                            ['Total Pengeluaran', self::rupiah($totSemua)],
                        ],
                        'Mencakup seluruh transaksi pengeluaran aktif pada periode terpilih (tidak termasuk transaksi yang dibatalkan).'
                    );
                },
            ],

            'dana-sosial' => [
                'judul' => 'Rekap Dana Sosial',
                'deskripsi' => 'Mutasi penerimaan, penyaluran, dan saldo akhir Dana Sosial per periode.',
                'kategori' => 'Operasional',
                'ikon' => 'heart-handshake',
                'filter' => ['tipe' => 'rentang'],
                'periodeDefault' => fn () => [now()->startOfMonth()->format('Y-m'), now()->format('Y-m')],
                'data' => function (Request $r) {
                    [$dari, $sampai] = self::rentang($r);
                    $rows = DB::table('jurnal_kas')
                        ->whereBetween('tanggal', [$dari, $sampai])
                        ->whereNull('dibatalkan_pada')
                        ->whereNull('koreksi_dari_id')
                        ->where(function ($q) {
                            $q->where('kantong', 'dana_sosial')
                                ->orWhere(function ($qq) {
                                    $qq->where('kantong', 'kas_kecil')
                                        ->where('kategori', 'pengeluaran_dana_sosial');
                                });
                        })
                        ->orderBy('tanggal')->orderBy('id')
                        ->get()->map(fn ($j) => [
                            self::tanggalId(Carbon::parse($j->tanggal)),
                            self::KATEGORI_LABEL[$j->kategori] ?? $j->kategori,
                            $j->keterangan,
                            $j->tipe === 'masuk' ? round((float) $j->jumlah, 2) : 0.0,
                            $j->tipe === 'keluar' ? round((float) $j->jumlah, 2) : 0.0,
                        ])->all();

                    $sisaSampaiCutoff = round((float) DB::table('jurnal_kas')
                        ->where('kantong', 'dana_sosial')
                        ->whereNull('dibatalkan_pada')
                        ->whereNull('koreksi_dari_id')
                        ->where('tanggal', '<=', $sampai->copy()->endOfMonth())
                        ->selectRaw("SUM(CASE WHEN tipe='masuk' THEN jumlah ELSE -jumlah END) as sisa")
                        ->value('sisa'), 2);

                    // Pisahkan keluar dana sosial vs keluar pinjaman (talangan) — 1 query GROUP BY.
                    // Pengeluaran dana sosial bisa tercatat di kantong dana_sosial (lama) atau kas_kecil (fisik baru).
                    $keluarAgg = DB::table('jurnal_kas')
                        ->where('tipe', 'keluar')
                        ->whereNull('dibatalkan_pada')
                        ->whereNull('koreksi_dari_id')
                        ->whereIn('kategori', ['pengeluaran_dana_sosial', 'talangan_sosial_ke_pinjaman'])
                        ->whereBetween('tanggal', [$dari, $sampai])
                        ->selectRaw('kategori, SUM(jumlah) as total')
                        ->groupBy('kategori')
                        ->pluck('total', 'kategori');

                    $santunan = round((float) ($keluarAgg['pengeluaran_dana_sosial'] ?? 0), 2);
                    $pinjaman = round((float) ($keluarAgg['talangan_sosial_ke_pinjaman'] ?? 0), 2);
                    $kembali = round((float) DB::table('jurnal_kas')
                        ->where('kantong', 'dana_sosial')->where('tipe', 'masuk')
                        ->whereNull('dibatalkan_pada')
                        ->whereNull('koreksi_dari_id')
                        ->where('kategori', 'kembali_talangan_ke_sosial')
                        ->whereBetween('tanggal', [$dari, $sampai])
                        ->sum('jumlah'), 2);

                    return self::hasil(
                        ['Tanggal', 'Kategori', 'Keterangan', 'Masuk', 'Keluar'],
                        [3, 4],
                        $rows,
                        [null, null, 'Total Periode', round(array_sum(array_column($rows, 3)), 2), round(array_sum(array_column($rows, 4)), 2)],
                        [
                            ['Santunan Tersalurkan', self::rupiah($santunan)],
                            ['Talangan ke Pinjaman', self::rupiah($pinjaman)],
                            ['Pengembalian Talangan', self::rupiah($kembali)],
                            ['Saldo Akhir Dana Sosial', self::rupiah($sisaSampaiCutoff)],
                        ],
                        'Mencakup mutasi iuran, penyaluran santunan, talangan, serta saldo akhir Dana Sosial hingga akhir periode terpilih.'
                    );
                },
            ],

            'iuran-pinjaman-rekap' => [
                'judul' => 'Rekap Iuran & Pinjaman',
                'deskripsi' => 'Rekap potongan bulanan per anggota meliputi iuran pokok, iuran wajib, asuransi sosial, serta angsuran pinjaman jatuh tempo.',
                'kategori' => 'Accounting',
                'ikon' => 'file-text',
                'filter' => ['tipe' => 'bulan', 'ekstra' => ['cabang']],
                'periodeDefault' => fn () => [now()->format('Y-m')],
                'data' => function (Request $r) {
                    [$dari, $sampai] = self::rentang($r);
                    $bulanPeriode = $dari->format('Y-m');

                    $anggota = Anggota::with('divisiMaster')->orderBy('nama');
                    if ($r->filled('cabang')) {
                        $anggota = $anggota->where('cabang', $r->input('cabang'));
                    }
                    $daftar = $anggota->get();
                    $anggotaIds = $daftar->pluck('id');
                    // 1 query peta pinjaman (ganti 3x pluck berantai)
                    $pinjamanMap = $anggotaIds->isEmpty()
                        ? collect()
                        : Pinjaman::whereIn('anggota_id', $anggotaIds)->pluck('anggota_id', 'id');
                    $pinjamanIds = $pinjamanMap->keys();

                    $simpanan = DB::table('simpanan')
                        ->where('bulan_periode', $bulanPeriode)
                        ->whereIn('anggota_id', $anggotaIds)
                        ->get(['anggota_id', 'jenis', 'jumlah'])
                        ->groupBy('anggota_id');

                    // Agregat SUM GROUP BY di SQL (ganti with()+get()+groupBy model penuh)
                    $cicilanBiasaAgg = $pinjamanIds->isEmpty() ? collect() : Angsuran::where('status', 'belum_bayar')
                        ->whereBetween('tanggal_jatuh_tempo', [$dari, $sampai])
                        ->whereIn('pinjaman_id', $pinjamanIds)
                        ->selectRaw('pinjaman_id, SUM(nominal_pokok) as pokok, SUM(nominal_bunga) as bunga')
                        ->groupBy('pinjaman_id')
                        ->get();
                    $cicilan = [];
                    foreach ($cicilanBiasaAgg as $row) {
                        $aid = $pinjamanMap[$row->pinjaman_id] ?? null;
                        if ($aid === null) {
                            continue;
                        }
                        $cicilan[$aid]['pokok'] = ((float) ($cicilan[$aid]['pokok'] ?? 0)) + (float) $row->pokok;
                        $cicilan[$aid]['bunga'] = ((float) ($cicilan[$aid]['bunga'] ?? 0)) + (float) $row->bunga;
                    }

                    $cicilanSusulanAgg = $pinjamanIds->isEmpty() ? collect() : AngsuranPercepatan::join('pengajuan_percepatan', 'pengajuan_percepatan.id', '=', 'angsuran_percepatan.pengajuan_percepatan_id')
                        ->where('angsuran_percepatan.status', 'belum_bayar')
                        ->where('pengajuan_percepatan.status', 'aktif')
                        ->whereBetween('angsuran_percepatan.tanggal_jatuh_tempo', [$dari, $sampai])
                        ->whereIn('pengajuan_percepatan.pinjaman_id', $pinjamanIds)
                        ->selectRaw('pengajuan_percepatan.pinjaman_id as pinjaman_id, SUM(angsuran_percepatan.nominal_pokok) as pokok, SUM(angsuran_percepatan.nominal_bunga) as bunga')
                        ->groupBy('pengajuan_percepatan.pinjaman_id')
                        ->get();
                    $cicilanPercepatan = [];
                    foreach ($cicilanSusulanAgg as $row) {
                        $aid = $pinjamanMap[$row->pinjaman_id] ?? null;
                        if ($aid === null) {
                            continue;
                        }
                        $cicilanPercepatan[$aid]['pokok'] = ((float) ($cicilanPercepatan[$aid]['pokok'] ?? 0)) + (float) $row->pokok;
                        $cicilanPercepatan[$aid]['bunga'] = ((float) ($cicilanPercepatan[$aid]['bunga'] ?? 0)) + (float) $row->bunga;
                    }

                    $tagihanWajib = (float) Cache::remember('setting_simpanan_wajib', 600, fn () => SettingSimpanan::where('jenis', 'wajib')->value('nominal') ?? 45_000);
                    $tagihanSosial = (float) Cache::remember('setting_simpanan_sosial', 600, fn () => SettingSimpanan::where('jenis', 'dana_sosial')->value('nominal') ?? 5_000);

                    $rows = [];
                    foreach ($daftar as $i => $a) {
                        $setor = $simpanan->get($a->id, collect());
                        $pokok = (float) $setor->where('jenis', 'pokok')->sum('jumlah');
                        $setorWajib = (float) $setor->where('jenis', 'wajib')->sum('jumlah');
                        $setorSosial = (float) $setor->where('jenis', 'dana_sosial')->sum('jumlah');
                        // Belum konfirmasi bulan ini → tampilkan tagihan tetap (contoh accounting).
                        // Anggota resign final tak punya tagihan berjalan.
                        $wajib = $setorWajib > 0 ? $setorWajib : ($a->status === 'aktif' ? $tagihanWajib : 0);
                        $sosial = $setorSosial > 0 ? $setorSosial : ($a->status === 'aktif' ? $tagihanSosial : 0);

                        $cicilanBiasa = $cicilan[$a->id] ?? ['pokok' => 0.0, 'bunga' => 0.0];
                        $cicilanSusulan = $cicilanPercepatan[$a->id] ?? ['pokok' => 0.0, 'bunga' => 0.0];
                        // Samakan tampilan sistem (formatRupiah 0 desimal): bulatkan per baris,
                        // total dijumlah dari nilai yang sudah dibulatkan.
                        $pokok = round($pokok);
                        $wajib = round($wajib);
                        $sosial = round($sosial);
                        $pinjamanPokok = round(
                            (float) $cicilanBiasa['pokok']
                            + (float) $cicilanSusulan['pokok']
                        );
                        $pinjamanBunga = round(
                            (float) $cicilanBiasa['bunga']
                            + (float) $cicilanSusulan['bunga']
                        );
                        $total = $pokok + $wajib + $sosial + $pinjamanPokok + $pinjamanBunga;

                        $rows[] = [
                            $i + 1,
                            $a->nama,
                            $pokok,
                            $wajib,
                            $sosial,
                            $pinjamanPokok,
                            $pinjamanBunga,
                            $total,
                            $a->getRelationValue('divisiMaster')?->nama ?? $a->divisi ?? '-',
                        ];
                    }

                    $totPokok = array_sum(array_column($rows, 2));
                    $totWajib = array_sum(array_column($rows, 3));
                    $totSosial = array_sum(array_column($rows, 4));
                    $totPinjaman = array_sum(array_column($rows, 5));
                    $totBunga = array_sum(array_column($rows, 6));
                    $totTagihan = array_sum(array_column($rows, 7));

                    return self::hasil(
                        ['No', 'Nama', 'Iuran Pokok', 'Iuran Wajib', 'Asuransi Sosial', 'Pinjaman', 'Bunga', 'Total', 'Divisi'],
                        [2, 3, 4, 5, 6, 7],
                        $rows,
                        [count($rows).' anggota', null, $totPokok, $totWajib, $totSosial, $totPinjaman, $totBunga, $totTagihan, null],
                        [
                            ['Total Iuran', self::rupiah($totPokok + $totWajib + $totSosial)],
                            ['Total Angsuran Pinjaman', self::rupiah($totPinjaman + $totBunga)],
                            ['Total Tagihan', self::rupiah($totTagihan)],
                        ],
                        'Anggota aktif yang belum dikonfirmasi pada bulan berjalan tetap ditampilkan sebesar tarif iuran standar; anggota resign tidak dikenakan tagihan berjalan.'
                    );
                },
            ],

        ];
    }

    public static function ada(string $jenis): bool
    {
        return array_key_exists($jenis, self::semua());
    }

    public static function ambil(string $jenis): array
    {
        return self::semua()[$jenis];
    }

    public static function kelompok(): array
    {
        $urut = ['Accounting', 'Keuangan', 'Pinjaman', 'Simpanan', 'Anggota', 'Operasional'];
        $grup = array_fill_keys($urut, []);
        foreach (self::semua() as $slug => $def) {
            $grup[$def['kategori']][] = [
                'slug' => $slug,
                ...collect($def)->except(['data', 'filter', 'periodeDefault'])->all(),
                'tipe_periode' => $def['filter']['tipe'] ?? 'tanpa_periode',
                'periode_default_label' => self::labelPeriodeDefault($def),
            ];
        }

        return $grup;
    }

    private static function labelPeriodeDefault(array $def): string
    {
        $tipe = $def['filter']['tipe'] ?? 'tanpa_periode';
        $default = collect($def['periodeDefault']())->values();

        return match ($tipe) {
            'bulan' => Carbon::parse(($default->get(0) ?? now()->format('Y-m')).'-01')->locale('id')->translatedFormat('F Y'),
            'tahun' => 'Tahun '.($default->get(0) ?? now()->format('Y')),
            'tanggal' => Carbon::parse($default->get(0) ?? now()->format('Y-m-d'))->locale('id')->translatedFormat('d M Y'),
            'rentang' => (function () use ($default): string {
                $dari = $default->get(0);
                $sampai = $default->get(1, $dari);
                $labelDari = $dari ? Carbon::parse($dari.'-01')->locale('id')->translatedFormat('M Y') : null;
                $labelSampai = $sampai ? Carbon::parse($sampai.'-01')->locale('id')->translatedFormat('M Y') : null;

                return ($labelSampai && $labelSampai !== $labelDari)
                    ? "{$labelDari} – {$labelSampai}"
                    : ($labelDari ?? 'Semua Periode');
            })(),
            default => 'Semua Periode',
        };
    }

    // ---------- helper ----------

    /**
     * Kas fisik per tanggal: saldo_setelah baris terakhir tiap akun
     * (bank / kas kecil) pada atau sebelum tanggal.
     * Pembanding berupa Carbon penuh (bukan string Y-m-d) supaya baris
     * bertime tetap tercakup di SQLite maupun MySQL.
     */
    private static function kasPerTanggal(Carbon $tanggal): array
    {
        $terakhir = fn (string $akun) => (float) (DB::table('jurnal_kas')
            ->when(
                $akun === 'kas_kecil',
                fn ($q) => $q->where('kantong', 'kas_kecil'),
                fn ($q) => $q->where('kantong', '!=', 'kas_kecil')
            )
            ->where('tanggal', '<=', $tanggal)
            ->orderByDesc('tanggal')->orderByDesc('id')
            ->value('saldo_setelah') ?? 0);

        return ['bank' => $terakhir('bank'), 'kas_kecil' => $terakhir('kas_kecil')];
    }

    /**
     * Piutang per tanggal: sisa pokok pinjaman yang sudah dicairkan
     * dikurangi pokok angsuran (normal + percepatan) yang lunas.
     * Konfirmasi tanpa tanggal dianggap sudah lunas.
     */
    private static function piutangPerTanggal(string $tanggal): float
    {
        $pinjamans = Pinjaman::whereNotNull('tanggal_pencairan')
            ->whereDate('tanggal_pencairan', '<=', $tanggal)
            ->pluck('nominal', 'id');

        if ($pinjamans->isEmpty()) {
            return 0.0;
        }

        $ids = $pinjamans->keys();
        $lunas = fn ($q) => $q->where('status', 'lunas')
            ->where(fn ($qq) => $qq
                ->whereDate('tanggal_konfirmasi_bayar', '<=', $tanggal)
                ->orWhereNull('tanggal_konfirmasi_bayar'));

        // 1 query agregat per tabel (ganti get()+each per baris)
        $dibayar = Angsuran::whereIn('pinjaman_id', $ids)
            ->where($lunas)
            ->selectRaw('pinjaman_id, SUM(nominal_pokok) as total')
            ->groupBy('pinjaman_id')
            ->pluck('total', 'pinjaman_id');

        $dibayarPercepatan = AngsuranPercepatan::join('pengajuan_percepatan', 'pengajuan_percepatan.id', '=', 'angsuran_percepatan.pengajuan_percepatan_id')
            ->whereIn('pengajuan_percepatan.pinjaman_id', $ids)
            ->where(fn ($q) => $q->where('angsuran_percepatan.status', 'lunas')
                ->where(fn ($qq) => $qq
                    ->whereDate('angsuran_percepatan.tanggal_konfirmasi_bayar', '<=', $tanggal)
                    ->orWhereNull('angsuran_percepatan.tanggal_konfirmasi_bayar')))
            ->selectRaw('pengajuan_percepatan.pinjaman_id as pinjaman_id, SUM(angsuran_percepatan.nominal_pokok) as total')
            ->groupBy('pengajuan_percepatan.pinjaman_id')
            ->pluck('total', 'pinjaman_id');

        foreach ($dibayarPercepatan as $pid => $nominal) {
            $dibayar[$pid] = ((float) ($dibayar[$pid] ?? 0)) + (float) $nominal;
        }

        // Kurangi pokok pinjaman yang telah dihapusbukukan (disetujui final) pada atau sebelum tanggal cut-off
        $dihapusbukukan = PengajuanPenghapusanPinjaman::whereIn('pinjaman_id', $ids)
            ->where('status', 'disetujui')
            ->whereNotNull('disetujui_pada')
            ->whereDate('disetujui_pada', '<=', $tanggal)
            ->selectRaw('pinjaman_id, SUM(sisa_pokok) as total')
            ->groupBy('pinjaman_id')
            ->pluck('total', 'pinjaman_id');

        foreach ($dihapusbukukan as $pid => $nominal) {
            $dibayar[$pid] = ((float) ($dibayar[$pid] ?? 0)) + (float) $nominal;
        }

        $total = 0.0;
        foreach ($pinjamans as $pid => $nominal) {
            $total += max(0.0, (float) $nominal - (float) ($dibayar[$pid] ?? 0));
        }

        return $total;
    }

    /**
     * Dana sosial per tanggal: neto kantong dana_sosial (masuk − keluar).
     */
    private static function danaSosialPerTanggal(Carbon $tanggal): float
    {
        return (float) (DB::table('jurnal_kas')
            ->where('kantong', 'dana_sosial')
            ->whereNull('dibatalkan_pada')
            ->whereNull('koreksi_dari_id')
            ->where('tanggal', '<=', $tanggal)
            ->selectRaw("SUM(CASE WHEN tipe = 'masuk' THEN jumlah ELSE -jumlah END) as total")
            ->value('total') ?? 0);
    }

    /**
     * Kewajiban pengembalian simpanan/tabungan anggota resign yang belum dicairkan
     * oleh Bendahara pada tanggal cut-off neraca (menjaga Neraca tetap seimbang).
     */
    private static function hutangPengembalianResignPerTanggal(Carbon $cutoff): float
    {
        $tgl = $cutoff->toDateString();

        $rows = Anggota::whereIn('status', ['resign', 'resign_menunggu'])
            ->whereIn('status_pencairan_resign', ['menunggu', 'dicairkan'])
            ->where(function ($q) use ($tgl) {
                $q->where('status_pencairan_resign', 'menunggu')
                    ->orWhereDate('tanggal_pencairan_resign', '>', $tgl);
            })
            ->get(['resigned_settlement_json']);

        $total = 0.0;
        foreach ($rows as $row) {
            $settlement = $row->resigned_settlement_json ?? [];
            $total += (float) ($settlement['total_dikembalikan'] ?? 0);
        }

        return $total;
    }

    /**
     * Kewajiban Tabungan Anggota per tanggal cut-off (neto transaksi aktif s/d tanggal).
     */
    private static function tabunganPerTanggal(string $tanggal): float
    {
        return (float) (DB::table('tabungan_transaksi')
            ->whereNull('dibatalkan_pada')
            ->whereNull('koreksi_dari_id')
            ->whereDate('tanggal', '<=', $tanggal)
            ->selectRaw("COALESCE(SUM(CASE
                WHEN jenis IN ('setor', 'bunga', 'koreksi_masuk') THEN nominal
                ELSE -nominal
            END), 0) as total")
            ->value('total') ?? 0);
    }

    /**
     * Total SHU setahun = Selisih Hasil Usaha (sumber sel D14 Excel SHU).
     * Dipakai modul Pembagian SHU sebagai default total_shu periode.
     */
    public static function totalShu(int $tahun): float
    {
        $laba = self::labaTahunan($tahun);

        $bruto = array_sum($laba['bunga']) + array_sum($laba['bank']);
        $beban = 0.0;
        foreach ($laba['beban'] as $perBulan) {
            $beban += array_sum($perBulan);
        }

        return round($bruto - $beban, 2);
    }

    /**
     * Laba setahun penuh: bunga angsuran + bunga bank per bulan 1–12,
     * dan beban per tipe (hanya jenis koperasi, santunan dikecualikan).
     * Pengelompokan di PHP agar portabel mysql/sqlite. Angsuran lunas tanpa
     * tanggal konfirmasi dikecualikan (tak bisa masuk bucket bulan).
     */
    private static function labaTahunan(int $tahun): array
    {
        $awal = sprintf('%04d-01-01 00:00:00', $tahun);
        $akhir = sprintf('%04d-12-31 23:59:59', $tahun);
        $nol = array_fill(1, 12, 0.0);
        $bunga = $bank = $nol;
        $beban = [];

        foreach ([Angsuran::class, AngsuranPercepatan::class] as $model) {
            $angsuran = $model::where('status', 'lunas')
                ->whereNotNull('tanggal_konfirmasi_bayar')
                ->whereBetween('tanggal_konfirmasi_bayar', [$awal, $akhir])
                ->get(['tanggal_konfirmasi_bayar', 'nominal_bunga']);
            foreach ($angsuran as $a) {
                $bunga[(int) $a->tanggal_konfirmasi_bayar->format('n')] += (float) $a->nominal_bunga;
            }
        }

        $jurnal = DB::table('jurnal_kas')
            ->where('tipe', 'masuk')
            ->where('kategori', 'topup_bulanan')
            ->where('sumber_topup', 'bunga_bank')
            ->whereNull('dibatalkan_pada')
            ->whereNull('koreksi_dari_id')
            ->whereBetween('tanggal', [$awal, $akhir])
            ->get(['tanggal', 'jumlah']);
        foreach ($jurnal as $j) {
            $bank[(int) Carbon::parse($j->tanggal)->format('n')] += (float) $j->jumlah;
        }

        $dikenal = ['perlengkapan', 'perijinan', 'bank', 'pajak', 'lain_lain'];
        $keluar = DB::table('pengeluaran')
            ->where('jenis', 'koperasi')
            ->where('tipe', '!=', 'santunan')
            ->whereNull('dibatalkan_pada')
            ->whereBetween('tanggal', [$awal, $akhir])
            ->get(['tanggal', 'tipe', 'jumlah']);
        foreach ($keluar as $p) {
            $tipe = in_array($p->tipe, $dikenal, true) ? $p->tipe : 'lain_lain';
            $beban[$tipe] ??= $nol;
            $beban[$tipe][(int) Carbon::parse($p->tanggal)->format('n')] += (float) $p->jumlah;
        }

        $penghapusan = PengajuanPenghapusanPinjaman::where('status', 'disetujui')
            ->whereNotNull('disetujui_pada')
            ->whereBetween('disetujui_pada', [$awal, $akhir])
            ->get(['disetujui_pada', 'sisa_pokok']);
        foreach ($penghapusan as $ph) {
            $beban['penghapusan_pinjaman'] ??= $nol;
            $beban['penghapusan_pinjaman'][(int) $ph->disetujui_pada->format('n')] += (float) $ph->sisa_pokok;
        }

        $bungaTabunganRows = DB::table('tabungan_transaksi')
            ->where('jenis', 'bunga')
            ->whereNull('dibatalkan_pada')
            ->whereNull('koreksi_dari_id')
            ->whereDate('tanggal', '>=', sprintf('%04d-01-01', $tahun))
            ->whereDate('tanggal', '<=', sprintf('%04d-12-31', $tahun))
            ->get(['tanggal', 'nominal']);
        foreach ($bungaTabunganRows as $bt) {
            $beban['bunga_tabungan'] ??= $nol;
            $beban['bunga_tabungan'][(int) Carbon::parse($bt->tanggal)->format('n')] += (float) $bt->nominal;
        }

        return ['bunga' => $bunga, 'bank' => $bank, 'beban' => $beban];
    }

    /**
     * SHU tahun berjalan: bunga angsuran lunas (normal + percepatan) +
     * bunga bank (topup sumber bunga_bank) dikurangi beban operasional kas
     * serta beban penghapusan pokok pinjaman macet.
     * Dana sosial bukan beban SHU (arus dana, bukan laba) sehingga
     * tidak dikurangkan. Topup modal bukan pendapatan SHU.
     */
    private static function shuTahunBerjalan(int $tahun, ?Carbon $cutoff = null): array
    {
        $awal = sprintf('%04d-01-01 00:00:00', $tahun);
        $akhir = $cutoff ? $cutoff->toDateTimeString() : sprintf('%04d-12-31 23:59:59', $tahun);
        $bunga = 0.0;
        foreach ([Angsuran::class, AngsuranPercepatan::class] as $model) {
            $bunga += (float) $model::where('status', 'lunas')
                ->whereBetween('tanggal_konfirmasi_bayar', [$awal, $akhir])
                ->sum('nominal_bunga');
        }

        $bungaBank = (float) DB::table('jurnal_kas')
            ->where('tipe', 'masuk')
            ->where('kategori', 'topup_bulanan')
            ->where('sumber_topup', 'bunga_bank')
            ->whereNull('dibatalkan_pada')
            ->whereNull('koreksi_dari_id')
            ->whereBetween('tanggal', [$awal, $akhir])
            ->sum('jumlah');

        $beban = (float) DB::table('jurnal_kas')
            ->where('tipe', 'keluar')
            ->where('kategori', 'pengeluaran_koperasi')
            ->whereNull('dibatalkan_pada')
            ->whereNull('koreksi_dari_id')
            ->whereBetween('tanggal', [$awal, $akhir])
            ->sum('jumlah');

        $bebanPenghapusan = (float) PengajuanPenghapusanPinjaman::where('status', 'disetujui')
            ->whereNotNull('disetujui_pada')
            ->whereBetween('disetujui_pada', [$awal, $akhir])
            ->sum('sisa_pokok');

        $akhirTanggal = $cutoff ? $cutoff->toDateString() : sprintf('%04d-12-31', $tahun);
        $bebanBungaTabungan = (float) DB::table('tabungan_transaksi')
            ->where('jenis', 'bunga')
            ->whereNull('dibatalkan_pada')
            ->whereNull('koreksi_dari_id')
            ->whereDate('tanggal', '>=', sprintf('%04d-01-01', $tahun))
            ->whereDate('tanggal', '<=', $akhirTanggal)
            ->sum('nominal');

        $totalBeban = $beban + $bebanPenghapusan + $bebanBungaTabungan;

        return ['bunga' => $bunga + $bungaBank, 'beban' => $totalBeban, 'shu' => $bunga + $bungaBank - $totalBeban];
    }

    /**
     * Akumulasi dana yang tidak dibagikan / sisa dana hasil pembagian SHU
     * untuk tahun-tahun buku sebelum cutoff yang sudah final.
     */
    private static function akumulasiSisaDanaShu(int $tahunCutoff): float
    {
        if (! class_exists(ShuPeriode::class)) {
            return 0.0;
        }

        $periodes = ShuPeriode::where('status', 'final')
            ->where('tahun_buku', '<', $tahunCutoff)
            ->get();

        if ($periodes->isEmpty()) {
            return 0.0;
        }

        $totalSisa = 0.0;
        $svc = app(ShuService::class);
        foreach ($periodes as $p) {
            $ringkas = $svc->ringkasan($p);
            $danaTakDibagi = (float) ($ringkas['dana_tak_dibagi'] ?? 0);
            $sisaAnggota = (float) ($ringkas['belum_diserahkan'] ?? $ringkas['sisa_dana']['anggota'] ?? 0);
            $sisaPengurus = (float) ($ringkas['sisa_dana']['pengurus'] ?? 0);
            $sisaPengawas = (float) ($ringkas['sisa_dana']['pengawas'] ?? 0);
            $totalSisa += ($danaTakDibagi + $sisaAnggota + $sisaPengurus + $sisaPengawas);
        }

        return round($totalSisa, 2);
    }

    private static function hasil(
        array $kolom,
        array $rataKanan,
        array $rows,
        ?array $totals = null,
        ?array $ringkasan = null,
        ?string $catatan = null,
        ?array $gayaBaris = null,
    ): array {
        return compact('kolom', 'rataKanan', 'rows', 'totals', 'ringkasan', 'catatan', 'gayaBaris');
    }

    /** Rentang dari input `dari`/`sampai` (Y-m). Controller menormalkan semua tipe filter ke bentuk ini. */
    private static function rentang(Request $r): array
    {
        $dari = $r->filled('dari') ? Carbon::parse($r->input('dari').'-01')->startOfDay() : now()->startOfMonth();
        $sampai = $r->filled('sampai') ? Carbon::parse($r->input('sampai').'-01')->endOfMonth()->endOfDay() : now()->endOfDay();

        return [$dari, $sampai];
    }

    private static function kantongLabel(string $k): string
    {
        return match ($k) {
            'pinjaman' => 'Dana Pinjaman',
            'dana_sosial' => 'Dana Sosial',
            'pengembalian_simpanan' => 'Pengembalian Simpanan',
            'simpanan' => 'Simpanan Anggota',
            'tabungan' => 'Tabungan Anggota',
            default => $k,
        };
    }

    private static function rupiah(float|int|null $n): string
    {
        return 'Rp '.number_format((float) $n, 0, ',', '.');
    }

    private static function tanggalId(?Carbon $tgl, string $format = 'd M Y'): string
    {
        return $tgl ? $tgl->copy()->locale('id')->translatedFormat($format) : '-';
    }

    private static function lamaKeanggotaan(?Carbon $tanggal): string
    {
        if (! $tanggal) {
            return '-';
        }
        if ($tanggal->isFuture()) {
            return '0 bln';
        }
        $diff = $tanggal->diff(now());
        if ($diff->y > 0 && $diff->m > 0) {
            return "{$diff->y} thn {$diff->m} bln";
        }
        if ($diff->y > 0) {
            return "{$diff->y} thn";
        }
        if ($diff->m > 0) {
            return "{$diff->m} bln";
        }

        return '< 1 bln';
    }
}
