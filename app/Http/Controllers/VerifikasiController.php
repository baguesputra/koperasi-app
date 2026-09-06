<?php

namespace App\Http\Controllers;

use App\Models\Pinjaman;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class VerifikasiController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('signed'),
            new Middleware('throttle:5,1'),
        ];
    }

    public function show(Request $request, Pinjaman $pinjaman)
    {
        if ($pinjaman->isVerificationRevoked()) {
            abort(403, 'Verifikasi telah dicabut oleh administrator.');
        }

        $pinjaman->load([
            'anggota',
            'angsuran' => fn ($q) => $q->orderBy('cicilan_ke'),
            'pengaju',
        ]);

        $timeline = $pinjaman->getVerificationTimeline();

        return view('verifikasi.bukti', [
            'pinjaman' => $pinjaman,
            'timeline' => $timeline,
            'verificationUrl' => $pinjaman->verificationUrl(),
        ]);
    }
}