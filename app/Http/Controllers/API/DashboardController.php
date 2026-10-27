<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\PinjamanResource;
use App\Http\Resources\PinjamanRingkasResource;
use App\Http\Resources\SessionUserResource;
use App\Models\Anggota;
use App\Models\Angsuran;
use App\Models\KasKoperasi;
use App\Models\PengajuanLimit;
use App\Models\PengajuanPercepatan;
use App\Models\Pinjaman;
use App\Models\SettingSimpanan;
use App\Models\Simpanan;
use App\Models\TabelTenor;
use App\Services\Pinjaman\EligibilitasPinjamanService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private EligibilitasPinjamanService $eligibilitas) {}

    private function anggotaOrFail(Request $request): Anggota
    {
        $anggota = $request->user()->anggota;

        if (! $anggota) {
            abort(response()->json([
                'message' => 'Anggota not found for user',
            ], 404));
        }

        return $anggota;
    }

    /**
     * Dashboard anggota untuk aplikasi mobile.
     *
     * @response 200 {"anggota": {"id": 1}, "ringkasan": {"total_simpanan": 100000}, "riwayat_gabungan": []}
     */
    public function dashboard(Request $request): JsonResponse
    {
        $anggota = $this->anggotaOrFail($request);

        $simpananAgg = $anggota->simpanan()
            ->selectRaw('
                SUM(CASE WHEN jenis IN ("pokok","wajib") THEN jumlah ELSE 0 END) as total,
                SUM(CASE WHEN jenis = "pokok" THEN jumlah ELSE 0 END) as pokok,
                SUM(CASE WHEN jenis = "wajib" THEN jumlah ELSE 0 END) as wajib
            ')
            ->first();

        $cek = $this->eligibilitas->cek($anggota);
        $limitMaksimal = $this->eligibilitas->limitMaksimal($anggota);

        $pinjamanAktif = $anggota->pinjamanAktifList();

        $resignMenunggu = null;
        if ($anggota->status === 'resign_menunggu') {
            $settlement = $anggota->resigned_settlement_json ?? [];
            $resignMenunggu = [
                'shortfall' => (float) ($settlement['shortfall'] ?? 0),
                'jatuh_tempo' => isset($settlement['cicilan_akhir_jatuh_tempo'])
                    ? Carbon::parse($settlement['cicilan_akhir_jatuh_tempo'])->format('Y-m-d')
                    : null,
                'tanggal_resign' => $anggota->tanggal_resign?->format('Y-m-d'),
            ];
        }

        $pengajuanBerjalan = $anggota->pinjaman()->with(['anggota', 'pengaju'])
            ->whereIn('status', ['diajukan', 'approved_admin', 'approved_bendahara'])
            ->latest('tanggal_pengajuan')
            ->first();

        $pengajuanLimitBerjalan = $anggota->pengajuanLimit()->with('anggota')
            ->whereIn('status', ['diajukan', 'approved_bendahara'])
            ->latest('tanggal_pengajuan')
            ->first();

        $pengajuanPercepatanMenunggu = $pinjamanAktif->isEmpty()
            ? collect()
            : PengajuanPercepatan::whereIn('pinjaman_id', $pinjamanAktif->pluck('id'))
                ->whereIn('status', ['diajukan', 'approved_bendahara'])
                ->with('pinjaman:id,nominal,tenor_bulan')
                ->orderBy('tanggal_pengajuan')
                ->get();

        $pengajuanDitolak = $anggota->pinjaman()->with(['anggota', 'pengaju'])
            ->where('status', 'ditolak')
            ->latest('updated_at')
            ->first();

        $angsuranBerikutnya = null;
        foreach ($pinjamanAktif as $pinjaman) {
            $agg = $pinjaman->agregatJadwalAktif();
            $terdekat = $pinjaman->angsuranBelumBayar()->orderBy('cicilan_ke')->first();
            if ($terdekat && ($this->terdekatLebihAwal($terdekat, $angsuranBerikutnya))) {
                $angsuranBerikutnya = [
                    'cicilan_ke' => $terdekat->cicilan_ke,
                    'total_bayar' => (float) $terdekat->total_bayar,
                    'tanggal_jatuh_tempo' => $terdekat->tanggal_jatuh_tempo?->format('Y-m-d'),
                ];
            }
            unset($agg);
        }

        $pinjamanIdsAnggota = $anggota->pinjaman()->pluck('id');
        $riwayatSimpanan = $anggota->simpanan()
            ->whereIn('jenis', ['wajib', 'pokok'])
            ->latest('tanggal_input')
            ->take(6)
            ->get();
        $riwayatAngsuran = $pinjamanIdsAnggota->isEmpty()
            ? collect()
            : Angsuran::whereIn('pinjaman_id', $pinjamanIdsAnggota)
                ->where('status', 'lunas')
                ->latest('tanggal_konfirmasi_bayar')
                ->take(4)
                ->get();
        $riwayatGabungan = $riwayatSimpanan->map(fn ($s) => [
            'tipe' => 'simpanan',
            'label' => match ($s->jenis) {
                'pokok' => 'Simpanan pokok',
                'wajib' => 'Simpanan wajib',
                default => $s->jenis,
            },
            'nominal' => (float) $s->jumlah,
            'tanggal_format' => $s->tanggal_input?->format('Y-m-d'),
            'tanggal' => $s->tanggal_input,
        ])->concat($riwayatAngsuran->map(fn ($a) => [
            'tipe' => 'angsuran',
            'label' => "Cicilan ke-{$a->cicilan_ke}",
            'nominal' => (float) $a->total_bayar,
            'tanggal_format' => ($a->tanggal_konfirmasi_bayar ?? $a->tanggal_jatuh_tempo)?->format('Y-m-d'),
            'tanggal' => $a->tanggal_konfirmasi_bayar ?? $a->tanggal_jatuh_tempo,
        ]))->sortByDesc(fn ($item) => $item['tanggal']?->toDateString() ?? '')
            ->take(5)
            ->values()
            ->map(fn ($item) => collect($item)->except('tanggal')->all());

        return response()->json([
            'anggota' => $this->anggotaPenuh($anggota, $resignMenunggu),
            'ringkasan' => [
                'total_simpanan' => (float) ($simpananAgg->total ?? 0),
                'simpanan_pokok' => (float) ($simpananAgg->pokok ?? 0),
                'simpanan_wajib' => (float) ($simpananAgg->wajib ?? 0),
                'limit_maksimal' => (float) $limitMaksimal,
                'limit_tersedia' => (float) $cek['limit_tersedia'],
                'boleh' => (bool) $cek['boleh'],
                'alasan' => $cek['alasan'],
                'sisa_angsuran' => (int) $cek['sisa_angsuran'],
                'cicilan_pokok' => (float) $cek['cicilan_pokok'],
            ],
            'pinjaman_aktif' => PinjamanRingkasResource::collection($pinjamanAktif)->resolve(),
            'pengajuan_berjalan' => $pengajuanBerjalan ? (new PinjamanResource($pengajuanBerjalan))->resolve() : null,
            'pengajuan_limit_berjalan' => $pengajuanLimitBerjalan ? $this->pengajuanLimit($pengajuanLimitBerjalan) : null,
            'pengajuan_percepatan_menunggu' => $pengajuanPercepatanMenunggu->map(fn ($pp) => $this->pengajuanPercepatan($pp))->values()->all(),
            'pengajuan_ditolak' => $pengajuanDitolak ? (new PinjamanResource($pengajuanDitolak))->resolve() : null,
            'angsuran_berikutnya' => $angsuranBerikutnya,
            'riwayat_gabungan' => $riwayatGabungan,
            'tabel_tenor' => TabelTenor::orderBy('nominal_min')->get(['nominal_min', 'nominal_max', 'tenor_maksimal_bulan'])
                ->map(fn ($t) => [
                    'nominal_min' => (float) $t->nominal_min,
                    'nominal_max' => (float) $t->nominal_max,
                    'tenor_maksimal_bulan' => (int) $t->tenor_maksimal_bulan,
                ])->values()->all(),
            'setting_simpanan' => SettingSimpanan::orderBy('id')->get(['jenis', 'label', 'nominal'])
                ->map(fn ($s) => [
                    'jenis' => $s->jenis,
                    'label' => $s->label,
                    'nominal' => (float) $s->nominal,
                ])->values()->all(),
        ]);
    }

    /**
     * Ringkasan pengurus untuk aplikasi mobile.
     *
     * @response 200 {"user": {"id": 1}, "antrean": {"pinjaman_admin": 0}, "kas": null}
     */
    public function ringkasan(Request $request): JsonResponse
    {
        $user = $request->user();
        $izinPengurus = [
            'pinjaman.tinjau-admin', 'pinjaman.tinjau-bendahara', 'pinjaman.approve-ketua',
            'pinjaman.lihat', 'limit.tinjau-bendahara', 'limit.approve-ketua',
            'angsuran.konfirmasi', 'simpanan.konfirmasi', 'simpanan.lihat', 'kas.lihat',
        ];

        if (! $user->hasAnyPermission($izinPengurus)) {
            abort(response()->json([
                'message' => 'Anda tidak memiliki akses ke ringkasan pengurus.',
            ], 403));
        }

        $pinjamanAntre = Pinjaman::whereIn('status', ['diajukan', 'approved_admin', 'approved_bendahara'])
            ->selectRaw('status, COUNT(*) as jumlah, SUM(cair_oleh_bendahara) as cair')
            ->groupBy('status')
            ->get()
            ->keyBy('status');
        $ketuaMenunggu = (int) ($pinjamanAntre['approved_bendahara']->jumlah ?? 0)
            - (int) ($pinjamanAntre['approved_bendahara']->cair ?? 0);

        $pencairan = Pinjaman::where('status', 'approved_bendahara')
            ->where('cair_oleh_bendahara', true)
            ->count();

        $limitAntre = PengajuanLimit::whereIn('status', ['diajukan', 'approved_bendahara'])
            ->selectRaw('status, COUNT(*) as jumlah')
            ->groupBy('status')
            ->pluck('jumlah', 'status');

        $percepatanAntre = PengajuanPercepatan::whereIn('status', ['diajukan', 'approved_bendahara'])
            ->selectRaw('status, COUNT(*) as jumlah')
            ->groupBy('status')
            ->pluck('jumlah', 'status');

        $angsuranBelumBayar = Angsuran::where('status', 'belum_bayar')
            ->whereHas('pinjaman', fn ($q) => $q->where('status', 'aktif'))
            ->count();

        $simpananBelumKonfirmasi = Anggota::where('status', 'aktif')
            ->whereDoesntHave('simpanan', fn ($q) => $q
                ->where('jenis', 'wajib')
                ->where('bulan_periode', now()->format('Y-m')))
            ->count();

        $kas = null;
        if ($user->can('kas.lihat')) {
            $row = KasKoperasi::first();
            if ($row) {
                $saldoBank = (float) $row->saldo_bank;
                $saldoKasKecil = (float) $row->saldo_kas_kecil;
                $awalBulan = now()->startOfMonth()->startOfDay()->toDateTimeString();
                $akhirBulan = now()->endOfMonth()->endOfDay()->toDateTimeString();
                $kas = [
                    'saldo_bank' => $saldoBank,
                    'saldo_kas_kecil' => $saldoKasKecil,
                    'kas_operasional' => $saldoBank + $saldoKasKecil,
                    'pinjaman_outstanding' => (float) Pinjaman::where('status', 'aktif')->sum('nominal'),
                    'simpanan_outstanding' => (float) Simpanan::whereIn('simpanan.jenis', ['pokok', 'wajib'])
                        ->join('anggota', 'simpanan.anggota_id', '=', 'anggota.id')
                        ->where('anggota.status', 'aktif')
                        ->sum('simpanan.jumlah'),
                    'pendapatan_bunga_bulan_ini' => (float) Angsuran::where('status', 'lunas')
                        ->whereBetween('tanggal_konfirmasi_bayar', [$awalBulan, $akhirBulan])
                        ->sum('nominal_bunga'),
                ];
            }
        }

        return response()->json([
            'user' => new SessionUserResource($user),
            'antrean' => [
                'pinjaman_admin' => (int) ($pinjamanAntre['diajukan']->jumlah ?? 0),
                'pinjaman_bendahara' => (int) ($pinjamanAntre['approved_admin']->jumlah ?? 0),
                'pinjaman_ketua' => max(0, $ketuaMenunggu),
                'pencairan_bendahara' => (int) $pencairan,
                'limit_bendahara' => (int) ($limitAntre['diajukan'] ?? 0),
                'limit_ketua' => (int) ($limitAntre['approved_bendahara'] ?? 0),
                'percepatan_bendahara' => (int) ($percepatanAntre['diajukan'] ?? 0),
                'percepatan_ketua' => (int) ($percepatanAntre['approved_bendahara'] ?? 0),
                'angsuran_belum_bayar' => (int) $angsuranBelumBayar,
                'simpanan_belum_konfirmasi' => (int) $simpananBelumKonfirmasi,
            ],
            'kas' => $kas,
        ]);
    }

    /**
     * Bentuk Anggota penuh sesuai kontrak mobile DashboardAnggota.anggota.
     */
    private function anggotaPenuh(Anggota $anggota, ?array $resignMenunggu): array
    {
        return [
            'id' => $anggota->id,
            'no_anggota' => $anggota->no_anggota,
            'no_karyawan' => $anggota->no_karyawan,
            'nama' => $anggota->nama,
            'jabatan' => $anggota->jabatan,
            'cabang' => $anggota->cabang,
            'unit_bisnis' => $anggota->unit_bisnis,
            'department' => $anggota->department,
            'divisi' => $anggota->divisi,
            'no_hp' => $anggota->no_hp,
            'email' => $anggota->user?->email,
            'status' => $anggota->status,
            'foto_url' => $anggota->foto_url,
            'tanggal_masuk' => ($anggota->tanggal_jadi_anggota ?? $anggota->tanggal_mulai_kerja)?->format('Y-m-d'),
            'limit_custom' => $anggota->limit_custom !== null ? (float) $anggota->limit_custom : null,
            'resign_menunggu' => $resignMenunggu,
        ];
    }

    /**
     * Pengajuan limit + tahapan (mirror toLimit mock/logic.ts).
     */
    private function pengajuanLimit(PengajuanLimit $l): array
    {
        $ditolakBendahara = $l->status === 'ditolak' && ! $l->catatan_ketua;
        $ditolakKetua = $l->status === 'ditolak' && $l->catatan_ketua;

        return [
            'id' => $l->id,
            'anggota_id' => $l->anggota_id,
            'limit_saat_ini' => (float) $l->limit_saat_ini,
            'limit_diminta' => (float) $l->limit_diminta,
            'limit_disetujui_bendahara' => $l->limit_disetujui_bendahara !== null ? (float) $l->limit_disetujui_bendahara : null,
            'limit_disetujui' => $l->limit_disetujui !== null ? (float) $l->limit_disetujui : null,
            'status' => $l->status,
            'keterangan' => $l->keterangan,
            'catatan_bendahara' => $l->catatan_bendahara,
            'catatan_ketua' => $l->catatan_ketua,
            'tanggal_pengajuan' => $l->tanggal_pengajuan?->format('Y-m-d'),
            'anggota' => $l->anggota ? [
                'id' => $l->anggota->id,
                'no_anggota' => $l->anggota->no_anggota,
                'no_karyawan' => $l->anggota->no_karyawan,
                'nama' => $l->anggota->nama,
                'jabatan' => $l->anggota->jabatan,
                'cabang' => $l->anggota->cabang,
                'unit_bisnis' => $l->anggota->unit_bisnis,
                'department' => $l->anggota->department,
                'divisi' => $l->anggota->divisi,
                'no_hp' => $l->anggota->no_hp,
                'status' => $l->anggota->status,
            ] : null,
            'tahapan' => [
                [
                    'label' => 'Pengajuan diterima',
                    'status' => 'done',
                    'tanggal' => $l->tanggal_pengajuan?->format('Y-m-d'),
                    'oleh' => $l->anggota?->nama,
                    'catatan' => $l->keterangan,
                ],
                [
                    'label' => 'Verifikasi Bendahara',
                    'status' => $ditolakBendahara ? 'rejected' : ($l->status === 'diajukan' ? 'current' : 'done'),
                    'tanggal' => null,
                    'oleh' => $l->catatan_bendahara ? 'Bendahara' : null,
                    'catatan' => $l->catatan_bendahara,
                ],
                [
                    'label' => 'Persetujuan Ketua',
                    'status' => $ditolakKetua ? 'rejected' : ($l->status === 'disetujui' ? 'done' : ($l->status === 'approved_bendahara' ? 'current' : 'pending')),
                    'tanggal' => null,
                    'oleh' => $l->status === 'disetujui' ? 'Ketua Koperasi' : null,
                    'catatan' => $l->catatan_ketua,
                ],
            ],
        ];
    }

    /**
     * Pengajuan percepatan + tahapan (mirror toPercepatan mock/logic.ts).
     */
    private function pengajuanPercepatan(PengajuanPercepatan $p): array
    {
        $ditolakBendahara = $p->status === 'ditolak' && ! $p->catatan_ketua;
        $ditolakKetua = $p->status === 'ditolak' && $p->catatan_ketua;
        $namaAnggota = $p->pinjaman?->anggota?->nama;

        return [
            'id' => $p->id,
            'pinjaman_id' => $p->pinjaman_id,
            'pinjaman_nominal' => (float) ($p->pinjaman->nominal ?? 0),
            'pinjaman_tenor' => (int) ($p->pinjaman->tenor_bulan ?? 0),
            'tipe' => $p->tipe,
            'tenor_lama' => (int) $p->tenor_lama,
            'tenor_baru' => $p->tenor_baru !== null ? (int) $p->tenor_baru : null,
            'keterangan' => $p->keterangan,
            'status' => $p->status,
            'catatan_bendahara' => $p->catatan_bendahara,
            'catatan_ketua' => $p->catatan_ketua,
            'tanggal_pengajuan' => $p->tanggal_pengajuan?->format('Y-m-d'),
            'berlaku_mulai' => $p->bulan_berlaku,
            'anggota' => $p->pinjaman?->anggota ? [
                'id' => $p->pinjaman->anggota->id,
                'no_anggota' => $p->pinjaman->anggota->no_anggota,
                'no_karyawan' => $p->pinjaman->anggota->no_karyawan,
                'nama' => $p->pinjaman->anggota->nama,
                'jabatan' => $p->pinjaman->anggota->jabatan,
                'cabang' => $p->pinjaman->anggota->cabang,
                'unit_bisnis' => $p->pinjaman->anggota->unit_bisnis,
                'department' => $p->pinjaman->anggota->department,
                'divisi' => $p->pinjaman->anggota->divisi,
                'no_hp' => $p->pinjaman->anggota->no_hp,
                'status' => $p->pinjaman->anggota->status,
            ] : null,
            'tahapan' => [
                [
                    'label' => 'Pengajuan diterima',
                    'status' => 'done',
                    'tanggal' => $p->tanggal_pengajuan?->format('Y-m-d'),
                    'oleh' => $namaAnggota,
                    'catatan' => $p->keterangan,
                ],
                [
                    'label' => 'Verifikasi Bendahara',
                    'status' => $ditolakBendahara ? 'rejected' : ($p->status === 'diajukan' ? 'current' : 'done'),
                    'tanggal' => null,
                    'oleh' => $p->catatan_bendahara ? 'Bendahara' : null,
                    'catatan' => $p->catatan_bendahara,
                ],
                [
                    'label' => 'Persetujuan Ketua',
                    'status' => $ditolakKetua ? 'rejected' : ($p->status === 'aktif' ? 'done' : ($p->status === 'approved_bendahara' ? 'current' : 'pending')),
                    'tanggal' => null,
                    'oleh' => $p->status === 'aktif' ? 'Ketua Koperasi' : null,
                    'catatan' => $p->catatan_ketua,
                ],
            ],
        ];
    }

    private function terdekatLebihAwal($kandidat, ?array $saatIni): bool
    {
        if ($saatIni === null) {
            return true;
        }

        $tglKandidat = $kandidat->tanggal_jatuh_tempo?->format('Y-m-d') ?? '';
        $tglSaatIni = $saatIni['tanggal_jatuh_tempo'] ?? '';

        return $tglKandidat !== '' && $tglKandidat < $tglSaatIni;
    }
}
