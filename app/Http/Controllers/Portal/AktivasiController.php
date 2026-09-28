<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\PengajuanAktivasi;
use App\Models\SettingLimitPinjaman;
use App\Models\SettingSimpanan;
use App\Services\Anggota\PengajuanAktivasiService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class AktivasiController extends Controller
{
    public function __construct(private PengajuanAktivasiService $service) {}

    public function create(): Response
    {
        $anggota = auth()->user()->anggota;

        abort_unless($anggota && $anggota->status === 'nonaktif', 404, 'Halaman aktivasi hanya untuk anggota berstatus nonaktif.');

        $berjalan = PengajuanAktivasi::where('anggota_id', $anggota->id)
            ->where('status', 'diajukan')
            ->latest('tanggal_pengajuan')
            ->first();

        $terakhirDitolak = PengajuanAktivasi::where('anggota_id', $anggota->id)
            ->where('status', 'ditolak')
            ->latest('updated_at')
            ->first();

        return Inertia::render('Portal/Aktivasi/Create', [
            'anggota' => [
                'nama' => $anggota->nama,
                'no_anggota' => $anggota->no_anggota,
                'no_karyawan' => $anggota->no_karyawan,
                'cabang' => $anggota->cabang,
                'unit_bisnis' => $anggota->unit_bisnis,
                'jabatan' => $anggota->jabatan,
                'department' => $anggota->department,
                'no_hp' => $anggota->no_hp,
                'alamat' => $anggota->alamat,
                'status' => $anggota->status,
            ],
            'poinSyarat' => config('syarat_aktivasi.poin'),
            'versiSyarat' => config('syarat_aktivasi.versi'),
            'simpananPokok' => (float) (SettingSimpanan::where('jenis', 'pokok')->value('nominal') ?? 50_000),
            'simpananWajib' => (float) (SettingSimpanan::where('jenis', 'wajib')->value('nominal') ?? 45_000),
            'danaSosial' => (float) (SettingSimpanan::where('jenis', 'dana_sosial')->value('nominal') ?? 5_000),
            'limitAwal' => (float) (SettingLimitPinjaman::where('kategori', 'kurang_1_tahun')->value('limit_maksimal') ?? 1_000_000),
            'pengajuanBerjalan' => $berjalan ? [
                'tanggal_pengajuan' => $berjalan->tanggal_pengajuan->format('d M Y'),
                'status' => $berjalan->status,
            ] : null,
            'ditolakTerakhir' => $terakhirDitolak ? [
                'catatan' => $terakhirDitolak->catatan_ketua,
                'tanggal' => $terakhirDitolak->updated_at->format('d M Y'),
            ] : null,
        ]);
    }

    public function store(Request $request)
    {
        $anggota = auth()->user()->anggota;

        abort_unless($anggota && $anggota->status === 'nonaktif', 403, 'Hanya anggota nonaktif yang dapat mengajukan aktivasi.');

        $request->validate([
            'data_benar' => ['required', 'accepted'],
            'setuju_syarat' => ['required', 'accepted'],
        ], [
            'data_benar.required' => 'Konfirmasi kebenaran data wajib dicentang.',
            'data_benar.accepted' => 'Konfirmasi kebenaran data wajib dicentang.',
            'setuju_syarat.required' => 'Persetujuan syarat wajib dicentang.',
            'setuju_syarat.accepted' => 'Persetujuan syarat wajib dicentang.',
        ]);

        try {
            $this->service->ajukan($anggota, true, true, auth()->id());
        } catch (RuntimeException $e) {
            return back()->withErrors(['pengajuan' => $e->getMessage()]);
        }

        return redirect()->route('portal.aktivasi.create')
            ->with('status', 'Pengajuan aktivasi terkirim. Menunggu persetujuan Ketua.');
    }
}
