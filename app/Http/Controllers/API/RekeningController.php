<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\RekeningAnggota;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RekeningController extends Controller
{
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

    private function bentuk(RekeningAnggota $rekening): array
    {
        return [
            'id' => $rekening->id,
            'nama_bank' => $rekening->nama_bank,
            'no_rekening' => $rekening->no_rekening,
            'atas_nama' => $rekening->atas_nama,
            'is_default' => (bool) $rekening->is_default,
        ];
    }

    /**
     * Daftar rekening milik sendiri, utama paling atas.
     *
     * @response 200 {"data": [{"id": 1, "nama_bank": "BRI", "is_default": true}]}
     */
    public function index(Request $request): JsonResponse
    {
        $anggota = $this->anggotaOrFail($request);

        $data = $anggota->rekening()->orderByDesc('is_default')->orderBy('id')
            ->get()->map(fn ($r) => $this->bentuk($r))->values();

        return response()->json(['data' => $data]);
    }

    /**
     * Tambah rekening. Rekening pertama otomatis jadi utama.
     *
     * @response 201 {"message": "Rekening ditambahkan.", "data": {"id": 1}}
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'nama_bank' => ['required', 'string', 'max:100'],
            'no_rekening' => ['required', 'string', 'max:50'],
            'atas_nama' => ['required', 'string', 'max:100'],
        ]);

        $anggota = $this->anggotaOrFail($request);

        $rekening = $anggota->rekening()->create([
            'nama_bank' => $request->nama_bank,
            'no_rekening' => $request->no_rekening,
            'atas_nama' => $request->atas_nama,
            'is_default' => ! $anggota->rekening()->exists(),
        ]);

        return response()->json([
            'message' => 'Rekening ditambahkan.',
            'data' => $this->bentuk($rekening),
        ], 201);
    }

    /**
     * Jadikan rekening utama (milik sendiri).
     *
     * @response 200 {"message": "Rekening utama diperbarui."}
     */
    public function setDefault(Request $request, int $id): JsonResponse
    {
        $anggota = $this->anggotaOrFail($request);
        $rekening = $anggota->rekening()->findOrFail($id);

        DB::transaction(function () use ($anggota, $rekening) {
            $anggota->rekening()->update(['is_default' => false]);
            $rekening->update(['is_default' => true]);
        });

        return response()->json(['message' => 'Rekening utama diperbarui.']);
    }

    /**
     * Hapus rekening (milik sendiri). Bila yang dihapus adalah utama,
     * sisa pertama dijadikan utama bila masih ada.
     *
     * @response 200 {"message": "Rekening dihapus."}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $anggota = $this->anggotaOrFail($request);
        $rekening = $anggota->rekening()->findOrFail($id);

        DB::transaction(function () use ($anggota, $rekening) {
            $wasDefault = (bool) $rekening->is_default;
            $rekening->delete();

            if ($wasDefault) {
                $pengganti = $anggota->rekening()->orderBy('id')->first();

                if ($pengganti) {
                    $pengganti->update(['is_default' => true]);
                }
            }
        });

        return response()->json(['message' => 'Rekening dihapus.']);
    }
}
