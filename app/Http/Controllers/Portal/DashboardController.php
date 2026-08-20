<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\PengajuanPercepatan;
use App\Models\SettingSimpanan;
use App\Models\TabelTenor;
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
        $simpananPokok = $anggota->simpanan()->where('jenis', 'pokok')->sum('jumlah');
        $simpananWajib = $anggota->simpanan()->where('jenis', 'wajib')->sum('jumlah');

        $pinjamanAktif = $anggota->pinjamanAktif();
        $cekEligibilitas = $this->eligibilitas->cek($anggota);
        $limitMaksimal = $this->eligibilitas->limitMaksimal($anggota);

        // Pengajuan yang sedang berjalan (belum aktif/lunas/ditolak)
        $pengajuanBerjalan = $anggota->pinjaman()
            ->whereIn('status', ['diajukan', 'approved_bendahara'])
            ->latest('tanggal_pengajuan')
            ->first();

        // Pengajuan yang baru saja ditolak (untuk ditampilkan sekali)
        $pengajuanDitolak = $anggota->pinjaman()
            ->where('status', 'ditolak')
            ->latest('updated_at')
            ->first();

        $angsuranBerikutnya = null;
        if ($pinjamanAktif) {
            $terdekat = $pinjamanAktif->jadwalAktif()
                ->where('status', 'belum_bayar')
                ->sortBy('tanggal_jatuh_tempo')
                ->first();

            if ($terdekat) {
                $angsuranBerikutnya = [
                    'cicilan_ke' => $terdekat->cicilan_ke,
                    'total_bayar' => (float) $terdekat->total_bayar,
                    'tanggal_jatuh_tempo' => $terdekat->tanggal_jatuh_tempo->format('d M Y'),
                ];
            }
        }
        $sisaTotalBayar = 0;

        if ($pinjamanAktif) {
            $angsuranBelumBayar = $pinjamanAktif->angsuranBelumBayar()->orderBy('cicilan_ke')->get();
            $sisaTotalBayar = $angsuranBelumBayar->sum('total_bayar');

            $terdekat = $angsuranBelumBayar->first();
            if ($terdekat) {
                $angsuranBerikutnya = [
                    'cicilan_ke' => $terdekat->cicilan_ke,
                    'total_bayar' => (float) $terdekat->total_bayar,
                    'tanggal_jatuh_tempo' => $terdekat->tanggal_jatuh_tempo->format('d M Y'),
                ];
            }
        }

        $riwayatSimpanan = $anggota->simpanan()
            ->whereIn('jenis', ['wajib', 'pokok'])
            ->latest('tanggal_input')
            ->take(6)
            ->get()
            ->map(fn ($s) => [
                'tipe' => 'simpanan',
                'label' => match ($s->jenis) {
                    'pokok' => 'Simpanan Pokok',
                    'wajib' => 'Simpanan Wajib',
                    'dana_sosial' => 'Dana Sosial',
                    default => $s->jenis,
                },
                'nominal' => (float) $s->jumlah,
                'tanggal' => $s->tanggal_input,
                'tanggal_format' => $s->tanggal_input->format('d M Y'),
            ]);

        $riwayatAngsuran = $anggota->pinjaman()
            ->with(['angsuran' => fn ($q) => $q->where('status', 'lunas')])
            ->get()
            ->pluck('angsuran')
            ->flatten()
            ->map(fn ($a) => [
                'tipe' => 'angsuran',
                'label' => "Cicilan ke-{$a->cicilan_ke}",
                'nominal' => (float) $a->total_bayar,
                'tanggal' => $a->tanggal_konfirmasi_bayar,
                'tanggal_format' => $a->tanggal_konfirmasi_bayar->format('d M Y'),
            ]);

        $riwayatGabungan = $riwayatSimpanan->concat($riwayatAngsuran)
            ->sortByDesc('tanggal')
            ->take(4)
            ->values()
            ->map(fn ($item) => collect($item)->except('tanggal'));

        // Info dinamis untuk panel kanan
        $tabelTenor = TabelTenor::orderBy('nominal_min')->get(['nominal_min', 'nominal_max', 'tenor_maksimal_bulan']);
        $settingSimpanan = SettingSimpanan::orderBy('id')->get(['jenis', 'label', 'nominal']);

        $pinjamanAktifList = $anggota->pinjamanAktifList()->map(fn ($p) => [
            'id' => $p->id,
            'nominal' => (float) $p->nominal,
            'tenor_bulan' => $p->tenor_bulan,
            'sisa_angsuran' => $p->sisaCicilanAktif(),
            'total_angsuran' => $p->totalCicilanAktif(),
            'sisa_total_bayar' => $p->sisaTotalBayarAktif(),
            'sudah_pakai_percepatan' => (bool) $p->sudah_pakai_percepatan,
        ]);

        // Pengajuan perubahan tenor/pelunasan yang masih menunggu approval
        // untuk SEMUA pinjaman aktif anggota.
        $pinjamanAktifIds = $pinjamanAktifList->pluck('id');
        $pengajuanPercepatanMenunggu = $pinjamanAktifIds->isEmpty()
            ? collect()
            : PengajuanPercepatan::whereIn('pinjaman_id', $pinjamanAktifIds)
                ->whereIn('status', ['diajukan', 'approved_bendahara'])
                ->with('pinjaman:id,nominal,tenor_bulan')
                ->orderBy('tanggal_pengajuan')
                ->get()
                ->map(fn ($pp) => [
                    'id' => $pp->id,
                    'pinjaman_id' => $pp->pinjaman_id,
                    'pinjaman_nominal' => (float) $pp->pinjaman->nominal,
                    'pinjaman_tenor' => $pp->pinjaman->tenor_bulan,
                    'tipe' => $pp->tipe,
                    'tenor_lama' => $pp->tenor_lama,
                    'tenor_baru' => $pp->tenor_baru,
                    'status' => $pp->status,
                    'tanggal_pengajuan' => $pp->tanggal_pengajuan->format('d M Y'),
                ]);

        return Inertia::render('Portal/Dashboard', [
            'anggota' => [
                'nama' => $anggota->nama,
                'no_anggota' => $anggota->no_anggota,
                'lama_keanggotaan_label' => $this->formatLamaKeanggotaan($anggota->tanggal_jadi_anggota),
            ],
            'totalSimpanan' => (float) $totalSimpanan,
            'simpananPokok' => (float) $simpananPokok,
            'simpananWajib' => (float) $simpananWajib,
            'limitMaksimal' => (float) $limitMaksimal,
            'limitTersedia' => (float) $cekEligibilitas['limit_tersedia'],
            'sisaAngsuranAktif' => (int) $cekEligibilitas['sisa_angsuran'],
            'cicilanPokokAktif' => (float) $cekEligibilitas['cicilan_pokok'],
            'pinjamanAktif' => $pinjamanAktif ? [
                'id' => $pinjamanAktif->id,
                'nominal' => (float) $pinjamanAktif->nominal,
                'tenor_bulan' => $pinjamanAktif->tenor_bulan,
                'sisa_angsuran' => $pinjamanAktif->sisaCicilanAktif(),
                'total_angsuran' => $pinjamanAktif->totalCicilanAktif(),
                'sisa_total_bayar' => $pinjamanAktif->sisaTotalBayarAktif(),
            ] : null,
            'pinjamanAktifList' => $pinjamanAktifList,
            'pinjamanAktifCount' => $pinjamanAktifList->count(),
            'pengajuanPercepatanMenunggu' => $pengajuanPercepatanMenunggu,
            'pengajuanBerjalan' => $pengajuanBerjalan ? [
                'nominal' => (float) $pengajuanBerjalan->nominal,
                'status' => $pengajuanBerjalan->status,
            ] : null,
            'pengajuanDitolak' => $pengajuanDitolak ? [
                'nominal' => (float) $pengajuanDitolak->nominal,
                'catatan' => $pengajuanDitolak->catatan_ketua ?? $pengajuanDitolak->catatan_bendahara,
            ] : null,
            'angsuranBerikutnya' => $angsuranBerikutnya,
            'bisaAjukan' => $cekEligibilitas['boleh'],
            'alasanTidakBisa' => $cekEligibilitas['alasan'],
            'riwayatGabungan' => $riwayatGabungan,
            'tabelTenor' => $tabelTenor,
            'settingSimpanan' => $settingSimpanan,
        ]);
    }

    private function formatLamaKeanggotaan($tanggalJadiAnggota): string
    {
        $sekarang = now();
        $tahun = (int) $tanggalJadiAnggota->diffInYears($sekarang);
        $tanggalSetelahTahun = $tanggalJadiAnggota->copy()->addYears($tahun);
        $bulan = (int) $tanggalSetelahTahun->diffInMonths($sekarang);

        if ($tahun === 0 && $bulan === 0) {
            return 'baru bergabung';
        }
        if ($tahun === 0) {
            return "{$bulan} bulan";
        }
        if ($bulan === 0) {
            return "{$tahun} tahun";
        }

        return "{$tahun} tahun {$bulan} bulan";
    }
}
