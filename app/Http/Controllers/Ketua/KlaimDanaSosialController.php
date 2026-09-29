<?php

namespace App\Http\Controllers\Ketua;

use App\Http\Controllers\Controller;
use App\Http\Requests\KeputusanKlaimRequest;
use App\Models\KlaimDanaSosial;
use App\Services\DanaSosial\KlaimDanaSosialService;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class KlaimDanaSosialController extends Controller
{
    public function __construct(private KlaimDanaSosialService $service) {}

    public function index(): Response
    {
        $menunggu = KlaimDanaSosial::with('anggota')
            ->where('status', 'approved_bendahara')
            ->latest('tanggal_pengajuan')
            ->get()
            ->map(self::formatItem());

        $riwayat = KlaimDanaSosial::with('anggota')
            ->whereIn('status', ['disetujui', 'ditolak'])
            ->latest('updated_at')
            ->take(20)
            ->get()
            ->map(self::formatItem());

        return Inertia::render('Ketua/KlaimDanaSosial/Index', [
            'menunggu' => $menunggu,
            'riwayat' => $riwayat,
        ]);
    }

    public function show(KlaimDanaSosial $klaimDanaSosial): Response
    {
        $klaimDanaSosial->load('anggota');

        return Inertia::render('Ketua/KlaimDanaSosial/Show', [
            'klaim' => self::formatItem()($klaimDanaSosial),
        ]);
    }

    public function approve(KeputusanKlaimRequest $request, KlaimDanaSosial $klaimDanaSosial)
    {
        try {
            $this->service->approveKetua($klaimDanaSosial, (float) $request->nominal, $request->catatan, auth()->id());
        } catch (RuntimeException $e) {
            return back()->withErrors(['keputusan' => $e->getMessage()]);
        }

        return redirect()->route('ketua.klaim-dana-sosial.index')
            ->with('status', 'Santunan dana sosial disetujui dan tercatat sebagai pengeluaran.');
    }

    public function reject(KeputusanKlaimRequest $request, KlaimDanaSosial $klaimDanaSosial)
    {
        try {
            $this->service->rejectKetua($klaimDanaSosial, $request->catatan, auth()->id());
        } catch (RuntimeException $e) {
            return back()->withErrors(['keputusan' => $e->getMessage()]);
        }

        return redirect()->route('ketua.klaim-dana-sosial.index')
            ->with('status', 'Pengajuan santunan ditolak.');
    }

    public static function formatItem(): \Closure
    {
        $service = app(KlaimDanaSosialService::class);

        return function ($k) use ($service) {
            $anggota = $k->anggota;

            return [
                'id' => $k->id,
                'jenis' => $k->jenis,
                'jenis_label' => $service->labelJenis($k->jenis),
                'sub_tipe' => $k->sub_tipe,
                'hubungan' => $k->hubungan,
                'tanggal_kejadian' => $k->tanggal_kejadian->format('d M Y'),
                'lama_hari' => $k->lama_hari,
                'keterangan' => $k->keterangan,
                'foto_url' => $k->fotoUrl(),
                'nominal_bendahara' => $k->nominal_bendahara !== null ? (float) $k->nominal_bendahara : null,
                'nominal_final' => $k->nominal_final !== null ? (float) $k->nominal_final : null,
                'status' => $k->status,
                'catatan_bendahara' => $k->catatan_bendahara,
                'catatan_ketua' => $k->catatan_ketua,
                'tanggal_pengajuan' => $k->tanggal_pengajuan->format('d M Y'),
                'anggota' => [
                    'nama' => $anggota->nama,
                    'no_anggota' => $anggota->no_anggota,
                    'no_karyawan' => $anggota->no_karyawan,
                    'cabang' => $anggota->cabang,
                    'foto_url' => $anggota->foto_url,
                    'lama_keanggotaan_tahun' => round($anggota->lama_keanggotaan_tahun, 1),
                ],
            ];
        };
    }
}
