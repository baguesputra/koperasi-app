<?php

namespace App\Http\Controllers\Bendahara;

use App\Http\Controllers\Controller;
use App\Models\Anggota;
use App\Models\Angsuran;
use App\Models\AngsuranPercepatan;
use App\Models\PengajuanPercepatan;
use App\Services\Pinjaman\KonfirmasiAngsuranService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AngsuranController extends Controller
{
    public function __construct(private KonfirmasiAngsuranService $konfirmasi) {}

    public function index(Request $request): Response
    {
        $bulan = $request->input('bulan', now()->format('Y-m'));
        [$tahun, $bulanAngka] = explode('-', $bulan);

        $cabangAktif = $request->string('cabang');

        $semuaNormal = Angsuran::with('pinjaman.anggota')
            ->where('status', 'belum_bayar')
            ->whereYear('tanggal_jatuh_tempo', $tahun)->whereMonth('tanggal_jatuh_tempo', $bulanAngka)
            ->get()
            ->map(function ($a) {
                $adaPengajuan = PengajuanPercepatan::where('pinjaman_id', $a->pinjaman_id)
                    ->whereIn('status', ['diajukan', 'approved_bendahara'])->exists();

                return [
                    'id' => 'n-'.$a->id,
                    'nama' => $a->pinjaman->anggota->nama,
                    'no_anggota' => $a->pinjaman->anggota->no_anggota,
                    'cabang' => $a->pinjaman->anggota->cabang,
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
            ->whereYear('tanggal_jatuh_tempo', $tahun)->whereMonth('tanggal_jatuh_tempo', $bulanAngka)
            ->get()
            ->map(function ($a) {
                $pinjaman = $a->pengajuan->pinjaman;

                return [
                    'id' => 'p-'.$a->id,
                    'nama' => $pinjaman->anggota->nama,
                    'no_anggota' => $pinjaman->anggota->no_anggota,
                    'cabang' => $pinjaman->anggota->cabang,
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

        $daftarCabang = Anggota::query()->whereNotNull('cabang')->distinct()->orderBy('cabang')->pluck('cabang');

        $totalKeuntunganBulanIni = Angsuran::where('status', 'lunas')
            ->whereYear('tanggal_konfirmasi_bayar', $tahun)->whereMonth('tanggal_konfirmasi_bayar', $bulanAngka)
            ->sum('nominal_bunga')
            + AngsuranPercepatan::where('status', 'lunas')
                ->whereYear('tanggal_konfirmasi_bayar', $tahun)->whereMonth('tanggal_konfirmasi_bayar', $bulanAngka)
                ->sum('nominal_bunga');

        $totalKeuntunganKeseluruhan = Angsuran::where('status', 'lunas')->sum('nominal_bunga')
            + AngsuranPercepatan::where('status', 'lunas')->sum('nominal_bunga');

        return Inertia::render('Bendahara/Angsuran/Index', [
            'bulan' => $bulan,
            'daftarAngsuran' => $daftarAngsuran,
            'cabangAktif' => $cabangAktif->value(),
            'daftarCabang' => $daftarCabang,
            'tagihanPerCabang' => $tagihanPerCabang,
            'totalTagihanBulanIni' => (float) $totalTagihanBulanIni,
            'totalKeuntunganBulanIni' => (float) $totalKeuntunganBulanIni,
            'totalKeuntunganKeseluruhan' => (float) $totalKeuntunganKeseluruhan,
        ]);
    }

    public function konfirmasi(Request $request)
    {
        $request->validate(['angsuran_ids' => ['required', 'array', 'min:1']]);

        $jumlah = $this->konfirmasi->konfirmasiMassal($request->angsuran_ids, auth()->id());

        return back()->with('status', "{$jumlah} angsuran berhasil dikonfirmasi lunas.");
    }
}
