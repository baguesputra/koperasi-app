<?php

namespace App\Http\Controllers;

use App\Models\Anggota;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AnggotaBuktiResignController extends Controller
{
    public function show(Request $request, Anggota $anggota): BinaryFileResponse
    {
        $user = $request->user();
        if (! $user) {
            abort(403);
        }

        $pemilik = $user->anggota && (int) $user->anggota->id === (int) $anggota->id;
        $pengurus = $user->can('anggota.lihat') || $user->can('anggota.resign') || $user->can('pinjaman.cairkan');

        if (! $pemilik && ! $pengurus) {
            abort(403, 'Anda tidak berhak melihat bukti transfer pencairan resign ini.');
        }

        $path = $anggota->bukti_pencairan_resign_path;
        if (! $path || ! Storage::disk('local')->exists($path)) {
            abort(404, 'File bukti transfer tidak ditemukan.');
        }

        $fullPath = Storage::disk('local')->path($path);
        $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', (string) ($anggota->bukti_pencairan_resign_nama_asli ?: basename($path)));

        return response()->file($fullPath, [
            'Content-Type' => $anggota->bukti_pencairan_resign_mime ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.$safeName.'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
