<?php

namespace App\Http\Controllers;

use App\Models\Anggota;
use App\Models\Angsuran;
use App\Models\JurnalKas;
use App\Models\KasKoperasi;
use App\Models\PengajuanLimit;
use App\Models\PengajuanPercepatan;
use App\Models\Pinjaman;
use App\Models\Simpanan;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response|RedirectResponse
    {
        if (auth()->user()->hasRole('anggota')) {
            return redirect()->route('portal.dashboard');
        }

        $kas = KasKoperasi::first();

        $keuntunganBulanIni = Angsuran::where('status', 'lunas')
            ->whereYear('tanggal_konfirmasi_bayar', now()->year)
            ->whereMonth('tanggal_konfirmasi_bayar', now()->month)
            ->sum('nominal_bunga');

        $totalSimpananPokokWajib = Simpanan::whereIn('jenis', ['pokok', 'wajib'])->sum('jumlah');

        $saldoDanaPinjaman = $kas->saldo_pinjaman;

        $totalKeseluruhan = $saldoDanaPinjaman + $kas->saldo_dana_sosial + $totalSimpananPokokWajib;

        $saldoDanaSosial = $kas->saldo_dana_sosial;

        // Actionable items - dipisah per tahap, bukan digabung
        $menungguTinjauanBendahara = Pinjaman::where('status', 'diajukan')->count();
        $menungguApprovalKetua = Pinjaman::where('status', 'approved_bendahara')->count();

        $menungguPerubahanTenor = PengajuanPercepatan::whereIn('status', ['diajukan', 'approved_bendahara'])->count();

        $menungguPengajuanLimit = PengajuanLimit::where('status', 'diajukan')->count();

        $anggotaBelumSimpananBulanIni = Anggota::where('status', 'aktif')
            ->whereDoesntHave('simpanan', fn ($q) => $q
                ->where('jenis', 'wajib')
                ->where('bulan_periode', now()->format('Y-m'))
            )->count();

        $angsuranJatuhTempoBulanIni = Angsuran::where('status', 'belum_bayar')
            ->whereYear('tanggal_jatuh_tempo', now()->year)
            ->whereMonth('tanggal_jatuh_tempo', now()->month)
            ->count();

        // Grafik - tren 6 bulan terakhir
        $labelBulan = [];
        $dataSimpanan = [];
        $dataPinjaman = [];

        for ($i = 5; $i >= 0; $i--) {
            $bulan = now()->subMonths($i);
            $labelBulan[] = $bulan->translatedFormat('M Y');

            $dataSimpanan[] = (float) Simpanan::whereYear('tanggal_input', $bulan->year)
                ->whereMonth('tanggal_input', $bulan->month)
                ->sum('jumlah');

            $dataPinjaman[] = (float) Pinjaman::whereYear('tanggal_pencairan', $bulan->year)
                ->whereMonth('tanggal_pencairan', $bulan->month)
                ->sum('nominal');
        }

        $grafikTren = collect($labelBulan)->map(fn ($label, $i) => [
            'bulan' => $label,
            'simpanan' => $dataSimpanan[$i],
            'pinjaman' => $dataPinjaman[$i],
        ]);

        // Mutasi kas - per kategori (jurnal) & dana sosial (simpanan), 6 bulan terakhir
        $dataTopup = [];
        $dataAngsuran = [];
        $dataPencairan = [];
        $dataDanaSosial = [];

        for ($i = 5; $i >= 0; $i--) {
            $bulan = now()->subMonths($i);

            $dataTopup[] = (float) JurnalKas::where('kategori', 'topup_bulanan')
                ->whereYear('tanggal', $bulan->year)
                ->whereMonth('tanggal', $bulan->month)
                ->sum('jumlah');

            $dataAngsuran[] = (float) JurnalKas::where('kategori', 'pembayaran_angsuran')
                ->whereYear('tanggal', $bulan->year)
                ->whereMonth('tanggal', $bulan->month)
                ->sum('jumlah');

            $dataPencairan[] = (float) JurnalKas::where('kategori', 'pencairan_pinjaman')
                ->whereYear('tanggal', $bulan->year)
                ->whereMonth('tanggal', $bulan->month)
                ->sum('jumlah');

            $dataDanaSosial[] = (float) Simpanan::where('jenis', 'dana_sosial')
                ->whereYear('tanggal_input', $bulan->year)
                ->whereMonth('tanggal_input', $bulan->month)
                ->sum('jumlah');
        }

        $grafikKas = collect($labelBulan)->map(fn ($label, $i) => [
            'bulan' => $label,
            'topup' => $dataTopup[$i],
            'angsuran' => $dataAngsuran[$i],
            'pencairan' => $dataPencairan[$i],
            'dana_sosial' => $dataDanaSosial[$i],
        ]);

        // Aktivitas terbaru gabungan
        $aktivitasPinjaman = Pinjaman::with('anggota')
            ->latest('tanggal_pengajuan')
            ->take(5)
            ->get()
            ->map(fn ($p) => [
                'tipe' => 'pinjaman',
                'nama' => $p->anggota->nama,
                'keterangan' => 'Mengajukan pinjaman '.number_format($p->nominal, 0, ',', '.'),
                'status' => $p->status,
                'tanggal' => $p->tanggal_pengajuan,
            ]);

        $aktivitasAngsuran = Angsuran::with('pinjaman.anggota')
            ->where('status', 'lunas')
            ->latest('tanggal_konfirmasi_bayar')
            ->take(5)
            ->get()
            ->map(fn ($a) => [
                'tipe' => 'angsuran',
                'nama' => $a->pinjaman->anggota->nama,
                'keterangan' => "Membayar cicilan ke-{$a->cicilan_ke}",
                'status' => 'lunas',
                'tanggal' => $a->tanggal_konfirmasi_bayar,
            ]);

        $aktivitasTerbaru = $aktivitasPinjaman->concat($aktivitasAngsuran)
            ->sortByDesc('tanggal')
            ->take(6)
            ->values()
            ->map(fn ($item) => [
                ...collect($item)->except('tanggal')->toArray(),
                'tanggal_format' => $item['tanggal']->format('d M Y'),
            ]);

        return Inertia::render('Dashboard', [
            'stats' => [
                'total_anggota_aktif' => Anggota::where('status', 'aktif')->count(),
                'total_simpanan' => (float) Simpanan::whereIn('jenis', ['pokok', 'wajib'])->sum('jumlah'),
                'pinjaman_outstanding' => (float) Pinjaman::where('status', 'aktif')->sum('nominal'),
                'saldo_dana_pinjaman' => (float) $saldoDanaPinjaman,
                'total_keseluruhan' => (float) $totalKeseluruhan,
                'keuntungan_bulan_ini' => (float) $keuntunganBulanIni,
                'saldo_dana_sosial' => (float) $saldoDanaSosial,
            ],
            'actionable' => [
                'menunggu_tinjauan_bendahara' => $menungguTinjauanBendahara,
                'menunggu_approval_ketua' => $menungguApprovalKetua,
                'perubahan_tenor' => $menungguPerubahanTenor,
                'pengajuan_limit' => $menungguPengajuanLimit,
                'anggota_belum_simpanan' => $anggotaBelumSimpananBulanIni,
                'angsuran_jatuh_tempo' => $angsuranJatuhTempoBulanIni,
            ],
            'grafikTren' => $grafikTren,
            'grafikKas' => $grafikKas,
            'aktivitasTerbaru' => $aktivitasTerbaru,
        ]);
    }
}
