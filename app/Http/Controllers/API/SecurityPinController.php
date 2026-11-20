<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\Security\PinSecurityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SecurityPinController extends Controller
{
    public function __construct(
        private PinSecurityService $pinService,
    ) {}

    /**
     * Status PIN 6-digit pengguna login (apakah sudah diatur atau sedang terkunci).
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'has_pin' => (bool) $user->hasPin(),
            'terkunci' => $this->pinService->terkunci($user),
            'locked_until' => $user->pin_locked_until?->toIso8601String(),
        ]);
    }

    /**
     * Atur PIN baru atau perbarui PIN keamanan 6-digit.
     * Mirror Security\PinSecurityController@setup.
     */
    public function setup(Request $request): JsonResponse
    {
        $user = $request->user();

        $rules = [
            'pin' => ['required', 'string', 'size:6', 'regex:/^[0-9]{6}$/', 'confirmed', 'not_regex:/(.)\\1{5}/'],
        ];

        if ($user->hasPin()) {
            $rules['old_pin'] = ['required', 'string', 'size:6'];
        }

        $request->validate($rules, [
            'pin.required' => 'PIN wajib diisi.',
            'pin.size' => 'PIN harus tepat 6 digit angka.',
            'pin.regex' => 'PIN hanya boleh berisi angka.',
            'pin.confirmed' => 'Konfirmasi PIN tidak cocok.',
            'pin.not_regex' => 'PIN terlalu lemah (6 digit sama).',
            'old_pin.required' => 'PIN lama wajib diisi.',
            'old_pin.size' => 'PIN lama harus 6 digit angka.',
        ]);

        if ($user->hasPin() && ! Hash::check((string) $request->old_pin, (string) $user->pin)) {
            return response()->json([
                'status' => 'failed',
                'message' => 'PIN lama tidak sesuai.',
                'errors' => ['old_pin' => ['PIN lama tidak sesuai.']],
            ], 422);
        }

        $this->pinService->setupPin($user, (string) $request->pin);

        return response()->json([
            'status' => 'success',
            'message' => 'PIN keamanan berhasil disimpan.',
            'has_pin' => true,
        ]);
    }

    /**
     * Verifikasi PIN 6-digit (untuk Screen Lock & otorisasi transaksi).
     */
    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'pin' => ['required', 'string', 'size:6', 'regex:/^[0-9]{6}$/'],
        ], [
            'pin.required' => 'Masukkan 6 digit PIN.',
            'pin.size' => 'PIN harus 6 digit angka.',
            'pin.regex' => 'PIN hanya boleh berisi angka.',
        ]);

        $user = $request->user();
        $result = $this->pinService->verify($user, (string) $request->pin, $request);

        if ($result['valid']) {
            return response()->json([
                'status' => 'success',
                'message' => 'PIN valid.',
            ]);
        }

        if ($result['locked_out']) {
            return response()->json([
                'status' => 'locked_out',
                'message' => $result['message'],
            ], 423);
        }

        return response()->json([
            'status' => 'failed',
            'message' => $result['message'],
            'remaining' => $result['remaining'],
        ], 422);
    }
}
