<?php

namespace App\Http\Controllers;

use App\Models\Anggota;
use App\Models\Angsuran;
use App\Models\PengajuanAktivasi;
use App\Models\JurnalKas;
use App\Models\KasKoperasi;
use App\Models\PengajuanLimit;
use App\Models\PengajuanPercepatan;
use App\Models\Pinjaman;
use App\Models\Simpanan;
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
        $jurnal = app(\App\Services\Keuangan\JurnalKasService::class);
        $kasOperasional = $jurnal->saldoOperasional($kas);

        $awalBulanIni = now()->startOfMonth()->startOfDay()->toDateTimeString();
        $akhirBulanIni = now()->endOfMonth()->endOfDay()->toDateTimeString();

        $pendapatanBungaBulanIni = Angsuran::where('status', 'lunas')
            ->whereBetween('tanggal_konfirmasi_bayar', [$awalBulanIni, $akhirBulanIni])
            ->sum('nominal_bunga');

        // Outstanding: simpanan anggota aktif saja. Gross: akumulasi semua.
        $totalSimpananOutstanding = (float) Simpanan::whereIn('jenis', ['pokok', 'wajib'])
            ->whereHas('anggota', fn ($q) => $q->where('status', 'aktif'))
            ->sum('jumlah');
        $totalAkumulasiSimpanan = (float) Simpanan::whereIn('jenis', ['pokok', 'wajib'])->sum('jumlah');

        $saldoDanaPinjaman = $kas->saldo_pinjaman;

        // Total = operasional gabungan + outstanding (transit dikecualikan, samakan halaman Kas).
        $totalKeseluruhan = $kasOperasional + $totalSimpananOutstanding;

        $saldoDanaSosial = $kas->saldo_dana_sosial;

        // Actionable items - 1 query agregat per tabel (bukan 2x count)
        $hitungStatus = fn (string $model) => $model::whereIn('status', ['diajukan', 'approved_admin', 'approved_bendahara'])
            ->selectRaw('status, COUNT(*) as jumlah')
            ->groupBy('status')
            ->pluck('jumlah', 'status');

        $pinjamanAntre = $hitungStatus(Pinjaman::class);
        $menungguTinjauanAdmin = (int) ($pinjamanAntre['diajukan'] ?? 0);
        $menungguTinjauanBendahara = (int) ($pinjamanAntre['approved_admin'] ?? 0);
        $menungguApprovalKetua = (int) ($pinjamanAntre['approved_bendahara'] ?? 0);

        $percepatanAntre = $hitungStatus(PengajuanPercepatan::class);
        $menungguPerubahanTenorBendahara = (int) ($percepatanAntre['diajukan'] ?? 0);
        $menungguPerubahanTenorKetua = (int) ($percepatanAntre['approved_bendahara'] ?? 0);
        $menungguPerubahanTenor = $menungguPerubahanTenorBendahara + $menungguPerubahanTenorKetua;

        $limitAntre = $hitungStatus(PengajuanLimit::class);
        $menungguPengajuanLimitBendahara = (int) ($limitAntre['diajukan'] ?? 0);
        $menungguPengajuanLimit = (int) ($limitAntre['approved_bendahara'] ?? 0);
        $menungguAktivasi = PengajuanAktivasi::where('status', 'diajukan')->count();

        $rincianAntrean = [
            'limit_bendahara' => $menungguPengajuanLimitBendahara,
            'limit_ketua' => $menungguPengajuanLimit,
            'percepatan_bendahara' => $menungguPerubahanTenorBendahara,
            'percepatan_ketua' => $menungguPerubahanTenorKetua,
            'aktivasi' => $menungguAktivasi,
        ];

        $anggotaBelumSimpananBulanIni = Anggota::where('status', 'aktif')
            ->whereDoesntHave('simpanan', fn ($q) => $q
                ->where('jenis', 'wajib')
                ->where('bulan_periode', now()->format('Y-m'))
            )->count();

        $angsuranJatuhTempoBulanIni = Angsuran::where('status', 'belum_bayar')
            ->whereBetween('tanggal_jatuh_tempo', [$awalBulanIni, $akhirBulanIni])
            ->count();

        // Grafik - tren 6 bulan terakhir: 2 query (simpanan + pinjaman) GROUP BY year, month
        $awalPeriode = now()->subMonths(5)->startOfMonth();

        $isSqlite = DB::connection()->getDriverName() === 'sqlite';
        $exprTahunBulan = fn (string $kolom) => $isSqlite
            ? "strftime('%Y', {$kolom}) as tahun, CAST(strftime('%m', {$kolom}) AS INTEGER) as bulan"
            : "YEAR({$kolom}) as tahun, MONTH({$kolom}) as bulan";

        $simpananPerBulan = Simpanan::selectRaw("{$exprTahunBulan('tanggal_input')}, SUM(jumlah) as total")
            ->where('tanggal_input', '>=', $awalPeriode)
            ->groupBy('tahun', 'bulan')
            ->orderBy('tahun')
            ->orderBy('bulan')
            ->get()
            ->keyBy(fn ($r) => sprintf('%04d-%02d', $r->tahun, $r->bulan));

        $pinjamanPerBulan = Pinjaman::selectRaw("{$exprTahunBulan('tanggal_pencairan')}, SUM(nominal) as total")
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
        $kasPerBulan = JurnalKas::selectRaw("{$exprTahunBulan('tanggal')}, kategori, tipe, SUM(jumlah) as total")
            ->where('tanggal', '>=', $awalPeriode)
            ->whereIn('kategori', ['topup_bulanan', 'pembayaran_angsuran', 'pencairan_pinjaman'])
            ->groupBy('tahun', 'bulan', 'kategori', 'tipe')
            ->get()
            ->groupBy(fn ($r) => sprintf('%04d-%02d', $r->tahun, $r->bulan));

        $danaSosialPerBulan = Simpanan::selectRaw("{$exprTahunBulan('tanggal_input')}, SUM(jumlah) as total")
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
                'nama' => $p->anggota?->nama ?? '-',
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
                'nama' => $a->pinjaman?->anggota?->nama ?? '-',
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
                'tanggal_format' => $item['tanggal']->translatedFormat('d M Y'),
                'tanggal_iso' => $item['tanggal']->toDateString(),
            ]);

        return Inertia::render('Dashboard', [
            'stats' => [
                'saldo_bank' => (float) $kas->saldo_bank,
                'saldo_kas_kecil' => (float) $kas->saldo_kas_kecil,
                'total_simpanan_outstanding' => $totalSimpananOutstanding,
                'total_simpanan_akumulasi' => $totalAkumulasiSimpanan,
                'pinjaman_outstanding' => (float) Pinjaman::where('status', 'aktif')->sum('nominal'),
                'saldo_dana_pinjaman' => (float) $saldoDanaPinjaman,
                'saldo_pengembalian_simpanan' => (float) $kas->saldo_pengembalian_simpanan,
                'total_keseluruhan' => (float) $totalKeseluruhan,
                'pendapatan_bunga_bulan_ini' => (float) $pendapatanBungaBulanIni,
                'saldo_dana_sosial' => (float) $saldoDanaSosial,
                'kas_operasional' => (float) $kasOperasional,
                'saldo_simpanan' => (float) $kas->saldo_simpanan,
            ],
            'rincianAntrean' => $rincianAntrean,
            'actionable' => [
                'menunggu_tinjauan_admin' => $menungguTinjauanAdmin,
                'menunggu_tinjauan_bendahara' => $menungguTinjauanBendahara,
                'menunggu_approval_ketua' => $menungguApprovalKetua,
                'perubahan_tenor' => $menungguPerubahanTenor,
                'perubahan_tenor_bendahara' => $menungguPerubahanTenorBendahara,
                'perubahan_tenor_ketua' => $menungguPerubahanTenorKetua,
                'pengajuan_limit' => $menungguPengajuanLimit,
                'pengajuan_limit_bendahara' => $menungguPengajuanLimitBendahara,
                'aktivasi_anggota' => $menungguAktivasi,
                'anggota_belum_simpanan' => $anggotaBelumSimpananBulanIni,
                'angsuran_jatuh_tempo' => $angsuranJatuhTempoBulanIni,
            ],
            'grafikTren' => $grafikTren,
            'grafikKas' => $grafikKas,
            'aktivitasTerbaru' => $aktivitasTerbaru,
        ]);
    }
}
