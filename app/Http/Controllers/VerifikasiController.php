<?php

namespace App\Http\Controllers;

use App\Models\Anggota;
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

    public function resign(Request $request, Anggota $anggota)
    {
        abort_unless(in_array($anggota->status, ['resign', 'resign_menunggu'], true), 404);

        $settlement = $anggota->resigned_settlement_json ?? [];

        return view('verifikasi.resign', [
            'anggota' => $anggota,
            'settlement' => $settlement,
            'kedaluwarsa' => $request->query('expires')
                ? now()->createFromTimestamp((int) $request->query('expires'))
                : $anggota->tanggal_resign,
        ]);
    }
}
