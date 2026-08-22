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
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
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

        // Outstanding: simpanan anggota aktif saja. Gross: akumulasi semua.
        $totalSimpananOutstanding = (float) Simpanan::whereIn('jenis', ['pokok', 'wajib'])
            ->whereHas('anggota', fn ($q) => $q->where('status', 'aktif'))
            ->sum('jumlah');
        $totalAkumulasiSimpanan = (float) Simpanan::whereIn('jenis', ['pokok', 'wajib'])->sum('jumlah');

        $saldoDanaPinjaman = $kas->saldo_pinjaman;

        // saldo_pengembalian_simpanan tidak dimasukkan di total karena hanya "dalam proses"
        $totalKeseluruhan = $saldoDanaPinjaman + $kas->saldo_dana_sosial + $totalSimpananOutstanding;

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

        // Grafik - tren 6 bulan terakhir: 2 query (simpanan + pinjaman) GROUP BY year, month
        $awalPeriode = now()->subMonths(5)->startOfMonth();

        $simpananPerBulan = Simpanan::selectRaw('YEAR(tanggal_input) as tahun, MONTH(tanggal_input) as bulan, SUM(jumlah) as total')
            ->where('tanggal_input', '>=', $awalPeriode)
            ->groupBy('tahun', 'bulan')
            ->orderBy('tahun')
            ->orderBy('bulan')
            ->get()
            ->keyBy(fn ($r) => sprintf('%04d-%02d', $r->tahun, $r->bulan));

        $pinjamanPerBulan = Pinjaman::selectRaw('YEAR(tanggal_pencairan) as tahun, MONTH(tanggal_pencairan) as bulan, SUM(nominal) as total')
            ->where('tanggal_pencairan', '>=', $awalPeriode)
            ->groupBy('tahun', 'bulan')
            ->orderBy('tahun')
            ->orderBy('bulan')
            ->get()
            ->keyBy(fn ($r) => sprintf('%04d-%02d', $r->tahun, $r->bulan));

        $labelBulan = [];
        $grafikTren = [];

        for ($i = 5; $i >= 0; $i--) {
            $bulan = now()->subMonths($i);
            $key = $bulan->format('Y-m');
            $labelBulan[] = $bulan->translatedFormat('M Y');

            $grafikTren[] = [
                'bulan' => $labelBulan[5 - $i],
                'simpanan' => (float) ($simpananPerBulan[$key]->total ?? 0),
                'pinjaman' => (float) ($pinjamanPerBulan[$key]->total ?? 0),
            ];
        }

        // Mutasi kas - per kategori (jurnal) & dana sosial (simpanan), 6 bulan terakhir: 1 query JurnalKas + 1 query Simpanan
        $kasPerBulan = JurnalKas::selectRaw('YEAR(tanggal) as tahun, MONTH(tanggal) as bulan, kategori, tipe, SUM(jumlah) as total')
            ->where('tanggal', '>=', $awalPeriode)
            ->whereIn('kategori', ['topup_bulanan', 'pembayaran_angsuran', 'pencairan_pinjaman'])
            ->groupBy('tahun', 'bulan', 'kategori', 'tipe')
            ->get()
            ->groupBy(fn ($r) => sprintf('%04d-%02d', $r->tahun, $r->bulan));

        $danaSosialPerBulan = Simpanan::selectRaw('YEAR(tanggal_input) as tahun, MONTH(tanggal_input) as bulan, SUM(jumlah) as total')
            ->where('jenis', 'dana_sosial')
            ->where('tanggal_input', '>=', $awalPeriode)
            ->groupBy('tahun', 'bulan')
            ->orderBy('tahun')
            ->orderBy('bulan')
            ->get()
            ->keyBy(fn ($r) => sprintf('%04d-%02d', $r->tahun, $r->bulan));

        $grafikKas = [];

        for ($i = 5; $i >= 0; $i--) {
            $bulan = now()->subMonths($i);
            $key = $bulan->format('Y-m');

            $topup = 0.0;
            $angsuran = 0.0;
            $pencairan = 0.0;

            if (isset($kasPerBulan[$key])) {
                foreach ($kasPerBulan[$key] as $row) {
                    $jumlah = (float) $row->total;
                    if ($row->kategori === 'topup_bulanan' && $row->tipe === 'masuk') {
                        $topup += $jumlah;
                    } elseif ($row->kategori === 'pembayaran_angsuran' && $row->tipe === 'masuk') {
                        $angsuran += $jumlah;
                    } elseif ($row->kategori === 'pencairan_pinjaman' && $row->tipe === 'keluar') {
                        $pencairan += $jumlah;
                    }
                }
            }

            $grafikKas[] = [
                'bulan' => $bulan->translatedFormat('M Y'),
                'topup' => $topup,
                'angsuran' => $angsuran,
                'pencairan' => $pencairan,
                'dana_sosial' => (float) ($danaSosialPerBulan[$key]->total ?? 0),
            ];
        }

        // Aktivitas terbaru gabungan
        $aktivitasPinjaman = Pinjaman::with('anggota')
            ->latest('tanggal_pengajuan')
            ->take(5)
            ->get()
            ->map(fn ($p) => [
                'tipe' => 'pinjaman',
                'nama' => $p->anggota->nama,
                'keterangan' => 'Mengajukan pinjaman ' . number_format($p->nominal, 0, ',', '.'),
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

        // Stats: total_anggota_aktif, pinjaman_outstanding - bisa dioptimasi nanti kalau perlu

        return Inertia::render('Dashboard', [
            'stats' => [
                'total_anggota_aktif' => Anggota::where('status', 'aktif')->count(),
                'total_simpanan_outstanding' => $totalSimpananOutstanding,
                'total_simpanan_akumulasi' => $totalAkumulasiSimpanan,
                'pinjaman_outstanding' => (float) Pinjaman::where('status', 'aktif')->sum('nominal'),
                'saldo_dana_pinjaman' => (float) $saldoDanaPinjaman,
                'saldo_pengembalian_simpanan' => (float) $kas->saldo_pengembalian_simpanan,
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