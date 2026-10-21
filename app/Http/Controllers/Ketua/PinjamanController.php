<?php

namespace App\Http\Controllers\Ketua;

use App\Http\Controllers\Controller;
use App\Http\Requests\KeputusanPinjamanNominalRequest;
use App\Models\KasKoperasi;
use App\Models\Pinjaman;
use App\Services\Pinjaman\EligibilitasPinjamanService;
use App\Services\Pinjaman\FormatPinjamanService;
use App\Services\Pinjaman\PerhitunganBungaService;
use App\Services\Pinjaman\PersetujuanPinjamanService;
use App\Services\Pinjaman\RingkasanPinjamanService;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class PinjamanController extends Controller
{
    public function __construct(
        private PersetujuanPinjamanService $persetujuan,
        private PerhitunganBungaService $bunga,
        private EligibilitasPinjamanService $eligibilitas,
        private FormatPinjamanService $format,
        private RingkasanPinjamanService $ringkasan,
    ) {}

    public function index(): Response
    {
        $menungguApproval = Pinjaman::with('anggota')
            ->where('status', 'approved_bendahara')
            ->where('cair_oleh_bendahara', false)
            ->latest('tanggal_pengajuan')
            ->get()
            ->map(fn ($p) => $this->format->format($p));

        $riwayat = Pinjaman::with('anggota')
            ->whereIn('status', ['aktif', 'lunas', 'ditolak'])
            ->whereNotNull('catatan_ketua')
            ->latest('updated_at')
            ->take(20)
            ->get()
            ->map(fn ($p) => $this->format->format($p));

        return Inertia::render('Ketua/Pinjaman/Index', [
            'menungguApproval' => $menungguApproval,
            'riwayat' => $riwayat,
            'ringkasanKas' => $this->ringkasan->ringkasan(),
        ]);
    }

    public function show(Pinjaman $pinjaman): Response
    {
        $pinjaman->load('anggota');

        return Inertia::render('Ketua/Pinjaman/Show', [
            'pinjaman' => $this->format->format($pinjaman),
        ]);
    }

    public function approve(KeputusanPinjamanNominalRequest $request, Pinjaman $pinjaman)
    {
        try {
            $this->persetujuan->approveKetua(
                $pinjaman,
                $request->catatan,
                $request->nominal !== null ? (float) $request->nominal : null,
                $request->tenor_bulan !== null ? (int) $request->tenor_bulan : null
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['keputusan' => $e->getMessage()]);
        }

        return redirect()->route('ketua.pinjaman.index')
            ->with('status', 'Pinjaman disetujui dan dana telah dicairkan.');
    }

    public function preview(Pinjaman $pinjaman)
    {
        $nominal = (float) request()->input(
            'nominal',
            $pinjaman->nominal_disetujui_bendahara ?? $pinjaman->nominal
        );
        $tenor = request()->input('tenor_bulan') !== null
            ? (int) request()->input('tenor_bulan')
            : null;

        try {
            $tenorFinal = $this->persetujuan->resolveTenor(
                $pinjaman,
                $nominal,
                $tenor,
                $pinjaman->tenor_disetujui_bendahara ?? $pinjaman->tenor_bulan
            );
        } catch (RuntimeException $e) {
            return response()->json(['pesan' => $e->getMessage()], 422);
        }

        $jadwal = $this->bunga->buatJadwal($nominal, $tenorFinal, (float) $pinjaman->persentase_bunga);
        $saldoOperasional = app(\App\Services\Keuangan\JurnalKasService::class)->saldoOperasional();

        return response()->json([
            'nominal' => $nominal,
            'tenor_bulan' => $tenorFinal,
            'tenor_maksimal' => $this->eligibilitas->tenorMaksimal($nominal),
            'jadwal' => array_slice($jadwal, 0, 6),
            'total_cicilan' => count($jadwal),
            'total_pokok' => array_sum(array_column($jadwal, 'nominal_pokok')),
            'total_bunga' => array_sum(array_column($jadwal, 'nominal_bunga')),
            'total_bayar' => array_sum(array_column($jadwal, 'total_bayar')),
            'cicilan_pertama' => $jadwal[0] ?? null,
            'kas_saldo' => $saldoOperasional,
            'kas_sisa' => $saldoOperasional - $nominal,
        ]);
    }

    public function reject(KeputusanPinjamanNominalRequest $request, Pinjaman $pinjaman)
    {
        try {
            $this->persetujuan->rejectKetua($pinjaman, $request->catatan);
        } catch (RuntimeException $e) {
            return back()->withErrors(['keputusan' => $e->getMessage()]);
        }

        return redirect()->route('ketua.pinjaman.index')
            ->with('status', 'Pengajuan pinjaman ditolak.');
    }
}
