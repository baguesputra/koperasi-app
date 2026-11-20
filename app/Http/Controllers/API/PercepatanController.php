<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\PengajuanPercepatanResource;
use App\Models\PengajuanPercepatan;
use App\Models\Pinjaman;
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
            ->with(['pinjaman.anggota', 'angsuranBaru'])
            ->orderByDesc('tanggal_pengajuan')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'pinjaman' => [
                'id' => $pinjaman->id,
                'nominal' => (float) $pinjaman->nominal,
                'tenor_bulan' => (int) $pinjaman->tenor_bulan,
                'sisa_angsuran' => $pinjaman->sisaCicilanAktif(),
                'sudah_pakai_percepatan' => (bool) $pinjaman->sudah_pakai_percepatan,
            ],
            'data' => PengajuanPercepatanResource::collection($rows)->resolve(),
        ]);
    }

    /**
     * Preview simulasi percepatan memakai PercepatanPinjamanService::preview().
     *
     * @response 200 {"tipe": "percepat", "tenor_baru": 3, "cicilan_lama": 520000, "cicilan_baru": 0, "sisa_pokok": 2000000, "total_bayar_baru": 0, "total_bunga_baru": 0, "jadwal_baru": [], "pelunasan_sekarang": null}
     */
    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'tipe' => ['required', 'in:percepat,perpanjang,lunas_total'],
            'tenor_baru' => ['required_if:tipe,percepat,perpanjang', 'nullable', 'integer', 'min:1'],
        ]);

        [$anggota, $pinjaman] = $this->pinjamanAktifOrFail($request);

        if (! $pinjaman) {
            return response()->json(['message' => 'Anda tidak memiliki pinjaman aktif.'], 422);
        }

        if ($this->hasLoanApplicationInProgress($anggota->id)) {
            return response()->json(['message' => 'Pengajuan pinjaman sedang diproses.'], 422);
        }

        $tipe = $request->tipe;
        $tenorBaru = $request->tenor_baru !== null ? (int) $request->tenor_baru : null;

        $cicilanBelum = $pinjaman->angsuranBelumBayar()->orderBy('cicilan_ke')->get(['total_bayar']);
        $cicilanLama = $cicilanBelum->isNotEmpty()
            ? (int) round($cicilanBelum->sum('total_bayar') / $cicilanBelum->count())
            : 0;

        $sim = $this->service->preview($pinjaman, $tipe, $tenorBaru);
        $sisaPokok = (float) ($sim['sisa_pokok'] ?? 0);

        if ($tipe === 'lunas_total') {
            $pelunasan = (int) round((float) ($sim['total_bayar'] ?? 0));

            return response()->json([
                'tipe' => $tipe,
                'tenor_baru' => null,
                'cicilan_lama' => $cicilanLama,
                'cicilan_baru' => 0,
                'sisa_pokok' => $sisaPokok,
                'total_bayar_baru' => (float) $pelunasan,
                'total_bunga_baru' => (float) ($sim['bunga'] ?? ($pelunasan - $sisaPokok)),
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
            'sisa_pokok' => $sisaPokok,
            'total_bayar_baru' => (float) array_sum(array_column($jadwalBaru, 'total_bayar')),
            'total_bunga_baru' => (float) array_sum(array_column($jadwalBaru, 'nominal_bunga')),
            'jadwal_baru' => array_values(array_slice($jadwalBaru, 0, 12)),
            'pelunasan_sekarang' => null,
        ]);
    }

    /**
     * Ajukan percepatan/perpanjangan/pelunasan.
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
        $sisaAngsuran = $pinjaman->sisaCicilanAktif();

        if ($tipe === 'percepat' && $tenorBaru >= $sisaAngsuran) {
            return response()->json([
                'message' => "Tenor baru harus lebih pendek dari sisa angsuran ({$sisaAngsuran} bulan).",
                'errors' => ['tenor_baru' => ['Tenor baru harus lebih pendek dari sisa angsuran.']],
            ], 422);
        }

        if ($tipe === 'perpanjang' && $tenorBaru <= $sisaAngsuran) {
            return response()->json([
                'message' => 'Tenor baru harus lebih panjang dari sisa angsuran saat ini.',
                'errors' => ['tenor_baru' => ['Tenor baru harus lebih panjang.']],
            ], 422);
        }

        try {
            $pengajuan = $this->service->ajukan($pinjaman, $tipe, $tenorBaru, $request->keterangan);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $pengajuan->loadMissing(['pinjaman.anggota', 'angsuranBaru']);

        return response()->json([
            'message' => 'Pengajuan perubahan tenor berhasil dikirim.',
            'data' => (new PengajuanPercepatanResource($pengajuan))->resolve(),
        ], 201);
    }

    private function hasLoanApplicationInProgress(int $anggotaId): bool
    {
        return Pinjaman::where('anggota_id', $anggotaId)
            ->whereIn('status', ['diajukan', 'approved_admin', 'approved_bendahara'])
            ->exists();
    }
}
