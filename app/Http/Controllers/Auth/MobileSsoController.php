<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\SessionUserResource;
use App\Models\MobileSsoCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class MobileSsoController extends Controller
{
    public function exchange(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'size:64', 'regex:/^[A-Za-z0-9_-]+$/'],
            'state' => ['required', 'string', 'min:43', 'max:128', 'regex:/^[A-Za-z0-9_-]+$/'],
            'code_verifier' => ['required', 'string', 'min:43', 'max:128', 'regex:/^[A-Za-z0-9._~-]+$/'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $result = DB::transaction(function () use ($data) {
            $record = MobileSsoCode::query()->where('code_hash', hash('sha256', $data['code']))->lockForUpdate()->first();
            if (! $record || $record->used_at || $record->expires_at->isPast() || ! hash_equals($record->state, $data['state'])) {
                return null;
            }

            $challenge = rtrim(strtr(base64_encode(hash('sha256', $data['code_verifier'], true)), '+/', '-_'), '=');
            if (! hash_equals($record->code_challenge, $challenge)) {
                return null;
            }

            $record->update(['used_at' => now()]);
            $user = $record->user;
            if (! $user || $user->status === 'nonaktif') {
                return null;
            }

            return [
                'user' => new SessionUserResource($user),
                'token' => $user->createToken($data['device_name'] ?? 'Koperasi Mobile')->plainTextToken,
            ];
        });

        if (! $result) {
            return response()->json(['message' => 'Kode SSO tidak valid, kedaluwarsa, atau sudah digunakan.'], 422);
        }

        return response()->json($result)->header('Cache-Control', 'no-store');
    }

    public static function issue(Request $request): string
    {
        $context = $request->session()->pull('mobile_sso');
        abort_unless(is_array($context) && isset($context['state'], $context['code_challenge']), 400);

        MobileSsoCode::where('expires_at', '<', now())->delete();
        $code = Str::random(64);
        MobileSsoCode::create([
            'user_id' => $request->user()->id,
            'code_hash' => hash('sha256', $code),
            'code_challenge' => $context['code_challenge'],
            'state' => $context['state'],
            'expires_at' => now()->addMinutes(2),
        ]);

        return 'koperasi://auth/sso/callback?'.http_build_query(['code' => $code, 'state' => $context['state']]);
    }

    public function redirect(Request $request)
    {
        $data = $request->validate([
            'state' => ['required', 'string', 'min:43', 'max:128', 'regex:/^[A-Za-z0-9_-]+$/'],
            'code_challenge' => ['required', 'string', 'size:43', 'regex:/^[A-Za-z0-9_-]+$/'],
        ]);
        $request->session()->put('mobile_sso', $data);

        $target = Socialite::driver('perusahaan')->stateless()->redirect()->getTargetUrl();

        return redirect()->away($target);
    }
}
