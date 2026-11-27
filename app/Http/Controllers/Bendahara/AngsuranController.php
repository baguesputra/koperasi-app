<?php

namespace App\Http\Controllers\Bendahara;

use App\Http\Controllers\Controller;
use App\Models\Anggota;
use App\Models\Angsuran;
use App\Models\AngsuranPercepatan;
use App\Models\PengajuanLimit;
use App\Models\PengajuanPenghapusanPinjaman;
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
        $bulan = (string) $request->input('bulan', now()->format('Y-m'));
        if (! preg_match('/^\d{4}-\d{2}$/', $bulan)) {
            $bulan = now()->format('Y-m');
        }

        $awalBulan = Carbon::createFromFormat('Y-m', $bulan)->startOfMonth()->toDateString();
        $akhirBulan = Carbon::createFromFormat('Y-m', $bulan)->endOfMonth()->endOfDay()->toDateTimeString();

        $bulanIni = now()->format('Y-m');
        $isBulanBerjalan = $bulan === $bulanIni;
        $isBulanLalu = $bulan < $bulanIni;
        $isBulanDepan = $bulan > $bulanIni;

        $cabangAktif = $request->string('cabang');

        // Prefetch 1x: pinjaman yang punya pengajuan percepatan atau penghapusan berjalan (anti N+1)
        $pinjamanBerpengajuan = PengajuanPercepatan::whereIn('status', ['diajukan', 'approved_bendahara'])
            ->pluck('pinjaman_id')
            ->flip();

        $pinjamanPenghapusanAktif = PengajuanPenghapusanPinjaman::whereNotIn('status', ['ditolak', 'disetujui'])
            ->pluck('pinjaman_id')
            ->flip();

        $unpaidEarliestByLoan = $this->petaCicilanTerawalBelumBayar();

        if ($isBulanLalu) {
            $semuaAngsuran = collect();
            $semuaTerlambat = collect();
            $dipindahSemua = $this->ambilBelumBayar(
                $awalBulan,
                $akhirBulan,
                false,
                $pinjamanBerpengajuan,
                $pinjamanPenghapusanAktif,
                $unpaidEarliestByLoan,
                false
            );
            $dipindahTampil = $cabangAktif->isNotEmpty()
                ? $dipindahSemua->where('cabang', $cabangAktif)->values()
                : $dipindahSemua;
            $dipindahKeBulanIni = [
                'jumlah' => $dipindahTampil->count(),
                'nominal' => (float) $dipindahTampil->sum('total_bayar'),
            ];
        } else {
            $semuaAngsuran = $this->ambilBelumBayar(
                $awalBulan,
                $akhirBulan,
                false,
                $pinjamanBerpengajuan,
                $pinjamanPenghapusanAktif,
                $unpaidEarliestByLoan,
                $isBulanDepan
            );
            $semuaTerlambat = $isBulanBerjalan
                ? $this->ambilBelumBayar(
                    null,
                    now()->startOfMonth()->toDateString(),
                    true,
                    $pinjamanBerpengajuan,
                    $pinjamanPenghapusanAktif,
                    $unpaidEarliestByLoan,
                    false
                )
                : collect();
            $dipindahKeBulanIni = ['jumlah' => 0, 'nominal' => 0.0];
        }

        $daftarAngsuran = $cabangAktif->isNotEmpty()
            ? $semuaAngsuran->where('cabang', $cabangAktif)->values()
            : $semuaAngsuran;

        $daftarTerlambat = $cabangAktif->isNotEmpty()
            ? $semuaTerlambat->where('cabang', $cabangAktif)->values()
            : $semuaTerlambat;

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

        $semuaSudah = $this->daftarSudahDikonfirmasi($awalBulan, $akhirBulan, $bulan, $pinjamanPenghapusanAktif);
        $sudahDikonfirmasi = $cabangAktif->isNotEmpty()
            ? $semuaSudah->where('cabang', $cabangAktif)->values()
            : $semuaSudah;

        $tabAwal = in_array($request->input('tab'), ['belum', 'terlambat', 'sudah'], true)
            ? (string) $request->input('tab')
            : ($isBulanLalu ? 'sudah' : 'belum');

        return Inertia::render('Bendahara/Angsuran/Index', [
            'bulan' => $bulan,
            'bulanIni' => $bulanIni,
            'isBulanDepan' => $isBulanDepan,
            'tabAwal' => $tabAwal,
            'daftarAngsuran' => $daftarAngsuran,
            'daftarTerlambat' => $daftarTerlambat,
            'sudahDikonfirmasi' => $sudahDikonfirmasi,
            'totalSudahDikonfirmasi' => (float) $semuaSudah->sum('total_bayar'),
            'cabangAktif' => $cabangAktif->value(),
            'daftarCabang' => $daftarCabang,
            'tagihanPerCabang' => $tagihanPerCabang,
            'totalTagihanBulanIni' => (float) $totalTagihanBulanIni,
            'totalTagihanTerlambat' => (float) $semuaTerlambat->sum('total_bayar'),
            'dipindahKeBulanIni' => $dipindahKeBulanIni,
            'totalPendapatanBungaBulanIni' => (float) $totalPendapatanBungaBulanIni,
            'totalPendapatanBungaKeseluruhan' => (float) $totalPendapatanBungaKeseluruhan,
        ]);
    }

    public function konfirmasi(Request $request)
    {
        $request->validate(['angsuran_ids' => ['required', 'array', 'min:1']]);

        try {
            $jumlah = $this->konfirmasi->konfirmasiMassal($request->angsuran_ids, auth()->id());
        } catch (RuntimeException $e) {
            return back()->withErrors(['konfirmasi' => $e->getMessage()]);
        }

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

    /**
     * Peta cicilan belum bayar paling awal per pinjaman aktif (lintas jadwal normal -> percepatan)
     * agar UI dapat menandai baris yang terkunci dan memicu konfirmasi berurutan.
     *
     * @return array<int, array{key: string, is_percepatan: int, cicilan_ke: int, jatuh_tempo: string, order_by_key: array<string, int>}>
     */
    private function petaCicilanTerawalBelumBayar(): array
    {
        $perPinjaman = [];

        $normal = Angsuran::where('status', 'belum_bayar')
            ->whereHas('pinjaman', fn ($q) => $q->where('status', 'aktif'))
            ->get(['id', 'pinjaman_id', 'cicilan_ke', 'tanggal_jatuh_tempo']);

        foreach ($normal as $a) {
            $perPinjaman[$a->pinjaman_id][] = [
                'key' => 'n-'.$a->id,
                'is_percepatan' => 0,
                'cicilan_ke' => (int) $a->cicilan_ke,
                'sort_date' => $a->tanggal_jatuh_tempo?->format('Y-m-d') ?? '9999-12-31',
                'jatuh_tempo' => $a->tanggal_jatuh_tempo?->translatedFormat('d M Y') ?? '-',
            ];
        }

        $percepatan = AngsuranPercepatan::with('pengajuan:id,pinjaman_id')
            ->where('status', 'belum_bayar')
            ->whereHas('pengajuan', fn ($q) => $q->where('status', 'aktif')
                ->whereHas('pinjaman', fn ($pq) => $pq->where('status', 'aktif')))
            ->get(['id', 'pengajuan_percepatan_id', 'cicilan_ke', 'tanggal_jatuh_tempo']);

        foreach ($percepatan as $a) {
            $pid = $a->pengajuan?->pinjaman_id;
            if (! $pid) {
                continue;
            }
            $perPinjaman[$pid][] = [
                'key' => 'p-'.$a->id,
                'is_percepatan' => 1,
                'cicilan_ke' => (int) $a->cicilan_ke,
                'sort_date' => $a->tanggal_jatuh_tempo?->format('Y-m-d') ?? '9999-12-31',
                'jatuh_tempo' => $a->tanggal_jatuh_tempo?->translatedFormat('d M Y') ?? '-',
            ];
        }

        $hasil = [];
        foreach ($perPinjaman as $pid => $items) {
            usort($items, function (array $a, array $b) {
                return [$a['is_percepatan'], $a['cicilan_ke'], $a['sort_date'], $a['key']]
                    <=> [$b['is_percepatan'], $b['cicilan_ke'], $b['sort_date'], $b['key']];
            });
            $orderByKey = [];
            foreach ($items as $idx => $slot) {
                $orderByKey[$slot['key']] = $idx;
            }
            $hasil[$pid] = [
                'key' => $items[0]['key'],
                'cicilan_ke' => $items[0]['cicilan_ke'],
                'jatuh_tempo' => $items[0]['jatuh_tempo'],
                'order_by_key' => $orderByKey,
            ];
        }

        return $hasil;
    }

    private function ambilBelumBayar(
        ?string $dari,
        ?string $sampai,
        bool $lebihKecilDari,
        $pinjamanBerpengajuan,
        $pinjamanPenghapusanAktif,
        array $unpaidEarliestByLoan,
        bool $isBulanDepan,
    ) {
        $awalBulanIni = now()->startOfMonth();

        $normalQuery = Angsuran::with('pinjaman.anggota')
            ->where('status', 'belum_bayar')
            ->whereHas('pinjaman', fn ($q) => $q->where('status', 'aktif'));

        $percepatanQuery = AngsuranPercepatan::with('pengajuan.pinjaman.anggota')
            ->where('status', 'belum_bayar')
            ->whereHas('pengajuan', fn ($q) => $q->where('status', 'aktif')
                ->whereHas('pinjaman', fn ($pq) => $pq->where('status', 'aktif')));

        if ($lebihKecilDari) {
            $normalQuery->where('tanggal_jatuh_tempo', '<', $sampai);
            $percepatanQuery->where('tanggal_jatuh_tempo', '<', $sampai);
        } else {
            $normalQuery->whereBetween('tanggal_jatuh_tempo', [$dari, $sampai]);
            $percepatanQuery->whereBetween('tanggal_jatuh_tempo', [$dari, $sampai]);
        }

        $bangunBaris = function ($a, bool $isPercepatan) use (
            $pinjamanBerpengajuan,
            $pinjamanPenghapusanAktif,
            $unpaidEarliestByLoan,
            $isBulanDepan,
            $lebihKecilDari,
            $awalBulanIni
        ) {
            $pinjaman = $isPercepatan ? $a->pengajuan?->pinjaman : $a->pinjaman;
            $pinjamanId = (int) ($pinjaman?->id ?? 0);
            $anggota = $pinjaman?->anggota;
            $tgl = $a->tanggal_jatuh_tempo;
            $kunci = ($isPercepatan ? 'p-' : 'n-').$a->id;

            $adaPercepatan = $pinjamanBerpengajuan->has($pinjamanId);
            $adaPenghapusan = $pinjamanPenghapusanAktif->has($pinjamanId);
            $earliest = $unpaidEarliestByLoan[$pinjamanId] ?? null;
            $urutanTunggakan = $earliest['order_by_key'][$kunci] ?? 0;

            $alasanTerkunci = null;
            $tunggakanSebelumnya = null;

            if ($isBulanDepan) {
                $alasanTerkunci = 'Periode mendatang belum dapat dikonfirmasi.';
            } elseif ($adaPenghapusan) {
                $alasanTerkunci = 'Sedang dalam pengajuan penghapusan pinjaman.';
            } elseif ($adaPercepatan) {
                $alasanTerkunci = 'Menunggu persetujuan perubahan tenor/pelunasan.';
            } elseif (! $lebihKecilDari && $earliest !== null && $earliest['key'] !== $kunci) {
                $tunggakanSebelumnya = [
                    'cicilan_ke' => $earliest['cicilan_ke'],
                    'jatuh_tempo' => $earliest['jatuh_tempo'],
                ];
                $alasanTerkunci = "Selesaikan angsuran ke-{$earliest['cicilan_ke']} terlebih dahulu.";
            }

            return [
                'id' => $kunci,
                'pinjaman_id' => $pinjamanId,
                'user_id_pemilik' => $anggota?->user_id,
                'nama' => $anggota?->nama ?? '-',
                'no_anggota' => $anggota?->no_anggota ?? '-',
                'no_karyawan' => $anggota?->no_karyawan ?? '-',
                'cabang' => $anggota?->cabang,
                'foto_url' => $anggota?->foto_url,
                'cicilan_ke' => $a->cicilan_ke,
                'total_bayar' => (float) $a->total_bayar,
                'nominal_bunga' => (float) $a->nominal_bunga,
                'tanggal_jatuh_tempo' => $tgl?->format('d M Y') ?? '-',
                'tanggal_jatuh_tempo_iso' => $tgl?->format('Y-m-d') ?? '',
                'periode_asal' => $tgl?->format('Y-m') ?? '',
                'periode_asal_label' => $tgl?->translatedFormat('M Y') ?? '-',
                'terlambat' => $tgl ? ($tgl->lt($awalBulanIni) || $tgl->isPast()) : false,
                'ada_pengajuan_percepatan' => $adaPercepatan,
                'ada_pengajuan_penghapusan' => $adaPenghapusan,
                'tunggakan_sebelumnya' => $tunggakanSebelumnya,
                'urutan_tunggakan' => $urutanTunggakan,
                'bisa_dipilih' => $alasanTerkunci === null,
                'alasan_terkunci' => $alasanTerkunci,
            ];
        };

        $semuaNormal = $normalQuery->get()->map(fn ($a) => $bangunBaris($a, false));
        $semuaPercepatan = $percepatanQuery->get()->map(fn ($a) => $bangunBaris($a, true));

        return $semuaNormal->concat($semuaPercepatan)
            ->sortBy([
                ['tanggal_jatuh_tempo_iso', 'asc'],
                ['nama', 'asc'],
                ['cicilan_ke', 'asc'],
            ])
            ->values();
    }

    private function daftarSudahDikonfirmasi(string $awalBulan, string $akhirBulan, string $bulan, $pinjamanPenghapusanAktif)
    {
        $lunasNormal = Angsuran::with('pinjaman.anggota')
            ->where('status', 'lunas')
            ->where(function ($q) use ($awalBulan, $akhirBulan) {
                $q->whereBetween('tanggal_jatuh_tempo', [$awalBulan, $akhirBulan])
                    ->orWhereBetween('tanggal_konfirmasi_bayar', [$awalBulan, $akhirBulan]);
            })
            ->get();

        $lunasPercepatan = AngsuranPercepatan::with('pengajuan.pinjaman.anggota')
            ->where('status', 'lunas')
            ->where(function ($q) use ($awalBulan, $akhirBulan) {
                $q->whereBetween('tanggal_jatuh_tempo', [$awalBulan, $akhirBulan])
                    ->orWhereBetween('tanggal_konfirmasi_bayar', [$awalBulan, $akhirBulan]);
            })
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
            $percepatanTerbaruPerPinjaman,
            $pinjamanPenghapusanAktif
        ): ?string {
            if (! $pinjaman || ! $anggota) {
                return 'Data pinjaman atau anggota tidak ditemukan.';
            }
            if ($anggota->status !== 'aktif') {
                return "Anggota berstatus '{$anggota->status}', konfirmasi angsuran tidak dapat dibatalkan.";
            }
            if ($pinjamanPenghapusanAktif->has($pinjaman->id)) {
                return 'Pinjaman sedang dalam proses pengajuan penghapusan.';
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

        $mapItem = function ($a, bool $isPercepatan) use ($alasanBlokir, $bulan) {
            $pinjaman = $isPercepatan ? $a->pengajuan?->pinjaman : $a->pinjaman;
            $anggota = $pinjaman?->anggota;
            $blokir = $alasanBlokir($a, $pinjaman, $anggota, $isPercepatan);
            $tglJatuhTempo = $a->tanggal_jatuh_tempo;
            $periodeAsal = $tglJatuhTempo?->format('Y-m') ?? '';

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
                'tanggal_jatuh_tempo' => $tglJatuhTempo?->format('d M Y') ?? '-',
                'periode_asal' => $periodeAsal,
                'periode_asal_label' => $tglJatuhTempo?->translatedFormat('M Y') ?? '-',
                'dari_bulan_lain' => $periodeAsal !== '' && $periodeAsal !== $bulan,
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
