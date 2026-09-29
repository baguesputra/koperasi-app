<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\PengajuanKlaimRequest;
use App\Models\KlaimDanaSosial;
use App\Services\DanaSosial\KlaimDanaSosialService;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class KlaimDanaSosialController extends Controller
{
    public function __construct(private KlaimDanaSosialService $service) {}

    public function create(): Response
    {
        $anggota = auth()->user()->anggota;

        $berjalan = KlaimDanaSosial::where('anggota_id', $anggota->id)
            ->whereIn('status', ['diajukan', 'approved_bendahara'])
            ->latest('tanggal_pengajuan')
            ->first();

        $riwayat = KlaimDanaSosial::where('anggota_id', $anggota->id)
            ->latest('tanggal_pengajuan')
            ->take(10)
            ->get()
            ->map(fn ($k) => $this->formatRiwayat($k));

        return Inertia::render('Portal/KlaimDanaSosial/Create', [
            'pengajuanBerjalan' => $berjalan ? $this->formatRiwayat($berjalan) : null,
            'riwayat' => $riwayat,
        ]);
    }

    public function store(PengajuanKlaimRequest $request)
    {
        $anggota = auth()->user()->anggota;

        try {
            $this->service->ajukan(
                $anggota,
                $request->only(['jenis', 'sub_tipe', 'hubungan', 'tanggal_kejadian', 'lama_hari', 'keterangan']),
                $request->file('foto'),
                auth()->id(),
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['pengajuan' => $e->getMessage()]);
        }

        return redirect()->route('portal.klaim-dana-sosial.create')
            ->with('status', 'Pengajuan santunan dana sosial terkirim. Menunggu verifikasi Bendahara.');
    }

    private function formatRiwayat(KlaimDanaSosial $klaim): array
    {
        return [
            'id' => $klaim->id,
            'jenis' => $klaim->jenis,
            'jenis_label' => $this->service->labelJenis($klaim->jenis),
            'sub_tipe' => $klaim->sub_tipe,
            'hubungan' => $klaim->hubungan,
            'tanggal_kejadian' => $klaim->tanggal_kejadian->format('d M Y'),
            'lama_hari' => $klaim->lama_hari,
            'keterangan' => $klaim->keterangan,
            'nominal_bendahara' => $klaim->nominal_bendahara !== null ? (float) $klaim->nominal_bendahara : null,
            'nominal_final' => $klaim->nominal_final !== null ? (float) $klaim->nominal_final : null,
            'status' => $klaim->status,
            'catatan_bendahara' => $klaim->catatan_bendahara,
            'catatan_ketua' => $klaim->catatan_ketua,
            'tanggal_pengajuan' => $klaim->tanggal_pengajuan->format('d M Y'),
        ];
    }
}
