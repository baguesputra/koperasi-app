<?php

namespace App\Http\Controllers\API\Pengurus;

use App\Http\Controllers\Controller;
use App\Models\JurnalKas;
use App\Models\KasKoperasi;
use App\Services\Keuangan\JurnalKasService;
use App\Services\Keuangan\PengeluaranService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class KasController extends Controller
{
    public function __construct(
        private JurnalKasService $jurnalKas,
        private PengeluaranService $pengeluaran,
    ) {}

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('kas.lihat'), 403, 'Anda tidak memiliki akses ke kas.');

        $request->validate([
            'bulan' => ['nullable', 'date_format:Y-m'],
            'kanal' => ['nullable', 'in:bank,kas_kecil'],
            'tipe' => ['nullable', 'in:masuk,keluar'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $bulan = $request->input('bulan', now()->format('Y-m'));
        $awal = Carbon::createFromFormat('Y-m', $bulan)->startOfMonth();
        $akhir = $awal->copy()->endOfMonth();
        $query = JurnalKas::with('pengeluaran:id,tipe')
            ->whereBetween('tanggal', [$awal, $akhir]);

        if ($request->input('kanal') === 'kas_kecil') {
            $query->where('kantong', 'kas_kecil');
        } elseif ($request->input('kanal') === 'bank') {
            $query->where('kantong', '!=', 'kas_kecil');
        }
        if ($request->filled('tipe')) {
            $query->where('tipe', $request->input('tipe'));
        }

        $page = $query->orderByDesc('tanggal')->orderByDesc('id')
            ->paginate($request->integer('per_page', 20), ['*'], 'page', $request->integer('page', 1));
        $agregat = JurnalKas::whereBetween('tanggal', [$awal, $akhir])
            ->selectRaw("SUM(CASE WHEN tipe = 'masuk' THEN jumlah ELSE 0 END) as total_masuk, SUM(CASE WHEN tipe = 'keluar' THEN jumlah ELSE 0 END) as total_keluar")
            ->first();
        $kas = KasKoperasi::firstOrFail();

        return response()->json([
            'saldo_bank' => (float) $kas->saldo_bank,
            'saldo_kas_kecil' => (float) $kas->saldo_kas_kecil,
            'bulan' => $bulan,
            'ringkasan' => ['total_masuk' => (float) $agregat->total_masuk, 'total_keluar' => (float) $agregat->total_keluar],
            'data' => $page->getCollection()->map(fn (JurnalKas $j) => [
                'id' => $j->id,
                'tipe' => $j->tipe,
                'kategori' => $j->kategori,
                'kantong' => $j->kantong,
                'kanal' => $j->kantong === 'kas_kecil' ? 'kas_kecil' : 'bank',
                'jumlah' => (float) $j->jumlah,
                'keterangan' => $j->keterangan,
                'sub_judul' => $j->sub_judul,
                'sumber_topup' => $j->sumber_topup,
                'tipe_pengeluaran' => $j->pengeluaran?->tipe,
                'tanggal' => $j->tanggal->format('Y-m-d'),
            ])->values(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    public function topup(Request $request): JsonResponse
    {
        $this->pastikanBisaMencatat($request);
        $data = $request->validate([
            'jumlah' => ['required', 'numeric', 'min:1'],
            'sumber_topup' => ['required', 'in:modal,bunga_bank'],
            'keterangan' => ['nullable', 'string', 'max:255'],
            'tanggal' => ['required', 'date'],
        ]);

        $this->jurnalKas->catat('masuk', 'topup_bulanan', 'bank', (float) $data['jumlah'], $data['keterangan'] ?? 'Topup saldo koperasi', null, $data['tanggal'], $request->user()->id, sumberTopup: $data['sumber_topup']);

        return response()->json(['message' => 'Saldo berhasil ditambahkan.']);
    }

    public function pengeluaran(Request $request): JsonResponse
    {
        $this->pastikanBisaMencatat($request);
        $data = $request->validate([
            'tipe' => ['required', 'in:pajak,santunan,lain_lain,perlengkapan,perijinan,bank'],
            'akun' => ['required', 'in:bank,kas_kecil'],
            'jumlah' => ['required', 'numeric', 'min:1'],
            'keterangan' => ['required', 'string', 'max:500'],
            'tanggal' => ['required', 'date'],
        ]);

        try {
            $this->pengeluaran->catat('koperasi', (float) $data['jumlah'], $data['keterangan'], $data['tanggal'], $request->user()->id, $data['tipe'], $data['akun']);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Pengeluaran berhasil dicatat.']);
    }

    public function sisihkan(Request $request): JsonResponse
    {
        $this->pastikanBisaMencatat($request);
        $data = $request->validate([
            'jumlah' => ['required', 'numeric', 'min:1'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->jurnalKas->sisihKasKecil((float) $data['jumlah'], $data['keterangan'] ?? 'Sisihkan kas kecil dari bank', now()->format('Y-m-d'), $request->user()->id);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Kas kecil berhasil disisihkan.']);
    }

    private function pastikanBisaMencatat(Request $request): void
    {
        abort_unless($request->user()->can('kas.topup'), 403, 'Anda tidak memiliki akses mencatat transaksi kas.');
    }
}
