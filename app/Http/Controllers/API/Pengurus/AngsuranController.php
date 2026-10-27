<?php

namespace App\Http\Controllers\API\Pengurus;

use App\Http\Controllers\Controller;
use App\Models\Angsuran;
use App\Models\AngsuranPercepatan;
use App\Models\PengajuanPercepatan;
use App\Services\Pinjaman\KonfirmasiAngsuranService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AngsuranController extends Controller
{
    public function __construct(private KonfirmasiAngsuranService $konfirmasi) {}

    /**
     * Antrean angsuran belum bayar (normal + percepatan) untuk konfirmasi massal.
     * Mirror mock calonAngsuran: semua pinjaman aktif, urut jatuh tempo.
     *
     * @response 200 {"data": [{"angsuran_id": 1}], "total": 1, "periode": "2026-10"}
     */
    public function index(Request $request): JsonResponse
    {
        if (! $request->user()->can('angsuran.konfirmasi')) {
            abort(response()->json([
                'message' => 'Anda tidak memiliki akses konfirmasi angsuran.',
            ], 403));
        }

        $pinjamanBerpengajuan = PengajuanPercepatan::whereIn('status', ['diajukan', 'approved_bendahara'])
            ->pluck('pinjaman_id')
            ->flip();

        $normal = Angsuran::with('pinjaman.anggota')
            ->where('status', 'belum_bayar')
            ->whereHas('pinjaman', fn ($q) => $q->where('status', 'aktif'))
            ->get()
            ->map(fn ($a) => $this->baris(
                angsuranId: $a->id,
                pinjamanId: $a->pinjaman_id,
                nama: $a->pinjaman->anggota->nama ?? '-',
                noAnggota: $a->pinjaman->anggota->no_anggota ?? '-',
                cicilanKe: $a->cicilan_ke,
                totalBayar: (float) $a->total_bayar,
                jatuhTempo: $a->tanggal_jatuh_tempo,
                adaPengajuan: $pinjamanBerpengajuan->has($a->pinjaman_id),
            ));

        $percepatan = AngsuranPercepatan::with('pengajuan.pinjaman.anggota')
            ->where('status', 'belum_bayar')
            ->whereHas('pengajuan.pinjaman', fn ($q) => $q->where('status', 'aktif'))
            ->get()
            ->map(fn ($a) => $this->baris(
                angsuranId: $a->id,
                pinjamanId: $a->pengajuan->pinjaman_id,
                nama: $a->pengajuan->pinjaman->anggota->nama ?? '-',
                noAnggota: $a->pengajuan->pinjaman->anggota->no_anggota ?? '-',
                cicilanKe: $a->cicilan_ke,
                totalBayar: (float) $a->total_bayar,
                jatuhTempo: $a->tanggal_jatuh_tempo,
                adaPengajuan: false,
            ));

        $rows = $normal->concat($percepatan)
            ->sortBy('tanggal_jatuh_tempo')
            ->values();

        return response()->json([
            'data' => $rows,
            'total' => $rows->count(),
            'periode' => now()->format('Y-m'),
        ]);
    }

    /**
     * Konfirmasi angsuran massal. Menerima id numerik campuran (normal + percepatan);
     * awalan dipetakan ulang: berhutang ke normal, lunas percepatan ke prefix p-.
     * Idempoten via middleware + header Idempotency-Key dari mobile.
     *
     * @response 200 {"message": "2 angsuran dikonfirmasi.", "total": 100000}
     */
    public function konfirmasi(Request $request): JsonResponse
    {
        if (! $request->user()->can('angsuran.konfirmasi')) {
            abort(response()->json([
                'message' => 'Anda tidak memiliki akses konfirmasi angsuran.',
            ], 403));
        }

        $request->validate([
            'angsuran_ids' => ['required', 'array', 'min:1'],
            'angsuran_ids.*' => ['integer', 'min:1'],
        ]);

        $items = $this->petakanPrefix($request->input('angsuran_ids', []));

        if ($items === []) {
            return response()->json(['message' => 'Pilih minimal satu angsuran.'], 422);
        }

        $sebelum = $this->totalTerkonfirmasi($items);
        $jumlah = $this->konfirmasi->konfirmasiMassal($items, (int) $request->user()->id);
        $total = $this->totalTerkonfirmasi($items) - $sebelum;

        return response()->json([
            'message' => "{$jumlah} angsuran dikonfirmasi.",
            'total' => $total,
        ]);
    }

    /**
     * Petakan id numerik campuran ke format service (n-{id}/p-{id}).
     * Strategi: id yang cocok di angsuran_percepatan → prefix p-, sisanya n-.
     * Urutan input dipertahankan supaya idempoten stabil.
     */
    private function petakanPrefix(array $ids): array
    {
        $bersih = collect($ids)->map(fn ($id) => (int) $id)->filter(fn ($id) => $id > 0)->values();
        if ($bersih->isEmpty()) {
            return [];
        }

        $pIds = AngsuranPercepatan::whereIn('id', $bersih)->pluck('id')->flip();

        return $bersih->map(fn ($id) => ($pIds->has($id) ? 'p-' : 'n-').$id)->all();
    }

    private function totalTerkonfirmasi(array $items): float
    {
        $normal = [];
        $percepatan = [];
        foreach ($items as $item) {
            [$prefix, $id] = explode('-', (string) $item, 2);
            $prefix === 'p' ? $percepatan[] = (int) $id : $normal[] = (int) $id;
        }

        $total = 0.0;
        if ($normal !== []) {
            $total += (float) Angsuran::whereIn('id', $normal)->where('status', 'lunas')->sum('total_bayar');
        }
        if ($percepatan !== []) {
            $total += (float) AngsuranPercepatan::whereIn('id', $percepatan)->where('status', 'lunas')->sum('total_bayar');
        }

        return $total;
    }

    private function baris(
        int $angsuranId,
        int $pinjamanId,
        string $nama,
        string $noAnggota,
        int $cicilanKe,
        float $totalBayar,
        mixed $jatuhTempo,
        bool $adaPengajuan,
    ): array {
        $tanggal = $jatuhTempo ? Carbon::parse($jatuhTempo) : null;

        return [
            'angsuran_id' => $angsuranId,
            'pinjaman_id' => $pinjamanId,
            'anggota_nama' => $nama,
            'no_anggota' => $noAnggota,
            'cicilan_ke' => $cicilanKe,
            'total_bayar' => $totalBayar,
            'tanggal_jatuh_tempo' => $tanggal?->format('Y-m-d'),
            'terlambat' => $tanggal ? $tanggal->isPast() : false,
            'diajukan_perubahan_tenor' => $adaPengajuan,
        ];
    }
}
