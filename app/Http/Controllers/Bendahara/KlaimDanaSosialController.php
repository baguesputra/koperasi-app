<?php

namespace App\Http\Controllers\Bendahara;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Ketua\KlaimDanaSosialController as KetuaKlaimController;
use App\Http\Requests\KeputusanKlaimRequest;
use App\Models\KlaimDanaSosial;
use App\Services\DanaSosial\KlaimDanaSosialService;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class KlaimDanaSosialController extends Controller
{
    public function __construct(private KlaimDanaSosialService $service) {}

    public function index(): Response
    {
        $menunggu = KlaimDanaSosial::with('anggota')
            ->where('status', 'diajukan')
            ->latest('tanggal_pengajuan')
            ->get()
            ->map(KetuaKlaimController::formatItem());

        $riwayat = KlaimDanaSosial::with('anggota')
            ->whereIn('status', ['approved_bendahara', 'disetujui', 'ditolak'])
            ->whereNotNull('catatan_bendahara')
            ->latest('updated_at')
            ->take(20)
            ->get()
            ->map(KetuaKlaimController::formatItem());

        return Inertia::render('Bendahara/KlaimDanaSosial/Index', [
            'menunggu' => $menunggu,
            'riwayat' => $riwayat,
        ]);
    }

    public function show(KlaimDanaSosial $klaimDanaSosial): Response
    {
        $klaimDanaSosial->load('anggota');

        return Inertia::render('Bendahara/KlaimDanaSosial/Show', [
            'klaim' => KetuaKlaimController::formatItem()($klaimDanaSosial),
        ]);
    }

    public function approve(KeputusanKlaimRequest $request, KlaimDanaSosial $klaimDanaSosial)
    {
        try {
            $this->service->approveBendahara($klaimDanaSosial, (float) $request->nominal, $request->catatan, auth()->id());
        } catch (RuntimeException $e) {
            return back()->withErrors(['keputusan' => $e->getMessage()]);
        }

        return redirect()->route('bendahara.klaim-dana-sosial.index')
            ->with('status', 'Pengajuan santunan diverifikasi dan diteruskan ke Ketua.');
    }

    public function reject(KeputusanKlaimRequest $request, KlaimDanaSosial $klaimDanaSosial)
    {
        try {
            $this->service->rejectBendahara($klaimDanaSosial, $request->catatan, auth()->id());
        } catch (RuntimeException $e) {
            return back()->withErrors(['keputusan' => $e->getMessage()]);
        }

        return redirect()->route('bendahara.klaim-dana-sosial.index')
            ->with('status', 'Pengajuan santunan ditolak.');
    }
}
