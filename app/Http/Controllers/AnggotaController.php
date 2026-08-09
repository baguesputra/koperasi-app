<?php

namespace App\Http\Controllers;

use App\Models\Anggota;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Http\Requests\StoreAnggotaRequest;
use App\Http\Requests\UpdateAnggotaRequest;
use App\Models\AuditLog;

class AnggotaController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Anggota::query();

        if ($request->filled('cari')) {
            $cari = $request->string('cari');
            $query->where(function ($q) use ($cari) {
                $q->where('nama', 'like', "%{$cari}%")
                  ->orWhere('no_anggota', 'like', "%{$cari}%");
            });
        }

        if ($request->filled('cabang')) {
            $query->where('cabang', $request->string('cabang'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        $anggota = $query->orderBy('nama')
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($a) => [
                'id' => $a->id,
                'no_anggota' => $a->no_anggota,
                'nama' => $a->nama,
                'cabang' => $a->cabang,
                'jabatan' => $a->jabatan,
                'status' => $a->status,
                'lama_keanggotaan_tahun' => round($a->lama_keanggotaan_tahun, 1),
            ]);

        return Inertia::render('Anggota/Index', [
            'anggota' => $anggota,
            'filters' => $request->only(['cari', 'cabang', 'status']),
            'daftarCabang' => ['Banjarmasin', 'Samarinda', 'Palangka'],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Anggota/Create', [
            'noAnggotaBerikutnya' => Anggota::generateNoAnggota(),
            'daftarCabang' => ['Banjarmasin', 'Samarinda', 'Palangka'],
        ]);
    }

    public function store(StoreAnggotaRequest $request)
    {
        Anggota::create([
            ...$request->validated(),
            'no_anggota' => Anggota::generateNoAnggota(),
            'status' => 'aktif',
        ]);

        return redirect()->route('anggota.index')
            ->with('status', 'Anggota berhasil ditambahkan.');
    }

    public function edit(Anggota $anggota): Response
    {
        return Inertia::render('Anggota/Edit', [
            'anggota' => $anggota,
            'daftarCabang' => ['Banjarmasin', 'Samarinda', 'Palangka'],
        ]);
    }

    public function update(UpdateAnggotaRequest $request, Anggota $anggota)
    {
        $limitCustomLama = $anggota->limit_custom;

        $anggota->update($request->validated());

        if ($limitCustomLama != $request->limit_custom) {
            AuditLog::catat(
                'update_limit_custom_anggota',
                "Limit khusus untuk {$anggota->nama} diubah menjadi " .
                    ($request->limit_custom ? 'Rp ' . number_format($request->limit_custom, 0, ',', '.') : 'dihapus (kembali ke aturan umum)') .
                    ". Alasan: {$request->limit_custom_keterangan}",
                ['limit_custom' => $limitCustomLama],
                ['limit_custom' => $request->limit_custom, 'keterangan' => $request->limit_custom_keterangan]
            );
        }

        return redirect()->route('anggota.index')
            ->with('status', 'Data anggota berhasil diperbarui.');
    }
}