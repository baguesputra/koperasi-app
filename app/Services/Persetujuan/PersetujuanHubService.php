<?php

namespace App\Services\Persetujuan;

use App\Helpers\TerbilangHelper;
use App\Http\Controllers\Ketua\AktivasiController as KetuaAktivasiController;
use App\Http\Controllers\Ketua\PengajuanLimitController as KetuaPengajuanLimitController;
use App\Models\Anggota;
use App\Models\KasKoperasi;
use App\Models\PengajuanAktivasi;
use App\Models\PengajuanLimit;
use App\Models\PengajuanPenghapusanPinjaman;
use App\Models\PengajuanPercepatan;
use App\Models\PengajuanTransaksiTabungan;
use App\Models\Pinjaman;
use App\Models\RekeningTabungan;
use App\Models\SettingLimitPinjaman;
use App\Models\SettingSimpanan;
use App\Models\User;
use App\Services\Pinjaman\FormatPinjamanService;
use App\Services\Pinjaman\PenghapusanPinjamanService;
use App\Services\Pinjaman\PercepatanPinjamanService;
use App\Services\Pinjaman\RingkasanPinjamanService;
use Illuminate\Support\Collection;

class PersetujuanHubService
{
    /** @var array<int, string> */
    public const ALL_PERMISSIONS = [
        'pinjaman.tinjau-admin',
        'pinjaman.tinjau-bendahara',
        'pinjaman.approve-ketua',
        'pinjaman.cairkan',
        'tabungan.tinjau-admin',
        'tabungan.kelola',
        'tabungan.tinjau-bendahara',
        'tabungan.approve-ketua',
        'limit.tinjau-bendahara',
        'limit.approve-ketua',
        'aktivasi.approve-ketua',
        'penghapusan-pinjaman.lihat',
    ];

    public function __construct(
        private FormatPinjamanService $formatPinjaman,
        private RingkasanPinjamanService $ringkasanPinjaman,
        private PercepatanPinjamanService $percepatanService,
        private PenghapusanPinjamanService $penghapusanService,
    ) {}

    /**
     * Hitung seluruh angka notifikasi persetujuan sekaligus memastikan total_persetujuan
     * non-overlapping dengan seluruh tab di Pusat Persetujuan.
     *
     * @param  Collection<int, string>  $permissions
     * @return array<string, int>
     */
    public function ringkasanNotifikasi(User $user, Collection $permissions): array
    {
        $request = app()->bound('request') ? request() : null;
        $memoKey = null;
        if ($request) {
            $memoKey = 'persetujuan_hub.notif.'.$user->id.'.'.md5($permissions->sort()->implode(','));
            if ($request->attributes->has($memoKey)) {
                return $request->attributes->get($memoKey);
            }
        }

        $notifications = [];
        $totalPersetujuan = 0;

        $needPinjamanAdmin = $permissions->contains('pinjaman.tinjau-admin');
        $needPinjamanBendahara = $permissions->contains('pinjaman.tinjau-bendahara');
        $needPinjamanKetua = $permissions->contains('pinjaman.approve-ketua');
        $needPinjamanCair = $permissions->contains('pinjaman.cairkan');

        if ($needPinjamanAdmin || $needPinjamanBendahara || $needPinjamanKetua || $needPinjamanCair) {
            $pinjamanAgg = Pinjaman::whereIn('status', ['diajukan', 'approved_admin', 'approved_bendahara', 'approved_ketua'])
                ->selectRaw('status, cair_oleh_bendahara, COUNT(*) as jumlah')
                ->groupBy('status', 'cair_oleh_bendahara')
                ->get();

            $hitungPinjaman = fn (string $status, ?bool $cairOlehBendahara = null): int => (int) $pinjamanAgg
                ->filter(fn ($row) => $row->status === $status && ($cairOlehBendahara === null || (bool) $row->cair_oleh_bendahara === $cairOlehBendahara))
                ->sum('jumlah');

            if ($needPinjamanAdmin) {
                $count = $hitungPinjaman('diajukan');
                $notifications['menunggu_tinjauan_admin'] = $count;
                $totalPersetujuan += $count;
            }

            if ($needPinjamanBendahara) {
                $count = $hitungPinjaman('approved_admin');
                $notifications['menunggu_tinjauan_bendahara'] = $count;
                $totalPersetujuan += $count;
            }

            if ($needPinjamanKetua) {
                $count = $hitungPinjaman('approved_bendahara', false);
                $notifications['menunggu_approval_ketua'] = $count;
                $totalPersetujuan += $count;
            }

            if ($needPinjamanCair) {
                $count = $hitungPinjaman('approved_ketua');
                $notifications['menunggu_pencairan_pinjaman'] = $count;
                $totalPersetujuan += $count;

                $countResign = Anggota::where('status_pencairan_resign', 'menunggu')->count();
                $notifications['menunggu_pencairan_resign'] = $countResign;
                $totalPersetujuan += $countResign;
            }
        }

        if ($needPinjamanBendahara || $needPinjamanKetua) {
            $percepatanAgg = PengajuanPercepatan::whereIn('status', ['diajukan', 'approved_bendahara'])
                ->selectRaw('status, COUNT(*) as jumlah')
                ->groupBy('status')
                ->pluck('jumlah', 'status');

            if ($needPinjamanBendahara) {
                $count = (int) ($percepatanAgg['diajukan'] ?? 0);
                $notifications['menunggu_perubahan_tenor_bendahara'] = $count;
                $totalPersetujuan += $count;
            }

            if ($needPinjamanKetua) {
                $count = (int) ($percepatanAgg['approved_bendahara'] ?? 0);
                $notifications['menunggu_perubahan_tenor_ketua'] = $count;
                $totalPersetujuan += $count;
            }
        }

        $needLimitBendahara = $permissions->contains('limit.tinjau-bendahara');
        $needLimitKetua = $permissions->contains('limit.approve-ketua');

        if ($needLimitBendahara || $needLimitKetua) {
            $limitAgg = PengajuanLimit::whereIn('status', ['diajukan', 'approved_bendahara'])
                ->selectRaw('status, COUNT(*) as jumlah')
                ->groupBy('status')
                ->pluck('jumlah', 'status');

            if ($needLimitBendahara) {
                $count = (int) ($limitAgg['diajukan'] ?? 0);
                $notifications['menunggu_pengajuan_limit_bendahara'] = $count;
                $totalPersetujuan += $count;
            }

            if ($needLimitKetua) {
                $count = (int) ($limitAgg['approved_bendahara'] ?? 0);
                $notifications['menunggu_pengajuan_limit'] = $count;
                $totalPersetujuan += $count;
            }
        }

        if ($permissions->contains('aktivasi.approve-ketua')) {
            $count = PengajuanAktivasi::where('status', 'diajukan')->count();
            $notifications['menunggu_aktivasi'] = $count;
            $totalPersetujuan += $count;
        }

        if ($permissions->contains('penghapusan-pinjaman.lihat')) {
            $count = $this->penghapusanService->hitungAntreanUntukUser($user);
            $notifications['menunggu_penghapusan_pinjaman'] = $count;
            $totalPersetujuan += $count;
        }

        $needTabunganAdmin = $permissions->contains('tabungan.tinjau-admin');
        $bisaTabunganBendahara = $permissions->contains('tabungan.kelola')
            || $permissions->contains('tabungan.tinjau-bendahara');
        $needTabunganKelola = $permissions->contains('tabungan.kelola');
        $needTabunganKetua = $permissions->contains('tabungan.approve-ketua');

        if ($needTabunganAdmin || $bisaTabunganBendahara || $needTabunganKelola || $needTabunganKetua) {
            $rekAgg = RekeningTabungan::whereIn('status', ['diajukan', 'approved_admin', 'approved_bendahara'])
                ->selectRaw('status, COUNT(*) as jumlah')
                ->groupBy('status')
                ->pluck('jumlah', 'status');

            $trxAgg = PengajuanTransaksiTabungan::whereIn('status', ['menunggu', 'approved_admin', 'approved_bendahara', 'approved_ketua'])
                ->selectRaw('jenis, status, COUNT(*) as jumlah')
                ->groupBy('jenis', 'status')
                ->get();

            $hitungTrx = fn (array $jenis, string $status): int => (int) $trxAgg
                ->filter(fn ($row) => $row->status === $status && in_array($row->jenis, $jenis, true))
                ->sum('jumlah');

            if ($needTabunganAdmin) {
                $count = (int) ($rekAgg['diajukan'] ?? 0)
                    + $hitungTrx(PengajuanTransaksiTabungan::JENIS_PENARIKAN, 'menunggu');
                $notifications['menunggu_tabungan_admin'] = $count;
                $totalPersetujuan += $count;
            }

            if ($bisaTabunganBendahara || $needTabunganKelola) {
                $tabunganBendaharaTinjau = (int) ($rekAgg['approved_admin'] ?? 0)
                    + $hitungTrx(['setor'], 'menunggu')
                    + $hitungTrx(PengajuanTransaksiTabungan::JENIS_PENARIKAN, 'approved_admin');
                $pencairanTabungan = $hitungTrx(PengajuanTransaksiTabungan::JENIS_PENARIKAN, 'approved_ketua');

                if ($bisaTabunganBendahara) {
                    $notifications['menunggu_tabungan'] = $tabunganBendaharaTinjau + $pencairanTabungan;
                    $notifications['menunggu_tabungan_bendahara'] = $tabunganBendaharaTinjau;
                    $totalPersetujuan += $tabunganBendaharaTinjau;
                }

                if ($needTabunganKelola) {
                    $notifications['menunggu_pencairan_tabungan'] = $pencairanTabungan;
                    $totalPersetujuan += $pencairanTabungan;
                }
            }

            if ($needTabunganKetua) {
                $count = (int) ($rekAgg['approved_bendahara'] ?? 0)
                    + $hitungTrx(PengajuanTransaksiTabungan::JENIS_PENARIKAN, 'approved_bendahara');
                $notifications['menunggu_tabungan_ketua'] = $count;
                $totalPersetujuan += $count;
            }
        }

        $notifications['total_persetujuan'] = $totalPersetujuan;

        if ($request && $memoKey) {
            $request->attributes->set($memoKey, $notifications);
        }

        return $notifications;
    }

    /**
     * Susun daftar tab kategori persetujuan & sub-tahap sesuai hak akses user.
     *
     * @param  Collection<int, string>  $permissions
     * @param  array<string, int>  $notif
     * @return array<int, array<string, mixed>>
     */
    public function bangunTabMenu(User $user, Collection $permissions, array $notif): array
    {
        $tabs = [];

        $pinjamanStages = [];
        if ($permissions->contains('pinjaman.tinjau-admin')) {
            $pinjamanStages[] = ['key' => 'admin', 'label' => 'Sekretaris', 'count' => (int) ($notif['menunggu_tinjauan_admin'] ?? 0)];
        }
        if ($permissions->contains('pinjaman.tinjau-bendahara')) {
            $pinjamanStages[] = ['key' => 'bendahara', 'label' => 'Bendahara', 'count' => (int) ($notif['menunggu_tinjauan_bendahara'] ?? 0)];
        }
        if ($permissions->contains('pinjaman.approve-ketua')) {
            $pinjamanStages[] = ['key' => 'ketua', 'label' => 'Ketua', 'count' => (int) ($notif['menunggu_approval_ketua'] ?? 0)];
        }
        if (! empty($pinjamanStages)) {
            $tabs[] = [
                'key' => 'pinjaman',
                'label' => 'Pinjaman',
                'count' => array_sum(array_column($pinjamanStages, 'count')),
                'stages' => $pinjamanStages,
            ];
        }

        $canCairPinjaman = $permissions->contains('pinjaman.cairkan');
        $canCairTabungan = $permissions->contains('tabungan.kelola');
        if ($canCairPinjaman || $canCairTabungan) {
            $countPencairan = ($canCairPinjaman ? (int) ($notif['menunggu_pencairan_pinjaman'] ?? 0) + (int) ($notif['menunggu_pencairan_resign'] ?? 0) : 0)
                + ($canCairTabungan ? (int) ($notif['menunggu_pencairan_tabungan'] ?? 0) : 0);

            $tabs[] = [
                'key' => 'pencairan',
                'label' => 'Pencairan Dana',
                'count' => $countPencairan,
                'stages' => [],
            ];
        }

        $tabunganStages = [];
        if ($permissions->contains('tabungan.tinjau-admin')) {
            $tabunganStages[] = ['key' => 'admin', 'label' => 'Sekretaris', 'count' => (int) ($notif['menunggu_tabungan_admin'] ?? 0)];
        }
        if ($permissions->contains('tabungan.kelola') || $permissions->contains('tabungan.tinjau-bendahara')) {
            $tabunganStages[] = ['key' => 'bendahara', 'label' => 'Bendahara', 'count' => (int) ($notif['menunggu_tabungan_bendahara'] ?? 0)];
        }
        if ($permissions->contains('tabungan.approve-ketua')) {
            $tabunganStages[] = ['key' => 'ketua', 'label' => 'Ketua', 'count' => (int) ($notif['menunggu_tabungan_ketua'] ?? 0)];
        }
        if (! empty($tabunganStages)) {
            $tabs[] = [
                'key' => 'tabungan',
                'label' => 'Tabungan',
                'count' => array_sum(array_column($tabunganStages, 'count')),
                'stages' => $tabunganStages,
            ];
        }

        $percepatanStages = [];
        if ($permissions->contains('pinjaman.tinjau-bendahara')) {
            $percepatanStages[] = ['key' => 'bendahara', 'label' => 'Bendahara', 'count' => (int) ($notif['menunggu_perubahan_tenor_bendahara'] ?? 0)];
        }
        if ($permissions->contains('pinjaman.approve-ketua')) {
            $percepatanStages[] = ['key' => 'ketua', 'label' => 'Ketua', 'count' => (int) ($notif['menunggu_perubahan_tenor_ketua'] ?? 0)];
        }
        if (! empty($percepatanStages)) {
            $tabs[] = [
                'key' => 'percepatan',
                'label' => 'Ubah Tenor',
                'count' => array_sum(array_column($percepatanStages, 'count')),
                'stages' => $percepatanStages,
            ];
        }

        $limitStages = [];
        if ($permissions->contains('limit.tinjau-bendahara')) {
            $limitStages[] = ['key' => 'bendahara', 'label' => 'Bendahara', 'count' => (int) ($notif['menunggu_pengajuan_limit_bendahara'] ?? 0)];
        }
        if ($permissions->contains('limit.approve-ketua')) {
            $limitStages[] = ['key' => 'ketua', 'label' => 'Ketua', 'count' => (int) ($notif['menunggu_pengajuan_limit'] ?? 0)];
        }
        if (! empty($limitStages)) {
            $tabs[] = [
                'key' => 'limit',
                'label' => 'Tambah Limit',
                'count' => array_sum(array_column($limitStages, 'count')),
                'stages' => $limitStages,
            ];
        }

        if ($permissions->contains('aktivasi.approve-ketua')) {
            $tabs[] = [
                'key' => 'aktivasi',
                'label' => 'Aktivasi',
                'count' => (int) ($notif['menunggu_aktivasi'] ?? 0),
                'stages' => [],
            ];
        }

        if ($permissions->contains('penghapusan-pinjaman.lihat')) {
            $tabs[] = [
                'key' => 'penghapusan',
                'label' => 'Penghapusan',
                'count' => (int) ($notif['menunggu_penghapusan_pinjaman'] ?? 0),
                'stages' => [],
            ];
        }

        return $tabs;
    }

    /**
     * Muat antrean terpadu (Konsep A — Unified Single-Stream Inbox) sesuai izin user.
     * View 'menunggu' (default) hanya memuat item pending + antrean penghapusan berjalan;
     * view 'riwayat' memuat riwayat keputusan hanya saat tab Riwayat dibuka.
     *
     * @param  Collection<int, string>  $permissions
     * @return array<string, mixed>
     */
    public function muatInboxTerpadu(User $user, Collection $permissions, string $view = 'menunggu'): array
    {
        $butuhKasRingkas = $permissions->contains('pinjaman.tinjau-bendahara')
            || $permissions->contains('pinjaman.approve-ketua')
            || $permissions->contains('pinjaman.cairkan')
            || $permissions->contains('tabungan.kelola');

        $kasRingkas = null;
        if ($butuhKasRingkas) {
            $kas = KasKoperasi::first();
            $ringkasanPinjaman = $this->ringkasanPinjaman->ringkasan();
            $kasRingkas = [
                'saldo_bank' => (float) ($kas?->saldo_bank ?? 0),
                'saldo_kas_kecil' => (float) ($kas?->saldo_kas_kecil ?? 0),
                'total_antrean_pinjaman' => (float) ($ringkasanPinjaman['total_antrean'] ?? 0),
                'jumlah_antrean_pinjaman' => (int) ($ringkasanPinjaman['jumlah_antrean'] ?? 0),
                'persen_terpakai' => (float) ($ringkasanPinjaman['persen_terpakai'] ?? 0),
                'sisa_proyeksi' => (float) ($ringkasanPinjaman['sisa_proyeksi'] ?? 0),
                'status_likuiditas' => (string) ($ringkasanPinjaman['status_likuiditas'] ?? 'sehat'),
            ];
        }

        $ketentuanAktivasi = $permissions->contains('aktivasi.approve-ketua')
            ? [
                'simpanan_pokok' => (float) (SettingSimpanan::where('jenis', 'pokok')->value('nominal') ?? 50_000),
                'simpanan_wajib' => (float) (SettingSimpanan::where('jenis', 'wajib')->value('nominal') ?? 45_000),
                'dana_sosial' => (float) (SettingSimpanan::where('jenis', 'dana_sosial')->value('nominal') ?? 5_000),
                'limit_awal' => (float) (SettingLimitPinjaman::orderBy('min_bulan')->value('limit_maksimal') ?? 1_000_000),
            ]
            : null;

        $bisaAjukanPenghapusan = $user->can('penghapusan-pinjaman.ajukan');
        $peranPenghapusan = $this->labelPeranUser($user);
        $pinjamanMenunggak = $bisaAjukanPenghapusan ? $this->penghapusanService->daftarPinjamanMenunggak()->values()->all() : [];

        $penghapusanLists = $permissions->contains('penghapusan-pinjaman.lihat')
            ? $this->kumpulkanPenghapusan($user, $peranPenghapusan)
            : ['menungguSaya' => collect(), 'sedangBerjalan' => collect(), 'riwayatSelesai' => collect()];

        $antrean = $view === 'menunggu'
            ? $this->kumpulkanAntreanMenunggu($permissions, $penghapusanLists['menungguSaya'])
            : [];

        $riwayat = $view === 'riwayat'
            ? $this->kumpulkanRiwayat($permissions, $penghapusanLists['riwayatSelesai'])
            : [];

        return [
            'kasRingkas' => $kasRingkas,
            'ketentuanAktivasi' => $ketentuanAktivasi,
            'bisaAjukanPenghapusan' => $bisaAjukanPenghapusan,
            'peranPenghapusan' => $peranPenghapusan,
            'pinjamanMenunggak' => $pinjamanMenunggak,
            'sedangBerjalanPenghapusan' => $penghapusanLists['sedangBerjalan']->values()->all(),
            'antrean' => $antrean,
            'riwayat' => $riwayat,
        ];
    }

    /**
     * @param  Collection<int, string>  $permissions
     * @param  Collection<int, array<string, mixed>>  $penghapusanMenunggu
     * @return array<int, array<string, mixed>>
     */
    private function kumpulkanAntreanMenunggu(Collection $permissions, Collection $penghapusanMenunggu): array
    {
        $items = collect();

        // 1. Pinjaman (3 jenjang)
        if ($permissions->contains('pinjaman.tinjau-admin')) {
            Pinjaman::with('anggota')->where('status', 'diajukan')->get()
                ->each(fn (Pinjaman $p) => $items->push($this->envelopePinjaman($p, 'admin', 'Sekretaris', 'Verifikasi')));
        }
        if ($permissions->contains('pinjaman.tinjau-bendahara')) {
            Pinjaman::with('anggota')->where('status', 'approved_admin')->get()
                ->each(fn (Pinjaman $p) => $items->push($this->envelopePinjaman($p, 'bendahara', 'Bendahara', 'Tinjau')));
        }
        if ($permissions->contains('pinjaman.approve-ketua')) {
            Pinjaman::with('anggota')->where('status', 'approved_bendahara')->where('cair_oleh_bendahara', false)->get()
                ->each(fn (Pinjaman $p) => $items->push($this->envelopePinjaman($p, 'ketua', 'Ketua', 'Putuskan')));
        }

        // 2. Pencairan Dana (Pinjaman & Penarikan Tabungan approved_ketua)
        if ($permissions->contains('pinjaman.cairkan')) {
            Pinjaman::with('anggota')->where('status', 'approved_ketua')->get()
                ->each(function (Pinjaman $p) use ($items) {
                    $detail = $this->formatPinjaman->format($p, withLimit: false);
                    $detail['disetujui_ketua_pada'] = $p->disetujui_ketua_pada?->format('d M Y H:i');
                    $detail['catatan_ketua'] = $p->catatan_ketua;
                    $nominal = (float) ($detail['nominal_disetujui'] ?? $p->nominal);
                    $tenor = (int) ($detail['tenor_disetujui'] ?? $p->tenor_bulan);

                    $items->push([
                        'uid' => "pencairan-pinjaman-{$p->id}",
                        'kategori' => 'pencairan',
                        'kategori_label' => 'Pencairan Dana',
                        'jenis_kode' => 'cair_pinjaman',
                        'jenis_label' => 'Pencairan Pinjaman',
                        'warna' => 'emerald',
                        'tahap' => 'bendahara',
                        'tahap_label' => 'Bendahara',
                        'anggota' => $this->ringkasAnggota($p->anggota),
                        'ringkasan_utama' => 'Rp '.number_format($nominal, 0, ',', '.'),
                        'ringkasan_sub' => trim("{$tenor} bln".($p->snapshot_bank ? " • {$p->snapshot_bank} {$p->snapshot_no_rekening}" : '')),
                        'is_nominal' => true,
                        'nada_nominal' => 'default',
                        'tanggal' => ($p->disetujui_ketua_pada ?? $p->tanggal_pengajuan)?->format('d M Y') ?? '-',
                        'sort_ts' => ($p->disetujui_ketua_pada ?? $p->tanggal_pengajuan)?->timestamp ?? 0,
                        'status' => $p->status,
                        'aksi_label' => 'Cairkan',
                        'detail' => $detail,
                    ]);
                });
        }

        if ($permissions->contains('tabungan.kelola')) {
            PengajuanTransaksiTabungan::with([
                'anggota:id,nama,no_karyawan,cabang,foto_url',
                'rekening:id,no_rekening,saldo,tanggal_buka',
                'adminOleh:id,name',
                'bendaharaOleh:id,name',
                'ketuaOleh:id,name',
            ])
                ->where('status', 'approved_ketua')
                ->whereIn('jenis', PengajuanTransaksiTabungan::JENIS_PENARIKAN)
                ->get()
                ->each(fn (PengajuanTransaksiTabungan $p) => $items->push(
                    $this->envelopeTabunganTransaksi($p, 'pencairan', 'Pencairan Dana', 'cair_tabungan', 'bendahara', 'Bendahara', 'Cairkan')
                ));
        }

        if ($permissions->contains('pinjaman.cairkan')) {
            Anggota::where('status_pencairan_resign', 'menunggu')
                ->orderBy('tanggal_resign')
                ->orderBy('id')
                ->get()
                ->each(fn (Anggota $a) => $items->push($this->envelopePencairanResign($a)));
        }

        // 3. Tabungan (Admin, Bendahara, Ketua)
        if ($permissions->contains('tabungan.tinjau-admin')) {
            RekeningTabungan::with('anggota:id,nama,no_karyawan,cabang,foto_url,tanggal_jadi_anggota')
                ->where('status', 'diajukan')->get()
                ->each(fn (RekeningTabungan $r) => $items->push($this->envelopeTabunganBuka($r, 'admin', 'Sekretaris', 'Verifikasi')));

            PengajuanTransaksiTabungan::with(['anggota:id,nama,no_karyawan,cabang,foto_url', 'rekening:id,no_rekening,saldo,tanggal_buka'])
                ->where('status', 'menunggu')->whereIn('jenis', PengajuanTransaksiTabungan::JENIS_PENARIKAN)->get()
                ->each(fn (PengajuanTransaksiTabungan $p) => $items->push(
                    $this->envelopeTabunganTransaksi($p, 'tabungan', 'Tabungan', 'tabungan_tarik', 'admin', 'Sekretaris', 'Verifikasi')
                ));
        }

        if ($permissions->contains('tabungan.kelola') || $permissions->contains('tabungan.tinjau-bendahara')) {
            RekeningTabungan::with(['anggota:id,nama,no_karyawan,cabang,foto_url,tanggal_jadi_anggota', 'adminOleh:id,name'])
                ->where('status', 'approved_admin')->get()
                ->each(fn (RekeningTabungan $r) => $items->push($this->envelopeTabunganBuka($r, 'bendahara', 'Bendahara', 'Tinjau')));

            PengajuanTransaksiTabungan::with(['anggota:id,nama,no_karyawan,cabang,foto_url', 'rekening:id,no_rekening,saldo,tanggal_buka'])
                ->where('status', 'menunggu')->where('jenis', 'setor')->get()
                ->each(fn (PengajuanTransaksiTabungan $p) => $items->push(
                    $this->envelopeTabunganTransaksi($p, 'tabungan', 'Tabungan', 'tabungan_setor', 'bendahara', 'Bendahara', 'Periksa Bukti')
                ));

            PengajuanTransaksiTabungan::with(['anggota:id,nama,no_karyawan,cabang,foto_url', 'rekening:id,no_rekening,saldo,tanggal_buka', 'adminOleh:id,name'])
                ->where('status', 'approved_admin')->whereIn('jenis', PengajuanTransaksiTabungan::JENIS_PENARIKAN)->get()
                ->each(fn (PengajuanTransaksiTabungan $p) => $items->push(
                    $this->envelopeTabunganTransaksi($p, 'tabungan', 'Tabungan', 'tabungan_tarik', 'bendahara', 'Bendahara', 'Tinjau')
                ));
        }

        if ($permissions->contains('tabungan.approve-ketua')) {
            RekeningTabungan::with(['anggota:id,nama,no_karyawan,cabang,foto_url,tanggal_jadi_anggota', 'adminOleh:id,name', 'bendaharaOleh:id,name'])
                ->where('status', 'approved_bendahara')->get()
                ->each(fn (RekeningTabungan $r) => $items->push($this->envelopeTabunganBuka($r, 'ketua', 'Ketua', 'Putuskan')));

            PengajuanTransaksiTabungan::with(['anggota:id,nama,no_karyawan,cabang,foto_url', 'rekening:id,no_rekening,saldo,tanggal_buka', 'adminOleh:id,name', 'bendaharaOleh:id,name'])
                ->where('status', 'approved_bendahara')->whereIn('jenis', PengajuanTransaksiTabungan::JENIS_PENARIKAN)->get()
                ->each(fn (PengajuanTransaksiTabungan $p) => $items->push(
                    $this->envelopeTabunganTransaksi($p, 'tabungan', 'Tabungan', 'tabungan_tarik', 'ketua', 'Ketua', 'Putuskan')
                ));
        }

        // 4. Ubah Tenor / Percepatan
        if ($permissions->contains('pinjaman.tinjau-bendahara')) {
            PengajuanPercepatan::with('pinjaman.anggota')->where('status', 'diajukan')->get()
                ->each(fn (PengajuanPercepatan $p) => $items->push($this->envelopePercepatan($p, 'bendahara', 'Bendahara', 'Tinjau')));
        }
        if ($permissions->contains('pinjaman.approve-ketua')) {
            PengajuanPercepatan::with('pinjaman.anggota')->where('status', 'approved_bendahara')->get()
                ->each(fn (PengajuanPercepatan $p) => $items->push($this->envelopePercepatan($p, 'ketua', 'Ketua', 'Putuskan')));
        }

        // 5. Tambah Limit
        if ($permissions->contains('limit.tinjau-bendahara')) {
            $limitBendahara = PengajuanLimit::with('anggota')->where('status', 'diajukan')->get();
            $cache = KetuaPengajuanLimitController::preloadPinjaman($limitBendahara);
            $limitBendahara->each(fn (PengajuanLimit $p) => $items->push($this->envelopeLimit($p, $cache, 'bendahara', 'Bendahara', 'Tinjau')));
        }
        if ($permissions->contains('limit.approve-ketua')) {
            $limitKetua = PengajuanLimit::with('anggota')->where('status', 'approved_bendahara')->get();
            $cache = KetuaPengajuanLimitController::preloadPinjaman($limitKetua);
            $limitKetua->each(fn (PengajuanLimit $p) => $items->push($this->envelopeLimit($p, $cache, 'ketua', 'Ketua', 'Putuskan')));
        }

        // 6. Aktivasi Anggota
        if ($permissions->contains('aktivasi.approve-ketua')) {
            PengajuanAktivasi::with(['anggota.user'])->where('status', 'diajukan')->get()
                ->each(fn (PengajuanAktivasi $p) => $items->push($this->envelopeAktivasi($p, 'Setujui')));
        }

        // 7. Penghapusan Pinjaman (khusus yang bisa diproses oleh user ini)
        $penghapusanMenunggu->each(fn ($item) => $items->push($item));

        return $items
            ->sortBy([['sort_ts', 'asc'], ['uid', 'asc']])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, string>  $permissions
     * @param  Collection<int, array<string, mixed>>  $penghapusanRiwayat
     * @return array<int, array<string, mixed>>
     */
    private function kumpulkanRiwayat(Collection $permissions, Collection $penghapusanRiwayat): array
    {
        $items = collect();

        if ($permissions->contains('pinjaman.tinjau-admin')) {
            Pinjaman::with('anggota')->whereIn('status', ['approved_admin', 'approved_bendahara', 'aktif', 'lunas', 'ditolak'])
                ->whereNotNull('catatan_admin')->latest('updated_at')->take(12)->get()
                ->each(fn (Pinjaman $p) => $items->push($this->envelopePinjaman($p, 'admin', 'Sekretaris', 'Detail', true)));
        }
        if ($permissions->contains('pinjaman.tinjau-bendahara')) {
            Pinjaman::with('anggota')->whereIn('status', ['approved_bendahara', 'approved_ketua', 'aktif', 'lunas', 'ditolak', 'dihapusbukukan'])
                ->whereNotNull('catatan_bendahara')->latest('updated_at')->take(12)->get()
                ->each(fn (Pinjaman $p) => $items->push($this->envelopePinjaman($p, 'bendahara', 'Bendahara', 'Detail', true)));
        }
        if ($permissions->contains('pinjaman.approve-ketua')) {
            Pinjaman::with('anggota')->whereIn('status', ['approved_ketua', 'aktif', 'lunas', 'ditolak', 'dihapusbukukan'])
                ->whereNotNull('catatan_ketua')->latest('updated_at')->take(12)->get()
                ->each(fn (Pinjaman $p) => $items->push($this->envelopePinjaman($p, 'ketua', 'Ketua', 'Detail', true)));
        }

        if ($permissions->contains('pinjaman.cairkan')) {
            Pinjaman::with(['anggota', 'dicairkanOleh:id,name'])
                ->whereIn('status', ['aktif', 'lunas', 'dihapusbukukan'])
                ->whereNotNull('tanggal_pencairan')
                ->latest('tanggal_pencairan')
                ->latest('id')
                ->take(15)
                ->get()
                ->each(function (Pinjaman $p) use ($items) {
                    $detail = [
                        'id' => $p->id,
                        'nomor_dokumen' => $p->nomor_dokumen,
                        'nominal' => (float) $p->nominal,
                        'tenor_bulan' => (int) $p->tenor_bulan,
                        'status' => $p->status,
                        'tanggal_pencairan' => $p->tanggal_pencairan?->format('d M Y'),
                        'dicairkan_oleh' => $p->dicairkanOleh?->name ?? '-',
                        'catatan_pencairan' => $p->catatan_pencairan,
                        'ada_bukti_pencairan' => (bool) $p->bukti_pencairan_path,
                        'bukti_pencairan_url' => $p->bukti_pencairan_path ? route('pinjaman.bukti-pencairan', $p->id) : null,
                        'rekening' => [
                            'bank' => $p->snapshot_bank,
                            'no_rekening' => $p->snapshot_no_rekening,
                            'atas_nama' => $p->snapshot_atas_nama,
                        ],
                        'anggota' => [
                            'nama' => $p->anggota?->nama ?? '-',
                            'no_karyawan' => $p->anggota?->no_karyawan ?? '-',
                            'cabang' => $p->anggota?->cabang ?? '-',
                            'foto_url' => $p->anggota?->foto_url,
                        ],
                    ];

                    $items->push([
                        'uid' => "riwayat-pencairan-pinjaman-{$p->id}",
                        'kategori' => 'pencairan',
                        'kategori_label' => 'Pencairan Dana',
                        'jenis_kode' => 'cair_pinjaman',
                        'jenis_label' => 'Pencairan Pinjaman',
                        'warna' => 'emerald',
                        'tahap' => 'bendahara',
                        'tahap_label' => 'Bendahara',
                        'anggota' => $this->ringkasAnggota($p->anggota),
                        'ringkasan_utama' => 'Rp '.number_format((float) $p->nominal, 0, ',', '.'),
                        'ringkasan_sub' => "Cair {$detail['tanggal_pencairan']} • {$p->snapshot_bank}",
                        'is_nominal' => true,
                        'nada_nominal' => 'default',
                        'tanggal' => $detail['tanggal_pencairan'] ?? '-',
                        'sort_ts' => ($p->tanggal_pencairan ?? $p->updated_at)?->timestamp ?? 0,
                        'status' => $p->status,
                        'aksi_label' => 'Detail',
                        'detail' => $detail,
                    ]);
                });

            Anggota::with('dicairkanResignOleh:id,name')
                ->where('status_pencairan_resign', 'dicairkan')
                ->latest('tanggal_pencairan_resign')
                ->latest('id')
                ->take(15)
                ->get()
                ->each(fn (Anggota $a) => $items->push($this->envelopePencairanResign($a, true)));
        }

        if ($permissions->contains('tabungan.kelola') || $permissions->contains('tabungan.approve-ketua') || $permissions->contains('tabungan.tinjau-admin')) {
            RekeningTabungan::with(['anggota:id,nama,no_karyawan,cabang,foto_url,tanggal_jadi_anggota', 'adminOleh:id,name', 'bendaharaOleh:id,name'])
                ->whereIn('status', ['aktif', 'ditolak'])->latest('updated_at')->take(10)->get()
                ->each(fn (RekeningTabungan $r) => $items->push($this->envelopeTabunganBuka($r, 'ketua', 'Tabungan', 'Detail', true)));

            PengajuanTransaksiTabungan::with([
                'anggota:id,nama,no_karyawan,cabang,foto_url',
                'rekening:id,no_rekening,saldo,tanggal_buka',
                'adminOleh:id,name',
                'bendaharaOleh:id,name',
                'ketuaOleh:id,name',
            ])
                ->whereIn('status', ['disetujui', 'ditolak'])
                ->latest('updated_at')
                ->take(12)
                ->get()
                ->each(function (PengajuanTransaksiTabungan $p) use ($items) {
                    $jenisKode = $p->jenis === 'setor' ? 'tabungan_setor' : 'tabungan_tarik';
                    $items->push($this->envelopeTabunganTransaksi($p, 'tabungan', 'Tabungan', $jenisKode, 'bendahara', 'Tabungan', 'Detail', true));
                });
        }

        if ($permissions->contains('pinjaman.tinjau-bendahara')) {
            PengajuanPercepatan::with('pinjaman.anggota')->whereIn('status', ['approved_bendahara', 'aktif', 'ditolak'])
                ->whereNotNull('catatan_bendahara')->latest('updated_at')->take(10)->get()
                ->each(fn (PengajuanPercepatan $p) => $items->push($this->envelopePercepatan($p, 'bendahara', 'Bendahara', 'Detail', true)));
        }
        if ($permissions->contains('pinjaman.approve-ketua')) {
            PengajuanPercepatan::with('pinjaman.anggota')->whereIn('status', ['aktif', 'ditolak'])
                ->whereNotNull('catatan_ketua')->latest('updated_at')->take(10)->get()
                ->each(fn (PengajuanPercepatan $p) => $items->push($this->envelopePercepatan($p, 'ketua', 'Ketua', 'Detail', true)));
        }

        if ($permissions->contains('limit.tinjau-bendahara')) {
            $raw = PengajuanLimit::with('anggota')->whereIn('status', ['approved_bendahara', 'disetujui', 'ditolak'])
                ->whereNotNull('catatan_bendahara')->latest('updated_at')->take(10)->get();
            $cache = KetuaPengajuanLimitController::preloadPinjaman($raw);
            $raw->each(fn (PengajuanLimit $p) => $items->push($this->envelopeLimit($p, $cache, 'bendahara', 'Bendahara', 'Detail', true)));
        }
        if ($permissions->contains('limit.approve-ketua')) {
            $raw = PengajuanLimit::with('anggota')->whereIn('status', ['disetujui', 'ditolak'])
                ->latest('updated_at')->take(10)->get();
            $cache = KetuaPengajuanLimitController::preloadPinjaman($raw);
            $raw->each(fn (PengajuanLimit $p) => $items->push($this->envelopeLimit($p, $cache, 'ketua', 'Ketua', 'Detail', true)));
        }

        if ($permissions->contains('aktivasi.approve-ketua')) {
            PengajuanAktivasi::with(['anggota.user'])->whereIn('status', ['disetujui', 'ditolak'])
                ->latest('updated_at')->take(10)->get()
                ->each(fn (PengajuanAktivasi $p) => $items->push($this->envelopeAktivasi($p, 'Detail', true)));
        }

        $penghapusanRiwayat->take(15)->each(fn ($item) => $items->push($item));

        return $items
            ->sortByDesc('sort_ts')
            ->take(50)
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function envelopePinjaman(Pinjaman $p, string $tahap, string $tahapLabel, string $aksiLabel, bool $isRiwayat = false): array
    {
        $detail = $this->formatPinjaman->format($p, withLimit: false);
        $nominal = (float) ($detail['nominal_disetujui_bendahara'] ?? $detail['nominal_diminta'] ?? $p->nominal);
        $tenor = (int) ($detail['tenor_disetujui_bendahara'] ?? $detail['tenor_diminta'] ?? $p->tenor_bulan);

        return [
            'uid' => ($isRiwayat ? 'riwayat-' : '')."pinjaman-{$tahap}-{$p->id}",
            'kategori' => 'pinjaman',
            'kategori_label' => 'Pinjaman',
            'jenis_kode' => 'pinjaman_baru',
            'jenis_label' => 'Pengajuan Pinjaman',
            'warna' => 'navy',
            'tahap' => $tahap,
            'tahap_label' => $tahapLabel,
            'anggota' => $this->ringkasAnggota($p->anggota),
            'ringkasan_utama' => 'Rp '.number_format($nominal, 0, ',', '.'),
            'ringkasan_sub' => trim("Tenor {$tenor} bln".($p->keperluan ? " • {$p->keperluan}" : '')),
            'is_nominal' => true,
            'nada_nominal' => 'default',
            'tanggal' => $p->tanggal_pengajuan?->format('d M Y') ?? '-',
            'sort_ts' => ($isRiwayat ? $p->updated_at : $p->tanggal_pengajuan)?->timestamp ?? 0,
            'status' => $isRiwayat ? $p->status : ($tahap === 'admin' ? 'pending' : $p->status),
            'aksi_label' => $aksiLabel,
            'detail' => $detail,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function envelopeTabunganBuka(RekeningTabungan $r, string $tahap, string $tahapLabel, string $aksiLabel, bool $isRiwayat = false): array
    {
        $detail = [
            'id' => $r->id,
            'no_rekening' => $r->no_rekening,
            'status' => $r->status,
            'suku_bunga_saat_buka' => (float) $r->suku_bunga_saat_buka,
            'versi_syarat' => $r->versi_syarat,
            'tanggal_pengajuan' => $r->tanggal_pengajuan?->format('d M Y'),
            'admin_oleh' => $r->adminOleh?->name,
            'catatan_admin' => $r->catatan_admin,
            'disetujui_admin_pada' => $r->disetujui_admin_pada?->format('d M Y H:i'),
            'bendahara_oleh' => $r->bendaharaOleh?->name,
            'catatan_bendahara' => $r->catatan_bendahara,
            'disetujui_bendahara_pada' => $r->disetujui_bendahara_pada?->format('d M Y H:i'),
            'anggota' => $r->anggota ? [
                'id' => $r->anggota->id,
                'nama' => $r->anggota->nama,
                'no_karyawan' => $r->anggota->no_karyawan,
                'cabang' => $r->anggota->cabang,
                'foto_url' => $r->anggota->foto_url,
                'tanggal_jadi_anggota' => $r->anggota->tanggal_jadi_anggota?->format('d M Y'),
            ] : null,
        ];

        return [
            'uid' => ($isRiwayat ? 'riwayat-' : '')."tabungan-buka-{$tahap}-{$r->id}",
            'kategori' => 'tabungan',
            'kategori_label' => 'Tabungan',
            'jenis_kode' => 'tabungan_buka',
            'jenis_label' => 'Buka Rekening',
            'warna' => 'sky',
            'tahap' => $tahap,
            'tahap_label' => $tahapLabel,
            'anggota' => $this->ringkasAnggota($r->anggota),
            'ringkasan_utama' => 'Pembukaan Rekening',
            'ringkasan_sub' => "Tarif {$detail['suku_bunga_saat_buka']}%/thn • S&K {$r->versi_syarat}",
            'is_nominal' => false,
            'nada_nominal' => 'default',
            'tanggal' => $detail['tanggal_pengajuan'] ?? '-',
            'sort_ts' => ($isRiwayat ? $r->updated_at : ($r->tanggal_pengajuan ?? $r->created_at))?->timestamp ?? 0,
            'status' => $r->status,
            'aksi_label' => $aksiLabel,
            'detail' => $detail,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function envelopeTabunganTransaksi(
        PengajuanTransaksiTabungan $p,
        string $kategori,
        string $kategoriLabel,
        string $jenisKode,
        string $tahap,
        string $tahapLabel,
        string $aksiLabel,
        bool $isRiwayat = false
    ): array {
        $detail = $this->mapPengajuanTabunganBendahara()($p);
        $isSetor = $p->jenis === 'setor';
        $prefixNominal = $isSetor ? '+' : '';
        $jenisLabel = match ($jenisKode) {
            'cair_tabungan' => $p->jenis === 'tutup_rekening' ? 'Cair Tutup Rekening' : 'Pencairan Tabungan',
            'tabungan_setor' => 'Setoran Tabungan',
            default => $p->jenis === 'tutup_rekening' ? 'Tutup Rekening' : 'Penarikan Tabungan',
        };

        $tujuan = $p->rekening_tujuan_snapshot
            ? "{$p->rekening_tujuan_snapshot['nama_bank']} {$p->rekening_tujuan_snapshot['no_rekening']}"
            : "Via {$p->kanal}";

        return [
            'uid' => ($isRiwayat ? 'riwayat-' : '')."{$kategori}-trx-{$tahap}-{$p->id}",
            'kategori' => $kategori,
            'kategori_label' => $kategoriLabel,
            'jenis_kode' => $jenisKode,
            'jenis_label' => $jenisLabel,
            'warna' => $kategori === 'pencairan' ? 'emerald' : 'sky',
            'tahap' => $tahap,
            'tahap_label' => $tahapLabel,
            'anggota' => $this->ringkasAnggota($p->anggota),
            'ringkasan_utama' => $prefixNominal.'Rp '.number_format((float) $p->nominal, 0, ',', '.'),
            'ringkasan_sub' => trim(($p->rekening?->no_rekening ?? '-').' • '.($isSetor ? "Via {$p->kanal}" : $tujuan)),
            'is_nominal' => true,
            'nada_nominal' => $isSetor ? 'emerald' : 'rose',
            'tanggal' => ($p->tanggal_transaksi ?? $p->created_at)?->format('d M Y') ?? '-',
            'sort_ts' => ($isRiwayat ? $p->updated_at : ($p->disetujui_ketua_pada ?? $p->created_at))?->timestamp ?? 0,
            'status' => $p->status,
            'aksi_label' => $aksiLabel,
            'detail' => $detail,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function envelopePercepatan(PengajuanPercepatan $p, string $tahap, string $tahapLabel, string $aksiLabel, bool $isRiwayat = false): array
    {
        $detail = $this->formatPercepatan($p, $tahap === 'bendahara' ? 'diajukan' : 'approved_bendahara', ! $isRiwayat);

        return [
            'uid' => ($isRiwayat ? 'riwayat-' : '')."percepatan-{$tahap}-{$p->id}",
            'kategori' => 'percepatan',
            'kategori_label' => 'Ubah Tenor',
            'jenis_kode' => 'percepatan',
            'jenis_label' => $detail['tipe_label'],
            'warna' => 'violet',
            'tahap' => $tahap,
            'tahap_label' => $tahapLabel,
            'anggota' => $this->ringkasAnggota($p->pinjaman?->anggota),
            'ringkasan_utama' => "{$p->tenor_lama} → {$p->tenor_baru} bln",
            'ringkasan_sub' => "Pinjaman #{$p->pinjaman_id} • Sisa {$detail['pinjaman']['sisa_angsuran']}x cicilan",
            'is_nominal' => false,
            'nada_nominal' => 'default',
            'tanggal' => $detail['tanggal_pengajuan'],
            'sort_ts' => ($isRiwayat ? $p->updated_at : $p->tanggal_pengajuan)?->timestamp ?? 0,
            'status' => $isRiwayat ? ($p->status === 'ditolak' ? 'ditolak' : 'lunas') : 'pending',
            'aksi_label' => $aksiLabel,
            'detail' => $detail,
        ];
    }

    /**
     * @param  array<string, mixed>  $cache
     * @return array<string, mixed>
     */
    private function envelopeLimit(PengajuanLimit $p, array $cache, string $tahap, string $tahapLabel, string $aksiLabel, bool $isRiwayat = false): array
    {
        $detail = KetuaPengajuanLimitController::formatItem($cache)($p);
        $targetLimit = $tahap === 'ketua' && $p->limit_disetujui_bendahara !== null
            ? (float) $p->limit_disetujui_bendahara
            : (float) $p->limit_diminta;

        return [
            'uid' => ($isRiwayat ? 'riwayat-' : '')."limit-{$tahap}-{$p->id}",
            'kategori' => 'limit',
            'kategori_label' => 'Tambah Limit',
            'jenis_kode' => 'limit',
            'jenis_label' => 'Kenaikan Limit',
            'warna' => 'amber',
            'tahap' => $tahap,
            'tahap_label' => $tahapLabel,
            'anggota' => $this->ringkasAnggota($p->anggota),
            'ringkasan_utama' => 'Rp '.number_format($targetLimit, 0, ',', '.'),
            'ringkasan_sub' => 'Dari Rp '.number_format((float) $p->limit_saat_ini, 0, ',', '.'),
            'is_nominal' => true,
            'nada_nominal' => 'default',
            'tanggal' => $detail['tanggal_pengajuan'],
            'sort_ts' => ($isRiwayat ? $p->updated_at : $p->tanggal_pengajuan)?->timestamp ?? 0,
            'status' => $isRiwayat ? ($p->status === 'ditolak' ? 'ditolak' : ($p->status === 'approved_bendahara' ? 'approved_bendahara' : 'disetujui')) : ($tahap === 'bendahara' ? 'pending' : $p->status),
            'aksi_label' => $aksiLabel,
            'detail' => $detail,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function envelopeAktivasi(PengajuanAktivasi $p, string $aksiLabel, bool $isRiwayat = false): array
    {
        $detail = KetuaAktivasiController::formatItem()($p);
        $reaktivasi = $detail['anggota']['is_reaktivasi'] ?? false;

        return [
            'uid' => ($isRiwayat ? 'riwayat-' : '')."aktivasi-{$p->id}",
            'kategori' => 'aktivasi',
            'kategori_label' => 'Aktivasi',
            'jenis_kode' => 'aktivasi',
            'jenis_label' => $reaktivasi ? 'Re-Aktivasi Anggota' : 'Aktivasi Anggota Baru',
            'warna' => 'teal',
            'tahap' => 'ketua',
            'tahap_label' => 'Ketua',
            'anggota' => $this->ringkasAnggota($p->anggota),
            'ringkasan_utama' => $reaktivasi ? 'Re-Aktivasi' : 'Anggota Baru',
            'ringkasan_sub' => $detail['anggota']['cabang'] ?? '-',
            'is_nominal' => false,
            'nada_nominal' => 'default',
            'tanggal' => $detail['tanggal_pengajuan'],
            'sort_ts' => ($isRiwayat ? $p->updated_at : $p->tanggal_pengajuan)?->timestamp ?? 0,
            'status' => $p->status === 'diajukan' ? 'pending' : $p->status,
            'aksi_label' => $aksiLabel,
            'detail' => $detail,
        ];
    }

    /**
     * @return array{menungguSaya: Collection, sedangBerjalan: Collection, riwayatSelesai: Collection}
     */
    private function kumpulkanPenghapusan(User $user, string $peranLabel): array
    {
        $daftar = PengajuanPenghapusanPinjaman::with([
            'pinjaman',
            'anggota',
            'pengaju:id,name,no_karyawan',
            'approverBendahara:id,name,no_karyawan',
            'approverKetua:id,name,no_karyawan',
            'approverPengawas3:id,name,no_karyawan',
            'approverPengawas2:id,name,no_karyawan',
            'approverPengawas1:id,name,no_karyawan',
            'penolak:id,name,no_karyawan',
        ])
            ->latest('created_at')
            ->latest('id')
            ->get()
            ->map(function (PengajuanPenghapusanPinjaman $p) use ($user, $peranLabel) {
                $detail = [
                    'id' => $p->id,
                    'nomor_dokumen' => $p->nomor_dokumen,
                    'pinjaman_id' => $p->pinjaman_id,
                    'pinjaman_nomor_dokumen' => $p->pinjaman?->nomor_dokumen ?? ('#'.$p->pinjaman_id),
                    'tanggal_pengajuan' => $p->tanggal_pengajuan?->format('d M Y') ?? '-',
                    'disetujui_pada' => $p->disetujui_pada?->format('d M Y, H:i'),
                    'alasan' => $p->alasan,
                    'status' => $p->status,
                    'status_label' => PengajuanPenghapusanPinjaman::STATUS_LABEL[$p->status] ?? $p->status,
                    'nominal_pinjaman' => (float) $p->nominal_pinjaman,
                    'total_pokok_terbayar' => (float) $p->total_pokok_terbayar,
                    'total_bunga_terbayar' => (float) $p->total_bunga_terbayar,
                    'sisa_cicilan' => (int) $p->sisa_cicilan,
                    'sisa_pokok' => (float) $p->sisa_pokok,
                    'sisa_bunga' => (float) $p->sisa_bunga,
                    'sisa_total_tagihan' => (float) $p->sisa_total_tagihan,
                    'anggota' => [
                        'id' => $p->anggota?->id,
                        'nama' => $p->anggota?->nama ?? '-',
                        'no_karyawan' => $p->anggota?->no_karyawan ?? '-',
                        'no_anggota' => $p->anggota?->no_anggota ?? '-',
                        'cabang' => $p->anggota?->cabang ?? '-',
                        'foto_url' => $p->anggota?->foto_url,
                        'status' => $p->anggota?->status ?? '-',
                    ],
                    'pengaju_nama' => $p->pengaju?->name ?? '-',
                    'bisa_diproses' => $this->penghapusanService->bisaDiprosesOleh($p, $user),
                    'timeline' => $p->getTimelineApproval(),
                ];

                $bisaDiproses = $detail['bisa_diproses'];

                return [
                    'raw_status' => $p->status,
                    'bisa_diproses' => $bisaDiproses,
                    'envelope' => [
                        'uid' => "penghapusan-{$p->id}",
                        'kategori' => 'penghapusan',
                        'kategori_label' => 'Penghapusan',
                        'jenis_kode' => 'penghapusan',
                        'jenis_label' => 'Penghapusan Pinjaman',
                        'warna' => 'rose',
                        'tahap' => null,
                        'tahap_label' => $bisaDiproses ? $peranLabel : $detail['status_label'],
                        'anggota' => $this->ringkasAnggota($p->anggota),
                        'ringkasan_utama' => 'Rp '.number_format((float) $p->sisa_pokok, 0, ',', '.'),
                        'ringkasan_sub' => "{$detail['sisa_cicilan']}x sisa cicilan • {$detail['status_label']}",
                        'is_nominal' => true,
                        'nada_nominal' => 'rose',
                        'tanggal' => $detail['tanggal_pengajuan'],
                        'sort_ts' => ($p->disetujui_pada ?? $p->tanggal_pengajuan ?? $p->created_at)?->timestamp ?? 0,
                        'status' => $p->status,
                        'aksi_label' => $bisaDiproses ? 'Putuskan' : 'Detail',
                        'detail' => $detail,
                    ],
                ];
            });

        $berjalanStatus = [
            'diajukan',
            'approved_bendahara',
            'approved_ketua',
            'approved_pengawas_3',
            'approved_pengawas_2',
        ];

        return [
            'menungguSaya' => $daftar->where('bisa_diproses', true)->pluck('envelope')->values(),
            'sedangBerjalan' => $daftar->whereIn('raw_status', $berjalanStatus)->pluck('envelope')->values(),
            'riwayatSelesai' => $daftar->whereIn('raw_status', ['disetujui', 'ditolak'])->pluck('envelope')->values(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function ringkasAnggota($anggota): array
    {
        return [
            'nama' => $anggota?->nama ?? '-',
            'no_karyawan' => $anggota?->no_karyawan ?? '-',
            'cabang' => $anggota?->cabang ?? '-',
            'foto_url' => $anggota?->foto_url,
        ];
    }

    private function mapPengajuanTabunganBendahara(): \Closure
    {
        return fn (PengajuanTransaksiTabungan $p) => [
            'id' => $p->id,
            'rekening_id' => $p->rekening_tabungan_id,
            'no_rekening' => $p->rekening?->no_rekening,
            'saldo_rekening' => (float) ($p->rekening?->saldo ?? 0),
            'tanggal_buka' => $p->rekening?->tanggal_buka?->format('d M Y'),
            'jenis' => $p->jenis,
            'kanal' => $p->kanal,
            'nominal' => (float) $p->nominal,
            'tanggal_transaksi' => $p->tanggal_transaksi?->format('d M Y'),
            'catatan_anggota' => $p->catatan_anggota,
            'rekening_tujuan' => $p->rekening_tujuan_snapshot,
            'status' => $p->status,
            'ada_bukti' => (bool) $p->bukti_path,
            'bukti_mime' => $p->bukti_mime,
            'bukti_nama_asli' => $p->bukti_nama_asli,
            'bukti_url' => $p->bukti_path ? route('tabungan.bukti.pengajuan', ['pengajuan' => $p->id, 'tipe' => 'setor']) : null,
            'admin_oleh' => $p->adminOleh?->name,
            'catatan_admin' => $p->catatan_admin,
            'disetujui_admin_pada' => $p->disetujui_admin_pada?->format('d M Y H:i'),
            'bendahara_oleh' => $p->bendaharaOleh?->name,
            'catatan_bendahara' => $p->catatan_bendahara,
            'disetujui_bendahara_pada' => $p->disetujui_bendahara_pada?->format('d M Y H:i'),
            'ketua_oleh' => $p->ketuaOleh?->name,
            'catatan_ketua' => $p->catatan_ketua,
            'disetujui_ketua_pada' => $p->disetujui_ketua_pada?->format('d M Y H:i'),
            'created_at' => $p->created_at?->format('d M Y H:i'),
            'anggota' => $p->anggota ? [
                'id' => $p->anggota->id,
                'nama' => $p->anggota->nama,
                'no_karyawan' => $p->anggota->no_karyawan,
                'cabang' => $p->anggota->cabang,
                'foto_url' => $p->anggota->foto_url,
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatPercepatan(PengajuanPercepatan $p, string $statusPreview, bool $denganPreview = true): array
    {
        $tipeLabel = [
            'percepat' => 'Percepat Pelunasan',
            'perpanjang' => 'Perpanjang Tenor',
            'lunas_total' => 'Lunas Sekarang',
        ][$p->tipe] ?? $p->tipe;

        $data = [
            'id' => $p->id,
            'tipe' => $p->tipe,
            'tipe_label' => $tipeLabel,
            'tenor_lama' => $p->tenor_lama,
            'tenor_baru' => $p->tenor_baru,
            'keterangan' => $p->keterangan,
            'status' => $p->status,
            'catatan_bendahara' => $p->catatan_bendahara,
            'catatan_ketua' => $p->catatan_ketua,
            'bulan_berlaku' => $p->bulan_berlaku,
            'nominal_final' => $p->nominal_final !== null ? (float) $p->nominal_final : null,
            'tanggal_pengajuan' => $p->tanggal_pengajuan->format('d M Y'),
            'pinjaman' => [
                'id' => $p->pinjaman->id,
                'nominal' => (float) $p->pinjaman->nominal,
                'persentase_bunga' => (float) $p->pinjaman->persentase_bunga,
                'tenor_bulan' => $p->pinjaman->tenor_bulan,
                'sisa_angsuran' => $p->pinjaman->sisaAngsuran(),
            ],
            'anggota' => [
                'nama' => $p->pinjaman->anggota->nama,
                'no_anggota' => $p->pinjaman->anggota->no_anggota,
                'no_karyawan' => $p->pinjaman->anggota->no_karyawan,
                'cabang' => $p->pinjaman->anggota->cabang,
                'foto_url' => $p->pinjaman->anggota->foto_url,
                'jabatan' => $p->pinjaman->anggota->jabatan,
                'lama_keanggotaan_tahun' => round($p->pinjaman->anggota->lama_keanggotaan_tahun, 1),
            ],
        ];

        if ($denganPreview && $p->status === $statusPreview) {
            $data['preview'] = $this->percepatanService->preview($p->pinjaman, $p->tipe, $p->tenor_baru);
        }

        if ($p->status === 'aktif') {
            $data['angsuran_baru'] = $p->angsuranBaru()->orderBy('cicilan_ke')->get()->map(fn ($a) => [
                'id' => $a->id,
                'cicilan_ke' => $a->cicilan_ke,
                'nominal_pokok' => (float) $a->nominal_pokok,
                'nominal_bunga' => (float) $a->nominal_bunga,
                'total_bayar' => (float) $a->total_bayar,
                'status' => $a->status,
                'tanggal_jatuh_tempo' => $a->tanggal_jatuh_tempo->format('Y-m-d'),
            ])->all();
        }

        return $data;
    }

    private function labelPeranUser(User $user): string
    {
        if ($user->hasRole('super_admin')) {
            return 'Super Admin';
        }
        if ($user->hasRole('pengawas')) {
            return $user->labelJabatanPengawas() ?? 'Pengawas';
        }
        if ($user->hasRole('ketua_koperasi')) {
            return 'Ketua Koperasi';
        }
        if ($user->hasRole('bendahara')) {
            return 'Bendahara';
        }
        if ($user->hasRole('sekretaris')) {
            return 'Sekretaris';
        }

        return 'Pengurus';
    }

    /**
     * @return array<string, mixed>
     */
    private function envelopePencairanResign(Anggota $a, bool $isRiwayat = false): array
    {
        $settlement = $a->resigned_settlement_json ?? [];
        $totalDikembalikan = round((float) ($settlement['total_dikembalikan'] ?? 0), 2);
        $docNo = $settlement['doc_no'] ?? '-';
        $bank = $a->resign_snapshot_bank ?? ($settlement['rekening_tujuan']['nama_bank'] ?? null);
        $noRek = $a->resign_snapshot_no_rekening ?? ($settlement['rekening_tujuan']['no_rekening'] ?? null);
        $atasNama = $a->resign_snapshot_atas_nama ?? ($settlement['rekening_tujuan']['atas_nama'] ?? null);

        $komponen = [];
        if (($settlement['kembali_pokok'] ?? 0) > 0 || ($settlement['kembali_wajib'] ?? 0) > 0) {
            $komponen[] = 'Simpanan';
        }
        if (($settlement['kembali_tabungan'] ?? 0) > 0) {
            $komponen[] = 'Tabungan';
        }
        $komponenLabel = ! empty($komponen) ? implode(' + ', $komponen) : 'Pengembalian';
        $rekRingkas = ($bank || $noRek) ? trim(($bank ?? '').' '.($noRek ?? '')) : null;

        $subBagian = $isRiwayat
            ? array_filter([
                $a->tanggal_pencairan_resign ? 'Cair '.$a->tanggal_pencairan_resign->format('d M Y') : null,
                $a->resign_akun_kas_pencairan === 'kas_kecil' ? 'Kas Kecil' : ($bank ?? 'Bank'),
                $docNo !== '-' ? $docNo : null,
            ])
            : array_filter([
                $komponenLabel,
                $rekRingkas,
                $docNo !== '-' ? $docNo : null,
            ]);

        return [
            'uid' => ($isRiwayat ? 'riwayat-' : '')."pencairan-resign-{$a->id}",
            'kategori' => 'pencairan',
            'kategori_label' => 'Pencairan Dana',
            'jenis_kode' => 'cair_resign',
            'jenis_label' => 'Pencairan Resign',
            'warna' => 'emerald',
            'tahap' => 'bendahara',
            'tahap_label' => 'Bendahara',
            'anggota' => $this->ringkasAnggota($a),
            'ringkasan_utama' => 'Rp '.number_format($totalDikembalikan, 0, ',', '.'),
            'ringkasan_sub' => implode(' • ', $subBagian),
            'is_nominal' => true,
            'nada_nominal' => 'default',
            'tanggal' => ($isRiwayat ? $a->tanggal_pencairan_resign : $a->tanggal_resign)?->format('d M Y') ?? '-',
            'sort_ts' => ($isRiwayat ? ($a->tanggal_pencairan_resign ?? $a->updated_at) : ($a->tanggal_resign ?? $a->updated_at))?->timestamp ?? 0,
            'status' => $isRiwayat ? 'dicairkan' : 'menunggu_pencairan',
            'aksi_label' => $isRiwayat ? 'Detail' : 'Cairkan',
            'detail' => [
                'id' => $a->id,
                'status' => $isRiwayat ? 'dicairkan' : 'menunggu_pencairan',
                'status_anggota' => $a->status,
                'nomor_dokumen' => $docNo,
                'tanggal_resign' => $a->tanggal_resign?->format('d M Y') ?? '-',
                'alasan_resign' => $a->alasan_resign ?? '-',
                'nominal' => $totalDikembalikan,
                'terbilang' => TerbilangHelper::angkaKeTerbilang($totalDikembalikan).' rupiah',
                'kembali_pokok' => (float) ($settlement['kembali_pokok'] ?? 0),
                'kembali_wajib' => (float) ($settlement['kembali_wajib'] ?? 0),
                'kembali_tabungan' => (float) ($settlement['kembali_tabungan'] ?? 0),
                'tabungan_no_rekening' => $settlement['tabungan_no_rekening'] ?? null,
                'tagihan_pelunasan' => (float) ($settlement['tagihan_pelunasan'] ?? 0),
                'dana_sosial_hangus' => (float) ($settlement['dana_sosial_hangus'] ?? 0),
                'akun_kas' => $a->resign_akun_kas_pencairan,
                'tanggal_pencairan' => $a->tanggal_pencairan_resign?->format('d M Y'),
                'dicairkan_oleh' => $a->dicairkanResignOleh?->name ?? ($settlement['dicairkan_oleh'] ?? '-'),
                'catatan_pencairan' => $a->catatan_pencairan_resign,
                'ada_bukti_pencairan' => (bool) $a->bukti_pencairan_resign_path,
                'bukti_pencairan_url' => $a->bukti_pencairan_resign_path ? route('anggota.bukti-pencairan-resign', $a->id) : null,
                'slip_resign_url' => route('anggota.slip-resign', $a->id),
                'rekening' => ($bank || $noRek) ? [
                    'bank' => $bank ?? '-',
                    'no_rekening' => $noRek ?? '-',
                    'atas_nama' => $atasNama ?? $a->nama,
                ] : null,
                'anggota' => [
                    'id' => $a->id,
                    'nama' => $a->nama,
                    'no_karyawan' => $a->no_karyawan ?? '-',
                    'cabang' => $a->cabang ?? '-',
                    'foto_url' => $a->foto_url,
                ],
            ],
        ];
    }
}
