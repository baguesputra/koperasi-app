<?php

namespace App\Http\Controllers;

use App\Models\Angsuran;
use App\Models\JurnalKas;
use App\Models\PengajuanPenghapusanPinjaman;
use App\Models\Pinjaman;
use App\Services\Pinjaman\PenghapusanPinjamanService;
use App\Services\Pinjaman\PerhitunganBungaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Inertia\Inertia;
use Inertia\Response;

class PinjamanController extends Controller
{
    public function __construct(
        private PerhitunganBungaService $bunga,
        private PenghapusanPinjamanService $penghapusanService,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $hariIni = now()->toDateString();

        $scopeMenunggak = function ($q) use ($hariIni) {
            $q->where('status', 'aktif')->where(function ($sub) use ($hariIni) {
                $sub->whereHas('angsuran', fn ($a) => $a->where('status', 'belum_bayar')->whereDate('tanggal_jatuh_tempo', '<', $hariIni))
                    ->orWhereHas('pengajuanPercepatan', fn ($pp) => $pp->where('status', 'aktif')
                        ->whereHas('angsuranBaru', fn ($ab) => $ab->where('status', 'belum_bayar')->whereDate('tanggal_jatuh_tempo', '<', $hariIni)));
            });
        };

        $query = Pinjaman::with([
            'anggota',
            'angsuran' => fn ($q) => $q->orderBy('cicilan_ke'),
            'pengajuanPercepatan' => fn ($q) => $q->where('status', 'aktif')->latest()->with('angsuranBaru'),
            'pengajuanPenghapusan' => fn ($q) => $q->whereNotIn('status', ['ditolak', 'disetujui'])->latest('id'),
        ]);

        $cabangAktif = $request->string('cabang');
        $statusFilter = $request->string('status')->value();

        if ($statusFilter === 'menunggak') {
            $scopeMenunggak($query);
        } elseif ($statusFilter !== '') {
            $query->where('status', $statusFilter);
        }

        if ($request->filled('cari')) {
            $cari = $request->string('cari');
            $query->whereHas('anggota', fn ($q) => $q
                ->where('nama', 'like', "%{$cari}%")
                ->orWhere('no_karyawan', 'like', "%{$cari}%"));
        }

        if ($cabangAktif->isNotEmpty()) {
            $query->whereHas('anggota', fn ($q) => $q->where('cabang', $cabangAktif));
        }

        $bisaAjukanGlobal = $user ? $user->can('penghapusan-pinjaman.ajukan') : false;
        $hariIniStart = now()->startOfDay();

        $pinjaman = $query->latest('tanggal_pengajuan')
            ->paginate(15)
            ->withQueryString()
            ->through(function (Pinjaman $p) use ($user, $bisaAjukanGlobal, $hariIniStart) {
                $jadwal = $p->jadwalAktif();
                $lunas = $jadwal->where('status', 'lunas');
                $belum = $jadwal->where('status', 'belum_bayar');
                $nominalPinjaman = round((float) $p->nominal, 2);
                $totalPokokTerbayar = round((float) $lunas->sum('nominal_pokok'), 2);
                $sisaPokok = round(max(0.0, $nominalPinjaman - $totalPokokTerbayar), 2);
                $sisaBunga = round((float) $belum->sum('nominal_bunga'), 2);
                $sisaCicilan = $belum->count();
                $jumlahTerlambat = $p->status === 'aktif'
                    ? $belum->filter(fn ($a) => $a->tanggal_jatuh_tempo && $a->tanggal_jatuh_tempo->lt($hariIniStart))->count()
                    : 0;

                $pengAktif = $p->pengajuanPenghapusan->first();
                $isMilikSendiri = $user && $p->anggota?->user_id !== null
                    && (int) $p->anggota->user_id === (int) $user->id;

                return [
                    'id' => $p->id,
                    'nomor_dokumen' => $p->nomor_dokumen ?? ('#'.$p->id),
                    'nama' => $p->anggota->nama,
                    'no_anggota' => $p->anggota->no_anggota,
                    'no_karyawan' => $p->anggota->no_karyawan,
                    'cabang' => $p->anggota->cabang,
                    'foto_url' => $p->anggota->foto_url,
                    'anggota_status' => $p->anggota->status,
                    'is_milik_sendiri' => $isMilikSendiri,
                    'nominal' => (float) $p->nominal,
                    'tenor_bulan' => $p->tenor_bulan,
                    'status' => $p->status,
                    'tanggal_pengajuan' => $p->tanggal_pengajuan->format('d M Y'),
                    'tanggal_cair' => $p->tanggal_pencairan?->format('d M Y'),
                    'ada_bukti_pencairan' => (bool) $p->bukti_pencairan_path,
                    'bukti_pencairan_url' => $p->bukti_pencairan_path ? route('pinjaman.bukti-pencairan', $p->id) : null,
                    'keperluan' => $p->keperluan,
                    'total_pokok_terbayar' => $totalPokokTerbayar,
                    'sisa_cicilan' => $sisaCicilan,
                    'sisa_pokok' => $sisaPokok,
                    'sisa_bunga' => $sisaBunga,
                    'jumlah_terlambat' => $jumlahTerlambat,
                    'penghapusan_aktif' => $pengAktif ? [
                        'id' => $pengAktif->id,
                        'status' => $pengAktif->status,
                        'status_label' => PengajuanPenghapusanPinjaman::STATUS_LABEL[$pengAktif->status] ?? $pengAktif->status,
                    ] : null,
                    'bisa_ajukan_penghapusan' => $bisaAjukanGlobal
                        && $p->status === 'aktif'
                        && $sisaPokok > 0
                        && $pengAktif === null
                        && ! $isMilikSendiri,
                    'pelunasan_resign_total' => 0.0,
                    'tanggal_pelunasan_resign' => null,
                    'is_resign' => $p->anggota->status === 'resign',
                    'angsuran' => $p->angsuran
                        ->map(fn ($a) => [
                            'id' => $a->id,
                            'cicilan_ke' => $a->cicilan_ke,
                            'total_bayar' => (float) $a->total_bayar,
                            'status' => $a->status,
                            'tanggal_jatuh_tempo' => $a->tanggal_jatuh_tempo?->format('d M Y'),
                            'tanggal_konfirmasi_bayar' => $a->tanggal_konfirmasi_bayar?->format('d M Y'),
                        ])
                        ->values(),
                    'pelunasan_resign' => [
                        'total' => 0.0,
                        'tanggal' => null,
                    ],
                    'jurnal_pelunasan' => [], // diisi via batch di bawah
                ];
            });

        // Load jurnal_pelunasan untuk semua pinjaman di halaman ini (2 query, bukan N+1)
        $pinjamanIds = $pinjaman->pluck('id');
        $jurnalMap = [];
        if ($pinjamanIds->isNotEmpty()) {
            // 1 query peta angsuran_id => pinjaman_id (ganti pluck ids + pluck map terpisah)
            $angsuranMap = Angsuran::whereIn('pinjaman_id', $pinjamanIds)->pluck('pinjaman_id', 'id');
            if ($angsuranMap->isNotEmpty()) {
                $jurnalList = JurnalKas::where('kategori', 'pelunasan_resign_pinjaman')
                    ->whereIn('referensi_id', $angsuranMap->keys())
                    ->orderBy('tanggal')
                    ->get()
                    ->groupBy(function ($j) {
                        // Cari pinjaman_id dari angsuran_id
                        return $j->referensi_id;
                    });

                // Map kembali ke pinjaman_id
                foreach ($jurnalList as $angsuranId => $journals) {
                    $pinjamanId = $angsuranMap[$angsuranId] ?? null;
                    if ($pinjamanId) {
                        $jurnalMap[$pinjamanId] = $journals->map(fn ($j) => [
                            'id' => $j->id,
                            'jumlah' => (float) $j->jumlah,
                            'tanggal' => $j->tanggal->format('d M Y'),
                            'keterangan' => $j->keterangan,
                            'sub_judul' => $j->sub_judul,
                        ])->values()->all();
                    }
                }
            }
        }

        // Assign jurnal_pelunasan dan hitung pelunasan_resign secara batch
        $pinjaman->getCollection()->transform(function ($p) use ($jurnalMap) {
            $jurnals = $jurnalMap[$p['id']] ?? [];
            $total = ! empty($jurnals) ? array_sum(array_column($jurnals, 'jumlah')) : 0.0;
            $tanggal = ! empty($jurnals) ? end($jurnals)['tanggal'] : null;

            $p['pelunasan_resign_total'] = $total;
            $p['tanggal_pelunasan_resign'] = $tanggal;
            $p['pelunasan_resign'] = [
                'total' => $total,
                'tanggal' => $tanggal,
            ];
            $p['jurnal_pelunasan'] = $jurnals;

            return $p;
        });

        $daftarCabang = Config::get('cabang');

        // Statistik: 1 query dengan conditional aggregation komprehensif
        $statistik = Pinjaman::query()
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'diajukan' THEN 1 ELSE 0 END) as diajukan,
                SUM(CASE WHEN status = 'approved_admin' THEN 1 ELSE 0 END) as approved_admin,
                SUM(CASE WHEN status = 'approved_bendahara' THEN 1 ELSE 0 END) as approved_bendahara,
                SUM(CASE WHEN status = 'approved_ketua' THEN 1 ELSE 0 END) as approved_ketua,
                SUM(CASE WHEN status = 'aktif' THEN 1 ELSE 0 END) as aktif,
                SUM(CASE WHEN status = 'lunas' THEN 1 ELSE 0 END) as lunas,
                SUM(CASE WHEN status = 'ditolak' THEN 1 ELSE 0 END) as ditolak,
                SUM(CASE WHEN status = 'dihapusbukukan' THEN 1 ELSE 0 END) as dihapusbukukan,
                COALESCE(SUM(CASE WHEN status = 'aktif' THEN nominal ELSE 0 END), 0) as total_nominal_aktif,
                COALESCE(SUM(CASE WHEN status IN ('diajukan', 'approved_admin', 'approved_bendahara', 'approved_ketua') THEN nominal ELSE 0 END), 0) as total_nominal_antrean
            ")
            ->first();

        $menunggakCount = Pinjaman::query()->tap($scopeMenunggak)->count();

        $ajukanId = $request->filled('ajukan_penghapusan') ? (int) $request->input('ajukan_penghapusan') : null;
        $ajukanInitialPinjaman = null;
        if ($ajukanId && $bisaAjukanGlobal) {
            $kandidat = $this->penghapusanService->daftarPinjamanMenunggak($user)->firstWhere('pinjaman_id', $ajukanId);
            if ($kandidat) {
                $ajukanInitialPinjaman = $kandidat;
            }
        }

        return Inertia::render('Pinjaman/Index', [
            'pinjaman' => $pinjaman,
            'filters' => $request->only(['cari', 'status', 'cabang']),
            'cabangAktif' => $cabangAktif->value(),
            'daftarCabang' => $daftarCabang,
            'bisaAjukanPenghapusan' => $bisaAjukanGlobal,
            'ajukanInitialPinjaman' => $ajukanInitialPinjaman,
            'pinjamanMenunggak' => Inertia::lazy(fn () => $bisaAjukanGlobal
                ? $this->penghapusanService->daftarPinjamanMenunggak($user)->values()->all()
                : []),
            'statistik' => [
                'total' => (int) $statistik->total,
                'diajukan' => (int) $statistik->diajukan,
                'approved_admin' => (int) $statistik->approved_admin,
                'approved_bendahara' => (int) $statistik->approved_bendahara,
                'approved_ketua' => (int) $statistik->approved_ketua,
                'aktif' => (int) $statistik->aktif,
                'menunggak' => (int) $menunggakCount,
                'lunas' => (int) $statistik->lunas,
                'ditolak' => (int) $statistik->ditolak,
                'dihapusbukukan' => (int) $statistik->dihapusbukukan,
                'total_nominal_aktif' => (float) $statistik->total_nominal_aktif,
                'total_nominal_antrean' => (float) $statistik->total_nominal_antrean,
            ],
        ]);
    }

    public function show(Pinjaman $pinjaman): Response
    {
        $pinjaman->load(['anggota', 'angsuran', 'pengajuanPercepatan']);

        // Pakai relasi yang sudah di-load (ganti query ulang angsuran)
        $angsuranList = $pinjaman->getRelationValue('angsuran')->sortBy('cicilan_ke')->values()
            ->map(fn ($a) => [
                'id' => $a->id,
                'cicilan_ke' => $a->cicilan_ke,
                'total_bayar' => (float) $a->total_bayar,
                'status' => $a->status,
                'tanggal_jatuh_tempo' => $a->tanggal_jatuh_tempo?->format('d M Y'),
                'tanggal_konfirmasi_bayar' => $a->tanggal_konfirmasi_bayar?->format('d M Y'),
            ]);

        // 1x pluck dishare (ganti pluck identik di 2 helper)
        $angsuranIds = $pinjaman->getRelationValue('angsuran')->pluck('id');
        $pelunasanResign = $this->hitungPelunasanResign($angsuranIds);
        $jurnalPelunasan = $this->ambilJurnalPelunasanResign($angsuranIds);

        return Inertia::render('Pinjaman/Show', [
            'pinjaman' => [
                'id' => $pinjaman->id,
                'anggota_id' => $pinjaman->anggota_id,
                'nama' => $pinjaman->anggota->nama,
                'no_anggota' => $pinjaman->anggota->no_anggota,
                'no_karyawan' => $pinjaman->anggota->no_karyawan,
                'foto_url' => $pinjaman->anggota->foto_url,
                'anggota_status' => $pinjaman->anggota->status,
                'nominal' => (float) $pinjaman->nominal,
                'tenor_bulan' => $pinjaman->tenor_bulan,
                'status' => $pinjaman->status,
                'tanggal_pengajuan' => $pinjaman->tanggal_pengajuan->format('d M Y'),
                'tanggal_cair' => $pinjaman->tanggal_pencairan?->format('d M Y'),
                'ada_bukti_pencairan' => (bool) $pinjaman->bukti_pencairan_path,
                'bukti_pencairan_url' => $pinjaman->bukti_pencairan_path ? route('pinjaman.bukti-pencairan', $pinjaman->id) : null,
                'keperluan' => $pinjaman->keperluan,
            ],
            'angsuran' => $angsuranList,
            'pelunasan_resign' => $pelunasanResign,
            'jurnal_pelunasan' => $jurnalPelunasan,
        ]);
    }

    private function hitungPelunasanResign($angsuranIds): array
    {
        if ($angsuranIds->isEmpty()) {
            return ['total' => 0.0, 'tanggal' => null];
        }

        $row = JurnalKas::where('kategori', 'pelunasan_resign_pinjaman')
            ->whereIn('referensi_id', $angsuranIds)
            ->selectRaw('SUM(jumlah) as total, MAX(tanggal) as tanggal')
            ->first();

        return [
            'total' => (float) ($row->total ?? 0),
            'tanggal' => $row->tanggal ? Carbon::parse($row->tanggal)->format('d M Y') : null,
        ];
    }

    private function ambilJurnalPelunasanResign($angsuranIds): array
    {
        if ($angsuranIds->isEmpty()) {
            return [];
        }

        return JurnalKas::where('kategori', 'pelunasan_resign_pinjaman')
            ->whereIn('referensi_id', $angsuranIds)
            ->orderBy('tanggal')
            ->get()
            ->map(fn ($j) => [
                'id' => $j->id,
                'jumlah' => (float) $j->jumlah,
                'tanggal' => $j->tanggal->format('d M Y'),
                'keterangan' => $j->keterangan,
                'sub_judul' => $j->sub_judul,
            ])
            ->all();
    }

    public function cetakBukti(Request $request, Pinjaman $pinjaman)
    {
        $user = $request->user();
        $pinjaman->loadMissing('anggota');
        $isOnlyPengawas = $user && $user->hasRole('pengawas') && ! $user->hasAnyRole(['super_admin', 'sekretaris', 'bendahara', 'ketua_koperasi']);
        $isMilikSendiri = $user && $pinjaman->anggota?->user_id !== null
            && (int) $pinjaman->anggota->user_id === (int) $user->id;

        abort_if(
            $isOnlyPengawas && ! $isMilikSendiri,
            403,
            'Pengawas hanya dapat mencetak bukti pinjaman miliknya sendiri.'
        );

        abort_unless($pinjaman->status === 'aktif', 403, 'Bukti peminjaman hanya tersedia untuk pinjaman dengan status Aktif.');

        if ($request->query('download')) {
            $pdf = Pdf::loadView('pinjaman.cetak_bukti', $pinjaman->dataBukti());

            return $pdf->download('pinjaman-'.$pinjaman->anggota->no_karyawan.'.pdf');
        }

        return Inertia::render('Pinjaman/CetakBukti', $pinjaman->dataBukti());
    }
}
