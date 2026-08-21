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

        if ($user && $user->harus_ganti_password && ! $request->routeIs('password.wajib-ganti', 'password.wajib-ganti.update', 'logout')) {
            return redirect()->route('password.wajib-ganti');
        }

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

        return $next($request);
    }
}
