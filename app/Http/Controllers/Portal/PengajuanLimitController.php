<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\PengajuanLimit;
use App\Services\Pinjaman\EligibilitasPinjamanService;
use App\Services\Pinjaman\PengajuanLimitService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class PengajuanLimitController extends Controller
{
    public function __construct(
        private PengajuanLimitService $service,
        private EligibilitasPinjamanService $eligibilitas,
    ) {}

    public function create(): Response
    {
        $anggota = auth()->user()->anggota;

        $riwayat = PengajuanLimit::where('anggota_id', $anggota->id)
            ->latest('tanggal_pengajuan')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'limit_diminta' => (float) $p->limit_diminta,
                'status' => $p->status,
                'catatan_ketua' => $p->catatan_ketua,
                'tanggal_pengajuan' => $p->tanggal_pengajuan->format('d M Y'),
            ]);

        return Inertia::render('Portal/PengajuanLimit/Create', [
            'limitSaatIni' => $this->eligibilitas->limitMaksimal($anggota),
            'riwayat' => $riwayat,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'limit_diminta' => ['required', 'numeric', 'min:1'],
            'keterangan' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        $anggota = auth()->user()->anggota;

        try {
            $this->service->ajukan(
                $anggota,
                (float) $request->limit_diminta,
                $request->keterangan
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['limit_diminta' => $e->getMessage()]);
        }

        return redirect()
            ->route('portal.dashboard')
            ->with('limit_terkirim', [
                'diminta' => (float) $request->limit_diminta,
                'limit_saat_ini' => (float) $this->eligibilitas->limitMaksimal($anggota),
            ]);
    }
}
