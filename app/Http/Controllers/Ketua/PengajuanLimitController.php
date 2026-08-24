<?php

namespace App\Http\Controllers\Ketua;

use App\Http\Controllers\Controller;
use App\Http\Requests\KeputusanPinjamanRequest;
use App\Models\PengajuanLimit;
use App\Models\Pinjaman;
use App\Services\Pinjaman\PengajuanLimitService;
use Inertia\Inertia;
use Inertia\Response;

class PengajuanLimitController extends Controller
{
    public function __construct(private PengajuanLimitService $service) {}

    public function index(): Response
    {
        $menunggu = PengajuanLimit::with('anggota')
            ->where('status', 'diajukan')
            ->latest('tanggal_pengajuan')
            ->get()
            ->map($this->formatRingkas());

        $riwayat = PengajuanLimit::with('anggota')
            ->whereIn('status', ['disetujui', 'ditolak'])
            ->latest('updated_at')
            ->take(20)
            ->get()
            ->map($this->formatRingkas());

        return Inertia::render('Ketua/PengajuanLimit/Index', [
            'menunggu' => $menunggu,
            'riwayat' => $riwayat,
        ]);
    }

    public function approve(KeputusanPinjamanRequest $request, PengajuanLimit $pengajuanLimit)
    {
        $this->service->setujui($pengajuanLimit, $request->catatan);

        return redirect()->route('ketua.pengajuan-limit.index')
            ->with('status', 'Pengajuan limit disetujui.');
    }

    public function reject(KeputusanPinjamanRequest $request, PengajuanLimit $pengajuanLimit)
    {
        $this->service->tolak($pengajuanLimit, $request->catatan);

        return redirect()->route('ketua.pengajuan-limit.index')
            ->with('status', 'Pengajuan limit ditolak.');
    }

    private function formatRingkas(): \Closure
    {
        return function ($p) {
            $anggota = $p->anggota;

            // Pinjaman aktif anggota
            $pinjamanAktif = Pinjaman::where('anggota_id', $anggota->id)
                ->where('status', 'aktif')
                ->with('angsuran:pinjaman_id,cicilan_ke,nominal_pokok,nominal_bunga,total_bayar,status,tanggal_jatuh_tempo')
                ->get()
                ->map(fn ($pin) => [
                    'id' => $pin->id,
                    'nominal' => (float) $pin->nominal,
                    'tenor_bulan' => $pin->tenor_bulan,
                    'sisa_cicilan' => $pin->sisaCicilanAktif(),
                    'total_cicilan' => $pin->totalCicilanAktif(),
                    'sisa_total_bayar' => $pin->sisaTotalBayarAktif(),
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
                ]);

            // Pinjaman yang sedang diajukan (belum aktif)
            $pinjamanPending = Pinjaman::where('anggota_id', $anggota->id)
                ->whereIn('status', ['diajukan', 'approved_bendahara'])
                ->latest('tanggal_pengajuan')
                ->first();

            return [
                'id' => $p->id,
                'limit_saat_ini' => (float) $p->limit_saat_ini,
                'limit_diminta' => (float) $p->limit_diminta,
                'keterangan' => $p->keterangan,
                'status' => $p->status,
                'catatan_ketua' => $p->catatan_ketua,
                'tanggal_pengajuan' => $p->tanggal_pengajuan->format('d M Y'),
                'anggota' => [
                    'nama' => $anggota->nama,
                    'no_anggota' => $anggota->no_anggota,
                    'cabang' => $anggota->cabang,
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
