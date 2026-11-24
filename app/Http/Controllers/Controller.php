<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * Redirect kembali ke Pusat Persetujuan (beserta query tab aktif) apabila aksi dipicu
     * dari halaman /persetujuan; selain itu tetap ke route legacy agar kompatibel.
     */
    protected function redirectSetelahAksi(Request $request, string $fallbackRoute, string $status): RedirectResponse
    {
        $referer = (string) $request->headers->get('referer', '');
        if ($referer !== '') {
            $parsed = parse_url($referer);
            $sameHost = ! isset($parsed['host']) || strcasecmp($parsed['host'], $request->getHost()) === 0;
            if ($sameHost && ($parsed['path'] ?? '') === '/persetujuan') {
                $target = '/persetujuan';
                if (isset($parsed['query']) && $parsed['query'] !== '') {
                    $target .= '?'.$parsed['query'];
                }

                return redirect($target)->with('status', $status);
            }
        }

        return redirect()->route($fallbackRoute)->with('status', $status);
    }
}
