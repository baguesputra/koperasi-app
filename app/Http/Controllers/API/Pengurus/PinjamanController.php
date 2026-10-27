<?php

namespace App\Http\Controllers\API\Pengurus;

use App\Http\Controllers\Controller;
use App\Http\Resources\PinjamanResource;
use App\Models\KasKoperasi;
use App\Models\Pinjaman;
use App\Services\Pinjaman\EligibilitasPinjamanService;
use App\Services\Pinjaman\PerhitunganBungaService;
use App\Services\Pinjaman\PersetujuanPinjamanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class PinjamanController extends Controller
{
    public function __construct(
        private PersetujuanPinjamanService $persetujuan,
        private PerhitunganBungaService $bunga,
        private EligibilitasPinjamanService $eligibilitas,
    ) {}

    private const TAHAP_STATUS = [
        'admin' => 'diajukan',
        'bendahara' => 'approved_admin',
        'ketua' => 'approved_bendahara',
        'pencairan' => 'approved_bendahara',
    ];

    private const TAHAP_IZIN = [
        'admin' => 'pinjaman.tinjau-admin',
        'bendahara' => 'pinjaman.tinjau-bendahara',
        'ketua' => 'pinjaman.approve-ketua',
        'pencairan' => 'pinjaman.tinjau-bendahara',
    ];

    private function tahapOrFail(Request $request): string
    {
        $tahap = (string) $request->input('tahap', 'admin');

        if (! array_key_exists($tahap, self::TAHAP_STATUS)) {
            abort(response()->json([
                'message' => 'Tahap tidak dikenal. Gunakan: admin, bendahara, ketua, pencairan.',
            ], 422));
        }

        if (! $request->user()->can(self::TAHAP_IZIN[$tahap])) {
            abort(response()->json([
                'message' => 'Anda tidak memiliki akses ke antrean tahap ini.',
            ], 403));
        }

        return $tahap;
    }

    /**
     * Antrean pinjaman per tahap verifikasi (mirror mock router antreanPinjaman).
     *
     * @response 200 {"tahap": "admin", "data": [{"id": 1}], "total": 1}
     */
    public function index(Request $request): JsonResponse
    {
        $tahap = $this->tahapOrFail($request);

        $query = Pinjaman::with(['anggota', 'pengaju'])
            ->where('status', self::TAHAP_STATUS[$tahap])
            ->orderByDesc('tanggal_pengajuan')
            ->orderByDesc('id');

        // Tahap ketua: hanya yang cair manual oleh ketua (bukan jalur mandiri bendahara).
        // Pencairan: hanya jalur mandiri (cair oleh bendahara).
        if ($tahap === 'ketua') {
            $query->where('cair_oleh_bendahara', false);
        } elseif ($tahap === 'pencairan') {
            $query->where('cair_oleh_bendahara', true);
        }

        $rows = $query->get();

        return response()->json([
            'tahap' => $tahap,
            'data' => PinjamanResource::collection($rows)->resolve(),
            'total' => $rows->count(),
        ]);
    }

    /**
     * Preview simulasi keputusan (nominal/tenor usulan) + saldo kas.
     * Mirror Ketua\PinjamanController@preview + kontrak PreviewKeputusanResponse.
     *
     * @response 200 {"nominal": 1000000, "tenor_bulan": 3, "jadwal": [], "kas_saldo": 0, "kas_sisa": 0}
     */
    public function preview(Request $request, Pinjaman $pinjaman): JsonResponse
    {
        $request->validate([
            'nominal' => ['nullable', 'numeric', 'min:1'],
            'tenor_bulan' => ['nullable', 'integer', 'min:1', 'max:120'],
        ]);

        $nominal = $request->nominal !== null
            ? (float) $request->nominal
            : (float) ($pinjaman->nominal_disetujui_bendahara ?? $pinjaman->nominal);

        try {
            $tenorFinal = $this->persetujuan->resolveTenor(
                $pinjaman,
                $nominal,
                $request->tenor_bulan !== null ? (int) $request->tenor_bulan : null,
                $pinjaman->tenor_disetujui_bendahara ?? $pinjaman->tenor_bulan
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $jadwal = $this->bunga->buatJadwal($nominal, $tenorFinal, (float) $pinjaman->persentase_bunga);
        $kasSaldo = (float) (KasKoperasi::first()?->saldo_bank ?? 0);

        return response()->json([
            'nominal' => $nominal,
            'tenor_bulan' => $tenorFinal,
            'tenor_maksimal' => $this->eligibilitas->tenorMaksimal($nominal),
            'jadwal' => $jadwal,
            'total_cicilan' => count($jadwal),
            'total_pokok' => array_sum(array_column($jadwal, 'nominal_pokok')),
            'total_bunga' => array_sum(array_column($jadwal, 'nominal_bunga')),
            'total_bayar' => array_sum(array_column($jadwal, 'total_bayar')),
            'cicilan_pertama' => $jadwal[0] ?? null,
            'cicilan_terakhir' => ! empty($jadwal) ? end($jadwal) : null,
            'cicilan_rata_rata' => count($jadwal) > 0
                ? round(array_sum(array_column($jadwal, 'total_bayar')) / count($jadwal), 2)
                : 0,
            'kas_saldo' => $kasSaldo,
            'kas_sisa' => $kasSaldo - $nominal,
        ]);
    }

    /**
     * Setujui pinjaman tahap berjalan (admin → bendahara → ketua/pencairan).
     * Mirror mock approvePinjaman: self-approval 403, status salah 409.
     *
     * @response 200 {"message": "Pengajuan diteruskan ke Bendahara.", "pinjaman": {"id": 1}}
     */
    public function approve(Request $request, Pinjaman $pinjaman): JsonResponse
    {
        $request->validate([
            'catatan' => ['required', 'string', 'min:5', 'max:500'],
            'nominal' => ['nullable', 'numeric', 'min:1'],
            'tenor_bulan' => ['nullable', 'integer', 'min:1', 'max:120'],
        ]);

        $this->tolakBilaSelfApproval($request, $pinjaman);

        $nominal = $request->nominal !== null ? (float) $request->nominal : null;
        $tenor = $request->tenor_bulan !== null ? (int) $request->tenor_bulan : null;

        try {
            $pesan = match ($pinjaman->status) {
                'diajukan' => $this->approveAdmin($request, $pinjaman),
                'approved_admin' => $this->approveBendahara($request, $pinjaman, $nominal, $tenor),
                'approved_bendahara' => $this->cairkanTahap($request, $pinjaman, $nominal, $tenor),
                default => throw new RuntimeException('Status pengajuan sudah berubah. Muat ulang antrean.'),
            };
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], $this->kodeKesalahanStatus($e));
        }

        $pinjaman->refresh()->loadMissing(['anggota', 'pengaju']);

        return response()->json([
            'message' => $pesan,
            'pinjaman' => (new PinjamanResource($pinjaman))->resolve(),
        ]);
    }

    private function approveAdmin(Request $request, Pinjaman $pinjaman): string
    {
        if (! $request->user()->can('pinjaman.tinjau-admin')) {
            throw new RuntimeException('Anda tidak memiliki akses ke tahap verifikasi Admin.');
        }

        $this->persetujuan->approveAdmin($pinjaman, $request->catatan);

        return 'Pengajuan diteruskan ke Bendahara.';
    }

    private function approveBendahara(Request $request, Pinjaman $pinjaman, ?float $nominal, ?int $tenor): string
    {
        if (! $request->user()->can('pinjaman.tinjau-bendahara')) {
            throw new RuntimeException('Anda tidak memiliki akses ke tahap verifikasi Bendahara.');
        }

        $this->persetujuan->approveBendahara($pinjaman, $request->catatan, $nominal, $tenor);

        return 'Pengajuan disetujui. Menunggu keputusan Ketua.';
    }

    private function cairkanTahap(Request $request, Pinjaman $pinjaman, ?float $nominal, ?int $tenor): string
    {
        if ($pinjaman->cair_oleh_bendahara) {
            if (! $request->user()->can('pinjaman.tinjau-bendahara')) {
                throw new RuntimeException('Anda tidak memiliki akses ke tahap pencairan.');
            }

            $this->persetujuan->cairBendahara($pinjaman, $request->catatan);

            return 'Pinjaman disetujui dan dana telah dicairkan.';
        }

        if (! $request->user()->can('pinjaman.approve-ketua')) {
            throw new RuntimeException('Anda tidak memiliki akses ke tahap persetujuan Ketua.');
        }

        $this->persetujuan->approveKetua($pinjaman, $request->catatan, $nominal, $tenor);

        return 'Pinjaman disetujui dan dana telah dicairkan.';
    }

    /**
     * Tolak pinjaman pada tahap berjalan (mirror mock rejectPinjaman).
     *
     * @response 200 {"message": "Pengajuan ditolak.", "pinjaman": {"id": 1}}
     */
    public function reject(Request $request, Pinjaman $pinjaman): JsonResponse
    {
        $request->validate([
            'catatan' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $this->tolakBilaSelfApproval($request, $pinjaman);

        try {
            $pesan = match ($pinjaman->status) {
                'diajukan' => $this->rejectAdmin($request, $pinjaman),
                'approved_admin' => $this->rejectBendahara($request, $pinjaman),
                'approved_bendahara' => $this->tolakKetua($request, $pinjaman),
                default => throw new RuntimeException('Status pengajuan sudah berubah. Muat ulang antrean.'),
            };
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], $this->kodeKesalahanStatus($e));
        }

        $pinjaman->refresh()->loadMissing(['anggota', 'pengaju']);

        return response()->json([
            'message' => $pesan,
            'pinjaman' => (new PinjamanResource($pinjaman))->resolve(),
        ]);
    }

    private function rejectAdmin(Request $request, Pinjaman $pinjaman): string
    {
        if (! $request->user()->can('pinjaman.tinjau-admin')) {
            throw new RuntimeException('Anda tidak memiliki akses ke tahap verifikasi Admin.');
        }

        $this->persetujuan->rejectAdmin($pinjaman, $request->catatan);

        return 'Pengajuan ditolak oleh Admin.';
    }

    private function rejectBendahara(Request $request, Pinjaman $pinjaman): string
    {
        if (! $request->user()->can('pinjaman.tinjau-bendahara')) {
            throw new RuntimeException('Anda tidak memiliki akses ke tahap verifikasi Bendahara.');
        }

        $this->persetujuan->rejectBendahara($pinjaman, $request->catatan);

        return 'Pengajuan ditolak oleh Bendahara.';
    }

    private function tolakKetua(Request $request, Pinjaman $pinjaman): string
    {
        // Jalur mandiri (cair oleh bendahara) tidak bisa ditolak ketua.
        if ($pinjaman->cair_oleh_bendahara) {
            if (! $request->user()->can('pinjaman.tinjau-bendahara')) {
                throw new RuntimeException('Anda tidak memiliki akses ke tahap pencairan.');
            }

            throw new RuntimeException('Pinjaman ini tidak dalam status menunggu pencairan Bendahara.');
        }

        if (! $request->user()->can('pinjaman.approve-ketua')) {
            throw new RuntimeException('Anda tidak memiliki akses ke tahap persetujuan Ketua.');
        }

        $this->persetujuan->rejectKetua($pinjaman, $request->catatan);

        return 'Pengajuan ditolak oleh Ketua.';
    }

    private function tolakBilaSelfApproval(Request $request, Pinjaman $pinjaman): void
    {
        $pengaju = $pinjaman->pengaju_user_id;
        $pemilik = $pinjaman->anggota?->user_id;

        if (($pengaju && $pengaju === $request->user()->id)
            || ($pemilik && $pemilik === $request->user()->id)) {
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
