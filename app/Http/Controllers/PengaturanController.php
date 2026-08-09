<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\SettingBunga;
use App\Models\SettingLimitPinjaman;
use App\Models\SettingSimpanan;
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
            'settingSimpanan' => SettingSimpanan::orderBy('id')->get(),
        ]);
    }

    public function updateLimit(Request $request, SettingLimitPinjaman $limit)
    {
        $request->validate(['limit_maksimal' => ['required', 'numeric', 'min:0']]);

        $nilaiLama = $limit->limit_maksimal;
        $limit->update(['limit_maksimal' => $request->limit_maksimal]);

        AuditLog::catat(
            'update_limit_pinjaman',
            "Limit '{$limit->label}' diubah dari Rp " . number_format($nilaiLama, 0, ',', '.') . " menjadi Rp " . number_format($request->limit_maksimal, 0, ',', '.'),
            ['limit_maksimal' => $nilaiLama],
            ['limit_maksimal' => $request->limit_maksimal]
        );

        return back()->with('status', 'Limit pinjaman berhasil diperbarui.');
    }

    public function storeTenor(Request $request)
    {
        $request->validate([
            'nominal_min' => ['required', 'numeric', 'min:0'],
            'nominal_max' => ['required', 'numeric', 'gt:nominal_min'],
            'tenor_maksimal_bulan' => ['required', 'integer', 'min:1'],
        ]);

        $tenor = TabelTenor::create($request->only('nominal_min', 'nominal_max', 'tenor_maksimal_bulan'));

        AuditLog::catat(
            'tambah_tenor',
            "Rentang tenor baru ditambahkan: Rp " . number_format($tenor->nominal_min, 0, ',', '.') . " - Rp " . number_format($tenor->nominal_max, 0, ',', '.') . " ({$tenor->tenor_maksimal_bulan} bulan)",
            null,
            $tenor->toArray()
        );

        return back()->with('status', 'Rentang tenor berhasil ditambahkan.');
    }

    public function updateTenor(Request $request, TabelTenor $tenor)
    {
        $request->validate([
            'nominal_min' => ['required', 'numeric', 'min:0'],
            'nominal_max' => ['required', 'numeric', 'gt:nominal_min'],
            'tenor_maksimal_bulan' => ['required', 'integer', 'min:1'],
        ]);

        $dataLama = $tenor->toArray();
        $tenor->update($request->only('nominal_min', 'nominal_max', 'tenor_maksimal_bulan'));

        AuditLog::catat('update_tenor', 'Rentang tenor diperbarui.', $dataLama, $tenor->fresh()->toArray());

        return back()->with('status', 'Rentang tenor berhasil diperbarui.');
    }

    public function destroyTenor(TabelTenor $tenor)
    {
        AuditLog::catat('hapus_tenor', "Rentang tenor dihapus: Rp " . number_format($tenor->nominal_min, 0, ',', '.') . " - Rp " . number_format($tenor->nominal_max, 0, ',', '.'), $tenor->toArray(), null);

        $tenor->delete();

        return back()->with('status', 'Rentang tenor berhasil dihapus.');
    }

    public function updateBunga(Request $request)
    {
        $request->validate(['persentase' => ['required', 'numeric', 'min:0', 'max:100']]);

        $bungaLama = SettingBunga::orderByDesc('berlaku_dari_tanggal')->first();

        SettingBunga::create([
            'persentase' => $request->persentase,
            'berlaku_dari_tanggal' => now(),
        ]);

        AuditLog::catat(
            'update_bunga',
            "Persentase bunga diubah dari {$bungaLama?->persentase}% menjadi {$request->persentase}%",
            ['persentase' => $bungaLama?->persentase],
            ['persentase' => $request->persentase]
        );

        return back()->with('status', 'Persentase bunga berhasil diperbarui. Berlaku untuk pengajuan baru mulai sekarang.');
    }

    public function updateSimpanan(Request $request, SettingSimpanan $setting)
    {
        $request->validate(['nominal' => ['required', 'numeric', 'min:0']]);

        $nilaiLama = $setting->nominal;
        $setting->update(['nominal' => $request->nominal]);

        AuditLog::catat(
            'update_setting_simpanan',
            "Nominal '{$setting->label}' diubah dari Rp " . number_format($nilaiLama, 0, ',', '.') . " menjadi Rp " . number_format($request->nominal, 0, ',', '.'),
            ['nominal' => $nilaiLama],
            ['nominal' => $request->nominal]
        );

        return back()->with('status', 'Nominal simpanan berhasil diperbarui.');
    }
}