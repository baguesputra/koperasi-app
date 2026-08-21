<?php

namespace App\Http\Controllers\Ketua;

use App\Http\Controllers\Controller;
use App\Http\Requests\KeputusanPinjamanRequest;
use App\Models\PengajuanLimit;
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

    public function show(PengajuanLimit $pengajuanLimit): Response
    {
        $pengajuanLimit->load('anggota');

        return Inertia::render('Ketua/PengajuanLimit/Show', [
            'pengajuan' => [
                'id' => $pengajuanLimit->id,
                'limit_saat_ini' => (float) $pengajuanLimit->limit_saat_ini,
                'limit_diminta' => (float) $pengajuanLimit->limit_diminta,
                'keterangan' => $pengajuanLimit->keterangan,
                'status' => $pengajuanLimit->status,
                'tanggal_pengajuan' => $pengajuanLimit->tanggal_pengajuan->format('d M Y'),
                'anggota' => [
                    'nama' => $pengajuanLimit->anggota->nama,
                    'no_anggota' => $pengajuanLimit->anggota->no_anggota,
                    'cabang' => $pengajuanLimit->anggota->cabang,
                    'lama_keanggotaan_tahun' => round($pengajuanLimit->anggota->lama_keanggotaan_tahun, 1),
                ],
            ],
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
        return fn ($p) => [
            'id' => $p->id,
            'nama' => $p->anggota->nama,
            'limit_diminta' => (float) $p->limit_diminta,
            'status' => $p->status,
            'tanggal_pengajuan' => $p->tanggal_pengajuan->format('d M Y'),
        ];
    }
}
