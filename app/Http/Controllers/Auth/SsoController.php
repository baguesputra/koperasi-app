<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Http;

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

            $ssoId = $raw['id'] ?? null;
            $email = $raw['email'] ?? null;
            $nik = $raw['nik'] ?? null;
            $name = $raw['name'] ?? null;

            // Cari user lokal berdasarkan SSO ID
            $user = User::where('sso_id', $ssoId)->first();

            // Jika belum terhubung, cari berdasarkan email
            if (!$user && $email) {
                $user = User::where('email', $email)->first();
            }

            // Jika belum ditemukan, cari berdasarkan NIK
            if (!$user && $nik) {
                $user = User::where('no_karyawan', $nik)->first();
            }

            if (!$user) {
                return redirect()
                    ->route('login')
                    ->with(
                        'error',
                        'Akun Anda belum terdaftar di aplikasi Koperasi.'
                    );
            }

            // Hubungkan akun lokal dengan akun SSO
            $user->update([
                'sso_id' => $ssoId,
                'auth_provider' => 'sso',
                'email_verified_at' => $user->email_verified_at ?? now(),
            ]);

            // Login menggunakan guard web Laravel
            Auth::guard('web')->login($user);

            // Regenerate session
            request()->session()->regenerate();

            // Masuk dashboard
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

  public function logout()
{
    return view('auth.sso-logout');
}

public function logoutCallback()
{
    return redirect()->route('login');
}
}