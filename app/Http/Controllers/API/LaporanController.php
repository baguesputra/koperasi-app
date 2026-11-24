<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Laporan\LaporanRegistry;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LaporanController extends Controller
{
    private const SLUGS_MOBILE = [
        'arus-kas',
        'neraca',
        'pendapatan-bunga',
        'laba-rugi',
        'pinjaman-per-status',
        'perubahan-tenor',
        'simpanan-per-anggota',
        'setoran-bulanan',
        'anggota-daftar',
        'pengeluaran-rekap',
        'dana-sosial',
        'iuran-pinjaman-rekap',
    ];

    private function tolakBilaTanpaIzin(Request $request): void
    {
        if (! $request->user()->can('laporan.lihat')) {
            abort(response()->json([
                'message' => 'Anda tidak memiliki akses ke laporan.',
            ], 403));
        }
    }

    /**
     * Daftar 12 laporan resmi untuk tab pengurus mobile.
     *
     * @response 200 {"data": [{"slug": "pinjaman-per-status"}]}
     */
    public function index(Request $request): JsonResponse
    {
        $this->tolakBilaTanpaIzin($request);

        $data = collect(LaporanRegistry::semua())
            ->only(self::SLUGS_MOBILE)
            ->map(function ($def, $slug) {
                return [
                    'slug' => $slug,
                    'judul' => $def['judul'],
                    'deskripsi' => $def['deskripsi'],
                    'kategori' => $def['kategori'],
                    'ikon' => $def['ikon'],
                    'filter' => $def['filter']['tipe'] ?? 'rentang',
                ];
            })->values();

        return response()->json(['data' => $data]);
    }

    /**
     * Data tabular satu laporan untuk pratinjau di app & render PDF.
     */
    public function show(Request $request, string $slug): JsonResponse
    {
        $this->tolakBilaTanpaIzin($request);
        abort_unless(LaporanRegistry::ada($slug), response()->json(['message' => 'Laporan tidak ditemukan.'], 404));
        abort_unless(
            in_array($slug, self::SLUGS_MOBILE, true),
            response()->json(['message' => 'Laporan tidak tersedia di aplikasi mobile.'], 404)
        );

        $request->validate([
            'dari' => ['nullable', 'date_format:Y-m'],
            'sampai' => ['nullable', 'date_format:Y-m'],
            'tahun' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'bulan' => ['nullable', 'date_format:Y-m'],
            'tanggal' => ['nullable', 'date_format:Y-m-d'],
            'cabang' => ['nullable', 'string', 'max:100'],
            'kantong' => ['nullable', 'string', 'max:50'],
            'status_anggota' => ['nullable', 'in:aktif,resign'],
            'status_pinjaman' => ['nullable', 'in:diajukan,approved_admin,approved_bendahara,aktif,lunas,ditolak'],
            'semua_periode' => ['nullable', 'boolean'],
        ]);

        $this->normalisasiFilter($request, $slug);
        $def = LaporanRegistry::ambil($slug);
        $hasil = $def['data']($request);

        return response()->json([
            'judul' => $def['judul'],
            'periode' => $this->periodeLabel($request, $slug),
            'kolom' => $hasil['kolom'],
            'angka' => $hasil['rataKanan'],
            'baris' => $hasil['rows'],
            'total' => $hasil['totals'] ?? [],
            'ringkasan' => $hasil['ringkasan'] ?? [],
            'catatan' => $hasil['catatan'] ?? null,
            'gaya_baris' => $hasil['gayaBaris'] ?? [],
        ]);
    }

    /**
     * Unduh PDF langsung.
     */
    public function pdf(Request $request, string $slug)
    {
        $this->tolakBilaTanpaIzin($request);
        abort_unless(LaporanRegistry::ada($slug), response()->json(['message' => 'Laporan tidak ditemukan.'], 404));
        abort_unless(
            in_array($slug, self::SLUGS_MOBILE, true),
            response()->json(['message' => 'Laporan tidak tersedia di aplikasi mobile.'], 404)
        );

        $request->validate([
            'dari' => ['nullable', 'date_format:Y-m'],
            'sampai' => ['nullable', 'date_format:Y-m'],
            'tahun' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'bulan' => ['nullable', 'date_format:Y-m'],
            'tanggal' => ['nullable', 'date_format:Y-m-d'],
            'cabang' => ['nullable', 'string', 'max:100'],
            'kantong' => ['nullable', 'string', 'max:50'],
            'status_anggota' => ['nullable', 'in:aktif,resign'],
            'status_pinjaman' => ['nullable', 'in:diajukan,approved_admin,approved_bendahara,aktif,lunas,ditolak'],
            'semua_periode' => ['nullable', 'boolean'],
        ]);

        $this->normalisasiFilter($request, $slug);
        $def = LaporanRegistry::ambil($slug);
        $periodeLabel = $this->periodeLabel($request, $slug);
        $hasil = $def['data']($request);

        $namaFile = $slug.'-'.str_replace([' ', '/'], '-', strtolower($periodeLabel)).'.pdf';

        $view = isset($hasil['skontro']) ? 'laporan.neraca' : 'laporan.generik';
        $orientasi = (isset($hasil['skontro']) || count($hasil['kolom']) > 6) ? 'landscape' : 'portrait';

        return Pdf::loadView($view, [
            'judul' => $def['judul'],
            'periodeLabel' => $periodeLabel,
            'hasil' => $hasil,
        ])->setPaper('a4', $orientasi)
            ->download($namaFile);
    }

    /**
     * Normalkan semua 5 tipe filter mobile (rentang|tahun|bulan|tanggal|tanpa_periode) ke bentuk Registry.
     */
    private function normalisasiFilter(Request $request, string $slug): void
    {
        $def = LaporanRegistry::ambil($slug);
        $default = collect($def['periodeDefault']())->values();

        $tipe = $def['filter']['tipe'] ?? 'rentang';
        if ($tipe === 'tahun') {
            $tahun = (int) ($request->input('tahun') ?? $default->get(0, now()->format('Y')));
            $request->merge(['tahun' => $tahun, 'dari' => $tahun.'-01', 'sampai' => $tahun.'-12']);
        } elseif ($tipe === 'bulan') {
            $bulan = (string) ($request->input('bulan') ?? $default->get(0, now()->format('Y-m')));
            $request->merge(['bulan' => $bulan, 'dari' => $bulan, 'sampai' => $bulan]);
        } elseif ($tipe === 'tanggal') {
            $tanggal = (string) ($request->input('tanggal') ?? $default->get(0, now()->format('Y-m-d')));
            $request->merge(['tanggal' => $tanggal]);
        } elseif ($tipe === 'tanpa_periode') {
            $request->merge(['dari' => null, 'sampai' => null]);
        } else {
            $request->merge([
                'dari' => $request->input('dari', $default->get(0)),
                'sampai' => $request->input('sampai', $default->get(1, $default->get(0))),
            ]);
        }
    }

    private function periodeLabel(Request $request, string $slug): string
    {
        $def = LaporanRegistry::ambil($slug);
        $ekstraNama = collect($def['filter']['ekstra'] ?? [])->map(fn ($e) => is_array($e) ? $e['nama'] : $e)->all();
        if ($request->boolean('semua_periode') && in_array('semua_periode', $ekstraNama, true)) {
            return 'Semua Periode';
        }

        $tipe = $def['filter']['tipe'] ?? 'rentang';

        if ($tipe === 'tahun') {
            return 'Tahun '.$request->input('tahun');
        }
        if ($tipe === 'bulan') {
            $bulan = $request->input('bulan');

            return $bulan ? Carbon::parse($bulan.'-01')->locale('id')->translatedFormat('F Y') : 'Bulan Berjalan';
        }
        if ($tipe === 'tanggal') {
            $tanggal = $request->input('tanggal');

            return $tanggal ? Carbon::parse($tanggal)->locale('id')->translatedFormat('d F Y') : 'Hari Ini';
        }
        if ($tipe === 'tanpa_periode') {
            return 'Semua Periode';
        }

        $dari = $request->input('dari');
        $sampai = $request->input('sampai');

        if ($dari && $sampai && $dari !== $sampai) {
            return Carbon::parse($dari.'-01')->locale('id')->translatedFormat('M Y').' s.d. '.Carbon::parse($sampai.'-01')->locale('id')->translatedFormat('M Y');
        }

        return $dari ? Carbon::parse($dari.'-01')->locale('id')->translatedFormat('F Y') : 'Semua Periode';
    }
}
