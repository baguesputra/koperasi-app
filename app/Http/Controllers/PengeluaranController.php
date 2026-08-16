<?php

namespace App\Http\Controllers;

use App\Models\Pengeluaran;
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

        $pengeluaran = Pengeluaran::with('inputOleh')
            ->where('jenis', $jenis)
            ->latest('tanggal')
            ->latest('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($p) => [
                'id' => $p->id,
                'jumlah' => (float) $p->jumlah,
                'keterangan' => $p->keterangan,
                'tanggal' => $p->tanggal->format('d M Y'),
                'input_oleh' => $p->inputOleh->name,
            ]);

        $totalKoperasi = Pengeluaran::where('jenis', 'koperasi')->sum('jumlah');
        $totalDanaSosial = Pengeluaran::where('jenis', 'dana_sosial')->sum('jumlah');

        return Inertia::render('Pengeluaran/Index', [
            'pengeluaran' => $pengeluaran,
            'jenisAktif' => $jenis,
            'totalKoperasi' => (float) $totalKoperasi,
            'totalDanaSosial' => (float) $totalDanaSosial,
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