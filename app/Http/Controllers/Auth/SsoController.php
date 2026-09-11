<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Laravel\Socialite\Facades\Socialite;

class SsoController extends Controller
{
    public function redirect()
    {
        $tujuan = Socialite::driver('perusahaan')
            ->stateless()
            ->redirect()
            ->getTargetUrl();

        return Inertia::location($tujuan);
    }

    public function callback()
    {
        try {
            $ssoUser = Socialite::driver('perusahaan')
                ->stateless()
                ->user();

            $ssoId = $ssoUser->id;
            $email = $ssoUser->email ?? $ssoId;
            $name = $ssoUser->name;

            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $email = null;
            }

            Log::info('SSO Callback Debug', [
                'sso_id' => $ssoId,
                'email' => $email,
                'name' => $name,
            ]);

            if (! $email) {
                AuditLog::catat('sso_login_failed', 'email_missing', [
                    'sso_id' => $ssoId,
                ]);

                return redirect()->route('sso.gagal')
                    ->with('error', 'Email tidak tersedia dari SSO. Hubungi admin koperasi untuk mendaftarkan email Anda.');
            }

            $user = User::where('sso_id', $ssoId)->first();

            if (! $user && $email) {
                $user = User::where('email', $email)->first();
            }

            if (! $user) {
                AuditLog::catat('sso_login_failed', 'user_not_found', [
                    'sso_id' => $ssoId,
                    'email' => $email,
                ]);

                return redirect()->route('sso.gagal')
                    ->with('error', 'Akun SSO tidak terdaftar di sistem koperasi. Hubungi admin koperasi untuk mendaftarkan Anda sebagai anggota.');
            }

            if ($user->hasRole('anggota') && ! $user->anggota) {
                AuditLog::catat('sso_login_failed', 'no_anggota_relation', [
                    'sso_id' => $ssoId,
                    'user_id' => $user->id,
                ], null, $user->id);

                return redirect()->route('sso.gagal')
                    ->with('error', 'Anda belum terdaftar sebagai anggota koperasi. Hubungi admin koperasi untuk mendaftarkan keanggotaan Anda.');
            }

            if ($user->email !== $email) {
                AuditLog::catat('sso_login_failed', 'email_mismatch', [
                    'sso_id' => $ssoId,
                    'user_id' => $user->id,
                    'local_email' => $user->email,
                    'sso_email' => $email,
                ], null, $user->id);

                return redirect()->route('sso.gagal')
                    ->with('error', 'Email SSO tidak cocok dengan data lokal. Hubungi admin koperasi untuk memperbarui data Anda.');
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

                return redirect()->route('sso.gagal')
                    ->with('error', 'Akun Anda dinonaktifkan. Silakan hubungi pengurus koperasi untuk mengaktifkan kembali.');
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

            return redirect()->route('sso.gagal')
                ->with('error', 'Login SSO gagal. Silakan coba lagi atau hubungi admin koperasi jika berulang.');
        }
    }

    public function logout()
    {
        $user = Auth::guard('web')->user();
        if ($user && $user->sso_id) {
            try {
                // Initiate SAML logout request
                return Socialite::driver('perusahaan')
                    ->logoutRequest($user->sso_id);
            } catch (\Exception $e) {
                // If SAML logout fails, fall back to clearing session and redirecting to IdP SLO URL
                report($e);
            }
        }

        // Fallback: clear session and redirect to login or IdP SLO URL
        Auth::guard('web')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        $sloUrl = env('SAML_IDP_SLO_URL', route('login'));

        return redirect()->away($sloUrl);
    }

    public function slo(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->has('SAMLRequest') || $request->has('SAMLResponse')) {
            try {
                return Socialite::driver('perusahaan')
                    ->stateless()
                    ->logoutResponse();
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return redirect()->route('login');
    }

    public function metadata()
    {
        return Socialite::driver('perusahaan')
            ->getServiceProviderMetadata();
    }
}
