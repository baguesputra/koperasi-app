<?php

namespace App\Http\Controllers\Ketua;

use App\Http\Controllers\Controller;
use App\Models\PengajuanPercepatan;
use App\Services\Pinjaman\PercepatanPinjamanService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PercepatanController extends Controller
{
    public function __construct(private PercepatanPinjamanService $service) {}

    public function index(): Response
    {
        $menunggu = PengajuanPercepatan::with('pinjaman.anggota')
            ->where('status', 'approved_bendahara')
            ->latest('tanggal_pengajuan')
            ->get()
            ->map($this->formatRingkas());

        $riwayat = PengajuanPercepatan::with('pinjaman.anggota')
            ->whereIn('status', ['aktif', 'ditolak'])
            ->whereNotNull('catatan_ketua')
            ->latest('updated_at')
            ->take(20)
            ->get()
            ->map($this->formatRingkas());

        return Inertia::render('Ketua/Percepatan/Index', [
            'menunggu' => $menunggu,
            'riwayat' => $riwayat,
        ]);
    }

    public function show(PengajuanPercepatan $percepatan): Response
    {
        $percepatan->load('pinjaman.anggota');

        return Inertia::render('Ketua/Percepatan/Show', [
            'pengajuan' => [
                'id' => $percepatan->id,
                'tipe' => $percepatan->tipe,
                'tipe_label' => $this->tipeLabel($percepatan->tipe),
                'tenor_lama' => $percepatan->tenor_lama,
                'tenor_baru' => $percepatan->tenor_baru,
                'keterangan' => $percepatan->keterangan,
                'status' => $percepatan->status,
                'catatan_bendahara' => $percepatan->catatan_bendahara,
                'tanggal_pengajuan' => $percepatan->tanggal_pengajuan->format('d M Y'),
                'pinjaman' => [
                    'nominal' => (float) $percepatan->pinjaman->nominal,
                    'sisa_angsuran' => $percepatan->pinjaman->sisaAngsuran(),
                ],
                'anggota' => [
                    'nama' => $percepatan->pinjaman->anggota->nama,
                    'no_anggota' => $percepatan->pinjaman->anggota->no_anggota,
                ],
            ],
        ]);
    }

    public function approve(Request $request, PengajuanPercepatan $percepatan)
    {
        $request->validate([
            'catatan' => ['required', 'string', 'min:5', 'max:500'],
            'bulan_berlaku' => ['required', 'in:bulan_ini,bulan_depan'],
        ]);

        $this->service->approveKetua($percepatan, $request->catatan, $request->bulan_berlaku);

        return redirect()->route('ketua.percepatan.index')->with('status', 'Pengajuan disetujui dan diterapkan.');
    }

    public function reject(Request $request, PengajuanPercepatan $percepatan)
    {
        $request->validate(['catatan' => ['required', 'string', 'min:5', 'max:500']]);

        $this->service->rejectKetua($percepatan, $request->catatan);

        return redirect()->route('ketua.percepatan.index')->with('status', 'Pengajuan ditolak.');
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
}