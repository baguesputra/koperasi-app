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
            return redirect()->route('portal.aktivasi.create');
        }

        return $next($request);
    }
}
