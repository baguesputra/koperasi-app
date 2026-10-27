<?php

namespace App\Http\Controllers\API\Pengurus;

use App\Http\Controllers\Controller;
use App\Http\Resources\PengajuanLimitResource;
use App\Models\PengajuanLimit;
use App\Services\Pinjaman\PengajuanLimitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class LimitController extends Controller
{
    public function __construct(private PengajuanLimitService $service) {}

    /**
     * Antrean pengajuan limit (status diajukan/approved_bendahara, terbaru dulu).
     *
     * @response 200 {"data": [{"id": 1}], "total": 1}
     */
    public function index(Request $request): JsonResponse
    {
        if (! $request->user()->can('limit.tinjau-bendahara')
            && ! $request->user()->can('limit.approve-ketua')) {
            abort(response()->json([
                'message' => 'Anda tidak memiliki akses ke antrean limit.',
            ], 403));
        }

        $rows = PengajuanLimit::with('anggota')
            ->whereIn('status', ['diajukan', 'approved_bendahara'])
            ->orderByDesc('tanggal_pengajuan')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'data' => PengajuanLimitResource::collection($rows)->resolve(),
            'total' => $rows->count(),
        ]);
    }

    /**
     * Setujui pengajuan limit tahap berjalan (bendahara → ketua, final set limit_custom).
     * Mirror mock approveLimit: self-approval 403, status salah 409.
     *
     * @response 200 {"message": "Pengajuan limit disetujui.", "pengajuan": {"id": 1}}
     */
    public function approve(Request $request, PengajuanLimit $pengajuan): JsonResponse
    {
        $request->validate([
            'catatan' => ['required', 'string', 'min:5', 'max:500'],
            'limit_disetujui' => ['nullable', 'numeric', 'min:1'],
        ]);

        $this->tolakBilaSelfApproval($request, $pengajuan);

        try {
            $pesan = match ($pengajuan->status) {
                'diajukan' => $this->approveBendahara($request, $pengajuan),
                'approved_bendahara' => $this->approveKetua($request, $pengajuan),
                default => throw new RuntimeException('Status pengajuan sudah berubah. Muat ulang antrean.'),
            };
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], $this->kodeKesalahanStatus($e));
        }

        $pengajuan->refresh()->loadMissing('anggota');

        return response()->json([
            'message' => $pesan,
            'pengajuan' => (new PengajuanLimitResource($pengajuan))->resolve(),
        ]);
    }

    private function approveBendahara(Request $request, PengajuanLimit $pengajuan): string
    {
        if (! $request->user()->can('limit.tinjau-bendahara')) {
            throw new RuntimeException('Anda tidak memiliki akses ke tahap verifikasi Bendahara.');
        }

        $nominal = $request->limit_disetujui !== null
            ? (float) $request->limit_disetujui
            : (float) $pengajuan->limit_diminta;

        $this->service->approveBendahara($pengajuan, $nominal, $request->catatan);

        return 'Pengajuan limit disetujui. Menunggu keputusan Ketua.';
    }

    private function approveKetua(Request $request, PengajuanLimit $pengajuan): string
    {
        if (! $request->user()->can('limit.approve-ketua')) {
            throw new RuntimeException('Anda tidak memiliki akses ke tahap persetujuan Ketua.');
        }

        $nominal = $request->limit_disetujui !== null
            ? (float) $request->limit_disetujui
            : (float) ($pengajuan->limit_disetujui_bendahara ?? $pengajuan->limit_diminta);

        $this->service->approveKetua($pengajuan, $nominal, $request->catatan);

        return 'Pengajuan limit disetujui. Limit anggota telah diperbarui.';
    }

    /**
     * Tolak pengajuan limit pada tahap berjalan.
     *
     * @response 200 {"message": "Pengajuan limit ditolak.", "pengajuan": {"id": 1}}
     */
    public function reject(Request $request, PengajuanLimit $pengajuan): JsonResponse
    {
        $request->validate([
            'catatan' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $this->tolakBilaSelfApproval($request, $pengajuan);

        try {
            $pesan = match ($pengajuan->status) {
                'diajukan' => $this->rejectBendahara($request, $pengajuan),
                'approved_bendahara' => $this->rejectKetua($request, $pengajuan),
                default => throw new RuntimeException('Status pengajuan sudah berubah. Muat ulang antrean.'),
            };
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], $this->kodeKesalahanStatus($e));
        }

        $pengajuan->refresh()->loadMissing('anggota');

        return response()->json([
            'message' => $pesan,
            'pengajuan' => (new PengajuanLimitResource($pengajuan))->resolve(),
        ]);
    }

    private function rejectBendahara(Request $request, PengajuanLimit $pengajuan): string
    {
        if (! $request->user()->can('limit.tinjau-bendahara')) {
            throw new RuntimeException('Anda tidak memiliki akses ke tahap verifikasi Bendahara.');
        }

        $this->service->rejectBendahara($pengajuan, $request->catatan);

        return 'Pengajuan limit ditolak oleh Bendahara.';
    }

    private function rejectKetua(Request $request, PengajuanLimit $pengajuan): string
    {
        if (! $request->user()->can('limit.approve-ketua')) {
            throw new RuntimeException('Anda tidak memiliki akses ke tahap persetujuan Ketua.');
        }

        $this->service->rejectKetua($pengajuan, $request->catatan);

        return 'Pengajuan limit ditolak oleh Ketua.';
    }

    private function tolakBilaSelfApproval(Request $request, PengajuanLimit $pengajuan): void
    {
        if ($pengajuan->anggota && $pengajuan->anggota->user_id === $request->user()->id) {
            abort(response()->json([
                'message' => 'Anda tidak dapat memproses pengajuan Anda sendiri.',
            ], 403));
        }
    }

    private function kodeKesalahanStatus(RuntimeException $e): int
    {
        return str_contains($e->getMessage(), 'Status pengajuan sudah berubah') ? 409 : 422;
    }
}
