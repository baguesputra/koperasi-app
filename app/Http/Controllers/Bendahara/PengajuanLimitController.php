<?php

namespace App\Http\Controllers\Bendahara;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Ketua\PengajuanLimitController as KetuaPengajuanLimitController;
use App\Http\Requests\KeputusanLimitRequest;
use App\Models\PengajuanLimit;
use App\Services\Pinjaman\PengajuanLimitService;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class PengajuanLimitController extends Controller
{
    public function __construct(private PengajuanLimitService $service) {}

    public function index(): Response
    {
        $menungguRaw = PengajuanLimit::with('anggota')
            ->where('status', 'diajukan')
            ->latest('tanggal_pengajuan')
            ->get();

        $riwayatRaw = PengajuanLimit::with('anggota')
            ->whereIn('status', ['approved_bendahara', 'disetujui', 'ditolak'])
            ->whereNotNull('catatan_bendahara')
            ->latest('updated_at')
            ->take(20)
            ->get();

        // 2 query preload untuk semua baris (ganti 2 query + agregat per baris)
        $cache = KetuaPengajuanLimitController::preloadPinjaman($menungguRaw->concat($riwayatRaw));

        return Inertia::render('Bendahara/PengajuanLimit/Index', [
            'menunggu' => $menungguRaw->map(KetuaPengajuanLimitController::formatItem($cache)),
            'riwayat' => $riwayatRaw->map(KetuaPengajuanLimitController::formatItem($cache)),
        ]);
    }

    public function show(PengajuanLimit $pengajuanLimit): Response
    {
        $pengajuanLimit->load('anggota');

        return Inertia::render('Bendahara/PengajuanLimit/Show', [
            'pengajuan' => KetuaPengajuanLimitController::formatItem()($pengajuanLimit),
        ]);
    }

    public function approve(KeputusanLimitRequest $request, PengajuanLimit $pengajuanLimit)
    {
        try {
            $this->service->approveBendahara($pengajuanLimit, (float) $request->limit_disetujui, $request->catatan);
        } catch (RuntimeException $e) {
            return back()->withErrors(['keputusan' => $e->getMessage()]);
        }

        return redirect()->route('bendahara.pengajuan-limit.index')
            ->with('status', 'Pengajuan limit disetujui dan diteruskan ke Ketua.');
    }

    public function reject(KeputusanLimitRequest $request, PengajuanLimit $pengajuanLimit)
    {
        try {
            $this->service->rejectBendahara($pengajuanLimit, $request->catatan);
        } catch (RuntimeException $e) {
            return back()->withErrors(['keputusan' => $e->getMessage()]);
        }

        return redirect()->route('bendahara.pengajuan-limit.index')
            ->with('status', 'Pengajuan limit ditolak.');
    }
}
