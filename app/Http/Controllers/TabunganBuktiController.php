<?php

namespace App\Http\Controllers;

use App\Models\PengajuanTransaksiTabungan;
use App\Models\TabunganTransaksi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TabunganBuktiController extends Controller
{
    public function pengajuan(Request $request, PengajuanTransaksiTabungan $pengajuan, string $tipe = 'setor'): BinaryFileResponse
    {
        $this->otorisasiPemilikAtauPengurus($request, $pengajuan->anggota_id);

        $path = $tipe === 'pencairan' ? $pengajuan->bukti_pencairan_path : $pengajuan->bukti_path;
        $nama = $tipe === 'pencairan' ? $pengajuan->bukti_pencairan_nama_asli : $pengajuan->bukti_nama_asli;
        $mime = $tipe === 'pencairan' ? $pengajuan->bukti_pencairan_mime : $pengajuan->bukti_mime;

        return $this->kirimFilePrivat($path, $nama, $mime);
    }

    public function transaksi(Request $request, TabunganTransaksi $transaksi): BinaryFileResponse
    {
        $this->otorisasiPemilikAtauPengurus($request, $transaksi->anggota_id);

        return $this->kirimFilePrivat($transaksi->bukti_path, $transaksi->bukti_nama_asli, $transaksi->bukti_mime);
    }

    private function otorisasiPemilikAtauPengurus(Request $request, int $pemilikAnggotaId): void
    {
        $user = $request->user();
        if (! $user) {
            abort(403);
        }

        if ($user->hasAnyPermission([
            'tabungan.lihat',
            'tabungan.kelola',
            'tabungan.tinjau-admin',
            'tabungan.tinjau-bendahara',
            'tabungan.approve-ketua',
        ])) {
            return;
        }

        if ($user->anggota && (int) $user->anggota->id === $pemilikAnggotaId) {
            return;
        }

        abort(403, 'Anda tidak berhak melihat bukti transaksi ini.');
    }

    private function kirimFilePrivat(?string $path, ?string $namaAsli, ?string $mime): BinaryFileResponse
    {
        if (! $path || ! Storage::disk('local')->exists($path)) {
            abort(404, 'File bukti transaksi tidak ditemukan.');
        }

        $fullPath = Storage::disk('local')->path($path);
        $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', (string) ($namaAsli ?: basename($path)));

        return response()->file($fullPath, [
            'Content-Type' => $mime ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.$safeName.'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
