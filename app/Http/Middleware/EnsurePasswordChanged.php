<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->harus_ganti_password && ! $request->routeIs('password.wajib-ganti', 'password.wajib-ganti.update', 'logout')) {
            return redirect()->route('password.wajib-ganti');
        }

        return $next($request);
    }
}