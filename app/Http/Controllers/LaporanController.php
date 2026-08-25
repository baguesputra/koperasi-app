<?php

namespace App\Http\Controllers;

use App\Exports\ArrayExport;
use App\Laporan\LaporanRegistry;
use App\Models\Anggota;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class LaporanController extends Controller
{
    public function index(): Response
    {
        $bolehAudit = Gate::allows('pengaturan.kelola');

        $kelompok = collect(LaporanRegistry::kelompok())
            ->map(fn ($items) => collect($items)
                ->filter(fn ($l) => $bolehAudit || ! ($l['admin_only'] ?? false))
                ->values()
                ->all())
            ->filter(fn ($items) => count($items) > 0)
            ->all();

        return Inertia::render('Laporan/Index', [
            'kelompok' => $kelompok,
        ]);
    }

    public function show(Request $request, string $jenis): Response
    {
        $def = $this->validasi($jenis);
        [$filter, $periodeLabel] = $this->siapkanFilter($request, $def);

        return Inertia::render('Laporan/Show', [
            'laporan' => ['slug' => $jenis, ...collect($def)->except(['data', 'filter', 'periodeDefault'])->all()],
            'filter' => $filter,
            'opsi' => $this->opsi($def),
            'periodeLabel' => $periodeLabel,
            'hasil' => $def['data']($request),
        ]);
    }

    public function pdf(Request $request, string $jenis)
    {
        $def = $this->validasi($jenis);
        [$filter, $periodeLabel] = $this->siapkanFilter($request, $def);
        $hasil = $def['data']($request);

        return Pdf::loadView('laporan.generik', [
            'judul' => $def['judul'],
            'periodeLabel' => $periodeLabel,
            'hasil' => $hasil,
        ])
            ->setPaper('a4', count($hasil['kolom']) > 6 ? 'landscape' : 'portrait')
            ->download($this->namaFile($def['judul'], $periodeLabel).'.pdf');
    }

    public function export(Request $request, string $jenis)
    {
        $def = $this->validasi($jenis);
        [, $periodeLabel] = $this->siapkanFilter($request, $def);
        $hasil = $def['data']($request);

        $baris = [
            [$def['judul']],
            ["Periode: {$periodeLabel}"],
            [],
            $hasil['kolom'],
            ...$hasil['rows'],
        ];
        if ($hasil['totals']) {
            $baris[] = array_map(fn ($v) => is_string($v) ? $v : (string) $v, $hasil['totals']);
        }

        return Excel::download(new ArrayExport($baris), $this->namaFile($def['judul'], $periodeLabel).'.xlsx');
    }

    private function validasi(string $jenis): array
    {
        abort_unless(LaporanRegistry::ada($jenis), 404);

        $def = LaporanRegistry::ambil($jenis);

        abort_unless(
            ! ($def['admin_only'] ?? false) || Gate::allows('pengaturan.kelola'),
            403,
            'Laporan ini hanya tersedia untuk Admin.'
        );

        return $def;
    }

    /** Normalkan semua tipe filter periode menjadi input `dari`/`sampai` (Y-m) + nilai filter untuk frontend. */
    private function siapkanFilter(Request $request, array $def): array
    {
        $tipe = $def['filter']['tipe'];
        $default = collect($def['periodeDefault']())->values();

        switch ($tipe) {
            case 'bulan':
                $b = $request->input('bulan', $default->get(0));
                $filter = ['bulan' => $b];
                $request->merge(['dari' => $b, 'sampai' => $b]);
                $periodeLabel = Carbon::parse($b.'-01')->translatedFormat('F Y');
                break;

            case 'tahun':
                $y = (int) ($request->input('tahun') ?? $default->get(0, now()->format('Y')));
                $filter = ['tahun' => $y];
                $request->merge(['dari' => "{$y}-01", 'sampai' => "{$y}-12"]);
                $periodeLabel = "Tahun {$y}";
                break;

            case 'tanggal':
                $d = $request->input('tanggal', $default->get(0, now()->format('Y-m-d')));
                $filter = ['tanggal' => $d];
                $periodeLabel = Carbon::parse($d)->translatedFormat('d F Y');
                break;

            default: // rentang / tanpa_periode
                $dari = $default->get(0);
                $sampai = $default->get(1, $default->get(0));
                $filter = [];
                if ($tipe === 'rentang') {
                    $dari = $request->input('dari', $dari);
                    $sampai = $request->input('sampai', $sampai);
                    $filter = ['dari' => $dari, 'sampai' => $sampai];
                    $labelDari = $dari ? Carbon::parse($dari.'-01')->translatedFormat('M Y') : null;
                    $labelSampai = $sampai ? Carbon::parse($sampai.'-01')->translatedFormat('M Y') : null;
                    $periodeLabel = $labelSampai && $labelSampai !== $labelDari
                        ? "{$labelDari} – {$labelSampai}"
                        : ($labelDari ?? 'Semua Periode');
                } else {
                    $periodeLabel = 'Semua Periode';
                }
                $request->merge(['dari' => $dari, 'sampai' => $sampai]);
                break;
        }

        foreach ($def['filter']['ekstra'] ?? [] as $ekstra) {
            $nama = is_array($ekstra) ? $ekstra['nama'] : $ekstra;
            $filter[$nama] = $request->input($nama, is_array($ekstra) ? ($ekstra['default'] ?? '') : '');
        }

        return [$filter, $periodeLabel];
    }

    /** Opsi select filter tambahan. */
    private function opsi(array $def): array
    {
        $perluCabang = false;
        foreach ($def['filter']['ekstra'] ?? [] as $e) {
            if ((is_array($e) ? $e['nama'] : $e) === 'cabang') {
                $perluCabang = true;
            }
        }

        return [
            'cabang' => $perluCabang
                ? Anggota::query()->whereNotNull('cabang')->distinct()->orderBy('cabang')->pluck('cabang')->all()
                : null,
        ];
    }

    private function namaFile(string $judul, string $periodeLabel): string
    {
        $nama = strtolower(trim(preg_replace('/^Laporan\s+/i', '', $judul)).' '.$periodeLabel);

        return str_replace([' ', '/', ','], '-', preg_replace('/[^a-z0-9\s\/,]/u', '', $nama));
    }
}
