<?php

namespace App\Http\Controllers;

use App\Models\JurnalKas;
use App\Models\KasKoperasi;
use App\Models\Simpanan;
use App\Services\Keuangan\JurnalKasService;
use Illuminate\Http\Request;
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
        // - 'pengembalian_simpanan': jurnal kantong transit + cross-ref pelunasan_resign_pinjaman
        //   dari kantong pinjaman (supaya user lihat pelunasan angsuran & return simpanan
        //   di satu tempat terkait proses resign).
        // - 'pinjaman': semua jurnal kantong pinjaman (termasuk pelunasan_resign_pinjaman).
        // - 'dana_sosial': semua jurnal kantong dana_sosial.
        $scopeKantong = match ($kantongAktif) {
            'pengembalian_simpanan' => [
                ['kantong' => 'pengembalian_simpanan'],
                ['kantong' => 'pinjaman', 'kategori' => ['pelunasan_resign_pinjaman']],
            ],
            'pinjaman' => [
                ['kantong' => 'pinjaman'],
            ],
            default => [['kantong' => $kantongAktif]],
        };

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

        // Outstanding: simpanan anggota aktif saja (yang masih jadi tanggungan kas).
        // Pakai JOIN eksplisit supaya unambiguous & tahan kalau scope/relasi berubah.
        $totalSimpananOutstanding = (float) DB::table('simpanan')
            ->join('anggota', 'anggota.id', '=', 'simpanan.anggota_id')
            ->whereIn('simpanan.jenis', ['pokok', 'wajib'])
            ->where('anggota.status', 'aktif')
            ->sum('simpanan.jumlah');

        // Gross: akumulasi semua simpanan (termasuk anggota resign) untuk transparansi audit.
        $totalAkumulasiSimpanan = (float) Simpanan::whereIn('jenis', ['pokok', 'wajib'])->sum('jumlah');

        // Total keseluruhan operasional = semua saldo kantong + simpanan outstanding.
        // saldo_pengembalian_simpanan sengaja tidak dimasukkan: itu cuma "dalam proses",
        // akan kembali ke 0 setelah transfer & return selesai. Masuk ke saldo_pinjaman via transfer.
        $totalKeseluruhan = $kas->saldo_pinjaman + $kas->saldo_dana_sosial + $totalSimpananOutstanding;

        return Inertia::render('KasKoperasi/Index', [
            'saldoPinjaman' => (float) $kas->saldo_pinjaman,
            'saldoDanaSosial' => (float) $kas->saldo_dana_sosial,
            'saldoPengembalianSimpanan' => (float) $kas->saldo_pengembalian_simpanan,
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

    public function topup(Request $request)
    {
        $request->validate([
            'kantong' => ['required', 'in:pinjaman,dana_sosial,pengembalian_simpanan'],
            'jumlah' => ['required', 'numeric', 'min:1'],
            'keterangan' => ['nullable', 'string', 'max:255'],
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
