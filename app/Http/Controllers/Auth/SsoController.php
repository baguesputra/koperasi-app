<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SsoController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('perusahaan')
            ->stateless()
            ->redirect();
    }

    public function callback()
    {
        try {
            $ssoUser = Socialite::driver('perusahaan')
                ->stateless()
                ->user();

            $raw = $ssoUser->getRaw();

            $ssoId = $raw['id'] ?? $raw['sub'] ?? null;
            $email = $raw['email'] ?? null;
            $nik   = $raw['nik'] ?? null;
            $name  = $raw['name'] ?? null;

            // Cari user berdasarkan SSO ID
            $user = User::where('sso_id', $ssoId)->first();

            // Jika belum ditemukan, cari berdasarkan email
            if (!$user && $email) {
                $user = User::where('email', $email)->first();
            }

            // Jika belum ditemukan, cari berdasarkan NIK/no_karyawan
            if (!$user && $nik) {
                $user = User::where('no_karyawan', $nik)->first();
            }

            // User belum terdaftar
            if (!$user) {
                return redirect()
                    ->route('login')
                    ->with(
                        'error',
                        'Akun Anda belum terdaftar di aplikasi Koperasi.'
                    );
            }

            // Hubungkan akun lokal dengan akun Gate
            $user->update([
                'sso_id' => $ssoId,
                'auth_provider' => 'sso',
                'name' => $name ?: $user->name,
                'email' => $email ?: $user->email,
                'email_verified_at' => $user->email_verified_at ?? now(),
            ]);

            // Login menggunakan guard web yang sama
            Auth::guard('web')->login($user);

            // Regenerate session
            request()->session()->regenerate();

            return redirect()->route('dashboard');

        } catch (\Throwable $e) {

            report($e);

            return redirect()
                ->route('login')
                ->with(
                    'error',
                    'Login SSO gagal. Silakan coba lagi.'
                );
        }
    }
}