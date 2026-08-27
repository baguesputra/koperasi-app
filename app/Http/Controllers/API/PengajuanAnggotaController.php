<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Pinjaman;
use App\Models\Anggota;
use Illuminate\Http\Request;

class PengajuanAnggotaController extends Controller
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

        // Build query - use Pinjaman model for loan applications
        $query = Pinjaman::where('anggota_id', $anggota->id)
            ->with(['anggota.user', 'pengaju']);

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('tanggal_pengajuan', [
                $request->start_date,
                $request->end_date
            ]);
        }

        // Get paginated results
        $pengajuans = $query->latest()->paginate(15);

        return response()->json($pengajuans);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate request
        $request->validate([
            'nominal' => 'required|numeric|min:1000000',
            'tenor_bulan' => 'required|integer|min:1|max:120',
            'keperluan' => 'required|string|max:255',
            // Note: In a real implementation, you would also validate bank details
            // These would come from the form in the portal
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

        // Create pengajuan (using Pinjaman model)
        // Get current bunga rate from settings
        $bungaRate = \App\Models\SettingBunga::latest('berlaku_dari_tanggal')
            ->value('persentase') ?? 1.5; // Default 1.5% if no setting found
            
        $pengajuan = Pinjaman::create([
            'anggota_id' => $anggota->id,
            'pengaju_user_id' => $request->user()->id,
            'nominal' => $request->nominal,
            'tenor_bulan' => $request->tenor_bulan,
            'keperluan' => $request->keperluan,
            'persentase_bunga' => $bungaRate,
            'status' => 'diajukan',
            'tanggal_pengajuan' => now(),
            // In a real implementation, you would also set:
            // snapshot_bank, snapshot_no_rekening, snapshot_atas_nama
        ]);

        return response()->json($pengajuan, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Pinjaman $pengajuan)
    {
        // Check if the pengajuan belongs to the authenticated user's anggota
        $anggota = $request->user()->anggota;
        
        if (!$anggota || $pengajuan->anggota_id !== $anggota->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        // Load relationships
        $pengajuan->load(['anggota.user', 'pengaju']);

        return response()->json($pengajuan);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Pinjaman $pengajuan)
    {
        // Check if the pengajuan belongs to the authenticated user's anggota
        $anggota = $request->user()->anggota;
        
        if (!$anggota || $pengajuan->anggota_id !== $anggota->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        // Only allow updates if status is still 'diajukan'
        if ($pengajuan->status !== 'diajukan') {
            return response()->json([
                'message' => 'Can only update applications that are still pending',
            ], 403);
        }

        // Validate request
        $request->validate([
            'nominal' => 'sometimes|required|numeric|min:1000000',
            'tenor_bulan' => 'sometimes|required|integer|min:1|max:120',
            'keperluan' => 'sometimes|required|string|max:255',
        ]);

        // Update pengajuan
        $pengajuan->update($request->only([
            'nominal',
            'tenor_bulan',
            'keperluan',
        ]));

        return response()->json($pengajuan);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Pinjaman $pengajuan)
    {
        // Check if the pengajuan belongs to the authenticated user's anggota
        $anggota = $request->user()->anggota;
        
        if (!$anggota || $pengajuan->anggota_id !== $anggota->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        // Only allow deletion if status is still 'diajukan'
        if ($pengajuan->status !== 'diajukan') {
            return response()->json([
                'message' => 'Can only delete applications that are still pending',
            ], 403);
        }

        $pengajuan->delete();

        return response()->json([
            'message' => 'Application deleted successfully',
        ]);
    }
}