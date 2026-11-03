<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Services\Security\PinSecurityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PinSecurityController extends Controller
{
    public function __construct(
        private PinSecurityService $pinService
    ) {}

    /**
     * Setup PIN baru atau perbarui PIN pengguna.
     * ponytail: old_pin wajib bila sudah punya PIN; reset hanya via admin/percobaan habis.
     */
    public function setup(Request $request): JsonResponse
    {
        $user = $request->user();

        $rules = [
            'pin' => ['required', 'string', 'size:6', 'regex:/^[0-9]{6}$/', 'confirmed', 'not_regex:/(.)\\1{5}/'],
        ];

        // Wajib verifikasi PIN lama bila sudah punya PIN — tanpa celah overwrite via sesi terbajak.
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

        if ($user->hasPin()) {
            if (! Hash::check($request->old_pin, $user->pin)) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'PIN lama tidak sesuai.',
                    'errors' => ['old_pin' => ['PIN lama tidak sesuai.']],
                ], 422);
            }
        }

        $this->pinService->setupPin($user, $request->pin);

        return response()->json([
            'status' => 'success',
            'message' => 'PIN keamanan berhasil disimpan.',
        ]);
    }

    /**
     * Verifikasi PIN untuk membuka Screen Lock.
     */
    public function verifyUnlock(Request $request): JsonResponse
    {
        $request->validate([
            'pin' => ['required', 'string', 'size:6', 'regex:/^[0-9]{6}$/'],
        ], [
            'pin.required' => 'Masukkan 6 digit PIN.',
            'pin.size' => 'PIN harus 6 digit angka.',
            'pin.regex' => 'PIN hanya boleh berisi angka.',
        ]);

        $user = $request->user();
        $result = $this->pinService->verify($user, $request->pin, $request);

        if ($result['valid']) {
            return response()->json([
                'status' => 'success',
                'message' => 'Layar berhasil dibuka.',
            ]);
        }

        if ($result['locked_out']) {
            return response()->json([
                'status' => 'locked_out',
                'message' => $result['message'],
                'redirect' => route('login'),
            ], 423);
        }

        return response()->json([
            'status' => 'failed',
            'message' => $result['message'],
            'remaining' => $result['remaining'],
        ], 422);
    }
}

