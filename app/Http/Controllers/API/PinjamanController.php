<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Pinjaman;
use App\Models\Anggota;
use Illuminate\Http\Request;

class PinjamanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // For API, we might want to show all pinjaman (for admins) or just user's pinjaman
        // For now, let's show the authenticated user's pinjaman
        $anggota = $request->user()->anggota;
        
        if (!$anggota) {
            return response()->json([
                'message' => 'Anggota not found for user',
            ], 404);
        }

        // Build query
        $query = Pinjaman::where('anggota_id', $anggota->id)
            ->with(['anggota.user', 'pengaju']);

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Get paginated results
        $pinjaman = $query->latest()->paginate(15);

        return response()->json($pinjaman);
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

        // Create pinjaman
        $pinjaman = Pinjaman::create([
            'anggota_id' => $anggota->id,
            'pengaju_user_id' => $request->user()->id,
            'nominal' => $request->nominal,
            'tenor_bulan' => $request->tenor_bulan,
            'keperluan' => $request->keperluan,
            'status' => 'diajukan',
            'tanggal_pengajuan' => now(),
            // In a real implementation, you would also set:
            // snapshot_bank, snapshot_no_rekening, snapshot_atas_nama
            // persentase_bunga (from settings)
        ]);

        return response()->json($pinjaman, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Pinjaman $pinjaman)
    {
        // Check if the pinjaman belongs to the authenticated user's anggota
        $anggota = $request->user()->anggota;
        
        if (!$anggota || $pinjaman->anggota_id !== $anggota->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        // Load relationships
        $pinjaman->load(['anggota.user', 'pengaju', 'angsuran']);

        return response()->json($pinjaman);
    }

    /**
     * Check loan eligibility/nominal
     */
    public function cekNominal(Request $request, Pinjaman $pinjaman)
    {
        // Check if the pinjaman belongs to the authenticated user's anggota
        $anggota = $request->user()->anggota;
        
        if (!$anggota || $pinjaman->anggota_id !== $anggota->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        // In a real implementation, this would call a service to check eligibility
        // For now, we'll return a mock response
        
        // Get eligibility service (this would be injected in real implementation)
        // For now, we'll calculate a simple eligibility based on simpanan
        
        $totalSimpanan = $anggota->simpanan()
            ->whereIn('jenis', ['pokok', 'wajib'])
            ->sum('jumlah');
            
        // Simple rule: maximum loan is 3x total simpanan
        $maxNominal = $totalSimpanan * 3;
        
        return response()->json([
            'eligible' => $request->nominal <= $maxNominal,
            'max_nominal' => $maxNominal,
            'current_nominal' => $request->nominal,
            'message' => $request->nominal <= $maxNominal 
                ? 'Loan amount is eligible' 
                : 'Loan amount exceeds maximum eligible amount of ' . number_format($maxNominal, 0, ',', '.'),
        ]);
    }

    /**
     * Simulate loan
     */
    public function simulasi(Request $request, Pinjaman $pinjaman)
    {
        // Check if the pinjaman belongs to the authenticated user's anggota
        $anggota = $request->user()->anggota;
        
        if (!$anggota || $pinjaman->anggota_id !== $anggota->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        // Validate request
        $request->validate([
            'nominal' => 'sometimes|required|numeric|min:1000000',
            'tenor_bulan' => 'sometimes|required|integer|min:1|max:120',
        ]);

        // Use values from request if provided, otherwise use pinjaman values
        $nominal = $request->nominal ?? $pinjaman->nominal;
        $tenorBulan = $request->tenor_bulan ?? $pinjaman->tenor_bulan;
        
        // In a real implementation, this would use a service to calculate simulation
        // For now, we'll do a simple calculation
        
        // Get bunga percentage from settings (simplified)
        $bungaPersentase = 1.5; // This should come from SettingBunga
        
        // Calculate bunga per bulan
        $bungaPerBulan = ($nominal * $bungaPersentase / 100);
        
        // Calculate angsuran per bulan (pokok + bunga)
        $pokokPerBulan = $nominal / $tenorBulan;
        $angsuranPerBulan = $pokokPerBulan + $bungaPerBulan;
        
        // Total bunga
        $totalBunga = $bungaPerBulan * $tenorBulan;
        
        // Total pembayaran
        $totalPembayaran = $nominal + $totalBunga;

        return response()->json([
            'nominal' => $nominal,
            'tenor_bulan' => $tenorBulan,
            'pokok_per_bulan' => $pokokPerBulan,
            'bunga_per_bulan' => $bungaPerBulan,
            'angsuran_per_bulan' => $angsuranPerBulan,
            'total_bunga' => $totalBunga,
            'total_pembayaran' => $totalPembayaran,
            'rincian_angsuran' => array_map(fn($i) => [
                'cicilan_ke' => $i + 1,
                'pokok' => $pokokPerBulan,
                'bunga' => $bungaPerBulan,
                'total' => $angsuranPerBulan,
            ], range(0, $tenorBulan - 1)),
        ]);
    }
}