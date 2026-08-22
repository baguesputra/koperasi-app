<?php

namespace App\Http\Controllers;

use App\Models\JurnalKas;
use App\Models\KasKoperasi;
use App\Models\Simpanan;
use App\Services\Keuangan\JurnalKasService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class KasKoperasiController extends Controller
{
    public function __construct(
        private JurnalKasService $jurnalKas,
    ) {}

    public function index(Request $request): Response
    {
        $kas = KasKoperasi::firstOrFail();
        $kantongAktif = $request->input('kantong', 'pinjaman');
        $bulanFilter = $request->input('bulan', now()->format('Y-m'));

        // Scope jurnal per tab:
        // - 'pengembalian_simpanan': hanya event RETURN simpanan ke anggota (bukan jurnal
        //   rekening transit). Saldo berjalan = akumulasi simpanan anggota aktif (gross − return).
        //   Ditangani di branch khusus di bawah (bukan via scopeKantong).
        // - 'pinjaman': semua jurnal kantong pinjaman (termasuk pelunasan_resign_pinjaman).
        // - 'dana_sosial': semua jurnal kantong dana_sosial.
        $scopeKantong = match ($kantongAktif) {
            'pengembalian_simpanan' => null,
            'pinjaman' => [
                ['kantong' => 'pinjaman'],
            ],
            default => [['kantong' => $kantongAktif]],
        };

        // Hitung simpanan outstanding (anggota aktif) & gross akumulasi (audit).
        // Pakai JOIN eksplisit supaya unambiguous & tahan kalau scope/relasi berubah.
        $totalSimpananOutstanding = (float) DB::table('simpanan')
            ->join('anggota', 'anggota.id', '=', 'simpanan.anggota_id')
            ->whereIn('simpanan.jenis', ['pokok', 'wajib'])
            ->where('anggota.status', 'aktif')
            ->sum('simpanan.jumlah');

        // Gross: akumulasi semua simpanan (termasuk anggota resign) untuk transparansi audit.
        $totalAkumulasiSimpanan = (float) Simpanan::whereIn('jenis', ['pokok', 'wajib'])->sum('jumlah');

        if ($kantongAktif === 'pengembalian_simpanan') {
            return $this->renderTabPengembalian(
                kas: $kas,
                bulanFilter: $bulanFilter,
                totalSimpananOutstanding: $totalSimpananOutstanding,
                totalAkumulasiSimpanan: $totalAkumulasiSimpanan,
            );
        }

        $query = JurnalKas::query();
        $query->where(function ($q) use ($scopeKantong) {
            foreach ($scopeKantong as $i => $scope) {
                $sub = $q;
                if ($i > 0) {
                    $sub = $q->orWhere(function ($qq) use ($scope) {
                        $qq->where('kantong', $scope['kantong']);
                        if (isset($scope['kategori'])) {
                            $qq->whereIn('kategori', $scope['kategori']);
                        }
                    });
                } else {
                    $sub->where('kantong', $scope['kantong']);
                    if (isset($scope['kategori'])) {
                        $sub->whereIn('kategori', $scope['kategori']);
                    }
                }
            }
        });

        if ($bulanFilter) {
            [$tahun, $bulan] = explode('-', $bulanFilter);
            $query->whereYear('tanggal', $tahun)->whereMonth('tanggal', $bulan);
        }

        $riwayat = $query->latest('tanggal')->latest('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($j) => [
                'id' => $j->id,
                'tipe' => $j->tipe,
                'kategori' => $j->kategori,
                'kantong' => $j->kantong,
                'jumlah' => (float) $j->jumlah,
                'saldo_setelah' => (float) $j->saldo_setelah,
                'keterangan' => $j->keterangan,
                'sub_judul' => $j->sub_judul,
                'tanggal' => $j->tanggal->format('d M Y'),
            ]);

        // Ringkasan arus kas untuk periode yang difilter (ikut scope gabungan).
        $ringkasanQuery = JurnalKas::query();
        $ringkasanQuery->where(function ($q) use ($scopeKantong) {
            foreach ($scopeKantong as $i => $scope) {
                if ($i > 0) {
                    $q->orWhere(function ($qq) use ($scope) {
                        $qq->where('kantong', $scope['kantong']);
                        if (isset($scope['kategori'])) {
                            $qq->whereIn('kategori', $scope['kategori']);
                        }
                    });
                } else {
                    $q->where('kantong', $scope['kantong']);
                    if (isset($scope['kategori'])) {
                        $q->whereIn('kategori', $scope['kategori']);
                    }
                }
            }
        });
        if ($bulanFilter) {
            [$tahun, $bulan] = explode('-', $bulanFilter);
            $ringkasanQuery->whereYear('tanggal', $tahun)->whereMonth('tanggal', $bulan);
        }
        $totalMasuk = (clone $ringkasanQuery)->where('tipe', 'masuk')->sum('jumlah');
        $totalKeluar = (clone $ringkasanQuery)->where('tipe', 'keluar')->sum('jumlah');

        // Total keseluruhan operasional = semua saldo kantong + simpanan outstanding.
        // saldo_pengembalian_simpanan sengaja tidak dimasukkan: itu cuma "dalam proses",
        // akan kembali ke 0 setelah transfer & return selesai. Masuk ke saldo_pinjaman via transfer.
        $totalKeseluruhan = $kas->saldo_pinjaman + $kas->saldo_dana_sosial + $kas->saldo_simpanan;

        return Inertia::render('KasKoperasi/Index', [
            'saldoPinjaman' => (float) $kas->saldo_pinjaman,
            'saldoDanaSosial' => (float) $kas->saldo_dana_sosial,
            'saldoPengembalianSimpanan' => (float) $kas->saldo_pengembalian_simpanan,
            'saldoSimpanan' => (float) $kas->saldo_simpanan,
            'totalSimpananOutstanding' => $totalSimpananOutstanding,
            'totalAkumulasiSimpanan' => $totalAkumulasiSimpanan,
            'totalKeseluruhan' => (float) $totalKeseluruhan,
            'kantongAktif' => $kantongAktif,
            'bulanFilter' => $bulanFilter,
            'ringkasanPeriode' => [
                'total_masuk' => (float) $totalMasuk,
                'total_keluar' => (float) $totalKeluar,
            ],
            'riwayat' => $riwayat,
        ]);
    }

    /**
     * Render tab khusus 'Pengembalian Simpanan': cash flow outstanding simpanan aktif.
     * Kategori:
     *   - simpanan_pokok_masuk (masuk)
     *   - simpanan_wajib_masuk (masuk)
     *   - pelunasan_resign_simpanan (keluar)
     *   - return_simpanan_pokok (keluar)
     *   - return_simpanan_wajib (keluar)
     *
     * Saldo berjalan = outstanding absolut simpanan anggota aktif.
     * Saldo awal = outstanding simpanan aktif sebelum bulan filter (hitung dari tabel simpanan).
     */
    private function renderTabPengembalian(
        KasKoperasi $kas,
        string $bulanFilter,
        float $totalSimpananOutstanding,
        float $totalAkumulasiSimpanan,
    ): Response {
        // 5 kategori yang mempengaruhi outstanding simpanan aktif
        $kategoriScope = [
            'simpanan_pokok_masuk',      // masuk
            'simpanan_wajib_masuk',      // masuk
            'pelunasan_resign_simpanan', // keluar
            'return_simpanan_pokok',     // keluar
            'return_simpanan_wajib',     // keluar
        ];

        // Saldo awal (outstanding sebelum bulan filter): hitung dari tabel simpanan
        // Sum simpanan pokok+wajib anggota aktif yang tanggal_input < awal bulan filter
        [$tahun, $bulan] = explode('-', $bulanFilter);
        $awalBulanFilter = Carbon::createFromDate($tahun, $bulan, 1)->startOfMonth();

        $saldoAwal = (float) DB::table('simpanan')
            ->join('anggota', 'anggota.id', '=', 'simpanan.anggota_id')
            ->whereIn('simpanan.jenis', ['pokok', 'wajib'])
            ->where('anggota.status', 'aktif')
            ->where('simpanan.tanggal_input', '<', $awalBulanFilter)
            ->sum('simpanan.jumlah');

        // Query event DALAM bulan filter, urut kronologis ASC
        $query = JurnalKas::query()
            ->whereIn('kategori', $kategoriScope);

        if ($bulanFilter) {
            $query->whereYear('tanggal', $tahun)->whereMonth('tanggal', $bulan);
        }

        $semuaEvent = (clone $query)
            ->orderBy('tanggal', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $saldoBerjalan = $saldoAwal;
        $rows = [];
        $totalMasuk = 0.0;
        $totalKeluar = 0.0;

        // Header row: saldo real-time (widget) agar mudah tracking vs card di atas
        $rows[] = [
            'id' => 0,
            'tipe' => 'masuk',
            'kategori' => 'saldo_awal',
            'kantong' => 'simpanan',
            'jumlah' => 0.0,
            'saldo_setelah' => (float) $totalSimpananOutstanding,
            'keterangan' => 'Outstanding real-time (widget)',
            'sub_judul' => 'Saldo awal periode ini',
            'tanggal' => '-',
        ];

        foreach ($semuaEvent as $j) {
            $jumlah = (float) $j->jumlah;
            $tipe = $j->tipe; // 'masuk' atau 'keluar'

            if ($tipe === 'masuk') {
                $saldoBerjalan += $jumlah;
                $totalMasuk += $jumlah;
            } else {
                $saldoBerjalan -= $jumlah;
                $totalKeluar += $jumlah;
            }

            $rows[] = [
                'id' => $j->id,
                'tipe' => $tipe,
                'kategori' => $j->kategori,
                'kantong' => $j->kantong,
                'jumlah' => $jumlah,
                'saldo_setelah' => (float) $saldoBerjalan,
                'keterangan' => $j->keterangan,
                'sub_judul' => $j->sub_judul,
                'tanggal' => $j->tanggal->format('d M Y'),
            ];
        }

        // Pagination manual untuk menjaga saldo berjalan konsisten
        $page = max(1, (int) request()->input('page', 1));
        $perPage = 20;
        $total = count($rows);
        $slicedRows = array_slice($rows, ($page - 1) * $perPage, $perPage);

        $riwayat = new LengthAwarePaginator(
            $slicedRows,
            $total,
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'pageName' => 'page',
                'query' => request()->query(),
            ]
        );

        $totalKeseluruhan = $kas->saldo_pinjaman + $kas->saldo_dana_sosial + $totalSimpananOutstanding;

        return Inertia::render('KasKoperasi/Index', [
            'saldoPinjaman' => (float) $kas->saldo_pinjaman,
            'saldoDanaSosial' => (float) $kas->saldo_dana_sosial,
            'saldoPengembalianSimpanan' => (float) $kas->saldo_pengembalian_simpanan,
            'saldoSimpanan' => (float) $kas->saldo_simpanan,
            'totalSimpananOutstanding' => $totalSimpananOutstanding,
            'totalAkumulasiSimpanan' => $totalAkumulasiSimpanan,
            'totalKeseluruhan' => (float) $totalKeseluruhan,
            'kantongAktif' => 'pengembalian_simpanan',
            'bulanFilter' => $bulanFilter,
            'ringkasanPeriode' => [
                'total_masuk' => (float) $totalMasuk,
                'total_keluar' => (float) $totalKeluar,
            ],
            'riwayat' => $riwayat,
        ]);
    }

    public function topup(Request $request)
    {
        $request->validate([
            'kantong' => ['required', 'in:pinjaman,dana_sosial,simpanan'],
            'jumlah' => ['required', 'numeric', 'min:1'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ], [
            'kantong.in' => 'Kantong pengembalian simpanan bersifat transit dan tidak menerima topup.',
        ]);

        $this->jurnalKas->catat(
            tipe: 'masuk',
            kategori: 'topup_bulanan',
            kantong: $request->kantong,
            jumlah: $request->jumlah,
            keterangan: $request->keterangan ?: 'Topup saldo koperasi',
            referensiId: null,
            tanggal: now()->format('Y-m-d'),
            userId: auth()->id(),
        );

        return back()->with('status', 'Saldo berhasil ditambahkan.');
    }
}
