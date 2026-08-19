<?php

namespace App\Http\Controllers;

use App\Models\Pinjaman;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PinjamanController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Pinjaman::with('anggota');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('cari')) {
            $cari = $request->string('cari');
            $query->whereHas('anggota', fn ($q) => $q->where('nama', 'like', "%{$cari}%"));
        }

        $pinjaman = $query->latest('tanggal_pengajuan')
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($p) => [
                'id' => $p->id,
                'nama' => $p->anggota->nama,
                'no_anggota' => $p->anggota->no_anggota,
                'nominal' => (float) $p->nominal,
                'tenor_bulan' => $p->tenor_bulan,
                'status' => $p->status,
                'tanggal_pengajuan' => $p->tanggal_pengajuan->format('d M Y'),
            ]);

        return Inertia::render('Pinjaman/Index', [
            'pinjaman' => $pinjaman,
            'filters' => $request->only(['cari', 'status']),
            'statistik' => [
                'total' => Pinjaman::count(),
                'diajukan' => Pinjaman::where('status', 'diajukan')->count(),
                'approved_bendahara' => Pinjaman::where('status', 'approved_bendahara')->count(),
                'aktif' => Pinjaman::where('status', 'aktif')->count(),
                'lunas' => Pinjaman::where('status', 'lunas')->count(),
                'ditolak' => Pinjaman::where('status', 'ditolak')->count(),
            ],
        ]);
    }
}
