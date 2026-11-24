<?php

namespace App\Http\Controllers\Pengurus;

use App\Http\Controllers\Controller;
use App\Models\PengajuanPenghapusanPinjaman;
use App\Models\Pinjaman;
use App\Services\Pinjaman\PenghapusanPinjamanService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class PenghapusanPinjamanController extends Controller
{
    public function __construct(
        private PenghapusanPinjamanService $service,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

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
            ->map(function (PengajuanPenghapusanPinjaman $p) use ($user) {
                return [
                    'id' => $p->id,
                    'nomor_dokumen' => $p->nomor_dokumen,
                    'pinjaman_id' => $p->pinjaman_id,
                    'pinjaman_nomor_dokumen' => $p->pinjaman?->nomor_dokumen ?? ('#'.$p->pinjaman_id),
                    'tanggal_pengajuan' => $p->tanggal_pengajuan?->format('d M Y') ?? '-',
                    'disetujui_pada' => $p->disetujui_pada?->format('d M Y, H:i'),
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
                    'bisa_diproses' => $this->service->bisaDiprosesOleh($p, $user),
                    'timeline' => $p->getTimelineApproval(),
                ];
            })
            ->values();

        $bisaAjukan = $user->can('penghapusan-pinjaman.ajukan');
        $pinjamanMenunggak = $bisaAjukan ? $this->service->daftarPinjamanMenunggak() : collect();

        $menungguSaya = $daftar->where('bisa_diproses', true)->values();
        $sedangBerjalan = $daftar->whereIn('status', [
            'diajukan',
            'approved_bendahara',
            'approved_ketua',
            'approved_pengawas_3',
            'approved_pengawas_2',
        ])->values();
        $riwayatSelesai = $daftar->whereIn('status', ['disetujui', 'ditolak'])->values();

        return Inertia::render('Pengurus/PenghapusanPinjaman/Index', [
            'pengajuanList' => $daftar,
            'menungguSaya' => $menungguSaya,
            'sedangBerjalan' => $sedangBerjalan,
            'riwayatSelesai' => $riwayatSelesai,
            'pinjamanMenunggak' => $pinjamanMenunggak,
            'bisaAjukan' => $bisaAjukan,
            'peranSaya' => $this->labelPeranUser($user),
            'statistik' => [
                'menunggu_saya' => $menungguSaya->count(),
                'sedang_berjalan' => $sedangBerjalan->count(),
                'disetujui' => $daftar->where('status', 'disetujui')->count(),
                'ditolak' => $daftar->where('status', 'ditolak')->count(),
                'total_pokok_dihapus' => (float) $daftar->where('status', 'disetujui')->sum('sisa_pokok'),
            ],
        ]);
    }

    public function store(Request $request)
    {
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
            return back()->withErrors(['alasan' => $e->getMessage()]);
        }

        $tahapBerikut = $pengajuan->status === 'approved_bendahara'
            ? 'Ketua Koperasi'
            : 'Bendahara';

        return $this->redirectSetelahAksi(
            $request,
            'pengurus.penghapusan-pinjaman.index',
            "Pengajuan penghapusan pinjaman berhasil dibuat dan menunggu persetujuan {$tahapBerikut}."
        );
    }

    public function approve(Request $request, PengajuanPenghapusanPinjaman $pengajuan)
    {
        $validated = $request->validate([
            'catatan' => ['required', 'string', 'min:3', 'max:1000'],
        ], [
            'catatan.required' => 'Catatan persetujuan wajib diisi.',
            'catatan.min' => 'Catatan persetujuan minimal 3 karakter.',
        ]);

        try {
            $hasil = $this->service->approve($pengajuan, $validated['catatan'], $request->user());
        } catch (RuntimeException $e) {
            return back()->withErrors(['catatan' => $e->getMessage()]);
        }

        $pesan = $hasil->status === 'disetujui'
            ? "Penghapusan pinjaman #{$hasil->pinjaman_id} disetujui final ({$hasil->nomor_dokumen}). Pinjaman telah dihapusbukukan."
            : 'Persetujuan berhasil dicatat dan diteruskan ke jenjang berikutnya.';

        return $this->redirectSetelahAksi($request, 'pengurus.penghapusan-pinjaman.index', $pesan);
    }

    public function reject(Request $request, PengajuanPenghapusanPinjaman $pengajuan)
    {
        $validated = $request->validate([
            'catatan' => ['required', 'string', 'min:3', 'max:1000'],
        ], [
            'catatan.required' => 'Alasan penolakan wajib diisi.',
            'catatan.min' => 'Alasan penolakan minimal 3 karakter.',
        ]);

        try {
            $this->service->reject($pengajuan, $validated['catatan'], $request->user());
        } catch (RuntimeException $e) {
            return back()->withErrors(['catatan' => $e->getMessage()]);
        }

        return $this->redirectSetelahAksi(
            $request,
            'pengurus.penghapusan-pinjaman.index',
            'Pengajuan penghapusan pinjaman telah ditolak.'
        );
    }

    private function labelPeranUser($user): string
    {
        if ($user->hasRole('super_admin')) {
            return 'Super Admin';
        }
        if ($user->hasRole('pengawas')) {
            return $user->labelJabatanPengawas() ?? 'Pengawas';
        }
        if ($user->hasRole('ketua_koperasi')) {
            return 'Ketua Koperasi';
        }
        if ($user->hasRole('bendahara')) {
            return 'Bendahara';
        }
        if ($user->hasRole('sekretaris')) {
            return 'Sekretaris';
        }

        return 'Pengurus';
    }
}
