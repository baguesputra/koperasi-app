<?php

namespace App\Http\Controllers;

use App\Models\Pengeluaran;
use App\Models\SettingKas;
use App\Services\Keuangan\PengeluaranService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class PengeluaranController extends Controller
{
    public function __construct(private PengeluaranService $service) {}

    public function index(Request $request): Response
    {
        $jenis = $request->input('jenis', 'koperasi');
        $bulan = $request->string('bulan');

        $query = Pengeluaran::with('inputOleh')->where('jenis', $jenis);

        if ($request->filled('cari')) {
            $cari = $request->string('cari');
            $query->where(function ($q) use ($cari) {
                $q->where('keterangan', 'like', "%{$cari}%")
                    ->orWhereHas('inputOleh', fn ($r) => $r->where('name', 'like', "%{$cari}%"));
            });
        }

        if ($bulan->isNotEmpty() && preg_match('/^\d{4}-\d{2}$/', $bulan->value())) {
            $query->whereYear('tanggal', substr($bulan->value(), 0, 4))
                ->whereMonth('tanggal', substr($bulan->value(), 5, 2));
        }

        $totalTampil = (clone $query)->sum('jumlah');

        $pengeluaran = $query
            ->latest('tanggal')
            ->latest('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($p) => [
                'id' => $p->id,
                'jumlah' => (float) $p->jumlah,
                'keterangan' => $p->keterangan,
                'tanggal' => $p->tanggal->format('d M Y'),
                'hari' => $p->tanggal->translatedFormat('l'),
                'input_oleh' => $p->inputOleh->name,
            ]);

        $totalKoperasi = Pengeluaran::where('jenis', 'koperasi')->sum('jumlah');
        $totalDanaSosial = Pengeluaran::where('jenis', 'dana_sosial')->sum('jumlah');

        $bulanAktif = preg_match('/^\d{4}-\d{2}$/', $bulan->value()) ? $bulan->value() : now()->format('Y-m');
        [$tahunAktif, $bulanAngka] = explode('-', $bulanAktif);

        $perBulan = fn (string $jenis) => (float) Pengeluaran::where('jenis', $jenis)
            ->whereYear('tanggal', $tahunAktif)
            ->whereMonth('tanggal', $bulanAngka)
            ->sum('jumlah');

        $totalKoperasiBulan = $perBulan('koperasi');
        $totalDanaSosialBulan = $perBulan('dana_sosial');

        $paguSosial = SettingKas::nilai(
            SettingKas::CADANGAN,
            (float) config('koperasi.cadangan_sosial_bulan', 5_000_000)
        );

        return Inertia::render('Pengeluaran/Index', [
            'pengeluaran' => $pengeluaran,
            'jenisAktif' => $jenis,
            'filters' => $request->only(['cari', 'bulan']),
            'totalKoperasi' => (float) $totalKoperasi,
            'totalDanaSosial' => (float) $totalDanaSosial,
            'totalTampil' => (float) $totalTampil,
            'bulanAktif' => $bulanAktif,
            'totalKoperasiBulan' => $totalKoperasiBulan,
            'totalDanaSosialBulan' => $totalDanaSosialBulan,
            'infoPaguSosial' => [
                'bulan' => $bulanAktif,
                'pagu' => $paguSosial,
                'terpakai' => $totalDanaSosialBulan,
                'sisa' => max(0, $paguSosial - $totalDanaSosialBulan),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'jenis' => ['required', 'in:koperasi,dana_sosial'],
            'jumlah' => ['required', 'numeric', 'min:1'],
            'keterangan' => ['required', 'string', 'max:500'],
            'tanggal' => ['required', 'date'],
        ]);

        try {
            $this->service->catat(
                $request->jenis,
                (float) $request->jumlah,
                $request->keterangan,
                $request->tanggal,
                auth()->id()
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['jumlah' => $e->getMessage()]);
        }

        return back()->with('status', 'Pengeluaran berhasil dicatat.');
    }
}
