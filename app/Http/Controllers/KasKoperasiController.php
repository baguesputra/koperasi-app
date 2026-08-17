<?php

namespace App\Http\Controllers;

use App\Models\JurnalKas;
use App\Models\KasKoperasi;
use App\Models\Simpanan;
use App\Models\Pinjaman;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Services\Keuangan\JurnalKasService;

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

        $query = JurnalKas::where('kantong', $kantongAktif);

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
                'jumlah' => (float) $j->jumlah,
                'saldo_setelah' => (float) $j->saldo_setelah,
                'keterangan' => $j->keterangan,
                'tanggal' => $j->tanggal->format('d M Y'),
            ]);

        // Ringkasan arus kas untuk periode yang difilter
        $ringkasanQuery = JurnalKas::where('kantong', $kantongAktif);
        if ($bulanFilter) {
            [$tahun, $bulan] = explode('-', $bulanFilter);
            $ringkasanQuery->whereYear('tanggal', $tahun)->whereMonth('tanggal', $bulan);
        }
        $totalMasuk = (clone $ringkasanQuery)->where('tipe', 'masuk')->sum('jumlah');
        $totalKeluar = (clone $ringkasanQuery)->where('tipe', 'keluar')->sum('jumlah');

        $totalSimpanan = Simpanan::whereIn('jenis', ['pokok', 'wajib'])->sum('jumlah');
        $totalKeseluruhan = $kas->saldo_pinjaman + $kas->saldo_dana_sosial + $totalSimpanan;

        return Inertia::render('KasKoperasi/Index', [
            'saldoPinjaman' => (float) $kas->saldo_pinjaman,
            'saldoDanaSosial' => (float) $kas->saldo_dana_sosial,
            'totalSimpanan' => (float) $totalSimpanan,
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
        'kantong' => ['required', 'in:pinjaman,dana_sosial'],
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