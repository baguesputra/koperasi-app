<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Anggota;
use App\Models\Pinjaman;
use App\Models\Simpanan;
use App\Models\KasKoperasi;
use App\Models\JurnalKas;
use App\Models\PengajuanLimit;
use App\Models\PengajuanPercepatan;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics
     */
    public function stats(Request $request)
    {
        // Get authenticated user's anggota
        $anggota = $request->user()->anggota;
        
        if (!$anggota) {
            return response()->json([
                'message' => 'Anggota not found for user',
            ], 404);
        }

        // For member dashboard, we show personal stats
        // For admin/bendahara/ketua, we would show overall stats
        // For now, let's implement member dashboard stats
        
        // Get simpanan totals
        $simpananAgg = $anggota->simpanan()
            ->selectRaw('
                SUM(CASE WHEN jenis IN ("pokok","wajib") THEN jumlah ELSE 0 END) as total,
                SUM(CASE WHEN jenis = "pokok" THEN jumlah ELSE 0 END) as pokok,
                SUM(CASE WHEN jenis = "wajib" THEN jumlah ELSE 0 END) as wajib
            ')
            ->first();

        $totalSimpanan = (float) ($simpananAgg->total ?? 0);
        $simpananPokok = (float) ($simpananAgg->pokok ?? 0);
        $simpananWajib = (float) ($simpananAgg->wajib ?? 0);

        // Get active pinjaman
        $pinjamanAktif = $anggota->pinjamanAktif();

        // Get eligibility (we would use a service in real implementation)
        // For now, we'll calculate a simple eligibility
        $limitMaksimal = $totalSimpanan * 3; // Simplified: 3x simpanan
        $cekEligibilitas = [
            'limit_tersedia' => $pinjamanAktif ? max(0, $limitMaksimal - ($pinjamanAktif->nominal ?? 0)) : $limitMaksimal,
            'sisa_angsuran' => $pinjamanAktif ? $pinjamanAktif->sisaCicilanAktif() : 0,
            'cicilan_pokok' => $pinjamanAktif ? $pinjamanAktif->cicilanPokokAktif() : 0,
            'boleh' => true, // Simplified
            'alasan' => null,
        ];

        // Get next angsuran
        $angsuranBerikutnya = null;
        $sisaTotalBayar = 0;

        if ($pinjamanAktif) {
            $angsuranBelumBayar = $pinjamanAktif->angsuranBelumBayar()
                ->orderBy('cicilan_ke')
                ->get();
                
            $sisaTotalBayar = $angsuranBelumBayar->sum('total_bayar');

            $terdekat = $angsuranBelumBayar->first();
            if ($terdekat) {
                $angsuranBerikutnya = [
                    'cicilan_ke' => $terdekat->cicilan_ke,
                    'total_bayar' => (float) $terdekat->total_bayar,
                    'tanggal_jatuh_tempo' => $terdekat->tanggal_jatuh_tempo->format('d M Y'),
                ];
            }
        }

        // Get riwayat simpanan (last 6)
        $riwayatSimpanan = $anggota->simpanan()
            ->whereIn('jenis', ['wajib', 'pokok'])
            ->latest('tanggal_input')
            ->take(6)
            ->get()
            ->map(fn ($s) => [
                'tipe' => 'simpanan',
                'label' => match ($s->jenis) {
                    'pokok' => 'Simpanan Pokok',
                    'wajib' => 'Simpanan Wajib',
                    'dana_sosial' => 'Dana Sosial',
                    default => $s->jenis,
                },
                'nominal' => (float) $s->jumlah,
                'tanggal' => $s->tanggal_input,
                'tanggal_format' => $s->tanggal_input->format('d M Y'),
            ]);

        // Get riwayat angsuran (lunas)
        $riwayatAngsuran = $anggota->pinjaman()
            ->with(['angsuran' => fn ($q) => $q->where('status', 'lunas')])
            ->get()
            ->pluck('angsuran')
            ->flatten()
            ->map(fn ($a) => [
                'tipe' => 'angsuran',
                'label' => "Cicilan ke-{$a->cicilan_ke}",
                'nominal' => (float) $a->total_bayar,
                'tanggal' => $a->tanggal_konfirmasi_bayar,
                'tanggal_format' => $a->tanggal_konfirmasi_bayar->format('d M Y'),
            ]);

        // Combine and sort riwayat
        $riwayatGabungan = $riwayatSimpanan->concat($riwayatAngsuran)
            ->sortByDesc('tanggal')
            ->take(4)
            ->values()
            ->map(fn ($item) => collect($item)->except('tanggal'));

        // Get tabel tenor (for reference)
        $tabelTenor = \App\Models\TabelTenor::orderBy('nominal_min')
            ->get(['nominal_min', 'nominal_max', 'tenor_maksimal_bulan']);

        // Get setting simpanan (for reference)
        $settingSimpanan = \App\Models\SettingSimpanan::orderBy('id')
            ->get(['jenis', 'label', 'nominal']);

        return response()->json([
            'anggota' => [
                'nama' => $anggota->nama,
                'no_anggota' => $anggota->no_anggota,
                'lama_keanggotaan_label' => $this->formatLamaKeanggotaan($anggota->tanggal_jadi_anggota),
            ],
            'totalSimpanan' => $totalSimpanan,
            'simpananPokok' => $simpananPokok,
            'simpananWajib' => $simpananWajib,
            'limitMaksimal' => (float) $limitMaksimal,
            'limitTersedia' => (float) $cekEligibilitas['limit_tersedia'],
            'sisaAngsuranAktif' => (int) $cekEligibilitas['sisa_angsuran'],
            'cicilanPokokAktif' => (float) $cekEligibilitas['cicilan_pokok'],
            'pinjamanAktif' => $pinjamanAktif ? [
                'id' => $pinjamanAktif->id,
                'nominal' => (float) $pinjamanAktif->nominal,
                'tenor_bulan' => $pinjamanAktif->tenor_bulan,
                'sisa_angsuran' => $pinjamanAktif->sisaCicilanAktif(),
                'total_angsuran' => $pinjamanAktif->totalCicilanAktif(),
                'sisa_total_bayar' => $pinjamanAktif->sisaTotalBayarAktif(),
            ] : null,
            // For simplicity, we're not including all the lists in the stats endpoint
            // These would be in separate endpoints or fetched as needed
        ]);
    }

    /**
     * Get actionable items
     */
    public function actionable(Request $request)
    {
        // This would typically be for admin/bendahara/ketua dashboards
        // For member dashboard, we might show personal actionable items
        // For now, let's return an empty array or basic info
        
        return response()->json([
            'message' => 'Actionable items endpoint - would show pending approvals for admins',
            'data' => []
        ]);
    }

    /**
     * Get chart data
     */
    public function charts(Request $request)
    {
        // Get authenticated user's anggota
        $anggota = $request->user()->anggota;
        
        if (!$anggota) {
            return response()->json([
                'message' => 'Anggota not found for user',
            ], 404);
        }

        // Get simpanan per bulan (last 6 months)
        $awalPeriode = Carbon::now()->subMonths(5)->startOfMonth();
        
        $simpananPerBulan = $anggota->simpanan()
            ->where('tanggal_input', '>=', $awalPeriode)
            ->selectRaw('YEAR(tanggal_input) as tahun, MONTH(tanggal_input) as bulan, SUM(jumlah) as total')
            ->groupBy('tahun', 'bulan')
            ->orderBy('tahun')
            ->orderBy('bulan')
            ->get()
            ->keyBy(fn ($r) => sprintf('%04d-%02d', $r->tahun, $r->bulan));

        // Get pinjaman per bulan (last 6 months) - for member, this would be their own pinjaman
        $pinjamanPerBulan = $anggota->pinjaman()
            ->where('tanggal_pencairan', '>=', $awalPeriode)
            ->selectRaw('YEAR(tanggal_pencairan) as tahun, MONTH(tanggal_pencairan) as bulan, SUM(nominal) as total')
            ->groupBy('tahun', 'bulan')
            ->orderBy('tahun')
            ->orderBy('bulan')
            ->get()
            ->keyBy(fn ($r) => sprintf('%04d-%02d', $r->tahun, $r->bulan));

        // Build chart data
        $labelBulan = [];
        $grafikTren = [];

        for ($i = 5; $i >= 0; $i--) {
            $bulan = Carbon::now()->subMonths($i);
            $key = $bulan->format('Y-m');
            $labelBulan[] = $bulan->translatedFormat('M Y');

            $grafikTren[] = [
                'bulan' => $labelBulan[5 - $i],
                'simpanan' => (float) ($simpananPerBulan[$key]->total ?? 0),
                'pinjaman' => (float) ($pinjamanPerBulan[$key]->total ?? 0),
            ];
        }

        return response()->json([
            'labelBulan' => $labelBulan,
            'grafikTren' => $grafikTren,
        ]);
    }

    /**
     * Get recent activity
     */
    public function aktivitas(Request $request)
    {
        // Get authenticated user's anggota
        $anggota = $request->user()->anggota;
        
        if (!$anggota) {
            return response()->json([
                'message' => 'Anggota not found for user',
            ], 404);
        }

        // Get recent pinjaman applications
        $aktivitasPinjaman = $anggota->pinjaman()
            ->with('anggota')
            ->latest('tanggal_pengajuan')
            ->take(5)
            ->get()
            ->map(fn ($p) => [
                'tipe' => 'pinjaman',
                'nama' => $p->anggota->nama,
                'keterangan' => 'Mengajukan pinjaman ' . number_format($p->nominal, 0, ',', '.'),
                'status' => $p->status,
                'tanggal' => $p->tanggal_pengajuan,
            ]);

        // Get recent angsuran lunas
        $aktivitasAngsuran = \App\Models\Angsuran::whereHas('pinjaman.anggota', function ($q) use ($anggota) {
            $q->where('id', $anggota->id);
        })
            ->where('status', 'lunas')
            ->with('pinjaman.anggota')
            ->latest('tanggal_konfirmasi_bayar')
            ->take(5)
            ->get()
            ->map(fn ($a) => [
                'tipe' => 'angsuran',
                'nama' => $a->pinjaman->anggota->nama,
                'keterangan' => "Membayar cicilan ke-{$a->cicilan_ke}",
                'status' => 'lunas',
                'tanggal' => $a->tanggal_konfirmasi_bayar,
            ]);

        // Combine and sort aktivitas
        $aktivitasTerbaru = $aktivitasPinjaman->concat($aktivitasAngsuran)
            ->sortByDesc('tanggal')
            ->take(6)
            ->values()
            ->map(fn ($item) => [
                ...collect($item)->except('tanggal')->toArray(),
                'tanggal_format' => $item['tanggal']->format('d M Y'),
            ]);

        return response()->json($aktivitasTerbaru);
    }

    /**
     * Format lama keanggotaan
     */
    private function formatLamaKeanggotaan($tanggalJadiAnggota): string
    {
        $sekarang = Carbon::now();
        $tahun = (int) $tanggalJadiAnggota->diffInYears($sekarang);
        $tanggalSetelahTahun = $tanggalJadiAnggota->copy()->addYears($tahun);
        $bulan = (int) $tanggalSetelahTahun->diffInMonths($sekarang);

        if ($tahun === 0 && $bulan === 0) {
            return 'baru bergabung';
        }
        if ($tahun === 0) {
            return "{$bulan} bulan";
        }
        if ($bulan === 0) {
            return "{$tahun} tahun";
        }

        return "{$tahun} tahun {$bulan} bulan";
    }
}