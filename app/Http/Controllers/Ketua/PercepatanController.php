<?php

namespace App\Http\Controllers\Ketua;

use App\Http\Controllers\Controller;
use App\Models\PengajuanPercepatan;
use App\Services\Pinjaman\PercepatanPinjamanService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PercepatanController extends Controller
{
    public function __construct(private PercepatanPinjamanService $service) {}

    public function index(): Response
    {
        $menunggu = PengajuanPercepatan::with('pinjaman.anggota')
            ->where('status', 'approved_bendahara')
            ->latest('tanggal_pengajuan')
            ->get()
            ->map(fn ($p) => $this->formatDetail($p));

        $riwayat = PengajuanPercepatan::with('pinjaman.anggota')
            ->whereIn('status', ['aktif', 'ditolak'])
            ->whereNotNull('catatan_ketua')
            ->latest('updated_at')
            ->take(20)
            ->get()
            ->map(fn ($p) => $this->formatDetail($p, denganPreview: false));

        return Inertia::render('Ketua/Percepatan/Index', [
            'menunggu' => $menunggu,
            'riwayat' => $riwayat,
        ]);
    }

    public function show(PengajuanPercepatan $percepatan): Response
    {
        $percepatan->load('pinjaman.anggota');

        return Inertia::render('Ketua/Percepatan/Show', [
            'pengajuan' => $this->formatDetail($percepatan),
        ]);
    }

    public function approve(Request $request, PengajuanPercepatan $percepatan)
    {
        $request->validate([
            'catatan' => ['required', 'string', 'min:5', 'max:500'],
            'bulan_berlaku' => ['required', 'in:bulan_ini,bulan_depan'],
        ]);

        $this->service->approveKetua($percepatan, $request->catatan, $request->bulan_berlaku);

        return redirect()->route('ketua.percepatan.index')->with('status', 'Pengajuan disetujui dan diterapkan.');
    }

    public function reject(Request $request, PengajuanPercepatan $percepatan)
    {
        $request->validate(['catatan' => ['required', 'string', 'min:5', 'max:500']]);

        $this->service->rejectKetua($percepatan, $request->catatan);

        return redirect()->route('ketua.percepatan.index')->with('status', 'Pengajuan ditolak.');
    }

    private function tipeLabel($tipe)
    {
        return ['percepat' => 'Percepat Pelunasan', 'perpanjang' => 'Perpanjang Tenor', 'lunas_total' => 'Lunas Sekarang'][$tipe] ?? $tipe;
    }

    private function formatDetail(PengajuanPercepatan $p, bool $denganPreview = true): array
    {
        $data = [
            'id' => $p->id,
            'tipe' => $p->tipe,
            'tipe_label' => $this->tipeLabel($p->tipe),
            'tenor_lama' => $p->tenor_lama,
            'tenor_baru' => $p->tenor_baru,
            'keterangan' => $p->keterangan,
            'status' => $p->status,
            'catatan_bendahara' => $p->catatan_bendahara,
            'catatan_ketua' => $p->catatan_ketua,
            'bulan_berlaku' => $p->bulan_berlaku,
            'nominal_final' => $p->nominal_final !== null ? (float) $p->nominal_final : null,
            'tanggal_pengajuan' => $p->tanggal_pengajuan->format('d M Y'),
            'pinjaman' => [
                'id' => $p->pinjaman->id,
                'nominal' => (float) $p->pinjaman->nominal,
                'persentase_bunga' => (float) $p->pinjaman->persentase_bunga,
                'tenor_bulan' => $p->pinjaman->tenor_bulan,
                'sisa_angsuran' => $p->pinjaman->sisaAngsuran(),
            ],
            'anggota' => [
                'nama' => $p->pinjaman->anggota->nama,
                'no_anggota' => $p->pinjaman->anggota->no_anggota,
                'cabang' => $p->pinjaman->anggota->cabang,
                'jabatan' => $p->pinjaman->anggota->jabatan,
                'lama_keanggotaan_tahun' => round($p->pinjaman->anggota->lama_keanggotaan_tahun, 1),
            ],
        ];

        if ($denganPreview && $p->status === 'approved_bendahara') {
            $data['preview'] = $this->service->preview($p->pinjaman, $p->tipe, $p->tenor_baru);
        }

        if ($p->status === 'aktif') {
            $data['angsuran_baru'] = $p->angsuranBaru()->orderBy('cicilan_ke')->get()->map(fn ($a) => [
                'id' => $a->id,
                'cicilan_ke' => $a->cicilan_ke,
                'nominal_pokok' => (float) $a->nominal_pokok,
                'nominal_bunga' => (float) $a->nominal_bunga,
                'total_bayar' => (float) $a->total_bayar,
                'status' => $a->status,
                'tanggal_jatuh_tempo' => $a->tanggal_jatuh_tempo->format('Y-m-d'),
            ])->all();
        }

        return $data;
    }
}
