<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAnggotaAktif
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->anggota) {
            if (! $user->hasRole('anggota') && ! $request->expectsJson()) {
                return redirect()->route('dashboard')
                    ->with('status', 'Akun ini belum terhubung dengan profil anggota koperasi.');
            }

            abort(403, 'Akun Anda belum terdaftar sebagai anggota koperasi.');
        }

        if ($user && $user->anggota->status === 'nonaktif' && ! $request->routeIs('portal.aktivasi.*')) {
            return redirect()->route('portal.aktivasi.landing');
        }

        return $next($request);
    }
}
