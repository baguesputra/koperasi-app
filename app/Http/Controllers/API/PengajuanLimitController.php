<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PengajuanLimit;
use App\Models\Anggota;
use Illuminate\Http\Request;

class PengajuanLimitController extends Controller
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

        // Build query
        $query = PengajuanLimit::where('anggota_id', $anggota->id)
            ->with(['anggota.user']);

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Get paginated results
        $pengajuanLimits = $query->latest()->paginate(15);

        return response()->json($pengajuanLimits);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate request
        $request->validate([
            'limit_diminta' => 'required|numeric|min:1000000',
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

        // Create pengajuan limit
        // Get current limit from anggota's limit_custom or use a default
        $limitSaatIni = $anggota->limit_custom ?? 0;
        
        $pengajuanLimit = PengajuanLimit::create([
            'anggota_id' => $anggota->id,
            'limit_saat_ini' => $limitSaatIni,
            'limit_diminta' => $request->limit_diminta,
            'keterangan' => 'Pengajuan via mobile app', // Default keterangan
            'status' => 'diajukan',
            'tanggal_pengajuan' => now(),
            // In a real implementation, you would also set:
            // alasan (from request)
        ]);

        return response()->json($pengajuanLimit, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, PengajuanLimit $pengajuanLimit)
    {
        // Check if the pengajuan limit belongs to the authenticated user's anggota
        $anggota = $request->user()->anggota;
        
        if (!$anggota || $pengajuanLimit->anggota_id !== $anggota->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        // Load relationships
        $pengajuanLimit->load(['anggota.user']);

        return response()->json($pengajuanLimit);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PengajuanLimit $pengajuanLimit)
    {
        // Check if the pengajuan limit belongs to the authenticated user's anggota
        $anggota = $request->user()->anggota;
        
        if (!$anggota || $pengajuanLimit->anggota_id !== $anggota->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        // Only allow updates if status is still 'diajukan'
        if ($pengajuanLimit->status !== 'diajukan') {
            return response()->json([
                'message' => 'Can only update applications that are still pending',
            ], 403);
        }

        // Validate request
        $request->validate([
            'limit_diminta' => 'sometimes|required|numeric|min:1000000',
            // Note: In a real implementation, you would also validate alasan
        ]);

        // Update pengajuan limit
        $pengajuanLimit->update($request->only([
            'limit_diminta',
            // Note: In a real implementation, you would also update alasan
        ]));

        return response()->json($pengajuanLimit);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, PengajuanLimit $pengajuanLimit)
    {
        // Check if the pengajuan limit belongs to the authenticated user's anggota
        $anggota = $request->user()->anggota;
        
        if (!$anggota || $pengajuanLimit->anggota_id !== $anggota->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        // Only allow deletion if status is still 'diajukan'
        if ($pengajuanLimit->status !== 'diajukan') {
            return response()->json([
                'message' => 'Can only delete applications that are still pending',
            ], 403);
        }

        $pengajuanLimit->delete();

        return response()->json([
            'message' => 'Application deleted successfully',
        ]);
    }
}