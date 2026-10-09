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

        if ($user && $user->anggota && $user->anggota->status === 'nonaktif' && ! $request->routeIs('portal.aktivasi.*')) {
            return redirect()->route('portal.aktivasi.landing');
        }

        // Block pengurus (admin, bendahara, ketua_koperasi) from transactonal portal routes
        if ($user && $user->hasAnyRole(['admin', 'bendahara', 'ketua_koperasi'])) {
            $transactonalRoutes = [
                'portal.pinjaman.create',
                'portal.pinjaman.store',
                'portal.pinjaman.cek-nominal',
                'portal.pinjaman.simulasi',
                'portal.pengajuan-limit.create',
                'portal.pengajuan-limit.store',
                'portal.percepatan.create',
                'portal.percepatan.store',
                'portal.percepatan.preview',
                'portal.klaim-dana-sosial.create',
                'portal.klaim-dana-sosial.store',
            ];

            if ($request->routeIs($transactonalRoutes)) {
                return redirect()->route('dashboard')
                    ->with('status', 'Pengurus tidak memiliki akses menu transaksional anggota. Silakan gunakan dashboard utama.');
            }
        }

        return $next($request);
    }
}
