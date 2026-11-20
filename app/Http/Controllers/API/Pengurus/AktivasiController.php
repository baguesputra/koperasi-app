<?php

namespace App\Http\Controllers\API\Pengurus;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Ketua\AktivasiController as WebAktivasiController;
use App\Models\PengajuanAktivasi;
use App\Models\SettingLimitPinjaman;
use App\Models\SettingSimpanan;
use App\Services\Anggota\PengajuanAktivasiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class AktivasiController extends Controller
{
    public function __construct(private PengajuanAktivasiService $service) {}

    private function tolakTanpaIzin(Request $request): void
    {
        if (! $request->user()->can('aktivasi.approve-ketua')) {
            abort(response()->json([
                'message' => 'Anda tidak memiliki akses ke antrean aktivasi anggota.',
            ], 403));
        }
    }

    /**
     * Daftar antrean aktivasi keanggotaan & riwayat keputusan Ketua.
     */
    public function index(Request $request): JsonResponse
    {
        $this->tolakTanpaIzin($request);

        $format = WebAktivasiController::formatItem();

        $menunggu = PengajuanAktivasi::with(['anggota.user'])
            ->where('status', 'diajukan')
            ->latest('tanggal_pengajuan')
            ->latest('id')
            ->get()
            ->map($format)
            ->values();

        $riwayat = PengajuanAktivasi::with(['anggota.user'])
            ->whereIn('status', ['disetujui', 'ditolak'])
            ->latest('updated_at')
            ->latest('id')
            ->take(20)
            ->get()
            ->map($format)
            ->values();

        $ketentuanFinansial = [
            'simpanan_pokok' => (float) (SettingSimpanan::where('jenis', 'pokok')->value('nominal') ?? 50_000),
            'simpanan_wajib' => (float) (SettingSimpanan::where('jenis', 'wajib')->value('nominal') ?? 45_000),
            'dana_sosial' => (float) (SettingSimpanan::where('jenis', 'dana_sosial')->value('nominal') ?? 5_000),
            'limit_awal' => (float) (SettingLimitPinjaman::orderBy('min_bulan')->value('limit_maksimal') ?? 1_000_000),
        ];

        return response()->json([
            'menunggu' => $menunggu,
            'riwayat' => $riwayat,
            'total_menunggu' => $menunggu->count(),
            'ketentuan_finansial' => $ketentuanFinansial,
        ]);
    }

    /**
     * Setujui pengajuan aktivasi keanggotaan oleh Ketua.
     */
    public function approve(Request $request, PengajuanAktivasi $aktivasi): JsonResponse
    {
        $this->tolakTanpaIzin($request);

        $request->validate([
            'catatan' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        try {
            $this->service->setujui($aktivasi, $request->catatan, (int) $request->user()->id);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $aktivasi->refresh()->loadMissing(['anggota.user']);

        return response()->json([
            'message' => 'Aktivasi keanggotaan disetujui.',
            'data' => (WebAktivasiController::formatItem())($aktivasi),
        ]);
    }

    /**
     * Tolak pengajuan aktivasi keanggotaan oleh Ketua.
     */
    public function reject(Request $request, PengajuanAktivasi $aktivasi): JsonResponse
    {
        $this->tolakTanpaIzin($request);

        $request->validate([
            'catatan' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        try {
            $this->service->tolak($aktivasi, $request->catatan, (int) $request->user()->id);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $aktivasi->refresh()->loadMissing(['anggota.user']);

        return response()->json([
            'message' => 'Pengajuan aktivasi ditolak.',
            'data' => (WebAktivasiController::formatItem())($aktivasi),
        ]);
    }
}
