<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pinjaman extends Model
{
    use HasFactory;

    protected $table = 'pinjaman';

    protected $fillable = [
        'anggota_id',
        'nominal',
        'tenor_bulan',
        'persentase_bunga',
        'status',
        'sudah_pakai_privilege_reloan',
        'tanggal_pengajuan',
        'tanggal_pencairan',
        'catatan_bendahara',
        'catatan_ketua',
    ];

    protected $casts = [
        'nominal' => 'decimal:2',
        'persentase_bunga' => 'decimal:2',
        'sudah_pakai_privilege_reloan' => 'boolean',
        'tanggal_pengajuan' => 'date',
        'tanggal_pencairan' => 'date',
    ];

    public function anggota(): BelongsTo
    {
        return $this->belongsTo(Anggota::class);
    }

    public function angsuran(): HasMany
    {
        return $this->hasMany(Angsuran::class);
    }

    public function angsuranBelumBayar(): HasMany
    {
        return $this->angsuran()->where('status', 'belum_bayar');
    }

    public function sisaAngsuran(): int
    {
        return $this->angsuranBelumBayar()->count();
    }
}