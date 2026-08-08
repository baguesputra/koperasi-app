<?php

namespace App\Http\Controllers;

use App\Models\Anggota;
use App\Models\Pinjaman;
use App\Models\Simpanan;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response|\Illuminate\Http\RedirectResponse
    {
        if (auth()->user()->hasRole('anggota')) {
        return redirect()->route('portal.dashboard');
    }
        $aktivitasTerbaru = Pinjaman::with('anggota')
            ->latest('tanggal_pengajuan')
            ->take(5)
            ->get()
            ->map(fn ($pinjaman) => [
                'nama' => $pinjaman->anggota->nama,
                'nominal' => (float) $pinjaman->nominal,
                'status' => $pinjaman->status,
                'tanggal' => $pinjaman->tanggal_pengajuan->format('d M Y'),
            ]);

        return Inertia::render('Dashboard', [
            'stats' => [
                'total_anggota_aktif' => Anggota::where('status', 'aktif')->count(),
                'total_simpanan' => (float) Simpanan::sum('jumlah'),
                'pinjaman_outstanding' => (float) Pinjaman::where('status', 'aktif')->sum('nominal'),
                'menunggu_approval' => Pinjaman::whereIn('status', [
                    'diajukan', 'ditinjau_bendahara', 'approved_bendahara',
                ])->count(),
            ],
            'aktivitasTerbaru' => $aktivitasTerbaru,
        ]);
    }
}