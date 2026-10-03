<?php

namespace App\Http\Controllers\Ketua;

use App\Http\Controllers\Controller;
use App\Http\Requests\KeputusanLimitRequest;
use App\Models\PengajuanLimit;
use App\Models\Pinjaman;
use App\Services\Pinjaman\PengajuanLimitService;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class PengajuanLimitController extends Controller
{
    public function __construct(private PengajuanLimitService $service) {}

    public function index(): Response
    {
        $menungguRaw = PengajuanLimit::with('anggota')
            ->where('status', 'approved_bendahara')
            ->latest('tanggal_pengajuan')
            ->get();

        $riwayatRaw = PengajuanLimit::with('anggota')
            ->whereIn('status', ['disetujui', 'ditolak'])
            ->latest('updated_at')
            ->take(20)
            ->get();

        // 2 query preload untuk semua baris (ganti 2 query + agregat per baris)
        $cache = self::preloadPinjaman($menungguRaw->concat($riwayatRaw));

        return Inertia::render('Ketua/PengajuanLimit/Index', [
            'menunggu' => $menungguRaw->map(self::formatItem($cache)),
            'riwayat' => $riwayatRaw->map(self::formatItem($cache)),
        ]);
    }

    public function show(PengajuanLimit $pengajuanLimit): Response
    {
        $pengajuanLimit->load('anggota');

        return Inertia::render('Ketua/PengajuanLimit/Show', [
            'pengajuan' => self::formatItem()($pengajuanLimit),
        ]);
    }

    public function approve(KeputusanLimitRequest $request, PengajuanLimit $pengajuanLimit)
    {
        try {
            $this->service->approveKetua($pengajuanLimit, (float) $request->limit_disetujui, $request->catatan);
        } catch (RuntimeException $e) {
            return back()->withErrors(['keputusan' => $e->getMessage()]);
        }

        return redirect()->route('ketua.pengajuan-limit.index')
            ->with('status', 'Pengajuan limit disetujui.');
    }

    public function reject(KeputusanLimitRequest $request, PengajuanLimit $pengajuanLimit)
    {
        try {
            $this->service->rejectKetua($pengajuanLimit, $request->catatan);
        } catch (RuntimeException $e) {
            return back()->withErrors(['keputusan' => $e->getMessage()]);
        }

        return redirect()->route('ketua.pengajuan-limit.index')
            ->with('status', 'Pengajuan limit ditolak.');
    }

    /**
     * Preload pinjaman aktif + pending untuk semua anggota di daftar pengajuan.
     * Dipakai index (banyak baris) agar formatItem tidak query per baris.
     * Return: ['aktif' => [anggota_id => Collection], 'pending' => [anggota_id => Pinjaman]]
     */
    public static function preloadPinjaman(\Illuminate\Support\Collection $pengajuan): array
    {
        $anggotaIds = $pengajuan->pluck('anggota_id')->unique()->values();
        if ($anggotaIds->isEmpty()) {
            return ['aktif' => [], 'pending' => []];
        }

        $aktif = Pinjaman::whereIn('anggota_id', $anggotaIds)
            ->where('status', 'aktif')
            ->with('angsuran:pinjaman_id,cicilan_ke,nominal_pokok,nominal_bunga,total_bayar,status,tanggal_jatuh_tempo')
            ->get()
            ->groupBy('anggota_id')
            ->all();

        $pending = Pinjaman::whereIn('anggota_id', $anggotaIds)
            ->whereIn('status', ['diajukan', 'approved_bendahara'])
            ->orderByDesc('tanggal_pengajuan')
            ->get()
            ->groupBy('anggota_id')
            ->map(fn ($rows) => $rows->first())
            ->all();

        return ['aktif' => $aktif, 'pending' => $pending];
    }

    public static function formatItem(array $cache = []): \Closure
    {
        return function ($p) use ($cache) {
            $anggota = $p->anggota;
            // Cache terisi (dari index) → kunci hilang berarti memang tidak ada, tanpa fallback query.
            // Cache kosong (dari show) → query seperti semula.
            $pakaiCache = ! empty($cache);

            $daftarAktif = $pakaiCache
                ? ($cache['aktif'][$anggota->id] ?? collect())
                : Pinjaman::where('anggota_id', $anggota->id)
                    ->where('status', 'aktif')
                    ->with('angsuran:pinjaman_id,cicilan_ke,nominal_pokok,nominal_bunga,total_bayar,status,tanggal_jatuh_tempo')
                    ->get();

            $pinjamanAktif = $daftarAktif
                ->map(function ($pin) {
                    $agg = $pin->agregatJadwalAktif();

                    return [
                        'id' => $pin->id,
                        'nominal' => (float) $pin->nominal,
                        'tenor_bulan' => $pin->tenor_bulan,
                        'sisa_cicilan' => $agg['sisa'],
                        'total_cicilan' => $agg['total'],
                        'sisa_total_bayar' => $agg['sisa_bayar'],
                        'jadwal_angsuran' => $pin->angsuran
                            ->where('status', 'belum_bayar')
                            ->sortBy('cicilan_ke')
                            ->values()
                            ->map(fn ($a) => [
                                'cicilan_ke' => $a->cicilan_ke,
                                'nominal_pokok' => (float) $a->nominal_pokok,
                                'nominal_bunga' => (float) $a->nominal_bunga,
                                'total_bayar' => (float) $a->total_bayar,
                                'tanggal_jatuh_tempo' => $a->tanggal_jatuh_tempo->format('d M Y'),
                            ])
                            ->all(),
                    ];
                });

            $pinjamanPending = $pakaiCache
                ? ($cache['pending'][$anggota->id] ?? null)
                : Pinjaman::where('anggota_id', $anggota->id)
                    ->whereIn('status', ['diajukan', 'approved_bendahara'])
                    ->latest('tanggal_pengajuan')
                    ->first();

            return [
                'id' => $p->id,
                'limit_saat_ini' => (float) $p->limit_saat_ini,
                'limit_diminta' => (float) $p->limit_diminta,
                'limit_disetujui_bendahara' => $p->limit_disetujui_bendahara !== null ? (float) $p->limit_disetujui_bendahara : null,
                'limit_disetujui' => $p->limit_disetujui !== null ? (float) $p->limit_disetujui : null,
                'keterangan' => $p->keterangan,
                'status' => $p->status,
                'catatan_bendahara' => $p->catatan_bendahara,
                'catatan_ketua' => $p->catatan_ketua,
                'tanggal_pengajuan' => $p->tanggal_pengajuan->format('d M Y'),
                'anggota' => [
                    'nama' => $anggota->nama,
                    'no_anggota' => $anggota->no_anggota,
                    'no_karyawan' => $anggota->no_karyawan,
                    'cabang' => $anggota->cabang,
                    'foto_url' => $anggota->foto_url,
                    'lama_keanggotaan_tahun' => round($anggota->lama_keanggotaan_tahun, 1),
                ],
                'pinjaman_aktif' => $pinjamanAktif,
                'pinjaman_pending' => $pinjamanPending ? [
                    'nominal' => (float) $pinjamanPending->nominal,
                    'status' => $pinjamanPending->status,
                    'tanggal_pengajuan' => $pinjamanPending->tanggal_pengajuan->format('d M Y'),
                ] : null,
            ];
        };
    }
}
