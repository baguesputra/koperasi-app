<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Tampilkan info akun (read-only, akses dikelola GATE).
     */
    public function edit(Request $request): Response
    {
        $user = $request->user()->loadMissing('anggota.perusahaan', 'anggota.departemen', 'anggota.divisiMaster', 'anggota.jabatanMaster');

        return Inertia::render('Profile/Edit', [
            'pengguna' => [
                'name' => $user->name,
                'email' => $user->email,
                'no_karyawan' => $user->no_karyawan,
                'roles' => $user->getRoleNames()->values(),
                'anggota' => $user->anggota ? [
                    'nama' => $user->anggota->nama,
                    'no_anggota' => $user->anggota->no_anggota,
                    'no_karyawan' => $user->anggota->no_karyawan,
                    'cabang' => $user->anggota->cabang,
                    'unit_bisnis' => $user->anggota->unit_bisnis,
                    'jabatan' => $user->anggota->getRelationValue('jabatanMaster')?->nama ?? $user->anggota->jabatan,
                    'department' => $user->anggota->getRelationValue('departemen')?->nama ?? $user->anggota->department,
                    'perusahaan' => $user->anggota->getRelationValue('perusahaan')?->nama,
                    'divisi' => $user->anggota->getRelationValue('divisiMaster')?->nama,
                    'status' => $user->anggota->status,
                    'no_hp' => $user->anggota->no_hp,
                    'alamat' => $user->anggota->alamat,
                    'tanggal_mulai_kerja' => $user->anggota->tanggal_mulai_kerja?->format('d M Y'),
                    'tanggal_jadi_anggota' => $user->anggota->tanggal_jadi_anggota?->format('d M Y'),
                    'lama_keanggotaan_tahun' => round($user->anggota->lama_keanggotaan_tahun, 1),
                    'foto_url' => $user->anggota->foto_url,
                ] : null,
            ],
        ]);
    }
}
