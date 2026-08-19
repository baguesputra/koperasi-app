<?php

namespace App\Http\Controllers\Ketua;

use App\Http\Controllers\Controller;
use App\Http\Requests\KeputusanPengajuanPercepatanRequest;
use App\Models\PengajuanPercepatan;
use App\Services\Pinjaman\PengajuanPercepatanService;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class PengajuanPercepatanController extends Controller
{
    public function __construct(private PengajuanPercepatanService $service) {}

    public function index(): Response
    {
        return Inertia::render('Ketua/PengajuanPercepatan/Index', [
            'pengajuan' => PengajuanPercepatan::with('pinjaman.anggota')
                ->where('status', 'approved_bendahara')
                ->latest('tanggal_pengajuan')
                ->get()
                ->map(fn ($p) => $this->map($p)),
        ]);
    }

    public function keputusan(KeputusanPengajuanPercepatanRequest $request, PengajuanPercepatan $pengajuanPercepatan)
    {
        try {
            if ($request->aksi === 'setuju') {
                $this->service->approveKetua($pengajuanPercepatan, $request->input('catatan', ''), $request->string('bulan_berlaku')->value(), auth()->id());
            } else {
                $this->service->rejectKetua($pengajuanPercepatan, $request->input('catatan', ''));
            }
        } catch (RuntimeException $e) {
            return back()->withErrors(['keputusan' => $e->getMessage()]);
        }

        return back()->with('status', 'Keputusan final pengajuan percepatan berhasil disimpan.');
    }

    private function map(PengajuanPercepatan $p): array
    {
        return [
            'id' => $p->id,
            'tipe' => $p->tipe,
            'tenor_lama' => $p->tenor_lama,
            'tenor_baru' => $p->tenor_baru,
            'sisa_pokok_saat_ajukan' => (float) $p->sisa_pokok_saat_ajukan,
            'nominal_final' => (float) $p->nominal_final,
            'keterangan' => $p->keterangan,
            'catatan_bendahara' => $p->catatan_bendahara,
            'status' => $p->status,
            'tanggal_pengajuan' => $p->tanggal_pengajuan->format('d M Y'),
            'anggota' => [
                'nama' => $p->pinjaman->anggota->nama,
                'no_anggota' => $p->pinjaman->anggota->no_anggota,
            ],
            'pinjaman' => [
                'nominal' => (float) $p->pinjaman->nominal,
                'tenor_bulan' => $p->pinjaman->tenor_bulan,
            ],
        ];
    }
}
