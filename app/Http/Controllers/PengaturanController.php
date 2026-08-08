<?php

namespace App\Http\Controllers;

use App\Models\SettingBunga;
use App\Models\SettingLimitPinjaman;
use App\Models\TabelTenor;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PengaturanController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Pengaturan/Index', [
            'limitPinjaman' => SettingLimitPinjaman::orderBy('id')->get(),
            'tabelTenor' => TabelTenor::orderBy('nominal_min')->get(),
            'bungaSaatIni' => SettingBunga::orderByDesc('berlaku_dari_tanggal')->first(),
        ]);
    }

    public function updateLimit(Request $request, SettingLimitPinjaman $limit)
    {
        $request->validate([
            'limit_maksimal' => ['required', 'numeric', 'min:0'],
        ]);

        $limit->update(['limit_maksimal' => $request->limit_maksimal]);

        return back()->with('status', 'Limit pinjaman berhasil diperbarui.');
    }

    public function storeTenor(Request $request)
    {
        $request->validate([
            'nominal_min' => ['required', 'numeric', 'min:0'],
            'nominal_max' => ['required', 'numeric', 'gt:nominal_min'],
            'tenor_maksimal_bulan' => ['required', 'integer', 'min:1'],
        ]);

        TabelTenor::create($request->only('nominal_min', 'nominal_max', 'tenor_maksimal_bulan'));

        return back()->with('status', 'Rentang tenor berhasil ditambahkan.');
    }

    public function updateTenor(Request $request, TabelTenor $tenor)
    {
        $request->validate([
            'nominal_min' => ['required', 'numeric', 'min:0'],
            'nominal_max' => ['required', 'numeric', 'gt:nominal_min'],
            'tenor_maksimal_bulan' => ['required', 'integer', 'min:1'],
        ]);

        $tenor->update($request->only('nominal_min', 'nominal_max', 'tenor_maksimal_bulan'));

        return back()->with('status', 'Rentang tenor berhasil diperbarui.');
    }

    public function destroyTenor(TabelTenor $tenor)
    {
        $tenor->delete();

        return back()->with('status', 'Rentang tenor berhasil dihapus.');
    }

    public function updateBunga(Request $request)
    {
        $request->validate([
            'persentase' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        SettingBunga::create([
            'persentase' => $request->persentase,
            'berlaku_dari_tanggal' => now(),
        ]);

        return back()->with('status', 'Persentase bunga berhasil diperbarui. Berlaku untuk pengajuan baru mulai sekarang.');
    }
}