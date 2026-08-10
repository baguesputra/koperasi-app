<?php

namespace App\Http\Controllers\Ketua;

use App\Http\Controllers\Controller;
use App\Http\Requests\KeputusanPinjamanRequest;
use App\Models\Pinjaman;
use App\Services\Pinjaman\PersetujuanPinjamanService;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use App\Helpers\TerbilangHelper;

class PinjamanController extends Controller
{
    public function __construct(private PersetujuanPinjamanService $persetujuan) {}

    public function index(): Response
    {
        $menungguApproval = Pinjaman::with('anggota')
            ->where('status', 'approved_bendahara')
            ->latest('tanggal_pengajuan')
            ->get()
            ->map($this->formatRingkas());

        $riwayat = Pinjaman::with('anggota')
            ->whereIn('status', ['aktif', 'lunas', 'ditolak'])
            ->whereNotNull('catatan_ketua')
            ->latest('updated_at')
            ->take(20)
            ->get()
            ->map($this->formatRingkas());

        return Inertia::render('Ketua/Pinjaman/Index', [
            'menungguApproval' => $menungguApproval,
            'riwayat' => $riwayat,
        ]);
    }

    public function show(Pinjaman $pinjaman): Response
    {
        $pinjaman->load('anggota');

        return Inertia::render('Ketua/Pinjaman/Show', [
            'pinjaman' => [
                'id' => $pinjaman->id,
                'nominal' => (float) $pinjaman->nominal,
                'terbilang' => TerbilangHelper::angkaKeTerbilang($pinjaman->nominal),
                'tenor_bulan' => $pinjaman->tenor_bulan,
                'persentase_bunga' => (float) $pinjaman->persentase_bunga,
                'keperluan' => $pinjaman->keperluan,
                'rekening' => [
                    'bank' => $pinjaman->snapshot_bank,
                    'no_rekening' => $pinjaman->snapshot_no_rekening,
                    'atas_nama' => $pinjaman->snapshot_atas_nama,
                ],
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
        try {
            $this->persetujuan->approveKetua($pinjaman, $request->catatan);
        } catch (RuntimeException $e) {
            return back()->withErrors(['keputusan' => $e->getMessage()]);
        }

        return redirect()->route('ketua.pinjaman.index')
            ->with('status', 'Pinjaman disetujui dan dana telah dicairkan.');
    }

    public function reject(KeputusanPinjamanRequest $request, Pinjaman $pinjaman)
    {
        $this->persetujuan->rejectKetua($pinjaman, $request->catatan);

        return redirect()->route('ketua.pinjaman.index')
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