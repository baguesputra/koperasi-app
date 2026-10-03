<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Angsuran;
use App\Models\KlaimDanaSosial;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RiwayatController extends Controller
{
    public function index(Request $request): Response
    {
        $anggota = auth()->user()->anggota;

        $daftarPinjaman = $anggota->pinjaman()
            ->with(['angsuran', 'pengajuanPercepatan.angsuranBaru'])
            ->latest('tanggal_pengajuan')
            ->get();

        // Preload jadwal lama angsuran per pengajuan aktif (ganti query per $pp di map)
        $semuaPpAktifIds = $daftarPinjaman->pluck('pengajuanPercepatan')->flatten()
            ->where('status', 'aktif')->pluck('id')->unique()->values();
        $jadwalLamaMap = $semuaPpAktifIds->isEmpty()
            ? collect()
            : Angsuran::whereIn('pengajuan_percepatan_id', $semuaPpAktifIds)
                ->orderBy('cicilan_ke')
                ->get()
                ->groupBy('pengajuan_percepatan_id');

        $pinjaman = $daftarPinjaman
            ->map(function ($p) use ($jadwalLamaMap) {
                return [
                    'id' => $p->id,
                    'nominal' => (float) $p->nominal,
                    'tenor_bulan' => $p->tenor_bulan,
                    'nominal_diminta' => (float) ($p->nominal_diminta ?? $p->nominal),
                    'tenor_diminta' => $p->tenor_diminta ?? $p->tenor_bulan,
                    'nominal_disetujui_bendahara' => $p->nominal_disetujui_bendahara !== null ? (float) $p->nominal_disetujui_bendahara : null,
                    'tenor_disetujui_bendahara' => $p->tenor_disetujui_bendahara,
                    'nominal_disetujui' => $p->nominal_disetujui !== null ? (float) $p->nominal_disetujui : null,
                    'tenor_disetujui' => $p->tenor_disetujui,
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
                        'jadwalLama' => ($jadwalLamaMap[$pp->id] ?? collect())
                            ->map(fn ($a) => ['cicilan_ke' => $a->cicilan_ke, 'total_bayar' => (float) $a->total_bayar, 'status' => $a->status])
                            ->values(),
                    ])->values(),
                ];
            });

        $bulanFilter = $request->input('bulan');

        $querySimpanan = $anggota->simpanan()->whereIn('jenis', ['wajib', 'pokok'])->latest('tanggal_input');
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

        $klaim = KlaimDanaSosial::where('anggota_id', $anggota->id)
            ->latest('tanggal_pengajuan')
            ->get()
            ->map(fn ($k) => [
                'id' => $k->id,
                'jenis_label' => app(\App\Services\DanaSosial\KlaimDanaSosialService::class)->labelJenis($k->jenis),
                'tanggal_kejadian' => $k->tanggal_kejadian->format('d M Y'),
                'nominal_bendahara' => $k->nominal_bendahara !== null ? (float) $k->nominal_bendahara : null,
                'nominal_final' => $k->nominal_final !== null ? (float) $k->nominal_final : null,
                'status' => $k->status,
                'catatan_bendahara' => $k->catatan_bendahara,
                'catatan_ketua' => $k->catatan_ketua,
                'tanggal_pengajuan' => $k->tanggal_pengajuan->format('d M Y'),
            ]);

        return Inertia::render('Portal/Riwayat', [
            'pinjaman' => $pinjaman,
            'simpanan' => $simpanan,
            'klaim' => $klaim,
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
