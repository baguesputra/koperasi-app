<?php

namespace App\Http\Controllers\Bendahara;

use App\Helpers\TerbilangHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\KeputusanPinjamanRequest;
use App\Models\Pinjaman;
use App\Services\Pinjaman\PersetujuanPinjamanService;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class PinjamanController extends Controller
{
    public function __construct(private PersetujuanPinjamanService $persetujuan) {}

    public function index(): Response
    {
        $menungguTinjauan = Pinjaman::with('anggota')
            ->where('status', 'diajukan')
            ->latest('tanggal_pengajuan')
            ->get()
            ->map($this->formatLengkap());

        // Pinjaman yang diajukan sendiri oleh Ketua: setelah disetujui bendahara,
        // pencairannya dilakukan oleh bendahara (bukan Ketua) agar tak self-approve.
        $menungguPencairan = Pinjaman::with('anggota')
            ->where('status', 'approved_bendahara')
            ->where('cair_oleh_bendahara', true)
            ->latest('tanggal_pengajuan')
            ->get()
            ->map($this->formatLengkap());

        $riwayat = Pinjaman::with('anggota')
            ->whereIn('status', ['approved_bendahara', 'aktif', 'lunas', 'ditolak'])
            ->whereNotNull('catatan_bendahara')
            ->latest('updated_at')
            ->take(20)
            ->get()
            ->map($this->formatRingkas());

        return Inertia::render('Bendahara/Pinjaman/Index', [
            'menungguTinjauan' => $menungguTinjauan,
            'menungguPencairan' => $menungguPencairan,
            'riwayat' => $riwayat,
        ]);
    }

    public function show(Pinjaman $pinjaman): Response
    {
        $pinjaman->load('anggota');

        return Inertia::render('Bendahara/Pinjaman/Show', [
            'pinjaman' => $this->formatLengkap()($pinjaman),
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

    public function cair(KeputusanPinjamanRequest $request, Pinjaman $pinjaman)
    {
        try {
            $this->persetujuan->cairBendahara($pinjaman, $request->catatan);
        } catch (RuntimeException $e) {
            return back()->withErrors(['keputusan' => $e->getMessage()]);
        }

        return redirect()->route('bendahara.pinjaman.index')
            ->with('status', 'Pinjaman disetujui dan dana telah dicairkan.');
    }

    private function formatRingkas(): \Closure
    {
        return fn ($p) => [
            'id' => $p->id,
            'nama' => $p->anggota->nama,
            'no_anggota' => $p->anggota->no_anggota,
            'nominal' => (float) $p->nominal,
            'tenor_bulan' => $p->tenor_bulan,
            'status' => $p->status,
            'tanggal_pengajuan' => $p->tanggal_pengajuan->format('d M Y'),
        ];
    }

    private function formatLengkap(): \Closure
    {
        return fn ($p) => [
            'id' => $p->id,
            'nominal' => (float) $p->nominal,
            'terbilang' => TerbilangHelper::angkaKeTerbilang($p->nominal),
            'tenor_bulan' => $p->tenor_bulan,
            'persentase_bunga' => (float) $p->persentase_bunga,
            'keperluan' => $p->keperluan,
            'rekening' => [
                'bank' => $p->snapshot_bank,
                'no_rekening' => $p->snapshot_no_rekening,
                'atas_nama' => $p->snapshot_atas_nama,
            ],
            'status' => $p->status,
            'tanggal_pengajuan' => $p->tanggal_pengajuan->format('d M Y'),
            'sudah_pakai_privilege_reloan' => $p->sudah_pakai_privilege_reloan,
            'catatan_bendahara' => $p->catatan_bendahara,
            'anggota' => [
                'nama' => $p->anggota->nama,
                'no_anggota' => $p->anggota->no_anggota,
                'cabang' => $p->anggota->cabang,
                'jabatan' => $p->anggota->jabatan,
                'lama_keanggotaan_tahun' => round($p->anggota->lama_keanggotaan_tahun, 1),
            ],
        ];
    }
}
