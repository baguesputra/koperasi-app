<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PengajuanLimit;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Pengajuan Limit', description: 'Limit increase applications')]
class PengajuanLimitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    #[OA\Get(
        path: '/api/pengajuan-limit',
        summary: 'List member limit increase applications',
        tags: ['Pengajuan Limit'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', description: 'Filter by status', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', description: 'Page number', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Items per page', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Paginated list of limit applications',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/PengajuanLimit')),
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

        if (! $anggota) {
            return response()->json([
                'message' => 'Anggota not found for user',
            ], 404);
        }

        $query = PengajuanLimit::where('anggota_id', $anggota->id)
            ->with(['anggota.user']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $pengajuanLimits = $query->latest()->paginate(15);

        return response()->json($pengajuanLimits);
    }

    /**
     * Store a newly created resource in storage.
     */
    #[OA\Post(
        path: '/api/pengajuan-limit',
        summary: 'Submit a limit increase request',
        tags: ['Pengajuan Limit'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['limit_diminta'],
                properties: [
                    new OA\Property(property: 'limit_diminta', type: 'number', format: 'float', minimum: 1000000, example: 10000000),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Limit increase application created',
                content: new OA\JsonContent(ref: '#/components/schemas/PengajuanLimit')
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Only active members can submit applications'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(Request $request)
    {
        $request->validate([
            'limit_diminta' => 'required|numeric|min:1000000',
        ]);

        $anggota = $request->user()->anggota;

        if (! $anggota) {
            return response()->json([
                'message' => 'Anggota not found for user',
            ], 404);
        }

        if ($anggota->status !== 'aktif') {
            return response()->json([
                'message' => 'Only active members can submit applications',
            ], 403);
        }

        $limitSaatIni = $anggota->limit_custom ?? 0;

        $pengajuanLimit = PengajuanLimit::create([
            'anggota_id' => $anggota->id,
            'limit_saat_ini' => $limitSaatIni,
            'limit_diminta' => $request->limit_diminta,
            'keterangan' => 'Pengajuan via mobile app',
            'status' => 'diajukan',
            'tanggal_pengajuan' => now(),
        ]);

        return response()->json($pengajuanLimit, 201);
    }

    /**
     * Display the specified resource.
     */
    #[OA\Get(
        path: '/api/pengajuan-limit/{id}',
        summary: 'Get limit application details',
        tags: ['Pengajuan Limit'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Limit application ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Limit application details',
                content: new OA\JsonContent(ref: '#/components/schemas/PengajuanLimit')
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Unauthorized - application does not belong to you'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(Request $request, PengajuanLimit $pengajuanLimit)
    {
        $anggota = $request->user()->anggota;

        if (! $anggota || $pengajuanLimit->anggota_id !== $anggota->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $pengajuanLimit->load(['anggota.user']);

        return response()->json($pengajuanLimit);
    }

    /**
     * Update the specified resource in storage.
     */
    #[OA\Put(
        path: '/api/pengajuan-limit/{id}',
        summary: 'Update limit application (only if pending)',
        tags: ['Pengajuan Limit'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Limit application ID', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'limit_diminta', type: 'number', format: 'float', minimum: 1000000, example: 10000000),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Limit application updated',
                content: new OA\JsonContent(ref: '#/components/schemas/PengajuanLimit')
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Can only update applications that are still pending'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(Request $request, PengajuanLimit $pengajuanLimit)
    {
        $anggota = $request->user()->anggota;

        if (! $anggota || $pengajuanLimit->anggota_id !== $anggota->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        if ($pengajuanLimit->status !== 'diajukan') {
            return response()->json([
                'message' => 'Can only update applications that are still pending',
            ], 403);
        }

        $request->validate([
            'limit_diminta' => 'sometimes|required|numeric|min:1000000',
        ]);

        $pengajuanLimit->update($request->only([
            'limit_diminta',
        ]));

        return response()->json($pengajuanLimit);
    }

    /**
     * Remove the specified resource from storage.
     */
    #[OA\Delete(
        path: '/api/pengajuan-limit/{id}',
        summary: 'Delete limit application (only if pending)',
        tags: ['Pengajuan Limit'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Limit application ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Application deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Application deleted successfully'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Can only delete applications that are still pending'),
        ]
    )]
    public function destroy(Request $request, PengajuanLimit $pengajuanLimit)
    {
        $anggota = $request->user()->anggota;

        if (! $anggota || $pengajuanLimit->anggota_id !== $anggota->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        if ($pengajuanLimit->status !== 'diajukan') {
            return response()->json([
                'message' => 'Can only delete applications that are still pending',
            ], 403);
        }

        $pengajuanLimit->delete();

        return response()->json([
            'message' => 'Application deleted successfully',
        ]);
    }
}
