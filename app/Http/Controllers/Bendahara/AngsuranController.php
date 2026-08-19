<?php

namespace App\Http\Controllers\Bendahara;

use App\Http\Controllers\Controller;
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

        $daftarAngsuran = Angsuran::with('pinjaman.anggota')
            ->where('status', 'belum_bayar')
            ->whereYear('tanggal_jatuh_tempo', $tahun)
            ->whereMonth('tanggal_jatuh_tempo', $bulanAngka)
            ->orderBy('tanggal_jatuh_tempo')
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'pinjaman_id' => $a->pinjaman_id,
                'nama' => $a->pinjaman->anggota->nama,
                'no_anggota' => $a->pinjaman->anggota->no_anggota,
                'cicilan_ke' => $a->cicilan_ke,
                'nominal_pokok' => (float) $a->nominal_pokok,
                'nominal_bunga' => (float) $a->nominal_bunga,
                'total_bayar' => (float) $a->total_bayar,
                'tanggal_jatuh_tempo' => $a->tanggal_jatuh_tempo->format('d M Y'),
                'terlambat' => $a->tanggal_jatuh_tempo->isPast(),
            ]);

        $totalTagihanBulanIni = $daftarAngsuran->sum('total_bayar');

        $totalKeuntunganBulanIni = Angsuran::where('status', 'lunas')
            ->whereYear('tanggal_konfirmasi_bayar', $tahun)
            ->whereMonth('tanggal_konfirmasi_bayar', $bulanAngka)
            ->sum('nominal_bunga');

        $totalKeuntunganKeseluruhan = Angsuran::where('status', 'lunas')->sum('nominal_bunga');

        $peringatanPercepatan = PengajuanPercepatan::whereIn('status', ['diajukan', 'approved_bendahara'])
            ->pluck('pinjaman_id')
            ->all();

        $daftarAngsuranPercepatan = AngsuranPercepatan::with('pengajuanPercepatan.pinjaman.anggota')
            ->where('status', 'belum_bayar')
            ->whereYear('tanggal_jatuh_tempo', $tahun)
            ->whereMonth('tanggal_jatuh_tempo', $bulanAngka)
            ->orderBy('tanggal_jatuh_tempo')
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'pinjaman_id' => $a->pengajuanPercepatan->pinjaman_id,
                'nama' => $a->pengajuanPercepatan->pinjaman->anggota->nama,
                'no_anggota' => $a->pengajuanPercepatan->pinjaman->anggota->no_anggota,
                'cicilan_ke' => $a->cicilan_ke,
                'nominal_pokok' => (float) $a->nominal_pokok,
                'nominal_bunga' => (float) $a->nominal_bunga,
                'total_bayar' => (float) $a->total_bayar,
                'tanggal_jatuh_tempo' => $a->tanggal_jatuh_tempo->format('d M Y'),
                'terlambat' => $a->tanggal_jatuh_tempo->isPast(),
                'tipe' => $a->pengajuanPercepatan->tipe,
            ]);

        return Inertia::render('Bendahara/Angsuran/Index', [
            'bulan' => $bulan,
            'daftarAngsuran' => $daftarAngsuran,
            'daftarAngsuranPercepatan' => $daftarAngsuranPercepatan,
            'peringatanPercepatan' => $peringatanPercepatan,
            'totalTagihanBulanIni' => (float) $totalTagihanBulanIni,
            'totalKeuntunganBulanIni' => (float) $totalKeuntunganBulanIni,
            'totalKeuntunganKeseluruhan' => (float) $totalKeuntunganKeseluruhan,
        ]);
    }

    public function konfirmasi(Request $request)
    {
        $request->validate([
            'angsuran_ids' => ['required', 'array', 'min:1'],
            'angsuran_ids.*' => ['integer', 'exists:angsuran,id'],
        ]);

        $jumlah = $this->konfirmasi->konfirmasiMassal($request->angsuran_ids, auth()->id());

        return back()->with('status', "{$jumlah} angsuran berhasil dikonfirmasi lunas.");
    }

    public function konfirmasiPercepatan(Request $request)
    {
        $request->validate([
            'angsuran_percepatan_ids' => ['required', 'array', 'min:1'],
            'angsuran_percepatan_ids.*' => ['integer', 'exists:angsuran_percepatan,id'],
        ]);

        $jumlah = $this->konfirmasi->konfirmasiMassalPercepatan($request->angsuran_percepatan_ids, auth()->id());

        return back()->with('status', "{$jumlah} tagihan percepatan berhasil dikonfirmasi lunas.");
    }
}
