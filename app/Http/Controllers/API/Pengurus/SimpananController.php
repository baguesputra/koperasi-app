<?php

namespace App\Http\Controllers\API\Pengurus;

use App\Http\Controllers\Controller;
use App\Models\SettingSimpanan;
use App\Services\Simpanan\KonfirmasiSimpananService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SimpananController extends Controller
{
    public function __construct(private KonfirmasiSimpananService $konfirmasi) {}

    /**
     * Antrean anggota belum setor simpanan wajib periode berjalan.
     * Mirror mock calonSimpanan + web Bendahara\SimpananController@index.
     *
     * @response 200 {"data": [{"anggota_id": 1}], "total": 1, "periode": "2026-10"}
     */
    public function index(Request $request): JsonResponse
    {
        if (! $request->user()->can('simpanan.konfirmasi')) {
            abort(response()->json([
                'message' => 'Anda tidak memiliki akses konfirmasi simpanan.',
            ], 403));
        }

        $periode = now()->format('Y-m');
        $nominal = $this->nominalPerAnggota();

        $rows = $this->konfirmasi->anggotaBelumSimpananWajib($periode)
            ->map(fn ($a) => [
                'anggota_id' => $a->id,
                'anggota_nama' => $a->nama,
                'no_anggota' => $a->no_anggota,
                'jenis' => 'wajib',
                'jumlah' => $nominal,
                'bulan_periode' => $periode,
            ])->values();

        return response()->json([
            'data' => $rows,
            'total' => $rows->count(),
            'periode' => $periode,
        ]);
    }

    /**
     * Konfirmasi simpanan massal periode berjalan (wajib + dana sosial per anggota).
     * Idempoten via middleware + header Idempotency-Key dari mobile.
     *
     * @response 200 {"message": "2 anggota dikonfirmasi.", "total": 100000}
     */
    public function konfirmasi(Request $request): JsonResponse
    {
        if (! $request->user()->can('simpanan.konfirmasi')) {
            abort(response()->json([
                'message' => 'Anda tidak memiliki akses konfirmasi simpanan.',
            ], 403));
        }

        $request->validate([
            'anggota_ids' => ['required', 'array', 'min:1'],
            'anggota_ids.*' => ['integer', 'exists:anggota,id'],
        ]);

        $periode = now()->format('Y-m');
        $nominal = $this->nominalPerAnggota();

        $jumlah = $this->konfirmasi->konfirmasiMassal(
            $request->input('anggota_ids', []),
            $periode,
            (int) $request->user()->id
        );

        return response()->json([
            'message' => "{$jumlah} anggota dikonfirmasi.",
            'total' => $jumlah * $nominal,
        ]);
    }

    private function nominalPerAnggota(): float
    {
        $wajib = (float) Cache::remember('setting_simpanan_wajib', 600, fn () => SettingSimpanan::where('jenis', 'wajib')->value('nominal') ?? 0);
        $sosial = (float) Cache::remember('setting_simpanan_sosial', 600, fn () => SettingSimpanan::where('jenis', 'dana_sosial')->value('nominal') ?? 0);

        return $wajib + $sosial;
    }
}
