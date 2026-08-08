<?php

namespace App\Http\Controllers\Bendahara;

use App\Http\Controllers\Controller;
use App\Http\Requests\KeputusanPinjamanRequest;
use App\Models\Pinjaman;
use App\Services\Pinjaman\PersetujuanPinjamanService;
use Inertia\Inertia;
use Inertia\Response;

class PinjamanController extends Controller
{
    public function __construct(private PersetujuanPinjamanService $persetujuan) {}

    public function index(): Response
    {
        $menungguTinjauan = Pinjaman::with('anggota')
            ->where('status', 'diajukan')
            ->latest('tanggal_pengajuan')
            ->get()
            ->map($this->formatRingkas());

        $riwayat = Pinjaman::with('anggota')
            ->whereIn('status', ['approved_bendahara', 'aktif', 'lunas', 'ditolak'])
            ->whereNotNull('catatan_bendahara')
            ->latest('updated_at')
            ->take(20)
            ->get()
            ->map($this->formatRingkas());

        return Inertia::render('Bendahara/Pinjaman/Index', [
            'menungguTinjauan' => $menungguTinjauan,
            'riwayat' => $riwayat,
        ]);
    }

    public function show(Pinjaman $pinjaman): Response
    {
        $pinjaman->load('anggota');

        return Inertia::render('Bendahara/Pinjaman/Show', [
            'pinjaman' => [
                'id' => $pinjaman->id,
                'nominal' => (float) $pinjaman->nominal,
                'tenor_bulan' => $pinjaman->tenor_bulan,
                'persentase_bunga' => (float) $pinjaman->persentase_bunga,
                'status' => $pinjaman->status,
                'tanggal_pengajuan' => $pinjaman->tanggal_pengajuan->format('d M Y'),
                'sudah_pakai_privilege_reloan' => $pinjaman->sudah_pakai_privilege_reloan,
                'catatan_bendahara' => $pinjaman->catatan_bendahara,
                'anggota' => [
                    'nama' => $pinjaman->anggota->nama,
                    'no_anggota' => $pinjaman->anggota->no_anggota,
                    'cabang' => $pinjaman->anggota->cabang,
                    'jabatan' => $pinjaman->anggota->jabatan,
                    'lama_keanggotaan_tahun' => round($pinjaman->anggota->lama_keanggotaan_tahun, 1),
                ],
            ],
        ]);
    }

    public function approve(KeputusanPinjamanRequest $request, Pinjaman $pinjaman)
    {
        $this->persetujuan->approveBendahara($pinjaman, $request->catatan);

        return redirect()->route('bendahara.pinjaman.index')
            ->with('status', 'Pengajuan pinjaman disetujui dan diteruskan ke Ketua Koperasi.');
    }

    public function reject(KeputusanPinjamanRequest $request, Pinjaman $pinjaman)
    {
        $this->persetujuan->rejectBendahara($pinjaman, $request->catatan);

        return redirect()->route('bendahara.pinjaman.index')
            ->with('status', 'Pengajuan pinjaman ditolak.');
    }

    private function formatRingkas(): \Closure
    {
        return fn ($p) => [
            'id' => $p->id,
            'nama' => $p->anggota->nama,
            'nominal' => (float) $p->nominal,
            'tenor_bulan' => $p->tenor_bulan,
            'status' => $p->status,
            'tanggal_pengajuan' => $p->tanggal_pengajuan->format('d M Y'),
        ];
    }
}