<?php

namespace App\Http\Controllers\Bendahara;

use App\Http\Controllers\Controller;
use App\Models\Anggota;
use App\Models\Angsuran;
use App\Models\AngsuranPercepatan;
use App\Models\PengajuanLimit;
use App\Models\PengajuanPercepatan;
use App\Models\Pinjaman;
use App\Services\Pinjaman\KonfirmasiAngsuranService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class AngsuranController extends Controller
{
    public function __construct(private KonfirmasiAngsuranService $konfirmasi) {}

    public function index(Request $request): Response
    {
        $bulan = $request->input('bulan', now()->format('Y-m'));
        $awalBulan = Carbon::createFromFormat('Y-m', $bulan)->startOfMonth()->startOfDay()->toDateTimeString();
        $akhirBulan = Carbon::createFromFormat('Y-m', $bulan)->endOfMonth()->endOfDay()->toDateTimeString();

        $cabangAktif = $request->string('cabang');

        // Prefetch 1x: pinjaman yang punya pengajuan percepatan berjalan (anti N+1 di map bawah)
        $pinjamanBerpengajuan = PengajuanPercepatan::whereIn('status', ['diajukan', 'approved_bendahara'])
            ->pluck('pinjaman_id')
            ->flip();

        $semuaNormal = Angsuran::with('pinjaman.anggota')
            ->where('status', 'belum_bayar')
            ->whereBetween('tanggal_jatuh_tempo', [$awalBulan, $akhirBulan])
            ->get()
            ->map(function ($a) use ($pinjamanBerpengajuan) {
                $adaPengajuan = $pinjamanBerpengajuan->has($a->pinjaman_id);

                return [
                    'id' => 'n-'.$a->id,
                    'nama' => $a->pinjaman->anggota->nama,
                    'no_anggota' => $a->pinjaman->anggota->no_anggota,
                    'no_karyawan' => $a->pinjaman->anggota->no_karyawan,
                    'cabang' => $a->pinjaman->anggota->cabang,
                    'foto_url' => $a->pinjaman->anggota->foto_url,
                    'cicilan_ke' => $a->cicilan_ke,
                    'total_bayar' => (float) $a->total_bayar,
                    'nominal_bunga' => (float) $a->nominal_bunga,
                    'tanggal_jatuh_tempo' => $a->tanggal_jatuh_tempo->format('d M Y'),
                    'terlambat' => $a->tanggal_jatuh_tempo->isPast(),
                    'ada_pengajuan_percepatan' => $adaPengajuan,
                ];
            });

        $semuaPercepatan = AngsuranPercepatan::with('pengajuan.pinjaman.anggota')
            ->where('status', 'belum_bayar')
            ->whereBetween('tanggal_jatuh_tempo', [$awalBulan, $akhirBulan])
            ->get()
            ->map(function ($a) {
                $pinjaman = $a->pengajuan->pinjaman;

                return [
                    'id' => 'p-'.$a->id,
                    'nama' => $pinjaman->anggota->nama,
                    'no_anggota' => $pinjaman->anggota->no_anggota,
                    'no_karyawan' => $pinjaman->anggota->no_karyawan,
                    'cabang' => $pinjaman->anggota->cabang,
                    'foto_url' => $pinjaman->anggota->foto_url,
                    'cicilan_ke' => $a->cicilan_ke,
                    'total_bayar' => (float) $a->total_bayar,
                    'nominal_bunga' => (float) $a->nominal_bunga,
                    'tanggal_jatuh_tempo' => $a->tanggal_jatuh_tempo->format('d M Y'),
                    'terlambat' => $a->tanggal_jatuh_tempo->isPast(),
                    'ada_pengajuan_percepatan' => false,
                ];
            });

        $semuaAngsuran = $semuaNormal->concat($semuaPercepatan)->sortBy('tanggal_jatuh_tempo')->values();

        $daftarAngsuran = $cabangAktif->isNotEmpty()
            ? $semuaAngsuran->where('cabang', $cabangAktif)->values()
            : $semuaAngsuran;

        $totalTagihanBulanIni = $semuaAngsuran->sum('total_bayar');

        $tagihanPerCabang = $semuaAngsuran
            ->groupBy('cabang')
            ->map(fn ($items) => (float) $items->sum('total_bayar'));

        $daftarCabang = Cache::remember('daftar_cabang', 600, fn () => Anggota::query()->whereNotNull('cabang')->distinct()->orderBy('cabang')->pluck('cabang')->values()->all());

        $totalPendapatanBungaBulanIni = Angsuran::where('status', 'lunas')
            ->whereBetween('tanggal_konfirmasi_bayar', [$awalBulan, $akhirBulan])
            ->sum('nominal_bunga')
            + AngsuranPercepatan::where('status', 'lunas')
                ->whereBetween('tanggal_konfirmasi_bayar', [$awalBulan, $akhirBulan])
                ->sum('nominal_bunga');

        $totalPendapatanBungaKeseluruhan = Angsuran::where('status', 'lunas')->sum('nominal_bunga')
            + AngsuranPercepatan::where('status', 'lunas')->sum('nominal_bunga');

        $semuaSudah = $this->daftarSudahDikonfirmasi($awalBulan, $akhirBulan);
        $sudahDikonfirmasi = $cabangAktif->isNotEmpty()
            ? $semuaSudah->where('cabang', $cabangAktif)->values()
            : $semuaSudah;

        return Inertia::render('Bendahara/Angsuran/Index', [
            'bulan' => $bulan,
            'daftarAngsuran' => $daftarAngsuran,
            'sudahDikonfirmasi' => $sudahDikonfirmasi,
            'totalSudahDikonfirmasi' => (float) $semuaSudah->sum('total_bayar'),
            'cabangAktif' => $cabangAktif->value(),
            'daftarCabang' => $daftarCabang,
            'tagihanPerCabang' => $tagihanPerCabang,
            'totalTagihanBulanIni' => (float) $totalTagihanBulanIni,
            'totalPendapatanBungaBulanIni' => (float) $totalPendapatanBungaBulanIni,
            'totalPendapatanBungaKeseluruhan' => (float) $totalPendapatanBungaKeseluruhan,
        ]);
    }

    public function konfirmasi(Request $request)
    {
        $request->validate(['angsuran_ids' => ['required', 'array', 'min:1']]);

        $jumlah = $this->konfirmasi->konfirmasiMassal($request->angsuran_ids, auth()->id());

        return back()->with('status', "{$jumlah} angsuran berhasil dikonfirmasi lunas.");
    }

    public function batalkan(Request $request, string $item)
    {
        $request->validate([
            'alasan' => ['required', 'string', 'min:3', 'max:255'],
        ]);

        try {
            $this->konfirmasi->batalkanKonfirmasi(
                item: $item,
                alasan: trim((string) $request->alasan),
                userId: auth()->id(),
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['alasan' => $e->getMessage()]);
        }

        return back()->with('status', 'Konfirmasi angsuran berhasil dibatalkan dengan jurnal pembalik (storno).');
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
            ->filter()
            ->unique()
            ->values();

        $anggotaIds = $lunasNormal->map(fn ($a) => $a->pinjaman?->anggota_id)
            ->merge($lunasPercepatan->map(fn ($a) => $a->pengajuan?->pinjaman?->anggota_id))
            ->filter()
            ->unique()
            ->values();

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

            return [
                'id' => ($isPercepatan ? 'p-' : 'n-').$a->id,
                'nama' => $anggota?->nama ?? '-',
                'no_anggota' => $anggota?->no_anggota ?? '-',
                'no_karyawan' => $anggota?->no_karyawan ?? '-',
                'cabang' => $anggota?->cabang ?? '-',
                'foto_url' => $anggota?->foto_url,
                'cicilan_ke' => $a->cicilan_ke,
                'nominal_pokok' => (float) $a->nominal_pokok,
                'nominal_bunga' => (float) $a->nominal_bunga,
                'total_bayar' => (float) $a->total_bayar,
                'tanggal_jatuh_tempo' => $a->tanggal_jatuh_tempo->format('d M Y'),
                'tanggal_konfirmasi_bayar' => $a->tanggal_konfirmasi_bayar?->format('d M Y'),
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
}
