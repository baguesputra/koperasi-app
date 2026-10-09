<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PengajuanPercepatan;
use App\Models\KlaimDanaSosial;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Tampilkan info akun dan ringkasan data personal (read-only, akses dikelola GATE).
     */
    public function edit(Request $request): Response
    {
        $user = $request->user()->loadMissing('anggota.perusahaan', 'anggota.departemen', 'anggota.divisiMaster', 'anggota.jabatanMaster');
        $anggota = $user->anggota;

        $ringkasan = null;
        if ($anggota) {
            $statusesMenunggu = ['diajukan', 'approved_bendahara'];

            $ringkasan = [
                'pinjaman_total' => $anggota->pinjaman()->count(),
                'pinjaman_aktif' => $anggota->pinjaman()->where('status', 'aktif')->count(),
                'simpanan_total' => (float) $anggota->simpanan()->whereIn('jenis', ['pokok', 'wajib'])->sum('jumlah'),
                'limit_berjalan' => $anggota->pengajuanLimit()->whereIn('status', $statusesMenunggu)->count(),
                'tenor_berjalan' => PengajuanPercepatan::whereHas('pinjaman', fn ($q) => $q->where('anggota_id', $anggota->id))
                    ->whereIn('status', $statusesMenunggu)->count(),
                'santunan_berjalan' => KlaimDanaSosial::where('anggota_id', $anggota->id)
                    ->whereIn('status', $statusesMenunggu)->count(),
            ];
        }

        return Inertia::render('Profile/Edit', [
            'pengguna' => [
                'name' => $user->name,
                'email' => $user->email,
                'no_karyawan' => $user->no_karyawan,
                'roles' => $user->getRoleNames()->values(),
                'anggota' => $anggota ? [
                    'nama' => $anggota->nama,
                    'no_anggota' => $anggota->no_anggota,
                    'no_karyawan' => $anggota->no_karyawan,
                    'cabang' => $anggota->cabang,
                    'unit_bisnis' => $anggota->unit_bisnis,
                    'jabatan' => $anggota->getRelationValue('jabatanMaster')?->nama ?? $anggota->jabatan,
                    'department' => $anggota->getRelationValue('departemen')?->nama ?? $anggota->department,
                    'perusahaan' => $anggota->getRelationValue('perusahaan')?->nama,
                    'divisi' => $anggota->getRelationValue('divisiMaster')?->nama,
                    'status' => $anggota->status,
                    'no_hp' => $anggota->no_hp,
                    'alamat' => $anggota->alamat,
                    'tanggal_mulai_kerja' => $anggota->tanggal_mulai_kerja?->format('d M Y'),
                    'tanggal_jadi_anggota' => $anggota->tanggal_jadi_anggota?->format('d M Y'),
                    'lama_keanggotaan_tahun' => round($anggota->lama_keanggotaan_tahun, 1),
                    'foto_url' => $anggota->foto_url,
                ] : null,
            ],
            'ringkasan' => $ringkasan,
        ]);
    }
}
