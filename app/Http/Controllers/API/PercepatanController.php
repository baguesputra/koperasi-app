<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PengajuanPercepatan;
use App\Models\Anggota;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Percepatan', description: 'Tenor change applications')]
class PercepatanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    #[OA\Get(
        path: '/api/percepatan',
        summary: 'List member tenor change applications',
        tags: ['Percepatan'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', description: 'Filter by status', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', description: 'Page number', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Items per page', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Paginated list of tenor applications',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/PengajuanPercepatan')),
                        new OA\Property(property: 'current_page', type: 'integer'),
                        new OA\Property(property: 'last_page', type: 'integer'),
                        new OA\Property(property: 'per_page', type: 'integer'),
                        new OA\Property(property: 'total', type: 'integer'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Anggota not found'),
        ]
    )]
    public function index(Request $request)
    {
        $anggota = $request->user()->anggota;
        
        if (!$anggota) {
            return response()->json([
                'message' => 'Anggota not found for user',
            ], 404);
        }

        $query = PengajuanPercepatan::whereHas('pinjaman.anggota', function ($q) use ($anggota) {
            $q->where('id', $anggota->id);
        })
        ->with(['pinjaman.anggota.user']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $percepatans = $query->latest()->paginate(15);

        return response()->json($percepatans);
    }

    /**
     * Store a newly created resource in storage.
     */
    #[OA\Post(
        path: '/api/percepatan',
        summary: 'Submit a tenor change request',
        tags: ['Percepatan'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['pinjaman_id', 'tenor_lama', 'tenor_baru'],
                properties: [
                    new OA\Property(property: 'pinjaman_id', type: 'integer', example: 1),
                    new OA\Property(property: 'tenor_lama', type: 'integer', minimum: 1, example: 12),
                    new OA\Property(property: 'tenor_baru', type: 'integer', minimum: 1, maximum: 120, example: 24),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Tenor change application created',
                content: new OA\JsonContent(ref: '#/components/schemas/PengajuanPercepatan')
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Only active members can submit applications / Can only apply on active loans'),
            new OA\Response(response: 404, description: 'Pinjaman not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(Request $request)
    {
        $request->validate([
            'pinjaman_id' => 'required|exists:pinjaman,id',
            'tenor_lama' => 'required|integer|min:1',
            'tenor_baru' => 'required|integer|min:1|max:120',
        ]);

        $anggota = $request->user()->anggota;
        
        if (!$anggota) {
            return response()->json([
                'message' => 'Anggota not found for user',
            ], 404);
        }

        if ($anggota->status !== 'aktif') {
            return response()->json([
                'message' => 'Only active members can submit applications',
            ], 403);
        }

        $pinjaman = \App\Models\Pinjaman::where('id', $request->pinjaman_id)
            ->where('anggota_id', $anggota->id)
            ->first();
            
        if (!$pinjaman) {
            return response()->json([
                'message' => 'Pinjaman not found or does not belong to you',
            ], 404);
        }

        if ($pinjaman->status !== 'aktif') {
            return response()->json([
                'message' => 'Can only apply for tenor change on active loans',
            ], 403);
        }

        $pengajuanPercepatan = PengajuanPercepatan::create([
            'pinjaman_id' => $request->pinjaman_id,
            'tenor_lama' => $request->tenor_lama,
            'tenor_baru' => $request->tenor_baru,
            'status' => 'diajukan',
            'tanggal_pengajuan' => now(),
        ]);

        return response()->json($pengajuanPercepatan, 201);
    }

    /**
     * Display the specified resource.
     */
    #[OA\Get(
        path: '/api/percepatan/{id}',
        summary: 'Get tenor application details',
        tags: ['Percepatan'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Tenor application ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tenor application details',
                content: new OA\JsonContent(ref: '#/components/schemas/PengajuanPercepatan')
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Unauthorized - application does not belong to you'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(Request $request, PengajuanPercepatan $pengajuanPercepatan)
    {
        $anggota = $request->user()->anggota;
        
        if (!$anggota || $pengajuanPercepatan->pinjaman->anggota->id !== $anggota->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $pengajuanPercepatan->load(['pinjaman.anggota.user']);

        return response()->json($pengajuanPercepatan);
    }

    /**
     * Update the specified resource in storage.
     */
    #[OA\Put(
        path: '/api/percepatan/{id}',
        summary: 'Update tenor application (only if pending)',
        tags: ['Percepatan'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Tenor application ID', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'tenor_lama', type: 'integer', minimum: 1, example: 12),
                    new OA\Property(property: 'tenor_baru', type: 'integer', minimum: 1, maximum: 120, example: 24),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tenor application updated',
                content: new OA\JsonContent(ref: '#/components/schemas/PengajuanPercepatan')
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Can only update applications that are still pending'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(Request $request, PengajuanPercepatan $pengajuanPercepatan)
    {
        $anggota = $request->user()->anggota;
        
        if (!$anggota || $pengajuanPercepatan->pinjaman->anggota->id !== $anggota->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        if ($pengajuanPercepatan->status !== 'diajukan') {
            return response()->json([
                'message' => 'Can only update applications that are still pending',
            ], 403);
        }

        $request->validate([
            'tenor_lama' => 'sometimes|required|integer|min:1',
            'tenor_baru' => 'sometimes|required|integer|min:1|max:120',
        ]);

        $pengajuanPercepatan->update($request->only([
            'tenor_lama',
            'tenor_baru',
        ]));

        return response()->json($pengajuanPercepatan);
    }

    /**
     * Remove the specified resource from storage.
     */
    #[OA\Delete(
        path: '/api/percepatan/{id}',
        summary: 'Delete tenor application (only if pending)',
        tags: ['Percepatan'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Tenor application ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Application deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Application deleted successfully')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Can only delete applications that are still pending'),
        ]
    )]
    public function destroy(Request $request, PengajuanPercepatan $pengajuanPercepatan)
    {
        $anggota = $request->user()->anggota;
        
        if (!$anggota || $pengajuanPercepatan->pinjaman->anggota->id !== $anggota->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        if ($pengajuanPercepatan->status !== 'diajukan') {
            return response()->json([
                'message' => 'Can only delete applications that are still pending',
            ], 403);
        }

        $pengajuanPercepatan->delete();

        return response()->json([
            'message' => 'Application deleted successfully',
        ]);
    }

    /**
     * Preview tenor changes impact
     */
    #[OA\Post(
        path: '/api/percepatan/{id}/preview',
        summary: 'Preview tenor change impact',
        tags: ['Percepatan'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Tenor application ID', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'tenor_lama', type: 'integer', minimum: 1, example: 12),
                    new OA\Property(property: 'tenor_baru', type: 'integer', minimum: 1, maximum: 120, example: 24),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tenor change preview',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'current', type: 'object',
                            properties: [
                                new OA\Property(property: 'tenor_sisa', type: 'integer'),
                                new OA\Property(property: 'pokok_per_bulan', type: 'number', format: 'float'),
                                new OA\Property(property: 'bunga_per_bulan', type: 'number', format: 'float'),
                                new OA\Property(property: 'total_per_bulan', type: 'number', format: 'float'),
                                new OA\Property(property: 'total_sisa', type: 'number', format: 'float'),
                            ]
                        ),
                        new OA\Property(property: 'proposed', type: 'object',
                            properties: [
                                new OA\Property(property: 'tenor_baru', type: 'integer'),
                                new OA\Property(property: 'pokok_per_bulan', type: 'number', format: 'float'),
                                new OA\Property(property: 'bunga_per_bulan', type: 'number', format: 'float'),
                                new OA\Property(property: 'total_per_bulan', type: 'number', format: 'float'),
                                new OA\Property(property: 'total_baru', type: 'number', format: 'float'),
                            ]
                        ),
                        new OA\Property(property: 'comparison', type: 'object',
                            properties: [
                                new OA\Property(property: 'selisih_total', type: 'number', format: 'float'),
                                new OA\Property(property: 'perubahan_tenor', type: 'integer'),
                            ]
                        ),
                        new OA\Property(property: 'message', type: 'string'),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'No remaining installments found'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Unauthorized'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function preview(Request $request, PengajuanPercepatan $pengajuanPercepatan)
    {
        $anggota = $request->user()->anggota;
        
        if (!$anggota || $pengajuanPercepatan->pinjaman->anggota->id !== $anggota->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $request->validate([
            'tenor_lama' => 'sometimes|required|integer|min:1',
            'tenor_baru' => 'sometimes|required|integer|min:1|max:120',
        ]);

        $tenorLama = $request->tenor_lama ?? $pengajuanPercepatan->tenor_lama;
        $tenorBaru = $request->tenor_baru ?? $pengajuanPercepatan->tenor_baru;
        
        $pinjaman = $pengajuanPercepatan->pinjaman;
        
        $angsuranList = $pinjaman->angsuran()
            ->where('status', 'belum_bayar')
            ->orderBy('cicilan_ke')
            ->get();
            
        if ($angsuranList->isEmpty()) {
            return response()->json([
                'message' => 'No remaining installments found',
            ], 400);
        }
        
        $currentPokokPerBulan = $angsuranList->avg('nominal_pokok');
        $currentBungaPerBulan = $angsuranList->avg('nominal_bunga');
        $currentTotalPerBulan = $angsuranList->avg('total_bayar');
        $remainingCicilan = $angsuranList->count();
        
        $pokokPerBulanBaru = $pinjaman->nominal / $tenorBaru;
        $bungaRate = $pinjaman->persentase_bunga;
        $totalBungaBaru = ($pinjaman->nominal * $bungaRate / 100) * $tenorBaru;
        $bungaPerBulanBaru = $totalBungaBaru / $tenorBaru;
        $totalPerBulanBaru = $pokokPerBulanBaru + $bungaPerBulanBaru;
        
        $totalSaatIni = $currentTotalPerBulan * $remainingCicilan;
        $totalBaru = $totalPerBulanBaru * $tenorBaru;
        $selisih = $totalBaru - $totalSaatIni;

        return response()->json([
            'current' => [
                'tenor_sisa' => $remainingCicilan,
                'pokok_per_bulan' => $currentPokokPerBulan,
                'bunga_per_bulan' => $currentBungaPerBulan,
                'total_per_bulan' => $currentTotalPerBulan,
                'total_sisa' => $totalSaatIni,
            ],
            'proposed' => [
                'tenor_baru' => $tenorBaru,
                'pokok_per_bulan' => $pokokPerBulanBaru,
                'bunga_per_bulan' => $bungaPerBulanBaru,
                'total_per_bulan' => $totalPerBulanBaru,
                'total_baru' => $totalBaru,
            ],
            'comparison' => [
                'selisih_total' => $selisih,
                'perubahan_tenor' => $tenorBaru - $tenorLama,
            ],
            'message' => $selisih > 0
                ? 'Tenor extension will increase total payment by ' . number_format($selisih, 0, ',', '.')
                : 'Tenor reduction will decrease total payment by ' . number_format(abs($selisih), 0, ',', '.'),
        ]);
    }
}