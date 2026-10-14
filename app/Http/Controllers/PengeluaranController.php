<?php

namespace App\Http\Controllers;

use App\Models\Pengeluaran;
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
        $bulan = $request->string('bulan');
        $tipe = $request->string('tipe');

        $query = Pengeluaran::with('inputOleh');

        if ($tipe->isNotEmpty() && in_array($tipe->value(), Pengeluaran::TIPE, true)) {
            $query->where('tipe', $tipe->value());
        }

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
                'tipe' => $p->tipe,
                'tipe_label' => Pengeluaran::TIPE_LABEL[$p->tipe] ?? $p->tipe,
                'akun' => $p->akun,
                'akun_label' => Pengeluaran::AKUN_LABEL[$p->akun] ?? $p->akun,
                'jumlah' => (float) $p->jumlah,
                'keterangan' => $p->keterangan,
                'tanggal' => $p->tanggal->format('d M Y'),
                'hari' => $p->tanggal->translatedFormat('l'),
                'input_oleh' => $p->inputOleh->name,
            ]);

        $bulanAktif = preg_match('/^\d{4}-\d{2}$/', $bulan->value()) ? $bulan->value() : now()->format('Y-m');
        $awalAktif = Carbon::createFromFormat('Y-m', $bulanAktif)->startOfMonth()->startOfDay()->toDateTimeString();
        $akhirAktif = Carbon::createFromFormat('Y-m', $bulanAktif)->endOfMonth()->endOfDay()->toDateTimeString();

        $totalBulan = (float) Pengeluaran::whereBetween('tanggal', [$awalAktif, $akhirAktif])->sum('jumlah');

        return Inertia::render('Pengeluaran/Index', [
            'pengeluaran' => $pengeluaran,
            'filters' => $request->only(['cari', 'bulan', 'tipe']),
            'tipeList' => Pengeluaran::TIPE_LABEL,
            'akunList' => Pengeluaran::AKUN_LABEL,
            'totalTampil' => (float) $totalTampil,
            'bulanAktif' => $bulanAktif,
            'totalBulan' => $totalBulan,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'tipe' => ['required', 'in:pajak,santunan,lain_lain,perlengkapan,perijinan,bank'],
            'akun' => ['required', 'in:bank,kas_kecil'],
            'jumlah' => ['required', 'numeric', 'min:1'],
            'keterangan' => ['required', 'string', 'max:500'],
            'tanggal' => ['required', 'date'],
        ]);

        try {
            // Jenis dihapus dari tampilan: semua record baru = koperasi
            // (jurnal pengeluaran_koperasi, terhitung beban SHU).
            $this->service->catat(
                'koperasi',
                (float) $request->jumlah,
                $request->keterangan,
                $request->tanggal,
                auth()->id(),
                $request->tipe,
                $request->akun
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['jumlah' => $e->getMessage()]);
        }

        return back()->with('status', 'Pengeluaran berhasil dicatat.');
    }
}
