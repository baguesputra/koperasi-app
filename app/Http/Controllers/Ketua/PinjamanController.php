<?php

namespace App\Http\Controllers\Ketua;

use App\Helpers\TerbilangHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\KeputusanPinjamanNominalRequest;
use App\Models\KasKoperasi;
use App\Models\Pinjaman;
use App\Services\Pinjaman\EligibilitasPinjamanService;
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
        $kasSaldo = (float) (KasKoperasi::first()?->saldo_pinjaman ?? 0);

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
            'kas_saldo' => $kasSaldo,
            'kas_sisa' => $kasSaldo - $nominal,
        ]);
    }

    public function reject(KeputusanPinjamanNominalRequest $request, Pinjaman $pinjaman)
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
                'nominal_diminta' => $p->nominal_diminta !== null ? (float) $p->nominal_diminta : (float) $p->nominal,
                'tenor_diminta' => $p->tenor_diminta ?? $p->tenor_bulan,
                'nominal_disetujui_bendahara' => $p->nominal_disetujui_bendahara !== null ? (float) $p->nominal_disetujui_bendahara : null,
                'tenor_disetujui_bendahara' => $p->tenor_disetujui_bendahara,
                'nominal_disetujui' => $p->nominal_disetujui !== null ? (float) $p->nominal_disetujui : null,
                'tenor_disetujui' => $p->tenor_disetujui,
                'kas' => [
                    'saldo_bendahara' => $p->kas_saldo_bendahara !== null ? (float) $p->kas_saldo_bendahara : null,
                    'saldo_ketua' => $p->kas_saldo_ketua !== null ? (float) $p->kas_saldo_ketua : null,
                    'sisa_ketua' => $p->kas_sisa_ketua !== null ? (float) $p->kas_sisa_ketua : null,
                    'saldo_sekarang' => (float) (KasKoperasi::first()?->saldo_pinjaman ?? 0),
                ],
                'limit_tersedia' => (float) $this->eligibilitas->limitTersedia($p->anggota),
                'tenor_maksimal_diminta' => $this->eligibilitas->tenorMaksimal((float) ($p->nominal_diminta ?? $p->nominal)),
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
                    'no_karyawan' => $p->anggota->no_karyawan,
                    'cabang' => $p->anggota->cabang,
                    'foto_url' => $p->anggota->foto_url,
                    'jabatan' => $p->anggota->jabatan,
                    'lama_keanggotaan_tahun' => round($p->anggota->lama_keanggotaan_tahun, 1),
                ],
            ];
        };
    }
}
