<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\PreviewPengajuanPercepatanRequest;
use App\Http\Requests\StorePengajuanPercepatanRequest;
use App\Models\Pinjaman;
use App\Services\Pinjaman\PengajuanPercepatanService;
use RuntimeException;

class PengajuanPercepatanController extends Controller
{
    public function __construct(private PengajuanPercepatanService $service) {}

    public function preview(PreviewPengajuanPercepatanRequest $request)
    {
        $anggota = $request->user()->anggota;
        $pinjaman = Pinjaman::where('anggota_id', $anggota->id)
            ->where('status', 'aktif')
            ->findOrFail($request->integer('pinjaman_id'));

        return response()->json(
            $this->service->preview($pinjaman, $request->string('tipe')->value(), $request->integer('tenor_baru') ?: null)
        );
    }

    public function store(StorePengajuanPercepatanRequest $request)
    {
        $anggota = $request->user()->anggota;
        $pinjaman = Pinjaman::where('anggota_id', $anggota->id)->findOrFail($request->integer('pinjaman_id'));

        try {
            $this->service->ajukan(
                $pinjaman,
                $request->string('tipe')->value(),
                $request->integer('tenor_baru') ?: null,
                $request->string('keterangan')->value(),
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['percepatan' => $e->getMessage()]);
        }

        return back()->with('status', 'Pengajuan percepatan berhasil dikirim.');
    }
}
