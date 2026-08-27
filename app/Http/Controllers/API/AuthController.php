<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

#[OA\Info(
    title: 'Koperasi App API',
    version: '1.0.0',
    description: 'RESTful API for Koperasi App mobile applications and integrations'
)]

#[OA\Tag(name: 'Authentication', description: 'Authentication endpoints')]

#[OA\Schema(
    schema: 'User',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
        new OA\Property(property: 'no_karyawan', type: 'string', example: 'TOP-100001'),
        new OA\Property(property: 'sso_id', type: 'string', nullable: true, example: null),
        new OA\Property(property: 'auth_provider', type: 'string', example: 'local'),
        new OA\Property(property: 'email_verified_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'harus_ganti_password', type: 'boolean', example: false),
        new OA\Property(property: 'status', type: 'string', example: 'aktif'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
    type: 'object'
)]

#[OA\Schema(
    schema: 'Pinjaman',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'anggota_id', type: 'integer', example: 1),
        new OA\Property(property: 'pengaju_user_id', type: 'integer', example: 4),
        new OA\Property(property: 'nominal', type: 'string', example: '5000000.00'),
        new OA\Property(property: 'tenor_bulan', type: 'integer', example: 12),
        new OA\Property(property: 'keperluan', type: 'string', example: 'Renovasi rumah'),
        new OA\Property(property: 'snapshot_bank', type: 'string', nullable: true, example: 'BCA'),
        new OA\Property(property: 'snapshot_no_rekening', type: 'string', nullable: true, example: '1234567890'),
        new OA\Property(property: 'snapshot_atas_nama', type: 'string', nullable: true, example: 'John Doe'),
        new OA\Property(property: 'persentase_bunga', type: 'string', example: '1.00'),
        new OA\Property(property: 'status', type: 'string', enum: ['diajukan', 'approved_bendahara', 'approved_ketua', 'aktif', 'ditolak', 'lunas'], example: 'diajukan'),
        new OA\Property(property: 'cair_oleh_bendahara', type: 'boolean', example: false),
        new OA\Property(property: 'sudah_pakai_privilege_reloan', type: 'boolean', example: false),
        new OA\Property(property: 'sudah_pakai_percepatan', type: 'boolean', example: false),
        new OA\Property(property: 'tanggal_pengajuan', type: 'string', format: 'date-time'),
        new OA\Property(property: 'tanggal_pencairan', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'disetujui_pada', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'versi_syarat', type: 'string', nullable: true),
        new OA\Property(property: 'ip_address_setuju', type: 'string', nullable: true),
        new OA\Property(property: 'user_agent_setuju', type: 'string', nullable: true),
        new OA\Property(property: 'catatan_bendahara', type: 'string', nullable: true),
        new OA\Property(property: 'catatan_ketua', type: 'string', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'anggota', ref: '#/components/schemas/Anggota'),
        new OA\Property(property: 'pengaju', ref: '#/components/schemas/User'),
    ],
    type: 'object'
)]

#[OA\Schema(
    schema: 'Anggota',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'user_id', type: 'integer', example: 4),
        new OA\Property(property: 'no_anggota', type: 'string', example: 'ANG-2026-0001'),
        new OA\Property(property: 'no_karyawan', type: 'string', example: 'TOP-100001'),
        new OA\Property(property: 'no_ktp', type: 'string', nullable: true),
        new OA\Property(property: 'nama', type: 'string', example: 'Budi Santoso'),
        new OA\Property(property: 'cabang', type: 'string', example: 'Banjarmasin'),
        new OA\Property(property: 'unit_bisnis', type: 'string', example: 'Operasional'),
        new OA\Property(property: 'department', type: 'string', example: 'Operasional'),
        new OA\Property(property: 'divisi', type: 'string', example: 'Lapangan'),
        new OA\Property(property: 'no_hp', type: 'string', nullable: true),
        new OA\Property(property: 'alamat', type: 'string', nullable: true),
        new OA\Property(property: 'jabatan', type: 'string', example: 'staff'),
        new OA\Property(property: 'tanggal_mulai_kerja', type: 'string', format: 'date'),
        new OA\Property(property: 'tanggal_jadi_anggota', type: 'string', format: 'date'),
        new OA\Property(property: 'status', type: 'string', example: 'aktif'),
        new OA\Property(property: 'tanggal_resign', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'alasan_resign', type: 'string', nullable: true),
        new OA\Property(property: 'resigned_by', type: 'integer', nullable: true),
        new OA\Property(property: 'resigned_settlement_json', type: 'array', nullable: true, items: new OA\Items()),
        new OA\Property(property: 'reaktivasi_history_json', type: 'array', nullable: true, items: new OA\Items()),
        new OA\Property(property: 'limit_custom', type: 'string', nullable: true),
        new OA\Property(property: 'limit_custom_keterangan', type: 'string', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'user', ref: '#/components/schemas/User'),
    ],
    type: 'object'
)]

#[OA\Schema(
    schema: 'PengajuanLimit',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'anggota_id', type: 'integer', example: 1),
        new OA\Property(property: 'limit_saat_ini', type: 'string', example: '0.00'),
        new OA\Property(property: 'limit_diminta', type: 'string', example: '10000000.00'),
        new OA\Property(property: 'keterangan', type: 'string', example: 'Pengajuan via mobile app'),
        new OA\Property(property: 'status', type: 'string', enum: ['diajukan', 'approved_ketua', 'ditolak'], example: 'diajukan'),
        new OA\Property(property: 'catatan_ketua', type: 'string', nullable: true),
        new OA\Property(property: 'tanggal_pengajuan', type: 'string', format: 'date'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'anggota', ref: '#/components/schemas/Anggota'),
    ],
    type: 'object'
)]

#[OA\Schema(
    schema: 'PengajuanPercepatan',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'pinjaman_id', type: 'integer', example: 1),
        new OA\Property(property: 'tenor_lama', type: 'integer', example: 12),
        new OA\Property(property: 'tenor_baru', type: 'integer', example: 24),
        new OA\Property(property: 'status', type: 'string', enum: ['diajukan', 'approved_bendahara', 'approved_ketua', 'aktif', 'ditolak'], example: 'diajukan'),
        new OA\Property(property: 'tanggal_pengajuan', type: 'string', format: 'date-time'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'pinjaman', ref: '#/components/schemas/Pinjaman'),
    ],
    type: 'object'
)]

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    #[OA\Post(
        path: '/api/register',
        summary: 'Register a new user',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'John Doe'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255, example: 'john@example.com'),
                    new OA\Property(property: 'password', type: 'string', minLength: 8, example: 'password123'),
                    new OA\Property(property: 'password_confirmation', type: 'string', minLength: 8, example: 'password123'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'User registered successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'user', ref: '#/components/schemas/User'),
                        new OA\Property(property: 'token', type: 'string', example: '1|abc123...')
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation error')
        ]
    )]
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    /**
     * Login user and return token
     */
    #[OA\Post(
        path: '/api/login',
        summary: 'Login user and return token',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
                    new OA\Property(property: 'password', type: 'string', example: 'password123'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login successful',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'user', ref: '#/components/schemas/User'),
                        new OA\Property(property: 'token', type: 'string', example: '1|abc123...')
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Invalid credentials')
        ]
    )]
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    /**
     * Logout user (revoke token)
     */
    #[OA\Post(
        path: '/api/logout',
        summary: 'Logout user (revoke token)',
        tags: ['Authentication'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Logged out successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Logged out successfully')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized')
        ]
    )]
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Get the authenticated user
     */
    #[OA\Get(
        path: '/api/user',
        summary: 'Get authenticated user',
        tags: ['Authentication'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Current user data',
                content: new OA\JsonContent(ref: '#/components/schemas/User')
            ),
            new OA\Response(response: 401, description: 'Unauthorized')
        ]
    )]
    public function user(Request $request)
    {
        return response()->json($request->user());
    }
}