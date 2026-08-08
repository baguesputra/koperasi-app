<?php

namespace App\Http\Controllers;

use App\Models\Anggota;
use App\Models\Simpanan;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SimpananController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Anggota::query()->withSum('simpanan as total_simpanan', 'jumlah');

        // Total simpanan seharusnya TIDAK termasuk dana sosial - hitung terpisah
        $query = Anggota::query()
            ->withSum(['simpanan as total_pokok_wajib' => fn ($q) => $q->whereIn('jenis', ['pokok', 'wajib'])], 'jumlah');

        if ($request->filled('cari')) {
            $cari = $request->string('cari');
            $query->where('nama', 'like', "%{$cari}%");
        }

        $anggota = $query->orderBy('nama')
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($a) => [
                'id' => $a->id,
                'nama' => $a->nama,
                'no_anggota' => $a->no_anggota,
                'cabang' => $a->cabang,
                'total_simpanan' => (float) ($a->total_pokok_wajib ?? 0),
            ]);

        $totalDanaSosialTerkumpul = Simpanan::where('jenis', 'dana_sosial')->sum('jumlah');
        $totalSimpananSeluruhAnggota = Simpanan::whereIn('jenis', ['pokok', 'wajib'])->sum('jumlah');

        return Inertia::render('Simpanan/Index', [
            'anggota' => $anggota,
            'filters' => $request->only('cari'),
            'totalDanaSosialTerkumpul' => (float) $totalDanaSosialTerkumpul,
            'totalSimpananSeluruhAnggota' => (float) $totalSimpananSeluruhAnggota,
        ]);
    }

    public function show(Anggota $anggota): Response
    {
        $riwayat = $anggota->simpanan()
            ->latest('tanggal_input')
            ->get()
            ->map(fn ($s) => [
                'jenis' => $s->jenis,
                'jumlah' => (float) $s->jumlah,
                'bulan_periode' => $s->bulan_periode,
                'tanggal_input' => $s->tanggal_input->format('d M Y'),
            ]);

        $totalSimpanan = $anggota->simpanan()->whereIn('jenis', ['pokok', 'wajib'])->sum('jumlah');

        return Inertia::render('Simpanan/Show', [
            'anggota' => ['nama' => $anggota->nama, 'no_anggota' => $anggota->no_anggota],
            'riwayat' => $riwayat,
            'totalSimpanan' => (float) $totalSimpanan,
        ]);
    }
}