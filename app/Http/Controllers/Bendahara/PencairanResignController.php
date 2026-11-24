<?php

namespace App\Http\Controllers\Bendahara;

use App\Http\Controllers\Controller;
use App\Models\Anggota;
use App\Services\Anggota\ResignService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class PencairanResignController extends Controller
{
    public function __construct(
        private ResignService $resignService,
    ) {}

    public function cair(Request $request, Anggota $anggota): RedirectResponse
    {
        $data = $request->validate([
            'tanggal_pencairan' => ['required', 'date', 'before_or_equal:today'],
            'akun_kas' => ['required', 'in:bank,kas_kecil'],
            'bukti_pencairan' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ], [
            'tanggal_pencairan.before_or_equal' => 'Tanggal pencairan tidak boleh melebihi hari ini.',
            'akun_kas.in' => 'Sumber kas harus Bank atau Kas Kecil.',
            'bukti_pencairan.required' => 'Bukti transfer pencairan resign wajib dilampirkan.',
            'bukti_pencairan.mimes' => 'Bukti transfer harus berupa file JPG, PNG, WEBP, atau PDF.',
            'bukti_pencairan.max' => 'Ukuran bukti transfer maksimal 5 MB.',
        ]);

        try {
            $this->resignService->cairkanBendahara(
                anggota: $anggota,
                buktiTransfer: $request->file('bukti_pencairan'),
                tanggalPencairan: (string) $data['tanggal_pencairan'],
                akunKas: (string) $data['akun_kas'],
                catatan: $data['catatan'] ?? null,
                aktorId: $request->user()->id,
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['keputusan' => $e->getMessage()]);
        }

        return $this->redirectSetelahAksi(
            $request,
            'persetujuan.index',
            "Pengembalian resign {$anggota->nama} berhasil dicairkan, saldo kas diperbarui, dan notifikasi WA + bukti transfer telah dikirim."
        );
    }
}
