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
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Dashboard', description: 'Dashboard endpoints for mobile app')]
class DashboardController extends Controller
{
    /**
     * Get dashboard statistics
     */
    #[OA\Get(
        path: '/api/dashboard/stats',
        summary: 'Get member dashboard statistics',
        tags: ['Dashboard'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Dashboard statistics',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'anggota', type: 'object',
                            properties: [
                                new OA\Property(property: 'nama', type: 'string', example: 'Budi Santoso'),
                                new OA\Property(property: 'no_anggota', type: 'string', example: 'ANG-2026-0001'),
                                new OA\Property(property: 'lama_keanggotaan_label', type: 'string', example: '6 bulan'),
                            ]
                        ),
                        new OA\Property(property: 'totalSimpanan', type: 'number', format: 'float', example: 320000),
                        new OA\Property(property: 'simpananPokok', type: 'number', format: 'float', example: 50000),
                        new OA\Property(property: 'simpananWajib', type: 'number', format: 'float', example: 270000),
                        new OA\Property(property: 'limitMaksimal', type: 'number', format: 'float', example: 960000),
                        new OA\Property(property: 'limitTersedia', type: 'number', format: 'float', example: 960000),
                        new OA\Property(property: 'sisaAngsuranAktif', type: 'integer', example: 0),
                        new OA\Property(property: 'cicilanPokokAktif', type: 'number', format: 'float', example: 0),
                        new OA\Property(property: 'pinjamanAktif', type: 'object', nullable: true,
                            properties: [
                                new OA\Property(property: 'id', type: 'integer'),
                                new OA\Property(property: 'nominal', type: 'number', format: 'float'),
                                new OA\Property(property: 'tenor_bulan', type: 'integer'),
                                new OA\Property(property: 'sisa_angsuran', type: 'integer'),
                                new OA\Property(property: 'total_angsuran', type: 'integer'),
                                new OA\Property(property: 'sisa_total_bayar', type: 'number', format: 'float'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Anggota not found'),
        ]
    )]
    public function stats(Request $request)
    {
        $anggota = $request->user()->anggota;
        
        if (!$anggota) {
            return response()->json([
                'message' => 'Anggota not found for user',
            ], 404);
        }

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

        $pinjamanAktif = $anggota->pinjamanAktif();

        $limitMaksimal = $totalSimpanan * 3;
        $cekEligibilitas = [
            'limit_tersedia' => $pinjamanAktif ? max(0, $limitMaksimal - ($pinjamanAktif->nominal ?? 0)) : $limitMaksimal,
            'sisa_angsuran' => $pinjamanAktif ? $pinjamanAktif->sisaCicilanAktif() : 0,
            'cicilan_pokok' => $pinjamanAktif ? $pinjamanAktif->cicilanPokokAktif() : 0,
            'boleh' => true,
            'alasan' => null,
        ];

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
        ]);
    }

    /**
     * Get actionable items
     */
    #[OA\Get(
        path: '/api/dashboard/actionable',
        summary: 'Get actionable items (for admin dashboard)',
        tags: ['Dashboard'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Actionable items',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items()),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function actionable(Request $request)
    {
        return response()->json([
            'message' => 'Actionable items endpoint - would show pending approvals for admins',
            'data' => []
        ]);
    }

    /**
     * Get chart data
     */
    #[OA\Get(
        path: '/api/dashboard/charts',
        summary: 'Get 6-month trend chart data',
        tags: ['Dashboard'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Chart data for trends',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'labelBulan', type: 'array', items: new OA\Items(type: 'string')),
                        new OA\Property(property: 'grafikTren', type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'bulan', type: 'string', example: 'Mar 2026'),
                                    new OA\Property(property: 'simpanan', type: 'number', format: 'float'),
                                    new OA\Property(property: 'pinjaman', type: 'number', format: 'float'),
                                ]
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Anggota not found'),
        ]
    )]
    public function charts(Request $request)
    {
        $anggota = $request->user()->anggota;
        
        if (!$anggota) {
            return response()->json([
                'message' => 'Anggota not found for user',
            ], 404);
        }

        $awalPeriode = Carbon::now()->subMonths(5)->startOfMonth();
        
        $simpananPerBulan = $anggota->simpanan()
            ->where('tanggal_input', '>=', $awalPeriode)
            ->selectRaw('YEAR(tanggal_input) as tahun, MONTH(tanggal_input) as bulan, SUM(jumlah) as total')
            ->groupBy('tahun', 'bulan')
            ->orderBy('tahun')
            ->orderBy('bulan')
            ->get()
            ->keyBy(fn ($r) => sprintf('%04d-%02d', $r->tahun, $r->bulan));

        $pinjamanPerBulan = $anggota->pinjaman()
            ->where('tanggal_pencairan', '>=', $awalPeriode)
            ->selectRaw('YEAR(tanggal_pencairan) as tahun, MONTH(tanggal_pencairan) as bulan, SUM(nominal) as total')
            ->groupBy('tahun', 'bulan')
            ->orderBy('tahun')
            ->orderBy('bulan')
            ->get()
            ->keyBy(fn ($r) => sprintf('%04d-%02d', $r->tahun, $r->bulan));

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
    #[OA\Get(
        path: '/api/dashboard/aktivitas',
        summary: 'Get recent activity (loan applications & payments)',
        tags: ['Dashboard'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Recent activity list',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'tipe', type: 'string', enum: ['pinjaman', 'angsuran']),
                            new OA\Property(property: 'nama', type: 'string'),
                            new OA\Property(property: 'keterangan', type: 'string'),
                            new OA\Property(property: 'status', type: 'string'),
                            new OA\Property(property: 'tanggal_format', type: 'string', example: '23 Aug 2026'),
                        ]
                    )
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Anggota not found'),
        ]
    )]
    public function aktivitas(Request $request)
    {
        $anggota = $request->user()->anggota;
        
        if (!$anggota) {
            return response()->json([
                'message' => 'Anggota not found for user',
            ], 404);
        }

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