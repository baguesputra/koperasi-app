<?php

namespace App\Http\Controllers;

use App\Models\Anggota;
use App\Models\Angsuran;
use App\Models\JurnalKas;
use App\Models\KasKoperasi;
use App\Models\PengajuanAktivasi;
use App\Models\PengajuanLimit;
use App\Models\PengajuanPenghapusanPinjaman;
use App\Models\PengajuanPercepatan;
use App\Models\PengajuanTransaksiTabungan;
use App\Models\Pinjaman;
use App\Models\RekeningTabungan;
use App\Models\Simpanan;
use App\Models\TabunganTransaksi;
use App\Services\Keuangan\JurnalKasService;
use App\Services\Pinjaman\PenghapusanPinjamanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /** @var array<int, string> */
    private const KATEGORI_INTERNAL_KAS = [
        'saldo_awal',
        'sisih_kas_kecil',
        'terima_sisih_kas_kecil',
        'transfer_ke_dana_pinjaman',
        'terima_dari_pengembalian_simpanan',
        'talangan_sosial_ke_pinjaman',
        'talangan_simpanan_ke_pinjaman',
        'terima_talangan_dari_sosial',
        'terima_talangan_dari_simpanan',
        'kembali_talangan_dari_pinjaman',
        'kembali_talangan_ke_simpanan',
        'kembali_talangan_ke_sosial',
    ];

    public function index(): Response|RedirectResponse
    {
        if (auth()->user()->hasRole('anggota')) {
            return redirect()->route('portal.dashboard');
        }

        $kas = KasKoperasi::first();
        $jurnal = app(JurnalKasService::class);
        $kasOperasional = $jurnal->saldoOperasional($kas);

        $awalBulanIni = now()->startOfMonth()->startOfDay()->toDateTimeString();
        $akhirBulanIni = now()->endOfMonth()->endOfDay()->toDateTimeString();

        // Outstanding: simpanan anggota aktif saja. Gross: akumulasi semua (indexed join).
        $totalSimpananOutstanding = (float) Simpanan::whereIn('simpanan.jenis', ['pokok', 'wajib'])
            ->join('anggota', 'simpanan.anggota_id', '=', 'anggota.id')
            ->where('anggota.status', 'aktif')
            ->sum('simpanan.jumlah');
        $totalAkumulasiSimpanan = (float) Simpanan::whereIn('jenis', ['pokok', 'wajib'])->sum('jumlah');

        $saldoDanaPinjaman = $kas->saldo_pinjaman;

        $saldoDanaSosial = $jurnal->saldoDanaSosial($kas);

        // Actionable items - 1 query agregat per tabel
        $hitungStatus = fn (string $model) => $model::whereIn('status', ['diajukan', 'approved_admin', 'approved_bendahara'])
            ->selectRaw('status, COUNT(*) as jumlah')
            ->groupBy('status')
            ->pluck('jumlah', 'status');

        $pinjamanAgg = Pinjaman::whereIn('status', ['diajukan', 'approved_admin', 'approved_bendahara', 'approved_ketua', 'aktif'])
            ->selectRaw('status, cair_oleh_bendahara, COUNT(*) as jumlah, COALESCE(SUM(nominal), 0) as total_nominal')
            ->groupBy('status', 'cair_oleh_bendahara')
            ->get();

        $menungguTinjauanAdmin = (int) $pinjamanAgg->where('status', 'diajukan')->sum('jumlah');
        $menungguTinjauanBendahara = (int) $pinjamanAgg->where('status', 'approved_admin')->sum('jumlah');
        $menungguApprovalKetua = (int) $pinjamanAgg
            ->filter(fn ($r) => $r->status === 'approved_bendahara' && ! (bool) $r->cair_oleh_bendahara)
            ->sum('jumlah');
        $menungguPencairanPinjaman = (int) $pinjamanAgg->where('status', 'approved_ketua')->sum('jumlah');
        $pinjamanOutstanding = (float) $pinjamanAgg->where('status', 'aktif')->sum('total_nominal');
        $menungguPencairanResign = Anggota::where('status_pencairan_resign', 'menunggu')->count();

        $percepatanAntre = $hitungStatus(PengajuanPercepatan::class);
        $menungguPerubahanTenorBendahara = (int) ($percepatanAntre['diajukan'] ?? 0);
        $menungguPerubahanTenorKetua = (int) ($percepatanAntre['approved_bendahara'] ?? 0);
        $menungguPerubahanTenor = $menungguPerubahanTenorBendahara + $menungguPerubahanTenorKetua;

        $limitAntre = $hitungStatus(PengajuanLimit::class);
        $menungguPengajuanLimitBendahara = (int) ($limitAntre['diajukan'] ?? 0);
        $menungguPengajuanLimit = (int) ($limitAntre['approved_bendahara'] ?? 0);
        $menungguAktivasi = PengajuanAktivasi::where('status', 'diajukan')->count();

        $rekAgg = RekeningTabungan::whereIn('status', ['diajukan', 'approved_admin', 'approved_bendahara', 'aktif'])
            ->selectRaw('status, COUNT(*) as jumlah, COALESCE(SUM(saldo), 0) as total_saldo')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $trxAgg = PengajuanTransaksiTabungan::whereIn('status', ['menunggu', 'approved_admin', 'approved_bendahara', 'approved_ketua'])
            ->selectRaw('jenis, status, COUNT(*) as jumlah')
            ->groupBy('jenis', 'status')
            ->get();

        $hitungTrx = fn (array $jenis, string $status): int => (int) $trxAgg
            ->filter(fn ($row) => $row->status === $status && in_array($row->jenis, $jenis, true))
            ->sum('jumlah');

        $menungguTabunganAdmin = (int) ($rekAgg['diajukan']->jumlah ?? 0)
            + $hitungTrx(PengajuanTransaksiTabungan::JENIS_PENARIKAN, 'menunggu');
        $tabunganBendaharaMenunggu = (int) ($rekAgg['approved_admin']->jumlah ?? 0)
            + $hitungTrx(['setor'], 'menunggu')
            + $hitungTrx(PengajuanTransaksiTabungan::JENIS_PENARIKAN, 'approved_admin');
        $pencairanTabunganMenunggu = $hitungTrx(PengajuanTransaksiTabungan::JENIS_PENARIKAN, 'approved_ketua');
        $menungguTabungan = $tabunganBendaharaMenunggu + $pencairanTabunganMenunggu;
        $menungguTabunganKetua = (int) ($rekAgg['approved_bendahara']->jumlah ?? 0)
            + $hitungTrx(PengajuanTransaksiTabungan::JENIS_PENARIKAN, 'approved_bendahara');
        $totalTabunganAnggota = (float) ($rekAgg['aktif']->total_saldo ?? 0);

        $userAktif = auth()->user();
        $penghapusanStatusAktif = [
            'diajukan',
            'approved_pengawas_3',
            'approved_pengawas_2',
        ];
        $penghapusanBerjalan = PengajuanPenghapusanPinjaman::whereIn('status', $penghapusanStatusAktif)->count();
        $menungguPenghapusanSaya = app(PenghapusanPinjamanService::class)->hitungAntreanUntukUser($userAktif);

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

        // Grafik 12 bulan terakhir: Dana Anggota (Simpanan Pokok/Wajib + Tabungan) vs Pencairan Pinjaman (cached 60s)
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';
        $exprTahunBulan = fn (string $kolom) => $isSqlite
            ? "strftime('%Y', {$kolom}) as tahun, CAST(strftime('%m', {$kolom}) AS INTEGER) as bulan"
            : "YEAR({$kolom}) as tahun, MONTH({$kolom}) as bulan";

        $grafikTren = Cache::remember('dashboard_grafik_tren_12m', 60, function () use ($exprTahunBulan) {
            $awalPeriode = now()->subMonths(11)->startOfMonth()->toDateString();

            $simpananPerBulan = Simpanan::selectRaw("{$exprTahunBulan('tanggal_input')}, SUM(jumlah) as total")
                ->whereIn('jenis', ['pokok', 'wajib'])
                ->where('tanggal_input', '>=', $awalPeriode)
                ->groupBy('tahun', 'bulan')
                ->orderBy('tahun')
                ->orderBy('bulan')
                ->get()
                ->keyBy(fn ($r) => sprintf('%04d-%02d', $r->tahun, $r->bulan));

            $tabunganPerBulan = TabunganTransaksi::selectRaw("{$exprTahunBulan('tanggal')}, jenis, SUM(nominal) as total")
                ->whereIn('jenis', ['setor', 'tarik', 'tutup_rekening'])
                ->whereNull('dibatalkan_pada')
                ->whereNull('koreksi_dari_id')
                ->where('tanggal', '>=', $awalPeriode)
                ->groupBy('tahun', 'bulan', 'jenis')
                ->get()
                ->groupBy(fn ($r) => sprintf('%04d-%02d', $r->tahun, $r->bulan));

            $pinjamanPerBulan = Pinjaman::selectRaw("{$exprTahunBulan('tanggal_pencairan')}, SUM(nominal) as total, COUNT(*) as jumlah_akad")
                ->whereIn('status', ['aktif', 'lunas', 'dihapusbukukan'])
                ->whereNotNull('tanggal_pencairan')
                ->where('tanggal_pencairan', '>=', $awalPeriode)
                ->groupBy('tahun', 'bulan')
                ->orderBy('tahun')
                ->orderBy('bulan')
                ->get()
                ->keyBy(fn ($r) => sprintf('%04d-%02d', $r->tahun, $r->bulan));

            $data = [];
            for ($i = 11; $i >= 0; $i--) {
                $bulan = now()->subMonths($i);
                $key = $bulan->format('Y-m');

                $tabunganSetor = 0.0;
                $tabunganTarik = 0.0;
                if (isset($tabunganPerBulan[$key])) {
                    foreach ($tabunganPerBulan[$key] as $row) {
                        $nominal = (float) $row->total;
                        if ($row->jenis === 'setor') {
                            $tabunganSetor += $nominal;
                        } else {
                            $tabunganTarik += $nominal;
                        }
                    }
                }

                $data[] = [
                    'bulan' => $bulan->translatedFormat('M Y'),
                    'bulan_singkat' => $bulan->translatedFormat('M'),
                    'simpanan' => round((float) ($simpananPerBulan[$key]->total ?? 0), 2),
                    'tabungan' => round($tabunganSetor, 2),
                    'tabungan_tarik' => round($tabunganTarik, 2),
                    'pinjaman' => round((float) ($pinjamanPerBulan[$key]->total ?? 0), 2),
                    'pinjaman_count' => (int) ($pinjamanPerBulan[$key]->jumlah_akad ?? 0),
                ];
            }

            return $data;
        });

        // Arus Kas Operasional Riil 12 bulan terakhir: seluruh mutasi JurnalKas non-batal, non-storno, & non-transfer internal (cached 60s)
        $grafikKas = Cache::remember('dashboard_grafik_kas_12m', 60, function () use ($exprTahunBulan) {
            $awalPeriode = now()->subMonths(11)->startOfMonth()->toDateString();

            $kasPerBulan = JurnalKas::selectRaw("{$exprTahunBulan('tanggal')}, kategori, tipe, SUM(jumlah) as total")
                ->where('tanggal', '>=', $awalPeriode)
                ->whereNotIn('kategori', self::KATEGORI_INTERNAL_KAS)
                ->whereNull('dibatalkan_pada')
                ->whereNull('koreksi_dari_id')
                ->groupBy('tahun', 'bulan', 'kategori', 'tipe')
                ->get()
                ->groupBy(fn ($r) => sprintf('%04d-%02d', $r->tahun, $r->bulan));

            $data = [];
            for ($i = 11; $i >= 0; $i--) {
                $bulan = now()->subMonths($i);
                $key = $bulan->format('Y-m');

                $angsuran = 0.0;
                $simpanan = 0.0;
                $setorTabungan = 0.0;
                $danaSosial = 0.0;
                $topup = 0.0;
                $pencairan = 0.0;
                $tarikTabungan = 0.0;
                $pengeluaran = 0.0;
                $resignShu = 0.0;

                if (isset($kasPerBulan[$key])) {
                    foreach ($kasPerBulan[$key] as $row) {
                        $jumlah = (float) $row->total;
                        if ($row->tipe === 'masuk') {
                            match ($row->kategori) {
                                'pembayaran_angsuran', 'pelunasan_resign_pinjaman' => $angsuran += $jumlah,
                                'simpanan_pokok_masuk', 'simpanan_wajib_masuk', 'simpanan_resign_masuk' => $simpanan += $jumlah,
                                'setor_tabungan' => $setorTabungan += $jumlah,
                                'dana_sosial_bulanan' => $danaSosial += $jumlah,
                                default => $topup += $jumlah,
                            };
                        } else {
                            match ($row->kategori) {
                                'pencairan_pinjaman' => $pencairan += $jumlah,
                                'tarik_tabungan' => $tarikTabungan += $jumlah,
                                'pengeluaran_koperasi', 'pengeluaran_dana_sosial' => $pengeluaran += $jumlah,
                                default => $resignShu += $jumlah,
                            };
                        }
                    }
                }

                $masuk = round($angsuran + $simpanan + $setorTabungan + $danaSosial + $topup, 2);
                $keluar = round($pencairan + $tarikTabungan + $pengeluaran + $resignShu, 2);

                $data[] = [
                    'bulan' => $bulan->translatedFormat('M Y'),
                    'bulan_singkat' => $bulan->translatedFormat('M'),
                    'masuk' => $masuk,
                    'keluar' => $keluar,
                    'net' => round($masuk - $keluar, 2),
                    'angsuran' => round($angsuran, 2),
                    'simpanan' => round($simpanan, 2),
                    'setor_tabungan' => round($setorTabungan, 2),
                    'dana_sosial' => round($danaSosial, 2),
                    'topup' => round($topup, 2),
                    'pencairan' => round($pencairan, 2),
                    'tarik_tabungan' => round($tarikTabungan, 2),
                    'pengeluaran' => round($pengeluaran, 2),
                    'resign_shu' => round($resignShu, 2),
                ];
            }

            return $data;
        });

        // Aktivitas terbaru gabungan
        $aktivitasPinjaman = Pinjaman::with('anggota')
            ->latest('tanggal_pengajuan')
            ->take(6)
            ->get()
            ->map(fn ($p) => [
                'tipe' => 'pinjaman',
                'pinjaman_id' => $p->id,
                'nama' => $p->anggota?->nama ?? '-',
                'keterangan' => 'Mengajukan pinjaman '.number_format($p->nominal, 0, ',', '.'),
                'status' => $p->status,
                'tanggal' => $p->tanggal_pengajuan,
            ]);

        $aktivitasAngsuran = Angsuran::with('pinjaman.anggota')
            ->where('status', 'lunas')
            ->latest('tanggal_konfirmasi_bayar')
            ->take(6)
            ->get()
            ->map(fn ($a) => [
                'tipe' => 'angsuran',
                'pinjaman_id' => $a->pinjaman_id,
                'nama' => $a->pinjaman?->anggota?->nama ?? '-',
                'keterangan' => "Membayar cicilan ke-{$a->cicilan_ke}",
                'status' => 'lunas',
                'tanggal' => $a->tanggal_konfirmasi_bayar,
            ]);

        $labelTabungan = [
            'setor' => 'Setor tabungan',
            'tarik' => 'Tarik tabungan',
            'tutup_rekening' => 'Tutup rekening tabungan',
        ];

        $aktivitasTabungan = TabunganTransaksi::with('anggota')
            ->whereIn('jenis', ['setor', 'tarik', 'tutup_rekening'])
            ->whereNull('dibatalkan_pada')
            ->whereNull('koreksi_dari_id')
            ->latest('created_at')
            ->take(6)
            ->get()
            ->map(fn ($t) => [
                'tipe' => 'tabungan',
                'rekening_tabungan_id' => $t->rekening_tabungan_id,
                'nama' => $t->anggota?->nama ?? '-',
                'keterangan' => ($labelTabungan[$t->jenis] ?? 'Transaksi tabungan').' Rp '.number_format((float) $t->nominal, 0, ',', '.'),
                'status' => 'disetujui',
                'tanggal' => $t->created_at,
            ]);

        $aktivitasTerbaru = $aktivitasPinjaman
            ->concat($aktivitasAngsuran)
            ->concat($aktivitasTabungan)
            ->sortByDesc('tanggal')
            ->take(12)
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
                'pinjaman_outstanding' => $pinjamanOutstanding,
                'saldo_dana_pinjaman' => (float) $saldoDanaPinjaman,
                'saldo_pengembalian_simpanan' => (float) $kas->saldo_pengembalian_simpanan,
                'saldo_dana_sosial' => (float) $saldoDanaSosial,
                'kas_operasional' => (float) $kasOperasional,
                'saldo_simpanan' => (float) $kas->saldo_simpanan,
                'total_tabungan_anggota' => $totalTabunganAnggota,
            ],
            'rincianAntrean' => $rincianAntrean,
            'actionable' => [
                'menunggu_tinjauan_admin' => $menungguTinjauanAdmin,
                'menunggu_tinjauan_bendahara' => $menungguTinjauanBendahara,
                'menunggu_approval_ketua' => $menungguApprovalKetua,
                'menunggu_pencairan_pinjaman' => $menungguPencairanPinjaman,
                'menunggu_pencairan_resign' => $menungguPencairanResign,
                'perubahan_tenor' => $menungguPerubahanTenor,
                'perubahan_tenor_bendahara' => $menungguPerubahanTenorBendahara,
                'perubahan_tenor_ketua' => $menungguPerubahanTenorKetua,
                'pengajuan_limit' => $menungguPengajuanLimit,
                'pengajuan_limit_bendahara' => $menungguPengajuanLimitBendahara,
                'aktivasi_anggota' => $menungguAktivasi,
                'tabungan_admin_menunggu' => $menungguTabunganAdmin,
                'tabungan_menunggu' => $menungguTabungan,
                'tabungan_bendahara_menunggu' => $tabunganBendaharaMenunggu,
                'pencairan_tabungan_menunggu' => $pencairanTabunganMenunggu,
                'tabungan_ketua_menunggu' => $menungguTabunganKetua,
                'anggota_belum_simpanan' => $anggotaBelumSimpananBulanIni,
                'angsuran_jatuh_tempo' => $angsuranJatuhTempoBulanIni,
                'penghapusan_pinjaman_menunggu' => $menungguPenghapusanSaya,
                'penghapusan_pinjaman_berjalan' => $penghapusanBerjalan,
            ],
            'grafikTren' => $grafikTren,
            'grafikKas' => $grafikKas,
            'aktivitasTerbaru' => $aktivitasTerbaru,
        ]);
    }
}
