<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
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

            $ssoId = $ssoUser->id;
            $email = $ssoUser->email;
            $nik = $ssoUser->nik;
            $name = $ssoUser->name;

            \Log::info('SSO Callback Debug', [
                'sso_id' => $ssoId,
                'email' => $email,
                'nik' => $nik,
                'name' => $name,
            ]);

            if (! $email) {
                AuditLog::catat('sso_login_failed', 'email_missing', [
                    'sso_id' => $ssoId,
                ]);

                return redirect()->route('login')
                    ->with('error', 'Email tidak tersedia dari SSO. Hubungi admin.');
            }

            $user = User::where('sso_id', $ssoId)->first();

            if (! $user && $email) {
                $user = User::where('email', $email)->first();
            }

            if (! $user && $nik) {
                $user = User::where('no_karyawan', $nik)->first();
            }

            if (! $user) {
                AuditLog::catat('sso_login_failed', 'user_not_found', [
                    'sso_id' => $ssoId,
                    'email' => $email,
                    'nik' => $nik,
                ]);

                return redirect()->route('login')
                    ->with('error', 'Akun SSO tidak terdaftar di sistem koperasi. Hubungi admin.');
            }

            if (! $user->anggota) {
                AuditLog::catat('sso_login_failed', 'no_anggota_relation', [
                    'sso_id' => $ssoId,
                    'user_id' => $user->id,
                ], null, $user->id);

                return redirect()->route('login')
                    ->with('error', 'Data anda belum sesuai. Hubungi admin.');
            }

            if ($user->email !== $email) {
                AuditLog::catat('sso_login_failed', 'email_mismatch', [
                    'sso_id' => $ssoId,
                    'user_id' => $user->id,
                    'local_email' => $user->email,
                    'sso_email' => $email,
                ], null, $user->id);

                return redirect()->route('login')
                    ->with('error', 'Data anda belum sesuai. Hubungi admin.');
            }

            $user->update([
                'sso_id' => $ssoId,
                'auth_provider' => 'sso',
                'email_verified_at' => $user->email_verified_at ?? now(),
            ]);

            if ($user->status === 'nonaktif') {
                AuditLog::catat('sso_login_failed', 'inactive', [
                    'sso_id' => $ssoId,
                    'user_id' => $user->id,
                ], null, $user->id);

                return redirect()->route('login')
                    ->with('error', 'Akun Anda dinonaktifkan. Silakan hubungi pengurus koperasi.');
            }

            Auth::guard('web')->login($user);

            request()->session()->regenerate();

            AuditLog::catat('sso_login', 'success', [
                'sso_id' => $ssoId,
            ], null, $user->id);

            return redirect()->route('dashboard');

        } catch (\Throwable $e) {
            report($e);

            AuditLog::catat('sso_login_failed', 'exception', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('login')
                ->with('error', 'Login SSO gagal. Silakan coba lagi.');
        }
    }

    public function logout()
    {
        Auth::guard('web')->logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        $logoutUrl = config('services.sso.logout_url') ?? route('login');

        return redirect()->away($logoutUrl);
    }
}
