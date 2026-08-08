<?php

namespace App\Http\Controllers\Bendahara;

use App\Http\Controllers\Controller;
use App\Models\Simpanan;
use App\Services\Simpanan\KonfirmasiSimpananService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SimpananController extends Controller
{
    public function __construct(private KonfirmasiSimpananService $konfirmasi) {}

    public function index(Request $request): Response
    {
        $bulan = $request->input('bulan', now()->format('Y-m'));

        $belumSimpanan = $this->konfirmasi->anggotaBelumSimpananWajib($bulan)
            ->map(fn ($a) => [
                'id' => $a->id,
                'nama' => $a->nama,
                'no_anggota' => $a->no_anggota,
                'cabang' => $a->cabang,
            ]);

        $totalDanaSosialTerkumpul = Simpanan::where('jenis', 'dana_sosial')->sum('jumlah');

        return Inertia::render('Bendahara/Simpanan/Index', [
            'bulan' => $bulan,
            'belumSimpanan' => $belumSimpanan,
            'totalDanaSosialTerkumpul' => (float) $totalDanaSosialTerkumpul,
        ]);
    }

    public function konfirmasi(Request $request)
    {
        $request->validate([
            'anggota_ids' => ['required', 'array', 'min:1'],
            'anggota_ids.*' => ['integer', 'exists:anggota,id'],
            'bulan_periode' => ['required', 'string'],
        ]);

        $jumlah = $this->konfirmasi->konfirmasiMassal(
            $request->anggota_ids,
            $request->bulan_periode,
            auth()->id()
        );

        return back()->with('status', "Simpanan wajib {$jumlah} anggota berhasil dikonfirmasi.");
    }
}