<?php

namespace App\Http\Controllers;

use App\Models\Anggota;
use App\Models\PengajuanPercepatan;
use App\Models\PengajuanTransaksiTabungan;
use App\Models\RekeningTabungan;
use App\Services\Pinjaman\EligibilitasPinjamanService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function __construct(
        private EligibilitasPinjamanService $eligibilitas,
    ) {}

    /**
     * Tampilkan info akun, ringkasan posisi keuangan personal, dan pengaturan keamanan.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user()->loadMissing([
            'anggota.perusahaan',
            'anggota.departemen',
            'anggota.divisiMaster',
            'anggota.jabatanMaster',
            'anggota.rekening',
        ]);
        $anggota = $user->anggota;

        $ringkasan = null;
        if ($anggota) {
            $simpananAgg = $anggota->simpanan()
                ->selectRaw("
                    SUM(CASE WHEN jenis = 'pokok' THEN jumlah ELSE 0 END) as pokok,
                    SUM(CASE WHEN jenis = 'wajib' THEN jumlah ELSE 0 END) as wajib,
                    SUM(CASE WHEN jenis = 'dana_sosial' THEN jumlah ELSE 0 END) as dana_sosial
                ")
                ->first();

            $simpananPokok = (float) ($simpananAgg->pokok ?? 0);
            $simpananWajib = (float) ($simpananAgg->wajib ?? 0);
            $simpananDanaSosial = (float) ($simpananAgg->dana_sosial ?? 0);

            $rekeningTabungan = $anggota->rekeningTabunganAktif();

            $pinjamanAktifs = $anggota->pinjaman()
                ->where('status', 'aktif')
                ->with([
                    'angsuran' => fn ($q) => $q->where('status', '!=', 'digantikan')->orderBy('cicilan_ke'),
                    'pengajuanPercepatan' => fn ($q) => $q->where('status', 'aktif')->latest()->with('angsuranBaru'),
                ])
                ->get();

            $sisaTagihanPinjaman = 0.0;
            $sisaCicilanPinjaman = 0;
            foreach ($pinjamanAktifs as $pinjaman) {
                $agregat = $pinjaman->agregatJadwalAktif();
                $sisaTagihanPinjaman += (float) $agregat['sisa_bayar'];
                $sisaCicilanPinjaman += (int) $agregat['sisa'];
            }

            $pinjamanBerjalan = $anggota->pinjaman()
                ->whereIn('status', ['diajukan', 'approved_admin', 'approved_bendahara', 'approved_ketua'])
                ->count();
            $limitBerjalan = $anggota->pengajuanLimit()
                ->whereIn('status', ['diajukan', 'approved_admin', 'approved_bendahara'])
                ->count();
            $tenorBerjalan = PengajuanPercepatan::whereHas('pinjaman', fn ($q) => $q->where('anggota_id', $anggota->id))
                ->whereIn('status', ['diajukan', 'approved_bendahara'])
                ->count();
            $tabunganBerjalan = RekeningTabungan::where('anggota_id', $anggota->id)
                ->whereIn('status', RekeningTabungan::STATUS_MENUNGGU)
                ->count()
                + PengajuanTransaksiTabungan::where('anggota_id', $anggota->id)
                    ->whereIn('status', PengajuanTransaksiTabungan::STATUS_MENUNGGU)
                    ->count();

            $antreanAktif = collect([
                ['kode' => 'pinjaman', 'label' => 'Pengajuan Pinjaman', 'jumlah' => $pinjamanBerjalan],
                ['kode' => 'limit', 'label' => 'Kenaikan Limit', 'jumlah' => $limitBerjalan],
                ['kode' => 'tenor', 'label' => 'Perubahan Tenor', 'jumlah' => $tenorBerjalan],
                ['kode' => 'tabungan', 'label' => 'Transaksi Tabungan', 'jumlah' => $tabunganBerjalan],
            ])->filter(fn (array $item) => $item['jumlah'] > 0)->values()->all();

            $ringkasan = [
                'pinjaman_total' => $anggota->pinjaman()->count(),
                'pinjaman_aktif' => $pinjamanAktifs->count(),
                'sisa_tagihan_pinjaman' => round($sisaTagihanPinjaman, 2),
                'sisa_cicilan_pinjaman' => $sisaCicilanPinjaman,
                'simpanan_pokok' => $simpananPokok,
                'simpanan_wajib' => $simpananWajib,
                'simpanan_dana_sosial' => $simpananDanaSosial,
                'simpanan_total' => $simpananPokok + $simpananWajib,
                'tabungan' => [
                    'ada' => (bool) $rekeningTabungan,
                    'no_rekening' => $rekeningTabungan?->no_rekening,
                    'saldo' => (float) ($rekeningTabungan?->saldo ?? 0),
                    'suku_bunga' => (float) ($rekeningTabungan?->suku_bunga_saat_buka ?? 0),
                ],
                'plafon_pinjaman' => $anggota->tanggal_jadi_anggota
                    ? (float) $this->eligibilitas->limitMaksimal($anggota)
                    : 0.0,
                'pinjaman_berjalan' => $pinjamanBerjalan,
                'limit_berjalan' => $limitBerjalan,
                'tenor_berjalan' => $tenorBerjalan,
                'tabungan_berjalan' => $tabunganBerjalan,
                'antrean_aktif' => $antreanAktif,
            ];
        }

        return Inertia::render('Profile/Edit', [
            'pengguna' => [
                'name' => $user->name,
                'email' => $user->email,
                'no_karyawan' => $user->no_karyawan,
                'roles' => $user->getRoleNames()->values(),
                'label_jabatan_pengawas' => $user->labelJabatanPengawas(),
                'has_pin' => $user->hasPin(),
                'auth_provider' => $user->auth_provider ?? config('auth.mode', 'local'),
                'anggota' => $anggota ? [
                    'nama' => $anggota->nama,
                    'no_anggota' => $anggota->no_anggota,
                    'no_karyawan' => $anggota->no_karyawan,
                    'cabang' => $anggota->cabang,
                    'unit_bisnis' => $anggota->unit_bisnis,
                    'jabatan' => $anggota->getRelationValue('jabatanMaster')?->nama ?? $anggota->jabatan,
                    'department' => $anggota->getRelationValue('departemen')?->nama ?? $anggota->department,
                    'perusahaan' => $anggota->getRelationValue('perusahaan')?->nama,
                    'divisi' => $anggota->getRelationValue('divisiMaster')?->nama,
                    'status' => $anggota->status,
                    'no_hp' => $anggota->no_hp,
                    'alamat' => $anggota->alamat,
                    'tanggal_mulai_kerja' => $anggota->tanggal_mulai_kerja?->format('d M Y'),
                    'tanggal_jadi_anggota' => $anggota->tanggal_jadi_anggota?->format('d M Y'),
                    'lama_keanggotaan_label' => Anggota::formatLamaKeanggotaan($anggota->tanggal_jadi_anggota),
                    'lama_keanggotaan_tahun' => $anggota->tanggal_jadi_anggota ? round($anggota->lama_keanggotaan_tahun, 1) : 0.0,
                    'gate_synced_at' => $anggota->gate_synced_at
                        ? $anggota->gate_synced_at->copy()->timezone('Asia/Makassar')->format('d M Y, H:i').' WITA'
                        : null,
                    'foto_url' => $anggota->foto_url,
                    'rekening' => $anggota->rekening
                        ->sortByDesc('is_default')
                        ->values()
                        ->map(fn ($r) => [
                            'id' => $r->id,
                            'nama_bank' => $r->nama_bank,
                            'no_rekening' => $r->no_rekening,
                            'atas_nama' => $r->atas_nama,
                            'is_default' => (bool) $r->is_default,
                        ])
                        ->all(),
                ] : null,
            ],
            'ringkasan' => $ringkasan,
        ]);
    }
}
