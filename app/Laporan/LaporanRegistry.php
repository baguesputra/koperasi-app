<?php

namespace App\Laporan;

use App\Models\Anggota;
use App\Models\Angsuran;
use App\Models\AngsuranPercepatan;
use App\Models\AuditLog;
use App\Models\PengajuanPercepatan;
use App\Models\Pengeluaran;
use App\Models\Pinjaman;
use Carbon\Carbon;
use Illuminate\Http\Request;
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
                'deskripsi' => 'Mutasi masuk/keluar per kantong dana selama periode terpilih.',
                'kategori' => 'Keuangan',
                'ikon' => 'wallet',
                'filter' => ['tipe' => 'rentang', 'ekstra' => ['kantong']],
                'periodeDefault' => fn () => [now()->startOfMonth()->format('Y-m'), now()->format('Y-m')],
                'data' => function (Request $r) {
                    [$dari, $sampai] = self::rentang($r);
                    $q = DB::table('jurnal_kas')->whereBetween('tanggal', [$dari, $sampai]);
                    if ($r->filled('kantong')) {
                        $q->where('kantong', $r->input('kantong'));
                    }
                    $rows = $q->orderBy('tanggal')->orderBy('id')
                        ->get()->map(fn ($j) => [
                            Carbon::parse($j->tanggal)->format('d M Y'),
                            self::kantongLabel($j->kantong),
                            self::KATEGORI_LABEL[$j->kategori] ?? $j->kategori,
                            $j->keterangan,
                            $j->tipe === 'masuk' ? (float) $j->jumlah : 0.0,
                            $j->tipe === 'keluar' ? (float) $j->jumlah : 0.0,
                        ])->all();

                    return self::hasil(
                        ['Tanggal', 'Kantong', 'Kategori', 'Keterangan', 'Masuk', 'Keluar'],
                        [4, 5],
                        $rows,
                        [null, null, null, 'TOTAL', array_sum(array_column($rows, 4)), array_sum(array_column($rows, 5))]
                    );
                },
            ],

            'neraca' => [
                'judul' => 'Neraca Sederhana',
                'deskripsi' => 'Posisi saldo koperasi per tanggal cut-off.',
                'kategori' => 'Keuangan',
                'ikon' => 'landmark',
                'filter' => ['tipe' => 'tanggal'],
                'periodeDefault' => fn () => [now()->format('Y-m-d')],
                'data' => function (Request $r) {
                    $cutoff = Carbon::parse($r->input('tanggal', now()->format('Y-m-d')))->endOfDay();

                    $saldo = fn (string $kantong) => (float) DB::table('jurnal_kas')
                        ->where('kantong', $kantong)
                        ->where('tanggal', '<=', $cutoff)
                        ->selectRaw("SUM(CASE WHEN tipe = 'masuk' THEN jumlah ELSE -jumlah END) as total")
                        ->value('total');

                    $simpanan = (float) DB::table('simpanan')
                        ->join('anggota', 'anggota.id', '=', 'simpanan.anggota_id')
                        ->whereIn('simpanan.jenis', ['pokok', 'wajib'])
                        ->where('anggota.status', 'aktif')
                        ->where('simpanan.tanggal_input', '<=', $cutoff)
                        ->sum('simpanan.jumlah');

                    $pinjaman = $saldo('pinjaman');
                    $sosial = $saldo('dana_sosial');

                    // ponytail: status "aktif" dibaca hari ini, bukan per tanggal cutoff —
                    // resign retroaktif bisa menggeser angka simpanan historis; catat bila jadi isu audit
                    return self::hasil(
                        ['Pos', 'Nilai'],
                        [1],
                        [
                            ['Saldo Dana Pinjaman', $pinjaman],
                            ['Saldo Dana Sosial', $sosial],
                            ['Total Simpanan Anggota (Pokok + Wajib)', $simpanan],
                        ],
                        ['TOTAL KESELURUHAN', $pinjaman + $sosial + $simpanan],
                        catatan: 'Status keanggotaan aktif dibaca per tanggal cetak.'
                    );
                },
            ],

            'keuntungan-bunga' => [
                'judul' => 'Laporan Keuntungan Bunga',
                'deskripsi' => 'Akumulasi bunga angsuran lunas per bulan — basis hitung SHU.',
                'kategori' => 'Keuangan',
                'ikon' => 'trending-up',
                'filter' => ['tipe' => 'tahun'],
                'periodeDefault' => fn () => [now()->format('Y')],
                'data' => function (Request $r) {
                    $tahun = (int) ($r->input('tahun') ?? now()->format('Y'));

                    // ponytail: dikelompokkan di PHP (bukan SQL MONTH()) supaya portabel mysql/sqlite
                    $kumpulkan = fn ($model) => $model::query()
                        ->where('status', 'lunas')
                        ->whereYear('tanggal_konfirmasi_bayar', $tahun)
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

            'pinjaman-jatuh-tempo' => [
                'judul' => 'Pinjaman Jatuh Tempo',
                'deskripsi' => 'Angsuran belum dibayar yang jatuh tempo di bulan terpilih — bahan follow-up.',
                'kategori' => 'Pinjaman',
                'ikon' => 'calendar-clock',
                'filter' => ['tipe' => 'bulan'],
                'periodeDefault' => fn () => [now()->format('Y-m')],
                'data' => function (Request $r) {
                    [$dari, $sampai] = self::rentang($r);
                    $rows = Angsuran::with('pinjaman.anggota')
                        ->where('status', 'belum_bayar')
                        ->whereBetween('tanggal_jatuh_tempo', [$dari, $sampai])
                        ->orderBy('tanggal_jatuh_tempo')
                        ->get()
                        ->map(fn ($a) => [
                            $a->pinjaman->anggota->nama,
                            $a->pinjaman->anggota->no_anggota,
                            $a->pinjaman->anggota->cabang,
                            $a->cicilan_ke,
                            $a->tanggal_jatuh_tempo->format('d M Y'),
                            (float) $a->total_bayar,
                            $a->tanggal_jatuh_tempo->isPast() ? 'Ya' : '-',
                        ])->all();

                    return self::hasil(
                        ['Nama', 'No. Anggota', 'Cabang', 'Cicilan Ke', 'Jatuh Tempo', 'Total Tagihan', 'Terlambat'],
                        [5],
                        $rows,
                        [count($rows).' angsuran', null, null, null, null, array_sum(array_column($rows, 5)), null]
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
                        ->groupBy('anggota.id', 'anggota.no_anggota', 'anggota.nama', 'anggota.cabang')
                        ->orderBy('anggota.nama')
                        ->selectRaw("anggota.no_anggota, anggota.nama, anggota.cabang,
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
                        $a->no_anggota,
                        $a->nama,
                        $a->cabang,
                        (float) $a->pokok,
                        (float) $a->wajib,
                        (float) $a->sosial,
                        (float) $a->pokok + (float) $a->wajib,
                    ])->all();
                    $kolom = ['No. Anggota', 'Nama', 'Cabang', 'Pokok', 'Wajib', 'Dana Sosial', 'Pokok + Wajib'];

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
                        ->whereYear('tanggal_input', $tahun)
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
                        $a->no_anggota,
                        $a->nama,
                        $a->cabang,
                        $a->unit_bisnis,
                        $a->jabatan,
                        $a->tanggal_jadi_anggota?->format('d M Y'),
                        $a->tanggal_jadi_anggota?->diffInYears(now()).' thn',
                        ucfirst($a->status),
                    ])->all();

                    return self::hasil(
                        ['No. Anggota', 'Nama', 'Cabang', 'Unit Bisnis', 'Jabatan', 'Sejak', 'Lama', 'Status'],
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
                                $a->no_anggota,
                                $a->nama,
                                $a->cabang,
                                $a->tanggal_resign->format('d M Y'),
                                $a->alasan_resign ?: '-',
                                (float) ($settlement['simpanan_pokok'] ?? 0) + (float) ($settlement['simpanan_wajib'] ?? 0),
                            ];
                        })->all();

                    return self::hasil(
                        ['No. Anggota', 'Nama', 'Cabang', 'Tgl Resign', 'Alasan', 'Simpanan Dikembalikan'],
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
                        ->where('kantong', 'dana_sosial')
                        ->whereBetween('tanggal', [$dari, $sampai])
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

                    return self::hasil(
                        ['Tanggal', 'Kategori', 'Keterangan', 'Masuk', 'Keluar'],
                        [3, 4],
                        $rows,
                        [null, null, 'TOTAL PERIODE', array_sum(array_column($rows, 3)), array_sum(array_column($rows, 4))],
                        ringkasan: [['Sisa Dana Sosial s/d akhir periode', self::rupiah($sisaSampaiCutoff)]]
                    );
                },
            ],

            'audit-log' => [
                'judul' => 'Laporan Audit',
                'deskripsi' => 'Jejak aktivitas: siapa mengubah apa dan kapan.',
                'kategori' => 'Operasional',
                'ikon' => 'shield-check',
                'admin_only' => true,
                'filter' => ['tipe' => 'rentang'],
                'periodeDefault' => fn () => [now()->subMonth()->format('Y-m'), now()->format('Y-m')],
                'data' => function (Request $r) {
                    [$dari, $sampai] = self::rentang($r);
                    $rows = AuditLog::with('user')
                        ->whereBetween('created_at', [$dari->copy()->startOfDay(), $sampai->copy()->endOfDay()])
                        ->latest()
                        ->limit(1000)
                        ->get()
                        ->map(fn ($log) => [
                            $log->created_at->format('d M Y H:i'),
                            $log->user?->name ?? '-',
                            $log->aksi,
                            $log->keterangan,
                        ])->all();

                    return self::hasil(
                        ['Waktu', 'Pengguna', 'Aksi', 'Keterangan'],
                        [],
                        $rows,
                        [count($rows).' aktivitas (maks 1000)', null, null, null],
                        catatan: 'Dibatasi 1000 baris terbaru untuk performa. Gunakan rentang lebih sempit bila perlu.'
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
        $urut = ['Keuangan', 'Pinjaman', 'Simpanan', 'Anggota', 'Operasional'];
        $grup = array_fill_keys($urut, []);
        foreach (self::semua() as $slug => $def) {
            $grup[$def['kategori']][] = ['slug' => $slug, ...collect($def)->except(['data', 'filter', 'periodeDefault'])->all()];
        }

        return $grup;
    }

    // ---------- helper ----------

    private static function hasil(
        array $kolom,
        array $rataKanan,
        array $rows,
        ?array $totals = null,
        ?array $ringkasan = null,
        ?string $catatan = null,
    ): array {
        return compact('kolom', 'rataKanan', 'rows', 'totals', 'ringkasan', 'catatan');
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
