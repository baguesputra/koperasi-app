<?php

namespace App\Http\Controllers\Bendahara;

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
        return Inertia::render('Bendahara/PengajuanPercepatan/Index', [
            'pengajuan' => $this->query()->get()->map(fn ($p) => $this->map($p)),
        ]);
    }

    public function keputusan(KeputusanPengajuanPercepatanRequest $request, PengajuanPercepatan $pengajuanPercepatan)
    {
        try {
            if ($request->aksi === 'setuju') {
                $this->service->approveBendahara($pengajuanPercepatan, $request->input('catatan', ''));
            } else {
                $this->service->rejectBendahara($pengajuanPercepatan, $request->input('catatan', ''));
            }
        } catch (RuntimeException $e) {
            return back()->withErrors(['keputusan' => $e->getMessage()]);
        }

        return back()->with('status', 'Keputusan pengajuan percepatan berhasil disimpan.');
    }

    private function query()
    {
        return PengajuanPercepatan::with('pinjaman.anggota')
            ->where('status', 'diajukan')
            ->latest('tanggal_pengajuan');
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
