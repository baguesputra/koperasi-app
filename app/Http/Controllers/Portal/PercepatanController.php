<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\Pinjaman\PercepatanPinjamanService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class PercepatanController extends Controller
{
    public function __construct(private PercepatanPinjamanService $service) {}

    public function create(): Response
    {
        $anggota = auth()->user()->anggota;
        $pinjaman = $anggota->pinjamanAktif();

        return Inertia::render('Portal/Percepatan/Create', [
            'pinjaman' => $pinjaman ? [
                'id' => $pinjaman->id,
                'nominal' => (float) $pinjaman->nominal,
                'tenor_bulan' => $pinjaman->tenor_bulan,
                'sisa_angsuran' => $pinjaman->sisaAngsuran(),
                'sudah_pakai_percepatan' => $pinjaman->sudah_pakai_percepatan,
            ] : null,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'tipe' => ['required', 'in:percepat,perpanjang,lunas_total'],
            'tenor_baru' => ['required_if:tipe,percepat,perpanjang', 'nullable', 'integer', 'min:1'],
            'keterangan' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        $pinjaman = auth()->user()->anggota->pinjamanAktif();

        if (! $pinjaman) {
            return back()->withErrors(['tipe' => 'Anda tidak memiliki pinjaman aktif.']);
        }

        try {
            $this->service->ajukan($pinjaman, $request->tipe, $request->tenor_baru, $request->keterangan);
        } catch (RuntimeException $e) {
            return back()->withErrors(['tipe' => $e->getMessage()]);
        }

        return redirect()->route('portal.dashboard')->with('status', 'Pengajuan berhasil dikirim.');
    }

    public function preview(Request $request)
    {
        $request->validate([
            'tipe' => ['required', 'in:percepat,perpanjang,lunas_total'],
            'tenor_baru' => ['required_if:tipe,percepat,perpanjang', 'nullable', 'integer', 'min:1'],
        ]);

        $pinjaman = auth()->user()->anggota->pinjamanAktif();

        if (! $pinjaman) {
            return response()->json(['message' => 'Tidak ada pinjaman aktif.'], 422);
        }

        return response()->json($this->service->preview($pinjaman, $request->tipe, $request->tenor_baru));
    }
}
