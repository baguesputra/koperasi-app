<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Anggota;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SsoController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('perusahaan')->redirect();
    }

    public function callback()
    {
        $ssoUser = Socialite::driver('perusahaan')->user();
        $noKaryawan = $ssoUser->getNickname();

        if (! $noKaryawan) {
            return redirect()->route('login')->withErrors([
                'sso' => 'Gagal memverifikasi identitas dari sistem perusahaan.',
            ]);
        }

        $anggota = Anggota::where('no_karyawan', $noKaryawan)->first();

        if (! $anggota) {
            return redirect()->route('login')->withErrors([
                'sso' => 'Anda belum terdaftar sebagai anggota koperasi. Silakan hubungi Admin.',
            ]);
        }

        $user = User::updateOrCreate(
            ['no_karyawan' => $noKaryawan],
            [
                'name' => $ssoUser->getName(),
                'email' => $ssoUser->getEmail(),
                'sso_id' => $ssoUser->getId(),
                'auth_provider' => 'sso',
            ]
        );

        if (! $anggota->user_id) {
            $anggota->update(['user_id' => $user->id]);
        }

        Auth::login($user);

        return redirect()->route('dashboard');
    }
}