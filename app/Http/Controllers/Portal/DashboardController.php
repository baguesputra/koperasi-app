<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\Pinjaman\EligibilitasPinjamanService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private EligibilitasPinjamanService $eligibilitas) {}

    public function index(): Response
    {
        $anggota = auth()->user()->anggota;

        $totalSimpanan = $anggota->simpanan()->whereIn('jenis', ['pokok', 'wajib'])->sum('jumlah');
        $pinjamanAktif = $anggota->pinjamanAktif();

        $cekEligibilitas = $this->eligibilitas->cek($anggota);

        $riwayatAngsuran = $anggota->pinjaman()
            ->with(['angsuran' => fn ($q) => $q->where('status', 'lunas')->latest('tanggal_konfirmasi_bayar')])
            ->get()
            ->pluck('angsuran')
            ->flatten()
            ->sortByDesc('tanggal_konfirmasi_bayar')
            ->take(3)
            ->map(fn ($a) => [
                'label' => "Cicilan ke-{$a->cicilan_ke}",
                'nominal' => (float) $a->total_bayar,
                'tanggal' => $a->tanggal_konfirmasi_bayar->format('d M Y'),
            ])
            ->values();

        return Inertia::render('Portal/Dashboard', [
            'anggota' => [
                'nama' => $anggota->nama,
                'no_anggota' => $anggota->no_anggota,
            ],
            'totalSimpanan' => (float) $totalSimpanan,
            'pinjamanAktif' => $pinjamanAktif ? [
                'id' => $pinjamanAktif->id,
                'nominal' => (float) $pinjamanAktif->nominal,
                'tenor_bulan' => $pinjamanAktif->tenor_bulan,
                'sisa_angsuran' => $pinjamanAktif->sisaAngsuran(),
                'total_angsuran' => $pinjamanAktif->angsuran()->count(),
            ] : null,
            'bisaAjukan' => $cekEligibilitas['boleh'],
            'alasanTidakBisa' => $cekEligibilitas['alasan'],
            'riwayatAngsuran' => $riwayatAngsuran,
        ]);
    }
}