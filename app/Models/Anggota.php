<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Anggota extends Model
{
    use HasFactory;

    protected $table = 'anggota';

    protected $fillable = [
        'user_id',
        'no_anggota',
        'no_karyawan',
        'no_ktp',
        'nama',
        'cabang',
        'unit_bisnis',
        'department',
        'divisi',
        'jabatan',
        'tanggal_mulai_kerja',
        'tanggal_jadi_anggota',
        'status',
        'tanggal_resign',
        'alasan_resign',
        'resigned_by',
        'resigned_settlement_json',
        'reaktivasi_history_json',
        'limit_custom',
        'limit_custom_keterangan',
        'no_hp',
        'alamat',
    ];

    protected $casts = [
        'tanggal_mulai_kerja' => 'date',
        'tanggal_jadi_anggota' => 'date',
        'tanggal_resign' => 'date',
        'resigned_settlement_json' => 'array',
        'reaktivasi_history_json' => 'array',
        'limit_custom' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function simpanan(): HasMany
    {
        return $this->hasMany(Simpanan::class);
    }

    public function pinjaman(): HasMany
    {
        return $this->hasMany(Pinjaman::class);
    }

    public function pengajuanLimit(): HasMany
    {
        return $this->hasMany(PengajuanLimit::class);
    }

    public function rekening(): HasMany
    {
        return $this->hasMany(RekeningAnggota::class);
    }

    /**
     * Hitung lama keanggotaan dalam tahun (desimal), acuan untuk limit pinjaman.
     */
    public function getLamaKeanggotaanTahunAttribute(): float
    {
        return $this->tanggal_jadi_anggota->diffInDays(now()) / 365;
    }

    /**
     * Pinjaman aktif pertama (untuk display/info di tempat yang butuh single row).
     * Untuk aggregate multi pinjaman, pakai pinjamanAktifDenganAgregat().
     */
    public function pinjamanAktif(): ?Pinjaman
    {
        return $this->pinjaman()->where('status', 'aktif')->first();
    }

    /**
     * Collection semua pinjaman berstatus aktif (bisa lebih dari 1, misal setelah reloan).
     */
    public function pinjamanAktifList(): Collection
    {
        return $this->pinjaman()->where('status', 'aktif')->get();
    }

    /**
     * Aggregate dari SEMUA pinjaman aktif:
     *   - sisa_total: total sisa angsuran (gabungan angsuran biasa belum_bayar
     *                + angsuran_percepatan belum_bayar dari pengajuan aktif per pinjaman)
     *   - cicilan_pokok_weighted_avg: weighted avg nominal_pokok angsuran aktif
     *                                 (Σ(pokok × sisa) / Σsisa)
     *   - pinjaman_aktif_list: Collection semua pinjaman aktif (untuk cek flag privilege)
     *
     * Return null jika tidak ada pinjaman aktif.
     * Anti N+1: 1 query besar dengan subquery aggregate di SELECT utama (sisa + cicilan_pokok per pinjaman).
     */
    public function pinjamanAktifDenganAgregat(): ?array
    {
        // Subquery untuk cicilan_pokok per pinjaman (weighted avg dari angsuran belum_bayar
        // + angsuran_percepatan belum_bayar). Filter angsuran biasa != 'digantikan'.
        $cicilanPokokSubquery = DB::table('angsuran')
            ->selectRaw('AVG(nominal_pokok)')
            ->whereColumn('angsuran.pinjaman_id', 'pinjaman.id')
            ->where('angsuran.status', 'belum_bayar');

        $cicilanPokokPercepatanSubquery = DB::table('angsuran_percepatan')
            ->selectRaw('AVG(angsuran_percepatan.nominal_pokok)')
            ->join('pengajuan_percepatan', 'pengajuan_percepatan.id', '=', 'angsuran_percepatan.pengajuan_percepatan_id')
            ->whereColumn('pengajuan_percepatan.pinjaman_id', 'pinjaman.id')
            ->where('pengajuan_percepatan.status', 'aktif')
            ->where('angsuran_percepatan.status', 'belum_bayar');

        $pinjamanAktifs = $this->pinjaman()
            ->select('pinjaman.*')
            ->selectSub($cicilanPokokSubquery, 'cicilan_pokok_avg')
            ->selectSub($cicilanPokokPercepatanSubquery, 'cicilan_pokok_percepatan_avg')
            ->withCount([
                'angsuran as sisa_angsuran' => fn ($q) => $q->where('angsuran.status', 'belum_bayar'),
                'angsuranPercepatan as sisa_angsuran_percepatan' => function ($q) {
                    $q->where('angsuran_percepatan.status', 'belum_bayar')
                        ->whereHas('pengajuan', fn ($q2) => $q2->where('pengajuan_percepatan.status', 'aktif'));
                },
            ])
            ->where('status', 'aktif')
            ->get();

        if ($pinjamanAktifs->isEmpty()) {
            return null;
        }

        $sisaTotal = 0;
        $pokokWeightedSum = 0;
        $sisaWeightedSum = 0;

        foreach ($pinjamanAktifs as $p) {
            $sisa = (int) (($p->sisa_angsuran ?? 0) + ($p->sisa_angsuran_percepatan ?? 0));
            $sisaTotal += $sisa;

            // Cicilan pokok: weighted avg dari kedua sumber (konversi decimal ke float).
            $pokokLama = (float) ($p->cicilan_pokok_avg ?? 0);
            $pokokBaru = (float) ($p->cicilan_pokok_percepatan_avg ?? 0);
            $sisaLama = (int) ($p->sisa_angsuran ?? 0);
            $sisaBaru = (int) ($p->sisa_angsuran_percepatan ?? 0);

            // Weighted avg per pinjaman: (Σ(pokok × sisa)) / Σsisa
            $pokokTimesSisa = ($pokokLama * $sisaLama) + ($pokokBaru * $sisaBaru);
            $sisaPerPinjaman = $sisaLama + $sisaBaru;

            if ($sisaPerPinjaman > 0) {
                $pokokWeightedSum += $pokokTimesSisa;
                $sisaWeightedSum += $sisaPerPinjaman;
            }
        }

        $cicilanPokokWeightedAvg = $sisaWeightedSum > 0
            ? $pokokWeightedSum / $sisaWeightedSum
            : 0.0;

        return [
            'pinjaman_aktif_list' => $pinjamanAktifs,
            'sisa_total' => $sisaTotal,
            'cicilan_pokok_weighted_avg' => (float) $cicilanPokokWeightedAvg,
        ];
    }

    /**
     * Ringkasan data yang dibutuhkan saat proses resign:
     *   - Total simpanan per jenis (pokok, wajib, dana_sosial)
     *   - Sisa pinjaman aktif (semua pinjaman berstatus 'aktif')
     *   - Total tagihan pelunasan (gabungan angsuran biasa + angsuran_percepatan aktif)
     *   - Estimasi pengembalian neto (simpanan_pokok+wajib - tagihan, min 0)
     *
     * Read-only: tidak mengubah data apapun. Dipakai oleh ResignService::ringkasan()
     * untuk preview di modal konfirmasi sebelum admin submit.
     */
    public function ringkasanResign(): array
    {
        $simpananPokok = (float) $this->simpanan()->where('jenis', 'pokok')->sum('jumlah');
        $simpananWajib = (float) $this->simpanan()->where('jenis', 'wajib')->sum('jumlah');
        $danaSosial = (float) $this->simpanan()->where('jenis', 'dana_sosial')->sum('jumlah');
        $totalSimpananKembali = $simpananPokok + $simpananWajib;

        $pinjamanAktif = $this->pinjaman()->where('status', 'aktif')->get();
        $totalSisaPinjaman = 0.0;
        foreach ($pinjamanAktif as $p) {
            $totalSisaPinjaman += $p->sisaTotalBayarAktif();
        }

        $kembalianNeto = max(0, $totalSimpananKembali - $totalSisaPinjaman);
        $alokasiDariPokok = min($simpananPokok, max(0, $totalSisaPinjaman));
        $sisaPokokSetelahPelunasan = $simpananPokok - $alokasiDariPokok;
        $sisaWajibSetelahPelunasan = max(0, $simpananWajib - max(0, $totalSisaPinjaman - $simpananPokok));

        return [
            'simpanan' => [
                'pokok' => $simpananPokok,
                'wajib' => $simpananWajib,
                'dana_sosial' => $danaSosial,
                'total_pokok_wajib' => $totalSimpananKembali,
            ],
            'pinjaman' => [
                'jumlah_pinjaman_aktif' => $pinjamanAktif->count(),
                'sisa_tagihan' => $totalSisaPinjaman,
                'detail' => $pinjamanAktif->map(fn ($p) => [
                    'id' => $p->id,
                    'nominal_awal' => (float) $p->nominal,
                    'sisa_tagihan' => (float) $p->sisaTotalBayarAktif(),
                    'sisa_cicilan' => $p->sisaCicilanAktif(),
                ])->values()->all(),
            ],
            'estimasi_pengembalian' => [
                'tagihan_pelunasan' => $totalSisaPinjaman,
                'simpanan_total' => $totalSimpananKembali,
                'alokasi_dari_pokok' => $alokasiDariPokok,
                'kembali_pokok' => $sisaPokokSetelahPelunasan,
                'kembali_wajib' => $sisaWajibSetelahPelunasan,
                'total_dikembalikan' => $kembalianNeto,
                'dana_sosial_hangus' => $danaSosial,
                'cukup_untuk_pelunasan' => $totalSimpananKembali >= $totalSisaPinjaman,
            ],
        ];
    }

    public static function generateNoAnggota(): string
    {
        $tahun = now()->year;

        $nomorTerakhir = self::where('no_anggota', 'like', "ANG-{$tahun}-%")
            ->orderByDesc('no_anggota')
            ->value('no_anggota');

        $urutan = $nomorTerakhir
            ? ((int) substr($nomorTerakhir, -4)) + 1
            : 1;

        return sprintf('ANG-%d-%04d', $tahun, $urutan);
    }
}
