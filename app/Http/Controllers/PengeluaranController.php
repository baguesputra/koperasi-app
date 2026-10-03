<?php

namespace App\Http\Controllers;

use App\Models\Pengeluaran;
use App\Models\SettingKas;
use App\Services\Keuangan\PengeluaranService;
use Carbon\Carbon;
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
            $awal = Carbon::createFromFormat('Y-m', $bulan->value())->startOfMonth()->startOfDay()->toDateTimeString();
            $akhir = Carbon::createFromFormat('Y-m', $bulan->value())->endOfMonth()->endOfDay()->toDateTimeString();
            $query->whereBetween('tanggal', [$awal, $akhir]);
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

        $agregatJenis = Pengeluaran::selectRaw('jenis, SUM(jumlah) as total')->groupBy('jenis')->pluck('total', 'jenis');
        $totalKoperasi = (float) ($agregatJenis['koperasi'] ?? 0);
        $totalDanaSosial = (float) ($agregatJenis['dana_sosial'] ?? 0);

        $bulanAktif = preg_match('/^\d{4}-\d{2}$/', $bulan->value()) ? $bulan->value() : now()->format('Y-m');
        $awalAktif = Carbon::createFromFormat('Y-m', $bulanAktif)->startOfMonth()->startOfDay()->toDateTimeString();
        $akhirAktif = Carbon::createFromFormat('Y-m', $bulanAktif)->endOfMonth()->endOfDay()->toDateTimeString();

        $perBulanAgg = Pengeluaran::whereBetween('tanggal', [$awalAktif, $akhirAktif])
            ->selectRaw('jenis, SUM(jumlah) as total')
            ->groupBy('jenis')
            ->pluck('total', 'jenis');

        $totalKoperasiBulan = (float) ($perBulanAgg['koperasi'] ?? 0);
        $totalDanaSosialBulan = (float) ($perBulanAgg['dana_sosial'] ?? 0);

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
