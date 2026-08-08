<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\Pinjaman\EligibilitasPinjamanService;
use App\Services\Pinjaman\PengajuanPinjamanService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class PinjamanController extends Controller
{
    public function __construct(
        private EligibilitasPinjamanService $eligibilitas,
        private PengajuanPinjamanService $pengajuan,
    ) {}

    /**
     * Halaman wizard pengajuan pinjaman.
     */
    public function create(): Response
    {
        $anggota = auth()->user()->anggota;
        $cek = $this->eligibilitas->cek($anggota);

        return Inertia::render('Portal/Pinjaman/Create', [
            'bisaAjukan' => $cek['boleh'],
            'alasanTidakBisa' => $cek['alasan'],
            'limitMaksimal' => $this->eligibilitas->limitMaksimal($anggota),
        ]);
    }

    /**
     * Step 1 -> 2: cek nominal valid, kembalikan tenor maksimal yang diizinkan.
     */
    public function cekNominal(Request $request)
    {
        $request->validate(['nominal' => ['required', 'numeric', 'min:1']]);

        $anggota = auth()->user()->anggota;
        $nominal = (float) $request->nominal;
        $limitMaksimal = $this->eligibilitas->limitMaksimal($anggota);

        if ($nominal > $limitMaksimal) {
            return response()->json([
                'valid' => false,
                'pesan' => 'Nominal melebihi limit maksimal Anda: Rp ' . number_format($limitMaksimal, 0, ',', '.'),
            ], 422);
        }

        $tenorMaksimal = $this->eligibilitas->tenorMaksimal($nominal);

        if (! $tenorMaksimal) {
            return response()->json([
                'valid' => false,
                'pesan' => 'Nominal pinjaman tidak sesuai dengan ketentuan yang berlaku.',
            ], 422);
        }

        return response()->json([
            'valid' => true,
            'tenor_maksimal' => $tenorMaksimal,
        ]);
    }

    /**
     * Step 2 -> 3: hitung simulasi jadwal cicilan.
     */
    public function simulasi(Request $request)
    {
        $request->validate([
            'nominal' => ['required', 'numeric', 'min:1'],
            'tenor_bulan' => ['required', 'integer', 'min:1'],
        ]);

        $jadwal = $this->pengajuan->preview((float) $request->nominal, (int) $request->tenor_bulan);

        return response()->json([
            'jadwal' => $jadwal,
            'total_dibayar' => array_sum(array_column($jadwal, 'total_bayar')),
        ]);
    }

    /**
     * Step 3: submit final pengajuan.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nominal' => ['required', 'numeric', 'min:1'],
            'tenor_bulan' => ['required', 'integer', 'min:1'],
        ]);

        $anggota = auth()->user()->anggota;

        try {
            $this->pengajuan->ajukan($anggota, (float) $request->nominal, (int) $request->tenor_bulan);
        } catch (RuntimeException $e) {
            return back()->withErrors(['pengajuan' => $e->getMessage()]);
        }

        return redirect()->route('portal.pinjaman.berhasil');
    }

    public function berhasil(): Response
    {
        return Inertia::render('Portal/Pinjaman/Berhasil');
    }
}