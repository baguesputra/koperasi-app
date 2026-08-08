<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class RiwayatController extends Controller
{
    public function index(): Response
    {
        $anggota = auth()->user()->anggota;

        $pinjaman = $anggota->pinjaman()
            ->with('angsuran')
            ->latest('tanggal_pengajuan')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'nominal' => (float) $p->nominal,
                'tenor_bulan' => $p->tenor_bulan,
                'status' => $p->status,
                'tanggal_pengajuan' => $p->tanggal_pengajuan->format('d M Y'),
                'angsuran' => $p->angsuran->map(fn ($a) => [
                    'cicilan_ke' => $a->cicilan_ke,
                    'total_bayar' => (float) $a->total_bayar,
                    'status' => $a->status,
                    'tanggal_jatuh_tempo' => $a->tanggal_jatuh_tempo->format('d M Y'),
                    'tanggal_konfirmasi_bayar' => $a->tanggal_konfirmasi_bayar?->format('d M Y'),
                ]),
            ]);

        $simpanan = $anggota->simpanan()
            ->latest('tanggal_input')
            ->get()
            ->map(fn ($s) => [
                'jenis' => $s->jenis,
                'jumlah' => (float) $s->jumlah,
                'bulan_periode' => $s->bulan_periode,
                'tanggal_input' => $s->tanggal_input->format('d M Y'),
            ]);

        return Inertia::render('Portal/Riwayat', [
            'pinjaman' => $pinjaman,
            'simpanan' => $simpanan,
        ]);
    }
}