<?php

namespace App\Http\Controllers\Bendahara;

use App\Http\Controllers\Controller;
use App\Http\Requests\KeputusanPinjamanRequest;
use App\Models\PengajuanPercepatan;
use App\Services\Pinjaman\PercepatanPinjamanService;
use Inertia\Inertia;
use Inertia\Response;

class PercepatanController extends Controller
{
    public function __construct(private PercepatanPinjamanService $service) {}

    public function index(): Response
    {
        $menunggu = PengajuanPercepatan::with('pinjaman.anggota')
            ->where('status', 'diajukan')
            ->latest('tanggal_pengajuan')
            ->get()
            ->map($this->formatRingkas());

        $riwayat = PengajuanPercepatan::with('pinjaman.anggota')
            ->whereIn('status', ['approved_bendahara', 'aktif', 'ditolak'])
            ->whereNotNull('catatan_bendahara')
            ->latest('updated_at')
            ->take(20)
            ->get()
            ->map($this->formatRingkas());

        return Inertia::render('Bendahara/Percepatan/Index', [
            'menunggu' => $menunggu,
            'riwayat' => $riwayat,
        ]);
    }

    public function show(PengajuanPercepatan $percepatan): Response
    {
        $percepatan->load('pinjaman.anggota');

        return Inertia::render('Bendahara/Percepatan/Show', [
            'pengajuan' => $this->formatDetail($percepatan),
        ]);
    }

    public function approve(KeputusanPinjamanRequest $request, PengajuanPercepatan $percepatan)
    {
        $this->service->approveBendahara($percepatan, $request->catatan);

        return redirect()->route('bendahara.percepatan.index')->with('status', 'Pengajuan disetujui, diteruskan ke Ketua.');
    }

    public function reject(KeputusanPinjamanRequest $request, PengajuanPercepatan $percepatan)
    {
        $this->service->rejectBendahara($percepatan, $request->catatan);

        return redirect()->route('bendahara.percepatan.index')->with('status', 'Pengajuan ditolak.');
    }

    private function tipeLabel($tipe)
    {
        return ['percepat' => 'Percepat Pelunasan', 'perpanjang' => 'Perpanjang Tenor', 'lunas_total' => 'Lunas Sekarang'][$tipe] ?? $tipe;
    }

    private function formatRingkas(): \Closure
    {
        return fn ($p) => [
            'id' => $p->id,
            'nama' => $p->pinjaman->anggota->nama,
            'tipe' => $this->tipeLabel($p->tipe),
            'status' => $p->status,
            'tanggal_pengajuan' => $p->tanggal_pengajuan->format('d M Y'),
        ];
    }

    private function formatDetail($p)
    {
        return [
            'id' => $p->id,
            'tipe' => $this->tipeLabel($p->tipe),
            'tenor_lama' => $p->tenor_lama,
            'tenor_baru' => $p->tenor_baru,
            'keterangan' => $p->keterangan,
            'status' => $p->status,
            'catatan_bendahara' => $p->catatan_bendahara,
            'tanggal_pengajuan' => $p->tanggal_pengajuan->format('d M Y'),
            'pinjaman' => [
                'nominal' => (float) $p->pinjaman->nominal,
                'sisa_angsuran' => $p->pinjaman->sisaAngsuran(),
            ],
            'anggota' => [
                'nama' => $p->pinjaman->anggota->nama,
                'no_anggota' => $p->pinjaman->anggota->no_anggota,
            ],
        ];
    }
}
