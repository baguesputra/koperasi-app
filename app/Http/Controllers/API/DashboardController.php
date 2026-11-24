<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\PinjamanResource;
use App\Http\Resources\PinjamanRingkasResource;
use App\Http\Resources\SessionUserResource;
use App\Models\Anggota;
use App\Models\Angsuran;
use App\Models\JurnalKas;
use App\Models\KasKoperasi;
use App\Models\PengajuanAktivasi;
use App\Models\PengajuanLimit;
use App\Models\PengajuanPercepatan;
use App\Models\Pinjaman;
use App\Models\SettingSimpanan;
use App\Models\Simpanan;
use App\Models\TabelTenor;
use App\Services\Keuangan\JurnalKasService;
use App\Services\Pinjaman\EligibilitasPinjamanService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct(private EligibilitasPinjamanService $eligibilitas) {}

    private function anggotaOrFail(Request $request): Anggota
    {
        $anggota = $request->user()->anggota;

        if (! $anggota) {
            abort(response()->json([
                'message' => 'Anggota not found for user',
            ], 404));
        }

        return $anggota;
    }

    /**
     * Dashboard anggota untuk aplikasi mobile.
     *
     * @response 200 {"anggota": {"id": 1}, "ringkasan": {"total_simpanan": 100000}, "riwayat_gabungan": []}
     */
    public function dashboard(Request $request): JsonResponse
    {
        $anggota = $this->anggotaOrFail($request);
        $anggota->loadMissing(['user:id,email', 'perusahaan:id,nama']);

        $simpananAgg = $anggota->simpanan()
            ->selectRaw('
                SUM(CASE WHEN jenis IN ("pokok","wajib") THEN jumlah ELSE 0 END) as total,
                SUM(CASE WHEN jenis = "pokok" THEN jumlah ELSE 0 END) as pokok,
                SUM(CASE WHEN jenis = "wajib" THEN jumlah ELSE 0 END) as wajib
            ')
            ->first();

        $cek = $this->eligibilitas->cek($anggota);
        $cekLimit = $this->eligibilitas->cekEligibilitasTambahLimit($anggota);
        $limitMaksimal = $this->eligibilitas->limitMaksimal($anggota);

        $pinjamanAktif = $anggota->pinjamanAktifList();

        $resignMenunggu = null;
        if ($anggota->status === 'resign_menunggu') {
            $settlement = $anggota->resigned_settlement_json ?? [];
            $resignMenunggu = [
                'shortfall' => (float) ($settlement['shortfall'] ?? 0),
                'jatuh_tempo' => isset($settlement['cicilan_akhir_jatuh_tempo'])
                    ? Carbon::parse($settlement['cicilan_akhir_jatuh_tempo'])->format('Y-m-d')
                    : null,
                'tanggal_resign' => $anggota->tanggal_resign?->format('Y-m-d'),
            ];
        }

        $pengajuanBerjalan = $anggota->pinjaman()->with(['anggota', 'pengaju', 'dicairkanOleh:id,name'])
            ->whereIn('status', ['diajukan', 'approved_admin', 'approved_bendahara', 'approved_ketua'])
            ->latest('tanggal_pengajuan')
            ->first();

        $pengajuanLimitBerjalan = $anggota->pengajuanLimit()->with('anggota')
            ->whereIn('status', ['diajukan', 'approved_bendahara'])
            ->latest('tanggal_pengajuan')
            ->first();

        $pengajuanPercepatanMenunggu = $pinjamanAktif->isEmpty()
            ? collect()
            : PengajuanPercepatan::whereIn('pinjaman_id', $pinjamanAktif->pluck('id'))
                ->whereIn('status', ['diajukan', 'approved_bendahara'])
                ->with('pinjaman:id,nominal,tenor_bulan')
                ->orderBy('tanggal_pengajuan')
                ->get();

        $pengajuanDitolak = $anggota->pinjaman()->with(['anggota', 'pengaju', 'dicairkanOleh:id,name'])
            ->where('status', 'ditolak')
            ->latest('updated_at')
            ->first();

        $angsuranBerikutnya = null;
        foreach ($pinjamanAktif as $pinjaman) {
            $agg = $pinjaman->agregatJadwalAktif();
            $terdekat = $pinjaman->angsuranBelumBayar()->orderBy('cicilan_ke')->first();
            if ($terdekat && ($this->terdekatLebihAwal($terdekat, $angsuranBerikutnya))) {
                $angsuranBerikutnya = [
                    'pinjaman_id' => $pinjaman->id,
                    'cicilan_ke' => $terdekat->cicilan_ke,
                    'total_bayar' => (float) $terdekat->total_bayar,
                    'tanggal_jatuh_tempo' => $terdekat->tanggal_jatuh_tempo?->format('Y-m-d'),
                ];
            }
            unset($agg);
        }

        $pinjamanIdsAnggota = $anggota->pinjaman()->pluck('id');
        $riwayatSimpanan = $anggota->simpanan()
            ->whereIn('jenis', ['wajib', 'pokok'])
            ->latest('tanggal_input')
            ->take(6)
            ->get();
        $riwayatAngsuran = $pinjamanIdsAnggota->isEmpty()
            ? collect()
            : Angsuran::whereIn('pinjaman_id', $pinjamanIdsAnggota)
                ->where('status', 'lunas')
                ->latest('tanggal_konfirmasi_bayar')
                ->take(4)
                ->get();
        $riwayatGabungan = $riwayatSimpanan->map(fn ($s) => [
            'tipe' => 'simpanan',
            'label' => match ($s->jenis) {
                'pokok' => 'Simpanan pokok',
                'wajib' => 'Simpanan wajib',
                default => $s->jenis,
            },
            'nominal' => (float) $s->jumlah,
            'tanggal_format' => $s->tanggal_input?->format('Y-m-d'),
            'tanggal' => $s->tanggal_input,
        ])->concat($riwayatAngsuran->map(fn ($a) => [
            'tipe' => 'angsuran',
            'label' => "Cicilan ke-{$a->cicilan_ke}",
            'nominal' => (float) $a->total_bayar,
            'tanggal_format' => ($a->tanggal_konfirmasi_bayar ?? $a->tanggal_jatuh_tempo)?->format('Y-m-d'),
            'tanggal' => $a->tanggal_konfirmasi_bayar ?? $a->tanggal_jatuh_tempo,
        ]))->sortByDesc(fn ($item) => $item['tanggal']?->toDateString() ?? '')
            ->take(5)
            ->values()
            ->map(fn ($item) => collect($item)->except('tanggal')->all());

        return response()->json([
            'anggota' => $this->anggotaPenuh($anggota, $resignMenunggu),
            'ringkasan' => [
                'total_simpanan' => (float) ($simpananAgg->total ?? 0),
                'simpanan_pokok' => (float) ($simpananAgg->pokok ?? 0),
                'simpanan_wajib' => (float) ($simpananAgg->wajib ?? 0),
                'limit_maksimal' => (float) $limitMaksimal,
                'limit_tersedia' => (float) $cek['limit_tersedia'],
                'boleh' => (bool) $cek['boleh'],
                'alasan' => $cek['alasan'],
                'syarat_masa_keanggotaan' => $cek['syarat_masa_keanggotaan'] ?? null,
                'sisa_angsuran' => (int) $cek['sisa_angsuran'],
                'cicilan_pokok' => (float) $cek['cicilan_pokok'],
                'boleh_tambah_limit' => (bool) $cekLimit['boleh'],
                'alasan_terkunci_limit' => $cekLimit['alasan'],
                'status_limit_badge' => $cekLimit['status_badge'],
                'state_limit_label' => $cekLimit['state_label'],
                'plafon_kebijakan' => (float) $cekLimit['plafon_koperasi'],
            ],
            'pinjaman_aktif' => PinjamanRingkasResource::collection($pinjamanAktif)->resolve(),
            'pengajuan_berjalan' => $pengajuanBerjalan ? (new PinjamanResource($pengajuanBerjalan))->resolve() : null,
            'pengajuan_limit_berjalan' => $pengajuanLimitBerjalan ? $this->pengajuanLimit($pengajuanLimitBerjalan) : null,
            'pengajuan_percepatan_menunggu' => $pengajuanPercepatanMenunggu->map(fn ($pp) => $this->pengajuanPercepatan($pp))->values()->all(),
            'pengajuan_ditolak' => $pengajuanDitolak ? (new PinjamanResource($pengajuanDitolak))->resolve() : null,
            'angsuran_berikutnya' => $angsuranBerikutnya,
            'riwayat_gabungan' => $riwayatGabungan,
            'tabel_tenor' => TabelTenor::orderBy('nominal_min')->get(['nominal_min', 'nominal_max', 'tenor_maksimal_bulan'])
                ->map(fn ($t) => [
                    'nominal_min' => (float) $t->nominal_min,
                    'nominal_max' => (float) $t->nominal_max,
                    'tenor_maksimal_bulan' => (int) $t->tenor_maksimal_bulan,
                ])->values()->all(),
            'setting_simpanan' => SettingSimpanan::orderBy('id')->get(['jenis', 'label', 'nominal'])
                ->map(fn ($s) => [
                    'jenis' => $s->jenis,
                    'label' => $s->label,
                    'nominal' => (float) $s->nominal,
                ])->values()->all(),
            'syarat_pinjaman' => [
                'versi' => config('syarat_pinjaman.versi'),
                'poin' => config('syarat_pinjaman.poin'),
            ],
        ]);
    }

    /**
     * Ringkasan pengurus untuk aplikasi mobile.
     *
     * @response 200 {"user": {"id": 1}, "antrean": {"pinjaman_admin": 0}, "kas": null}
     */
    public function ringkasan(Request $request): JsonResponse
    {
        $user = $request->user();
        $izinPengurus = [
            'pinjaman.tinjau-admin', 'pinjaman.tinjau-bendahara', 'pinjaman.approve-ketua',
            'pinjaman.lihat', 'limit.tinjau-bendahara', 'limit.approve-ketua',
            'aktivasi.approve-ketua', 'angsuran.konfirmasi', 'simpanan.konfirmasi',
            'simpanan.lihat', 'kas.lihat', 'anggota.lihat', 'laporan.lihat',
        ];

        if (! $user->hasAnyPermission($izinPengurus)) {
            abort(response()->json([
                'message' => 'Anda tidak memiliki akses ke ringkasan pengurus.',
            ], 403));
        }

        $pinjamanAntre = Pinjaman::whereIn('status', ['diajukan', 'approved_admin', 'approved_bendahara'])
            ->selectRaw('status, COUNT(*) as jumlah, SUM(cair_oleh_bendahara) as cair')
            ->groupBy('status')
            ->get()
            ->keyBy('status');
        $ketuaMenunggu = (int) ($pinjamanAntre['approved_bendahara']->jumlah ?? 0)
            - (int) ($pinjamanAntre['approved_bendahara']->cair ?? 0);

        $pencairan = Pinjaman::where('status', 'approved_ketua')->count();

        $limitAntre = PengajuanLimit::whereIn('status', ['diajukan', 'approved_bendahara'])
            ->selectRaw('status, COUNT(*) as jumlah')
            ->groupBy('status')
            ->pluck('jumlah', 'status');

        $percepatanAntre = PengajuanPercepatan::whereIn('status', ['diajukan', 'approved_bendahara'])
            ->selectRaw('status, COUNT(*) as jumlah')
            ->groupBy('status')
            ->pluck('jumlah', 'status');

        $aktivasiAntre = PengajuanAktivasi::where('status', 'diajukan')->count();

        $angsuranBelumBayar = Angsuran::where('status', 'belum_bayar')
            ->whereHas('pinjaman', fn ($q) => $q->where('status', 'aktif'))
            ->count();

        $simpananBelumKonfirmasi = Anggota::where('status', 'aktif')
            ->whereDoesntHave('simpanan', fn ($q) => $q
                ->where('jenis', 'wajib')
                ->where('bulan_periode', now()->format('Y-m')))
            ->count();

        $kas = null;
        if ($user->can('kas.lihat')) {
            $row = KasKoperasi::first();
            if ($row) {
                $saldoBank = (float) $row->saldo_bank;
                $saldoKasKecil = (float) $row->saldo_kas_kecil;
                $kasOperasional = $saldoBank + $saldoKasKecil;
                $simpananOutstanding = (float) Simpanan::whereIn('simpanan.jenis', ['pokok', 'wajib'])
                    ->join('anggota', 'simpanan.anggota_id', '=', 'anggota.id')
                    ->where('anggota.status', 'aktif')
                    ->sum('simpanan.jumlah');
                $awalBulan = now()->startOfMonth()->startOfDay()->toDateTimeString();
                $akhirBulan = now()->endOfMonth()->endOfDay()->toDateTimeString();
                $kas = [
                    'saldo_bank' => $saldoBank,
                    'saldo_kas_kecil' => $saldoKasKecil,
                    'kas_operasional' => $kasOperasional,
                    'saldo_dana_sosial' => app(JurnalKasService::class)->saldoDanaSosial($row),
                    'total_keseluruhan' => $kasOperasional + $simpananOutstanding,
                    'pinjaman_outstanding' => (float) Pinjaman::where('status', 'aktif')->sum('nominal'),
                    'simpanan_outstanding' => $simpananOutstanding,
                    'pendapatan_bunga_bulan_ini' => (float) Angsuran::where('status', 'lunas')
                        ->whereBetween('tanggal_konfirmasi_bayar', [$awalBulan, $akhirBulan])
                        ->sum('nominal_bunga'),
                ];
            }
        }

        $isSqlite = DB::connection()->getDriverName() === 'sqlite';
        $exprTahunBulan = fn (string $kolom) => $isSqlite
            ? "strftime('%Y', {$kolom}) as tahun, CAST(strftime('%m', {$kolom}) AS INTEGER) as bulan"
            : "YEAR({$kolom}) as tahun, MONTH({$kolom}) as bulan";

        $bangunTren = function () use ($exprTahunBulan): array {
            $awalPeriode = now()->subMonths(5)->startOfMonth();

            $simpananPerBulan = Simpanan::selectRaw("{$exprTahunBulan('tanggal_input')}, SUM(jumlah) as total")
                ->where('tanggal_input', '>=', $awalPeriode)
                ->groupBy('tahun', 'bulan')
                ->get()
                ->keyBy(fn ($r) => sprintf('%04d-%02d', $r->tahun, $r->bulan));

            $pinjamanPerBulan = Pinjaman::selectRaw("{$exprTahunBulan('tanggal_pencairan')}, SUM(nominal) as total")
                ->whereNotNull('tanggal_pencairan')
                ->where('tanggal_pencairan', '>=', $awalPeriode)
                ->groupBy('tahun', 'bulan')
                ->get()
                ->keyBy(fn ($r) => sprintf('%04d-%02d', $r->tahun, $r->bulan));

            $data = [];
            for ($i = 5; $i >= 0; $i--) {
                $bulan = now()->subMonths($i);
                $key = $bulan->format('Y-m');
                $data[] = [
                    'bulan' => $bulan->translatedFormat('M Y'),
                    'simpanan' => (float) ($simpananPerBulan[$key]->total ?? 0),
                    'pinjaman' => (float) ($pinjamanPerBulan[$key]->total ?? 0),
                ];
            }

            return $data;
        };

        $grafikTren = app()->runningUnitTests()
            ? $bangunTren()
            : Cache::remember('api_dashboard_grafik_tren_6m', 60, $bangunTren);

        $grafikKas = [];
        if ($user->can('kas.lihat')) {
            $bangunKas = function () use ($exprTahunBulan): array {
                $awalPeriode = now()->subMonths(5)->startOfMonth();

                $kasPerBulan = JurnalKas::selectRaw("{$exprTahunBulan('tanggal')}, kategori, tipe, SUM(jumlah) as total")
                    ->where('tanggal', '>=', $awalPeriode)
                    ->whereIn('kategori', ['topup_bulanan', 'pembayaran_angsuran', 'pencairan_pinjaman'])
                    ->whereNull('dibatalkan_pada')
                    ->whereNull('koreksi_dari_id')
                    ->groupBy('tahun', 'bulan', 'kategori', 'tipe')
                    ->get()
                    ->groupBy(fn ($r) => sprintf('%04d-%02d', $r->tahun, $r->bulan));

                $danaSosialPerBulan = Simpanan::selectRaw("{$exprTahunBulan('tanggal_input')}, SUM(jumlah) as total")
                    ->where('jenis', 'dana_sosial')
                    ->where('tanggal_input', '>=', $awalPeriode)
                    ->groupBy('tahun', 'bulan')
                    ->get()
                    ->keyBy(fn ($r) => sprintf('%04d-%02d', $r->tahun, $r->bulan));

                $data = [];
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

                    $data[] = [
                        'bulan' => $bulan->translatedFormat('M Y'),
                        'topup' => $topup,
                        'angsuran' => $angsuran,
                        'pencairan' => $pencairan,
                        'dana_sosial' => (float) ($danaSosialPerBulan[$key]->total ?? 0),
                    ];
                }

                return $data;
            };

            $grafikKas = app()->runningUnitTests()
                ? $bangunKas()
                : Cache::remember('api_dashboard_grafik_kas_6m', 60, $bangunKas);
        }

        $aktivitasPinjaman = Pinjaman::with('anggota:id,nama')
            ->latest('tanggal_pengajuan')
            ->latest('id')
            ->take(5)
            ->get()
            ->map(fn ($p) => [
                'tipe' => 'pinjaman',
                'nama' => $p->anggota?->nama ?? '-',
                'keterangan' => 'Mengajukan pinjaman '.number_format((float) $p->nominal, 0, ',', '.'),
                'status' => $p->status,
                'tanggal' => $p->tanggal_pengajuan,
            ]);

        $aktivitasAngsuran = Angsuran::with(['pinjaman.anggota:id,nama'])
            ->where('status', 'lunas')
            ->latest('tanggal_konfirmasi_bayar')
            ->latest('id')
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
            ->sortByDesc(fn ($item) => $item['tanggal']?->timestamp ?? 0)
            ->take(6)
            ->values()
            ->map(fn ($item) => [
                'tipe' => $item['tipe'],
                'nama' => $item['nama'],
                'keterangan' => $item['keterangan'],
                'status' => $item['status'],
                'tanggal_format' => $item['tanggal']?->translatedFormat('d M Y') ?? '-',
                'tanggal_iso' => $item['tanggal']?->toDateString(),
            ])
            ->all();

        return response()->json([
            'user' => new SessionUserResource($user),
            'antrean' => [
                'pinjaman_admin' => (int) ($pinjamanAntre['diajukan']->jumlah ?? 0),
                'pinjaman_bendahara' => (int) ($pinjamanAntre['approved_admin']->jumlah ?? 0),
                'pinjaman_ketua' => max(0, $ketuaMenunggu),
                'pencairan_bendahara' => (int) $pencairan,
                'limit_bendahara' => (int) ($limitAntre['diajukan'] ?? 0),
                'limit_ketua' => (int) ($limitAntre['approved_bendahara'] ?? 0),
                'percepatan_bendahara' => (int) ($percepatanAntre['diajukan'] ?? 0),
                'percepatan_ketua' => (int) ($percepatanAntre['approved_bendahara'] ?? 0),
                'aktivasi_anggota' => (int) $aktivasiAntre,
                'angsuran_belum_bayar' => (int) $angsuranBelumBayar,
                'simpanan_belum_konfirmasi' => (int) $simpananBelumKonfirmasi,
            ],
            'kas' => $kas,
            'grafik_tren' => $grafikTren,
            'grafik_kas' => $grafikKas,
            'aktivitas_terbaru' => $aktivitasTerbaru,
        ]);
    }

    /**
     * Bentuk Anggota penuh sesuai kontrak mobile DashboardAnggota.anggota.
     */
    private function anggotaPenuh(Anggota $anggota, ?array $resignMenunggu): array
    {
        $tglAnggota = $anggota->tanggal_jadi_anggota ?? $anggota->tanggal_mulai_kerja;

        return [
            'id' => $anggota->id,
            'no_anggota' => $anggota->no_anggota,
            'no_karyawan' => $anggota->no_karyawan,
            'nama' => $anggota->nama,
            'jabatan' => $anggota->jabatan,
            'cabang' => $anggota->cabang,
            'perusahaan' => $anggota->getRelationValue('perusahaan')?->nama,
            'unit_bisnis' => $anggota->unit_bisnis,
            'department' => $anggota->department,
            'divisi' => $anggota->divisi,
            'no_hp' => $anggota->no_hp,
            'email' => $anggota->user?->email,
            'status' => $anggota->status,
            'foto_url' => $anggota->foto_url,
            'tanggal_masuk' => $tglAnggota?->format('Y-m-d'),
            'tanggal_mulai_kerja' => $anggota->tanggal_mulai_kerja?->format('Y-m-d'),
            'tanggal_jadi_anggota' => $anggota->tanggal_jadi_anggota?->format('Y-m-d'),
            'lama_keanggotaan_label' => Anggota::formatLamaKeanggotaan($tglAnggota),
            'limit_custom' => $anggota->limit_custom !== null ? (float) $anggota->limit_custom : null,
            'resign_menunggu' => $resignMenunggu,
        ];
    }

    /**
     * Pengajuan limit + tahapan (mirror toLimit mock/logic.ts).
     */
    private function pengajuanLimit(PengajuanLimit $l): array
    {
        $ditolakBendahara = $l->status === 'ditolak' && ! $l->catatan_ketua;
        $ditolakKetua = $l->status === 'ditolak' && $l->catatan_ketua;

        return [
            'id' => $l->id,
            'anggota_id' => $l->anggota_id,
            'limit_saat_ini' => (float) $l->limit_saat_ini,
            'limit_diminta' => (float) $l->limit_diminta,
            'limit_disetujui_bendahara' => $l->limit_disetujui_bendahara !== null ? (float) $l->limit_disetujui_bendahara : null,
            'limit_disetujui' => $l->limit_disetujui !== null ? (float) $l->limit_disetujui : null,
            'status' => $l->status,
            'keterangan' => $l->keterangan,
            'catatan_bendahara' => $l->catatan_bendahara,
            'catatan_ketua' => $l->catatan_ketua,
            'tanggal_pengajuan' => $l->tanggal_pengajuan?->format('Y-m-d'),
            'anggota' => $l->anggota ? [
                'id' => $l->anggota->id,
                'no_anggota' => $l->anggota->no_anggota,
                'no_karyawan' => $l->anggota->no_karyawan,
                'nama' => $l->anggota->nama,
                'jabatan' => $l->anggota->jabatan,
                'cabang' => $l->anggota->cabang,
                'unit_bisnis' => $l->anggota->unit_bisnis,
                'department' => $l->anggota->department,
                'divisi' => $l->anggota->divisi,
                'no_hp' => $l->anggota->no_hp,
                'status' => $l->anggota->status,
            ] : null,
            'tahapan' => [
                [
                    'label' => 'Pengajuan diterima',
                    'status' => 'done',
                    'tanggal' => $l->tanggal_pengajuan?->format('Y-m-d'),
                    'oleh' => $l->anggota?->nama,
                    'catatan' => $l->keterangan,
                ],
                [
                    'label' => 'Verifikasi Bendahara',
                    'status' => $ditolakBendahara ? 'rejected' : ($l->status === 'diajukan' ? 'current' : 'done'),
                    'tanggal' => null,
                    'oleh' => $l->catatan_bendahara ? 'Bendahara' : null,
                    'catatan' => $l->catatan_bendahara,
                ],
                [
                    'label' => 'Persetujuan Ketua',
                    'status' => $ditolakKetua ? 'rejected' : ($l->status === 'disetujui' ? 'done' : ($l->status === 'approved_bendahara' ? 'current' : 'pending')),
                    'tanggal' => null,
                    'oleh' => $l->status === 'disetujui' ? 'Ketua Koperasi' : null,
                    'catatan' => $l->catatan_ketua,
                ],
            ],
        ];
    }

    /**
     * Pengajuan percepatan + tahapan (mirror toPercepatan mock/logic.ts).
     */
    private function pengajuanPercepatan(PengajuanPercepatan $p): array
    {
        $ditolakBendahara = $p->status === 'ditolak' && ! $p->catatan_ketua;
        $ditolakKetua = $p->status === 'ditolak' && $p->catatan_ketua;
        $namaAnggota = $p->pinjaman?->anggota?->nama;

        return [
            'id' => $p->id,
            'pinjaman_id' => $p->pinjaman_id,
            'pinjaman_nominal' => (float) ($p->pinjaman->nominal ?? 0),
            'pinjaman_tenor' => (int) ($p->pinjaman->tenor_bulan ?? 0),
            'tipe' => $p->tipe,
            'tenor_lama' => (int) $p->tenor_lama,
            'tenor_baru' => $p->tenor_baru !== null ? (int) $p->tenor_baru : null,
            'keterangan' => $p->keterangan,
            'status' => $p->status,
            'catatan_bendahara' => $p->catatan_bendahara,
            'catatan_ketua' => $p->catatan_ketua,
            'tanggal_pengajuan' => $p->tanggal_pengajuan?->format('Y-m-d'),
            'berlaku_mulai' => $p->bulan_berlaku,
            'anggota' => $p->pinjaman?->anggota ? [
                'id' => $p->pinjaman->anggota->id,
                'no_anggota' => $p->pinjaman->anggota->no_anggota,
                'no_karyawan' => $p->pinjaman->anggota->no_karyawan,
                'nama' => $p->pinjaman->anggota->nama,
                'jabatan' => $p->pinjaman->anggota->jabatan,
                'cabang' => $p->pinjaman->anggota->cabang,
                'unit_bisnis' => $p->pinjaman->anggota->unit_bisnis,
                'department' => $p->pinjaman->anggota->department,
                'divisi' => $p->pinjaman->anggota->divisi,
                'no_hp' => $p->pinjaman->anggota->no_hp,
                'status' => $p->pinjaman->anggota->status,
            ] : null,
            'tahapan' => [
                [
                    'label' => 'Pengajuan diterima',
                    'status' => 'done',
                    'tanggal' => $p->tanggal_pengajuan?->format('Y-m-d'),
                    'oleh' => $namaAnggota,
                    'catatan' => $p->keterangan,
                ],
                [
                    'label' => 'Verifikasi Bendahara',
                    'status' => $ditolakBendahara ? 'rejected' : ($p->status === 'diajukan' ? 'current' : 'done'),
                    'tanggal' => null,
                    'oleh' => $p->catatan_bendahara ? 'Bendahara' : null,
                    'catatan' => $p->catatan_bendahara,
                ],
                [
                    'label' => 'Persetujuan Ketua',
                    'status' => $ditolakKetua ? 'rejected' : ($p->status === 'aktif' ? 'done' : ($p->status === 'approved_bendahara' ? 'current' : 'pending')),
                    'tanggal' => null,
                    'oleh' => $p->status === 'aktif' ? 'Ketua Koperasi' : null,
                    'catatan' => $p->catatan_ketua,
                ],
            ],
        ];
    }

    private function terdekatLebihAwal($kandidat, ?array $saatIni): bool
    {
        if ($saatIni === null) {
            return true;
        }

        $tglKandidat = $kandidat->tanggal_jatuh_tempo?->format('Y-m-d') ?? '';
        $tglSaatIni = $saatIni['tanggal_jatuh_tempo'] ?? '';

        return $tglKandidat !== '' && $tglKandidat < $tglSaatIni;
    }
}
