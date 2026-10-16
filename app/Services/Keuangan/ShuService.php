<?php

namespace App\Services\Keuangan;

use App\Laporan\LaporanRegistry;
use App\Models\Anggota;
use App\Models\AuditLog;
use App\Models\Pinjaman;
use App\Models\ShuDetail;
use App\Models\ShuPeriode;
use App\Models\Simpanan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Modul Pembagian SHU tahunan (mirror Excel "Komponen Pembagian SHU 2024").
 *
 * Rumus per anggota (sheet Distribusi SHU):
 *   modal     = simp_pokok + simp_wajib (akumulasi, bukan setahun)
 *   transaksi = total nominal pinjaman CAIR dalam tahun buku
 *   shu_modal     = modal / total_modal × pool_jasa_simpanan
 *   shu_transaksi = transaksi / total_transaksi × pool_jasa_transaksi
 *   shu_bruto = shu_modal + shu_transaksi
 *   shu_bersih = FLOOR(shu_bruto, 1000) (pembulatan ke bawah ribuan)
 *
 * Hanya anggota aktif yang dihitung; resign/nonaktif diexclude
 * (Excel 2024: 6 nama di Iuran Wajib tidak masuk Distribusi).
 */
class ShuService
{
    public const STATUS_CAIIR = ['aktif', 'lunas'];

    public const PERSEN_DEFAULT = [
        'pct_cadangan' => 0.10,
        'pct_jasa_simpanan' => 0.20,
        'pct_jasa_transaksi' => 0.40,
        'pct_pengurus' => 0.15,
        'pct_pengawas' => 0.05,
        'pct_pendidikan' => 0.05,
        'pct_sosial' => 0.0,
        'pct_wilker' => 0.05,
    ];

    public function __construct(private JurnalKasService $jurnalKas) {}

    /** Total SHU dari Laba Rugi YTD (pengganti link eksternal [2]YTD Excel). */
    public function totalShuDariLaba(int $tahun): float
    {
        return LaporanRegistry::totalShu($tahun);
    }

    public function buatPeriode(int $tahun, ?array $persen, ?float $totalShu, int $userId, ?array $custom = null): ShuPeriode
    {
        if (ShuPeriode::where('tahun_buku', $tahun)->exists()) {
            throw new RuntimeException("Periode SHU tahun {$tahun} sudah ada.");
        }

        [$persen, $custom] = $this->normalkanPersenDanCustom($persen ?? self::PERSEN_DEFAULT, $custom ?? []);

        $periode = DB::transaction(function () use ($tahun, $persen, $custom, $totalShu) {
            $periode = ShuPeriode::create([
                'tahun_buku' => $tahun,
                'total_shu' => $totalShu ?? $this->totalShuDariLaba($tahun),
                ...$persen,
                'status' => 'draft',
            ]);
            $this->sinkronCustom($periode, $custom);

            return $periode;
        });

        AuditLog::catat(
            aksi: 'shu_periode_dibuat',
            keterangan: "Periode SHU {$tahun} dibuat, total: ".number_format((float) $periode->total_shu, 0, ',', '.'),
            dataBaru: ['shu_periode_id' => $periode->id, 'tahun_buku' => $tahun],
            userId: $userId,
        );

        return $periode;
    }

    public function ubahPersen(ShuPeriode $periode, array $persen, int $userId, ?array $custom = null): ShuPeriode
    {
        $this->wajibDraft($periode);
        // $custom null = custom lama dipertahankan (ikut validasi total);
        // array (boleh kosong) = sync ke daftar itu (tambah/hapus custom).
        $custom ??= $periode->customKomponen()->get(['slug', 'label', 'persen'])
            ->map(fn ($r) => ['label' => $r->label, 'persen' => (float) $r->persen])->all();
        [$persen, $custom] = $this->normalkanPersenDanCustom($persen, $custom);

        DB::transaction(function () use ($periode, $persen, $custom) {
            $periode->update($persen);
            $this->sinkronCustom($periode, $custom);
        });

        AuditLog::catat(
            aksi: 'shu_persen_diubah',
            keterangan: "Persentase SHU {$periode->tahun_buku} diubah.",
            dataBaru: ['shu_periode_id' => $periode->id, 'persen' => $persen],
            userId: $userId,
        );

        return $periode->refresh();
    }

    /**
     * Hitung ulang seluruh rincian anggota (draft saja).
     * Return ringkasan angka untuk preview.
     */
    public function hitung(ShuPeriode $periode, int $userId): array
    {
        $this->wajibDraft($periode);
        $tahun = $periode->tahun_buku;

        $simpanan = Simpanan::query()
            ->join('anggota', 'anggota.id', '=', 'simpanan.anggota_id')
            ->where('anggota.status', 'aktif')
            ->whereIn('simpanan.jenis', ['pokok', 'wajib'])
            ->groupBy('simpanan.anggota_id')
            ->selectRaw("simpanan.anggota_id as anggota_id,
                SUM(CASE WHEN simpanan.jenis = 'pokok' THEN simpanan.jumlah ELSE 0 END) as pokok,
                SUM(CASE WHEN simpanan.jenis = 'wajib' THEN simpanan.jumlah ELSE 0 END) as wajib")
            ->get()
            ->keyBy('anggota_id');

        $transaksi = Pinjaman::query()
            ->join('anggota', 'anggota.id', '=', 'pinjaman.anggota_id')
            ->where('anggota.status', 'aktif')
            ->whereIn('pinjaman.status', self::STATUS_CAIIR)
            ->whereNotNull('pinjaman.tanggal_pencairan')
            ->whereYear('pinjaman.tanggal_pencairan', $tahun)
            ->groupBy('pinjaman.anggota_id')
            ->selectRaw('pinjaman.anggota_id as anggota_id, SUM(pinjaman.nominal) as total')
            ->get()
            ->keyBy('anggota_id');

        $anggotaIds = $simpanan->keys()->merge($transaksi->keys())->unique();

        $baris = [];
        $totalModal = 0.0;
        $totalTransaksi = 0.0;
        foreach ($anggotaIds as $anggotaId) {
            $pokok = (float) ($simpanan[$anggotaId]->pokok ?? 0);
            $wajib = (float) ($simpanan[$anggotaId]->wajib ?? 0);
            $modal = $pokok + $wajib;
            $cair = (float) ($transaksi[$anggotaId]->total ?? 0);
            if ($modal <= 0 && $cair <= 0) {
                continue;
            }
            $baris[$anggotaId] = ['pokok' => $pokok, 'wajib' => $wajib, 'modal' => $modal, 'transaksi' => $cair];
            $totalModal += $modal;
            $totalTransaksi += $cair;
        }

        $komponen = $periode->nominalKomponen();
        $poolSimpanan = $komponen['jasa_simpanan'];
        $poolTransaksi = $komponen['jasa_transaksi'];

        DB::transaction(function () use ($periode, $baris, $totalModal, $totalTransaksi, $poolSimpanan, $poolTransaksi) {
            $terlihat = [];
            foreach ($baris as $anggotaId => $b) {
                $shuModal = $totalModal > 0 ? $b['modal'] / $totalModal * $poolSimpanan : 0;
                $shuTrx = $totalTransaksi > 0 ? $b['transaksi'] / $totalTransaksi * $poolTransaksi : 0;
                $bruto = $shuModal + $shuTrx;
                $bersih = floor($bruto / 1000) * 1000;

                ShuDetail::updateOrCreate(
                    ['shu_periode_id' => $periode->id, 'anggota_id' => $anggotaId],
                    [
                        'simp_pokok' => round($b['pokok'], 2),
                        'simp_wajib' => round($b['wajib'], 2),
                        'modal' => round($b['modal'], 2),
                        'transaksi' => round($b['transaksi'], 2),
                        'shu_modal' => round($shuModal, 2),
                        'shu_transaksi' => round($shuTrx, 2),
                        'shu_bruto' => round($bruto, 2),
                        'shu_bersih' => $bersih,
                    ]
                );
                $terlihat[] = $anggotaId;
            }
            ShuDetail::where('shu_periode_id', $periode->id)
                ->when($terlihat !== [], fn ($q) => $q->whereNotIn('anggota_id', $terlihat))
                ->delete();
        });

        AuditLog::catat(
            aksi: 'shu_dihitung',
            keterangan: "SHU {$tahun} dihitung: ".count($baris).' anggota.',
            dataBaru: ['shu_periode_id' => $periode->id, 'anggota' => count($baris)],
            userId: $userId,
        );

        return $this->ringkasan($periode);
    }

    /** Ringkasan angka periode: komponen + total distribusi + sisa (mirror F20/F24/O15/J247 Excel). */
    public function ringkasan(ShuPeriode $periode): array
    {
        $komponen = $periode->nominalKomponen();
        $agregat = ShuDetail::where('shu_periode_id', $periode->id)
            ->selectRaw('COUNT(*) as anggota, COALESCE(SUM(modal),0) as modal,
                COALESCE(SUM(transaksi),0) as transaksi, COALESCE(SUM(shu_bruto),0) as bruto,
                COALESCE(SUM(shu_bersih),0) as bersih,
                SUM(CASE WHEN status_bayar = \'sudah\' THEN 1 ELSE 0 END) as sudah_bayar,
                COALESCE(SUM(CASE WHEN status_bayar = \'sudah\' THEN shu_bersih ELSE 0 END),0) as terbayar')
            ->first();

        $periode->loadMissing('customKomponen');
        $poolAnggota = $komponen['jasa_simpanan'] + $komponen['jasa_transaksi'];
        $sisaPembulatan = round($poolAnggota - (float) ($agregat->bruto ?? 0), 2);
        $belumDiserahkan = round((float) ($agregat->bruto ?? 0) - (float) ($agregat->bersih ?? 0), 2);
        $danaDibagi = $poolAnggota + $komponen['pengurus'] + $komponen['pengawas'];
        // Tak dibagi = sisa total (cadangan + pendidikan + sosial + wilker + semua custom).
        $danaTakDibagi = round((float) $periode->total_shu - $danaDibagi, 2);

        return [
            'komponen' => $komponen,
            'custom' => $periode->daftarCustom(),
            'pool_anggota' => round($poolAnggota, 2),
            'dana_dibagi' => round($danaDibagi, 2),
            'dana_tak_dibagi' => $danaTakDibagi,
            'anggota' => (int) ($agregat->anggota ?? 0),
            'total_modal' => (float) ($agregat->modal ?? 0),
            'total_transaksi' => (float) ($agregat->transaksi ?? 0),
            'total_bruto' => (float) ($agregat->bruto ?? 0),
            'total_bersih' => (float) ($agregat->bersih ?? 0),
            'sisa_pembulatan' => $sisaPembulatan,
            'belum_diserahkan' => $belumDiserahkan,
            'sudah_bayar' => (int) ($agregat->sudah_bayar ?? 0),
            'terbayar' => (float) ($agregat->terbayar ?? 0),
        ];
    }

    public function simpanRincianPengelola(ShuPeriode $periode, ?array $pengurus, ?array $pengawas, int $userId): ShuPeriode
    {
        $this->wajibDraft($periode);
        $periode->update([
            'pengurus_rincian' => $this->normalkanRincianPengurus($pengurus),
            'pengawas_rincian' => $this->normalkanRincian($pengawas),
        ]);

        AuditLog::catat(
            aksi: 'shu_rincian_pengelola_disimpan',
            keterangan: "Rincian pengurus/pengawas SHU {$periode->tahun_buku} disimpan.",
            dataBaru: ['shu_periode_id' => $periode->id],
            userId: $userId,
        );

        return $periode->refresh();
    }

    public function finalisasi(ShuPeriode $periode, int $userId): ShuPeriode
    {
        if ($periode->status !== 'diajukan') {
            throw new RuntimeException('Hanya periode yang Diajukan (selesai dibahas) bisa difinalisasi.');
        }

        // Snapshot total SHU terbaru dari Laba Rugi bila masih nol.
        if ((float) $periode->total_shu <= 0) {
            $periode->total_shu = $this->totalShuDariLaba($periode->tahun_buku);
        }

        $periode->update([
            'status' => 'final',
            'finalized_by' => $userId,
            'finalized_at' => now(),
        ]);

        AuditLog::catat(
            aksi: 'shu_difinalisasi',
            keterangan: "SHU {$periode->tahun_buku} difinalisasi, total: ".number_format((float) $periode->total_shu, 0, ',', '.'),
            dataBaru: ['shu_periode_id' => $periode->id],
            userId: $userId,
        );

        return $periode->refresh();
    }

    /** Bayar SHU satu anggota tunai/transfer via JurnalKas (periode harus final). */
    public function bayarDetail(ShuDetail $detail, string $akun, string $tanggal, int $userId): ShuDetail
    {
        $periode = $detail->periode;
        if (! $periode->isFinal()) {
            throw new RuntimeException('Periode SHU belum difinalisasi.');
        }
        if ($detail->sudahDibayar()) {
            throw new RuntimeException('SHU anggota ini sudah dibayar.');
        }
        if (! in_array($akun, ['bank', 'kas_kecil'], true)) {
            throw new RuntimeException("Sumber dana '{$akun}' tidak dikenal.");
        }

        return DB::transaction(function () use ($detail, $periode, $akun, $tanggal, $userId) {
            $bersih = (float) $detail->shu_bersih;
            $jurnalId = null;

            if ($bersih > 0) {
                $detail->loadMissing('anggota');
                $jurnal = $this->jurnalKas->catat(
                    tipe: 'keluar',
                    kategori: 'pembayaran_shu',
                    kantong: $akun,
                    jumlah: $bersih,
                    keterangan: "Pembayaran SHU {$periode->tahun_buku} — {$detail->anggota->nama}",
                    referensiId: $detail->id,
                    tanggal: Carbon::parse($tanggal)->format('Y-m-d'),
                    userId: $userId,
                );
                $jurnalId = $jurnal->id;
            }

            $detail->update([
                'status_bayar' => 'sudah',
                'dibayar_pada' => now(),
                'dibayar_oleh' => $userId,
                'jurnal_id' => $jurnalId,
            ]);

            AuditLog::catat(
                aksi: 'shu_dibayar',
                keterangan: "SHU {$periode->tahun_buku} {$detail->anggota->nama} dibayar: ".number_format($bersih, 0, ',', '.')." via {$akun}",
                dataBaru: ['shu_detail_id' => $detail->id, 'jurnal_id' => $jurnalId],
                userId: $userId,
            );

            return $detail->refresh();
        });
    }

    /** Bayar massal seluruh rincian belum bayar (periode final). Return jumlah terbayar. */
    public function bayarSemua(ShuPeriode $periode, string $akun, string $tanggal, int $userId): int
    {
        if (! $periode->isFinal()) {
            throw new RuntimeException('Periode SHU belum difinalisasi.');
        }

        $n = 0;
        ShuDetail::where('shu_periode_id', $periode->id)
            ->where('status_bayar', 'belum')
            ->orderBy('id')
            ->chunkById(100, function ($chunk) use ($akun, $tanggal, $userId, &$n) {
                foreach ($chunk as $detail) {
                    $this->bayarDetail($detail, $akun, $tanggal, $userId);
                    $n++;
                }
            });

        return $n;
    }

    /** Ajukan draft sebagai bahan meeting: dikunci seperti final. */
    public function ajukan(ShuPeriode $periode, int $userId): ShuPeriode
    {
        $this->wajibDraft($periode);

        if ($periode->detail()->count() === 0) {
            throw new RuntimeException('Hitung dulu rincian anggota sebelum diajukan.');
        }

        $periode->update([
            'status' => 'diajukan',
            'diajukan_by' => $userId,
            'diajukan_at' => now(),
        ]);

        AuditLog::catat(
            aksi: 'shu_diajukan',
            keterangan: "SHU {$periode->tahun_buku} diajukan untuk dibahas.",
            dataBaru: ['shu_periode_id' => $periode->id],
            userId: $userId,
        );

        return $periode->refresh();
    }

    /** Kembalikan bahan meeting ke draft untuk revisi. */
    public function kembalikanKeDraft(ShuPeriode $periode, int $userId): ShuPeriode
    {
        if (! $periode->isDiajukan()) {
            throw new RuntimeException('Hanya periode Diajukan yang bisa dikembalikan ke draft.');
        }

        $periode->update([
            'status' => 'draft',
            'diajukan_by' => null,
            'diajukan_at' => null,
        ]);

        AuditLog::catat(
            aksi: 'shu_dikembalikan',
            keterangan: "SHU {$periode->tahun_buku} dikembalikan ke draft (revisi).",
            dataBaru: ['shu_periode_id' => $periode->id],
            userId: $userId,
        );

        return $periode->refresh();
    }

    private function wajibDraft(ShuPeriode $periode): void
    {
        if (! $periode->bisaDiubah()) {
            throw new RuntimeException('Periode SHU sudah diajukan/final, tidak bisa diubah.');
        }
    }

    /**
     * Validasi 8 bawaan (permanen, tak bisa dihapus) + N custom, total 100%.
     * Return [persenBawaan, customNormal].
     */
    private function normalkanPersenDanCustom(array $persen, ?array $custom): array
    {
        $kolom = array_values(ShuPeriode::KOMPONEN);
        $hasil = [];
        foreach ($kolom as $k) {
            $hasil[$k] = round((float) ($persen[$k] ?? 0), 4);
        }
        foreach ($hasil as $v) {
            if ($v < 0) {
                throw new RuntimeException('Persentase tidak boleh negatif.');
            }
        }

        $custom = $this->normalkanCustom($custom ?? []);
        $totalCustom = array_sum(array_column($custom, 'persen'));
        if (abs(array_sum($hasil) + $totalCustom - 1.0) > 0.0001) {
            throw new RuntimeException('Total persentase 8 komponen + tambahan harus 100%.');
        }

        return [$hasil, $custom];
    }

    /**
     * Validasi komponen tambahan. 8 bawaan dilindungi: slug custom yang
     * tabrakan ditolak. Return [{slug, label, persen}].
     */
    private function normalkanCustom(array $custom): array
    {
        $hasil = [];
        $slugTerpakai = [];
        $labelTerpakai = [];
        foreach (array_values($custom) as $i => $r) {
            if (! is_array($r)) {
                throw new RuntimeException('Format komponen tambahan tidak valid.');
            }
            $label = trim((string) ($r['label'] ?? ''));
            if ($label === '') {
                throw new RuntimeException('Nama komponen tambahan wajib diisi.');
            }
            if (mb_strlen($label) > 100) {
                throw new RuntimeException("Nama komponen '{$label}' maksimal 100 karakter.");
            }
            $persen = round((float) ($r['persen'] ?? 0), 4);
            if ($persen < 0 || $persen > 1) {
                throw new RuntimeException("Persentase '{$label}' harus 0–100%.");
            }
            // Nol persen = diabaikan (tidak disimpan) agar daftar tetap bersih.
            if ($persen <= 0) {
                continue;
            }
            $slug = $this->slugKomponen($label);
            if (in_array($slug, ShuPeriode::KUNCI_BAWAAN, true)) {
                throw new RuntimeException("Nama '{$label}' menyerupai komponen bawaan, gunakan nama lain.");
            }
            $kunciLabel = mb_strtolower($label);
            if (isset($slugTerpakai[$slug]) || isset($labelTerpakai[$kunciLabel])) {
                throw new RuntimeException("Komponen tambahan '{$label}' duplikat.");
            }
            $slugTerpakai[$slug] = true;
            $labelTerpakai[$kunciLabel] = true;
            $hasil[] = ['slug' => $slug, 'label' => $label, 'persen' => $persen];
        }
        if (count($hasil) > 20) {
            throw new RuntimeException('Maksimal 20 komponen tambahan per periode.');
        }

        return $hasil;
    }

    private function slugKomponen(string $label): string
    {
        $slug = mb_strtolower(trim($label));
        $slug = (string) preg_replace('/[^a-z0-9]+/u', '_', $slug);
        $slug = trim($slug, '_');
        if ($slug === '') {
            $slug = 'komponen';
        }

        return mb_substr($slug, 0, 50);
    }

    /** Sync tabel custom: tambah/update baru, hapus yang tidak ada di daftar. */
    private function sinkronCustom(ShuPeriode $periode, array $custom): void
    {
        $slugs = array_column($custom, 'slug');
        $periode->customKomponen()->whereNotIn('slug', $slugs)->delete();
        foreach ($custom as $r) {
            $periode->customKomponen()->updateOrCreate(
                ['slug' => $r['slug']],
                ['label' => $r['label'], 'persen' => $r['persen']]
            );
        }
    }

    /** Pengurus wajib dipilih dari anggota (disnapshot namanya); jabatan teks bebas. */
    private function normalkanRincianPengurus(?array $rincian): ?array
    {
        if ($rincian === null) {
            return null;
        }
        $hasil = [];
        foreach ($rincian as $r) {
            // Baris lama (teks bebas tanpa anggota_id) tetap terbaca apa adanya.
            if (empty($r['anggota_id'])) {
                if (trim((string) ($r['nama'] ?? '')) === '') {
                    continue;
                }
                $hasil[] = [
                    'anggota_id' => null,
                    'nama' => trim((string) $r['nama']),
                    'jabatan' => trim((string) ($r['jabatan'] ?? '')),
                    'nominal' => (float) ($r['nominal'] ?? 0),
                ];
                continue;
            }
            $anggota = Anggota::find($r['anggota_id']);
            if (! $anggota) {
                throw new RuntimeException('Anggota pengurus tidak ditemukan.');
            }
            $nominal = (float) ($r['nominal'] ?? 0);
            if ($nominal <= 0) {
                continue;
            }
            $hasil[] = [
                'anggota_id' => $anggota->id,
                'nama' => $anggota->nama,
                'jabatan' => trim((string) ($r['jabatan'] ?? '')),
                'nominal' => $nominal,
            ];
        }

        return $hasil;
    }

    private function normalkanRincian(?array $rincian): ?array
    {
        if ($rincian === null) {
            return null;
        }
        $hasil = [];
        foreach ($rincian as $r) {
            $nama = trim((string) ($r['nama'] ?? ''));
            $nominal = (float) ($r['nominal'] ?? 0);
            if ($nama === '' || $nominal <= 0) {
                continue;
            }
            $hasil[] = ['nama' => $nama, 'nominal' => $nominal];
        }

        return $hasil;
    }
}
