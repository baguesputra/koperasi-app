<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\PengajuanLimitResource;
use App\Models\PengajuanLimit;
use App\Services\Pinjaman\EligibilitasPinjamanService;
use App\Services\Pinjaman\PengajuanLimitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class PengajuanLimitController extends Controller
{
    public function __construct(
        private PengajuanLimitService $service,
        private EligibilitasPinjamanService $eligibilitas,
    ) {}

    private function anggotaOrFail(Request $request)
    {
        $anggota = $request->user()->anggota;

        if (! $anggota) {
            abort(response()->json([
                'message' => 'Anggota not found for user',
            ], 404));
        }

        return $anggota;
    }

    /**
     * Riwayat pengajuan limit milik sendiri (terbaru dulu) + limit saat ini & plafon kebijakan.
     *
     * @response 200 {"data": [{"id": 1, "status": "diajukan"}], "limit_saat_ini": 5000000, "plafon_kebijakan": 10000000}
     */
    public function index(Request $request): JsonResponse
    {
        $anggota = $this->anggotaOrFail($request);

        $rows = PengajuanLimit::where('anggota_id', $anggota->id)
            ->with('anggota')
            ->orderByDesc('tanggal_pengajuan')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'data' => PengajuanLimitResource::collection($rows)->resolve(),
            'limit_saat_ini' => (float) $this->eligibilitas->limitMaksimal($anggota),
            'plafon_kebijakan' => (float) $this->eligibilitas->plafonTertinggiKoperasi(),
        ]);
    }

    /**
     * Ajukan kenaikan limit. Mirror validasi mock router.ts baris 1031-1094.
     *
     * @response 201 {"message": "Pengajuan limit berhasil dikirim.", "data": {"id": 1}}
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'limit_diminta' => ['required', 'numeric', 'min:1'],
            'keterangan' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        $anggota = $this->anggotaOrFail($request);

        $sekarang = $this->eligibilitas->limitMaksimal($anggota);
        $plafon = $this->eligibilitas->plafonTertinggiKoperasi();

        if ((float) $request->limit_diminta <= $sekarang) {
            return response()->json([
                'message' => 'Limit yang diminta harus lebih besar dari limit saat ini (Rp '.number_format($sekarang, 0, ',', '.').').',
                'errors' => ['limit_diminta' => ['Nominal harus lebih besar dari limit saat ini.']],
            ], 422);
        }

        if ((float) $request->limit_diminta > $plafon) {
            return response()->json([
                'message' => 'Nominal melebihi plafon kebijakan koperasi.',
                'errors' => ['limit_diminta' => ['Nominal melebihi plafon kebijakan koperasi.']],
            ], 422);
        }

        try {
            $pengajuan = $this->service->ajukan(
                $anggota,
                (float) $request->limit_diminta,
                $request->keterangan
            );
        } catch (RuntimeException $e) {
            return response()->json([
                'message' => 'Masih ada pengajuan limit yang sedang diproses.',
                'errors' => ['limit_diminta' => ['Selesaikan pengajuan sebelumnya terlebih dahulu.']],
            ], 422);
        }

        $pengajuan->loadMissing('anggota');

        return response()->json([
            'message' => 'Pengajuan limit berhasil dikirim.',
            'data' => (new PengajuanLimitResource($pengajuan))->resolve(),
        ], 201);
    }
}
