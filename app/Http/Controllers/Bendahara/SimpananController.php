<?php

namespace App\Http\Controllers\Bendahara;

use App\Http\Controllers\Controller;
use App\Models\Anggota;
use App\Models\SettingSimpanan;
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

        $cabangAktif = $request->string('cabang');

        $semuaBelumSimpanan = $this->konfirmasi->anggotaBelumSimpananWajib($bulan)
            ->map(fn ($a) => [
                'id' => $a->id,
                'nama' => $a->nama,
                'no_anggota' => $a->no_anggota,
                'cabang' => $a->cabang,
            ]);

        $belumSimpanan = $cabangAktif->isNotEmpty()
            ? $semuaBelumSimpanan->where('cabang', $cabangAktif)->values()
            : $semuaBelumSimpanan;

        $nominalWajib = (float) (SettingSimpanan::where('jenis', 'wajib')->value('nominal') ?? 0);
        $nominalDanaSosial = (float) (SettingSimpanan::where('jenis', 'dana_sosial')->value('nominal') ?? 0);
        $nominalPerAnggota = $nominalWajib + $nominalDanaSosial;

        $ringkasanCabang = $semuaBelumSimpanan
            ->groupBy('cabang')
            ->map(fn ($anggota) => [
                'jumlah_anggota' => $anggota->count(),
                'nominal' => (float) ($anggota->count() * $nominalPerAnggota),
            ]);

        $daftarCabang = Anggota::query()->whereNotNull('cabang')->distinct()->orderBy('cabang')->pluck('cabang');

        $totalDanaSosialTerkumpul = Simpanan::where('jenis', 'dana_sosial')->sum('jumlah');

        return Inertia::render('Bendahara/Simpanan/Index', [
            'bulan' => $bulan,
            'belumSimpanan' => $belumSimpanan,
            'cabangAktif' => $cabangAktif->value(),
            'daftarCabang' => $daftarCabang,
            'ringkasanCabang' => $ringkasanCabang,
            'nominalPerAnggota' => $nominalPerAnggota,
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
