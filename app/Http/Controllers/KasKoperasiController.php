<?php

namespace App\Http\Controllers;

use App\Models\JurnalKas;
use App\Models\KasKoperasi;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KasKoperasiController extends Controller
{
    public function index(): Response
    {
        $kas = KasKoperasi::firstOrFail();

        $riwayat = JurnalKas::latest('tanggal')
            ->latest('id')
            ->take(30)
            ->get()
            ->map(fn ($j) => [
                'id' => $j->id,
                'tipe' => $j->tipe,
                'kategori' => $j->kategori,
                'jumlah' => (float) $j->jumlah,
                'keterangan' => $j->keterangan,
                'tanggal' => $j->tanggal->format('d M Y'),
            ]);

        return Inertia::render('KasKoperasi/Index', [
            'saldoSaatIni' => (float) $kas->saldo_saat_ini,
            'riwayat' => $riwayat,
        ]);
    }

    public function topup(Request $request)
    {
        $request->validate([
            'jumlah' => ['required', 'numeric', 'min:1'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        $kas = KasKoperasi::firstOrFail();
        $kas->increment('saldo_saat_ini', $request->jumlah);

        JurnalKas::create([
            'tipe' => 'masuk',
            'kategori' => 'topup_bulanan',
            'jumlah' => $request->jumlah,
            'keterangan' => $request->keterangan ?: 'Topup saldo koperasi',
            'tanggal' => now(),
            'created_by' => auth()->id(),
        ]);

        return back()->with('status', 'Saldo kas koperasi berhasil ditambahkan.');
    }
}