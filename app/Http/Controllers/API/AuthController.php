<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\SessionUserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login user and return Sanctum token.
     *
     * Only available when AUTH_MODE=local. In SSO mode use the browser
     * SSO flow and exchange the web session at POST /api/v1/sso/token.
     */
    public function login(Request $request)
    {
        if (config('auth.mode') === 'sso') {
            return response()->json([
                'message' => 'Login password dinonaktifkan. Gunakan login SSO via browser.',
            ], 403);
        }

        $request->validate([
            'no_karyawan' => 'required|string',
            'password' => 'required|string',
            'device_name' => 'nullable|string',
        ]);

        $user = User::whereRaw('UPPER(no_karyawan) = ?', [strtoupper($request->no_karyawan)])->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'no_karyawan' => ['Nomor karyawan atau kata sandi tidak sesuai.'],
            ]);
        }

        $token = $user->createToken($request->device_name ?? 'mobile')->plainTextToken;

        return response()->json([
            'user' => new SessionUserResource($user),
            'token' => $token,
            'harus_ganti_password' => (bool) $user->harus_ganti_password,
        ]);
    }

    /**
     * Logout user (revoke current token).
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Berhasil keluar.',
        ]);
    }

    /**
     * Get the authenticated user.
     */
    public function user(Request $request)
    {
        return response()->json($request->user());
    }

    /**
     * Get the authenticated user shaped for the mobile app.
     */
    public function me(Request $request)
    {
        return response()->json([
            'user' => new SessionUserResource($request->user()),
        ]);
    }
}
