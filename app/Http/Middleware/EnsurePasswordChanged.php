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

        // Catatan: Wajib ganti password lokal dinonaktifkan karena otentikasi akun dikelola oleh GATE (SSO SAML2).
        // Password lokal digenerate random 32 karakter dan tidak digunakan untuk login web.

        return $next($request);
    }
}
