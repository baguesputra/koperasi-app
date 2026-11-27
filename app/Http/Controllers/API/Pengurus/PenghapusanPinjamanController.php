<?php

namespace App\Http\Controllers\API\Pengurus;

use App\Http\Controllers\Controller;
use App\Models\PengajuanPenghapusanPinjaman;
use App\Models\Pinjaman;
use App\Services\Pinjaman\PenghapusanPinjamanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class PenghapusanPinjamanController extends Controller
{
    public function __construct(private PenghapusanPinjamanService $service) {}

    /**
     * Daftar pengajuan penghapusan pinjaman (write-off) 5-tahap beserta daftar pinjaman menunggak.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user->can('penghapusan-pinjaman.lihat')) {
            abort(response()->json([
                'message' => 'Anda tidak memiliki akses ke penghapusan pinjaman.',
            ], 403));
        }

        $daftar = PengajuanPenghapusanPinjaman::with([
            'pinjaman',
            'anggota',
            'pengaju:id,name,no_karyawan',
            'approverBendahara:id,name,no_karyawan',
            'approverKetua:id,name,no_karyawan',
            'approverPengawas3:id,name,no_karyawan',
            'approverPengawas2:id,name,no_karyawan',
            'approverPengawas1:id,name,no_karyawan',
            'penolak:id,name,no_karyawan',
        ])
            ->latest('created_at')
            ->latest('id')
            ->get()
            ->map(fn (PengajuanPenghapusanPinjaman $p) => [
                'id' => $p->id,
                'nomor_dokumen' => $p->nomor_dokumen,
                'pinjaman_id' => $p->pinjaman_id,
                'pinjaman_nomor_dokumen' => $p->pinjaman?->nomor_dokumen ?? ('#'.$p->pinjaman_id),
                'tanggal_pengajuan' => $p->tanggal_pengajuan?->format('Y-m-d'),
                'disetujui_pada' => $p->disetujui_pada?->format('Y-m-d H:i'),
                'alasan' => $p->alasan,
                'status' => $p->status,
                'status_label' => PengajuanPenghapusanPinjaman::STATUS_LABEL[$p->status] ?? $p->status,
                'nominal_pinjaman' => (float) $p->nominal_pinjaman,
                'total_pokok_terbayar' => (float) $p->total_pokok_terbayar,
                'total_bunga_terbayar' => (float) $p->total_bunga_terbayar,
                'sisa_cicilan' => (int) $p->sisa_cicilan,
                'sisa_pokok' => (float) $p->sisa_pokok,
                'sisa_bunga' => (float) $p->sisa_bunga,
                'sisa_total_tagihan' => (float) $p->sisa_total_tagihan,
                'anggota' => [
                    'id' => $p->anggota?->id,
                    'nama' => $p->anggota?->nama ?? '-',
                    'no_karyawan' => $p->anggota?->no_karyawan ?? '-',
                    'no_anggota' => $p->anggota?->no_anggota ?? '-',
                    'cabang' => $p->anggota?->cabang ?? '-',
                    'foto_url' => $p->anggota?->foto_url,
                    'status' => $p->anggota?->status ?? '-',
                ],
                'pengaju_nama' => $p->pengaju?->name ?? '-',
                'tahap_ditolak' => $p->tahap_ditolak,
                'tahap_ditolak_label' => $p->tahap_ditolak ? (PengajuanPenghapusanPinjaman::TAHAP_DITOLAK_LABEL[$p->tahap_ditolak] ?? $p->tahap_ditolak) : null,
                'catatan_tolak' => $p->catatan_tolak,
                'ditolak_pada' => $p->ditolak_pada?->format('Y-m-d H:i'),
                'penolak_nama' => $p->penolak?->name,
                'bisa_diproses' => $this->service->bisaDiprosesOleh($p, $user),
                'timeline' => $p->getTimelineApproval(),
            ])
            ->values();

        $bisaAjukan = $user->can('penghapusan-pinjaman.ajukan');
        $pinjamanMenunggak = $bisaAjukan ? $this->service->daftarPinjamanMenunggak($user)->values()->all() : [];

        return response()->json([
            'data' => $daftar->all(),
            'pinjaman_menunggak' => $pinjamanMenunggak,
            'bisa_ajukan' => $bisaAjukan,
            'statistik' => [
                'menunggu_saya' => $daftar->where('bisa_diproses', true)->count(),
                'sedang_berjalan' => $daftar->whereIn('status', [
                    'diajukan',
                    'approved_pengawas_3',
                    'approved_pengawas_2',
                ])->count(),
                'disetujui' => $daftar->where('status', 'disetujui')->count(),
                'ditolak' => $daftar->where('status', 'ditolak')->count(),
                'total_pokok_dihapus' => (float) $daftar->where('status', 'disetujui')->sum('sisa_pokok'),
            ],
        ]);
    }

    /**
     * Ajukan penghapusan buku (write-off) untuk pinjaman aktif bermasalah.
     */
    public function store(Request $request): JsonResponse
    {
        if (! $request->user()->can('penghapusan-pinjaman.ajukan')) {
            abort(response()->json([
                'message' => 'Anda tidak memiliki akses mengajukan penghapusan pinjaman.',
            ], 403));
        }

        $validated = $request->validate([
            'pinjaman_id' => ['required', 'integer', 'exists:pinjaman,id'],
            'alasan' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'pinjaman_id.required' => 'Pilih pinjaman yang akan diajukan penghapusan.',
            'alasan.required' => 'Alasan penghapusan wajib diisi.',
            'alasan.min' => 'Alasan penghapusan minimal 5 karakter.',
        ]);

        $pinjaman = Pinjaman::findOrFail($validated['pinjaman_id']);

        try {
            $pengajuan = $this->service->ajukan($pinjaman, $validated['alasan'], $request->user());
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $tahapBerikut = $pengajuan->status === 'approved_pengawas_3'
            ? 'Pengawas 2'
            : 'Pengawas 3';

        return response()->json([
            'message' => "Pengajuan penghapusan pinjaman berhasil dibuat dan menunggu {$tahapBerikut}.",
            'data' => [
                'id' => $pengajuan->id,
                'nomor_dokumen' => $pengajuan->nomor_dokumen,
                'status' => $pengajuan->status,
            ],
        ], 201);
    }

    /**
     * Setujui tahap aktif penghapusan pinjaman (Pengawas 3 -> Pengawas 2 -> Pengawas 1).
     */
    public function approve(Request $request, PengajuanPenghapusanPinjaman $pengajuan): JsonResponse
    {
        $user = $request->user();
        if (! $this->service->bisaDiprosesOleh($pengajuan, $user)) {
            abort(response()->json([
                'message' => 'Anda tidak berwenang memproses pengajuan penghapusan pada tahap ini.',
            ], 403));
        }

        $validated = $request->validate([
            'catatan' => ['required', 'string', 'min:3', 'max:1000'],
        ], [
            'catatan.required' => 'Catatan persetujuan wajib diisi.',
            'catatan.min' => 'Catatan persetujuan minimal 3 karakter.',
        ]);

        try {
            $hasil = $this->service->approve($pengajuan, $validated['catatan'], $user);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => $hasil->status === 'disetujui'
                ? "Penghapusan pinjaman #{$hasil->pinjaman_id} disetujui final ({$hasil->nomor_dokumen}). Pinjaman dihapusbukukan."
                : 'Persetujuan berhasil dicatat dan diteruskan ke jenjang berikutnya.',
            'data' => [
                'id' => $hasil->id,
                'status' => $hasil->status,
            ],
        ]);
    }

    /**
     * Tolak pengajuan penghapusan pinjaman pada tahap aktif.
     */
    public function reject(Request $request, PengajuanPenghapusanPinjaman $pengajuan): JsonResponse
    {
        $user = $request->user();
        if (! $this->service->bisaDiprosesOleh($pengajuan, $user)) {
            abort(response()->json([
                'message' => 'Anda tidak berwenang menolak pengajuan penghapusan pada tahap ini.',
            ], 403));
        }

        $validated = $request->validate([
            'catatan' => ['required', 'string', 'min:3', 'max:1000'],
        ], [
            'catatan.required' => 'Alasan penolakan wajib diisi.',
            'catatan.min' => 'Alasan penolakan minimal 3 karakter.',
        ]);

        try {
            $hasil = $this->service->reject($pengajuan, $validated['catatan'], $user);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Pengajuan penghapusan pinjaman telah ditolak. Pinjaman tetap aktif untuk terus ditagih.',
            'data' => [
                'id' => $hasil->id,
                'status' => $hasil->status,
            ],
        ]);
    }
}
