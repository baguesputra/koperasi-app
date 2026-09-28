<?php

namespace App\Http\Controllers\Ketua;

use App\Http\Controllers\Controller;
use App\Http\Requests\KeputusanPinjamanRequest;
use App\Models\PengajuanAktivasi;
use App\Services\Anggota\PengajuanAktivasiService;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class AktivasiController extends Controller
{
    public function __construct(private PengajuanAktivasiService $service) {}

    public function index(): Response
    {
        $menunggu = PengajuanAktivasi::with('anggota')
            ->where('status', 'diajukan')
            ->latest('tanggal_pengajuan')
            ->get()
            ->map(self::formatItem());

        $riwayat = PengajuanAktivasi::with('anggota')
            ->whereIn('status', ['disetujui', 'ditolak'])
            ->latest('updated_at')
            ->take(20)
            ->get()
            ->map(self::formatItem());

        return Inertia::render('Ketua/Aktivasi/Index', [
            'menunggu' => $menunggu,
            'riwayat' => $riwayat,
        ]);
    }

    public function approve(KeputusanPinjamanRequest $request, PengajuanAktivasi $aktivasi)
    {
        try {
            $this->service->setujui($aktivasi, $request->catatan, auth()->id());
        } catch (RuntimeException $e) {
            return back()->withErrors(['keputusan' => $e->getMessage()]);
        }

        return redirect()->route('ketua.aktivasi.index')
            ->with('status', 'Aktivasi keanggotaan disetujui.');
    }

    public function reject(KeputusanPinjamanRequest $request, PengajuanAktivasi $aktivasi)
    {
        try {
            $this->service->tolak($aktivasi, $request->catatan, auth()->id());
        } catch (RuntimeException $e) {
            return back()->withErrors(['keputusan' => $e->getMessage()]);
        }

        return redirect()->route('ketua.aktivasi.index')
            ->with('status', 'Pengajuan aktivasi ditolak.');
    }

    public static function formatItem(): \Closure
    {
        return fn ($p) => [
            'id' => $p->id,
            'status' => $p->status,
            'versi_syarat' => $p->versi_syarat,
            'catatan_ketua' => $p->catatan_ketua,
            'tanggal_pengajuan' => $p->tanggal_pengajuan->format('d M Y'),
            'anggota' => [
                'nama' => $p->anggota->nama,
                'no_anggota' => $p->anggota->no_anggota,
                'no_karyawan' => $p->anggota->no_karyawan,
                'cabang' => $p->anggota->cabang,
                'unit_bisnis' => $p->anggota->unit_bisnis,
                'jabatan' => $p->anggota->jabatan,
                'department' => $p->anggota->department,
                'no_hp' => $p->anggota->no_hp,
                'alamat' => $p->anggota->alamat,
                'foto_url' => $p->anggota->foto_url,
                'status' => $p->anggota->status,
                'lama_keanggotaan_tahun' => round($p->anggota->lama_keanggotaan_tahun, 1),
            ],
        ];
    }
}
