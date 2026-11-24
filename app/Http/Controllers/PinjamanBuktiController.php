<?php

namespace App\Http\Controllers;

use App\Models\Pinjaman;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PinjamanBuktiController extends Controller
{
    public function show(Request $request, Pinjaman $pinjaman): BinaryFileResponse
    {
        $user = $request->user();
        if (! $user) {
            abort(403);
        }

        $pemilik = $user->anggota && (int) $user->anggota->id === (int) $pinjaman->anggota_id;
        $pengurus = $user->can('pinjaman.lihat') || $user->can('pinjaman.cairkan');

        if (! $pemilik && ! $pengurus) {
            abort(403, 'Anda tidak berhak melihat bukti transfer pencairan ini.');
        }

        $path = $pinjaman->bukti_pencairan_path;
        if (! $path || ! Storage::disk('local')->exists($path)) {
            abort(404, 'File bukti transfer tidak ditemukan.');
        }

        $fullPath = Storage::disk('local')->path($path);
        $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', (string) ($pinjaman->bukti_pencairan_nama_asli ?: basename($path)));

        return response()->file($fullPath, [
            'Content-Type' => $pinjaman->bukti_pencairan_mime ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.$safeName.'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
