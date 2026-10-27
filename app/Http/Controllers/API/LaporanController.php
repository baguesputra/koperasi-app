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
    private function tolakBilaTanpaIzin(Request $request): void
    {
        if (! $request->user()->can('laporan.lihat')) {
            abort(response()->json([
                'message' => 'Anda tidak memiliki akses ke laporan.',
            ], 403));
        }
    }

    /**
     * Daftar laporan untuk tab pengurus mobile (2 laporan tabular siap unduh).
     * Mirror string filter LaporanRegistry ke enum kontrak mobile (rentang|tahun).
     *
     * @response 200 {"data": [{"slug": "pinjaman-per-status"}]}
     */
    public function index(Request $request): JsonResponse
    {
        $this->tolakBilaTanpaIzin($request);

        $data = collect(LaporanRegistry::semua())
            ->only(['pinjaman-per-status', 'setoran-bulanan'])
            ->map(function ($def, $slug) {
                return [
                    'slug' => $slug,
                    'judul' => $def['judul'],
                    'deskripsi' => $def['deskripsi'],
                    'kategori' => $def['kategori'],
                    'ikon' => $def['ikon'],
                    'filter' => $def['filter']['tipe'] === 'tahun' ? 'tahun' : 'rentang',
                ];
            })->values();

        return response()->json(['data' => $data]);
    }

    /**
     * Data tabular satu laporan untuk render PDF di perangkat.
     * Mirror web LaporanController@show + normalisasi filter mobile.
     *
     * @response 200 {"judul": "Rekap Pinjaman per Status", "periode": "2026-01", "kolom": [], "angka": [], "baris": [], "total": []}
     */
    public function show(Request $request, string $slug): JsonResponse
    {
        $this->tolakBilaTanpaIzin($request);
        abort_unless(LaporanRegistry::ada($slug), response()->json(['message' => 'Laporan tidak ditemukan.'], 404));
        abort_unless(
            in_array($slug, ['pinjaman-per-status', 'setoran-bulanan'], true),
            response()->json(['message' => 'Laporan tidak tersedia di aplikasi mobile.'], 404)
        );

        $request->validate([
            'dari' => ['nullable', 'date_format:Y-m'],
            'sampai' => ['nullable', 'date_format:Y-m'],
            'tahun' => ['nullable', 'integer', 'min:2000', 'max:2100'],
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
        ]);
    }

    /**
     * Unduh PDF langsung (dipakai tombol Unduh saat USE_MOCK=false).
     * Mirror web LaporanController@pdf, dibatasi 2 laporan mobile.
     */
    public function pdf(Request $request, string $slug)
    {
        $this->tolakBilaTanpaIzin($request);
        abort_unless(LaporanRegistry::ada($slug), response()->json(['message' => 'Laporan tidak ditemukan.'], 404));
        abort_unless(
            in_array($slug, ['pinjaman-per-status', 'setoran-bulanan'], true),
            response()->json(['message' => 'Laporan tidak tersedia di aplikasi mobile.'], 404)
        );

        $request->validate([
            'dari' => ['nullable', 'date_format:Y-m'],
            'sampai' => ['nullable', 'date_format:Y-m'],
            'tahun' => ['nullable', 'integer', 'min:2000', 'max:2100'],
        ]);

        $this->normalisasiFilter($request, $slug);
        $def = LaporanRegistry::ambil($slug);
        $periodeLabel = $this->periodeLabel($request, $slug);
        $hasil = $def['data']($request);

        $namaFile = $slug.'-'.str_replace([' ', '/'], '-', strtolower($periodeLabel)).'.pdf';

        return Pdf::loadView('laporan.generik', [
            'judul' => $def['judul'],
            'periodeLabel' => $periodeLabel,
            'hasil' => $hasil,
        ])->setPaper('a4', count($hasil['kolom']) > 6 ? 'landscape' : 'portrait')
            ->download($namaFile);
    }

    /**
     * Normalkan filter mobile ke bentuk Registry: rentang pakai dari/sampai (Y-m),
     * tahun pakai tahun (+ dari/sampai setahun penuh). Default = periodeDefault Registry.
     */
    private function normalisasiFilter(Request $request, string $slug): void
    {
        $def = LaporanRegistry::ambil($slug);
        $default = collect($def['periodeDefault']())->values();

        $tipe = $def['filter']['tipe'] ?? 'rentang';
        if ($tipe === 'tahun') {
            $tahun = (int) ($request->input('tahun') ?? $default->get(0, now()->format('Y')));
            $request->merge(['tahun' => $tahun, 'dari' => $tahun.'-01', 'sampai' => $tahun.'-12']);
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

        $tipe = $def['filter']['tipe'] ?? 'rentang';
        if ($tipe === 'tahun') {
            return 'Tahun '.$request->input('tahun');
        }

        $dari = $request->input('dari');
        $sampai = $request->input('sampai');

        if ($dari && $sampai && $dari !== $sampai) {
            return Carbon::parse($dari.'-01')->translatedFormat('M Y').' s.d. '.Carbon::parse($sampai.'-01')->translatedFormat('M Y');
        }

        return $dari ? Carbon::parse($dari.'-01')->translatedFormat('F Y') : 'Semua Periode';
    }
}
