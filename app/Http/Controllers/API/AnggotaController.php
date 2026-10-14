<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RiwayatPinjamanRequest;
use App\Http\Requests\Api\SimpananRequest;
use App\Models\Pinjaman;
use App\Services\Pinjaman\EligibilitasPinjamanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnggotaController extends Controller
{
    public function __construct(private EligibilitasPinjamanService $eligibilitas) {}

    private function anggotaOrFail(Request $request)
    {
        $anggota = $request->user()->anggota;

        if (! $anggota) {
            abort(response()->json([
                'message' => 'Anggota not found for user',
            ], 404));
        }

        return $anggota;
    }

    /**
     * Pinjaman aktif milik anggota yang sedang login.
     *
     * @response 200 {"data": [{"id": 1, "nominal": 5000000, "tenor_bulan": 12, "status": "aktif", "tanggal_pengajuan": "2026-08-01", "tanggal_pencairan": "2026-08-05", "sisa_angsuran": 8, "total_angsuran": 12, "sisa_total_bayar": 3600000, "angsuran_berikutnya": {"cicilan_ke": 5, "total_bayar": 450000, "tanggal_jatuh_tempo": "05 Feb 2026"}}], "count": 1}
     */
    public function pinjamanAktif(Request $request): JsonResponse
    {
        $anggota = $this->anggotaOrFail($request);

        $data = $anggota->pinjamanAktifList()->map(function ($p) {
            $agg = $p->agregatJadwalAktif();
            $terdekat = $p->angsuranBelumBayar()->orderBy('cicilan_ke')->first();

            return [
                'id' => $p->id,
                'nominal' => (float) $p->nominal,
                'tenor_bulan' => $p->tenor_bulan,
                'status' => $p->status,
                'tanggal_pengajuan' => $p->tanggal_pengajuan?->format('Y-m-d'),
                'tanggal_pencairan' => $p->tanggal_pencairan?->format('Y-m-d'),
                'sisa_angsuran' => $agg['sisa'],
                'total_angsuran' => $agg['total'],
                'sisa_total_bayar' => $agg['sisa_bayar'],
                'angsuran_berikutnya' => $terdekat ? [
                    'cicilan_ke' => $terdekat->cicilan_ke,
                    'total_bayar' => (float) $terdekat->total_bayar,
                    'tanggal_jatuh_tempo' => $terdekat->tanggal_jatuh_tempo?->format('Y-m-d'),
                ] : null,
            ];
        })->values();

        return response()->json([
            'data' => $data,
            'count' => $data->count(),
        ]);
    }

    /**
     * Riwayat pinjaman milik anggota yang sedang login.
     *
     * @response 200 {"data": [{"id": 1, "nominal": 5000000, "tenor_bulan": 12, "status": "lunas", "tanggal_pengajuan": "2026-08-01", "catatan_bendahara": null, "catatan_ketua": null, "angsuran": [], "riwayat_perubahan": []}], "meta": {"current_page": 1, "last_page": 1, "per_page": 15, "total": 1}}
     */
    public function riwayatPinjaman(RiwayatPinjamanRequest $request): JsonResponse
    {
        $anggota = $this->anggotaOrFail($request);

        $query = $anggota->pinjaman()
            ->with(['angsuran', 'pengajuanPercepatan.angsuranBaru'])
            ->latest('tanggal_pengajuan');

        if ($request->filled('status')) {
            $query->where('status', $request->validated('status'));
        }

        $halaman = $query->paginate($request->validated('per_page', 15));

        $data = $halaman->getCollection()->map(fn ($p) => [
            'id' => $p->id,
            'nominal' => (float) $p->nominal,
            'tenor_bulan' => $p->tenor_bulan,
            'status' => $p->status,
            'tanggal_pengajuan' => $p->tanggal_pengajuan?->format('Y-m-d'),
            'catatan_bendahara' => $p->catatan_bendahara,
            'catatan_ketua' => $p->catatan_ketua,
            'angsuran' => $this->jadwalDariEager($p)->values()->map(fn ($a) => [
                'cicilan_ke' => $a->cicilan_ke,
                'total_bayar' => (float) $a->total_bayar,
                'status' => $a->status,
                'tanggal_jatuh_tempo' => $a->tanggal_jatuh_tempo?->format('Y-m-d'),
            ]),
            'riwayat_perubahan' => $p->pengajuanPercepatan->where('status', 'aktif')->map(fn ($pp) => [
                'tipe' => $pp->tipe,
                'tenor_lama' => $pp->tenor_lama,
                'tenor_baru' => $pp->tenor_baru,
                'tanggal' => $pp->updated_at?->format('Y-m-d'),
            ])->values(),
        ])->values();

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $halaman->currentPage(),
                'last_page' => $halaman->lastPage(),
                'per_page' => $halaman->perPage(),
                'total' => $halaman->total(),
            ],
        ]);
    }

    /**
     * Replika Pinjaman::jadwalAktif() murni dari relasi ter-eager
     * (nol query baru) — pola sama seperti MigrasiController::progresPinjaman.
     */
    private function jadwalDariEager(Pinjaman $pinjaman)
    {
        if (! $pinjaman->relationLoaded('angsuran') || ! $pinjaman->relationLoaded('pengajuanPercepatan')) {
            return $pinjaman->jadwalAktif();
        }

        $pengajuanAktif = $pinjaman->getRelationValue('pengajuanPercepatan')
            ->where('status', 'aktif')->sortByDesc('created_at')->first();
        $lama = $pinjaman->getRelationValue('angsuran')
            ->where('status', '!=', 'digantikan')->sortBy('cicilan_ke')->values();

        if (! $pengajuanAktif) {
            return $lama;
        }

        if (! $pengajuanAktif->relationLoaded('angsuranBaru')) {
            return $pinjaman->jadwalAktif();
        }

        return $lama->concat($pengajuanAktif->getRelationValue('angsuranBaru')->sortBy('cicilan_ke')->values());
    }

    /**
     * Limit pinjaman milik anggota yang sedang login.
     *
     * @response 200 {"limit_maksimal": 10000000, "limit_tersedia": 10000000, "boleh": true, "alasan": null, "sisa_angsuran": 0, "cicilan_pokok": 0}
     */
    public function limit(Request $request): JsonResponse
    {
        $anggota = $this->anggotaOrFail($request);

        $cek = $this->eligibilitas->cek($anggota);

        return response()->json([
            'limit_maksimal' => (float) $this->eligibilitas->limitMaksimal($anggota),
            'limit_tersedia' => (float) $cek['limit_tersedia'],
            'boleh' => (bool) $cek['boleh'],
            'alasan' => $cek['alasan'],
            'sisa_angsuran' => (int) $cek['sisa_angsuran'],
            'cicilan_pokok' => (float) $cek['cicilan_pokok'],
        ]);
    }

    /**
     * Simpanan milik anggota yang sedang login.
     *
     * @response 200 {"ringkasan": {"total": 320000, "pokok": 50000, "wajib": 270000}, "data": [{"jenis": "wajib", "jumlah": 30000, "bulan_periode": "2026-09", "tanggal_input": "2026-09-30"}]}
     */
    public function simpanan(SimpananRequest $request): JsonResponse
    {
        $anggota = $this->anggotaOrFail($request);

        $agg = $anggota->simpanan()
            ->selectRaw('
                SUM(CASE WHEN jenis IN ("pokok","wajib") THEN jumlah ELSE 0 END) as total,
                SUM(CASE WHEN jenis = "pokok" THEN jumlah ELSE 0 END) as pokok,
                SUM(CASE WHEN jenis = "wajib" THEN jumlah ELSE 0 END) as wajib
            ')
            ->first();

        $query = $anggota->simpanan()
            ->whereIn('jenis', ['pokok', 'wajib'])
            ->latest('tanggal_input');

        if ($request->filled('bulan_periode')) {
            $query->where('bulan_periode', $request->validated('bulan_periode'));
        }

        $data = $query->take(20)->get()->map(fn ($s) => [
            'jenis' => $s->jenis,
            'jumlah' => (float) $s->jumlah,
            'bulan_periode' => $s->bulan_periode,
            'tanggal_input' => $s->tanggal_input?->format('Y-m-d'),
        ])->values();

        return response()->json([
            'ringkasan' => [
                'total' => (float) ($agg->total ?? 0),
                'pokok' => (float) ($agg->pokok ?? 0),
                'wajib' => (float) ($agg->wajib ?? 0),
            ],
            'data' => $data,
        ]);
    }

    /**
     * Tukar sesi web SSO menjadi token Sanctum (dipakai aplikasi mobile
     * setelah login SSO via browser selesai).
     */
    public function ssoToken(Request $request): JsonResponse
    {
        $token = $request->user()->createToken('sso_token')->plainTextToken;

        return response()->json([
            'user' => $request->user(),
            'token' => $token,
        ]);
    }
}
