<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\KeputusanPinjamanRequest;
use App\Models\Pinjaman;
use App\Services\Pinjaman\FormatPinjamanService;
use App\Services\Pinjaman\PersetujuanPinjamanService;
use App\Services\Pinjaman\RingkasanPinjamanService;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class PinjamanController extends Controller
{
    public function __construct(
        private PersetujuanPinjamanService $persetujuan,
        private FormatPinjamanService $format,
        private RingkasanPinjamanService $ringkasan,
    ) {}

    public function index(): Response
    {
        $menungguTinjauan = Pinjaman::with('anggota')
            ->where('status', 'diajukan')
            ->latest('tanggal_pengajuan')
            ->get()
            ->map(fn ($p) => $this->format->format($p));

        $riwayat = Pinjaman::with('anggota')
            ->whereIn('status', ['approved_admin', 'approved_bendahara', 'aktif', 'lunas', 'ditolak'])
            ->whereNotNull('catatan_admin')
            ->latest('updated_at')
            ->take(20)
            ->get()
            ->map(fn ($p) => $this->format->format($p));

        return Inertia::render('Admin/Pinjaman/Index', [
            'menungguTinjauan' => $menungguTinjauan,
            'riwayat' => $riwayat,
            'ringkasanKas' => $this->ringkasan->ringkasan(),
        ]);
    }

    public function approve(KeputusanPinjamanRequest $request, Pinjaman $pinjaman)
    {
        try {
            $this->persetujuan->approveAdmin($pinjaman, $request->catatan);
        } catch (RuntimeException $e) {
            return back()->withErrors(['keputusan' => $e->getMessage()]);
        }

        return redirect()->route('admin.pinjaman.index')
            ->with('status', 'Pengajuan pinjaman diteruskan ke Bendahara.');
    }

    public function reject(KeputusanPinjamanRequest $request, Pinjaman $pinjaman)
    {
        try {
            $this->persetujuan->rejectAdmin($pinjaman, $request->catatan);
        } catch (RuntimeException $e) {
            return back()->withErrors(['keputusan' => $e->getMessage()]);
        }

        return redirect()->route('admin.pinjaman.index')
            ->with('status', 'Pengajuan pinjaman ditolak.');
    }
}
