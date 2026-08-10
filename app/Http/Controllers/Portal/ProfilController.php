<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\RekeningAnggota;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfilController extends Controller
{
    public function index(): Response
    {
        $anggota = auth()->user()->anggota;

        return Inertia::render('Portal/Profil', [
            'anggota' => [
                'nama' => $anggota->nama,
                'no_anggota' => $anggota->no_anggota,
                'cabang' => $anggota->cabang,
                'unit_bisnis' => $anggota->unit_bisnis,
                'jabatan' => $anggota->jabatan,
                'tanggal_mulai_kerja' => $anggota->tanggal_mulai_kerja->format('d M Y'),
                'tanggal_jadi_anggota' => $anggota->tanggal_jadi_anggota->format('d M Y'),
                'email' => auth()->user()->email,
            ],
            'rekening' => $anggota->rekening()->orderByDesc('is_default')->get(),
        ]);
    }

    public function storeRekening(Request $request)
    {
        $validated = $request->validate([
            'nama_bank' => ['required', 'string', 'max:100'],
            'no_rekening' => ['required', 'string', 'max:50'],
            'atas_nama' => ['required', 'string', 'max:100'],
        ]);

        $anggota = auth()->user()->anggota;
        $jumlahRekening = $anggota->rekening()->count();

        $anggota->rekening()->create([
            ...$validated,
            'is_default' => $jumlahRekening === 0,
        ]);

        return back()->with('status', 'Rekening berhasil ditambahkan.');
    }

    public function setDefaultRekening(RekeningAnggota $rekening)
    {
        $anggota = auth()->user()->anggota;

        if ($rekening->anggota_id !== $anggota->id) {
            abort(403);
        }

        $anggota->rekening()->update(['is_default' => false]);
        $rekening->update(['is_default' => true]);

        return back()->with('status', 'Rekening utama berhasil diperbarui.');
    }

    public function destroyRekening(RekeningAnggota $rekening)
    {
        $anggota = auth()->user()->anggota;

        if ($rekening->anggota_id !== $anggota->id) {
            abort(403);
        }

        $wasDefault = $rekening->is_default;
        $rekening->delete();

        // Kalau yang dihapus itu default, jadikan rekening lain (kalau ada) sebagai default baru
        if ($wasDefault) {
            $anggota->rekening()->first()?->update(['is_default' => true]);
        }

        return back()->with('status', 'Rekening berhasil dihapus.');
    }
}