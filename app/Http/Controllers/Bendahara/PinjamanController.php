<?php

namespace App\Http\Controllers\Bendahara;

use App\Http\Controllers\Controller;
use App\Http\Requests\KeputusanPinjamanNominalRequest;
use App\Models\KasKoperasi;
use App\Models\Pinjaman;
use App\Services\Pinjaman\EligibilitasPinjamanService;
use App\Services\Pinjaman\FormatPinjamanService;
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
        private EligibilitasPinjamanService $eligibilitas,
        private FormatPinjamanService $format,
    ) {}

    public function index(): Response
    {
        $menungguTinjauan = Pinjaman::with('anggota')
            ->where('status', 'approved_admin')
            ->latest('tanggal_pengajuan')
            ->get()
            ->map(fn ($p) => $this->format->format($p));

        // Pinjaman yang diajukan sendiri oleh Ketua: setelah disetujui bendahara,
        // pencairannya dilakukan oleh bendahara (bukan Ketua) agar tak self-approve.
        $menungguPencairan = Pinjaman::with('anggota')
            ->where('status', 'approved_bendahara')
            ->where('cair_oleh_bendahara', true)
            ->latest('tanggal_pengajuan')
            ->get()
            ->map(fn ($p) => $this->format->format($p));

        $riwayat = Pinjaman::with('anggota')
            ->whereIn('status', ['approved_bendahara', 'aktif', 'lunas', 'ditolak'])
            ->whereNotNull('catatan_bendahara')
            ->latest('updated_at')
            ->take(20)
            ->get()
            ->map(fn ($p) => $this->format->format($p));

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
            'pinjaman' => $this->format->format($pinjaman),
        ]);
    }

    public function approve(KeputusanPinjamanNominalRequest $request, Pinjaman $pinjaman)
    {
        try {
            $this->persetujuan->approveBendahara(
                $pinjaman,
                $request->catatan,
                $request->nominal !== null ? (float) $request->nominal : null,
                $request->tenor_bulan !== null ? (int) $request->tenor_bulan : null
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['keputusan' => $e->getMessage()]);
        }

        return redirect()->route('bendahara.pinjaman.index')
            ->with('status', 'Pengajuan pinjaman disetujui dan diteruskan ke Ketua Koperasi.');
    }

    public function reject(KeputusanPinjamanNominalRequest $request, Pinjaman $pinjaman)
    {
        try {
            $this->persetujuan->rejectBendahara($pinjaman, $request->catatan);
        } catch (RuntimeException $e) {
            return back()->withErrors(['keputusan' => $e->getMessage()]);
        }

        return redirect()->route('bendahara.pinjaman.index')
            ->with('status', 'Pengajuan pinjaman ditolak.');
    }

    public function preview(Pinjaman $pinjaman)
    {
        $nominal = (float) request()->input('nominal', $pinjaman->nominal);
        $tenor = request()->input('tenor_bulan') !== null
            ? (int) request()->input('tenor_bulan')
            : null;

        try {
            $tenorFinal = $this->persetujuan->resolveTenor($pinjaman, $nominal, $tenor);
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

    public function cair(KeputusanPinjamanNominalRequest $request, Pinjaman $pinjaman)
    {
        try {
            $this->persetujuan->cairBendahara($pinjaman, $request->catatan);
        } catch (RuntimeException $e) {
            return back()->withErrors(['keputusan' => $e->getMessage()]);
        }

        return redirect()->route('bendahara.pinjaman.index')
            ->with('status', 'Pinjaman disetujui dan dana telah dicairkan.');
    }
}
