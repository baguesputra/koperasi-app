<?php

namespace App\Http\Controllers;

use App\Models\JurnalKas;
use App\Models\KasKoperasi;
use App\Services\Keuangan\JurnalKasService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class KasKoperasiController extends Controller
{
    public function __construct(
        private JurnalKasService $jurnalKas,
    ) {}

    /**
     * Konsep Kas Tunggal: satu daftar arus kas gabungan semua kantong +
     * filter bulan. Badge kantong di tiap baris jadi penanda jenis mutasi.
     */
    public function index(Request $request): Response
    {
        $kas = KasKoperasi::firstOrFail();
        $bulanFilter = $request->input('bulan', now()->format('Y-m'));

        $query = JurnalKas::query();

        if ($bulanFilter) {
            [$tahun, $bulan] = explode('-', $bulanFilter);
            $query->whereYear('tanggal', $tahun)->whereMonth('tanggal', $bulan);
        }

        $riwayat = (clone $query)->latest('tanggal')->latest('id')
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

        $totalMasuk = (clone $query)->where('tipe', 'masuk')->sum('jumlah');
        $totalKeluar = (clone $query)->where('tipe', 'keluar')->sum('jumlah');

        return Inertia::render('KasKoperasi/Index', [
            'saldoBank' => (float) $kas->saldo_bank,
            'saldoKasKecil' => (float) $kas->saldo_kas_kecil,
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
            'jumlah' => ['required', 'numeric', 'min:1'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        // Konsep Kas Tunggal: topup selalu masuk Bank.
        $this->jurnalKas->catat(
            tipe: 'masuk',
            kategori: 'topup_bulanan',
            kantong: 'bank',
            jumlah: $request->jumlah,
            keterangan: $request->keterangan ?: 'Topup saldo koperasi',
            referensiId: null,
            tanggal: now()->format('Y-m-d'),
            userId: auth()->id(),
        );

        return back()->with('status', 'Saldo berhasil ditambahkan.');
    }

    public function sisihKasKecil(Request $request)
    {
        $request->validate([
            'jumlah' => ['required', 'numeric', 'min:1'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->jurnalKas->sisihKasKecil(
                jumlah: (float) $request->jumlah,
                keterangan: $request->keterangan ?: 'Sisihkan kas kecil dari bank',
                tanggal: now()->format('Y-m-d'),
                userId: auth()->id(),
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['jumlah' => $e->getMessage()]);
        }

        return back()->with('status', 'Kas kecil berhasil disisihkan.');
    }
}
