<?php

namespace App\Laporan;

use App\Models\Anggota;
use App\Models\Angsuran;
use App\Models\AngsuranPercepatan;
use App\Models\PengajuanPercepatan;
use App\Models\Pengeluaran;
use App\Models\Pinjaman;
use App\Models\SettingSimpanan;
use App\Services\Keuangan\JurnalKasService;
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
        'topup_bulanan' => 'Topup Saldo',
        'pencairan_pinjaman' => 'Pencairan Pinjaman',
        'pembayaran_angsuran' => 'Pembayaran Angsuran',
        'dana_sosial_bulanan' => 'Dana Sosial Bulanan',
        'pengeluaran_koperasi' => 'Pengeluaran Koperasi',
        'pengeluaran_dana_sosial' => 'Pengeluaran Dana Sosial',
        'pelunasan_resign_pinjaman' => 'Pelunasan Resign Pinjaman',
        'pelunasan_resign_simpanan' => 'Pelunasan Pinjaman dari Simpanan',
        'simpanan_resign_masuk' => 'Simpanan Anggota (Resign)',
        'return_simpanan_pokok' => 'Return Simpanan Pokok',
        'return_simpanan_wajib' => 'Return Simpanan Wajib',
        'simpanan_pokok_masuk' => 'Simpanan Pokok Masuk',
        'simpanan_wajib_masuk' => 'Simpanan Wajib Masuk',
        'transfer_ke_dana_pinjaman' => 'Transfer ke Dana Pinjaman',
        'terima_dari_pengembalian_simpanan' => 'Terima dari Pengembalian Simpanan',
        'talangan_sosial_ke_pinjaman' => 'Talangan Sosial ke Pinjaman',
        'talangan_simpanan_ke_pinjaman' => 'Talangan Simpanan ke Pinjaman',
        'terima_talangan_dari_sosial' => 'Terima Talangan dari Sosial',
        'terima_talangan_dari_simpanan' => 'Terima Talangan dari Simpanan',
        'kembali_talangan_dari_pinjaman' => 'Pengembalian Talangan dari Pinjaman',
        'kembali_talangan_ke_simpanan' => 'Pengembalian Talangan ke Simpanan',
        'kembali_talangan_ke_sosial' => 'Pengembalian Talangan ke Sosial',
        'sisih_kas_kecil' => 'Sisih Kas Kecil',
        'terima_sisih_kas_kecil' => 'Terima Sisih Kas Kecil',
    ];

    public const STATUS_PINJAMAN = [
        'diajukan' => 'Diajukan',
        'approved_bendahara' => 'Disetujui Bendahara',
        'aktif' => 'Aktif',
        'lunas' => 'Lunas',
        'ditolak' => 'Ditolak',
    ];

    public static function semua(): array
    {
        return [
            // ============ KEUANGAN ============
            'arus-kas' => [
                'judul' => 'Laporan Arus Kas',
                'deskripsi' => 'Arus masuk/keluar per kategori dengan saldo awal, arus bersih, dan saldo akhir.',
                'kategori' => 'Keuangan',
                'ikon' => 'wallet',
                'filter' => ['tipe' => 'rentang', 'ekstra' => ['kantong']],
                'periodeDefault' => fn () => [now()->startOfMonth()->format('Y-m'), now()->format('Y-m')],
                'data' => function (Request $r) {
                    [$dari, $sampai] = self::rentang($r);
                    $kantongFilter = $r->input('kantong');

                    // Basis kas fisik (Kas Tunggal): hanya baris bank/kas kecil.
                    // Kantong tinggal label klasifikasi, bukan rekening.
                    $agregat = DB::table('jurnal_kas')
                        ->whereNotIn('kategori', JurnalKasService::KATEGORI_NON_FISIK)
                        ->whereBetween('tanggal', [$dari, $sampai])
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
                            $masuk[$a->kategori] = (float) $a->total;
                        } else {
                            $keluar[$a->kategori] = (float) $a->total;
                        }
                    }

                    $awal = self::kasPerTanggal($dari->copy()->subDay()->endOfDay());
                    $awalTotal = $awal['bank'] + $awal['kas_kecil'];

                    $rows = [];
                    $gayaBaris = [];
                    $bagian = function (string $label) use (&$rows, &$gayaBaris) {
                        $rows[] = [$label, null];
                        $gayaBaris[] = 'section';
                    };
                    $baris = function (string $uraian, $nilai, string $gaya = 'data') use (&$rows, &$gayaBaris) {
                        $rows[] = [$uraian, $nilai];
                        $gayaBaris[] = $gaya;
                    };

                    $totalAwal = $awalTotal;
                    $totalMasuk = 0.0;
                    $totalKeluar = 0.0;

                    $bagian('A. Saldo awal periode');
                    $baris('Saldo awal — Bank', $awal['bank']);
                    $baris('Saldo awal — Kas Kecil', $awal['kas_kecil']);
                    $baris('Jumlah saldo awal', $totalAwal, 'subtotal');

                    $bagian('B. Penerimaan kas');
                    foreach (self::KATEGORI_LABEL as $kategori => $label) {
                        if (! empty($masuk[$kategori])) {
                            $totalMasuk += $masuk[$kategori];
                            $baris($label, $masuk[$kategori]);
                        }
                    }
                    if ($totalMasuk == 0) {
                        $baris('Tidak ada penerimaan pada periode ini', 0.0);
                    }
                    $baris('Jumlah penerimaan', $totalMasuk, 'subtotal');

                    $bagian('C. Pengeluaran kas');
                    foreach (self::KATEGORI_LABEL as $kategori => $label) {
                        if (! empty($keluar[$kategori])) {
                            $totalKeluar += $keluar[$kategori];
                            $baris($label, $keluar[$kategori]);
                        }
                    }
                    if ($totalKeluar == 0) {
                        $baris('Tidak ada pengeluaran pada periode ini', 0.0);
                    }
                    $baris('Jumlah pengeluaran', $totalKeluar, 'subtotal');

                    $bersih = $totalMasuk - $totalKeluar;
                    $akhirHitung = $totalAwal + $bersih;
                    $akhir = self::kasPerTanggal($sampai);
                    $akhirTotal = $akhir['bank'] + $akhir['kas_kecil'];

                    $bagian('D. Rekonsiliasi');
                    $baris('Arus bersih (masuk − keluar)', $bersih, 'subtotal');
                    $baris('Saldo akhir (awal + bersih)', $akhirHitung, 'subtotal');
                    $baris('Saldo akhir aktual (buku kas)', $akhirTotal, 'subtotal');

                    $catatan = 'Hanya transaksi kas fisik (bank & kas kecil); transaksi non-kas dikecualikan. Kantong berfungsi sebagai label klasifikasi, bukan rekening.';
                    if ($kantongFilter && isset(JurnalKasService::KANTONG_LABEL[$kantongFilter])) {
                        $catatan .= ' Filter label kantong: '.JurnalKasService::KANTONG_LABEL[$kantongFilter].'.';
                    }

                    return self::hasil(
                        ['Uraian', 'Nilai'],
                        [1],
                        $rows,
                        null,
                        [
                            ['Saldo awal periode', self::rupiah($totalAwal)],
                            ['Total arus masuk', self::rupiah($totalMasuk)],
                            ['Total arus keluar', self::rupiah($totalKeluar)],
                            ['Arus bersih', self::rupiah($bersih)],
                            ['Saldo akhir aktual', self::rupiah($akhirTotal)],
                        ],
                        $catatan,
                        $gayaBaris,
                    );
                },
            ],

            'neraca' => [
                'judul' => 'Neraca Sederhana',
                'deskripsi' => 'Posisi keuangan koperasi per tanggal cut-off: aktiva = kewajiban + ekuitas.',
                'kategori' => 'Keuangan',
                'ikon' => 'landmark',
                'filter' => ['tipe' => 'tanggal'],
                'periodeDefault' => fn () => [now()->format('Y-m-d')],
                'data' => function (Request $r) {
                    $cutoff = Carbon::parse($r->input('tanggal', now()->format('Y-m-d')))->endOfDay();
                    $tgl = $cutoff->toDateString();
                    $tahun = $cutoff->year;

                    $kas = self::kasPerTanggal($cutoff);
                    $kasTotal = $kas['bank'] + $kas['kas_kecil'];
                    $piutang = self::piutangPerTanggal($tgl);
                    $aktiva = $kasTotal + $piutang;

                    $simpanan = (float) DB::table('simpanan')
                        ->join('anggota', 'anggota.id', '=', 'simpanan.anggota_id')
                        ->whereIn('simpanan.jenis', ['pokok', 'wajib'])
                        ->where('anggota.status', 'aktif')
                        ->where('simpanan.tanggal_input', '<=', $cutoff)
                        ->sum('simpanan.jumlah');

                    $danaSosial = self::danaSosialPerTanggal($cutoff);
                    $shu = self::shuTahunBerjalan($tahun);

                    // Modal = penyeimbang agar aktiva = pasiva (selisih dibuktikan nol di ringkasan).
                    $modal = $aktiva - $simpanan - $danaSosial - $shu['shu'];
                    $pasiva = $simpanan + $danaSosial + $shu['shu'] + $modal;
                    $selisih = round($aktiva - $pasiva, 2);

                    $rows = [];
                    $gayaBaris = [];
                    $bagian = function (string $label) use (&$rows, &$gayaBaris) {
                        $rows[] = [$label, null];
                        $gayaBaris[] = 'section';
                    };
                    $baris = function (string $uraian, $nilai, string $gaya = 'data') use (&$rows, &$gayaBaris) {
                        $rows[] = [$uraian, $nilai];
                        $gayaBaris[] = $gaya;
                    };

                    $bagian('Aktiva');
                    $baris('— Bank', $kas['bank']);
                    $baris('— Kas Kecil', $kas['kas_kecil']);
                    $baris('Kas & Bank', $kasTotal, 'subtotal');
                    $baris('Piutang Pinjaman (sisa pokok)', $piutang);
                    $baris('TOTAL AKTIVA', $aktiva, 'subtotal');

                    $bagian('Pasiva');
                    $baris('Simpanan Anggota (Pokok + Wajib)', $simpanan);
                    $baris('Dana Sosial', $danaSosial);
                    $baris("SHU Tahun {$tahun} berjalan (bunga − beban)", $shu['shu']);
                    $baris('Modal (penyeimbang)', $modal);
                    $baris('TOTAL PASIVA', $pasiva, 'subtotal');

                    // ponytail: status "aktif" dibaca hari ini, bukan per tanggal cutoff —
                    // resign retroaktif bisa menggeser angka simpanan historis; catat bila jadi isu audit
                    return self::hasil(
                        ['Pos', 'Nilai'],
                        [1],
                        $rows,
                        null,
                        [
                            ['Total Aktiva', self::rupiah($aktiva)],
                            ['Total Pasiva', self::rupiah($pasiva)],
                            ['Selisih (Aktiva − Pasiva)', self::rupiah($selisih).($selisih == 0 ? ' — Seimbang' : ' — TIDAK SEIMBANG')],
                        ],
                        'Kas & Bank dari saldo buku kas per cut-off; piutang = sisa pokok pinjaman dicairkan dikurangi pokok angsuran lunas (konfirmasi tanpa tanggal dianggap lunas); dana sosial = neto kantong dana sosial fisik; SHU = bunga '.self::rupiah($shu['bunga']).' − beban operasional '.self::rupiah($shu['beban'])." tahun {$tahun}; modal = selisih penyeimbang. Status keanggotaan aktif dibaca per tanggal cetak.",
                        $gayaBaris,
                    );
                },
            ],

            'pendapatan-bunga' => [
                'judul' => 'Laporan Pendapatan Bunga',
                'deskripsi' => 'Akumulasi bunga angsuran lunas per bulan — basis hitung SHU.',
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
                        $rows[] = [$namaBulan[$bulan].' '.$tahun, $jml, $pokok, $bunga];
                    }

                    return self::hasil(
                        ['Periode', 'Angsuran Lunas', 'Total Pokok', 'Total Bunga'],
                        [1, 2, 3],
                        $rows,
                        ['TOTAL '.$tahun, array_sum(array_column($rows, 1)), array_sum(array_column($rows, 2)), array_sum(array_column($rows, 3))]
                    );
                },
            ],

            // ============ PINJAMAN ============
            'pinjaman-per-status' => [
                'judul' => 'Rekap Pinjaman per Status',
                'deskripsi' => 'Daftar pinjaman beserta statusnya dalam periode pengajuan.',
                'kategori' => 'Pinjaman',
                'ikon' => 'hand-coins',
                'filter' => ['tipe' => 'rentang', 'ekstra' => ['cabang']],
                'periodeDefault' => fn () => [now()->startOfYear()->format('Y-m'), now()->format('Y-m')],
                'data' => function (Request $r) {
                    [$dari, $sampai] = self::rentang($r);
                    $q = Pinjaman::with('anggota')
                        ->whereBetween('tanggal_pengajuan', [$dari, $sampai->copy()->endOfMonth()])
                        ->orderBy('tanggal_pengajuan');
                    if ($r->filled('cabang')) {
                        $q->whereHas('anggota', fn ($qq) => $qq->where('cabang', $r->input('cabang')));
                    }
                    $rows = $q->get()->values()->map(fn ($p, $i) => [
                        $i + 1,
                        $p->anggota->nama,
                        $p->anggota->cabang,
                        (float) $p->nominal,
                        $p->tenor_bulan.' bln',
                        self::STATUS_PINJAMAN[$p->status] ?? $p->status,
                        $p->tanggal_pengajuan->format('d M Y'),
                    ])->all();

                    return self::hasil(
                        ['No', 'Nama', 'Cabang', 'Nominal', 'Tenor', 'Status', 'Tgl Pengajuan'],
                        [3],
                        $rows,
                        [null, null, null, array_sum(array_column($rows, 3)), count($rows).' pinjaman', null, null]
                    );
                },
            ],

            'perubahan-tenor' => [
                'judul' => 'Laporan Perubahan Tenor',
                'deskripsi' => 'Riwayat pengajuan percepatan/perpanjangan/pelunasan dan hasilnya.',
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
                            $p->tanggal_pengajuan->format('d M Y'),
                            $p->pinjaman->anggota->nama,
                            '#'.$p->pinjaman_id,
                            $labelTipe[$p->tipe] ?? $p->tipe,
                            $p->tenor_baru ? "{$p->tenor_lama} → {$p->tenor_baru} bln" : "{$p->tenor_lama} bln",
                            $labelStatus[$p->status] ?? $p->status,
                        ])->all();

                    return self::hasil(
                        ['Tanggal', 'Nama', 'Pinjaman', 'Jenis', 'Tenor', 'Status'],
                        [],
                        $rows,
                        [count($rows).' pengajuan', null, null, null, null, null]
                    );
                },
            ],

            // ============ SIMPANAN ============
            'simpanan-per-anggota' => [
                'judul' => 'Rekap Simpanan per Anggota',
                'deskripsi' => 'Akumulasi simpanan tiap anggota — bisa dicetak sebagai slip tahunan.',
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
                    $rows = $q->get()->map(fn ($a) => [
                        $a->no_karyawan,
                        $a->nama,
                        $a->cabang,
                        (float) $a->pokok,
                        (float) $a->wajib,
                        (float) $a->sosial,
                        (float) $a->pokok + (float) $a->wajib,
                    ])->all();
                    $kolom = ['No. Karyawan', 'Nama', 'Cabang', 'Pokok', 'Wajib', 'Dana Sosial', 'Pokok + Wajib'];

                    return self::hasil(
                        $kolom,
                        [3, 4, 5, 6],
                        $rows,
                        [count($rows).' anggota', null, null, array_sum(array_column($rows, 3)), array_sum(array_column($rows, 4)), array_sum(array_column($rows, 5)), array_sum(array_column($rows, 6))]
                    );
                },
            ],

            'setoran-bulanan' => [
                'judul' => 'Rekap Setoran Bulanan',
                'deskripsi' => 'Total simpanan wajib & dana sosial yang terkumpul tiap bulan.',
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
                        $rows[] = [$namaBulan[$bulan], count($penerimaWajib[$bulan] ?? []), $w, $s, $w + $s];
                    }

                    return self::hasil(
                        ['Bulan', 'Penerima Wajib', 'Simpanan Wajib', 'Dana Sosial', 'Total'],
                        [1, 2, 3, 4],
                        $rows,
                        ['TOTAL', array_sum(array_column($rows, 1)), array_sum(array_column($rows, 2)), array_sum(array_column($rows, 3)), array_sum(array_column($rows, 4))]
                    );
                },
            ],

            // ============ ANGGOTA ============
            'anggota-daftar' => [
                'judul' => 'Daftar Anggota',
                'deskripsi' => 'Daftar anggota aktif/nonaktif beserta lama keanggotaan.',
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
                        $a->no_karyawan,
                        $a->nama,
                        $a->cabang,
                        $a->unit_bisnis,
                        $a->jabatan,
                        $a->tanggal_jadi_anggota?->format('d M Y'),
                        $a->tanggal_jadi_anggota?->diffInYears(now()).' thn',
                        ucfirst($a->status),
                    ])->all();

                    return self::hasil(
                        ['No. Karyawan', 'Nama', 'Cabang', 'Unit Bisnis', 'Jabatan', 'Sejak', 'Lama', 'Status'],
                        [],
                        $rows,
                        [count($rows).' anggota', null, null, null, null, null, null, null]
                    );
                },
            ],

            'anggota-resign' => [
                'judul' => 'Laporan Resign',
                'deskripsi' => 'Anggota yang resign beserta nilai simpanan yang dikembalikan.',
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
                                $a->no_karyawan,
                                $a->nama,
                                $a->cabang,
                                $a->tanggal_resign->format('d M Y'),
                                $a->alasan_resign ?: '-',
                                (float) ($settlement['simpanan_pokok'] ?? 0) + (float) ($settlement['simpanan_wajib'] ?? 0),
                            ];
                        })->all();

                    return self::hasil(
                        ['No. Karyawan', 'Nama', 'Cabang', 'Tgl Resign', 'Alasan', 'Simpanan Dikembalikan'],
                        [5],
                        $rows,
                        [count($rows).' anggota', null, null, null, null, array_sum(array_column($rows, 5))]
                    );
                },
            ],

            // ============ OPERASIONAL ============
            'pengeluaran-rekap' => [
                'judul' => 'Rekap Pengeluaran',
                'deskripsi' => 'Rincian pengeluaran Koperasi & Dana Sosial per periode.',
                'kategori' => 'Operasional',
                'ikon' => 'receipt',
                'filter' => ['tipe' => 'rentang'],
                'periodeDefault' => fn () => [now()->startOfMonth()->format('Y-m'), now()->format('Y-m')],
                'data' => function (Request $r) {
                    [$dari, $sampai] = self::rentang($r);
                    $rows = Pengeluaran::with('inputOleh')
                        ->whereBetween('tanggal', [$dari, $sampai->copy()->endOfMonth()])
                        ->orderBy('tanggal')
                        ->get()
                        ->map(fn ($p) => [
                            $p->tanggal->format('d M Y'),
                            $p->jenis === 'dana_sosial' ? 'Dana Sosial' : 'Koperasi',
                            $p->keterangan,
                            $p->inputOleh->name,
                            (float) $p->jumlah,
                        ])->all();
                    $koperasi = array_sum(array_filter(array_map(fn ($row) => $row[1] === 'Koperasi' ? $row[4] : 0, $rows)));
                    $sosial = array_sum(array_filter(array_map(fn ($row) => $row[1] === 'Dana Sosial' ? $row[4] : 0, $rows)));

                    return self::hasil(
                        ['Tanggal', 'Jenis', 'Keterangan', 'Dicatat Oleh', 'Jumlah'],
                        [4],
                        $rows,
                        ['TOTAL', null, 'Koperasi '.self::rupiah($koperasi).' · Dana Sosial '.self::rupiah($sosial), null, array_sum(array_column($rows, 4))]
                    );
                },
            ],

            'dana-sosial' => [
                'judul' => 'Rekap Dana Sosial',
                'deskripsi' => 'Dana sosial terkumpul vs tersalurkan, lengkap dengan sisa saldonya.',
                'kategori' => 'Operasional',
                'ikon' => 'heart-handshake',
                'filter' => ['tipe' => 'rentang'],
                'periodeDefault' => fn () => [now()->startOfMonth()->format('Y-m'), now()->format('Y-m')],
                'data' => function (Request $r) {
                    [$dari, $sampai] = self::rentang($r);
                    $rows = DB::table('jurnal_kas')
                        ->whereBetween('tanggal', [$dari, $sampai])
                        ->where(function ($q) {
                            $q->where('kantong', 'dana_sosial')
                                ->orWhere(function ($qq) {
                                    $qq->where('kantong', 'kas_kecil')
                                        ->where('kategori', 'pengeluaran_dana_sosial');
                                });
                        })
                        ->orderBy('tanggal')->orderBy('id')
                        ->get()->map(fn ($j) => [
                            Carbon::parse($j->tanggal)->format('d M Y'),
                            self::KATEGORI_LABEL[$j->kategori] ?? $j->kategori,
                            $j->keterangan,
                            $j->tipe === 'masuk' ? (float) $j->jumlah : 0.0,
                            $j->tipe === 'keluar' ? (float) $j->jumlah : 0.0,
                        ])->all();

                    $sisaSampaiCutoff = (float) DB::table('jurnal_kas')
                        ->where('kantong', 'dana_sosial')->where('tanggal', '<=', $sampai->copy()->endOfMonth())
                        ->selectRaw("SUM(CASE WHEN tipe='masuk' THEN jumlah ELSE -jumlah END) as sisa")
                        ->value('sisa');

                    // P1-4: pisahkan keluar santunan vs keluar pinjaman (talangan) — 1 query GROUP BY.
                    // Santunan bisa tercatat di kantong dana_sosial (lama) atau kas_kecil (fisik baru).
                    $keluarAgg = DB::table('jurnal_kas')
                        ->where('tipe', 'keluar')
                        ->whereIn('kategori', ['pengeluaran_dana_sosial', 'talangan_sosial_ke_pinjaman'])
                        ->whereBetween('tanggal', [$dari, $sampai])
                        ->selectRaw('kategori, SUM(jumlah) as total')
                        ->groupBy('kategori')
                        ->pluck('total', 'kategori');

                    $santunan = (float) ($keluarAgg['pengeluaran_dana_sosial'] ?? 0);
                    $pinjaman = (float) ($keluarAgg['talangan_sosial_ke_pinjaman'] ?? 0);
                    $kembali = (float) DB::table('jurnal_kas')
                        ->where('kantong', 'dana_sosial')->where('tipe', 'masuk')
                        ->where('kategori', 'kembali_talangan_ke_sosial')
                        ->whereBetween('tanggal', [$dari, $sampai])
                        ->sum('jumlah');

                    return self::hasil(
                        ['Tanggal', 'Kategori', 'Keterangan', 'Masuk', 'Keluar'],
                        [3, 4],
                        $rows,
                        [null, null, 'TOTAL PERIODE', array_sum(array_column($rows, 3)), array_sum(array_column($rows, 4))],
                        ringkasan: [
                            ['Santunan tersalurkan (periode)', self::rupiah($santunan)],
                            ['Pinjaman tertalangi (periode)', self::rupiah($pinjaman)],
                            ['Talangan kembali (periode)', self::rupiah($kembali)],
                            ['Sisa Dana Sosial s/d akhir periode', self::rupiah($sisaSampaiCutoff)],
                        ]
                    );
                },
            ],

            'iuran-pinjaman-rekap' => [
                'judul' => 'Rekap Iuran & Pinjaman',
                'deskripsi' => 'Satu baris per anggota: iuran bulan berjalan + cicilan jatuh tempo bulan berjalan + total tagihan untuk accounting.',
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

                    return self::hasil(
                        ['No', 'Nama', 'Iuran Pokok', 'Iuran Wajib', 'Asuransi Sosial', 'Pinjaman', 'Bunga', 'Total', 'Divisi'],
                        [2, 3, 4, 5, 6, 7],
                        $rows,
                        [count($rows).' anggota', null, array_sum(array_column($rows, 2)), array_sum(array_column($rows, 3)), array_sum(array_column($rows, 4)), array_sum(array_column($rows, 5)), array_sum(array_column($rows, 6)), array_sum(array_column($rows, 7)), null],
                        ringkasan: [
                            ['Total iuran bulan berjalan', self::rupiah(array_sum(array_column($rows, 2)) + array_sum(array_column($rows, 3)) + array_sum(array_column($rows, 4)))],
                            ['Total cicilan bulan berjalan', self::rupiah(array_sum(array_column($rows, 5)) + array_sum(array_column($rows, 6)))],
                            ['Total tagihan', self::rupiah(array_sum(array_column($rows, 7)))],
                        ]
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
            $grup[$def['kategori']][] = ['slug' => $slug, ...collect($def)->except(['data', 'filter', 'periodeDefault'])->all()];
        }

        return $grup;
    }

    // ---------- helper ----------

    /**
     * Kas fisik per tanggal: saldo_setelah baris terakhir tiap akun
     * (bank / kas kecil) pada atau sebelum tanggal. Baris non-fisik
     * tidak menggerakkan saldo sehingga dikecualikan.
     * Pembanding berupa Carbon penuh (bukan string Y-m-d) supaya baris
     * bertime tetap tercakup di SQLite maupun MySQL.
     */
    private static function kasPerTanggal(Carbon $tanggal): array
    {
        $terakhir = fn (string $akun) => (float) (DB::table('jurnal_kas')
            ->whereNotIn('kategori', JurnalKasService::KATEGORI_NON_FISIK)
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

        $total = 0.0;
        foreach ($pinjamans as $pid => $nominal) {
            $total += max(0.0, (float) $nominal - (float) ($dibayar[$pid] ?? 0));
        }

        return $total;
    }

    /**
     * Dana sosial per tanggal: neto kantong dana_sosial fisik
     * (masuk − keluar), tanpa baris non-fisik.
     */
    private static function danaSosialPerTanggal(Carbon $tanggal): float
    {
        return (float) (DB::table('jurnal_kas')
            ->where('kantong', 'dana_sosial')
            ->whereNotIn('kategori', JurnalKasService::KATEGORI_NON_FISIK)
            ->where('tanggal', '<=', $tanggal)
            ->selectRaw("SUM(CASE WHEN tipe = 'masuk' THEN jumlah ELSE -jumlah END) as total")
            ->value('total') ?? 0);
    }

    /**
     * SHU tahun berjalan: bunga angsuran lunas (normal + percepatan)
     * dikurangi beban operasional kas. Dana sosial bukan beban SHU
     * (arus dana, bukan laba) sehingga tidak dikurangkan.
     */
    private static function shuTahunBerjalan(int $tahun): array
    {
        $awal = sprintf('%04d-01-01 00:00:00', $tahun);
        $akhir = sprintf('%04d-12-31 23:59:59', $tahun);
        $bunga = 0.0;
        foreach ([Angsuran::class, AngsuranPercepatan::class] as $model) {
            $bunga += (float) $model::where('status', 'lunas')
                ->whereBetween('tanggal_konfirmasi_bayar', [$awal, $akhir])
                ->sum('nominal_bunga');
        }

        $beban = (float) DB::table('jurnal_kas')
            ->where('tipe', 'keluar')
            ->where('kategori', 'pengeluaran_koperasi')
            ->whereBetween('tanggal', [$awal, $akhir])
            ->sum('jumlah');

        return ['bunga' => $bunga, 'beban' => $beban, 'shu' => $bunga - $beban];
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
            default => $k,
        };
    }

    private static function rupiah(float|int|null $n): string
    {
        return 'Rp '.number_format((float) $n, 0, ',', '.');
    }
}
