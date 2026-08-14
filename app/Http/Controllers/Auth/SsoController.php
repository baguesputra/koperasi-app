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
            $nik = $raw['nik'] ?? $raw['employee_id'] ?? null;
            $name = $raw['name'] ?? null;

            if (! $nik) {
                return redirect()->route('login')
                    ->with('error', 'Data identitas dari SSO tidak lengkap. Silakan hubungi Admin.');
            }

            // 1. Cari User yang sudah pernah terhubung SSO ini
            $user = User::where('sso_id', $ssoId)->first();

            // 2. Belum ada -> cari User berdasarkan No. Karyawan
            if (! $user) {
                $user = User::where('no_karyawan', $nik)->first();
            }

            // 3. Masih belum ada -> cek apakah dia sudah terdaftar sebagai ANGGOTA
            //    (mungkin belum pernah punya akun User sama sekali)
            if (! $user) {
                $anggota = Anggota::where('no_karyawan', $nik)->first();

                if (! $anggota) {
                    return redirect()->route('login')
                        ->with('error', 'Akun Anda belum terdaftar sebagai anggota koperasi. Silakan hubungi Admin.');
                }

                // Buat akun User baru, hubungkan ke Anggota yang sudah ada
                $user = User::create([
                    'name' => $name ?? $anggota->nama,
                    'email' => $email,
                    'no_karyawan' => $nik,
                    'password' => Hash::make(str()->random(32)), // tidak pernah dipakai, login selalu via SSO
                    'harus_ganti_password' => false,
                ]);
                $user->assignRole('anggota');

                if (! $anggota->user_id) {
                    $anggota->update(['user_id' => $user->id]);
                }
            }

            // Sinkronkan data SSO ke user (baik yang baru maupun yang sudah ada)
            $user->update([
                'sso_id' => $ssoId,
                'auth_provider' => 'sso',
                'email' => $email ?? $user->email,
                'email_verified_at' => $user->email_verified_at ?? now(),
            ]);

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

        return redirect()->route('login');
    }
}