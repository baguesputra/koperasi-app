<?php

namespace App\Http\Controllers;

use App\Helpers\TerbilangHelper;
use App\Models\Anggota;
use App\Models\Angsuran;
use App\Models\AngsuranPercepatan;
use App\Models\JurnalKas;
use App\Models\PengajuanPenghapusanPinjaman;
use App\Models\Pinjaman;
use App\Models\User;
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

        $query = Pinjaman::with($this->relasiDetailPinjaman());

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
            ->through(fn (Pinjaman $p) => $this->formatItemPinjaman($p, $user, $bisaAjukanGlobal, $hariIniStart));

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

        // Assign jurnal_pelunasan, hitung pelunasan_resign, dan finalisasi info penyelesaian lunas secara batch
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
            $p['penyelesaian'] = $this->bangunInfoPenyelesaian($p, $total, $tanggal);

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

    public function show(Request $request, Pinjaman $pinjaman): Response
    {
        $user = $request->user();
        $pinjaman->load($this->relasiDetailPinjaman());

        $bisaAjukanGlobal = $user ? $user->can('penghapusan-pinjaman.ajukan') : false;
        $detail = $this->formatItemPinjaman($pinjaman, $user, $bisaAjukanGlobal, now()->startOfDay());

        $angsuranIds = $pinjaman->getRelationValue('angsuran')->pluck('id');
        $pelunasanResign = $this->hitungPelunasanResign($angsuranIds);
        $jurnalPelunasan = $this->ambilJurnalPelunasanResign($angsuranIds);

        $detail['pelunasan_resign_total'] = (float) ($pelunasanResign['total'] ?? 0);
        $detail['tanggal_pelunasan_resign'] = $pelunasanResign['tanggal'] ?? null;
        $detail['pelunasan_resign'] = $pelunasanResign;
        $detail['jurnal_pelunasan'] = $jurnalPelunasan;
        $detail['penyelesaian'] = $this->bangunInfoPenyelesaian(
            $detail,
            $detail['pelunasan_resign_total'],
            $detail['tanggal_pelunasan_resign'],
        );

        return Inertia::render('Pinjaman/Show', [
            'pinjaman' => $detail,
            'angsuran' => $detail['angsuran'],
            'pelunasan_resign' => $pelunasanResign,
            'jurnal_pelunasan' => $jurnalPelunasan,
        ]);
    }

    /**
     * Daftar relasi standar agar seluruh detail pinjaman lintas status termuat dalam query konstan.
     *
     * @return array<int|string, mixed>
     */
    private function relasiDetailPinjaman(): array
    {
        return [
            'anggota',
            'pengaju:id,name,no_karyawan',
            'dicairkanOleh:id,name,no_karyawan',
            'angsuran' => fn ($q) => $q->orderBy('cicilan_ke')->with('confirmedBy:id,name'),
            'pengajuanPercepatan' => fn ($q) => $q->latest('id')->with([
                'angsuranBaru' => fn ($ab) => $ab->orderBy('cicilan_ke')->with('confirmedBy:id,name'),
            ]),
            'pengajuanPenghapusan' => fn ($q) => $q->latest('id')->with([
                'pengaju:id,name,no_karyawan',
                'approverBendahara:id,name,no_karyawan',
                'approverKetua:id,name,no_karyawan',
                'approverPengawas3:id,name,no_karyawan',
                'approverPengawas2:id,name,no_karyawan',
                'approverPengawas1:id,name,no_karyawan',
                'penolak:id,name,no_karyawan',
            ]),
        ];
    }

    /**
     * Bentuk payload lengkap pinjaman yang adaptif untuk seluruh 9 filter status.
     *
     * @return array<string, mixed>
     */
    private function formatItemPinjaman(Pinjaman $p, ?User $user, bool $bisaAjukanGlobal, Carbon $hariIniStart): array
    {
        $jadwal = $p->jadwalAktif()->sortBy('cicilan_ke')->values();
        $lunas = $jadwal->where('status', 'lunas');
        $belum = $jadwal->where('status', 'belum_bayar')->sortBy('cicilan_ke')->values();
        $terlambatItems = $p->status === 'aktif'
            ? $belum->filter(fn ($a) => $a->tanggal_jatuh_tempo && $a->tanggal_jatuh_tempo->lt($hariIniStart))->values()
            : collect();

        $nominalPinjaman = round((float) $p->nominal, 2);
        $totalPokokTerbayar = round((float) $lunas->sum('nominal_pokok'), 2);
        $totalBungaTerbayar = round((float) $lunas->sum('nominal_bunga'), 2);
        $sisaPokok = in_array($p->status, ['lunas', 'dihapusbukukan'], true)
            ? 0.0
            : round(max(0.0, $nominalPinjaman - $totalPokokTerbayar), 2);
        $sisaBunga = $p->status === 'aktif'
            ? round((float) $belum->sum('nominal_bunga'), 2)
            : 0.0;
        $sisaCicilan = $belum->count();
        $jumlahTerlambat = $terlambatItems->count();

        $riwayatPenghapusan = $p->pengajuanPenghapusan;
        $pengAktif = $riwayatPenghapusan->first(fn ($ph) => ! in_array($ph->status, ['ditolak', 'disetujui'], true));
        $pengDisetujui = $riwayatPenghapusan->firstWhere('status', 'disetujui');
        $pengDitolak = $riwayatPenghapusan->firstWhere('status', 'ditolak');

        $isMilikSendiri = $user && $p->anggota?->user_id !== null
            && (int) $p->anggota->user_id === (int) $user->id;

        $angsuranMapped = $jadwal->map(function ($a) use ($p, $hariIniStart) {
            $isPercepatan = $a instanceof AngsuranPercepatan;

            return [
                'id' => $isPercepatan ? ('p-'.$a->id) : $a->id,
                'cicilan_ke' => (int) $a->cicilan_ke,
                'nominal_pokok' => (float) $a->nominal_pokok,
                'nominal_bunga' => (float) $a->nominal_bunga,
                'total_bayar' => (float) $a->total_bayar,
                'status' => $a->status,
                'is_percepatan' => $isPercepatan,
                'is_terlambat' => $p->status === 'aktif'
                    && $a->status === 'belum_bayar'
                    && $a->tanggal_jatuh_tempo
                    && $a->tanggal_jatuh_tempo->lt($hariIniStart),
                'tanggal_jatuh_tempo' => $a->tanggal_jatuh_tempo?->format('d M Y'),
                'tanggal_konfirmasi_bayar' => $a->tanggal_konfirmasi_bayar?->format('d M Y'),
                'confirmed_by_nama' => $a->relationLoaded('confirmedBy') ? $a->confirmedBy?->name : null,
            ];
        })->values()->all();

        $angsuranDigantikan = $p->angsuran
            ->where('status', 'digantikan')
            ->sortBy('cicilan_ke')
            ->values()
            ->map(fn ($a) => [
                'id' => $a->id,
                'cicilan_ke' => (int) $a->cicilan_ke,
                'nominal_pokok' => (float) $a->nominal_pokok,
                'nominal_bunga' => (float) $a->nominal_bunga,
                'total_bayar' => (float) $a->total_bayar,
                'tanggal_jatuh_tempo' => $a->tanggal_jatuh_tempo?->format('d M Y'),
            ])
            ->all();

        $simulasi = [];
        $estimasiTotalBunga = 0.0;
        $estimasiTotalBayar = 0.0;
        $estimasiCicilanPertama = 0.0;
        if (in_array($p->status, ['diajukan', 'approved_admin', 'approved_bendahara', 'approved_ketua'], true)) {
            $simulasi = $this->bunga->buatJadwal(
                (float) $p->nominal,
                (int) $p->tenor_bulan,
                (float) $p->persentase_bunga,
            );
            $estimasiTotalBunga = round((float) array_sum(array_column($simulasi, 'nominal_bunga')), 2);
            $estimasiTotalBayar = round((float) array_sum(array_column($simulasi, 'total_bayar')), 2);
            $estimasiCicilanPertama = (float) ($simulasi[0]['total_bayar'] ?? 0);
        }

        $percepatanAktifModel = $p->pengajuanPercepatan->firstWhere('status', 'aktif');
        $percepatanMenungguModel = $p->pengajuanPercepatan->first(fn ($pp) => in_array($pp->status, ['diajukan', 'approved_bendahara'], true));
        $formatPercepatan = function ($pp): ?array {
            if (! $pp) {
                return null;
            }
            $labelTipe = match ($pp->tipe) {
                'percepat' => 'Percepat Pelunasan',
                'perpanjang' => 'Perpanjang Tenor',
                'lunas_total' => 'Pelunasan Sekaligus',
                default => (string) $pp->tipe,
            };

            return [
                'id' => $pp->id,
                'tipe' => $pp->tipe,
                'tipe_label' => $labelTipe,
                'tenor_lama' => (int) $pp->tenor_lama,
                'tenor_baru' => (int) $pp->tenor_baru,
                'bulan_berlaku' => match ($pp->bulan_berlaku) {
                    'bulan_ini' => 'Bulan Ini',
                    'bulan_depan' => 'Bulan Depan',
                    default => $pp->bulan_berlaku,
                },
                'status' => $pp->status,
                'keterangan' => $pp->keterangan,
                'tanggal_pengajuan' => $pp->tanggal_pengajuan?->format('d M Y'),
            ];
        };

        $penghapusanAktif = $this->ringkasPenghapusan($pengAktif);
        $penghapusanDisetujui = $this->ringkasPenghapusan($pengDisetujui);
        $penghapusanDitolakTerakhir = $this->ringkasPenghapusan($pengDitolak);

        $infoPenolakan = $this->ekstrakInfoPenolakan($p);
        $tanggalLunasAktual = $p->status === 'lunas'
            ? ($lunas->pluck('tanggal_konfirmasi_bayar')->filter()->sortDesc()->first()?->format('d M Y') ?? $p->updated_at?->format('d M Y'))
            : null;

        return [
            'id' => $p->id,
            'anggota_id' => $p->anggota_id,
            'nomor_dokumen' => $p->nomor_dokumen ?? ('#'.$p->id),
            'nomor_dokumen_resmi' => $p->nomor_dokumen,
            'nama' => $p->anggota->nama,
            'no_anggota' => $p->anggota->no_anggota,
            'no_karyawan' => $p->anggota->no_karyawan,
            'cabang' => $p->anggota->cabang,
            'foto_url' => $p->anggota->foto_url,
            'anggota_status' => $p->anggota->status,
            'anggota_info' => [
                'jabatan' => $p->anggota->jabatan,
                'unit_bisnis' => $p->anggota->unit_bisnis,
                'department' => $p->anggota->department,
                'lama_keanggotaan' => Anggota::formatLamaKeanggotaan($p->anggota->tanggal_jadi_anggota),
            ],
            'is_milik_sendiri' => $isMilikSendiri,
            'nominal' => (float) $p->nominal,
            'terbilang' => TerbilangHelper::angkaKeTerbilang((float) $p->nominal),
            'tenor_bulan' => (int) $p->tenor_bulan,
            'persentase_bunga' => (float) $p->persentase_bunga,
            'nominal_diminta' => $p->nominal_diminta !== null ? (float) $p->nominal_diminta : (float) $p->nominal,
            'tenor_diminta' => (int) ($p->tenor_diminta ?? $p->tenor_bulan),
            'nominal_disetujui_bendahara' => $p->nominal_disetujui_bendahara !== null ? (float) $p->nominal_disetujui_bendahara : null,
            'tenor_disetujui_bendahara' => $p->tenor_disetujui_bendahara !== null ? (int) $p->tenor_disetujui_bendahara : null,
            'nominal_disetujui' => $p->nominal_disetujui !== null ? (float) $p->nominal_disetujui : null,
            'tenor_disetujui' => $p->tenor_disetujui !== null ? (int) $p->tenor_disetujui : null,
            'status' => $p->status,
            'cair_oleh_bendahara' => (bool) $p->cair_oleh_bendahara,
            'mandiri_bendahara' => $p->catatan_bendahara === 'Diajukan mandiri oleh Bendahara',
            'mandiri_sekretaris' => $p->catatan_admin === 'Diajukan mandiri oleh Sekretaris',
            'sudah_pakai_privilege_reloan' => (bool) $p->sudah_pakai_privilege_reloan,
            'tanggal_pengajuan' => $p->tanggal_pengajuan->format('d M Y'),
            'tanggal_cair' => $p->tanggal_pencairan?->format('d M Y'),
            'tanggal_lunas_terakhir' => $tanggalLunasAktual,
            'disetujui_ketua_pada' => ($p->disetujui_ketua_pada ?? $p->disetujui_pada)?->format('d M Y, H:i'),
            'pengaju_nama' => $p->pengaju?->name ?? $p->anggota->nama,
            'dicairkan_oleh_nama' => $p->dicairkanOleh?->name,
            'rekening' => [
                'bank' => $p->snapshot_bank,
                'no_rekening' => $p->snapshot_no_rekening,
                'atas_nama' => $p->snapshot_atas_nama,
            ],
            'ada_bukti_pencairan' => (bool) $p->bukti_pencairan_path,
            'bukti_pencairan_url' => $p->bukti_pencairan_path ? route('pinjaman.bukti-pencairan', $p->id) : null,
            'keperluan' => $p->keperluan,
            'catatan_admin' => $p->catatan_admin,
            'catatan_bendahara' => $p->catatan_bendahara,
            'catatan_ketua' => $p->catatan_ketua,
            'catatan_pencairan' => $p->catatan_pencairan,
            'timeline_verifikasi' => $p->getVerificationTimeline(),
            'info_penolakan' => $infoPenolakan,
            'total_pokok_terbayar' => $totalPokokTerbayar,
            'total_bunga_terbayar' => $totalBungaTerbayar,
            'total_terbayar' => round($totalPokokTerbayar + $totalBungaTerbayar, 2),
            'total_cicilan_aktif' => $jadwal->count(),
            'lunas_cicilan_aktif' => $lunas->count(),
            'sisa_cicilan' => $sisaCicilan,
            'sisa_pokok' => $sisaPokok,
            'sisa_bunga' => $sisaBunga,
            'sisa_total_tagihan' => round($sisaPokok + $sisaBunga, 2),
            'jumlah_terlambat' => $jumlahTerlambat,
            'tunggakan' => [
                'jumlah_terlambat' => $jumlahTerlambat,
                'nominal_tunggakan' => round((float) $terlambatItems->sum('total_bayar'), 2),
                'jatuh_tempo_tertua' => $terlambatItems->first()?->tanggal_jatuh_tempo?->format('d M Y'),
                'cicilan_list' => $terlambatItems->pluck('cicilan_ke')->map(fn ($ke) => (int) $ke)->values()->all(),
            ],
            'simulasi_angsuran' => $simulasi,
            'estimasi_total_bunga' => $estimasiTotalBunga,
            'estimasi_total_bayar' => $estimasiTotalBayar,
            'estimasi_cicilan_pertama' => $estimasiCicilanPertama,
            'percepatan_aktif' => $formatPercepatan($percepatanAktifModel),
            'percepatan_menunggu' => $formatPercepatan($percepatanMenungguModel),
            'penghapusan_aktif' => $penghapusanAktif,
            'penghapusan_disetujui' => $penghapusanDisetujui,
            'penghapusan_ditolak_terakhir' => $penghapusanDitolakTerakhir,
            'bisa_ajukan_penghapusan' => $bisaAjukanGlobal
                && $p->status === 'aktif'
                && $sisaPokok > 0
                && $pengAktif === null
                && ! $isMilikSendiri,
            'aksi_persetujuan' => $this->tentukanAksiPersetujuan($user, $p, $isMilikSendiri, $pengAktif, $jumlahTerlambat),
            'pelunasan_resign_total' => 0.0,
            'tanggal_pelunasan_resign' => null,
            'is_resign' => $p->anggota->status === 'resign',
            'angsuran' => $angsuranMapped,
            'angsuran_digantikan' => $angsuranDigantikan,
            'pelunasan_resign' => [
                'total' => 0.0,
                'tanggal' => null,
            ],
            'jurnal_pelunasan' => [], // diisi via batch / show
            'penyelesaian' => null, // diisi setelah pelunasan_resign dihitung
        ];
    }

    private function ringkasPenghapusan(?PengajuanPenghapusanPinjaman $ph): ?array
    {
        if (! $ph) {
            return null;
        }

        return [
            'id' => $ph->id,
            'nomor_dokumen' => $ph->nomor_dokumen,
            'status' => $ph->status,
            'status_label' => PengajuanPenghapusanPinjaman::STATUS_LABEL[$ph->status] ?? $ph->status,
            'tanggal_pengajuan' => $ph->tanggal_pengajuan?->format('d M Y'),
            'disetujui_pada' => $ph->disetujui_pada?->format('d M Y, H:i'),
            'ditolak_pada' => $ph->ditolak_pada?->format('d M Y, H:i'),
            'pengaju_nama' => $ph->pengaju?->name ?? '-',
            'alasan' => $ph->alasan,
            'total_pokok_terbayar' => (float) $ph->total_pokok_terbayar,
            'sisa_cicilan' => (int) $ph->sisa_cicilan,
            'sisa_pokok' => (float) $ph->sisa_pokok,
            'sisa_bunga' => (float) $ph->sisa_bunga,
            'tahap_ditolak' => $ph->tahap_ditolak,
            'tahap_ditolak_label' => $ph->tahap_ditolak
                ? (PengajuanPenghapusanPinjaman::TAHAP_DITOLAK_LABEL[$ph->tahap_ditolak] ?? $ph->tahap_ditolak)
                : null,
            'catatan_tolak' => $ph->catatan_tolak,
            'penolak_nama' => $ph->penolak?->name,
            'timeline' => $ph->getTimelineApproval(),
        ];
    }

    /**
     * @return array{tahap: string, alasan: string, tanggal: ?string}|null
     */
    private function ekstrakInfoPenolakan(Pinjaman $p): ?array
    {
        if ($p->status !== 'ditolak') {
            return null;
        }

        $catatanBendaharaValid = ! empty($p->catatan_bendahara)
            && $p->catatan_bendahara !== 'Diajukan mandiri oleh Bendahara';

        if (! empty($p->catatan_ketua)) {
            return [
                'tahap' => 'Ketua Koperasi',
                'alasan' => $p->catatan_ketua,
                'tanggal' => $p->updated_at?->format('d M Y, H:i'),
            ];
        }

        if ($catatanBendaharaValid) {
            return [
                'tahap' => 'Bendahara',
                'alasan' => $p->catatan_bendahara,
                'tanggal' => $p->updated_at?->format('d M Y, H:i'),
            ];
        }

        return [
            'tahap' => 'Sekretaris',
            'alasan' => $p->catatan_admin ?: 'Tidak memenuhi ketentuan verifikasi awal.',
            'tanggal' => $p->updated_at?->format('d M Y, H:i'),
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array{metode: string, tanggal_lunas: ?string}|null
     */
    private function bangunInfoPenyelesaian(array $item, float $pelunasanResignTotal, ?string $tanggalResign): ?array
    {
        if (($item['status'] ?? null) !== 'lunas') {
            return null;
        }

        $percepatanAktif = $item['percepatan_aktif'] ?? null;

        if ($pelunasanResignTotal > 0) {
            $metode = 'Potong Simpanan / Tabungan Resign';
            $tanggalLunas = $tanggalResign ?? ($item['tanggal_lunas_terakhir'] ?? null);
        } elseif ($percepatanAktif && ($percepatanAktif['tipe'] ?? null) === 'lunas_total') {
            $metode = 'Pelunasan Sekaligus (Dipercepat)';
            $tanggalLunas = $item['tanggal_lunas_terakhir'] ?? null;
        } elseif ($percepatanAktif) {
            $metode = 'Angsuran Perubahan Tenor';
            $tanggalLunas = $item['tanggal_lunas_terakhir'] ?? null;
        } else {
            $metode = 'Angsuran Reguler Penuh';
            $tanggalLunas = $item['tanggal_lunas_terakhir'] ?? null;
        }

        return [
            'metode' => $metode,
            'tanggal_lunas' => $tanggalLunas,
        ];
    }

    /**
     * @return array{label: string, href: string}|null
     */
    private function tentukanAksiPersetujuan(
        ?User $user,
        Pinjaman $p,
        bool $isMilikSendiri,
        ?PengajuanPenghapusanPinjaman $pengAktif,
        int $jumlahTerlambat,
    ): ?array {
        if (! $user) {
            return null;
        }

        if ($pengAktif && $this->penghapusanService->bisaDiprosesOleh($pengAktif, $user)) {
            return [
                'label' => 'Putuskan Penghapusan',
                'href' => route('persetujuan.index', ['kategori' => 'penghapusan']),
            ];
        }

        if ($p->status === 'diajukan' && ! $isMilikSendiri && $user->can('pinjaman.tinjau-admin')) {
            return [
                'label' => 'Verifikasi di Pusat Persetujuan',
                'href' => route('persetujuan.index', ['kategori' => 'pinjaman', 'tahap' => 'admin']),
            ];
        }

        if ($p->status === 'approved_admin' && ! $isMilikSendiri && $user->can('pinjaman.tinjau-bendahara')) {
            return [
                'label' => 'Tinjau di Pusat Persetujuan',
                'href' => route('persetujuan.index', ['kategori' => 'pinjaman', 'tahap' => 'bendahara']),
            ];
        }

        if ($p->status === 'approved_bendahara' && ! $isMilikSendiri && $user->can('pinjaman.approve-ketua')) {
            return [
                'label' => 'Setujui di Pusat Persetujuan',
                'href' => route('persetujuan.index', ['kategori' => 'pinjaman', 'tahap' => 'ketua']),
            ];
        }

        if (
            $p->status === 'approved_ketua'
            && $user->can('pinjaman.cairkan')
            && (! $isMilikSendiri || (! $p->cair_oleh_bendahara && $p->disetujui_ketua_pada !== null))
        ) {
            return [
                'label' => 'Cairkan Dana',
                'href' => route('persetujuan.index', ['kategori' => 'pencairan']),
            ];
        }

        if ($p->status === 'aktif' && $jumlahTerlambat > 0 && ! $pengAktif && $user->can('angsuran.konfirmasi')) {
            return [
                'label' => 'Konfirmasi Angsuran',
                'href' => route('bendahara.angsuran.index'),
            ];
        }

        return null;
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
