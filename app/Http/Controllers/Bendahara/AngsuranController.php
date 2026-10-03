<?php

namespace App\Http\Controllers\Bendahara;

use App\Http\Controllers\Controller;
use App\Models\Anggota;
use App\Models\Angsuran;
use App\Models\AngsuranPercepatan;
use App\Models\PengajuanPercepatan;
use App\Services\Pinjaman\KonfirmasiAngsuranService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

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

        $daftarCabang = Cache::remember('daftar_cabang', 600, fn () => Anggota::query()->whereNotNull('cabang')->distinct()->orderBy('cabang')->pluck('cabang'));

        $totalPendapatanBungaBulanIni = Angsuran::where('status', 'lunas')
            ->whereBetween('tanggal_konfirmasi_bayar', [$awalBulan, $akhirBulan])
            ->sum('nominal_bunga')
            + AngsuranPercepatan::where('status', 'lunas')
                ->whereBetween('tanggal_konfirmasi_bayar', [$awalBulan, $akhirBulan])
                ->sum('nominal_bunga');

        $totalPendapatanBungaKeseluruhan = Angsuran::where('status', 'lunas')->sum('nominal_bunga')
            + AngsuranPercepatan::where('status', 'lunas')->sum('nominal_bunga');

        return Inertia::render('Bendahara/Angsuran/Index', [
            'bulan' => $bulan,
            'daftarAngsuran' => $daftarAngsuran,
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
}
