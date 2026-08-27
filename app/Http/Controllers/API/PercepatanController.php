<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PengajuanPercepatan;
use App\Models\Anggota;
use Illuminate\Http\Request;

class PercepatanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Get authenticated user's anggota
        $anggota = $request->user()->anggota;
        
        if (!$anggota) {
            return response()->json([
                'message' => 'Anggota not found for user',
            ], 404);
        }

        // Build query to get percepatan through pinjaman
        $query = PengajuanPercepatan::whereHas('pinjaman.anggota', function ($q) use ($anggota) {
            $q->where('id', $anggota->id);
        })
        ->with(['pinjaman.anggota.user']);

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Get paginated results
        $percepatans = $query->latest()->paginate(15);

        return response()->json($percepatans);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate request
        $request->validate([
            'pinjaman_id' => 'required|exists:pinjaman,id',
            'tenor_lama' => 'required|integer|min:1',
            'tenor_baru' => 'required|integer|min:1|max:120',
            // Note: In a real implementation, you would also validate alasan
        ]);

        // Get authenticated user's anggota
        $anggota = $request->user()->anggota;
        
        if (!$anggota) {
            return response()->json([
                'message' => 'Anggota not found for user',
            ], 404);
        }

        // Check if anggota is active
        if ($anggota->status !== 'aktif') {
            return response()->json([
                'message' => 'Only active members can submit applications',
            ], 403);
        }

        // Verify that the pinjaman belongs to the authenticated user's anggota
        $pinjaman = \App\Models\Pinjaman::where('id', $request->pinjaman_id)
            ->where('anggota_id', $anggota->id)
            ->first();
            
        if (!$pinjaman) {
            return response()->json([
                'message' => 'Pinjaman not found or does not belong to you',
            ], 404);
        }

        // Check if pinjaman is active
        if ($pinjaman->status !== 'aktif') {
            return response()->json([
                'message' => 'Can only apply for tenor change on active loans',
            ], 403);
        }

        // Create pengajuan percepatan
        $pengajuanPercepatan = PengajuanPercepatan::create([
            'pinjaman_id' => $request->pinjaman_id,
            'tenor_lama' => $request->tenor_lama,
            'tenor_baru' => $request->tenor_baru,
            'status' => 'diajukan',
            'tanggal_pengajuan' => now(),
            // In a real implementation, you would also set:
            // alasan (from request)
        ]);

        return response()->json($pengajuanPercepatan, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, PengajuanPercepatan $pengajuanPercepatan)
    {
        // Check if the percepatan belongs to the authenticated user's anggota
        $anggota = $request->user()->anggota;
        
        if (!$anggota || $pengajuanPercepatan->pinjaman->anggota->id !== $anggota->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        // Load relationships
        $pengajuanPercepatan->load(['pinjaman.anggota.user']);

        return response()->json($pengajuanPercepatan);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PengajuanPercepatan $pengajuanPercepatan)
    {
        // Check if the percepatan belongs to the authenticated user's anggota
        $anggota = $request->user()->anggota;
        
        if (!$anggota || $pengajuanPercepatan->pinjaman->anggota->id !== $anggota->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        // Only allow updates if status is still 'diajukan'
        if ($pengajuanPercepatan->status !== 'diajukan') {
            return response()->json([
                'message' => 'Can only update applications that are still pending',
            ], 403);
        }

        // Validate request
        $request->validate([
            'tenor_lama' => 'sometimes|required|integer|min:1',
            'tenor_baru' => 'sometimes|required|integer|min:1|max:120',
            // Note: In a real implementation, you would also validate alasan
        ]);

        // Update pengajuan percepatan
        $pengajuanPercepatan->update($request->only([
            'tenor_lama',
            'tenor_baru',
            // Note: In a real implementation, you would also update alasan
        ]));

        return response()->json($pengajuanPercepatan);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, PengajuanPercepatan $pengajuanPercepatan)
    {
        // Check if the percepatan belongs to the authenticated user's anggota
        $anggota = $request->user()->anggota;
        
        if (!$anggota || $pengajuanPercepatan->pinjaman->anggota->id !== $anggota->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        // Only allow deletion if status is still 'diajukan'
        if ($pengajuanPercepatan->status !== 'diajukan') {
            return response()->json([
                'message' => 'Can only delete applications that are still pending',
            ], 403);
        }

        // Note: In a real implementation, we might want to soft delete or keep history
        // For now, we'll just delete
        $pengajuanPercepatan->delete();

        return response()->json([
            'message' => 'Application deleted successfully',
        ]);
    }

    /**
     * Preview tenor changes impact
     */
    public function preview(Request $request, PengajuanPercepatan $pengajuanPercepatan)
    {
        // Check if the percepatan belongs to the authenticated user's anggota
        $anggota = $request->user()->anggota;
        
        if (!$anggota || $pengajuanPercepatan->pinjaman->anggota->id !== $anggota->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        // Validate request
        $request->validate([
            'tenor_lama' => 'sometimes|required|integer|min:1',
            'tenor_baru' => 'sometimes|required|integer|min:1|max:120',
        ]);

        // Use values from request if provided, otherwise use percepatan values
        $tenorLama = $request->tenor_lama ?? $pengajuanPercepatan->tenor_lama;
        $tenorBaru = $request->tenor_baru ?? $pengajuanPercepatan->tenor_baru;
        
        // Get the pinjaman
        $pinjaman = $pengajuanPercepatan->pinjaman;
        
        // In a real implementation, this would use a service to calculate the impact
        // For now, we'll do a simplified calculation
        
        // Get current angsuran details
        $angsuranList = $pinjaman->angsuran()
            ->where('status', 'belum_bayar')
            ->orderBy('cicilan_ke')
            ->get();
            
        if ($angsuranList->isEmpty()) {
            return response()->json([
                'message' => 'No remaining installments found',
            ], 400);
        }
        
        // Calculate current situation
        $currentPokokPerBulan = $angsuranList->avg('nominal_pokok');
        $currentBungaPerBulan = $angsuranList->avg('nominal_bunga');
        $currentTotalPerBulan = $angsuranList->avg('total_bayar');
        $remainingCicilan = $angsuranList->count();
        
        // Calculate new situation (simplified)
        // In reality, this would involve recalculating the entire schedule
        // For now, we'll just show what would change
        
        $pokokPerBulanBaru = $pinjaman->nominal / $tenorBaru;
        // Assuming bunga rate stays the same
        $bungaRate = $pinjaman->persentase_bunga;
        $totalBungaBaru = ($pinjaman->nominal * $bungaRate / 100) * $tenorBaru;
        $bungaPerBulanBaru = $totalBungaBaru / $tenorBaru;
        $totalPerBulanBaru = $pokokPerBulanBaru + $bungaPerBulanBaru;
        
        // Calculate savings or extra cost
        $totalSaatIni = $currentTotalPerBulan * $remainingCicilan;
        $totalBaru = $totalPerBulanBaru * $tenorBaru;
        $selisih = $totalBaru - $totalSaatIni;

        return response()->json([
            'current' => [
                'tenor_sisa' => $remainingCicilan,
                'pokok_per_bulan' => $currentPokokPerBulan,
                'bunga_per_bulan' => $currentBungaPerBulan,
                'total_per_bulan' => $currentTotalPerBulan,
                'total_sisa' => $totalSaatIni,
            ],
            'proposed' => [
                'tenor_baru' => $tenorBaru,
                'pokok_per_bulan' => $pokokPerBulanBaru,
                'bunga_per_bulan' => $bungaPerBulanBaru,
                'total_per_bulan' => $totalPerBulanBaru,
                'total_baru' => $totalBaru,
            ],
            'comparison' => [
                'selisih_total' => $selisih,
                'perubahan_tenor' => $tenorBaru - $tenorLama,
            ],
            'message' => $selisih > 0
                ? 'Tenor extension will increase total payment by ' . number_format($selisih, 0, ',', '.')
                : 'Tenor reduction will decrease total payment by ' . number_format(abs($selisih), 0, ',', '.'),
        ]);
    }
}