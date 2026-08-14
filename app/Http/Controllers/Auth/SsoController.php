<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Anggota;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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

            $ssoId = $raw['sub'] ?? $raw['id'] ?? null;
            $email = $raw['email'] ?? null;
            $nik   = $raw['nik'] ?? $raw['employee_id'] ?? null;
            $name  = $raw['name'] ?? null;

            // 1. Cari berdasarkan SSO ID
            $user = User::where('sso_id', $ssoId)->first();

            // 2. Kalau belum ada, cari berdasarkan email
            if (! $user && $email) {
                $user = User::where('email', $email)->first();
            }

            // 3. Kalau belum ada, cari berdasarkan no karyawan
            if (! $user && $nik) {
                $user = User::where('no_karyawan', $nik)->first();
            }

            // 4. Kalau belum ada, cari anggota
            if (! $user && $nik) {
                $anggota = Anggota::where('no_karyawan', $nik)->first();

                if (! $anggota) {
                    return redirect()->route('login')
                        ->with(
                            'error',
                            'Akun Anda belum terdaftar sebagai anggota koperasi.'
                        );
                }

                $user = User::create([
                    'name' => $name ?? $anggota->nama,
                    'email' => $email,
                    'no_karyawan' => $nik,
                    'password' => Hash::make(str()->random(32)),
                    'harus_ganti_password' => false,
                ]);

                $user->assignRole('anggota');

                if (! $anggota->user_id) {
                    $anggota->update([
                        'user_id' => $user->id
                    ]);
                }
            }

            // 5. Hubungkan akun lokal dengan akun SSO
            $user->update([
                'sso_id' => $ssoId,
                'auth_provider' => 'sso',
                'email' => $email ?? $user->email,
                'email_verified_at' => $user->email_verified_at ?? now(),
            ]);

            // 6. Login ke aplikasi Koperasi
            Auth::guard('web')->login($user);

            request()->session()->regenerate();

            return redirect()->route('dashboard');

        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('login')
                ->with('error', 'Login SSO gagal. Silakan coba lagi.');
        }
    }

    public function logout()
{
    Auth::guard('web')->logout();

    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->away('https://gate.appdutamall.com/dashboard');
}
}