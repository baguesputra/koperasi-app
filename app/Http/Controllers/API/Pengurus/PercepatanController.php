<?php

namespace App\Http\Controllers\API\Pengurus;

use App\Http\Controllers\Controller;
use App\Http\Resources\PengajuanPercepatanResource;
use App\Models\PengajuanPercepatan;
use App\Services\Pinjaman\PercepatanPinjamanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class PercepatanController extends Controller
{
    public function __construct(private PercepatanPinjamanService $service) {}

    /**
     * Antrean pengajuan percepatan (status diajukan/approved_bendahara, terbaru dulu).
     *
     * @response 200 {"data": [{"id": 1}], "total": 1}
     */
    public function index(Request $request): JsonResponse
    {
        if (! $request->user()->can('pinjaman.tinjau-bendahara')
            && ! $request->user()->can('pinjaman.approve-ketua')) {
            abort(response()->json([
                'message' => 'Anda tidak memiliki akses ke antrean percepatan.',
            ], 403));
        }

        $rows = PengajuanPercepatan::with(['pinjaman.anggota'])
            ->whereIn('status', ['diajukan', 'approved_bendahara'])
            ->orderByDesc('tanggal_pengajuan')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'data' => PengajuanPercepatanResource::collection($rows)->resolve(),
            'total' => $rows->count(),
        ]);
    }

    /**
     * Setujui pengajuan percepatan (bendahara → ketua, final terapkan jadwal baru).
     * Catatan: Ketua wajib menyertakan bulan_berlaku (bulan_ini|bulan_depan);
     * layar mobile mengunci tombol final ke bulan_depan bila tidak dikirim.
     * Mirror mock approvePercepatan: self-approval 403, status salah 409.
     *
     * @response 200 {"message": "Pengajuan percepatan disetujui.", "pengajuan": {"id": 1}}
     */
    public function approve(Request $request, PengajuanPercepatan $pengajuan): JsonResponse
    {
        $request->validate([
            'catatan' => ['required', 'string', 'min:5', 'max:500'],
            'bulan_berlaku' => ['nullable', 'in:bulan_ini,bulan_depan'],
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

        $pengajuan->refresh()->loadMissing(['pinjaman.anggota']);

        return response()->json([
            'message' => $pesan,
            'pengajuan' => (new PengajuanPercepatanResource($pengajuan))->resolve(),
        ]);
    }

    private function approveBendahara(Request $request, PengajuanPercepatan $pengajuan): string
    {
        if (! $request->user()->can('pinjaman.tinjau-bendahara')) {
            throw new RuntimeException('Anda tidak memiliki akses ke tahap verifikasi Bendahara.');
        }

        $this->service->approveBendahara($pengajuan, $request->catatan);

        return 'Pengajuan percepatan disetujui. Menunggu keputusan Ketua.';
    }

    private function approveKetua(Request $request, PengajuanPercepatan $pengajuan): string
    {
        if (! $request->user()->can('pinjaman.approve-ketua')) {
            throw new RuntimeException('Anda tidak memiliki akses ke tahap persetujuan Ketua.');
        }

        $this->service->approveKetua($pengajuan, $request->catatan, $request->bulan_berlaku ?? 'bulan_depan');

        return 'Pengajuan percepatan disetujui dan jadwal baru telah diterapkan.';
    }

    /**
     * Tolak pengajuan percepatan pada tahap berjalan.
     *
     * @response 200 {"message": "Pengajuan percepatan ditolak.", "pengajuan": {"id": 1}}
     */
    public function reject(Request $request, PengajuanPercepatan $pengajuan): JsonResponse
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

        $pengajuan->refresh()->loadMissing(['pinjaman.anggota']);

        return response()->json([
            'message' => $pesan,
            'pengajuan' => (new PengajuanPercepatanResource($pengajuan))->resolve(),
        ]);
    }

    private function rejectBendahara(Request $request, PengajuanPercepatan $pengajuan): string
    {
        if (! $request->user()->can('pinjaman.tinjau-bendahara')) {
            throw new RuntimeException('Anda tidak memiliki akses ke tahap verifikasi Bendahara.');
        }

        $this->service->rejectBendahara($pengajuan, $request->catatan);

        return 'Pengajuan percepatan ditolak oleh Bendahara.';
    }

    private function rejectKetua(Request $request, PengajuanPercepatan $pengajuan): string
    {
        if (! $request->user()->can('pinjaman.approve-ketua')) {
            throw new RuntimeException('Anda tidak memiliki akses ke tahap persetujuan Ketua.');
        }

        $this->service->rejectKetua($pengajuan, $request->catatan);

        return 'Pengajuan percepatan ditolak oleh Ketua.';
    }

    private function tolakBilaSelfApproval(Request $request, PengajuanPercepatan $pengajuan): void
    {
        $pengajuan->loadMissing('pinjaman.anggota');

        if ($pengajuan->pinjaman && $pengajuan->pinjaman->anggota
            && $pengajuan->pinjaman->anggota->user_id === $request->user()->id) {
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
