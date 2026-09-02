<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Pinjaman;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Pinjaman', description: 'Loan management')]
class PinjamanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    #[OA\Get(
        path: '/api/pinjaman',
        summary: 'List user loans',
        tags: ['Pinjaman'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', description: 'Filter by status', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', description: 'Page number', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Items per page', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Paginated list of loans',
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

        $pinjaman = $query->latest()->paginate(15);

        return response()->json($pinjaman);
    }

    /**
     * Store a newly created resource in storage.
     */
    #[OA\Post(
        path: '/api/pinjaman',
        summary: 'Submit a new loan application',
        tags: ['Pinjaman'],
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

        $pinjaman = Pinjaman::create([
            'anggota_id' => $anggota->id,
            'pengaju_user_id' => $request->user()->id,
            'nominal' => $request->nominal,
            'tenor_bulan' => $request->tenor_bulan,
            'keperluan' => $request->keperluan,
            'status' => 'diajukan',
            'tanggal_pengajuan' => now(),
        ]);

        return response()->json($pinjaman, 201);
    }

    /**
     * Display the specified resource.
     */
    #[OA\Get(
        path: '/api/pinjaman/{id}',
        summary: 'Get loan details',
        tags: ['Pinjaman'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Loan ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Loan details with installments',
                content: new OA\JsonContent(ref: '#/components/schemas/Pinjaman')
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Unauthorized - loan does not belong to you'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(Request $request, Pinjaman $pinjaman)
    {
        $anggota = $request->user()->anggota;

        if (! $anggota || $pinjaman->anggota_id !== $anggota->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $pinjaman->load(['anggota.user', 'pengaju', 'angsuran']);

        return response()->json($pinjaman);
    }

    /**
     * Check loan eligibility/nominal
     */
    #[OA\Post(
        path: '/api/pinjaman/{id}/cek-nominal',
        summary: 'Check loan eligibility for a nominal amount',
        tags: ['Pinjaman'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Loan ID', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nominal'],
                properties: [
                    new OA\Property(property: 'nominal', type: 'number', format: 'float', minimum: 1000000, example: 5000000),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Eligibility check result',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'eligible', type: 'boolean', example: true),
                        new OA\Property(property: 'max_nominal', type: 'number', format: 'float', example: 960000),
                        new OA\Property(property: 'current_nominal', type: 'number', format: 'float', example: 5000000),
                        new OA\Property(property: 'message', type: 'string', example: 'Loan amount exceeds maximum eligible amount'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Unauthorized'),
        ]
    )]
    public function cekNominal(Request $request, Pinjaman $pinjaman)
    {
        $anggota = $request->user()->anggota;

        if (! $anggota || $pinjaman->anggota_id !== $anggota->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $totalSimpanan = $anggota->simpanan()
            ->whereIn('jenis', ['pokok', 'wajib'])
            ->sum('jumlah');

        $maxNominal = $totalSimpanan * 3;

        return response()->json([
            'eligible' => $request->nominal <= $maxNominal,
            'max_nominal' => $maxNominal,
            'current_nominal' => $request->nominal,
            'message' => $request->nominal <= $maxNominal
                ? 'Loan amount is eligible'
                : 'Loan amount exceeds maximum eligible amount of '.number_format($maxNominal, 0, ',', '.'),
        ]);
    }

    /**
     * Simulate loan
     */
    #[OA\Post(
        path: '/api/pinjaman/{id}/simulasi',
        summary: 'Simulate loan repayment schedule',
        tags: ['Pinjaman'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Loan ID', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'nominal', type: 'number', format: 'float', minimum: 1000000, example: 1000000),
                    new OA\Property(property: 'tenor_bulan', type: 'integer', minimum: 1, maximum: 120, example: 12),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Loan simulation result',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'nominal', type: 'number', format: 'float'),
                        new OA\Property(property: 'tenor_bulan', type: 'integer'),
                        new OA\Property(property: 'pokok_per_bulan', type: 'number', format: 'float'),
                        new OA\Property(property: 'bunga_per_bulan', type: 'number', format: 'float'),
                        new OA\Property(property: 'angsuran_per_bulan', type: 'number', format: 'float'),
                        new OA\Property(property: 'total_bunga', type: 'number', format: 'float'),
                        new OA\Property(property: 'total_pembayaran', type: 'number', format: 'float'),
                        new OA\Property(property: 'rincian_angsuran', type: 'array', items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'cicilan_ke', type: 'integer'),
                                new OA\Property(property: 'pokok', type: 'number', format: 'float'),
                                new OA\Property(property: 'bunga', type: 'number', format: 'float'),
                                new OA\Property(property: 'total', type: 'number', format: 'float'),
                            ]
                        )),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Unauthorized'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function simulasi(Request $request, Pinjaman $pinjaman)
    {
        $anggota = $request->user()->anggota;

        if (! $anggota || $pinjaman->anggota_id !== $anggota->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $request->validate([
            'nominal' => 'sometimes|required|numeric|min:1000000',
            'tenor_bulan' => 'sometimes|required|integer|min:1|max:120',
        ]);

        $nominal = $request->nominal ?? $pinjaman->nominal;
        $tenorBulan = $request->tenor_bulan ?? $pinjaman->tenor_bulan;

        $bungaPersentase = 1.5;

        $bungaPerBulan = ($nominal * $bungaPersentase / 100);
        $pokokPerBulan = $nominal / $tenorBulan;
        $angsuranPerBulan = $pokokPerBulan + $bungaPerBulan;
        $totalBunga = $bungaPerBulan * $tenorBulan;
        $totalPembayaran = $nominal + $totalBunga;

        return response()->json([
            'nominal' => $nominal,
            'tenor_bulan' => $tenorBulan,
            'pokok_per_bulan' => $pokokPerBulan,
            'bunga_per_bulan' => $bungaPerBulan,
            'angsuran_per_bulan' => $angsuranPerBulan,
            'total_bunga' => $totalBunga,
            'total_pembayaran' => $totalPembayaran,
            'rincian_angsuran' => array_map(fn ($i) => [
                'cicilan_ke' => $i + 1,
                'pokok' => $pokokPerBulan,
                'bunga' => $bungaPerBulan,
                'total' => $angsuranPerBulan,
            ], range(0, $tenorBulan - 1)),
        ]);
    }
}
