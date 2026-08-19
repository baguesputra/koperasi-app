<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Helpers\TerbilangHelper;

class RiwayatController extends Controller
{
    public function index(Request $request): Response
    {
        $anggota = auth()->user()->anggota;

        $pinjaman = $anggota->pinjaman()
        ->with(['angsuran', 'pengajuanPercepatan.angsuranBaru'])
        ->latest('tanggal_pengajuan')
        ->get()
        ->map(function ($p) {
            return [
                'id' => $p->id,
                'nominal' => (float) $p->nominal,
                'tenor_bulan' => $p->tenor_bulan,
                'status' => $p->status,
                'tanggal_pengajuan' => $p->tanggal_pengajuan->format('d M Y'),
                'catatan_bendahara' => $p->catatan_bendahara,
                'catatan_ketua' => $p->catatan_ketua,
                'angsuran' => $p->jadwalAktif()->values()->map(fn ($a) => [
                    'cicilan_ke' => $a->cicilan_ke,
                    'total_bayar' => (float) $a->total_bayar,
                    'status' => $a->status,
                    'tanggal_jatuh_tempo' => $a->tanggal_jatuh_tempo->format('d M Y'),
                    'tanggal_konfirmasi_bayar' => $a->tanggal_konfirmasi_bayar?->format('d M Y'),
                ]),
                'riwayatPerubahan' => $p->pengajuanPercepatan->where('status', 'aktif')->map(fn ($pp) => [
                    'tipe' => match ($pp->tipe) {
                        'percepat' => 'Percepat Pelunasan',
                        'perpanjang' => 'Perpanjang Tenor',
                        default => 'Lunas Sekarang',
                    },
                    'tenor_lama' => $pp->tenor_lama,
                    'tenor_baru' => $pp->tenor_baru,
                    'bulan_berlaku' => $pp->bulan_berlaku === 'bulan_ini' ? 'Bulan Ini' : 'Bulan Depan',
                    'tanggal' => $pp->updated_at->format('d M Y'),
                    'jadwalLama' => \App\Models\Angsuran::where('pengajuan_percepatan_id', $pp->id)
                        ->orderBy('cicilan_ke')->get()
                        ->map(fn ($a) => ['cicilan_ke' => $a->cicilan_ke, 'total_bayar' => (float) $a->total_bayar, 'status' => $a->status]),
                ])->values(),
            ];
        });

        $bulanFilter = $request->input('bulan');

        $querySimpanan = $anggota->simpanan()->latest('tanggal_input');
        if ($bulanFilter) {
            $querySimpanan->where('bulan_periode', $bulanFilter);
        }

        $simpanan = $querySimpanan->get()->map(fn ($s) => [
            'jenis' => $s->jenis,
            'jumlah' => (float) $s->jumlah,
            'bulan_periode' => $s->bulan_periode,
            'tanggal_input' => $s->tanggal_input->format('d M Y'),
        ]);

        $daftarBulanTersedia = $anggota->simpanan()
            ->select('bulan_periode')
            ->distinct()
            ->orderByDesc('bulan_periode')
            ->pluck('bulan_periode');

        // Ringkasan
        $totalPinjamanDiajukan = $anggota->pinjaman()->count();
        $totalPinjamanLunas = $anggota->pinjaman()->where('status', 'lunas')->count();
        $totalSimpananTerkumpul = $anggota->simpanan()->whereIn('jenis', ['pokok', 'wajib'])->sum('jumlah');

        return Inertia::render('Portal/Riwayat', [
            'pinjaman' => $pinjaman,
            'simpanan' => $simpanan,
            'daftarBulanTersedia' => $daftarBulanTersedia,
            'bulanFilter' => $bulanFilter,
            'ringkasan' => [
                'total_pinjaman_diajukan' => $totalPinjamanDiajukan,
                'total_pinjaman_lunas' => $totalPinjamanLunas,
                'total_simpanan_terkumpul' => (float) $totalSimpananTerkumpul,
            ],
        ]);
    }
}