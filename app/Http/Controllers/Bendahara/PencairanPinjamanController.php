<?php

namespace App\Http\Controllers\Bendahara;

use App\Http\Controllers\Controller;
use App\Models\Pinjaman;
use App\Services\Pinjaman\FormatPinjamanService;
use App\Services\Pinjaman\PersetujuanPinjamanService;
use App\Services\Pinjaman\RingkasanPinjamanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class PencairanPinjamanController extends Controller
{
    public function __construct(
        private PersetujuanPinjamanService $persetujuan,
        private FormatPinjamanService $format,
        private RingkasanPinjamanService $ringkasan,
    ) {}

    public function index(): Response
    {
        $menungguPencairan = Pinjaman::with('anggota')
            ->where('status', 'approved_ketua')
            ->orderBy('disetujui_ketua_pada')
            ->orderBy('id')
            ->get()
            ->map(function (Pinjaman $p) {
                $formatted = $this->format->format($p, withLimit: false);
                $formatted['disetujui_ketua_pada'] = $p->disetujui_ketua_pada?->format('d M Y H:i');
                $formatted['catatan_ketua'] = $p->catatan_ketua;

                return $formatted;
            });

        $riwayatPencairan = Pinjaman::with(['anggota', 'dicairkanOleh:id,name'])
            ->whereIn('status', ['aktif', 'lunas', 'dihapusbukukan'])
            ->whereNotNull('tanggal_pencairan')
            ->latest('tanggal_pencairan')
            ->latest('id')
            ->take(30)
            ->get()
            ->map(fn (Pinjaman $p) => [
                'id' => $p->id,
                'nomor_dokumen' => $p->nomor_dokumen,
                'nominal' => (float) $p->nominal,
                'tenor_bulan' => (int) $p->tenor_bulan,
                'status' => $p->status,
                'tanggal_pencairan' => $p->tanggal_pencairan?->format('d M Y'),
                'dicairkan_oleh' => $p->dicairkanOleh?->name ?? '-',
                'catatan_pencairan' => $p->catatan_pencairan,
                'ada_bukti_pencairan' => (bool) $p->bukti_pencairan_path,
                'bukti_pencairan_url' => $p->bukti_pencairan_path ? route('pinjaman.bukti-pencairan', $p->id) : null,
                'rekening' => [
                    'bank' => $p->snapshot_bank,
                    'no_rekening' => $p->snapshot_no_rekening,
                    'atas_nama' => $p->snapshot_atas_nama,
                ],
                'anggota' => [
                    'nama' => $p->anggota?->nama ?? '-',
                    'no_karyawan' => $p->anggota?->no_karyawan ?? '-',
                    'cabang' => $p->anggota?->cabang ?? '-',
                    'foto_url' => $p->anggota?->foto_url,
                ],
            ]);

        return Inertia::render('Bendahara/PencairanPinjaman/Index', [
            'menungguPencairan' => $menungguPencairan,
            'riwayatPencairan' => $riwayatPencairan,
            'ringkasanKas' => $this->ringkasan->ringkasan('pencairan'),
        ]);
    }

    public function cair(Request $request, Pinjaman $pinjaman): RedirectResponse
    {
        $data = $request->validate([
            'tanggal_pencairan' => ['required', 'date', 'before_or_equal:today'],
            'bukti_pencairan' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ], [
            'tanggal_pencairan.before_or_equal' => 'Tanggal pencairan tidak boleh melebihi hari ini.',
            'bukti_pencairan.required' => 'Bukti transfer pencairan wajib dilampirkan.',
            'bukti_pencairan.mimes' => 'Bukti transfer harus berupa file JPG, PNG, WEBP, atau PDF.',
            'bukti_pencairan.max' => 'Ukuran bukti transfer maksimal 5 MB.',
        ]);

        try {
            $this->persetujuan->cairkanBendahara(
                pinjaman: $pinjaman,
                buktiTransfer: $request->file('bukti_pencairan'),
                tanggalPencairan: (string) $data['tanggal_pencairan'],
                catatan: $data['catatan'] ?? null,
                aktorId: $request->user()->id,
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['keputusan' => $e->getMessage()]);
        }

        return redirect()->route('bendahara.pencairan-pinjaman.index')
            ->with('status', "Pinjaman #{$pinjaman->id} berhasil dicairkan, saldo kas diperbarui, dan notifikasi WA + bukti transfer telah dikirim.");
    }
}
