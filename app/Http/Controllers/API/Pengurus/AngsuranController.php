<?php

namespace App\Http\Controllers\API\Pengurus;

use App\Http\Controllers\Controller;
use App\Models\Anggota;
use App\Models\Angsuran;
use App\Models\AngsuranPercepatan;
use App\Models\PengajuanLimit;
use App\Models\PengajuanPercepatan;
use App\Models\Pinjaman;
use App\Services\Pinjaman\KonfirmasiAngsuranService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

class AngsuranController extends Controller
{
    public function __construct(private KonfirmasiAngsuranService $konfirmasi) {}

    /**
     * Antrean angsuran belum bayar + riwayat sudah dikonfirmasi bulan terkait.
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

        $request->validate([
            'bulan' => ['nullable', 'date_format:Y-m'],
            'cabang' => ['nullable', 'string', 'max:100'],
        ]);

        $bulanInput = $request->input('bulan');
        $periode = $bulanInput ?: now()->format('Y-m');
        $awalBulan = Carbon::createFromFormat('Y-m', $periode)->startOfMonth()->startOfDay()->toDateTimeString();
        $akhirBulan = Carbon::createFromFormat('Y-m', $periode)->endOfMonth()->endOfDay()->toDateTimeString();
        $cabang = trim((string) $request->input('cabang', ''));

        $pinjamanBerpengajuan = PengajuanPercepatan::whereIn('status', ['diajukan', 'approved_bendahara'])
            ->pluck('pinjaman_id')
            ->flip();

        $normalQuery = Angsuran::with('pinjaman.anggota')
            ->where('status', 'belum_bayar')
            ->whereHas('pinjaman', fn ($q) => $q->where('status', 'aktif'));
        if ($bulanInput) {
            $normalQuery->whereBetween('tanggal_jatuh_tempo', [$awalBulan, $akhirBulan]);
        }

        $normal = $normalQuery->get()
            ->map(fn ($a) => $this->baris(
                'normal',
                angsuranId: $a->id,
                pinjamanId: $a->pinjaman_id,
                nama: $a->pinjaman->anggota->nama ?? '-',
                noAnggota: $a->pinjaman->anggota->no_anggota ?? '-',
                cabang: $a->pinjaman->anggota->cabang,
                fotoUrl: $a->pinjaman->anggota->foto_url,
                cicilanKe: $a->cicilan_ke,
                totalBayar: (float) $a->total_bayar,
                jatuhTempo: $a->tanggal_jatuh_tempo,
                adaPengajuan: $pinjamanBerpengajuan->has($a->pinjaman_id),
            ));

        $percepatanQuery = AngsuranPercepatan::with('pengajuan.pinjaman.anggota')
            ->where('status', 'belum_bayar')
            ->whereHas('pengajuan.pinjaman', fn ($q) => $q->where('status', 'aktif'));
        if ($bulanInput) {
            $percepatanQuery->whereBetween('tanggal_jatuh_tempo', [$awalBulan, $akhirBulan]);
        }

        $percepatan = $percepatanQuery->get()
            ->map(fn ($a) => $this->baris(
                'percepatan',
                angsuranId: $a->id,
                pinjamanId: $a->pengajuan->pinjaman_id,
                nama: $a->pengajuan->pinjaman->anggota->nama ?? '-',
                noAnggota: $a->pengajuan->pinjaman->anggota->no_anggota ?? '-',
                cabang: $a->pengajuan->pinjaman->anggota->cabang,
                fotoUrl: $a->pengajuan->pinjaman->anggota->foto_url,
                cicilanKe: $a->cicilan_ke,
                totalBayar: (float) $a->total_bayar,
                jatuhTempo: $a->tanggal_jatuh_tempo,
                adaPengajuan: false,
            ));

        $semuaRows = $normal->concat($percepatan)
            ->sortBy('tanggal_jatuh_tempo')
            ->values();

        $tagihanPerCabang = $semuaRows
            ->groupBy(fn ($r) => $r['cabang'] ?: 'Lainnya')
            ->map(fn ($items) => (float) $items->sum('total_bayar'));

        $rows = $cabang !== ''
            ? $semuaRows->where('cabang', $cabang)->values()
            : $semuaRows;

        $semuaSudah = $this->daftarSudahDikonfirmasi($awalBulan, $akhirBulan);
        $sudahDikonfirmasi = $cabang !== ''
            ? $semuaSudah->where('cabang', $cabang)->values()
            : $semuaSudah;

        $daftarCabang = Cache::remember(
            'daftar_cabang',
            600,
            fn () => Anggota::query()->whereNotNull('cabang')->distinct()->orderBy('cabang')->pluck('cabang')->values()->all()
        );

        return response()->json([
            'data' => $rows,
            'total' => $rows->count(),
            'periode' => $periode,
            'cabang' => $cabang !== '' ? $cabang : null,
            'daftar_cabang' => $daftarCabang,
            'tagihan_per_cabang' => $tagihanPerCabang,
            'total_tagihan' => (float) $rows->sum('total_bayar'),
            'sudah_dikonfirmasi' => $sudahDikonfirmasi,
            'total_sudah_dikonfirmasi' => (float) $sudahDikonfirmasi->sum('total_bayar'),
        ]);
    }

    /**
     * Konfirmasi angsuran massal.
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
            'angsuran_ids.*' => ['required'],
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
     * Batalkan konfirmasi satu angsuran dengan jurnal pembalik (storno).
     */
    public function batalkan(Request $request, string $item): JsonResponse
    {
        if (! $request->user()->can('angsuran.konfirmasi')) {
            abort(response()->json([
                'message' => 'Anda tidak memiliki akses konfirmasi angsuran.',
            ], 403));
        }

        $request->validate([
            'alasan' => ['required', 'string', 'min:3', 'max:255'],
        ]);

        try {
            $this->konfirmasi->batalkanKonfirmasi(
                item: $item,
                alasan: trim((string) $request->alasan),
                userId: (int) $request->user()->id,
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Konfirmasi angsuran berhasil dibatalkan dengan jurnal pembalik (storno).',
        ]);
    }

    private function daftarSudahDikonfirmasi(string $awalBulan, string $akhirBulan)
    {
        $lunasNormal = Angsuran::with('pinjaman.anggota')
            ->where('status', 'lunas')
            ->whereBetween('tanggal_jatuh_tempo', [$awalBulan, $akhirBulan])
            ->get();

        $lunasPercepatan = AngsuranPercepatan::with('pengajuan.pinjaman.anggota')
            ->where('status', 'lunas')
            ->whereBetween('tanggal_jatuh_tempo', [$awalBulan, $akhirBulan])
            ->get();

        if ($lunasNormal->isEmpty() && $lunasPercepatan->isEmpty()) {
            return collect();
        }

        $pinjamanIds = $lunasNormal->pluck('pinjaman_id')
            ->merge($lunasPercepatan->map(fn ($a) => $a->pengajuan?->pinjaman_id))
            ->filter()->unique()->values();

        $anggotaIds = $lunasNormal->map(fn ($a) => $a->pinjaman?->anggota_id)
            ->merge($lunasPercepatan->map(fn ($a) => $a->pengajuan?->pinjaman?->anggota_id))
            ->filter()->unique()->values();

        $maxCicilanNormal = $pinjamanIds->isEmpty() ? collect() : Angsuran::whereIn('pinjaman_id', $pinjamanIds)
            ->where('status', 'lunas')
            ->selectRaw('pinjaman_id, MAX(cicilan_ke) as max_ke')
            ->groupBy('pinjaman_id')
            ->pluck('max_ke', 'pinjaman_id');

        $percepatanIds = $lunasPercepatan->pluck('pengajuan_percepatan_id')->unique()->values();
        $maxCicilanPercepatan = $percepatanIds->isEmpty() ? collect() : AngsuranPercepatan::whereIn('pengajuan_percepatan_id', $percepatanIds)
            ->where('status', 'lunas')
            ->selectRaw('pengajuan_percepatan_id, MAX(cicilan_ke) as max_ke')
            ->groupBy('pengajuan_percepatan_id')
            ->pluck('max_ke', 'pengajuan_percepatan_id');

        $pinjamanAdaPercepatanLunas = $pinjamanIds->isEmpty() ? collect() : PengajuanPercepatan::whereIn('pinjaman_id', $pinjamanIds)
            ->where('status', 'aktif')
            ->whereHas('angsuranBaru', fn ($q) => $q->where('status', 'lunas'))
            ->pluck('pinjaman_id')
            ->flip();

        $maxPinjamanPerAnggota = $anggotaIds->isEmpty() ? collect() : Pinjaman::whereIn('anggota_id', $anggotaIds)
            ->where('status', '!=', 'ditolak')
            ->selectRaw('anggota_id, MAX(id) as max_id')
            ->groupBy('anggota_id')
            ->pluck('max_id', 'anggota_id');

        $limitTerbaruPerAnggota = $anggotaIds->isEmpty() ? collect() : PengajuanLimit::whereIn('anggota_id', $anggotaIds)
            ->where('status', '!=', 'ditolak')
            ->selectRaw('anggota_id, MAX(created_at) as max_at')
            ->groupBy('anggota_id')
            ->pluck('max_at', 'anggota_id');

        $percepatanTerbaruPerPinjaman = $pinjamanIds->isEmpty() ? collect() : PengajuanPercepatan::whereIn('pinjaman_id', $pinjamanIds)
            ->where('status', '!=', 'ditolak')
            ->selectRaw('pinjaman_id, MAX(created_at) as max_at')
            ->groupBy('pinjaman_id')
            ->pluck('max_at', 'pinjaman_id');

        $alasanBlokir = function ($angsuran, ?Pinjaman $pinjaman, ?Anggota $anggota, bool $isPercepatan) use (
            $maxCicilanNormal,
            $maxCicilanPercepatan,
            $pinjamanAdaPercepatanLunas,
            $maxPinjamanPerAnggota,
            $limitTerbaruPerAnggota,
            $percepatanTerbaruPerPinjaman
        ): ?string {
            if (! $pinjaman || ! $anggota) {
                return 'Data pinjaman atau anggota tidak ditemukan.';
            }
            if ($anggota->status !== 'aktif') {
                return "Anggota berstatus '{$anggota->status}', konfirmasi angsuran tidak dapat dibatalkan.";
            }
            if ($isPercepatan) {
                if ((int) ($maxCicilanPercepatan[$angsuran->pengajuan_percepatan_id] ?? 0) > (int) $angsuran->cicilan_ke) {
                    return 'Terdapat cicilan berikutnya yang sudah lunas; batalkan dari cicilan terakhir terlebih dahulu.';
                }
            } else {
                if ((int) ($maxCicilanNormal[$pinjaman->id] ?? 0) > (int) $angsuran->cicilan_ke) {
                    return 'Terdapat cicilan berikutnya yang sudah lunas; batalkan dari cicilan terakhir terlebih dahulu.';
                }
                if ($pinjamanAdaPercepatanLunas->has($pinjaman->id)) {
                    return 'Terdapat cicilan perubahan tenor yang sudah lunas pada pinjaman ini.';
                }
            }
            if ((int) ($maxPinjamanPerAnggota[$anggota->id] ?? 0) > (int) $pinjaman->id) {
                return 'Anggota sudah memiliki pengajuan pinjaman baru setelah pinjaman ini.';
            }

            $waktuKonfirmasi = $angsuran->updated_at ?? $angsuran->tanggal_konfirmasi_bayar?->copy()->endOfDay();
            if ($waktuKonfirmasi) {
                $maxLimitAt = $limitTerbaruPerAnggota[$anggota->id] ?? null;
                if ($maxLimitAt && Carbon::parse($maxLimitAt)->gt($waktuKonfirmasi)) {
                    return 'Anggota sudah mengajukan kenaikan limit setelah konfirmasi angsuran ini.';
                }
                $maxPercepatanAt = $percepatanTerbaruPerPinjaman[$pinjaman->id] ?? null;
                if ($maxPercepatanAt && Carbon::parse($maxPercepatanAt)->gt($waktuKonfirmasi)) {
                    return 'Terdapat pengajuan perubahan tenor setelah konfirmasi angsuran ini.';
                }
            }

            return null;
        };

        $mapItem = function ($a, bool $isPercepatan) use ($alasanBlokir) {
            $pinjaman = $isPercepatan ? $a->pengajuan?->pinjaman : $a->pinjaman;
            $anggota = $pinjaman?->anggota;
            $blokir = $alasanBlokir($a, $pinjaman, $anggota, $isPercepatan);
            $kunci = ($isPercepatan ? 'p-' : 'n-').$a->id;

            return [
                'id' => $kunci,
                'kunci' => $kunci,
                'pinjaman_id' => $pinjaman?->id,
                'anggota_nama' => $anggota?->nama ?? '-',
                'no_anggota' => $anggota?->no_anggota ?? '-',
                'cabang' => $anggota?->cabang,
                'foto_url' => $anggota?->foto_url,
                'cicilan_ke' => (int) $a->cicilan_ke,
                'total_bayar' => (float) $a->total_bayar,
                'tanggal_jatuh_tempo' => $a->tanggal_jatuh_tempo?->format('Y-m-d'),
                'tanggal_konfirmasi_bayar' => $a->tanggal_konfirmasi_bayar?->format('Y-m-d'),
                'urutan_konfirmasi' => $a->tanggal_konfirmasi_bayar?->timestamp ?? 0,
                'is_percepatan' => $isPercepatan,
                'bisa_dibatalkan' => $blokir === null,
                'alasan_tidak_bisa_batal' => $blokir,
            ];
        };

        return $lunasNormal->map(fn ($a) => $mapItem($a, false))
            ->concat($lunasPercepatan->map(fn ($a) => $mapItem($a, true)))
            ->sortByDesc('urutan_konfirmasi')
            ->values();
    }

    private function petakanPrefix(array $ids): array
    {
        $items = [];
        foreach ($ids as $id) {
            $teks = trim((string) $id);
            if ($teks === '') {
                continue;
            }
            if (preg_match('/^[np]-(\d+)$/i', $teks, $cocok)) {
                $items[] = strtolower($teks[0]).'-'.(int) $cocok[1];
            } elseif (ctype_digit($teks) && (int) $teks > 0) {
                $items[] = 'n-'.(int) $teks;
            }
        }

        return array_values(array_unique($items));
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
        string $jenis,
        int $angsuranId,
        int $pinjamanId,
        string $nama,
        string $noAnggota,
        ?string $cabang,
        ?string $fotoUrl,
        int $cicilanKe,
        float $totalBayar,
        mixed $jatuhTempo,
        bool $adaPengajuan,
    ): array {
        $tanggal = $jatuhTempo ? Carbon::parse($jatuhTempo) : null;

        return [
            'jenis' => $jenis,
            'angsuran_id' => $angsuranId,
            'kunci' => ($jenis === 'percepatan' ? 'p-' : 'n-').$angsuranId,
            'pinjaman_id' => $pinjamanId,
            'anggota_nama' => $nama,
            'no_anggota' => $noAnggota,
            'cabang' => $cabang,
            'foto_url' => $fotoUrl,
            'cicilan_ke' => $cicilanKe,
            'total_bayar' => $totalBayar,
            'tanggal_jatuh_tempo' => $tanggal?->format('Y-m-d'),
            'terlambat' => $tanggal ? $tanggal->isPast() : false,
            'diajukan_perubahan_tenor' => $adaPengajuan,
        ];
    }
}
