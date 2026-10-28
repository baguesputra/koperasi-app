<?php

namespace App\Http\Controllers\API\Pengurus;

use App\Http\Controllers\Controller;
use App\Http\Resources\PinjamanResource;
use App\Models\Anggota;
use App\Models\Pinjaman;
use App\Models\Simpanan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListController extends Controller
{
    private const STATUS_SAH = ['diajukan', 'approved_admin', 'approved_bendahara', 'approved_ketua', 'aktif', 'lunas', 'ditolak'];

    /**
     * Daftar semua pinjaman + rekap per status mengikuti filter cari.
     * Mirror mock /pengurus/list-pinjaman + kartu statistik web Pinjaman/Index.
     *
     * @response 200 {"data": [{"id": 1}], "meta": {"current_page": 1}, "rekap": [{"status": "aktif"}]}
     */
    public function pinjaman(Request $request): JsonResponse
    {
        if (! $request->user()->can('pinjaman.lihat')) {
            abort(response()->json([
                'message' => 'Anda tidak memiliki akses ke data pinjaman.',
            ], 403));
        }

        $request->validate([
            'cari' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $status = (string) $request->input('status', '');
        if ($status !== '' && $status !== 'semua' && ! in_array($status, self::STATUS_SAH, true)) {
            return response()->json(['message' => 'Status tidak dikenal.'], 422);
        }

        $cari = trim((string) $request->input('cari', ''));

        $dasar = Pinjaman::with(['anggota', 'pengaju']);
        $this->terapkanCari($dasar, $cari);

        $rekap = (clone $dasar)
            ->selectRaw('status, COUNT(*) as jumlah, COALESCE(SUM(nominal), 0) as total_nominal')
            ->groupBy('status')
            ->pluck('total_nominal', 'status');

        // Rekap mengikuti hasil pencarian (tanpa filter status) ala kartu statistik web.
        $rekapJumlah = (clone $dasar)
            ->selectRaw('status, COUNT(*) as jumlah')
            ->groupBy('status')
            ->pluck('jumlah', 'status');

        $query = clone $dasar;
        if ($status !== '' && $status !== 'semua') {
            $query->where('status', $status);
        }

        $halaman = $query->latest('tanggal_pengajuan')->latest('id')
            ->paginate($request->input('per_page', 15), ['*'], 'page', $request->input('page', 1));

        return response()->json([
            'data' => PinjamanResource::collection($halaman->getCollection())->resolve(),
            'meta' => [
                'current_page' => $halaman->currentPage(),
                'last_page' => $halaman->lastPage(),
                'per_page' => $halaman->perPage(),
                'total' => $halaman->total(),
            ],
            'rekap' => collect(self::STATUS_SAH)->map(fn ($s) => [
                'status' => $s,
                'jumlah' => (int) ($rekapJumlah[$s] ?? 0),
                'total_nominal' => (float) ($rekap[$s] ?? 0),
            ])->all(),
        ]);
    }

    /**
     * Daftar simpanan per anggota (pokok/wajib/sosial + setoran terakhir).
     * Mirror mock /pengurus/list-simpanan + web Simpanan/Index.
     *
     * @response 200 {"data": [{"anggota_id": 1}], "meta": {"current_page": 1}}
     */
    public function simpanan(Request $request): JsonResponse
    {
        if (! $request->user()->can('simpanan.lihat')) {
            abort(response()->json([
                'message' => 'Anda tidak memiliki akses ke data simpanan.',
            ], 403));
        }

        $request->validate([
            'cari' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Anggota::query()->with(['simpanan' => fn ($q) => $q->latest('tanggal_input')]);

        $cari = trim((string) $request->input('cari', ''));
        if ($cari !== '') {
            $query->where(fn ($q) => $q
                ->where('nama', 'like', "%{$cari}%")
                ->orWhere('no_karyawan', 'like', "%{$cari}%")
                ->orWhere('no_anggota', 'like', "%{$cari}%"));
        }

        $query->withSum(['simpanan as agregat_pokok' => fn ($q) => $q->where('jenis', 'pokok')], 'jumlah')
            ->withSum(['simpanan as agregat_wajib' => fn ($q) => $q->where('jenis', 'wajib')], 'jumlah')
            ->withSum(['simpanan as agregat_sosial' => fn ($q) => $q->where('jenis', 'dana_sosial')], 'jumlah')
            ->orderByDesc('agregat_pokok')
            ->orderByDesc('agregat_wajib');

        $halaman = $query->paginate($request->input('per_page', 15), ['*'], 'page', $request->input('page', 1));

        $data = $halaman->getCollection()->map(fn ($a) => $this->barisSimpanan($a))->values();

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
     * Detail simpanan satu anggota (ringkas agregat + riwayat).
     * Mirror mock /pengurus/list-simpanan/{id}.
     *
     * @response 200 {"anggota": {"id": 1}, "total": 100000, "data": []}
     */
    public function simpananDetail(Request $request, Anggota $anggota): JsonResponse
    {
        if (! $request->user()->can('simpanan.lihat')) {
            abort(response()->json([
                'message' => 'Anda tidak memiliki akses ke data simpanan.',
            ], 403));
        }

        $anggota->load(['simpanan' => fn ($q) => $q->latest('tanggal_input')]);

        $baris = $this->barisSimpanan($anggota);

        return response()->json([
            'anggota' => $baris['anggota'],
            'pokok' => $baris['simpanan_pokok'],
            'wajib' => $baris['simpanan_wajib'],
            'dana_sosial' => $baris['dana_sosial'],
            'total' => $baris['total_simpanan'],
            'data' => $anggota->simpanan->map(fn ($s) => [
                'id' => $s->id,
                'anggota_id' => $s->anggota_id,
                'jenis' => $s->jenis,
                'jumlah' => (float) $s->jumlah,
                'bulan_periode' => $s->bulan_periode,
                'tanggal_input' => $s->tanggal_input?->format('Y-m-d'),
            ])->values(),
        ]);
    }

    private function barisSimpanan(Anggota $a): array
    {
        $pokok = (float) ($a->agregat_pokok
            ?? $a->simpanan->where('jenis', 'pokok')->sum('jumlah'));
        $wajib = (float) ($a->agregat_wajib
            ?? $a->simpanan->where('jenis', 'wajib')->sum('jumlah'));
        $sosial = (float) ($a->agregat_sosial
            ?? $a->simpanan->where('jenis', 'dana_sosial')->sum('jumlah'));

        return [
            'anggota_id' => $a->id,
            'anggota' => [
                'id' => $a->id,
                'no_anggota' => $a->no_anggota,
                'no_karyawan' => $a->no_karyawan,
                'nama' => $a->nama,
                'jabatan' => $a->jabatan,
                'cabang' => $a->cabang,
                'unit_bisnis' => $a->unit_bisnis,
                'department' => $a->department,
                'divisi' => $a->divisi,
                'no_hp' => $a->no_hp,
                'status' => $a->status,
                'foto_url' => $a->foto_url,
            ],
            'simpanan_pokok' => $pokok,
            'simpanan_wajib' => $wajib,
            'dana_sosial' => $sosial,
            'total_simpanan' => $pokok + $wajib + $sosial,
            'setoran_terakhir' => $a->simpanan->first()?->tanggal_input?->format('Y-m-d'),
        ];
    }

    private function terapkanCari($query, string $cari): void
    {
        if ($cari === '') {
            return;
        }

        $query->where(function ($q) use ($cari) {
            $q->whereHas('anggota', fn ($qq) => $qq
                ->where('nama', 'like', "%{$cari}%")
                ->orWhere('no_karyawan', 'like', "%{$cari}%")
                ->orWhere('no_anggota', 'like', "%{$cari}%"))
                ->orWhere('nomor_dokumen', 'like', "%{$cari}%");
        });
    }
}
