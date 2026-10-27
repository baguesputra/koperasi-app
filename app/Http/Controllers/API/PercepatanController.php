<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\PengajuanPercepatanResource;
use App\Models\PengajuanPercepatan;
use App\Services\Pinjaman\PercepatanPinjamanService;
use App\Services\Pinjaman\PerhitunganBungaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class PercepatanController extends Controller
{
    public function __construct(
        private PercepatanPinjamanService $service,
        private PerhitunganBungaService $bunga,
    ) {}

    private function pinjamanAktifOrFail(Request $request)
    {
        $anggota = $request->user()->anggota;

        if (! $anggota) {
            abort(response()->json([
                'message' => 'Anggota not found for user',
            ], 404));
        }

        return [$anggota, $anggota->pinjamanAktif()];
    }

    /**
     * Pinjaman aktif milik sendiri + riwayat pengajuan percepatan miliknya.
     *
     * @response 200 {"pinjaman": {"id": 1, "nominal": 2500000}, "data": [{"id": 1}]}
     */
    public function index(Request $request): JsonResponse
    {
        [$anggota, $pinjaman] = $this->pinjamanAktifOrFail($request);

        if (! $pinjaman) {
            return response()->json(['pinjaman' => null, 'data' => []]);
        }

        $rows = PengajuanPercepatan::where('pinjaman_id', $pinjaman->id)
            ->with(['pinjaman.anggota'])
            ->orderByDesc('tanggal_pengajuan')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'pinjaman' => [
                'id' => $pinjaman->id,
                'nominal' => (float) $pinjaman->nominal,
                'tenor_bulan' => (int) $pinjaman->tenor_bulan,
                'sisa_angsuran' => $pinjaman->sisaAngsuran(),
                'sudah_pakai_percepatan' => (bool) $pinjaman->sudah_pakai_percepatan,
            ],
            'data' => PengajuanPercepatanResource::collection($rows)->resolve(),
        ]);
    }

    /**
     * Preview simulasi percepatan. Mirror logika mock router.ts baris 451-500.
     *
     * @response 200 {"tipe": "percepat", "tenor_baru": 3, "cicilan_lama": 520000, "cicilan_baru": 0, "sisa_pokok": 2000000, "total_bayar_baru": 0, "total_bunga_baru": 0, "jadwal_baru": [], "pelunasan_sekarang": null}
     */
    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'tipe' => ['required', 'in:percepat,perpanjang,lunas_total'],
            'tenor_baru' => ['required_if:tipe,percepat,perpanjang', 'nullable', 'integer', 'min:1'],
        ]);

        [, $pinjaman] = $this->pinjamanAktifOrFail($request);

        if (! $pinjaman) {
            return response()->json(['message' => 'Anda tidak memiliki pinjaman aktif.'], 422);
        }

        $tipe = $request->tipe;
        $tenorBaru = $request->tenor_baru !== null ? (int) $request->tenor_baru : null;
        $sisaPokok = (float) $pinjaman->angsuranBelumBayar()->sum('nominal_pokok');

        $cicilanBelum = $pinjaman->angsuranBelumBayar()->orderBy('cicilan_ke')->get(['total_bayar']);
        $cicilanLama = $cicilanBelum->isNotEmpty()
            ? (int) round($cicilanBelum->sum('total_bayar') / $cicilanBelum->count())
            : 0;

        if ($tipe === 'lunas_total') {
            $pelunasan = (int) round($sisaPokok + ($sisaPokok * (float) $pinjaman->persentase_bunga) / 100);

            return response()->json([
                'tipe' => $tipe,
                'tenor_baru' => null,
                'cicilan_lama' => $cicilanLama,
                'cicilan_baru' => 0,
                'sisa_pokok' => (float) $sisaPokok,
                'total_bayar_baru' => (float) $pelunasan,
                'total_bunga_baru' => (float) ($pelunasan - $sisaPokok),
                'jadwal_baru' => [],
                'pelunasan_sekarang' => $pelunasan,
            ]);
        }

        $jadwalBaru = $this->bunga->buatJadwal($sisaPokok, $tenorBaru, (float) $pinjaman->persentase_bunga);

        return response()->json([
            'tipe' => $tipe,
            'tenor_baru' => $tenorBaru,
            'cicilan_lama' => $cicilanLama,
            'cicilan_baru' => (float) ($jadwalBaru[0]['total_bayar'] ?? 0),
            'sisa_pokok' => (float) $sisaPokok,
            'total_bayar_baru' => (float) array_sum(array_column($jadwalBaru, 'total_bayar')),
            'total_bunga_baru' => (float) array_sum(array_column($jadwalBaru, 'nominal_bunga')),
            'jadwal_baru' => array_values(array_slice($jadwalBaru, 0, 12)),
            'pelunasan_sekarang' => null,
        ]);
    }

    /**
     * Ajukan percepatan/perpanjangan/pelunasan. Mirror validasi mock router.ts baris 1131-1174.
     *
     * @response 201 {"message": "Pengajuan perubahan tenor berhasil dikirim.", "data": {"id": 1}}
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'tipe' => ['required', 'in:percepat,perpanjang,lunas_total'],
            'tenor_baru' => ['required_if:tipe,percepat,perpanjang', 'nullable', 'integer', 'min:1'],
            'keterangan' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        [, $pinjaman] = $this->pinjamanAktifOrFail($request);

        if (! $pinjaman) {
            return response()->json(['message' => 'Anda tidak memiliki pinjaman aktif.'], 422);
        }

        if ($pinjaman->sudah_pakai_percepatan) {
            return response()->json(['message' => 'Perubahan tenor hanya dapat dilakukan satu kali per pinjaman.'], 422);
        }

        $menunggu = PengajuanPercepatan::where('pinjaman_id', $pinjaman->id)
            ->whereIn('status', ['diajukan', 'approved_bendahara'])
            ->exists();

        if ($menunggu) {
            return response()->json(['message' => 'Masih ada pengajuan perubahan tenor yang sedang diproses.'], 422);
        }

        $tipe = $request->tipe;
        $tenorBaru = $request->tenor_baru !== null ? (int) $request->tenor_baru : null;

        if ($tipe === 'percepat' && $tenorBaru >= $pinjaman->tenor_bulan) {
            return response()->json([
                'message' => 'Tenor baru harus lebih pendek dari tenor saat ini.',
                'errors' => ['tenor_baru' => ['Tenor baru harus lebih pendek.']],
            ], 422);
        }

        if ($tipe === 'perpanjang' && $tenorBaru <= $pinjaman->tenor_bulan) {
            return response()->json([
                'message' => 'Tenor baru harus lebih panjang dari tenor saat ini.',
                'errors' => ['tenor_baru' => ['Tenor baru harus lebih panjang.']],
            ], 422);
        }

        try {
            $pengajuan = $this->service->ajukan($pinjaman, $tipe, $tenorBaru, $request->keterangan);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $pengajuan->loadMissing(['pinjaman.anggota']);

        return response()->json([
            'message' => 'Pengajuan perubahan tenor berhasil dikirim.',
            'data' => (new PengajuanPercepatanResource($pengajuan))->resolve(),
        ], 201);
    }
}
