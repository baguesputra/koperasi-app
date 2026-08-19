<?php

namespace App\Http\Controllers\Portal;

use App\Helpers\TerbilangHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RiwayatController extends Controller
{
    public function index(Request $request): Response
    {
        $anggota = auth()->user()->anggota;

        $pinjaman = $anggota->pinjaman()
            ->with([
                'angsuran',
                'pengajuanPercepatan' => fn ($q) => $q->with('angsuranPercepatan')->latest('tanggal_pengajuan'),
            ])
            ->latest('tanggal_pengajuan')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'nominal' => (float) $p->nominal,
                'terbilang' => TerbilangHelper::angkaKeTerbilang($p->nominal),
                'tenor_bulan' => $p->tenor_bulan,
                'keperluan' => $p->keperluan,
                'rekening' => [
                    'bank' => $p->snapshot_bank,
                    'no_rekening' => $p->snapshot_no_rekening,
                    'atas_nama' => $p->snapshot_atas_nama,
                ],
                'status' => $p->status,
                'tanggal_pengajuan' => $p->tanggal_pengajuan->format('d M Y'),
                'catatan_bendahara' => $p->catatan_bendahara,
                'catatan_ketua' => $p->catatan_ketua,
                'sudah_pernah_percepatan' => $p->pengajuanPercepatan()->exists(),
                'angsuran' => $p->angsuran->map(fn ($a) => [
                    'cicilan_ke' => $a->cicilan_ke,
                    'total_bayar' => (float) $a->total_bayar,
                    'status' => $a->status,
                    'tanggal_jatuh_tempo' => $a->tanggal_jatuh_tempo->format('d M Y'),
                    'tanggal_konfirmasi_bayar' => $a->tanggal_konfirmasi_bayar?->format('d M Y'),
                ]),
                'percepatan' => $p->pengajuanPercepatan->map(fn ($pp) => [
                    'id' => $pp->id,
                    'tipe' => $pp->tipe,
                    'tenor_lama' => $pp->tenor_lama,
                    'tenor_baru' => null,
                    'sisa_pokok_saat_ajukan' => (float) $pp->sisa_pokok_saat_ajukan,
                    'nominal_final' => (float) $pp->nominal_final,
                    'status' => $pp->status,
                    'keterangan' => $pp->keterangan,
                    'tanggal_pengajuan' => $pp->tanggal_pengajuan->format('d M Y'),
                    'angsuran_baru' => $pp->angsuranPercepatan->map(fn ($ap) => [
                        'cicilan_ke' => $ap->cicilan_ke,
                        'total_bayar' => (float) $ap->total_bayar,
                        'status' => $ap->status,
                        'tanggal_jatuh_tempo' => $ap->tanggal_jatuh_tempo->format('d M Y'),
                        'tanggal_konfirmasi_bayar' => $ap->tanggal_konfirmasi_bayar?->format('d M Y'),
                    ]),
                ]),
            ]);

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
            'autoPercepatan' => $request->query('percepatan') === '1',
            'ringkasan' => [
                'total_pinjaman_diajukan' => $totalPinjamanDiajukan,
                'total_pinjaman_lunas' => $totalPinjamanLunas,
                'total_simpanan_terkumpul' => (float) $totalSimpananTerkumpul,
            ],
        ]);
    }
}
