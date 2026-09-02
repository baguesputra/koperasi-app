<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Pinjaman;
use App\Models\SettingBunga;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Pengajuan Anggota', description: 'Member loan applications')]
class PengajuanAnggotaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    #[OA\Get(
        path: '/api/pengajuan-anggota',
        summary: 'List member loan applications',
        tags: ['Pengajuan Anggota'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', description: 'Filter by status', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'start_date', in: 'query', description: 'Filter by start date (Y-m-d)', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'end_date', in: 'query', description: 'Filter by end date (Y-m-d)', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'page', in: 'query', description: 'Page number', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Items per page', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Paginated list of loan applications',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Pinjaman')),
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

        $query = Pinjaman::where('anggota_id', $anggota->id)
            ->with(['anggota.user', 'pengaju']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('tanggal_pengajuan', [
                $request->start_date,
                $request->end_date,
            ]);
        }

        $pengajuans = $query->latest()->paginate(15);

        return response()->json($pengajuans);
    }

    /**
     * Store a newly created resource in storage.
     */
    #[OA\Post(
        path: '/api/pengajuan-anggota',
        summary: 'Create a new loan application',
        tags: ['Pengajuan Anggota'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nominal', 'tenor_bulan', 'keperluan'],
                properties: [
                    new OA\Property(property: 'nominal', type: 'number', format: 'float', minimum: 1000000, example: 5000000),
                    new OA\Property(property: 'tenor_bulan', type: 'integer', minimum: 1, maximum: 120, example: 12),
                    new OA\Property(property: 'keperluan', type: 'string', maxLength: 255, example: 'Renovasi rumah'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Loan application created',
                content: new OA\JsonContent(ref: '#/components/schemas/Pinjaman')
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Only active members can submit applications'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(Request $request)
    {
        $request->validate([
            'nominal' => 'required|numeric|min:1000000',
            'tenor_bulan' => 'required|integer|min:1|max:120',
            'keperluan' => 'required|string|max:255',
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

        $bungaRate = SettingBunga::latest('berlaku_dari_tanggal')
            ->value('persentase') ?? 1.5;

        $pengajuan = Pinjaman::create([
            'anggota_id' => $anggota->id,
            'pengaju_user_id' => $request->user()->id,
            'nominal' => $request->nominal,
            'tenor_bulan' => $request->tenor_bulan,
            'keperluan' => $request->keperluan,
            'persentase_bunga' => $bungaRate,
            'status' => 'diajukan',
            'tanggal_pengajuan' => now(),
        ]);

        return response()->json($pengajuan, 201);
    }

    /**
     * Display the specified resource.
     */
    #[OA\Get(
        path: '/api/pengajuan-anggota/{id}',
        summary: 'Get loan application details',
        tags: ['Pengajuan Anggota'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Loan application ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Loan application details',
                content: new OA\JsonContent(ref: '#/components/schemas/Pinjaman')
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Unauthorized - application does not belong to you'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(Request $request, Pinjaman $pengajuan)
    {
        $anggota = $request->user()->anggota;

        if (! $anggota || $pengajuan->anggota_id !== $anggota->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $pengajuan->load(['anggota.user', 'pengaju']);

        return response()->json($pengajuan);
    }

    /**
     * Update the specified resource in storage.
     */
    #[OA\Put(
        path: '/api/pengajuan-anggota/{id}',
        summary: 'Update loan application (only if pending)',
        tags: ['Pengajuan Anggota'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Loan application ID', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'nominal', type: 'number', format: 'float', minimum: 1000000, example: 5000000),
                    new OA\Property(property: 'tenor_bulan', type: 'integer', minimum: 1, maximum: 120, example: 12),
                    new OA\Property(property: 'keperluan', type: 'string', maxLength: 255, example: 'Renovasi rumah'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Loan application updated',
                content: new OA\JsonContent(ref: '#/components/schemas/Pinjaman')
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Can only update applications that are still pending'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(Request $request, Pinjaman $pengajuan)
    {
        $anggota = $request->user()->anggota;

        if (! $anggota || $pengajuan->anggota_id !== $anggota->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        if ($pengajuan->status !== 'diajukan') {
            return response()->json([
                'message' => 'Can only update applications that are still pending',
            ], 403);
        }

        $request->validate([
            'nominal' => 'sometimes|required|numeric|min:1000000',
            'tenor_bulan' => 'sometimes|required|integer|min:1|max:120',
            'keperluan' => 'sometimes|required|string|max:255',
        ]);

        $pengajuan->update($request->only([
            'nominal',
            'tenor_bulan',
            'keperluan',
        ]));

        return response()->json($pengajuan);
    }

    /**
     * Remove the specified resource from storage.
     */
    #[OA\Delete(
        path: '/api/pengajuan-anggota/{id}',
        summary: 'Delete loan application (only if pending)',
        tags: ['Pengajuan Anggota'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Loan application ID', schema: new OA\Schema(type: 'integer')),
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
    public function destroy(Request $request, Pinjaman $pengajuan)
    {
        $anggota = $request->user()->anggota;

        if (! $anggota || $pengajuan->anggota_id !== $anggota->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        if ($pengajuan->status !== 'diajukan') {
            return response()->json([
                'message' => 'Can only delete applications that are still pending',
            ], 403);
        }

        $pengajuan->delete();

        return response()->json([
            'message' => 'Application deleted successfully',
        ]);
    }
}
