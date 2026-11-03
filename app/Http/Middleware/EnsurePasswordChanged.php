<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Safety net: akun lama yang masih bertanda wajib ganti password diarahkan ke profil
        // sampai password diganti. Akun baru SSO-only tidak memakai flag ini.
        // Blokir anggota yang sudah di-resign dari mengakses aplikasi.
        // Hanya cek untuk user yang punya relasi anggota (bukan admin/bendahara/ketua).
        if ($user && $user->anggota && $user->anggota->status === 'resign') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'no_karyawan' => 'Akun Anda telah di-resign dari koperasi. Hubungi admin untuk informasi lebih lanjut.',
            ]);
        }

        // ponytail: tahan model parsial (tanpa kolom) — anggap tidak wajib ganti.
        $raw = $user ? $user->getAttributes() : [];
        $wajibGanti = (bool) ($raw['harus_ganti_password'] ?? false);

        if ($wajibGanti
            && ! $request->routeIs('profile.*', 'logout', 'logout.get')
            && ! $request->is('profile*', 'logout')
        ) {
            return redirect()->route('profile.edit')->withErrors([
                'password' => 'Demi keamanan, ganti password awal Anda sebelum melanjutkan.',
            ]);
        }

        return $next($request);
    }
}
