<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Anggota;
use App\Models\Angsuran;
use App\Models\PengajuanPercepatan;
use App\Models\PengajuanTransaksiTabungan;
use App\Models\RekeningTabungan;
use App\Models\SettingBungaTabungan;
use App\Models\SettingSimpanan;
use App\Models\TabelTenor;
use App\Services\Pinjaman\EligibilitasPinjamanService;
use App\Services\Tabungan\PerhitunganBungaTabunganService;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private EligibilitasPinjamanService $eligibilitas,
        private PerhitunganBungaTabunganService $bungaTabungan,
    ) {}

    public function index(): Response
    {
        $anggota = auth()->user()->anggota;

        $simpananAgg = $anggota->simpanan()
            ->selectRaw('
                SUM(CASE WHEN jenis IN ("pokok","wajib") THEN jumlah ELSE 0 END) as total,
                SUM(CASE WHEN jenis = "pokok" THEN jumlah ELSE 0 END) as pokok,
                SUM(CASE WHEN jenis = "wajib" THEN jumlah ELSE 0 END) as wajib
            ')
            ->first();

        $totalSimpanan = (float) ($simpananAgg->total ?? 0);
        $simpananPokok = (float) ($simpananAgg->pokok ?? 0);
        $simpananWajib = (float) ($simpananAgg->wajib ?? 0);

        $pinjamanAktif = $anggota->pinjamanAktif();
        $cekEligibilitas = $this->eligibilitas->cek($anggota);
        $cekTambahLimit = $this->eligibilitas->cekEligibilitasTambahLimit($anggota);
        $limitMaksimal = $this->eligibilitas->limitMaksimal($anggota);

        // Pengajuan yang sedang berjalan (belum aktif/lunas/ditolak; termasuk approved_ketua menunggu pencairan)
        $pengajuanBerjalan = $anggota->pinjaman()
            ->whereIn('status', ['diajukan', 'approved_admin', 'approved_bendahara', 'approved_ketua'])
            ->latest('tanggal_pengajuan')
            ->first();

        // Pengajuan yang baru saja ditolak (untuk ditampilkan sekali)
        $pengajuanDitolak = $anggota->pinjaman()
            ->where('status', 'ditolak')
            ->where('updated_at', '>=', now()->subDays(30))
            ->latest('updated_at')
            ->first();

        // Pengajuan limit yang sedang berjalan (diajukan ke bendahara / approved_bendahara ke ketua)
        $pengajuanLimitBerjalan = $anggota->pengajuanLimit()
            ->whereIn('status', ['diajukan', 'approved_admin', 'approved_bendahara'])
            ->latest('tanggal_pengajuan')
            ->first();

        $resignMenunggu = null;
        if ($anggota->status === 'resign_menunggu') {
            $settlement = $anggota->resigned_settlement_json ?? [];
            $resignMenunggu = [
                'shortfall' => (float) ($settlement['shortfall'] ?? 0),
                'jatuh_tempo' => isset($settlement['cicilan_akhir_jatuh_tempo'])
                    ? Carbon::parse($settlement['cicilan_akhir_jatuh_tempo'])->format('d M Y')
                    : null,
                'tanggal_resign' => $anggota->tanggal_resign?->format('d M Y'),
            ];
        }

        $angsuranBerikutnya = null;
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

        // 1 query angsuran lunas terbaru (ganti load semua pinjaman + semua angsuran lalu flatten)
        $pinjamanIdsAnggota = $anggota->pinjaman()->pluck('id');
        $riwayatAngsuran = $pinjamanIdsAnggota->isEmpty()
            ? collect()
            : Angsuran::whereIn('pinjaman_id', $pinjamanIdsAnggota)
                ->where('status', 'lunas')
                ->latest('tanggal_konfirmasi_bayar')
                ->take(4)
                ->get()
                ->map(fn ($a) => [
                    'tipe' => 'angsuran',
                    'label' => "Cicilan ke-{$a->cicilan_ke}",
                    'nominal' => (float) $a->total_bayar,
                    'tanggal' => $a->tanggal_konfirmasi_bayar,
                    'tanggal_format' => $a->tanggal_konfirmasi_bayar?->format('d M Y'),
                ]);

        $riwayatGabungan = $riwayatSimpanan->concat($riwayatAngsuran);

        $rekeningTabungan = $anggota->rekeningTabungan()
            ->whereIn('status', ['aktif', ...RekeningTabungan::STATUS_MENUNGGU])
            ->orderByRaw("CASE status WHEN 'aktif' THEN 1 ELSE 2 END")
            ->latest('id')
            ->first();

        $tabunganRingkasan = null;
        if ($rekeningTabungan) {
            if ($rekeningTabungan->status === 'aktif') {
                $estimasi = $this->bungaTabungan->estimasiBulanBerjalan($rekeningTabungan);
                $tabunganRingkasan = [
                    'status' => 'aktif',
                    'no_rekening' => $rekeningTabungan->no_rekening,
                    'saldo' => (float) $rekeningTabungan->saldo,
                    'estimasi_bunga_bulan_ini' => (float) ($estimasi['bunga_bersih'] ?? 0),
                    'total_bunga' => (float) $rekeningTabungan->bungaPeriode()->sum('bunga_bersih'),
                    'suku_bunga_aktif' => SettingBungaTabungan::persentaseAktif(),
                    'pengajuan_menunggu_count' => $rekeningTabungan->pengajuanTransaksi()
                        ->whereIn('status', PengajuanTransaksiTabungan::STATUS_MENUNGGU)
                        ->count(),
                ];

                $riwayatTabungan = $rekeningTabungan->transaksi()
                    ->whereNull('dibatalkan_pada')
                    ->whereNull('koreksi_dari_id')
                    ->latest('tanggal')
                    ->latest('id')
                    ->take(4)
                    ->get()
                    ->map(fn ($t) => [
                        'tipe' => $t->isKredit() ? ($t->jenis === 'bunga' ? 'tabungan_bunga' : 'tabungan_masuk') : 'tabungan_keluar',
                        'label' => match ($t->jenis) {
                            'setor' => 'Setor Tabungan',
                            'bunga' => 'Bunga Tabungan',
                            'tarik' => 'Tarik Tabungan',
                            'tutup_rekening' => 'Penutupan Tabungan',
                            default => 'Mutasi Tabungan',
                        },
                        'nominal' => (float) $t->nominal,
                        'tanggal' => $t->tanggal,
                        'tanggal_format' => $t->tanggal?->format('d M Y'),
                    ]);

                $riwayatGabungan = $riwayatGabungan->concat($riwayatTabungan);
            } else {
                $tabunganRingkasan = [
                    'status' => $rekeningTabungan->status,
                    'no_rekening' => null,
                    'saldo' => 0.0,
                    'estimasi_bunga_bulan_ini' => 0.0,
                    'total_bunga' => 0.0,
                    'suku_bunga_aktif' => SettingBungaTabungan::persentaseAktif(),
                    'pengajuan_menunggu_count' => 1,
                ];
            }
        }

        $riwayatGabungan = $riwayatGabungan
            ->sortByDesc('tanggal')
            ->take(4)
            ->values()
            ->map(fn ($item) => collect($item)->except('tanggal'));

        // Info dinamis untuk panel kanan
        $tabelTenor = TabelTenor::orderBy('nominal_min')->get(['nominal_min', 'nominal_max', 'tenor_maksimal_bulan']);
        $settingSimpanan = SettingSimpanan::orderBy('id')->get(['jenis', 'label', 'nominal']);

        $pinjamanAktifList = $anggota->pinjamanAktifList()->map(function ($p) {
            $agg = $p->agregatJadwalAktif();

            return [
                'id' => $p->id,
                'nominal' => (float) $p->nominal,
                'tenor_bulan' => $p->tenor_bulan,
                'sisa_angsuran' => $agg['sisa'],
                'total_angsuran' => $agg['total'],
                'sisa_total_bayar' => $agg['sisa_bayar'],
                'sudah_pakai_percepatan' => (bool) $p->sudah_pakai_percepatan,
                'tanggal_pencairan' => $p->tanggal_pencairan?->format('d M Y'),
                'bukti_pencairan_url' => $p->bukti_pencairan_path ? route('pinjaman.bukti-pencairan', $p->id) : null,
            ];
        });

        // Pengajuan perubahan tenor/pelunasan yang masih menunggu approval
        // untuk SEMUA pinjaman aktif anggota.
        $pinjamanAktifIds = $pinjamanAktifList->pluck('id');
        $pengajuanPercepatanMenunggu = $pinjamanAktifIds->isEmpty()
            ? collect()
            : PengajuanPercepatan::whereIn('pinjaman_id', $pinjamanAktifIds)
                ->whereIn('status', ['diajukan', 'approved_admin', 'approved_bendahara'])
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
                'no_karyawan' => $anggota->no_karyawan,
                'foto_url' => $anggota->foto_url,
                'lama_keanggotaan_label' => Anggota::formatLamaKeanggotaan($anggota->tanggal_jadi_anggota),
            ],
            'totalSimpanan' => (float) $totalSimpanan,
            'simpananPokok' => (float) $simpananPokok,
            'simpananWajib' => (float) $simpananWajib,
            'limitMaksimal' => (float) $limitMaksimal,
            'limitTersedia' => (float) $cekEligibilitas['limit_tersedia'],
            'sisaAngsuranAktif' => (int) $cekEligibilitas['sisa_angsuran'],
            'cicilanPokokAktif' => (float) $cekEligibilitas['cicilan_pokok'],
            'pinjamanAktif' => $pinjamanAktif ? (function () use ($pinjamanAktif) {
                $agg = $pinjamanAktif->agregatJadwalAktif();

                return [
                    'id' => $pinjamanAktif->id,
                    'nominal' => (float) $pinjamanAktif->nominal,
                    'tenor_bulan' => $pinjamanAktif->tenor_bulan,
                    'sisa_angsuran' => $agg['sisa'],
                    'total_angsuran' => $agg['total'],
                    'sisa_total_bayar' => $agg['sisa_bayar'],
                    'tanggal_pencairan' => $pinjamanAktif->tanggal_pencairan?->format('d M Y'),
                    'bukti_pencairan_url' => $pinjamanAktif->bukti_pencairan_path ? route('pinjaman.bukti-pencairan', $pinjamanAktif->id) : null,
                ];
            })() : null,
            'pinjamanAktifList' => $pinjamanAktifList,
            'pinjamanAktifCount' => $pinjamanAktifList->count(),
            'pengajuanPercepatanMenunggu' => $pengajuanPercepatanMenunggu,
            'pengajuanBerjalan' => $pengajuanBerjalan ? [
                'id' => $pengajuanBerjalan->id,
                'nominal' => (float) $pengajuanBerjalan->nominal,
                'nominal_diminta' => (float) ($pengajuanBerjalan->nominal_diminta ?? $pengajuanBerjalan->nominal),
                'nominal_disetujui_bendahara' => $pengajuanBerjalan->nominal_disetujui_bendahara !== null ? (float) $pengajuanBerjalan->nominal_disetujui_bendahara : null,
                'nominal_disetujui' => $pengajuanBerjalan->nominal_disetujui !== null ? (float) $pengajuanBerjalan->nominal_disetujui : null,
                'tenor_bulan' => (int) $pengajuanBerjalan->tenor_bulan,
                'tenor_disetujui' => $pengajuanBerjalan->tenor_disetujui !== null ? (int) $pengajuanBerjalan->tenor_disetujui : null,
                'snapshot_bank' => $pengajuanBerjalan->snapshot_bank,
                'snapshot_no_rekening' => $pengajuanBerjalan->snapshot_no_rekening,
                'snapshot_atas_nama' => $pengajuanBerjalan->snapshot_atas_nama,
                'tanggal_pengajuan' => $pengajuanBerjalan->tanggal_pengajuan?->format('d M Y'),
                'disetujui_ketua_pada' => $pengajuanBerjalan->disetujui_ketua_pada?->format('d M Y'),
                'status' => $pengajuanBerjalan->status,
            ] : null,
            'pengajuanLimitBerjalan' => $pengajuanLimitBerjalan ? [
                'limit_diminta' => (float) $pengajuanLimitBerjalan->limit_diminta,
                'limit_disetujui_bendahara' => $pengajuanLimitBerjalan->limit_disetujui_bendahara !== null ? (float) $pengajuanLimitBerjalan->limit_disetujui_bendahara : null,
                'status' => $pengajuanLimitBerjalan->status,
                'tanggal_pengajuan' => $pengajuanLimitBerjalan->tanggal_pengajuan->format('d M Y'),
            ] : null,
            'resignMenunggu' => $resignMenunggu,
            'pengajuanDitolak' => $pengajuanDitolak ? [
                'id' => $pengajuanDitolak->id,
                'nominal' => (float) $pengajuanDitolak->nominal,
                'catatan' => $pengajuanDitolak->catatan_ketua ?? $pengajuanDitolak->catatan_bendahara,
            ] : null,
            'angsuranBerikutnya' => $angsuranBerikutnya,
            'bisaAjukan' => $cekEligibilitas['boleh'],
            'alasanTidakBisa' => $cekEligibilitas['alasan'],
            'syaratMasaKeanggotaan' => $cekEligibilitas['syarat_masa_keanggotaan'] ?? null,
            'bolehAjukanLimit' => $cekTambahLimit['boleh'],
            'pesanTerkunciLimit' => $cekTambahLimit['alasan'],
            'statusLimitBadge' => $cekTambahLimit['status_badge'],
            'stateLimitLabel' => $cekTambahLimit['state_label'],
            'plafonKebijakan' => (float) $cekTambahLimit['plafon_koperasi'],
            'riwayatGabungan' => $riwayatGabungan,
            'tabungan' => $tabunganRingkasan,
            'sukuBungaTabunganAktif' => SettingBungaTabungan::persentaseAktif(),
            'tabelTenor' => $tabelTenor,
            'settingSimpanan' => $settingSimpanan,
        ]);
    }
}
