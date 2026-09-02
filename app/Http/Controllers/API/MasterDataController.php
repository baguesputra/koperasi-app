<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Anggota;
use App\Models\SettingSimpanan;
use App\Models\TabelTenor;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Master Data', description: 'Reference data for forms and dropdowns')]
class MasterDataController extends Controller
{
    /**
     * Get list of cabang
     */
    #[OA\Get(
        path: '/api/master-data/cabang',
        summary: 'Get list of branches (cabang)',
        tags: ['Master Data'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of branches',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(type: 'string', example: 'Banjarmasin')
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function cabang(Request $request)
    {
        $cabang = Anggota::distinct()
            ->pluck('cabang')
            ->sort()
            ->values()
            ->all();

        return response()->json($cabang);
    }

    /**
     * Get list of unit bisnis
     */
    #[OA\Get(
        path: '/api/master-data/unit-bisnis',
        summary: 'Get list of business units',
        tags: ['Master Data'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of business units',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(type: 'string', example: 'Operasional')
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function unitBisnis(Request $request)
    {
        $unitBisnis = Anggota::distinct()
            ->pluck('unit_bisnis')
            ->sort()
            ->values()
            ->all();

        return response()->json($unitBisnis);
    }

    /**
     * Get list of jabatan
     */
    #[OA\Get(
        path: '/api/master-data/jabatan',
        summary: 'Get list of positions (jabatan)',
        tags: ['Master Data'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of positions',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(type: 'string', example: 'staff')
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function jabatan(Request $request)
    {
        $jabatan = Anggota::distinct()
            ->pluck('jabatan')
            ->sort()
            ->values()
            ->all();

        return response()->json($jabatan);
    }

    /**
     * Get list of divisi
     */
    #[OA\Get(
        path: '/api/master-data/divisi',
        summary: 'Get list of divisions (divisi)',
        tags: ['Master Data'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of divisions',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(type: 'string', example: 'Lapangan')
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function divisi(Request $request)
    {
        $divisi = Anggota::distinct()
            ->pluck('divisi')
            ->sort()
            ->values()
            ->all();

        return response()->json($divisi);
    }

    /**
     * Get list of department
     */
    #[OA\Get(
        path: '/api/master-data/department',
        summary: 'Get list of departments',
        tags: ['Master Data'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of departments',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(type: 'string', example: 'Operasional')
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function department(Request $request)
    {
        $department = Anggota::distinct()
            ->pluck('department')
            ->sort()
            ->values()
            ->all();

        return response()->json($department);
    }

    /**
     * Get tabel tenor
     */
    #[OA\Get(
        path: '/api/master-data/tabel-tenor',
        summary: 'Get tenor table for loan applications',
        tags: ['Master Data'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tenor table',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'nominal_min', type: 'string', example: '0.00'),
                            new OA\Property(property: 'nominal_max', type: 'string', example: '1000000.00'),
                            new OA\Property(property: 'tenor_maksimal_bulan', type: 'integer', example: 3),
                        ]
                    )
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function tabelTenor(Request $request)
    {
        $tabelTenor = TabelTenor::orderBy('nominal_min')
            ->get(['nominal_min', 'nominal_max', 'tenor_maksimal_bulan']);

        return response()->json($tabelTenor);
    }

    /**
     * Get setting simpanan
     */
    #[OA\Get(
        path: '/api/master-data/setting-simpanan',
        summary: 'Get savings settings',
        tags: ['Master Data'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Savings settings',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'jenis', type: 'string', example: 'pokok'),
                            new OA\Property(property: 'label', type: 'string', example: 'Simpanan Pokok'),
                            new OA\Property(property: 'nominal', type: 'string', example: '50000.00'),
                        ]
                    )
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function settingSimpanan(Request $request)
    {
        $settingSimpanan = SettingSimpanan::orderBy('id')
            ->get(['jenis', 'label', 'nominal']);

        return response()->json($settingSimpanan);
    }
}
