<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShuPeriode extends Model
{
    use HasFactory;

    protected $table = 'shu_periode';

    protected $fillable = [
        'tahun_buku',
        'total_shu',
        'pct_cadangan',
        'pct_jasa_simpanan',
        'pct_jasa_transaksi',
        'pct_pengurus',
        'pct_pengawas',
        'pct_pendidikan',
        'pct_sosial',
        'pct_wilker',
        'pengurus_rincian',
        'pengawas_rincian',
        'status',
        'finalized_by',
        'finalized_at',
        'diajukan_by',
        'diajukan_at',
    ];

    protected $casts = [
        'tahun_buku' => 'integer',
        'total_shu' => 'decimal:2',
        'pct_cadangan' => 'decimal:4',
        'pct_jasa_simpanan' => 'decimal:4',
        'pct_jasa_transaksi' => 'decimal:4',
        'pct_pengurus' => 'decimal:4',
        'pct_pengawas' => 'decimal:4',
        'pct_pendidikan' => 'decimal:4',
        'pct_sosial' => 'decimal:4',
        'pct_wilker' => 'decimal:4',
        'pengurus_rincian' => 'array',
        'pengawas_rincian' => 'array',
        'diajukan_at' => 'datetime',
        'finalized_at' => 'datetime',
    ];

    public const KOMPONEN = [
        'cadangan' => 'pct_cadangan',
        'jasa_simpanan' => 'pct_jasa_simpanan',
        'jasa_transaksi' => 'pct_jasa_transaksi',
        'pengurus' => 'pct_pengurus',
        'pengawas' => 'pct_pengawas',
        'pendidikan' => 'pct_pendidikan',
        'sosial' => 'pct_sosial',
        'wilker' => 'pct_wilker',
    ];

    /** 8 bawaan permanen — tidak bisa dihapus/rename, custom dilarang pakai kunci ini. */
    public const KUNCI_BAWAAN = [
        'cadangan',
        'jasa_simpanan',
        'jasa_transaksi',
        'pengurus',
        'pengawas',
        'pendidikan',
        'sosial',
        'wilker',
    ];

    public function detail(): HasMany
    {
        return $this->hasMany(ShuDetail::class, 'shu_periode_id');
    }

    /** Komponen tambahan per periode (selain 8 bawaan permanen). */
    public function customKomponen(): HasMany
    {
        return $this->hasMany(ShuKomponen::class, 'shu_periode_id')->orderBy('label');
    }

    /** Pelacakan pembayaran komponen SHU non-anggota (pengurus, pengawas, dana alokasi, custom). */
    public function pembayaranKomponen(): HasMany
    {
        return $this->hasMany(ShuKomponenPembayaran::class, 'shu_periode_id');
    }

    public function finalisator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    public function isFinal(): bool
    {
        return $this->status === 'final';
    }

    public function isDiajukan(): bool
    {
        return $this->status === 'diajukan';
    }

    public function bisaDiubah(): bool
    {
        return $this->status === 'draft';
    }

    /** Total persen 8 bawaan + custom (Excel: harus 100%). */
    public function totalPersen(): float
    {
        $total = 0.0;
        foreach (self::KOMPONEN as $kolom) {
            $total += (float) ($this->{$kolom} ?? 0);
        }
        $custom = $this->relationLoaded('customKomponen')
            ? $this->customKomponen->sum(fn ($r) => (float) $r->persen)
            : (float) ($this->customKomponen()->sum('persen') ?? 0);
        $total += $custom;

        return $total;
    }

    /**
     * Nominal per komponen = total_shu × persen (mirror kolom D sheet Komponen SHU).
     * Return flat: 8 kunci bawaan + slug custom (tanpa tabrakan, divalidasi di service).
     */
    public function nominalKomponen(): array
    {
        $total = (float) $this->total_shu;
        $hasil = [];
        foreach (self::KOMPONEN as $nama => $kolom) {
            $hasil[$nama] = round($total * (float) ($this->{$kolom} ?? 0), 2);
        }
        $rows = $this->relationLoaded('customKomponen')
            ? $this->customKomponen
            : $this->customKomponen()->get();
        foreach ($rows as $r) {
            $hasil[$r->slug] = round($total * (float) $r->persen, 2);
        }

        return $hasil;
    }

    /** Daftar custom untuk payload Inertia/export: [{slug, label, persen, nominal}]. */
    public function daftarCustom(): array
    {
        $total = (float) $this->total_shu;
        $rows = $this->relationLoaded('customKomponen')
            ? $this->customKomponen
            : $this->customKomponen()->orderBy('label')->get();

        return $rows->map(fn ($r) => [
            'slug' => $r->slug,
            'label' => $r->label,
            'persen' => (float) $r->persen,
            'nominal' => round($total * (float) $r->persen, 2),
        ])->all();
    }
}
