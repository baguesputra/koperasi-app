<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\PinjamanResource;
use App\Models\Pinjaman;
use App\Services\Pinjaman\EligibilitasPinjamanService;
use App\Services\Pinjaman\PengajuanPinjamanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class PinjamanController extends Controller
{
    public function __construct(
        private EligibilitasPinjamanService $eligibilitas,
        private PengajuanPinjamanService $pengajuan,
    ) {}

    private function anggotaOrFail(Request $request)
    {
        $anggota = $request->user()->anggota;

        if (! $anggota) {
            abort(response()->json([
                'message' => 'Anggota not found for user',
            ], 404));
        }

        return $anggota;
    }

    /**
     * Detail pinjaman: milik sendiri, atau pemegang izin pinjaman.lihat.
     *
     * @response 200 {"data": {"id": 1, "status": "diajukan"}}
     */
    public function show(Request $request, Pinjaman $pinjaman): JsonResponse
    {
        $anggota = $request->user()->anggota;
        $milikSendiri = $anggota && $pinjaman->anggota_id === $anggota->id;

        if (! $milikSendiri && ! $request->user()->can('pinjaman.lihat')) {
            abort(response()->json([
                'message' => 'Anda tidak memiliki akses ke pinjaman ini.',
            ], 403));
        }

        $pinjaman->loadMissing(['anggota', 'pengaju']);

        return response()->json([
            'data' => (new PinjamanResource($pinjaman))->resolve(),
        ]);
    }

    /**
     * Cek nominal terhadap limit tersedia + tabel tenor.
     *
     * @response 200 {"valid": true, "tenor_maksimal": 3}
     */
    public function cekNominal(Request $request): JsonResponse
    {
        $request->validate(['nominal' => ['required', 'numeric', 'min:1']]);

        $anggota = $this->anggotaOrFail($request);
        $nominal = (float) $request->nominal;
        $limitTersedia = $this->eligibilitas->limitTersedia($anggota);

        if ($nominal > $limitTersedia) {
            $pesan = 'Nominal melebihi limit tersedia Anda: Rp '.number_format($limitTersedia, 0, ',', '.');

            return response()->json(['message' => $pesan, 'valid' => false, 'pesan' => $pesan], 422);
        }

        $tenorMaksimal = $this->eligibilitas->tenorMaksimal($nominal);

        if (! $tenorMaksimal) {
            $pesan = 'Ketentuan tenor untuk nominal ini belum diatur. Silakan hubungi Admin koperasi.';

            return response()->json(['message' => $pesan, 'valid' => false, 'pesan' => $pesan], 422);
        }

        return response()->json(['valid' => true, 'tenor_maksimal' => $tenorMaksimal]);
    }

    /**
     * Simulasi jadwal angsuran (belum disimpan).
     *
     * @response 200 {"jadwal": [{"cicilan_ke": 1}], "total_dibayar": 1030000}
     */
    public function simulasi(Request $request): JsonResponse
    {
        $request->validate([
            'nominal' => ['required', 'numeric', 'min:1'],
            'tenor_bulan' => ['required', 'integer', 'min:1'],
        ]);

        $jadwal = $this->pengajuan->preview((float) $request->nominal, (int) $request->tenor_bulan);

        return response()->json([
            'jadwal' => $jadwal,
            'total_dibayar' => array_sum(array_column($jadwal, 'total_bayar')),
        ]);
    }

    /**
     * Pengajuan pinjaman baru dari aplikasi mobile.
     *
     * Sengaja TANPA verifikasi PIN: sesi Sanctum + biometrik di HP
     * menggantikan peran PIN yang di portal web diketik manual.
     *
     * @response 201 {"message": "Pengajuan pinjaman berhasil dikirim.", "data": {"id": 1}}
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'nominal' => ['required', 'numeric', 'min:1'],
            'tenor_bulan' => ['required', 'integer', 'min:1'],
            'keperluan' => ['required', 'string', 'min:5', 'max:500'],
            'rekening_mode' => ['required', 'in:tersimpan,baru'],
            'rekening_id' => ['required_if:rekening_mode,tersimpan', 'nullable', 'integer'],
            'nama_bank' => ['required_if:rekening_mode,baru', 'nullable', 'string', 'max:100'],
            'no_rekening' => ['required_if:rekening_mode,baru', 'nullable', 'string', 'max:50'],
            'atas_nama' => ['required_if:rekening_mode,baru', 'nullable', 'string', 'max:100'],
            'persetujuan' => ['required', 'accepted'],
        ], [
            'persetujuan.required' => 'Anda harus menyetujui seluruh syarat & ketentuan pinjaman terlebih dahulu.',
            'persetujuan.accepted' => 'Anda harus menyetujui seluruh syarat & ketentuan pinjaman terlebih dahulu.',
        ]);

        $anggota = $this->anggotaOrFail($request);

        if ($request->rekening_mode === 'tersimpan' && ! $anggota->rekening()->find($request->rekening_id)) {
            return response()->json([
                'message' => 'Rekening tujuan wajib dipilih.',
                'errors' => ['rekening_id' => ['Pilih rekening tujuan.']],
            ], 422);
        }

        try {
            $pinjaman = $this->pengajuan->ajukan(
                $request->user(),
                (float) $request->nominal,
                (int) $request->tenor_bulan,
                $request->keperluan,
                [
                    'mode' => $request->rekening_mode,
                    'rekening_id' => $request->rekening_id,
                    'nama_bank' => $request->nama_bank,
                    'no_rekening' => $request->no_rekening,
                    'atas_nama' => $request->atas_nama,
                ],
                [
                    'persetujuan' => true,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $pinjaman->loadMissing(['anggota', 'pengaju']);

        return response()->json([
            'message' => 'Pengajuan pinjaman berhasil dikirim.',
            'data' => (new PinjamanResource($pinjaman))->resolve(),
        ], 201);
    }
}
