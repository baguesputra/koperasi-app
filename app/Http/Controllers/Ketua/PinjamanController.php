<?php

namespace App\Http\Controllers\Ketua;

use App\Helpers\TerbilangHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\KeputusanPinjamanRequest;
use App\Models\Pinjaman;
use App\Services\Pinjaman\PerhitunganBungaService;
use App\Services\Pinjaman\PersetujuanPinjamanService;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class PinjamanController extends Controller
{
    public function __construct(
        private PersetujuanPinjamanService $persetujuan,
        private PerhitunganBungaService $bunga,
    ) {}

    public function index(): Response
    {
        $menungguApproval = Pinjaman::with('anggota')
            ->where('status', 'approved_bendahara')
            ->where('cair_oleh_bendahara', false)
            ->latest('tanggal_pengajuan')
            ->get()
            ->map($this->formatLengkap());

        $riwayat = Pinjaman::with('anggota')
            ->whereIn('status', ['aktif', 'lunas', 'ditolak'])
            ->whereNotNull('catatan_ketua')
            ->latest('updated_at')
            ->take(20)
            ->get()
            ->map($this->formatLengkap());

        return Inertia::render('Ketua/Pinjaman/Index', [
            'menungguApproval' => $menungguApproval,
            'riwayat' => $riwayat,
        ]);
    }

    public function show(Pinjaman $pinjaman): Response
    {
        $pinjaman->load('anggota');

        return Inertia::render('Ketua/Pinjaman/Show', [
            'pinjaman' => $this->formatLengkap()($pinjaman),
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

    private function formatLengkap(): \Closure
    {
        return function ($p) {
            $jadwal = $this->bunga->buatJadwal(
                (float) $p->nominal,
                $p->tenor_bulan,
                (float) $p->persentase_bunga
            );
            $totalPokok = array_sum(array_column($jadwal, 'nominal_pokok'));
            $totalBunga = array_sum(array_column($jadwal, 'nominal_bunga'));
            $totalAngsuran = array_sum(array_column($jadwal, 'total_bayar'));

            return [
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
                'jadwal_angsuran' => $jadwal,
                'total_angsuran' => $totalAngsuran,
                'total_pokok_angsuran' => $totalPokok,
                'total_bunga_angsuran' => $totalBunga,
                'anggota' => [
                    'nama' => $p->anggota->nama,
                    'no_anggota' => $p->anggota->no_anggota,
                    'cabang' => $p->anggota->cabang,
                    'jabatan' => $p->anggota->jabatan,
                    'lama_keanggotaan_tahun' => round($p->anggota->lama_keanggotaan_tahun, 1),
                ],
            ];
        };
    }
}
