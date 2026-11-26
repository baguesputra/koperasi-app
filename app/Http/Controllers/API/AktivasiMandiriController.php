<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PengajuanAktivasi;
use App\Models\SettingLimitPinjaman;
use App\Models\SettingSimpanan;
use App\Services\Anggota\PengajuanAktivasiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class AktivasiMandiriController extends Controller
{
    public function __construct(private PengajuanAktivasiService $service) {}

    /**
     * Detail halaman aktivasi mandiri untuk anggota nonaktif.
     */
    public function show(Request $request): JsonResponse
    {
        $anggota = $request->user()->anggota;
        if (! $anggota) {
            return response()->json(['message' => 'Data anggota tidak ditemukan.'], 404);
        }

        $berjalan = PengajuanAktivasi::where('anggota_id', $anggota->id)
            ->where('status', 'diajukan')
            ->latest('tanggal_pengajuan')
            ->first();

        $terakhirDitolak = PengajuanAktivasi::where('anggota_id', $anggota->id)
            ->where('status', 'ditolak')
            ->latest('updated_at')
            ->first();

        return response()->json([
            'anggota' => [
                'id' => $anggota->id,
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
            'simpanan_pokok' => (float) (SettingSimpanan::where('jenis', 'pokok')->value('nominal') ?? 50_000),
            'simpanan_wajib' => (float) (SettingSimpanan::where('jenis', 'wajib')->value('nominal') ?? 45_000),
            'dana_sosial' => (float) (SettingSimpanan::where('jenis', 'dana_sosial')->value('nominal') ?? 5_000),
            'limit_awal' => (float) (SettingLimitPinjaman::orderBy('min_bulan')->value('limit_maksimal') ?? 1_000_000),
            'poin_syarat' => config('syarat_aktivasi.poin', []),
            'versi_syarat' => config('syarat_aktivasi.versi'),
            'pengajuan_berjalan' => $berjalan ? [
                'id' => $berjalan->id,
                'tanggal_pengajuan' => $berjalan->tanggal_pengajuan?->format('Y-m-d'),
                'status' => $berjalan->status,
            ] : null,
            'ditolak_terakhir' => $terakhirDitolak ? [
                'catatan' => $terakhirDitolak->catatan_ketua,
                'tanggal' => $terakhirDitolak->updated_at?->format('Y-m-d'),
            ] : null,
        ]);
    }

    /**
     * Kirim pengajuan aktivasi mandiri anggota nonaktif.
     */
    public function store(Request $request): JsonResponse
    {
        $anggota = $request->user()->anggota;

        if (! $anggota || $anggota->status !== 'nonaktif') {
            return response()->json([
                'message' => 'Hanya anggota berstatus nonaktif yang dapat mengajukan aktivasi.',
            ], 403);
        }

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
            $pengajuan = $this->service->ajukan($anggota, true, true, $request->user()->id);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Pengajuan aktivasi terkirim. Menunggu persetujuan Ketua.',
            'data' => [
                'id' => $pengajuan->id,
                'status' => $pengajuan->status,
                'tanggal_pengajuan' => $pengajuan->tanggal_pengajuan?->format('Y-m-d'),
            ],
        ], 201);
    }
}
