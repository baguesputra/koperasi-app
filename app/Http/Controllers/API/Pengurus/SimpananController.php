<?php

namespace App\Http\Controllers\API\Pengurus;

use App\Http\Controllers\Controller;
use App\Models\Anggota;
use App\Models\SettingSimpanan;
use App\Services\Simpanan\KonfirmasiSimpananService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

class SimpananController extends Controller
{
    public function __construct(private KonfirmasiSimpananService $konfirmasi) {}

    /**
     * Antrean anggota belum setor simpanan wajib + riwayat sudah dikonfirmasi pada periode.
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

        $request->validate([
            'bulan' => ['nullable', 'date_format:Y-m'],
            'cabang' => ['nullable', 'string', 'max:100'],
        ]);

        $periode = (string) ($request->input('bulan') ?: now()->format('Y-m'));
        $cabang = trim((string) $request->input('cabang', ''));
        $nominal = $this->nominalPerAnggota();

        $semuaBelum = $this->konfirmasi->anggotaBelumSimpananWajib($periode)
            ->map(fn ($a) => [
                'anggota_id' => $a->id,
                'anggota_nama' => $a->nama,
                'no_anggota' => $a->no_anggota,
                'cabang' => $a->cabang,
                'foto_url' => $a->foto_url,
                'jenis' => 'wajib',
                'jumlah' => $nominal,
                'bulan_periode' => $periode,
            ])->values();

        $rows = $cabang !== ''
            ? $semuaBelum->where('cabang', $cabang)->values()
            : $semuaBelum;

        $semuaSudah = $this->konfirmasi->anggotaSudahSimpananWajib($periode);
        $sudahDikonfirmasi = $cabang !== ''
            ? $semuaSudah->where('cabang', $cabang)->values()
            : $semuaSudah;

        $daftarCabang = Cache::remember(
            'daftar_cabang',
            600,
            fn () => Anggota::query()->whereNotNull('cabang')->distinct()->orderBy('cabang')->pluck('cabang')->values()->all()
        );

        return response()->json([
            'data' => $rows,
            'total' => $rows->count(),
            'periode' => $periode,
            'cabang' => $cabang !== '' ? $cabang : null,
            'daftar_cabang' => $daftarCabang,
            'nominal_per_anggota' => $nominal,
            'sudah_dikonfirmasi' => $sudahDikonfirmasi,
            'total_sudah_dikonfirmasi' => (float) $sudahDikonfirmasi->sum('nominal_total'),
        ]);
    }

    /**
     * Konfirmasi simpanan massal (wajib + dana sosial per anggota).
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
            'bulan_periode' => ['nullable', 'date_format:Y-m'],
        ]);

        $periode = (string) ($request->input('bulan_periode') ?: now()->format('Y-m'));
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

    /**
     * Batalkan konfirmasi simpanan wajib satu anggota pada periode tertentu.
     */
    public function batalkan(Request $request, Anggota $anggota): JsonResponse
    {
        if (! $request->user()->can('simpanan.konfirmasi')) {
            abort(response()->json([
                'message' => 'Anda tidak memiliki akses konfirmasi simpanan.',
            ], 403));
        }

        $request->validate([
            'bulan_periode' => ['required', 'string', 'regex:/^\d{4}-\d{2}$/'],
            'alasan' => ['required', 'string', 'min:3', 'max:255'],
        ]);

        try {
            $this->konfirmasi->batalkanKonfirmasi(
                anggotaId: $anggota->id,
                bulanPeriode: (string) $request->bulan_periode,
                alasan: trim((string) $request->alasan),
                userId: (int) $request->user()->id,
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => "Konfirmasi simpanan {$anggota->nama} berhasil dibatalkan.",
        ]);
    }

    private function nominalPerAnggota(): float
    {
        $wajib = (float) Cache::remember('setting_simpanan_wajib', 600, fn () => SettingSimpanan::where('jenis', 'wajib')->value('nominal') ?? 0);
        $sosial = (float) Cache::remember('setting_simpanan_sosial', 600, fn () => SettingSimpanan::where('jenis', 'dana_sosial')->value('nominal') ?? 0);

        return $wajib + $sosial;
    }
}
