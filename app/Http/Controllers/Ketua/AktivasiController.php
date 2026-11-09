<?php

namespace App\Http\Controllers\Ketua;

use App\Http\Controllers\Controller;
use App\Http\Requests\KeputusanPinjamanRequest;
use App\Models\PengajuanAktivasi;
use App\Models\SettingLimitPinjaman;
use App\Models\SettingSimpanan;
use App\Services\Anggota\PengajuanAktivasiService;
use Carbon\CarbonInterface;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class AktivasiController extends Controller
{
    public function __construct(private PengajuanAktivasiService $service) {}

    public function index(): Response
    {
        $menunggu = PengajuanAktivasi::with(['anggota.user'])
            ->where('status', 'diajukan')
            ->latest('tanggal_pengajuan')
            ->get()
            ->map(self::formatItem());

        $riwayat = PengajuanAktivasi::with(['anggota.user'])
            ->whereIn('status', ['disetujui', 'ditolak'])
            ->latest('updated_at')
            ->take(20)
            ->get()
            ->map(self::formatItem());

        $ketentuanFinansial = [
            'simpanan_pokok' => (float) (SettingSimpanan::where('jenis', 'pokok')->value('nominal') ?? 50_000),
            'simpanan_wajib' => (float) (SettingSimpanan::where('jenis', 'wajib')->value('nominal') ?? 45_000),
            'dana_sosial' => (float) (SettingSimpanan::where('jenis', 'dana_sosial')->value('nominal') ?? 5_000),
            'limit_awal' => (float) (SettingLimitPinjaman::orderBy('min_bulan')->value('limit_maksimal') ?? 1_000_000),
        ];

        return Inertia::render('Ketua/Aktivasi/Index', [
            'menunggu' => $menunggu,
            'riwayat' => $riwayat,
            'ketentuanFinansial' => $ketentuanFinansial,
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
            'data_benar' => (bool) $p->data_benar,
            'setuju_syarat' => (bool) $p->setuju_syarat,
            'catatan_ketua' => $p->catatan_ketua,
            'tanggal_pengajuan' => $p->tanggal_pengajuan->format('d M Y'),
            'tanggal_keputusan' => $p->updated_at && $p->status !== 'diajukan' ? $p->updated_at->format('d M Y, H:i') : null,
            'anggota' => [
                'id' => $p->anggota->id,
                'nama' => $p->anggota->nama,
                'no_anggota' => $p->anggota->no_anggota,
                'no_karyawan' => $p->anggota->no_karyawan,
                'no_ktp' => $p->anggota->no_ktp,
                'email' => $p->anggota->user?->email,
                'cabang' => $p->anggota->cabang,
                'unit_bisnis' => $p->anggota->unit_bisnis,
                'department' => $p->anggota->department,
                'divisi' => $p->anggota->divisi,
                'jabatan' => $p->anggota->jabatan,
                'no_hp' => $p->anggota->no_hp,
                'alamat' => $p->anggota->alamat,
                'foto_url' => $p->anggota->foto_url,
                'status' => $p->anggota->status,
                'tanggal_mulai_kerja' => $p->anggota->tanggal_mulai_kerja?->format('d M Y'),
                'masa_kerja' => self::hitungMasaKerja($p->anggota->tanggal_mulai_kerja),
                'tanggal_jadi_anggota' => $p->anggota->tanggal_jadi_anggota?->format('d M Y'),
                'lama_keanggotaan_tahun' => round($p->anggota->lama_keanggotaan_tahun, 1),
                'gate_synced_at' => $p->anggota->gate_synced_at?->format('d M Y, H:i'),
                'is_reaktivasi' => ! empty($p->anggota->tanggal_resign) || ! empty($p->anggota->reaktivasi_history_json),
                'tanggal_resign' => $p->anggota->tanggal_resign?->format('d M Y'),
                'alasan_resign' => $p->anggota->alasan_resign,
            ],
        ];
    }

    private static function hitungMasaKerja(?CarbonInterface $tanggal): ?string
    {
        if (! $tanggal) {
            return null;
        }

        $diff = $tanggal->diff(now());
        $tahun = $diff->y;
        $bulan = $diff->m;

        if ($tahun > 0 && $bulan > 0) {
            return "{$tahun} tahun {$bulan} bulan";
        } elseif ($tahun > 0) {
            return "{$tahun} tahun";
        } elseif ($bulan > 0) {
            return "{$bulan} bulan";
        }

        return 'Kurang dari 1 bulan';
    }
}
